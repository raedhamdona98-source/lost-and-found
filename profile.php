<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();
$pdo=getDatabaseConnection();$userId=(int)currentUser()['user_id'];$errors=[];$message='';
$statement=$pdo->prepare('SELECT * FROM users WHERE user_id=:id');$statement->execute(['id'=>$userId]);$account=$statement->fetch();
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!validCsrfToken($_POST['csrf_token']??null))$errors[]='Your session expired.';
    $action=(string)($_POST['action']??'profile');
    if($action==='profile'){
        $name=trim((string)($_POST['name']??''));$email=trim((string)($_POST['email']??''));$phone=trim((string)($_POST['phone']??''));
        if($name==='')$errors[]='Name is required.';if(!filter_var($email,FILTER_VALIDATE_EMAIL))$errors[]='Enter a valid email address.';
        if(!$errors){try{$update=$pdo->prepare('UPDATE users SET name=:name,email=:email,phone=:phone WHERE user_id=:id');$update->execute(['name'=>$name,'email'=>$email,'phone'=>$phone?:null,'id'=>$userId]);$_SESSION['user']['name']=$name;$_SESSION['user']['email']=$email;$message='Profile updated successfully.';$account=array_merge($account,['name'=>$name,'email'=>$email,'phone'=>$phone]);}catch(PDOException $exception){$errors[]=$exception->getCode()==='23000'?'This email is already in use.':'Profile could not be updated.';}}
    }elseif($action==='password'){
        $current=(string)($_POST['current_password']??'');$new=(string)($_POST['new_password']??'');$confirmation=(string)($_POST['password_confirmation']??'');
        if(!password_verify($current,$account['password_hash']))$errors[]='Current password is incorrect.';if(strlen($new)<8)$errors[]='New password must contain at least 8 characters.';if($new!==$confirmation)$errors[]='Password confirmation does not match.';
        if(!$errors){$update=$pdo->prepare('UPDATE users SET password_hash=:hash WHERE user_id=:id');$update->execute(['hash'=>password_hash($new,PASSWORD_DEFAULT),'id'=>$userId]);$message='Password changed successfully.';}
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>My Profile</title><link rel="stylesheet" href="assets/css/style.css"></head><body><header class="site-header"><a class="brand brand-with-mark" href="dashboard.php"><span class="brand-mark">LU</span>Liwa University Lost & Found</a><nav><a href="dashboard.php">Dashboard</a><a class="button button-outline" href="logout.php">Logout</a></nav></header><main class="admin-page"><p class="eyebrow">Account settings</p><h1>My Profile</h1><?php if($message):?><div class="alert alert-success"><?=e($message)?></div><?php endif;?><?php if($errors):?><div class="alert alert-error"><ul><?php foreach($errors as $error):?><li><?=e($error)?></li><?php endforeach;?></ul></div><?php endif;?><div class="management-grid"><section class="management-card"><h2>Personal information</h2><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><input type="hidden" name="action" value="profile"><label>Full name<input name="name" value="<?=e($account['name'])?>" required></label><label>Email address<input type="email" name="email" value="<?=e($account['email'])?>" required></label><label>Phone number<input name="phone" value="<?=e((string)$account['phone'])?>"></label><label>Student ID<input value="<?=e((string)$account['student_id'])?>" disabled></label><button class="button button-primary">Save Profile</button></form></section><section class="management-card"><h2>Change password</h2><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><input type="hidden" name="action" value="password"><label>Current password<input type="password" name="current_password" required></label><label>New password<input type="password" name="new_password" minlength="8" required></label><label>Confirm new password<input type="password" name="password_confirmation" minlength="8" required></label><button class="button button-primary">Change Password</button></form></section></div></main></body></html>
