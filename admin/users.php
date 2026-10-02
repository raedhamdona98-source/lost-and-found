<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
requireAdmin();

$pdo = getDatabaseConnection();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validCsrfToken($_POST['csrf_token'] ?? null)) {
        $message = 'Your session expired. Please try again.';
    } else {
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $action = (string) ($_POST['action'] ?? '');
        $status = $action === 'approve' ? 'active' : ($action === 'reject' ? 'suspended' : '');

        if ($userId && $status !== '') {
            $statement = $pdo->prepare("UPDATE users SET account_status = :status WHERE user_id = :user_id AND role = 'student' AND account_status = 'pending'");
            $statement->execute(['status' => $status, 'user_id' => $userId]);
            $message = $status === 'active' ? 'Registration approved successfully.' : 'Registration rejected.';
        }
    }
}

$pendingUsers = $pdo->query("SELECT user_id, name, student_id, email, phone, created_at FROM users WHERE role = 'student' AND account_status = 'pending' ORDER BY created_at ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Registration Requests</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
<header class="site-header"><a class="brand" href="../dashboard.php">Liwa University Lost & Found</a><nav><a href="../dashboard.php">Dashboard</a><a class="button button-outline" href="../logout.php">Logout</a></nav></header>
<main class="admin-page"><p class="eyebrow">User management</p><h1>Registration Requests</h1><p class="form-intro">Review student details before activating an account.</p>
<?php if ($message !== ''): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<?php if ($pendingUsers === []): ?><div class="empty-state">No pending registration requests.</div><?php else: ?>
<div class="table-wrap"><table><thead><tr><th>Name</th><th>Student ID</th><th>Email</th><th>Phone</th><th>Submitted</th><th>Action</th></tr></thead><tbody>
<?php foreach ($pendingUsers as $pendingUser): ?><tr><td><?= e($pendingUser['name']) ?></td><td><?= e((string) $pendingUser['student_id']) ?></td><td><?= e($pendingUser['email']) ?></td><td><?= e((string) $pendingUser['phone']) ?></td><td><?= e($pendingUser['created_at']) ?></td><td><form class="inline-actions" method="post"><input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>"><input type="hidden" name="user_id" value="<?= (int) $pendingUser['user_id'] ?>"><button class="action-approve" name="action" value="approve">Approve</button><button class="action-reject" name="action" value="reject">Reject</button></form></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?></main></body></html>
