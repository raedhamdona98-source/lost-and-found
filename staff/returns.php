<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
requireStaff();
$pdo=getDatabaseConnection();$message='';$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!validCsrfToken($_POST['csrf_token']??null))$error='Your session expired.';
    else{
        $claimId=filter_input(INPUT_POST,'claim_id',FILTER_VALIDATE_INT);$action=(string)($_POST['action']??'');$notes=trim((string)($_POST['confirmation_notes']??''));
        if(!$claimId||!in_array($action,['return','close'],true))$error='Invalid return action.';
        else{
            try{$pdo->beginTransaction();$query=$pdo->prepare("SELECT cl.claim_id,cl.user_id,cl.lost_id,cl.found_id,cl.approval_status,r.return_id FROM claims cl LEFT JOIN returns r ON r.claim_id=cl.claim_id WHERE cl.claim_id=:id FOR UPDATE");$query->execute(['id'=>$claimId]);$claim=$query->fetch();if(!$claim||$claim['approval_status']!=='approved')throw new RuntimeException('Only approved claims can be returned.');
                if($action==='return'){
                    if($claim['return_id'])throw new RuntimeException('This item has already been returned.');
                    $insert=$pdo->prepare('INSERT INTO returns(claim_id,student_id,staff_id,confirmation_notes) VALUES(:claim_id,:student_id,:staff_id,:notes)');$insert->execute(['claim_id'=>$claimId,'student_id'=>$claim['user_id'],'staff_id'=>currentUser()['user_id'],'notes'=>$notes?:null]);
                    $pdo->prepare("UPDATE lost_items SET status='returned' WHERE lost_id=:id")->execute(['id'=>$claim['lost_id']]);$pdo->prepare("UPDATE found_items SET status='returned' WHERE found_id=:id")->execute(['id'=>$claim['found_id']]);
                    createNotification((int)$claim['user_id'],'Item returned','The item handover was confirmed successfully. Your case is ready to be closed.');$message='Item return confirmed successfully.';
                }else{
                    if(!$claim['return_id'])throw new RuntimeException('Confirm the item return before closing the case.');
                    $pdo->prepare("UPDATE lost_items SET status='closed' WHERE lost_id=:id")->execute(['id'=>$claim['lost_id']]);$pdo->prepare("UPDATE found_items SET status='closed' WHERE found_id=:id")->execute(['id'=>$claim['found_id']]);
                    createNotification((int)$claim['user_id'],'Case closed','Your lost-and-found case has been completed and closed.');$message='Case closed successfully.';
                }$pdo->commit();
            }catch(Throwable $exception){if($pdo->inTransaction())$pdo->rollBack();$error=$exception->getMessage();}
        }
    }
}
$claims=$pdo->query("SELECT cl.claim_id,cl.user_id,cl.pickup_date,u.name claimant_name,u.student_id,l.item_name,l.status lost_status,f.status found_status,r.return_id,r.return_date,r.confirmation_notes FROM claims cl JOIN users u ON u.user_id=cl.user_id JOIN lost_items l ON l.lost_id=cl.lost_id JOIN found_items f ON f.found_id=cl.found_id LEFT JOIN returns r ON r.claim_id=cl.claim_id WHERE cl.approval_status='approved' ORDER BY cl.pickup_date ASC")->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Item Returns</title><link rel="stylesheet" href="../assets/css/style.css"></head><body><header class="site-header"><a class="brand" href="../dashboard.php">Liwa University Lost & Found</a><nav><a href="reports.php">Reports</a><a href="claims.php">Claims</a><a href="../dashboard.php">Dashboard</a><a class="button button-outline" href="../logout.php">Logout</a></nav></header><main class="admin-page"><p class="eyebrow">Item retrieval</p><h1>Returns & Case Closure</h1><p class="form-intro">Confirm physical handover, then close the completed case.</p><?php if($message):?><div class="alert alert-success"><?=e($message)?></div><?php endif;?><?php if($error):?><div class="alert alert-error"><?=e($error)?></div><?php endif;?><?php if(!$claims):?><div class="empty-state">No approved claims are ready for collection.</div><?php else:?><div class="table-wrap"><table><thead><tr><th>Claim</th><th>Item</th><th>Student</th><th>Pickup</th><th>Return status</th><th>Action</th></tr></thead><tbody><?php foreach($claims as $claim):?><tr><td>#<?=(int)$claim['claim_id']?></td><td><?=e($claim['item_name'])?></td><td><?=e($claim['claimant_name'])?> (<?=e((string)$claim['student_id'])?>)</td><td><?=e($claim['pickup_date']??'-')?></td><td><?=$claim['return_id']?'Returned '.$claim['return_date']:'Awaiting collection'?></td><td><?php if(!$claim['return_id']):?><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><input type="hidden" name="claim_id" value="<?=(int)$claim['claim_id']?>"><input name="confirmation_notes" placeholder="Confirmation notes"><button class="action-approve" name="action" value="return">Confirm Return</button></form><?php elseif($claim['lost_status']!=='closed'):?><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><input type="hidden" name="claim_id" value="<?=(int)$claim['claim_id']?>"><button class="action-approve" name="action" value="close">Close Case</button></form><?php else:?><span class="status status-closed">Closed</span><?php endif;?></td></tr><?php endforeach;?></tbody></table></div><?php endif;?></main></body></html>
