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

$unreadCount = dashboardCount($pdo, 'SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0', ['user_id' => $user['user_id']]);
$metrics = [];
$quickActions = [];
$roleIntro = '';

if ($user['role'] === 'admin') {
    $roleIntro = 'Monitor system activity, review requests, and keep every case moving.';
    $metrics = [
        ['label' => 'Pending registrations', 'value' => dashboardCount($pdo, "SELECT COUNT(*) FROM users WHERE account_status = 'pending'"), 'icon' => 'U', 'href' => 'admin/users.php'],
        ['label' => 'Open reports', 'value' => dashboardCount($pdo, "SELECT (SELECT COUNT(*) FROM lost_items WHERE status NOT IN ('returned','closed','rejected')) + (SELECT COUNT(*) FROM found_items WHERE status NOT IN ('returned','closed','rejected'))"), 'icon' => 'R', 'href' => 'staff/reports.php'],
        ['label' => 'Pending claims', 'value' => dashboardCount($pdo, "SELECT COUNT(*) FROM claims WHERE approval_status = 'pending'"), 'icon' => 'C', 'href' => 'staff/claims.php'],
        ['label' => 'Completed returns', 'value' => dashboardCount($pdo, 'SELECT COUNT(*) FROM returns'), 'icon' => '✓', 'href' => 'staff/returns.php'],
    ];
    $quickActions = [
        ['title' => 'Registration requests', 'text' => 'Approve or reject new student accounts.', 'href' => 'admin/users.php'],
        ['title' => 'System analytics', 'text' => 'Review reports, outcomes, and activity trends.', 'href' => 'admin/analytics.php'],
        ['title' => 'Create staff account', 'text' => 'Add a Lost & Found department employee.', 'href' => 'admin/create_staff.php'],
    ];
} elseif ($user['role'] === 'staff') {
    $roleIntro = 'Review incoming reports, verify claims, and coordinate safe returns.';
    $metrics = [
        ['label' => 'Reports to review', 'value' => dashboardCount($pdo, "SELECT (SELECT COUNT(*) FROM lost_items WHERE status = 'submitted') + (SELECT COUNT(*) FROM found_items WHERE status = 'submitted')"), 'icon' => 'R', 'href' => 'staff/reports.php'],
        ['label' => 'Pending claims', 'value' => dashboardCount($pdo, "SELECT COUNT(*) FROM claims WHERE approval_status = 'pending'"), 'icon' => 'C', 'href' => 'staff/claims.php'],
        ['label' => 'Ready for collection', 'value' => dashboardCount($pdo, "SELECT COUNT(*) FROM claims WHERE approval_status = 'approved' AND pickup_date IS NOT NULL AND claim_id NOT IN (SELECT claim_id FROM returns)"), 'icon' => '✓', 'href' => 'staff/returns.php'],
        ['label' => 'Items in inventory', 'value' => dashboardCount($pdo, "SELECT COUNT(*) FROM found_items WHERE status IN ('available','matched','ready_for_collection')"), 'icon' => 'I', 'href' => 'staff/inventory.php'],
    ];
    $quickActions = [
        ['title' => 'Review reports', 'text' => 'Accept, reject, and update incoming reports.', 'href' => 'staff/reports.php'],
        ['title' => 'Verify claims', 'text' => 'Review ownership evidence and decisions.', 'href' => 'staff/claims.php'],
        ['title' => 'Confirm returns', 'text' => 'Record handovers and close completed cases.', 'href' => 'staff/returns.php'],
    ];
} else {
    $roleIntro = 'Report an item, follow your cases, and discover possible matches.';
    $metrics = [
        ['label' => 'My reports', 'value' => dashboardCount($pdo, 'SELECT (SELECT COUNT(*) FROM lost_items WHERE user_id = :lost_user) + (SELECT COUNT(*) FROM found_items WHERE finder_id = :found_user)', ['lost_user' => $user['user_id'], 'found_user' => $user['user_id']]), 'icon' => 'R', 'href' => 'my_reports.php'],
        ['label' => 'Under review', 'value' => dashboardCount($pdo, "SELECT (SELECT COUNT(*) FROM lost_items WHERE user_id = :lost_user AND status IN ('submitted','under_review')) + (SELECT COUNT(*) FROM found_items WHERE finder_id = :found_user AND status IN ('submitted','under_review'))", ['lost_user' => $user['user_id'], 'found_user' => $user['user_id']]), 'icon' => 'V', 'href' => 'my_reports.php'],
        ['label' => 'My claims', 'value' => dashboardCount($pdo, 'SELECT COUNT(*) FROM claims WHERE user_id = :user_id', ['user_id' => $user['user_id']]), 'icon' => 'C', 'href' => 'matches.php'],
        ['label' => 'Unread notifications', 'value' => $unreadCount, 'icon' => 'N', 'href' => 'notifications.php'],
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
                <a class="overview-card" href="<?= e($metric['href']) ?>"><span class="overview-icon" aria-hidden="true"><?= e($metric['icon']) ?></span><span><strong class="overview-value"><?= (int) $metric['value'] ?></strong><span class="overview-label"><?= e($metric['label']) ?></span></span><span class="overview-arrow" aria-hidden="true">→</span></a>
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
