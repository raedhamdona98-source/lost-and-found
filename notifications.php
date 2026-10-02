<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();
$pdo=getDatabaseConnection(); $userId=(int)currentUser()['user_id'];
if($_SERVER['REQUEST_METHOD']==='POST'&&validCsrfToken($_POST['csrf_token']??null)){
    $statement=$pdo->prepare('UPDATE notifications SET is_read=1 WHERE user_id=:user_id');$statement->execute(['user_id'=>$userId]);
    header('Location: notifications.php');exit;
}
$statement=$pdo->prepare('SELECT * FROM notifications WHERE user_id=:user_id ORDER BY created_at DESC');$statement->execute(['user_id'=>$userId]);$notifications=$statement->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Notifications</title><link rel="stylesheet" href="assets/css/style.css"></head><body><header class="site-header"><a class="brand" href="dashboard.php">Liwa University Lost & Found</a><nav><a href="dashboard.php">Dashboard</a><a class="button button-outline" href="logout.php">Logout</a></nav></header><main class="admin-page"><div class="section-heading"><div><p class="eyebrow">Updates</p><h1>Notifications</h1></div><?php if($notifications):?><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><button class="button button-secondary">Mark all as read</button></form><?php endif;?></div><?php if(!$notifications):?><div class="empty-state">You do not have any notifications.</div><?php else:?><div class="notification-list"><?php foreach($notifications as $notification):?><article class="notification-item <?=$notification['is_read']?'':'unread'?>"><div><h3><?=e($notification['title'])?></h3><p><?=e($notification['message'])?></p><small><?=e($notification['created_at'])?></small></div><?php if(!$notification['is_read']):?><span>New</span><?php endif;?></article><?php endforeach;?></div><?php endif;?></main></body></html>
