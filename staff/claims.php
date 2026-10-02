<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
requireStaff();
$pdo=getDatabaseConnection(); $message=''; $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!validCsrfToken($_POST['csrf_token']??null)) $error='Your session expired.';
    else{
        $claimId=filter_input(INPUT_POST,'claim_id',FILTER_VALIDATE_INT); $action=(string)($_POST['action']??''); $notes=trim((string)($_POST['review_notes']??'')); $pickup=trim((string)($_POST['pickup_date']??''));
        if(!$claimId||!in_array($action,['approve','reject'],true)) $error='Invalid claim update.';
        elseif($action==='approve'&&$pickup==='') $error='Select a pickup date and time.';
        else{
            try{$pdo->beginTransaction();
                $claimQuery=$pdo->prepare("SELECT lost_id,found_id,user_id FROM claims WHERE claim_id=:id AND approval_status='pending' FOR UPDATE");$claimQuery->execute(['id'=>$claimId]);$claim=$claimQuery->fetch();
                if(!$claim) throw new RuntimeException('This claim has already been reviewed.');
                if($action==='approve'){
                    $update=$pdo->prepare("UPDATE claims SET verification_status='verified',approval_status='approved',reviewed_by=:staff,review_notes=:notes,pickup_date=:pickup WHERE claim_id=:id");$update->execute(['staff'=>currentUser()['user_id'],'notes'=>$notes?:null,'pickup'=>str_replace('T',' ',$pickup),'id'=>$claimId]);
                    $pdo->prepare("UPDATE lost_items SET status='ready_for_collection' WHERE lost_id=:id")->execute(['id'=>$claim['lost_id']]);
                    $pdo->prepare("UPDATE found_items SET status='ready_for_collection' WHERE found_id=:id")->execute(['id'=>$claim['found_id']]);
                    createNotification((int)$claim['user_id'], 'Claim approved', 'Your ownership claim was approved. Collection is scheduled for ' . str_replace('T',' ',$pickup) . '.');
                    $message='Claim verified and approved. The item is ready for collection.';
                }else{
                    $update=$pdo->prepare("UPDATE claims SET verification_status='rejected',approval_status='rejected',reviewed_by=:staff,review_notes=:notes WHERE claim_id=:id");$update->execute(['staff'=>currentUser()['user_id'],'notes'=>$notes?:null,'id'=>$claimId]);createNotification((int)$claim['user_id'], 'Claim rejected', 'Your ownership claim was not approved. Review notes: ' . ($notes ?: 'No additional notes.'));$message='Claim rejected.';
                }
                $pdo->commit();
            }catch(Throwable $exception){if($pdo->inTransaction())$pdo->rollBack();$error=$exception->getMessage();}
        }
    }
}
$claims=$pdo->query("SELECT cl.*,u.name claimant_name,u.student_id,l.item_name lost_name,f.item_name found_name,f.image_path FROM claims cl JOIN users u ON u.user_id=cl.user_id JOIN lost_items l ON l.lost_id=cl.lost_id JOIN found_items f ON f.found_id=cl.found_id ORDER BY cl.claim_date DESC")->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Verify Claims</title><link rel="stylesheet" href="../assets/css/style.css"></head><body><header class="site-header"><a class="brand" href="../dashboard.php">Liwa University Lost & Found</a><nav><a href="reports.php">Reports</a><a href="../dashboard.php">Dashboard</a><a class="button button-outline" href="../logout.php">Logout</a></nav></header><main class="admin-page"><p class="eyebrow">Ownership verification</p><h1>Claim Requests</h1><p class="form-intro">Review the claimant's private ownership details before approving collection.</p><?php if($message):?><div class="alert alert-success"><?=e($message)?></div><?php endif;?><?php if($error):?><div class="alert alert-error"><?=e($error)?></div><?php endif;?><?php if(!$claims):?><div class="empty-state">No claim requests available.</div><?php else:?><div class="claim-review-grid"><?php foreach($claims as $claim):?><article class="claim-review-card"><?php if($claim['image_path']):?><img src="../<?=e($claim['image_path'])?>" alt="Found item"><?php endif;?><div><div class="report-meta"><span>Claim #<?=(int)$claim['claim_id']?></span><span class="status status-<?=e($claim['approval_status']==='pending'?'under_review':$claim['approval_status'])?>"><?=e(ucfirst($claim['approval_status']))?></span></div><h2><?=e($claim['lost_name'])?> ↔ <?=e($claim['found_name'])?></h2><p><strong>Claimant:</strong> <?=e($claim['claimant_name'])?> (<?=e((string)$claim['student_id'])?>)</p><div class="ownership-box"><strong>Ownership evidence</strong><p><?=nl2br(e($claim['ownership_details']))?></p></div><?php if($claim['approval_status']==='pending'):?><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><input type="hidden" name="claim_id" value="<?=(int)$claim['claim_id']?>"><label>Review notes<textarea name="review_notes" rows="3"></textarea></label><label>Pickup date and time<input type="datetime-local" name="pickup_date" min="<?=date('Y-m-d\TH:i')?>"></label><div class="inline-actions"><button class="action-approve" name="action" value="approve">Verify & Approve</button><button class="action-reject" name="action" value="reject">Reject</button></div></form><?php else:?><p><strong>Notes:</strong> <?=e($claim['review_notes']??'-')?></p><?php if($claim['pickup_date']):?><p><strong>Pickup:</strong> <?=e($claim['pickup_date'])?></p><?php endif;?><?php endif;?></div></article><?php endforeach;?></div><?php endif;?></main></body></html>
