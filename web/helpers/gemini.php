<?php
class GeminiHelper
{
  private const GEMINI_MODEL = 'gemini-2.5-flash';
  private const MAX_TOKENS = 2000;
  private const KNOWLEDGE_DIR = __DIR__ . '/knowledge';
  private const ENV_FILE = __DIR__ . '/../.env';

  private string $apiKey;
  private array $docs = [];

  /**
   * Constructor loads API key and knowledge documents
   */
  public function __construct()
  {
    if (file_exists(self::ENV_FILE)) {
      $lines = file(self::ENV_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

      foreach ($lines as $line) {
        // Ignore comments
        if (str_starts_with($line, '#')) {
          continue;
        }
        // Ignore lines with no '='
        if (strpos($line, '=') === false) {
          continue;
        }

        // Parse key-value pairs
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if ($key === 'GEMINI_API_KEY') {
          $this->apiKey = $value;
        }
      }
    }

    $this->loadKnowledgeFromDirectory();
  }

  /**
   * Call Gemini generateContent endpoint
   */
  private function generateWithGemini(string $prompt): array
  {
    $endpoint = sprintf(
      'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
      self::GEMINI_MODEL,
      urlencode($this->apiKey),
    );

    $payload = [
      'contents' => [['parts' => [['text' => $prompt]]]],
      'generationConfig' => [
        'maxOutputTokens' => self::MAX_TOKENS,
        'temperature' => 0.2,
      ],
    ];

    // Use cURL to make the API request
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
    $httpCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $curlError = curl_error($ch);

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
    $text = preg_replace('/^#{1,6}\s+/m', '', $text); // Remove headers
    $text = str_replace("\r", '', $text); // Normalize line endings
    return trim($text);
  }

  /**
   * Load knowledge documents from markdown files
   */
  private function loadKnowledgeFromDirectory(): void
  {
    $knowledgeDir = self::KNOWLEDGE_DIR;
    if (!is_dir($knowledgeDir)) {
      return;
    }

    $this->docs = [];
    $files = new DirectoryIterator($knowledgeDir);

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

    return pathinfo($fileName, PATHINFO_FILENAME);
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
    $prompt = $this->constructPrompt(
      [['role' => 'user', 'content' => $userMessage]],
      $this->retrieveRelevantDocs($userMessage),
    );

    $result = $this->generateWithGemini($prompt, self::MAX_TOKENS);
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

    Keep your response under 200 words, be objective and factual.

    Context:
    $context

    Summary:
    PROMPT;

    $result = $this->generateWithGemini($prompt, self::MAX_TOKENS);
    return $result['text'];
  }
}
