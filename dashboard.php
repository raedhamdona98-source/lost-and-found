<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$user = currentUser();
$pdo = getDatabaseConnection();

function dashboardCount(PDO $pdo, string $sql, array $params = []): int
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    return (int) $statement->fetchColumn();
}

function dashboardMetricIcon(string $name): string
{
    $paths = [
        'users' => '<circle cx="9" cy="8" r="3"/><path d="M3 20v-2a6 6 0 0 1 12 0v2M16 5a3 3 0 0 1 0 6M17 14a5 5 0 0 1 4 5v1"/>',
        'reports' => '<path d="M5 3h14v18H5zM8 7h8M8 11h8M8 15h5"/>',
        'claims' => '<path d="M9 11l2 2 4-5"/><circle cx="12" cy="12" r="9"/>',
        'returns' => '<path d="M9 7H5v4M5.5 10A8 8 0 1 1 5 15"/>',
        'review' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
        'inventory' => '<path d="m4 7 8-4 8 4-8 4zM4 7v10l8 4 8-4V7M12 11v10"/>',
        'collection' => '<path d="M4 12h16M12 4v16M7 7l10 10M17 7 7 17"/><circle cx="12" cy="12" r="9"/>',
    ];
    $path = $paths[$name] ?? $paths['reports'];
    return '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
}

$unreadCount = dashboardCount($pdo, 'SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0', ['user_id' => $user['user_id']]);
$metrics = [];
$quickActions = [];
$roleIntro = '';

if ($user['role'] === 'admin') {
    $roleIntro = 'Monitor system activity, review requests, and keep every case moving.';
    $metrics = [
        ['label' => 'Pending registrations', 'value' => dashboardCount($pdo, "SELECT COUNT(*) FROM users WHERE account_status = 'pending'"), 'icon' => 'users', 'href' => 'admin/users.php'],
        ['label' => 'Open reports', 'value' => dashboardCount($pdo, "SELECT (SELECT COUNT(*) FROM lost_items WHERE status NOT IN ('returned','closed','rejected')) + (SELECT COUNT(*) FROM found_items WHERE status NOT IN ('returned','closed','rejected'))"), 'icon' => 'reports', 'href' => 'staff/reports.php'],
        ['label' => 'Pending claims', 'value' => dashboardCount($pdo, "SELECT COUNT(*) FROM claims WHERE approval_status = 'pending'"), 'icon' => 'claims', 'href' => 'staff/claims.php'],
        ['label' => 'Completed returns', 'value' => dashboardCount($pdo, 'SELECT COUNT(*) FROM returns'), 'icon' => 'returns', 'href' => 'staff/returns.php'],
    ];
    $quickActions = [
        ['title' => 'Registration requests', 'text' => 'Approve or reject new student accounts.', 'href' => 'admin/users.php'],
        ['title' => 'System analytics', 'text' => 'Review reports, outcomes, and activity trends.', 'href' => 'admin/analytics.php'],
        ['title' => 'Create staff account', 'text' => 'Add a Lost & Found department employee.', 'href' => 'admin/create_staff.php'],
    ];
} elseif ($user['role'] === 'staff') {
    $roleIntro = 'Review incoming reports, verify claims, and coordinate safe returns.';
    $metrics = [
        ['label' => 'Reports to review', 'value' => dashboardCount($pdo, "SELECT (SELECT COUNT(*) FROM lost_items WHERE status = 'submitted') + (SELECT COUNT(*) FROM found_items WHERE status = 'submitted')"), 'icon' => 'review', 'href' => 'staff/reports.php'],
        ['label' => 'Pending claims', 'value' => dashboardCount($pdo, "SELECT COUNT(*) FROM claims WHERE approval_status = 'pending'"), 'icon' => 'claims', 'href' => 'staff/claims.php'],
        ['label' => 'Ready for collection', 'value' => dashboardCount($pdo, "SELECT COUNT(*) FROM claims WHERE approval_status = 'approved' AND pickup_date IS NOT NULL AND claim_id NOT IN (SELECT claim_id FROM returns)"), 'icon' => 'collection', 'href' => 'staff/returns.php'],
        ['label' => 'Items in inventory', 'value' => dashboardCount($pdo, "SELECT COUNT(*) FROM found_items WHERE status IN ('available','matched','ready_for_collection')"), 'icon' => 'inventory', 'href' => 'staff/inventory.php'],
    ];
    $quickActions = [
        ['title' => 'Review reports', 'text' => 'Accept, reject, and update incoming reports.', 'href' => 'staff/reports.php'],
        ['title' => 'Verify claims', 'text' => 'Review ownership evidence and decisions.', 'href' => 'staff/claims.php'],
        ['title' => 'Confirm returns', 'text' => 'Record handovers and close completed cases.', 'href' => 'staff/returns.php'],
    ];
} else {
    $roleIntro = 'Report an item, follow your cases, and discover possible matches.';
    $metrics = [
        ['label' => 'My reports', 'value' => dashboardCount($pdo, 'SELECT (SELECT COUNT(*) FROM lost_items WHERE user_id = :lost_user) + (SELECT COUNT(*) FROM found_items WHERE finder_id = :found_user)', ['lost_user' => $user['user_id'], 'found_user' => $user['user_id']]), 'icon' => 'reports', 'href' => 'my_reports.php'],
        ['label' => 'Under review', 'value' => dashboardCount($pdo, "SELECT (SELECT COUNT(*) FROM lost_items WHERE user_id = :lost_user AND status IN ('submitted','under_review')) + (SELECT COUNT(*) FROM found_items WHERE finder_id = :found_user AND status IN ('submitted','under_review'))", ['lost_user' => $user['user_id'], 'found_user' => $user['user_id']]), 'icon' => 'review', 'href' => 'my_reports.php'],
        ['label' => 'My claims', 'value' => dashboardCount($pdo, 'SELECT COUNT(*) FROM claims WHERE user_id = :user_id', ['user_id' => $user['user_id']]), 'icon' => 'claims', 'href' => 'matches.php'],
        ['label' => 'Unread notifications', 'value' => $unreadCount, 'icon' => 'bell', 'href' => 'notifications.php'],
    ];
    $quickActions = [
        ['title' => 'Report a lost item', 'text' => 'Tell us what you lost and where it happened.', 'href' => 'report_lost.php'],
        ['title' => 'Report a found item', 'text' => 'Register an item you found on campus.', 'href' => 'report_found.php'],
        ['title' => 'Search all items', 'text' => 'Browse reports and look for a possible match.', 'href' => 'search.php'],
    ];
}

