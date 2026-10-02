<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
requireStaff();
$pdo=getDatabaseConnection(); $message=''; $error='';
$lostStatuses=['submitted','under_review','matched','ready_for_collection','returned','closed','rejected'];
$foundStatuses=['submitted','under_review','available','matched','ready_for_collection','returned','closed','rejected'];
function allowedReportStatuses(string $type,string $current):array{
    $maps=[
        'lost'=>['submitted'=>['submitted','under_review','rejected'],'under_review'=>['under_review','matched','rejected'],'matched'=>['matched','ready_for_collection','rejected'],'ready_for_collection'=>['ready_for_collection','returned'],'returned'=>['returned','closed'],'closed'=>['closed'],'rejected'=>['rejected']],
        'found'=>['submitted'=>['submitted','under_review','rejected'],'under_review'=>['under_review','available','matched','rejected'],'available'=>['available','matched','closed'],'matched'=>['matched','ready_for_collection','rejected'],'ready_for_collection'=>['ready_for_collection','returned'],'returned'=>['returned','closed'],'closed'=>['closed'],'rejected'=>['rejected']],
    ];return $maps[$type][$current]??[$current];
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!validCsrfToken($_POST['csrf_token']??null)) $error='Your session expired.';
    else{
        $type=(string)($_POST['report_type']??''); $id=filter_input(INPUT_POST,'report_id',FILTER_VALIDATE_INT); $status=(string)($_POST['status']??'');
        $allowed=$type==='lost'?$lostStatuses:($type==='found'?$foundStatuses:[]);
        $table=$type==='lost'?'lost_items':'found_items'; $column=$type==='lost'?'lost_id':'found_id';
        $currentStatement=$pdo->prepare("SELECT status FROM {$table} WHERE {$column}=:id");$currentStatement->execute(['id'=>$id]);$current=(string)$currentStatement->fetchColumn();
        if($id && in_array($status,$allowed,true) && in_array($status,allowedReportStatuses($type,$current),true)){
            $statement=$pdo->prepare("UPDATE {$table} SET status=:status WHERE {$column}=:id"); $statement->execute(['status'=>$status,'id'=>$id]); $message='Report status updated successfully.';
        }else $error='This status transition is not allowed.';
    }
}
$lost=$pdo->query("SELECT l.lost_id id,l.item_name,l.lost_date incident_date,l.status,u.name user_name,c.category_name,b.building_name FROM lost_items l JOIN users u ON u.user_id=l.user_id JOIN categories c ON c.category_id=l.category_id LEFT JOIN buildings b ON b.building_id=l.building_id ORDER BY l.created_at DESC")->fetchAll();
$found=$pdo->query("SELECT f.found_id id,f.item_name,f.found_date incident_date,f.status,u.name user_name,c.category_name,b.building_name FROM found_items f JOIN users u ON u.user_id=f.finder_id JOIN categories c ON c.category_id=f.category_id LEFT JOIN buildings b ON b.building_id=f.building_id ORDER BY f.created_at DESC")->fetchAll();
function reportStatus(string $value):string{return ucwords(str_replace('_',' ',$value));}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Review Reports</title><link rel="stylesheet" href="../assets/css/style.css"></head><body><header class="site-header"><a class="brand" href="../dashboard.php">Liwa University Lost & Found</a><nav><a href="claims.php">Claims</a><a href="../dashboard.php">Dashboard</a><a class="button button-outline" href="../logout.php">Logout</a></nav></header><main class="admin-page"><p class="eyebrow">Lost & Found staff</p><h1>Review Reports</h1><p class="form-intro">Review and update the status of all lost and found reports.</p><?php if($message):?><div class="alert alert-success"><?=e($message)?></div><?php endif;?><?php if($error):?><div class="alert alert-error"><?=e($error)?></div><?php endif;?>
<?php foreach([['Lost Item Reports','lost',$lost,$lostStatuses],['Found Item Reports','found',$found,$foundStatuses]] as [$title,$type,$rows,$statuses]):?><section><div class="section-heading"><h2><?=e($title)?></h2></div><?php if(!$rows):?><div class="empty-state">No reports available.</div><?php else:?><div class="table-wrap"><table><thead><tr><th>ID</th><th>Item</th><th>Category</th><th>Building</th><th>Submitted by</th><th>Date</th><th>Status</th></tr></thead><tbody><?php foreach($rows as $row):?><tr><td>#<?=(int)$row['id']?></td><td><?=e($row['item_name'])?></td><td><?=e($row['category_name'])?></td><td><?=e($row['building_name']??'-')?></td><td><?=e($row['user_name'])?></td><td><?=e($row['incident_date'])?></td><td><form class="status-form" method="post"><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><input type="hidden" name="report_type" value="<?=e($type)?>"><input type="hidden" name="report_id" value="<?=(int)$row['id']?>"><select name="status"><?php foreach($statuses as $status):?><option value="<?=e($status)?>" <?=$row['status']===$status?'selected':''?>><?=e(reportStatus($status))?></option><?php endforeach;?></select><button>Save</button></form></td></tr><?php endforeach;?></tbody></table></div><?php endif;?></section><?php endforeach;?></main></body></html>
