<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pdo = getDatabaseConnection();
$adminExists = (bool) $pdo->query("SELECT 1 FROM users WHERE role = 'admin' LIMIT 1")->fetchColumn();
$errors = [];
$success = false;

if (!$adminExists && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');

    if (!validCsrfToken($_POST['csrf_token'] ?? null)) $errors[] = 'Your session expired. Please try again.';
    if ($name === '') $errors[] = 'Administrator name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email address.';
    if (strlen($password) < 8) $errors[] = 'Password must contain at least 8 characters.';
    if ($password !== $confirmation) $errors[] = 'Password confirmation does not match.';

    if ($errors === []) {
        $check = $pdo->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
        $check->execute(['email' => $email]);
        if ($check->fetch()) {
            $errors[] = 'This email is already registered.';
        } else {
            $statement = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, account_status) VALUES (:name, :email, :password_hash, 'admin', 'active')");
            $statement->execute(['name' => $name, 'email' => $email, 'password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
            $success = true;
            $adminExists = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Administrator Setup</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body class="auth-page">
<header class="site-header"><a class="brand" href="index.php">Liwa University Lost & Found</a></header>
<main class="auth-wrapper"><section class="auth-card auth-card-small">
<?php if ($success): ?>
    <p class="eyebrow">Setup complete</p><h1>Administrator created</h1><div class="alert alert-success">The administrator account is active and ready.</div><a class="button button-primary" href="login.php">Go to Login</a>
<?php elseif ($adminExists): ?>
    <p class="eyebrow">Protected setup</p><h1>Administrator exists</h1><div class="alert alert-success">The initial administrator account has already been created. This setup page is now locked.</div><a class="button button-primary" href="login.php">Go to Login</a>
<?php else: ?>
    <p class="eyebrow">One-time setup</p><h1>Create administrator</h1><p class="form-intro">This form becomes unavailable after the first administrator is created.</p>
    <?php if ($errors !== []): ?><div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><label>Administrator name<input type="text" name="name" required></label><label>Email address<input type="email" name="email" required></label><label>Password<input type="password" name="password" minlength="8" required></label><label>Confirm password<input type="password" name="password_confirmation" minlength="8" required></label><button class="button button-primary form-submit" type="submit">Create Administrator</button></form>
<?php endif; ?>
</section></main></body></html>
