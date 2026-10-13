<?php
/**
 * Admin-Dashboard-Rahmen. Variablen kommen aus public/admin/index.php:
 * $sections, $section, $flash, $editing, $allArtists, $val
 */
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>HAVOC ADMIN | <?= e($sections[$section]) ?></title>
    <link rel="stylesheet" href="<?= e(url('assets/css/admin.css')) ?>">
    <script src="<?= e(url('assets/js/admin.js')) ?>" defer></script>
</head>
<body class="admin-dashboard">

    <aside>
        <a class="sidebar-logo" href="<?= e(url()) ?>" title="Zur Webseite">HAVOC</a>
        <nav class="sidebar-menu">
<?php foreach ($sections as $key => $label): ?>
            <a class="menu-item<?= $key === $section ? ' active' : '' ?>" href="<?= e(url('admin/?section=' . $key)) ?>"><?= e($label) ?></a>
<?php endforeach; ?>
        </nav>
        <form method="post" action="<?= e(url('admin/logout.php')) ?>" class="sidebar-logout-form">
            <?= csrf_field() ?>
            <button type="submit" class="sidebar-logout">LOGOUT</button>
        </form>
    </aside>

    <div class="content-area">
<?php if ($flash): ?>
        <div class="status-message is-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
<?php endif; ?>

<?php require __DIR__ . '/' . $section . '.php'; ?>
    </div>

</body>
</html>
