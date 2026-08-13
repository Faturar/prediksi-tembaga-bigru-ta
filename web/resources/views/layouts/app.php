<?php
$user = $_SESSION['user'] ?? null;
$appName = config('app')['name'];
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$flashError = $_SESSION['flash_error'] ?? null;
$flashSuccess = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_error']);
unset($_SESSION['flash_success']);
$navItems = [
    '/dashboard' => ['Dashboard', 'grid'],
    '/prices' => ['Data Harga', 'bars'],
    '/import' => ['Import CSV', 'upload'],
    '/models' => ['Model', 'chart'],
    '/predictions' => ['Prediksi', 'trend'],
    '/evaluation' => ['Evaluasi', 'check'],
    '/reports' => ['Laporan', 'doc'],
];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(($title ?? 'Dashboard') . ' - ' . $appName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php if ($user): ?>
        <div class="mobile-nav-bar">
            <button class="menu-toggle" type="button" aria-label="Buka menu" aria-controls="sidebar" aria-expanded="false">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="mobile-user-chip">
                <div>
                    <strong><?= e($user['name']) ?></strong>
                    <small><?= e($user['email']) ?></small>
                </div>
            </div>
        </div>
        <div class="nav-overlay" data-close-menu></div>
        <aside class="sidebar" id="sidebar">
            <div class="brand">
                <span>
                    <strong><?= e($appName) ?></strong>
                </span>
                <button class="sidebar-close" type="button" aria-label="Tutup menu" data-close-menu>&times;</button>
            </div>
            <nav class="nav-list" aria-label="Navigasi utama">
                <?php foreach ($navItems as $path => [$label, $icon]): ?>
                    <a class="<?= $currentPath === $path ? 'active' : '' ?>" href="<?= e($path) ?>">
                        <span class="nav-icon nav-icon-<?= e($icon) ?>"></span>
                        <span><?= e($label) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
            <form method="post" action="/logout" class="logout-form">
                <?= csrf_field() ?>
                <button class="link-button" type="submit">Logout</button>
            </form>
        </aside>
    <?php elseif ($currentPath !== '/login'): ?>
        <header class="public-header">
            <div class="public-brand">
                <a href="/" class="brand-link"><strong><?= e($appName) ?></strong></a>
            </div>
            <nav class="public-nav" aria-label="Navigasi publik">
                <a class="<?= $currentPath === '/' ? 'active' : '' ?>" href="/">Beranda</a>
                <a class="<?= $currentPath === '/historical' ? 'active' : '' ?>" href="/historical">Data Historis</a>
                <a class="<?= $currentPath === '/forecast' ? 'active' : '' ?>" href="/forecast">Prediksi</a>
                <a href="/login">Login Admin</a>
            </nav>
        </header>
    <?php endif; ?>
    <main class="<?= $user ? 'content' : ($currentPath === '/login' ? 'auth-content' : 'public-content') ?>">
        <?php if ($user): ?>
            <header class="page-bar">
                <div>
                    <p class="eyebrow"><?= e(format_indonesian_date(date('Y-m-d'))) ?></p>
                    <h1><?= e($title ?? 'Dashboard') ?></h1>
                </div>
                <div class="top-actions">
                    <div class="user-chip">
                        <span><?= e(substr($user['name'], 0, 1)) ?></span>
                        <div>
                            <strong><?= e($user['name']) ?></strong>
                            <small><?= e($user['email']) ?></small>
                        </div>
                    </div>
                </div>
            </header>
        <?php endif; ?>
        <?php if (!empty($flashSuccess)): ?><p class="alert alert-success"><?= e($flashSuccess) ?></p><?php endif; ?>
        <?php if (!empty($flashError)): ?><p class="alert"><?= e($flashError) ?></p><?php endif; ?>
        <?php require $viewPath; ?>
    </main>
    <?php if ($currentPath !== '/login'): ?><script src="/assets/js/table-pagination.js"></script><?php endif; ?>
    <?php if ($user): ?><script src="/assets/js/navigation.js"></script><?php endif; ?>
</body>
</html>
