<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
requireAdmin();
$pdo=getDatabaseConnection();$message='';$error='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!validCsrfToken($_POST['csrf_token']??null))$error='Your session expired.';
    else{
        $entity=(string)($_POST['entity']??'');$action=(string)($_POST['action']??'');$id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT);$name=trim((string)($_POST['name']??''));$description=trim((string)($_POST['description']??''));
        if(!in_array($entity,['building','category'],true)||!in_array($action,['add','update'],true)||$name==='')$error='Enter valid system data.';
        else{
            try{
                if($entity==='building'){
                    if($action==='add'){$statement=$pdo->prepare('INSERT INTO buildings(building_name,description) VALUES(:name,:description)');$statement->execute(['name'=>$name,'description'=>$description?:null]);}
                    elseif($id){$statement=$pdo->prepare('UPDATE buildings SET building_name=:name,description=:description WHERE building_id=:id');$statement->execute(['name'=>$name,'description'=>$description?:null,'id'=>$id]);}
                }else{
                    if($action==='add'){$statement=$pdo->prepare('INSERT INTO categories(category_name) VALUES(:name)');$statement->execute(['name'=>$name]);}
                    elseif($id){$statement=$pdo->prepare('UPDATE categories SET category_name=:name WHERE category_id=:id');$statement->execute(['name'=>$name,'id'=>$id]);}
                }
                $message=ucfirst($entity).' saved successfully.';
            }catch(PDOException $exception){$error=$exception->getCode()==='23000'?'This name already exists.':'The system data could not be saved.';}
        }
    }
}
$buildings=$pdo->query('SELECT * FROM buildings ORDER BY building_name')->fetchAll();$categories=$pdo->query('SELECT * FROM categories ORDER BY category_name')->fetchAll();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>System Data</title><link rel="stylesheet" href="../assets/css/style.css"></head><body><header class="site-header"><a class="brand" href="../dashboard.php">Liwa University Lost & Found</a><nav><a href="manage_users.php">Users</a><a href="analytics.php">Analytics</a><a href="../dashboard.php">Dashboard</a><a class="button button-outline" href="../logout.php">Logout</a></nav></header><main class="admin-page"><p class="eyebrow">Configuration</p><h1>System Data</h1><p class="form-intro">Manage campus buildings and item categories used in reports.</p><?php if($message):?><div class="alert alert-success"><?=e($message)?></div><?php endif;?><?php if($error):?><div class="alert alert-error"><?=e($error)?></div><?php endif;?><div class="management-grid"><section class="management-card"><h2>Buildings</h2><form class="add-data-form" method="post"><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><input type="hidden" name="entity" value="building"><input type="hidden" name="action" value="add"><label>Building name<input name="name" required></label><label>Description<input name="description"></label><button class="button button-primary">Add Building</button></form><div class="editable-list"><?php foreach($buildings as $building):?><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><input type="hidden" name="entity" value="building"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?=(int)$building['building_id']?>"><input name="name" value="<?=e($building['building_name'])?>" required><input name="description" value="<?=e((string)$building['description'])?>" placeholder="Description"><button>Update</button></form><?php endforeach;?></div></section><section class="management-card"><h2>Item Categories</h2><form class="add-data-form" method="post"><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><input type="hidden" name="entity" value="category"><input type="hidden" name="action" value="add"><label>Category name<input name="name" required></label><button class="button button-primary">Add Category</button></form><div class="editable-list"><?php foreach($categories as $category):?><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrfToken())?>"><input type="hidden" name="entity" value="category"><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?=(int)$category['category_id']?>"><input name="name" value="<?=e($category['category_name'])?>" required><button>Update</button></form><?php endforeach;?></div></section></div></main></body></html>
