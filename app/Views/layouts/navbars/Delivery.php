<?php
$nav_current_page = $nav_current_page ?? '';
?>
<div class="header-bar">
    <div class="logo-container">
        <?= $this->include('layouts/brand_logo') ?>
    </div>
    <nav class="navbar">
        <div class="main-nav" id="main-nav-links">
            <a href="<?= site_url('dashboard') ?>" class="<?= $nav_current_page === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
            <?php if (can('orders.view')): ?>
                <a href="<?= site_url('orders') ?>" class="<?= $nav_current_page === 'orders' ? 'active' : '' ?>">Orders</a>
            <?php endif; ?>
            <a href="<?= site_url('settings') ?>" class="<?= $nav_current_page === 'settings' ? 'active' : '' ?>">Settings</a>
            <a href="<?= site_url('auth/logout') ?>" class="logout-link">Logout</a>
        </div>
        <button class="hamburger" id="hamburger-button" aria-label="Toggle navigation menu">&#9776;</button>
    </nav>
</div>
