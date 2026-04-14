<?php

declare(strict_types=1);

require_once __DIR__ . '/init.php';
require_once __DIR__ . '/../database/repository/LikesRepository.php';

$likeRepository = new LikesRepository($pdo);
$liker_id = (int) $_SESSION['user_id'];
$liked_id = isset($_POST['liked_user_id']) ? (int) $_POST['liked_user_id'] : null;

if ($liked_id !== null && $liked_id > 0) {
  try {
    $likeRepository->createLike($liker_id, $liked_id);
    header('Location: /search.php?success=1');
  } catch (DuplicateLikeException $exception) {
    header('Location: /search.php?error=like_exists');
  } catch (MatchError $exception) {
    header('Location: /search.php?match=1');
  } catch (Throwable $exception) {
    header('Location: /search.php?error=like_failed');
  }

  exit();
}

header('Location: /search.php?error=invalid_user');
exit();
