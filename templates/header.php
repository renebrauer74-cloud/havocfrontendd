<?php
/**
 * Gemeinsamer Seitenkopf.
 *
 * Erwartete Variablen:
 *   $pageTitle  string        Titel hinter "HAVOC AGENCY | "
 *   $layout     'app'|'editorial'   Grundlayout (siehe base.css)
 *   $styles     list<string>  zusätzliche CSS-Dateien aus assets/css/
 *   $backLink   array{0:string,1:string}|null  statt Navigation nur ein Zurück-Link [href, label]
 */
$layout ??= 'app';
$styles ??= [];
$backLink ??= null;

$navItems = [
    ''              => 'START //',
    'roster.php'    => 'ARTISTS //',
    'kalender.php'  => 'TOUR DATES //',
    'shows.php'     => 'SHOWS //',
    'team.php'      => 'TEAM //',
    'stu.php'       => 'STU //',
    'kontakt.php'   => 'CONTACT //',
];
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= e(url('assets/css/base.css')) ?>">
<?php foreach ($styles as $style): ?>
    <link rel="stylesheet" href="<?= e(url('assets/css/' . $style)) ?>">
<?php endforeach; ?>
    <script src="<?= e(url('assets/js/site.js')) ?>" defer></script>
</head>
<body class="layout-<?= e($layout) ?>">

<header class="site-header">
    <a href="<?= e(url()) ?>" class="logo-link">
        <img src="<?= e(url('assets/img/havoc-logo.jpeg')) ?>" alt="HAVOC AGENCY">
    </a>
    <nav>
<?php if ($backLink): ?>
        <a href="<?= e(url($backLink[0])) ?>"><?= e($backLink[1]) ?></a>
<?php else: ?>
<?php foreach ($navItems as $path => $label): ?>
        <a href="<?= e(url($path)) ?>"<?= $currentScript === ($path ?: 'index.php') ? ' class="active"' : '' ?>><?= e($label) ?></a>
<?php endforeach; ?>
        <a href="<?= e(url('admin/')) ?>" class="admin-btn">ADMIN</a>
<?php endif; ?>
    </nav>
</header>
