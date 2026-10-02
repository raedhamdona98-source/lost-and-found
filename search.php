<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$pdo = getDatabaseConnection();
$categories = $pdo->query('SELECT category_id, category_name FROM categories ORDER BY category_name')->fetchAll();
$buildings = $pdo->query('SELECT building_id, building_name FROM buildings ORDER BY building_name')->fetchAll();

$keyword = trim((string) ($_GET['keyword'] ?? ''));
$reportType = (string) ($_GET['type'] ?? 'all');
$categoryId = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT) ?: null;
$buildingId = filter_input(INPUT_GET, 'building_id', FILTER_VALIDATE_INT) ?: null;
if (!in_array($reportType, ['all', 'lost', 'found'], true)) $reportType = 'all';

$parts = [];
$parameters = [];

if ($reportType === 'all' || $reportType === 'lost') {
    $parts[] = "SELECT 'lost' AS report_type, l.lost_id AS report_id, l.item_name, l.description,
        l.specific_location, l.lost_date AS incident_date, l.image_path, l.status,
        c.category_name, b.building_name, l.category_id, l.building_id
        FROM lost_items l JOIN categories c ON c.category_id=l.category_id
        LEFT JOIN buildings b ON b.building_id=l.building_id
        WHERE l.status NOT IN ('closed','rejected')";
}
if ($reportType === 'all' || $reportType === 'found') {
    $parts[] = "SELECT 'found' AS report_type, f.found_id AS report_id, f.item_name, f.description,
        f.specific_location, f.found_date AS incident_date, f.image_path, f.status,
        c.category_name, b.building_name, f.category_id, f.building_id
        FROM found_items f JOIN categories c ON c.category_id=f.category_id
        LEFT JOIN buildings b ON b.building_id=f.building_id
        WHERE f.status NOT IN ('closed','rejected')";
}

$sql = 'SELECT * FROM (' . implode(' UNION ALL ', $parts) . ') reports WHERE 1=1';
if ($keyword !== '') {
    $sql .= ' AND (item_name LIKE :keyword OR description LIKE :keyword OR specific_location LIKE :keyword)';
    $parameters['keyword'] = '%' . $keyword . '%';
}
if ($categoryId) {
    $sql .= ' AND category_id = :category_id';
    $parameters['category_id'] = $categoryId;
}
if ($buildingId) {
    $sql .= ' AND building_id = :building_id';
    $parameters['building_id'] = $buildingId;
}
$countStatement = $pdo->prepare('SELECT COUNT(*) FROM (' . $sql . ') filtered_reports');
$countStatement->execute($parameters);
$totalResults = (int) $countStatement->fetchColumn();
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$pager = pagination($totalResults, $page, 9);
$sql .= " ORDER BY incident_date DESC, report_id DESC LIMIT {$pager['per_page']} OFFSET {$pager['offset']}";
$statement = $pdo->prepare($sql);
$statement->execute($parameters);
$results = $statement->fetchAll();

function searchStatusLabel(string $status): string
{
    return ucwords(str_replace('_', ' ', $status));
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Search Items</title><link rel="stylesheet" href="assets/css/style.css"></head><body>
<header class="site-header"><a class="brand" href="dashboard.php">Liwa University Lost & Found</a><nav><a href="dashboard.php">Dashboard</a><a href="my_reports.php">My Reports</a><a class="button button-outline" href="logout.php">Logout</a></nav></header>
<main class="reports-page"><p class="eyebrow">System search</p><h1>Search Items</h1><p class="form-intro">Search current lost and found reports using any combination of filters.</p>
<form class="search-panel" method="get"><label>Keyword<input name="keyword" value="<?= e($keyword) ?>" placeholder="Phone, earphones, black..."></label><label>Report type<select name="type"><option value="all" <?= $reportType==='all'?'selected':'' ?>>All reports</option><option value="lost" <?= $reportType==='lost'?'selected':'' ?>>Lost items</option><option value="found" <?= $reportType==='found'?'selected':'' ?>>Found items</option></select></label><label>Category<select name="category_id"><option value="">All categories</option><?php foreach ($categories as $category): ?><option value="<?= (int)$category['category_id'] ?>" <?= $categoryId===(int)$category['category_id']?'selected':'' ?>><?= e($category['category_name']) ?></option><?php endforeach; ?></select></label><label>Building<select name="building_id"><option value="">All buildings</option><?php foreach ($buildings as $building): ?><option value="<?= (int)$building['building_id'] ?>" <?= $buildingId===(int)$building['building_id']?'selected':'' ?>><?= e($building['building_name']) ?></option><?php endforeach; ?></select></label><button class="button button-primary" type="submit">Search</button><a class="button button-secondary" href="search.php">Clear</a></form>
<div class="section-heading"><h2><?= $totalResults ?> result<?= $totalResults===1?'':'s' ?></h2></div>
<?php if ($results === []): ?><div class="empty-state">No reports match your search.</div><?php else: ?><div class="report-grid"><?php foreach ($results as $report): ?><article class="report-card"><?php if ($report['image_path']): ?><img src="<?= e($report['image_path']) ?>" alt="<?= e($report['item_name']) ?>"><?php else: ?><div class="image-placeholder">No photo</div><?php endif; ?><div class="report-card-body"><div class="report-meta"><span><?= e(ucfirst($report['report_type'])) ?> #<?= (int)$report['report_id'] ?></span><span class="status status-<?= e($report['status']) ?>"><?= e(searchStatusLabel($report['status'])) ?></span></div><h3><?= e($report['item_name']) ?></h3><p><?= e($report['category_name']) ?> · <?= e($report['building_name'] ?? 'Location not specified') ?></p><p><?= e($report['description']) ?></p><small><?= e(ucfirst($report['report_type'])) ?> on <?= e($report['incident_date']) ?></small><a class="text-link" href="report_details.php?type=<?=e($report['report_type'])?>&id=<?=(int)$report['report_id']?>">View details →</a></div></article><?php endforeach; ?></div><?php endif; ?><?php if($pager['pages']>1):?><nav class="pagination" aria-label="Search result pages"><?php for($number=1;$number<=$pager['pages'];$number++):?><a class="<?=$number===$pager['page']?'active':''?>" href="?<?=e(http_build_query(array_merge($_GET,['page'=>$number])))?>"><?=$number?></a><?php endfor;?></nav><?php endif;?>
</main></body></html>
