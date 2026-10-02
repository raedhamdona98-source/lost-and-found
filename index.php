<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$databaseConnected = false;

try {
    $pdo = getDatabaseConnection();
    $databaseConnected = true;
} catch (PDOException $exception) {
    error_log($exception->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Lost & Found</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <a class="brand university-brand" href="index.php">
            <img src="assets/images/liwa-university-logo.png" alt="Liwa University">
            <span>Lost & Found</span>
        </a>
        <nav>
            <a href="search.php">Search Items</a>
            <a href="report_lost.php">Report Lost Item</a>
            <a href="report_found.php">Report Found Item</a>
            <a class="button button-outline" href="login.php">Login</a>
        </nav>
    </header>

    <main>
        <section class="hero">
            <div class="hero-content">
                <span class="eyebrow">Digital Lost-and-Found Management System</span>
                <h1>Find what you lost.<br>Return what you found.</h1>
                <p>A secure university platform for reporting, matching, verifying, and returning lost items to their owners.</p>
                <div class="hero-actions">
                    <a class="button button-primary" href="register.php">Create Account</a>
                    <a class="button button-secondary" href="login.php">Login</a>
                </div>
            </div>

            <div class="hero-visual">
                <img class="campus-image" src="assets/images/liwa-university-campus.jpg" alt="Liwa University campus building">
                <div class="status-card">
                    <div class="status-icon">✓</div>
                    <div>
                        <h2>System Ready</h2>
                        <span class="connection-status <?= $databaseConnected ? 'connected' : 'disconnected' ?>">
                            <?= $databaseConnected ? 'Database connected' : 'Database connection failed' ?>
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <section class="features" aria-label="System features">
            <article><span>01</span><h3>Submit a Report</h3><p>Register a lost or found item with its details, location, date, and photo.</p></article>
            <article><span>02</span><h3>Search & Match</h3><p>Search reports and identify possible matches between lost and found items.</p></article>
            <article><span>03</span><h3>Verify & Return</h3><p>Staff verify ownership, schedule collection, and close the case securely.</p></article>
        </section>
    </main>

    <footer><img src="assets/images/liwa-university-logo.png" alt="Liwa University"><p>&copy; <?= date('Y') ?> Liwa University — Graduation Project</p></footer>
</body>
</html>
