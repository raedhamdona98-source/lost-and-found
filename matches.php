<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();

$pdo = getDatabaseConnection();
$userId = (int) currentUser()['user_id'];
$errors = [];
$success = isset($_GET['claimed']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lostId = filter_input(INPUT_POST, 'lost_id', FILTER_VALIDATE_INT);
    $foundId = filter_input(INPUT_POST, 'found_id', FILTER_VALIDATE_INT);
    $ownershipDetails = trim((string) ($_POST['ownership_details'] ?? ''));

    if (!validCsrfToken($_POST['csrf_token'] ?? null)) $errors[] = 'Your session expired. Please try again.';
    if (!$lostId || !$foundId) $errors[] = 'Invalid lost or found item selection.';
    if (strlen($ownershipDetails) < 15) $errors[] = 'Provide at least 15 characters describing proof of ownership.';

    if ($errors === []) {
        $validation = $pdo->prepare("SELECT l.lost_id, f.found_id FROM lost_items l JOIN found_items f ON f.found_id=:found_id AND f.category_id=l.category_id WHERE l.lost_id=:lost_id AND l.user_id=:user_id AND l.status NOT IN ('returned','closed','rejected') AND f.status NOT IN ('returned','closed','rejected') LIMIT 1");
        $validation->execute(['found_id' => $foundId, 'lost_id' => $lostId, 'user_id' => $userId]);

        if (!$validation->fetch()) {
            $errors[] = 'This match is no longer available.';
        } else {
            try {
                $claim = $pdo->prepare('INSERT INTO claims (lost_id, found_id, user_id, ownership_details) VALUES (:lost_id,:found_id,:user_id,:ownership_details)');
                $claim->execute(['lost_id' => $lostId, 'found_id' => $foundId, 'user_id' => $userId, 'ownership_details' => $ownershipDetails]);
                header('Location: matches.php?claimed=1'); exit;
            } catch (PDOException $exception) {
                if ($exception->getCode() === '23000') $errors[] = 'You already submitted a claim for this match.';
                else { error_log($exception->getMessage()); $errors[] = 'The claim could not be submitted.'; }
            }
        }
    }
}

$lostStatement = $pdo->prepare("SELECT l.*, c.category_name, b.building_name FROM lost_items l JOIN categories c ON c.category_id=l.category_id LEFT JOIN buildings b ON b.building_id=l.building_id WHERE l.user_id=:user_id AND l.status NOT IN ('returned','closed','rejected') ORDER BY l.lost_date DESC");
$lostStatement->execute(['user_id' => $userId]);
$lostItems = $lostStatement->fetchAll();

$matchStatement = $pdo->prepare("SELECT f.found_id, f.item_name, f.description, f.specific_location, f.found_date, f.image_path, f.status, b.building_name,
    (CASE WHEN f.building_id <=> :building_id THEN 25 ELSE 0 END + CASE WHEN LOWER(f.item_name) LIKE :item_keyword THEN 25 ELSE 0 END + 50) AS match_score,
    EXISTS(SELECT 1 FROM claims cl WHERE cl.lost_id=:lost_id AND cl.found_id=f.found_id AND cl.user_id=:claim_user_id) AS already_claimed
    FROM found_items f LEFT JOIN buildings b ON b.building_id=f.building_id
    WHERE f.category_id=:category_id AND f.finder_id<>:finder_id AND f.status NOT IN ('returned','closed','rejected')
    AND f.found_date BETWEEN DATE_SUB(:lost_date, INTERVAL 7 DAY) AND DATE_ADD(:lost_date_again, INTERVAL 60 DAY)
    ORDER BY match_score DESC, ABS(DATEDIFF(f.found_date,:lost_date_order)) ASC");

$matchesByLostId = [];
foreach ($lostItems as $lostItem) {
    $matchStatement->execute([
        'building_id' => $lostItem['building_id'], 'item_keyword' => '%' . strtolower($lostItem['item_name']) . '%',
        'lost_id' => $lostItem['lost_id'], 'claim_user_id' => $userId, 'category_id' => $lostItem['category_id'],
        'finder_id' => $userId, 'lost_date' => $lostItem['lost_date'], 'lost_date_again' => $lostItem['lost_date'],
        'lost_date_order' => $lostItem['lost_date'],
    ]);
    $matchesByLostId[$lostItem['lost_id']] = $matchStatement->fetchAll();
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>Possible Matches</title><link rel="stylesheet" href="assets/css/style.css"></head><body>
<header class="site-header"><a class="brand" href="dashboard.php">Liwa University Lost & Found</a><nav><a href="dashboard.php">Dashboard</a><a href="my_reports.php">My Reports</a><a class="button button-outline" href="logout.php">Logout</a></nav></header>
<main class="reports-page"><p class="eyebrow">Data matching</p><h1>Possible Matches</h1><p class="form-intro">Matches are suggested using category, location, item name, and incident date. Staff will verify ownership before collection.</p>
<?php if ($success): ?><div class="alert alert-success">Your claim request was submitted for staff verification.</div><?php endif; ?>
<?php if ($errors): ?><div class="alert alert-error"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<?php if ($lostItems === []): ?><div class="empty-state">Submit a lost item report before searching for matches.</div><?php endif; ?>
<?php foreach ($lostItems as $lostItem): ?><section class="match-section"><div class="match-lost-summary"><span class="type-label">Your lost report #<?= (int)$lostItem['lost_id'] ?></span><h2><?= e($lostItem['item_name']) ?></h2><p><?= e($lostItem['category_name']) ?> · <?= e($lostItem['building_name'] ?? 'Location not specified') ?> · <?= e($lostItem['lost_date']) ?></p></div>
<?php $matches=$matchesByLostId[$lostItem['lost_id']] ?? []; if ($matches === []): ?><div class="empty-state">No possible matches found yet.</div><?php else: ?><div class="match-grid"><?php foreach ($matches as $match): ?><article class="match-card"><?php if ($match['image_path']): ?><img src="<?= e($match['image_path']) ?>" alt="<?= e($match['item_name']) ?>"><?php else: ?><div class="image-placeholder">No photo</div><?php endif; ?><div class="report-card-body"><div class="report-meta"><span>Found #<?= (int)$match['found_id'] ?></span><strong><?= (int)$match['match_score'] ?>% match</strong></div><h3><?= e($match['item_name']) ?></h3><p><?= e($match['building_name'] ?? 'Location not specified') ?> · <?= e($match['found_date']) ?></p><p><?= e($match['description']) ?></p><?php if ($match['already_claimed']): ?><span class="status status-under_review">Claim Pending</span><?php else: ?><form class="claim-form" method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="lost_id" value="<?= (int)$lostItem['lost_id'] ?>"><input type="hidden" name="found_id" value="<?= (int)$match['found_id'] ?>"><label>Proof of ownership<textarea name="ownership_details" rows="3" minlength="15" placeholder="Describe a detail only the owner would know..." required></textarea></label><button class="button button-primary" type="submit">Submit Claim</button></form><?php endif; ?></div></article><?php endforeach; ?></div><?php endif; ?></section><?php endforeach; ?>
</main></body></html>
