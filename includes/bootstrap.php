<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

if (PHP_SAPI !== 'cli') {
    register_shutdown_function(function (): void {
        $status = http_response_code();
        if ($status >= 300 && $status < 400) {
            return;
        }
        renderGlobalInterface();
    });
}

set_exception_handler(function (Throwable $exception): void {
    error_log($exception->__toString());
    http_response_code(500);
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Application error: {$exception->getMessage()}\n");
        return;
    }
    require __DIR__ . '/../errors/500.php';
});

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function validCsrfToken(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function renderGlobalInterface(): void
{
    $user = currentUser();
    $sidebar = '';
    $utilityBar = '';

    $renderIcon = static function (string $name): string {
        $paths = [
            'home' => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10.5V20h13v-9.5M9.5 20v-6h5v6"/>',
            'lost' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4M11 8v6M8 11h6"/>',
            'found' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4M8 11h6"/>',
            'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 4.5 4.5"/>',
            'match' => '<path d="M8 7h9a4 4 0 0 1 0 8h-2M16 17H7a4 4 0 0 1 0-8h2"/><path d="m13 12-2 2 2 2m-2-8 2 2-2 2"/>',
            'reports' => '<path d="M5 3h14v18H5zM8 7h8M8 11h8M8 15h5"/>',
            'inventory' => '<path d="m4 7 8-4 8 4-8 4zM4 7v10l8 4 8-4V7M12 11v10"/>',
            'claims' => '<path d="M9 11l2 2 4-5"/><circle cx="12" cy="12" r="9"/>',
            'returns' => '<path d="M9 7H5v4M5.5 10A8 8 0 1 1 5 15"/>',
            'analytics' => '<path d="M4 20V10h4v10M10 20V4h4v16M16 20v-7h4v7"/>',
            'users' => '<circle cx="9" cy="8" r="3"/><path d="M3 20v-2a6 6 0 0 1 12 0v2M16 5a3 3 0 0 1 0 6M17 14a5 5 0 0 1 4 5v1"/>',
            'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
            'plus' => '<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>',
            'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19 13.5v-3l-2-.7-.7-1.7.9-2-2.1-2.1-2 .9-1.7-.7L10.5 2h-3l-.7 2.2-1.7.7-2-.9L1 6.1l.9 2-.7 1.7-2 .7v3l2 .7.7 1.7-.9 2L3.1 20l2-.9 1.7.7.7 2.2h3l.7-2.2 1.7-.7 2 .9 2.1-2.1-.9-2 .7-1.7z" transform="translate(2) scale(.83)"/>',
            'departments' => '<path d="M4 21V7l8-4 8 4v14M8 10h2M14 10h2M8 14h2M14 14h2M10 21v-3h4v3"/>',
            'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
            'profile' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
            'logout' => '<path d="M10 4H4v16h6M14 8l4 4-4 4M8 12h10"/>',
            'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
            'moon' => '<path d="M20 15.5A8 8 0 0 1 8.5 4 8.5 8.5 0 1 0 20 15.5z"/>',
        ];
        return '<svg class="ui-icon" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . ($paths[$name] ?? $paths['home']) . '</svg>';
    };

    if ($user !== null) {
        $role = $user['role'];
        $links = [
            ['Dashboard', '/lost-and-found/dashboard.php', 'home'],
        ];
        if ($role === 'student') {
            $links = array_merge($links, [
                ['Report Lost Item', '/lost-and-found/report_lost.php', 'lost'],
                ['Report Found Item', '/lost-and-found/report_found.php', 'found'],
                ['Search Items', '/lost-and-found/search.php', 'search'],
                ['Possible Matches', '/lost-and-found/matches.php', 'match'],
                ['My Reports', '/lost-and-found/my_reports.php', 'reports'],
            ]);
        } elseif ($role === 'staff') {
            $links = array_merge($links, [
                ['Reports', '/lost-and-found/staff/reports.php', 'reports'],
                ['Inventory', '/lost-and-found/staff/inventory.php', 'inventory'],
                ['Claims', '/lost-and-found/staff/claims.php', 'claims'],
                ['Returns', '/lost-and-found/staff/returns.php', 'returns'],
            ]);
        } else {
            $links = array_merge($links, [
                ['Analytics', '/lost-and-found/admin/analytics.php', 'analytics'],
                ['Users', '/lost-and-found/admin/manage_users.php', 'users'],
                ['Registration Requests', '/lost-and-found/admin/users.php', 'clock'],
                ['Create Staff', '/lost-and-found/admin/create_staff.php', 'plus'],
                ['System Data', '/lost-and-found/admin/catalog.php', 'settings'],
                ['Departments', '/lost-and-found/admin/departments.php', 'departments'],
                ['Reports', '/lost-and-found/staff/reports.php', 'reports'],
                ['Claims', '/lost-and-found/staff/claims.php', 'claims'],
                ['Returns', '/lost-and-found/staff/returns.php', 'returns'],
            ]);
        }
        $links[] = ['Notifications', '/lost-and-found/notifications.php', 'bell'];
        $links[] = ['My Profile', '/lost-and-found/profile.php', 'profile'];

        $nav = '';
        foreach ($links as [$label, $href, $iconName]) {
            $nav .= '<a class="sidebar-link" href="' . e($href) . '"><span>' . $renderIcon($iconName) . '</span><b>' . e($label) . '</b></a>';
        }
        $initial = strtoupper(substr((string) $user['name'], 0, 1));
        $unreadStatement = getDatabaseConnection()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0');
        $unreadStatement->execute(['user_id' => $user['user_id']]);
        $unreadCount = (int) $unreadStatement->fetchColumn();
        $currentFile = basename((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
        $pageTitles = ['dashboard.php'=>'Dashboard','analytics.php'=>'Analytics','manage_users.php'=>'Manage Users','users.php'=>'Registration Requests','create_staff.php'=>'Create Staff','catalog.php'=>'System Data','departments.php'=>'Departments','reports.php'=>'Reports','inventory.php'=>'Inventory','claims.php'=>'Claims','returns.php'=>'Returns','report_lost.php'=>'Report Lost Item','report_found.php'=>'Report Found Item','search.php'=>'Search Items','matches.php'=>'Possible Matches','my_reports.php'=>'My Reports','notifications.php'=>'Notifications','profile.php'=>'My Profile','report_details.php'=>'Report Details','edit_report.php'=>'Edit Report'];
        $pageTitle = $pageTitles[$currentFile] ?? 'Lost & Found';
        $badge = $unreadCount > 0 ? '<span class="notification-badge">' . min($unreadCount, 99) . '</span>' : '';
        $sidebar = '<button id="sidebarToggle" class="sidebar-toggle" type="button" aria-label="Open navigation">' . $renderIcon('menu') . '</button>'
            . '<div id="sidebarBackdrop" class="sidebar-backdrop"></div>'
            . '<aside id="appSidebar" class="app-sidebar">'
            . '<a class="sidebar-brand" href="/lost-and-found/dashboard.php"><img src="/lost-and-found/assets/images/liwa-university-logo.png" alt="Liwa University"><span>Lost &amp; Found</span></a>'
            . '<nav class="sidebar-nav">' . $nav . '</nav>'
            . '<div class="sidebar-user"><span class="user-avatar">' . e($initial) . '</span><div><strong>' . e($user['name']) . '</strong><small>' . e(ucfirst($role)) . '</small></div><a href="/lost-and-found/logout.php" aria-label="Logout">' . $renderIcon('logout') . '</a></div>'
            . '</aside>';
        $utilityBar = '<header class="app-utility-bar"><div><span class="utility-kicker">Liwa University</span><strong>' . e($pageTitle) . '</strong></div><div class="utility-actions">'
            . '<a class="notification-button' . ($unreadCount > 0 ? ' has-unread' : '') . '" href="/lost-and-found/notifications.php" aria-label="Notifications">' . $renderIcon('bell') . $badge . '</a>'
            . '<button id="languageToggle" class="utility-button language-toggle" type="button" aria-label="Switch language">العربية</button>'
            . '<button id="themeToggle" class="utility-button theme-toggle" type="button" aria-label="Toggle dark mode"><span>' . $renderIcon('moon') . '</span></button>'
            . '</div></header>';
    }

    $publicTools = $user === null ? '<div class="global-tools" aria-label="Display settings"><button id="languageToggle" class="tool-button language-toggle" type="button" aria-label="Switch language">العربية</button><button id="themeToggle" class="tool-button theme-toggle" type="button" aria-label="Toggle dark mode"><span aria-hidden="true">☾</span></button></div>' : '';
    echo $sidebar . $utilityBar . $publicTools . '<script src="/lost-and-found/assets/js/app.js?v=20261002-5"></script>';
}

function requireLogin(): void
{
    if (currentUser() === null) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin(): void
{
    requireLogin();

    if ((currentUser()['role'] ?? '') !== 'admin') {
        http_response_code(403);
        exit('Access denied. Administrator permission is required.');
    }
}

function requireStaff(): void
{
    requireLogin();

    if (!in_array(currentUser()['role'] ?? '', ['staff', 'admin'], true)) {
        http_response_code(403);
        exit('Access denied. Staff permission is required.');
    }
}

function uploadItemImage(array $file, array &$errors): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $errors[] = 'The image could not be uploaded.';
        return null;
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        $errors[] = 'The image must not exceed 5 MB.';
        return null;
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) {
        $errors[] = 'Only JPG, PNG, and WEBP images are allowed.';
        return null;
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    $destination = __DIR__ . '/../uploads/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        $errors[] = 'The image could not be saved.';
        return null;
    }

    return 'uploads/' . $filename;
}

function createNotification(int $userId, string $title, string $message): void
{
    $statement = getDatabaseConnection()->prepare(
        'INSERT INTO notifications (user_id, title, message) VALUES (:user_id, :title, :message)'
    );
    $statement->execute(['user_id' => $userId, 'title' => $title, 'message' => $message]);
}

function redirectWithFlash(string $location, string $type, string $message): never
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    header('Location: ' . $location);
    exit;
}

function pullFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

function pagination(int $total, int $page, int $perPage = 10): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $page = max(1, min($page, $pages));
    return ['page' => $page, 'pages' => $pages, 'per_page' => $perPage, 'offset' => ($page - 1) * $perPage];
}
