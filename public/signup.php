<?php
declare(strict_types=1);
require dirname(__DIR__) . '/src/bootstrap.php';

csrf_check();

$back = (string) ($_POST['back'] ?? '/');
if (!str_starts_with($back, '/') || str_starts_with($back, '//')) {
    $back = '/';
}

$email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
if (!$email) {
    flash('That email address doesn’t look right. Try again?');
    redirect($back, 303);
}

try {
    q('INSERT INTO signups (email, created_at) VALUES (?, ?)', [strtolower($email), now()]);
} catch (PDOException) {
    // Already on the list.
}

flash('You’re on the list. We’ll email you before the next drop.');
redirect($back, 303);
