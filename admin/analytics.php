<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
requireAdmin();
$pdo=getDatabaseConnection();

$summary=[
    'users'=>(int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn(),
    'pending_users'=>(int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='student' AND account_status='pending'")->fetchColumn(),
    'lost'=>(int)$pdo->query('SELECT COUNT(*) FROM lost_items')->fetchColumn(),
    'found'=>(int)$pdo->query('SELECT COUNT(*) FROM found_items')->fetchColumn(),
    'claims'=>(int)$pdo->query('SELECT COUNT(*) FROM claims')->fetchColumn(),
    'returns'=>(int)$pdo->query('SELECT COUNT(*) FROM returns')->fetchColumn(),
];

$statusRows=$pdo->query("SELECT status,SUM(total) total FROM (SELECT status,COUNT(*) total FROM lost_items GROUP BY status UNION ALL SELECT status,COUNT(*) total FROM found_items GROUP BY status) x GROUP BY status ORDER BY total DESC")->fetchAll();
$hotspots=$pdo->query("SELECT building_name,SUM(total) total FROM (SELECT b.building_name,COUNT(*) total FROM lost_items l JOIN buildings b ON b.building_id=l.building_id GROUP BY b.building_id,b.building_name UNION ALL SELECT b.building_name,COUNT(*) total FROM found_items f JOIN buildings b ON b.building_id=f.building_id GROUP BY b.building_id,b.building_name) x GROUP BY building_name ORDER BY total DESC LIMIT 6")->fetchAll();
$categories=$pdo->query("SELECT category_name,SUM(total) total FROM (SELECT c.category_name,COUNT(*) total FROM lost_items l JOIN categories c ON c.category_id=l.category_id GROUP BY c.category_id,c.category_name UNION ALL SELECT c.category_name,COUNT(*) total FROM found_items f JOIN categories c ON c.category_id=f.category_id GROUP BY c.category_id,c.category_name) x GROUP BY category_name ORDER BY total DESC LIMIT 8")->fetchAll();
$monthly=$pdo->query("SELECT month,SUM(total) total FROM (SELECT DATE_FORMAT(created_at,'%Y-%m') month,COUNT(*) total FROM lost_items GROUP BY month UNION ALL SELECT DATE_FORMAT(created_at,'%Y-%m') month,COUNT(*) total FROM found_items GROUP BY month) x GROUP BY month ORDER BY month DESC LIMIT 6")->fetchAll();
$monthly=array_reverse($monthly);
$maxHotspot=max(array_merge([1],array_map(fn($row)=>(int)$row['total'],$hotspots)));
$maxCategory=max(array_merge([1],array_map(fn($row)=>(int)$row['total'],$categories)));
$maxMonthly=max(array_merge([1],array_map(fn($row)=>(int)$row['total'],$monthly)));
function analyticsLabel(string $value):string{return ucwords(str_replace('_',' ',$value));}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Reports & Analytics</title><link rel="stylesheet" href="../assets/css/style.css"></head><body><header class="site-header"><a class="brand" href="../dashboard.php">Liwa University Lost & Found</a><nav><a href="users.php">Users</a><a href="../staff/reports.php">Reports</a><a href="../staff/claims.php">Claims</a><a href="../dashboard.php">Dashboard</a><a class="button button-outline" href="../logout.php">Logout</a></nav></header><main class="analytics-page"><p class="eyebrow">Administration dashboard</p><h1>Reports & Analytics</h1><p class="form-intro">Monitor implementation, report activity, case progress, and campus incident hotspots.</p>
<section class="metric-grid"><article><span>Students</span><strong><?=$summary['users']?></strong><small><?=$summary['pending_users']?> pending approval</small></article><article><span>Lost reports</span><strong><?=$summary['lost']?></strong><small>All submitted cases</small></article><article><span>Found reports</span><strong><?=$summary['found']?></strong><small>All registered items</small></article><article><span>Claims</span><strong><?=$summary['claims']?></strong><small>Ownership requests</small></article><article><span>Returns</span><strong><?=$summary['returns']?></strong><small>Completed handovers</small></article></section>
<section class="analytics-grid"><article class="analytics-card"><h2>Incident Hotspots</h2><p>Buildings with the most lost and found reports.</p><?php if(!$hotspots):?><div class="empty-state">No location data yet.</div><?php else:?><div class="bar-list"><?php foreach($hotspots as $row):?><div><span><?=e($row['building_name'])?></span><div class="bar-track"><i style="width:<?=round(((int)$row['total']/$maxHotspot)*100)?>%"></i></div><strong><?=(int)$row['total']?></strong></div><?php endforeach;?></div><?php endif;?></article>
<article class="analytics-card"><h2>Item Categories</h2><p>Most frequently reported types of items.</p><?php if(!$categories):?><div class="empty-state">No category data yet.</div><?php else:?><div class="bar-list category-bars"><?php foreach($categories as $row):?><div><span><?=e($row['category_name'])?></span><div class="bar-track"><i style="width:<?=round(((int)$row['total']/$maxCategory)*100)?>%"></i></div><strong><?=(int)$row['total']?></strong></div><?php endforeach;?></div><?php endif;?></article>
<article class="analytics-card"><h2>Monthly Activity</h2><p>Reports submitted during recent months.</p><?php if(!$monthly):?><div class="empty-state">No monthly activity yet.</div><?php else:?><div class="bar-list monthly-bars"><?php foreach($monthly as $row):?><div><span><?=e($row['month'])?></span><div class="bar-track"><i style="width:<?=round(((int)$row['total']/$maxMonthly)*100)?>%"></i></div><strong><?=(int)$row['total']?></strong></div><?php endforeach;?></div><?php endif;?></article>
<article class="analytics-card"><h2>Report Statuses</h2><p>Current workflow distribution across all reports.</p><?php if(!$statusRows):?><div class="empty-state">No report status data yet.</div><?php else:?><div class="status-summary"><?php foreach($statusRows as $row):?><div><span class="status status-<?=e($row['status'])?>"><?=e(analyticsLabel($row['status']))?></span><strong><?=(int)$row['total']?></strong></div><?php endforeach;?></div><?php endif;?></article></section>
</main></body></html>
