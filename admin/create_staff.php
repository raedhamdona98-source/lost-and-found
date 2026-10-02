<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
requireAdmin();
$errors=[]; $success=false;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name=trim((string)($_POST['name']??'')); $email=trim((string)($_POST['email']??'')); $phone=trim((string)($_POST['phone']??'')); $password=(string)($_POST['password']??'');
    if (!validCsrfToken($_POST['csrf_token']??null)) $errors[]='Your session expired.';
    if ($name==='') $errors[]='Staff name is required.';
    if (!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors[]='Enter a valid email address.';
    if (strlen($password)<8) $errors[]='Password must contain at least 8 characters.';
    if (!$errors) {
        try { $statement=getDatabaseConnection()->prepare("INSERT INTO users(name,email,phone,password_hash,role,account_status) VALUES(:name,:email,:phone,:password_hash,'staff','active')"); $statement->execute(['name'=>$name,'email'=>$email,'phone'=>$phone?:null,'password_hash'=>password_hash($password,PASSWORD_DEFAULT)]); $success=true; }
        catch (PDOException $exception) { $errors[]=$exception->getCode()==='23000'?'This email is already registered.':'The staff account could not be created.'; }
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Create Staff Account</title><link rel="stylesheet" href="../assets/css/style.css"></head><body class="auth-page"><header class="site-header"><a class="brand" href="../dashboard.php">Liwa University Lost & Found</a><a href="../dashboard.php">Dashboard</a></header><main class="auth-wrapper"><section class="auth-card auth-card-small"><p class="eyebrow">User management</p><h1>Create Staff</h1><p class="form-intro">Create an active account for a Lost & Found department employee.</p><?php if($success):?><div class="alert alert-success">Staff account created successfully.</div><?php endif;?><?php if($errors):?><div class="alert alert-error"><ul><?php foreach($errors as $error):?><li><?=e($error)?></li><?php endforeach;?></ul></div><?php endif;?><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><label>Full name<input name="name" required></label><label>Email address<input type="email" name="email" required></label><label>Phone number<input name="phone"></label><label>Temporary password<input type="password" name="password" minlength="8" required></label><button class="button button-primary form-submit">Create Staff Account</button></form></section></main></body></html>
