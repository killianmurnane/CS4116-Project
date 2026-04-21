<?php
class GeminiHelper
{
  private const GEMINI_MODEL = 'gemini-2.5-flash';
  private const CHAT_MAX_OUTPUT_TOKENS = 2000;
  private const ADMIN_MAX_OUTPUT_TOKENS = 2000;
  private const KNOWLEDGE_DIR = __DIR__ . '/knowledge';

  private string $apiKey;
  private array $docs = [];

  public function __construct(?string $apiKey = null)
  {
    // Load .env file if it exists
    $envPath = dirname(__DIR__) . '/.env';
    if ($apiKey === null && file_exists($envPath)) {
      $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
      foreach ($lines as $line) {
        if (str_starts_with($line, '#')) {
          continue;
        }
        if (strpos($line, '=') === false) {
          continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        putenv("{$key}={$value}");
        if (!isset($_ENV[$key])) {
          $_ENV[$key] = $value;
        }
      }
    }

    $this->apiKey =
      $apiKey ??
      (string) ($_ENV['GEMINI_API_KEY'] ??
        ($_SERVER['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY') ?: ''));
    if (empty($this->apiKey)) {
      throw new RuntimeException(
        'GEMINI_API_KEY not set in environment. Please check your .env file.',
      );
    }

    $this->loadKnowledgeFromDirectory();
  }

  /**
   * Call Gemini generateContent endpoint
   */
  private function generateWithGemini(
    string $prompt,
    int $maxOutputTokens = self::CHAT_MAX_OUTPUT_TOKENS,
  ): array {
    $endpoint = sprintf(
      'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
      self::GEMINI_MODEL,
      urlencode($this->apiKey),
    );

    $payload = [
      'contents' => [['parts' => [['text' => $prompt]]]],
      'generationConfig' => [
        'maxOutputTokens' => $maxOutputTokens,
        'temperature' => 0.2,
      ],
    ];

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
      CURLOPT_POST => true,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
      CURLOPT_POSTFIELDS => json_encode($payload),
      CURLOPT_CONNECTTIMEOUT => 15,
      CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
      throw new RuntimeException("Gemini API curl error: $curlError");
    }

    $decoded = json_decode((string) $response, true);

    if ($httpCode !== 200) {
      $message = $decoded['error']['message'] ?? "Gemini request failed with HTTP $httpCode";
      throw new RuntimeException($message);
    }

    $candidate = $decoded['candidates'][0] ?? null;
    if (!$candidate) {
      throw new RuntimeException('Gemini returned no candidates.');
    }

    $parts = $candidate['content']['parts'] ?? [];
    $text = implode('', array_map(static fn($p) => $p['text'] ?? '', $parts));
    $text = trim((string) $text);

    if (empty($text)) {
      throw new RuntimeException('Gemini returned an empty response.');
    }

    return [
      'text' => $text,
      'finishReason' => $candidate['finishReason'] ?? null,
    ];
  }

  /**
   * Tokenize text into meaningful words
   */
  private function tokenize(string $text): array
  {
    $stopWords = ['the', 'and', 'for', 'with', 'that', 'this', 'are', 'you', 'your', 'from'];

    $tokens = preg_split('/\s+/', strtolower(trim((string) $text)), -1, PREG_SPLIT_NO_EMPTY);
    return array_filter(
      $tokens,
      static fn($t) => strlen($t) > 2 && !in_array($t, $stopWords, true),
    );
  }

