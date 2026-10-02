<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
requireAdmin();
$pdo=getDatabaseConnection();$message='';$error='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!validCsrfToken($_POST['csrf_token']??null))$error='Your session expired.';
    else{
        $userId=filter_input(INPUT_POST,'user_id',FILTER_VALIDATE_INT);
        $status=(string)($_POST['account_status']??'');
        $role=(string)($_POST['role']??'');
        if(!$userId||!in_array($status,['pending','active','suspended'],true)||!in_array($role,['student','staff'],true))$error='Invalid user update.';
        else{
            $statement=$pdo->prepare("UPDATE users SET account_status=:status,role=:role WHERE user_id=:user_id AND role<>'admin'");
            $statement->execute(['status'=>$status,'role'=>$role,'user_id'=>$userId]);
            $message=$statement->rowCount()?'User account updated successfully.':'No account was changed.';
        }
    }
}

$filter=(string)($_GET['filter']??'all');
$allowedFilters=['all','student','staff','active','pending','suspended'];
if(!in_array($filter,$allowedFilters,true))$filter='all';
$where=" FROM users WHERE role<>'admin'";
$params=[];
if(in_array($filter,['student','staff'],true)){ $where.=' AND role=:filter';$params['filter']=$filter; }
elseif(in_array($filter,['active','pending','suspended'],true)){ $where.=' AND account_status=:filter';$params['filter']=$filter; }
$countStatement=$pdo->prepare('SELECT COUNT(*)'.$where);$countStatement->execute($params);$totalUsers=(int)$countStatement->fetchColumn();$page=filter_input(INPUT_GET,'page',FILTER_VALIDATE_INT)?:1;$pager=pagination($totalUsers,$page,10);
$sql='SELECT user_id,name,student_id,email,phone,role,account_status,created_at'.$where." ORDER BY created_at DESC LIMIT {$pager['per_page']} OFFSET {$pager['offset']}";
$statement=$pdo->prepare($sql);$statement->execute($params);$users=$statement->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Manage Users</title><link rel="stylesheet" href="../assets/css/style.css"></head><body><header class="site-header"><a class="brand" href="../dashboard.php">Liwa University Lost & Found</a><nav><a href="users.php">Registration Requests</a><a href="create_staff.php">Create Staff</a><a href="catalog.php">System Data</a><a href="../dashboard.php">Dashboard</a><a class="button button-outline" href="../logout.php">Logout</a></nav></header><main class="admin-page"><p class="eyebrow">Administration</p><h1>Manage Users</h1><p class="form-intro">Review account information, roles, and access status.</p><?php if($message):?><div class="alert alert-success"><?=e($message)?></div><?php endif;?><?php if($error):?><div class="alert alert-error"><?=e($error)?></div><?php endif;?><div class="filter-links"><?php foreach($allowedFilters as $item):?><a class="<?=$filter===$item?'active':''?>" href="?filter=<?=e($item)?>"><?=e(ucfirst($item))?></a><?php endforeach;?></div><?php if(!$users):?><div class="empty-state">No users match this filter.</div><?php else:?><div class="table-wrap"><table><thead><tr><th>Name</th><th>Student ID</th><th>Email</th><th>Phone</th><th>Joined</th><th>Role & Status</th></tr></thead><tbody><?php foreach($users as $account):?><tr><td><?=e($account['name'])?></td><td><?=e((string)$account['student_id'])?></td><td><?=e($account['email'])?></td><td><?=e((string)$account['phone'])?></td><td><?=e($account['created_at'])?></td><td><form class="user-control-form" method="post"><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><input type="hidden" name="user_id" value="<?=(int)$account['user_id']?>"><select name="role"><option value="student" <?=$account['role']==='student'?'selected':''?>>Student</option><option value="staff" <?=$account['role']==='staff'?'selected':''?>>Staff</option></select><select name="account_status"><option value="pending" <?=$account['account_status']==='pending'?'selected':''?>>Pending</option><option value="active" <?=$account['account_status']==='active'?'selected':''?>>Active</option><option value="suspended" <?=$account['account_status']==='suspended'?'selected':''?>>Suspended</option></select><button>Save</button></form></td></tr><?php endforeach;?></tbody></table></div><?php endif;?></main></body></html>
