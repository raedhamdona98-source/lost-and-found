<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (currentUser() !== null) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!validCsrfToken($_POST['csrf_token'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } else {
        $statement = getDatabaseConnection()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $error = 'Incorrect email or password.';
        } elseif ($user['account_status'] === 'pending') {
            $error = 'Your registration is waiting for administrator approval.';
        } elseif ($user['account_status'] === 'suspended') {
            $error = 'Your account is suspended. Please contact the administrator.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'user_id' => (int) $user['user_id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
            ];
            header('Location: dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Digital Lost & Found</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <header class="site-header"><a class="brand" href="index.php">Liwa University Lost & Found</a><a href="register.php">Create account</a></header>
    <main class="auth-wrapper">
        <section class="auth-card auth-card-small">
            <p class="eyebrow">Welcome back</p>
            <h1>Login</h1>

            <?php if (isset($_GET['registered'])): ?><div class="alert alert-success">Registration submitted. Wait for administrator approval before logging in.</div><?php endif; ?>
            <?php if ($error !== ''): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <label>Email address<input type="email" name="email" value="<?= e($email) ?>" required></label>
                <label>Password<input type="password" name="password" required></label>
                <button class="button button-primary form-submit" type="submit">Login</button>
            </form>
        </section>
    </main>
</body>
</html>
