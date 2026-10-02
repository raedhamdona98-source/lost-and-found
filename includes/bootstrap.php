<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validCsrfToken(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function requireLogin(): void
{
    if (currentUser() === null) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin(): void
{
    requireLogin();

    if ((currentUser()['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Access denied. Administrator permission is required.');
    }
}

function requireStaff(): void
{
    requireLogin();

    if (!in_array(currentUser()['role'] ?? '', ['staff', 'admin'], true)) {
        http_response_code(403);
        exit('Access denied. Staff permission is required.');
    }
}

function uploadItemImage(array $file, array &$errors): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $errors[] = 'The image could not be uploaded.';
        return null;
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        $errors[] = 'The image must not exceed 5 MB.';
        return null;
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) {
        $errors[] = 'Only JPG, PNG, and WEBP images are allowed.';
        return null;
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    $destination = __DIR__ . '/../uploads/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        $errors[] = 'The image could not be saved.';
        return null;
    }

    return 'uploads/' . $filename;
}

function createNotification(int $userId, string $title, string $message): void
{
    $statement = getDatabaseConnection()->prepare(
        'INSERT INTO notifications (user_id, title, message) VALUES (:user_id, :title, :message)'
    );
    $statement->execute(['user_id' => $userId, 'title' => $title, 'message' => $message]);
}
