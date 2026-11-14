<?php
$nav_current_page = $nav_current_page ?? '';
$isSuperAdmin = ($nav_role ?? '') === 'Super Admin';
$inventory_pages = ['ingredient-inventory', 'product-inventory'];
$report_pages = ['sales-report', 'ingredient-audit', 'product-audit', 'delivery-receipts', 'account-reports'];
$inInventorySection = in_array($nav_current_page, $inventory_pages, true);
$inReportSection = in_array($nav_current_page, $report_pages, true);

// Super Admin can switch functions off per role (Permissions page), so each
// link only shows if this user still has it.
$reportLinks = [];
if (can('reports.sales')) {
    $reportLinks['sales-report'] = 'Sales Report';
}
if (can('reports.ingredient_audit')) {
    $reportLinks['ingredient-audit'] = 'Ingredient Audit';
}
if (can('reports.product_audit')) {
    $reportLinks['product-audit'] = 'Product Audit';
}
if (can('reports.receipts')) {
    $reportLinks['delivery-receipts'] = 'Delivery Receipts';
}
if ($isSuperAdmin && table_ready('account_history')) {
    $reportLinks['account-reports'] = 'Account Reports';
}
$firstReport = array_key_first($reportLinks);
?>
<div class="header-bar">
    <div class="logo-container">
        <?= $this->include('layouts/brand_logo') ?>
    </div>
    <nav class="navbar">
        <div class="main-nav" id="main-nav-links">
            <a href="<?= site_url('dashboard') ?>" class="<?= $nav_current_page === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
            <?php if (can('inventory.view')): ?>
                <a href="<?= site_url('ingredient-inventory') ?>" class="<?= $inInventorySection ? 'active' : '' ?>">Inventory</a>
            <?php endif; ?>
            <?php if (can('orders.view')): ?>
                <a href="<?= site_url('orders') ?>" class="<?= $nav_current_page === 'orders' ? 'active' : '' ?>">Orders</a>
            <?php endif; ?>
            <?php if ($firstReport !== null): ?>
                <a href="<?= site_url($firstReport) ?>" class="<?= $inReportSection ? 'active' : '' ?>">Reports</a>
            <?php endif; ?>
            <?php if (can('accounts.view')): ?>
                <a href="<?= site_url('accounts') ?>" class="<?= $nav_current_page === 'accounts' ? 'active' : '' ?>">Accounts</a>
            <?php endif; ?>
            <?php if ($isSuperAdmin && permissions_panel_available()): ?>
                <a href="<?= site_url('permissions') ?>" class="<?= $nav_current_page === 'permissions' ? 'active' : '' ?>">Permissions</a>
            <?php endif; ?>
            <a href="<?= site_url('settings') ?>" class="<?= $nav_current_page === 'settings' ? 'active' : '' ?>">Settings</a>
            <a href="<?= site_url('auth/logout') ?>" class="logout-link">Logout</a>
        </div>
        <button class="hamburger" id="hamburger-button" aria-label="Toggle navigation menu">&#9776;</button>
    </nav>
</div>
<?php if ($inInventorySection): ?>
    <nav class="sub-navbar">
        <a href="<?= site_url('ingredient-inventory') ?>" class="<?= $nav_current_page === 'ingredient-inventory' ? 'active' : '' ?>">Ingredient Inventory</a>
        <a href="<?= site_url('product-inventory') ?>" class="<?= $nav_current_page === 'product-inventory' ? 'active' : '' ?>">Product Inventory</a>
    </nav>
<?php elseif ($inReportSection && $reportLinks !== []): ?>
    <nav class="sub-navbar">
        <?php foreach ($reportLinks as $path => $label): ?>
            <a href="<?= site_url($path) ?>" class="<?= $nav_current_page === $path ? 'active' : '' ?>"><?= esc($label) ?></a>
        <?php endforeach; ?>
    </nav>
<?php endif; ?>
