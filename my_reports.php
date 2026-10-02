<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$pdo = getDatabaseConnection();
$userId = currentUser()['user_id'];
$flash = pullFlash();

$lostStatement = $pdo->prepare(
    'SELECT l.lost_id AS report_id, l.item_name, l.description, l.specific_location,
            l.lost_date AS incident_date, l.image_path, l.status, l.created_at,
            c.category_name, b.building_name
     FROM lost_items l
     JOIN categories c ON c.category_id = l.category_id
     LEFT JOIN buildings b ON b.building_id = l.building_id
     WHERE l.user_id = :user_id ORDER BY l.created_at DESC'
);
$lostStatement->execute(['user_id' => $userId]);
$lostReports = $lostStatement->fetchAll();

$foundStatement = $pdo->prepare(
    'SELECT f.found_id AS report_id, f.item_name, f.description, f.specific_location,
            f.found_date AS incident_date, f.image_path, f.status, f.created_at,
            c.category_name, b.building_name
     FROM found_items f
     JOIN categories c ON c.category_id = f.category_id
     LEFT JOIN buildings b ON b.building_id = f.building_id
     WHERE f.finder_id = :user_id ORDER BY f.created_at DESC'
);
$foundStatement->execute(['user_id' => $userId]);
$foundReports = $foundStatement->fetchAll();

function statusLabel(string $status): string
{
    return ucwords(str_replace('_', ' ', $status));
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>My Reports</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body>
<header class="site-header"><a class="brand" href="dashboard.php">Liwa University Lost & Found</a><nav><a href="dashboard.php">Dashboard</a><a class="button button-outline" href="logout.php">Logout</a></nav></header>
<main class="reports-page">
    <p class="eyebrow">Status tracking</p><h1>My Reports</h1><p class="form-intro">Follow the progress of your lost and found item reports.</p><?php if($flash):?><div class="alert alert-<?=e($flash['type'])?>"><?=e($flash['message'])?></div><?php endif;?>
    <div class="report-tabs"><a href="#lost">Lost Items (<?= count($lostReports) ?>)</a><a href="#found">Found Items (<?= count($foundReports) ?>)</a></div>

    <section id="lost"><div class="section-heading"><h2>Lost Item Reports</h2><a class="button button-primary" href="report_lost.php">New Lost Report</a></div>
    <?php if ($lostReports === []): ?><div class="empty-state">You have not submitted a lost item report.</div><?php else: ?><div class="report-grid"><?php foreach ($lostReports as $report): ?>
        <article class="report-card"><?php if ($report['image_path']): ?><img src="<?= e($report['image_path']) ?>" alt="<?= e($report['item_name']) ?>"><?php else: ?><div class="image-placeholder">No photo</div><?php endif; ?><div class="report-card-body"><div class="report-meta"><span>Lost #<?= (int)$report['report_id'] ?></span><span class="status status-<?= e($report['status']) ?>"><?= e(statusLabel($report['status'])) ?></span></div><h3><?= e($report['item_name']) ?></h3><p><?= e($report['category_name']) ?> · <?= e($report['building_name'] ?? 'Location not specified') ?></p><p><?= e($report['description']) ?></p><small>Lost on <?= e($report['incident_date']) ?></small><a class="text-link" href="report_details.php?type=lost&id=<?=(int)$report['report_id']?>">View details →</a></div></article>
    <?php endforeach; ?></div><?php endif; ?></section>

    <section id="found"><div class="section-heading"><h2>Found Item Reports</h2><a class="button button-primary" href="report_found.php">New Found Report</a></div>
    <?php if ($foundReports === []): ?><div class="empty-state">You have not submitted a found item report.</div><?php else: ?><div class="report-grid"><?php foreach ($foundReports as $report): ?>
        <article class="report-card"><?php if ($report['image_path']): ?><img src="<?= e($report['image_path']) ?>" alt="<?= e($report['item_name']) ?>"><?php else: ?><div class="image-placeholder">No photo</div><?php endif; ?><div class="report-card-body"><div class="report-meta"><span>Found #<?= (int)$report['report_id'] ?></span><span class="status status-<?= e($report['status']) ?>"><?= e(statusLabel($report['status'])) ?></span></div><h3><?= e($report['item_name']) ?></h3><p><?= e($report['category_name']) ?> · <?= e($report['building_name'] ?? 'Location not specified') ?></p><p><?= e($report['description']) ?></p><small>Found on <?= e($report['incident_date']) ?></small><a class="text-link" href="report_details.php?type=found&id=<?=(int)$report['report_id']?>">View details →</a></div></article>
    <?php endforeach; ?></div><?php endif; ?></section>
</main></body></html>