  /**
   * Strip markdown formatting
   */
  private function cleanMarkdown(string $markdown): string
  {
    $text = (string) $markdown;
    $text = preg_replace('/^#{1,6}\s+/m', '', $text);
    $text = preg_replace('/`{1,3}([^`]*)`{1,3}/', '$1', $text);
    $text = preg_replace('/\*\*([^*]+)\*\*/', '$1', $text);
    $text = preg_replace('/\*([^*]+)\*/', '$1', $text);
    $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '$1', $text);
    $text = preg_replace('/^>\s?/m', '', $text);
    $text = preg_replace('/^[-*+]\s+/m', '', $text);
    $text = str_replace("\r", '', $text);
    $text = preg_replace('/\n{2,}/', "\n", $text);
    return trim($text);
  }

  /**
   * Load knowledge documents from markdown files
   */
  private function loadKnowledgeFromDirectory(string $dir = self::KNOWLEDGE_DIR): void
  {
    if (!is_dir($dir)) {
      return;
    }

    $this->docs = [];
    $files = new DirectoryIterator($dir);

    foreach ($files as $file) {
      if (!$file->isFile() || $file->getExtension() !== 'md') {
        continue;
      }

      $content = file_get_contents((string) $file->getRealPath());
      if ($content === false || empty(trim($content))) {
        continue;
      }

      $title = $this->extractTitle($file->getFilename(), $content);
      $cleanText = $this->cleanMarkdown($content);

      if (empty($cleanText)) {
        continue;
      }

      $this->docs[] = [
        'id' => count($this->docs) + 1,
        'title' => $title,
        'content' => $cleanText,
        'tokens' => $this->tokenize($cleanText),
      ];
    }
  }

  /**
   * Extract title from markdown
   */
  private function extractTitle(string $fileName, string $markdown): string
  {
    if (preg_match('/^#\s+(.+)$/m', $markdown, $m)) {
      return trim($m[1]);
    }

    $base = pathinfo($fileName, PATHINFO_FILENAME);
    return ucwords(str_replace(['-', '_'], ' ', $base));
  }

  /**
   * Retrieve relevant documents based on user message
   */
  private function retrieveRelevantDocs(string $userMessage, int $limit = 3): array
  {
    $userTokens = array_flip($this->tokenize($userMessage));
    $scored = [];

    foreach ($this->docs as $doc) {
      $overlap = 0;
      foreach ($doc['tokens'] as $token) {
        $overlap += isset($userTokens[$token]) ? 1 : 0;
      }

      if ($overlap > 0) {
        $scored[] = ['doc' => $doc, 'score' => $overlap];
      }
    }

    usort($scored, static fn($a, $b) => $b['score'] <=> $a['score']);
    return array_slice(array_map(static fn($s) => $s['doc'], $scored), 0, $limit);
  }

  /**
   * Construct prompt with context and docs
   */
  private function constructPrompt(array $history, array $relevantDocs): string
  {
    $context = 'You are a helpful GymDate support assistant. Keep responses concise and friendly.';

    if (!empty($relevantDocs)) {
      $context .= "\n\nRelevant site information:\n";
      foreach ($relevantDocs as $doc) {
        $context .= "\n**{$doc['title']}:**\n{$doc['content']}\n";
      }
    }

    $context .= "\n\nConversation history:";
    foreach ($history as $turn) {
      $role = $turn['role'];
      $msg = $turn['content'];
      $context .= "\n$role: $msg";
    }

    $context .= "\n\nassistant: ";
    return $context;
  }

  /**
   * Generate a support chat response
   */
  public function generateResponse(string $userMessage): string
  {
    // Simple single-turn response (no session state needed for web handlers)
    $prompt = $this->constructPrompt(
      [['role' => 'user', 'content' => $userMessage]],
      $this->retrieveRelevantDocs($userMessage),
    );

    $result = $this->generateWithGemini($prompt, self::CHAT_MAX_OUTPUT_TOKENS);
    return $result['text'];
  }

  /**
   * Generate an admin report overview
   */
  public function generateAdminResponse(string $context): string
  {
    $prompt = <<<PROMPT
    You are an expert content moderator for a fitness dating platform.
    Analyze the following user messages and moderation context, then provide a brief summary that answers:
    1. Is this conversation problematic?
    2. What is the issue (if any)?
    3. Recommended action.

    Keep your response under 150 words, be objective and factual.

    Context:
    $context

    Summary:
    PROMPT;

    $result = $this->generateWithGemini($prompt, self::ADMIN_MAX_OUTPUT_TOKENS);
    return $result['text'];
  }
}
