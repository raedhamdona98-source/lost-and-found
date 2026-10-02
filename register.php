<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$errors = [];
$values = ['name' => '', 'student_id' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $field => $value) {
        $values[$field] = trim((string) ($_POST[$field] ?? ''));
    }
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

    if (!validCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    }
    if ($values['name'] === '') {
        $errors[] = 'Full name is required.';
    }
    if ($values['student_id'] === '') {
        $errors[] = 'Student ID is required.';
    }
    if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must contain at least 8 characters.';
    }
    if ($password !== $passwordConfirmation) {
        $errors[] = 'Password confirmation does not match.';
    }

    if ($errors === []) {
        $pdo = getDatabaseConnection();
        $check = $pdo->prepare('SELECT user_id FROM users WHERE email = :email OR student_id = :student_id LIMIT 1');
        $check->execute(['email' => $values['email'], 'student_id' => $values['student_id']]);

        if ($check->fetch()) {
            $errors[] = 'The email or Student ID is already registered.';
        } else {
            $statement = $pdo->prepare(
                'INSERT INTO users (name, student_id, email, phone, password_hash, role, account_status)
                 VALUES (:name, :student_id, :email, :phone, :password_hash, :role, :account_status)'
            );
            $statement->execute([
                'name' => $values['name'],
                'student_id' => $values['student_id'],
                'email' => $values['email'],
                'phone' => $values['phone'] !== '' ? $values['phone'] : null,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'student',
                'account_status' => 'pending',
            ]);

            header('Location: login.php?registered=1');
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
    <title>Create Account | Digital Lost & Found</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <header class="site-header"><a class="brand" href="index.php">Liwa University Lost & Found</a><a href="login.php">Already registered? Login</a></header>
    <main class="auth-wrapper">
        <section class="auth-card">
            <p class="eyebrow">Student registration</p>
            <h1>Create your account</h1>
            <p class="form-intro">Your registration will be reviewed by the system administrator.</p>

            <?php if ($errors !== []): ?>
                <div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>

            <form method="post" novalidate>
                <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                <div class="form-grid">
                    <label>Full name<input type="text" name="name" value="<?= e($values['name']) ?>" required></label>
                    <label>Student ID<input type="text" name="student_id" value="<?= e($values['student_id']) ?>" required></label>
                    <label>Email address<input type="email" name="email" value="<?= e($values['email']) ?>" required></label>
                    <label>Phone number<input type="tel" name="phone" value="<?= e($values['phone']) ?>"></label>
                    <label>Password<input type="password" name="password" minlength="8" required></label>
                    <label>Confirm password<input type="password" name="password_confirmation" minlength="8" required></label>
                </div>
                <button class="button button-primary form-submit" type="submit">Submit Registration</button>
            </form>
        </section>
    </main>
</body>
</html>
