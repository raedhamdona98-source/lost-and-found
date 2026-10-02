<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$pdo = getDatabaseConnection();
$categories = $pdo->query('SELECT category_id, category_name FROM categories ORDER BY category_name')->fetchAll();
$buildings = $pdo->query('SELECT building_id, building_name FROM buildings ORDER BY building_name')->fetchAll();
$errors = [];
$success = isset($_GET['success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemName = trim((string) ($_POST['item_name'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $specificLocation = trim((string) ($_POST['specific_location'] ?? ''));
    $storageLocation = trim((string) ($_POST['storage_location'] ?? ''));
    $foundDate = (string) ($_POST['found_date'] ?? '');
    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $buildingId = filter_input(INPUT_POST, 'building_id', FILTER_VALIDATE_INT);

    if (!validCsrfToken($_POST['csrf_token'] ?? null)) $errors[] = 'Your session expired. Please try again.';
    if ($itemName === '') $errors[] = 'Item name is required.';
    if (!$categoryId) $errors[] = 'Select an item category.';
    if ($description === '') $errors[] = 'Description is required.';
    $dateObject = DateTime::createFromFormat('Y-m-d', $foundDate);
    if (!$dateObject || $dateObject->format('Y-m-d') !== $foundDate || $foundDate > date('Y-m-d')) $errors[] = 'Enter a valid found date that is not in the future.';

    $imagePath = uploadItemImage($_FILES['image'] ?? [], $errors);
    if ($errors === []) {
        try {
            $statement = $pdo->prepare('INSERT INTO found_items (finder_id, category_id, building_id, item_name, description, specific_location, found_date, image_path, storage_location) VALUES (:finder_id, :category_id, :building_id, :item_name, :description, :specific_location, :found_date, :image_path, :storage_location)');
            $statement->execute(['finder_id' => currentUser()['user_id'], 'category_id' => $categoryId, 'building_id' => $buildingId ?: null, 'item_name' => $itemName, 'description' => $description, 'specific_location' => $specificLocation !== '' ? $specificLocation : null, 'found_date' => $foundDate, 'image_path' => $imagePath, 'storage_location' => $storageLocation !== '' ? $storageLocation : null]);
            header('Location: report_found.php?success=1'); exit;
        } catch (Throwable $exception) {
            if ($imagePath) @unlink(__DIR__ . '/' . $imagePath);
            error_log($exception->getMessage());
            $errors[] = 'The report could not be submitted. Please try again.';
        }
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Report Found Item</title><link rel="stylesheet" href="assets/css/style.css"></head><body>
<header class="site-header"><a class="brand" href="dashboard.php">Liwa University Lost & Found</a><nav><a href="dashboard.php">Dashboard</a><a class="button button-outline" href="logout.php">Logout</a></nav></header>
<main class="form-page"><section class="item-form-card"><p class="eyebrow">Found item report</p><h1>What did you find?</h1><p class="form-intro">Register the item so its owner and Lost & Found staff can locate it.</p>
<?php if ($success): ?><div class="alert alert-success">Your found item report was submitted successfully.</div><?php endif; ?>
<?php if ($errors): ?><div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><div class="form-grid"><label>Item name<input name="item_name" maxlength="150" required></label><label>Category<select name="category_id" required><option value="">Select category</option><?php foreach ($categories as $category): ?><option value="<?= (int)$category['category_id'] ?>"><?= e($category['category_name']) ?></option><?php endforeach; ?></select></label><label>Building<select name="building_id"><option value="">Select building</option><?php foreach ($buildings as $building): ?><option value="<?= (int)$building['building_id'] ?>"><?= e($building['building_name']) ?></option><?php endforeach; ?></select></label><label>Specific location<input name="specific_location" maxlength="255" placeholder="Room, hall, or floor"></label><label>Date found<input type="date" name="found_date" max="<?= date('Y-m-d') ?>" required></label><label>Photo (optional)<input type="file" name="image" accept="image/jpeg,image/png,image/webp"></label></div><label>Storage location (optional)<input name="storage_location" maxlength="255" placeholder="Where is the item now?"></label><label>Description<textarea name="description" rows="5" required placeholder="Color, brand, distinguishing marks..."></textarea></label><button class="button button-primary form-submit" type="submit">Submit Found Item Report</button></form></section></main></body></html>
