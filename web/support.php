<?php

declare(strict_types=1);

require __DIR__ . '/helpers/init.php';
require __DIR__ . '/helpers/auth.php';

requireLogin();

if (!isset($_SESSION['support_chat_messages']) || !is_array($_SESSION['support_chat_messages'])) {
  $_SESSION['support_chat_messages'] = [];
}

$chatMessages = $_SESSION['support_chat_messages'];
$givenName = trim((string) ($_SESSION['user_given'] ?? ''));
$welcomeName = $givenName !== '' ? $givenName : 'there';
?>
<!DOCTYPE html>
<html>
    <head>
        <!-- CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
        <link rel="stylesheet" href="css/base.css" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@latest/css/all.min.css">
        <link rel="stylesheet" href="css/support.css" />
        <!-- Other -->
        <title>GymDate</title>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    </head>
    <body>
        <div class="container">
            <aside class="sidebar">
                <h2 class="sidebar-title">GymDate</h2>
                <nav class="menu" aria-label="Main menu">
                    <a class="menu-item" href="/">Home</a>
                    <a class="menu-item" href="/message.php">Messages</a>
                    <a class="menu-item" href="/search.php">Search</a>
                    <a class="menu-item" href="/profile.php">Profile</a>
                    <a class="menu-item active" href="#">Support</a>
                    <?php if (isAdmin()): ?>
                      <a class="menu-item" href="/admin.php">Admin</a>
                    <?php endif; ?>
                    <a class="menu-item" href="/helpers/auth.php?action=logout">Logout</a>
                </nav>
            </aside>

			<main class="content">
				<section class="top-rectangle p-4 mb-4">
					<div class="w-100">
						<p class="text-uppercase text-muted mb-1 small">Support</p>
						<h3 class="mb-2">Need help, <?= htmlspecialchars($welcomeName) ?>?</h3>
						<p class="text-muted mb-0">Ask the support assistant about how GymDate works, including profiles, matching, messaging, and account features.</p>
					</div>
				</section>

				<section class="split-panel p-4 support-shell">
					<div>
						<h5 class="mb-1">Support Chat</h5>
						<p class="text-muted mb-0">This assistant uses the current site summaries to answer common questions.</p>
					</div>

					<div id="support-messages" class="support-messages" aria-live="polite">
						<?php if (empty($chatMessages)): ?>
							<div class="support-bubble support-bubble-assistant">Hi! I can help with GymDate features like search, matches, messaging, and profile setup. What would you like to know?</div>
						<?php else: ?>
							<?php foreach ($chatMessages as $message): ?>
								<div class="support-bubble <?= ($message['role'] ?? '') === 'user'
          ? 'support-bubble-user'
          : 'support-bubble-assistant' ?>"><?= nl2br(
  htmlspecialchars($message['content'] ?? ''),
) ?></div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>

					<form id="support-form" class="w-100">
						<div id="support-error" class="alert alert-danger d-none" role="alert"></div>
						<div class="support-actions">
							<input id="support-message" class="form-control" type="text" name="message" placeholder="Ask about the site..." required />
							<button id="support-submit" class="btn btn-dark" type="submit">Send</button>
							<button id="support-reset" class="btn btn-outline-secondary" type="button">Reset</button>
						</div>
					</form>
				</section>
			</main>
		</div>
		<script src="scripts/support.js"></script>
	</body>
</html>