$notificationStatement = $pdo->prepare('SELECT title, message, is_read, created_at FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 4');
$notificationStatement->execute(['user_id' => $user['user_id']]);
$recentNotifications = $notificationStatement->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Digital Lost &amp; Found</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header class="site-header app-topbar">
        <a class="brand brand-with-mark" href="dashboard.php"><span class="brand-mark">LU</span>Liwa University Lost &amp; Found</a>
        <nav><a href="notifications.php">Notifications<?= $unreadCount ? ' (' . $unreadCount . ')' : '' ?></a><a href="profile.php"><?= e($user['name']) ?></a><a class="button button-outline" href="logout.php">Logout</a></nav>
    </header>

    <main class="dashboard dashboard-shell">
        <section class="dashboard-heading">
            <div><p class="eyebrow"><?= e(ucfirst($user['role'])) ?> dashboard</p><h1>Welcome back, <?= e($user['name']) ?></h1><p><?= e($roleIntro) ?></p></div>
            <span class="dashboard-date"><?= e(date('l, F j')) ?></span>
        </section>

        <section class="overview-grid" aria-label="Account overview">
            <?php foreach ($metrics as $metric): ?>
                <a class="overview-card" href="<?= e($metric['href']) ?>"><span class="overview-icon" aria-hidden="true"><?= dashboardMetricIcon($metric['icon']) ?></span><span><strong class="overview-value"><?= (int) $metric['value'] ?></strong><span class="overview-label"><?= e($metric['label']) ?></span></span><span class="overview-arrow" aria-hidden="true">→</span></a>
            <?php endforeach; ?>
        </section>

        <section class="dashboard-panels">
            <div class="dashboard-panel">
                <div class="panel-heading"><div><p class="eyebrow">Quick access</p><h2>What would you like to do?</h2></div></div>
                <div class="quick-action-list">
                    <?php foreach ($quickActions as $index => $action): ?>
                        <a class="quick-action" href="<?= e($action['href']) ?>"><span class="quick-action-number">0<?= $index + 1 ?></span><span><strong><?= e($action['title']) ?></strong><small><?= e($action['text']) ?></small></span><span aria-hidden="true">→</span></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="dashboard-panel">
                <div class="panel-heading"><div><p class="eyebrow">Latest updates</p><h2>Recent notifications</h2></div><a href="notifications.php">View all</a></div>
                <?php if (!$recentNotifications): ?>
                    <div class="empty-activity"><span>✓</span><strong>You're all caught up</strong><p>New updates about your reports and claims will appear here.</p></div>
                <?php else: ?>
                    <div class="activity-list">
                        <?php foreach ($recentNotifications as $notification): ?>
                            <a class="activity-item<?= $notification['is_read'] ? '' : ' unread' ?>" href="notifications.php"><span class="activity-dot"></span><span><strong><?= e($notification['title']) ?></strong><small><?= e($notification['message']) ?></small></span><time><?= e(date('M j', strtotime($notification['created_at']))) ?></time></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>
