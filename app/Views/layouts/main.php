<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($page_title ?? "Gab's Bakeshop") ?></title>
    <?php
        $cssDir = FCPATH . 'assets/css/';
        $assetVersion = static fn (string $file): string => is_file($cssDir . $file) ? (string) filemtime($cssDir . $file) : '1';
    ?>
    <?php if (!empty($page_css)): ?>
        <link rel="stylesheet" href="<?= base_url('assets/css/' . $page_css) ?>?v=<?= $assetVersion($page_css) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= base_url('assets/css/brand.css') ?>?v=<?= $assetVersion('brand.css') ?>">
</head>
<?php
    // Super Admin's nav is identical to Admin's, so it reuses that file
    // instead of a second copy that could drift out of sync.
    $navRoleFile = ($nav_role ?? 'Admin') === 'Super Admin' ? 'Admin' : ($nav_role ?? 'Admin');
?>
<body>
    <?= $this->include('layouts/navbars/' . esc($navRoleFile)) ?>
    <?= $content ?? '' ?>
    <?= $this->include('layouts/hamburger_script') ?>
    <?= $this->include('layouts/modal_script') ?>
    <?= $this->include('layouts/table_search_script') ?>
    <?= $this->include('layouts/file_pick_script') ?>
    <?php if (in_array($nav_role ?? '', ['Admin', 'Super Admin'], true) && can('orders.approve')): ?>
        <?= $this->include('layouts/live_orders_script') ?>
    <?php endif; ?>
</body>
</html>
