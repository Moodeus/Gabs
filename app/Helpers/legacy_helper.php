<?php

/**
 * Sync CodeIgniter session data into $_SESSION for legacy PHP pages.
 */
function gabs_sync_legacy_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $ciSession = session();
    foreach ($ciSession->get() as $key => $value) {
        $_SESSION[$key] = $value;
    }
}

/**
 * Rewrite legacy HTML asset and navigation links for CI routing.
 */
function gabs_fix_legacy_html(string $html): string
{
    $cssBase = rtrim(base_url('assets/css'), '/') . '/';
    $html = preg_replace('/href="([a-z_]+\.css)"/i', 'href="' . $cssBase . '$1"', $html);
    $brandCss = '<link rel="stylesheet" href="' . $cssBase . 'brand.css">';
    if (stripos($html, 'brand.css') === false) {
        $html = preg_replace('/<\/head>/i', $brandCss . '</head>', $html, 1);
    }
    $html = preg_replace_callback('/action="([a-z_]+\.php)"/i', static function (array $matches): string {
        return 'action="' . site_url($matches[1]) . '"';
    }, $html);

    $legacyPages = [
        'welcome.php', 'logout.php', 'accounts.php', 'inventory.php', 'orders.php',
        'reports.php', 'settings.php', 'productinventory.php', 'producthistory.php',
        'ingredienthistory.php', 'receipt.php', 'export_receipt.php', 'index.php',
    ];

    foreach ($legacyPages as $legacy) {
        $page = preg_replace('/\.php$/', '', $legacy);
        $ciUrl = ($legacy === 'index.php') ? site_url('auth/login')
            : (($legacy === 'welcome.php') ? site_url('dashboard')
            : (($legacy === 'logout.php') ? site_url('auth/logout') : site_url($page)));
        $html = str_replace('href="' . $legacy . '"', 'href="' . $ciUrl . '"', $html);
        $html = str_replace("href=\"{$legacy}?", 'href="' . $ciUrl . '?', $html);
        $html = str_replace("value=\"{$legacy}?", 'value="' . $ciUrl . '?', $html);
    }

    return $html;
}
