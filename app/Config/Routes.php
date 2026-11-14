<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', static function () {
    return redirect()->to('auth/login');
});

$routes->get('auth/login', 'Auth::login');
$routes->post('auth/login', 'Auth::login');
$routes->get('auth/logout', 'Auth::logout');

$routes->get('dashboard', 'Dashboard::index', ['filter' => 'auth']);
$routes->get('welcome', static function () {
    return redirect()->to('dashboard');
}, ['filter' => 'auth']);

// Common to every role
$routes->match(['GET', 'POST'], 'settings', 'Settings::index', ['filter' => 'auth']);

// Each route below is gated twice: `auth:` says which roles have the page by
// default, and `perm:` lets Super Admin switch it off for a role (see the
// Permissions page). Super Admin always passes both.
$gate = static fn (string $roles, string $permission): array => ['filter' => ['auth:' . $roles, 'perm:' . $permission]];

// Admin, Branch, Delivery
$routes->match(['GET', 'POST'], 'orders', 'Orders::index', $gate('Admin,Branch,Delivery', 'orders.view'));
$routes->get('orders/pending-summary/(:num)', 'Orders::pendingSummary/$1', $gate('Admin', 'orders.approve'));
$routes->get('orders/status/(:segment)', 'Orders::index/$1', $gate('Admin,Branch,Delivery', 'orders.view'));
$routes->get('orders/browse/(:segment)/(:segment)/(:num)', 'Orders::browse/$1/$2/$3', $gate('Admin,Branch,Delivery', 'orders.view'));
$routes->get('orders/(:num)/invoice', 'Orders::invoice/$1', $gate('Admin,Branch', 'orders.invoice'));
$routes->get('orders/(:num)/billing-statement', 'Orders::billingStatement/$1', $gate('Admin,Branch', 'orders.invoice'));

// Admin, Branch
$routes->get('delivery-receipts', 'DeliveryReceipts::index', $gate('Admin,Branch', 'reports.receipts'));
$routes->get('delivery-receipts/page/(:num)', 'DeliveryReceipts::index/$1', $gate('Admin,Branch', 'reports.receipts'));
$routes->get('delivery-receipts/image/(:segment)', 'DeliveryReceipts::image/$1', $gate('Admin,Branch', 'reports.receipts'));
$routes->get('delivery-receipts/filter/(:segment)', 'DeliveryReceipts::filtered/$1', $gate('Admin,Branch', 'reports.receipts'));
$routes->get('delivery-receipts/filter/(:segment)/page/(:num)', 'DeliveryReceipts::filtered/$1/$2', $gate('Admin,Branch', 'reports.receipts'));
// Narrowed to one branch (Admin / Super Admin), alone or with the payment filter
$routes->get('delivery-receipts/branch/(:num)', 'DeliveryReceipts::branch/$1', $gate('Admin,Branch', 'reports.receipts'));
$routes->get('delivery-receipts/branch/(:num)/page/(:num)', 'DeliveryReceipts::branch/$1/$2', $gate('Admin,Branch', 'reports.receipts'));
$routes->get('delivery-receipts/filter/(:segment)/branch/(:num)', 'DeliveryReceipts::filteredBranch/$1/$2', $gate('Admin,Branch', 'reports.receipts'));
$routes->get('delivery-receipts/filter/(:segment)/branch/(:num)/page/(:num)', 'DeliveryReceipts::filteredBranch/$1/$2/$3', $gate('Admin,Branch', 'reports.receipts'));

// Admin only
$routes->match(['GET', 'POST'], 'accounts', 'Accounts::index', $gate('Admin', 'accounts.view'));
$routes->get('accounts/browse/(:segment)/(:segment)/(:num)', 'Accounts::browse/$1/$2/$3', $gate('Admin', 'accounts.view'));
$routes->match(['GET', 'POST'], 'ingredient-inventory', 'IngredientInventory::index', $gate('Admin', 'inventory.view'));
$routes->get('ingredient-inventory/browse/(:segment)/(:segment)/(:num)', 'IngredientInventory::browse/$1/$2/$3', $gate('Admin', 'inventory.view'));
$routes->match(['GET', 'POST'], 'product-inventory', 'ProductInventory::index', $gate('Admin', 'inventory.view'));
$routes->get('product-inventory/browse/(:segment)/(:segment)/(:num)', 'ProductInventory::browse/$1/$2/$3', $gate('Admin', 'inventory.view'));
$routes->get('sales-report', 'SalesReport::index', $gate('Admin', 'reports.sales'));
$routes->get('sales-report/page/(:num)', 'SalesReport::index/$1', $gate('Admin', 'reports.sales'));
$routes->get('sales-report/date/(:segment)', 'SalesReport::date/$1', $gate('Admin', 'reports.sales'));
$routes->get('sales-report/date/(:segment)/page/(:num)', 'SalesReport::date/$1/$2', $gate('Admin', 'reports.sales'));
$routes->get('ingredient-audit', 'IngredientAudit::index', $gate('Admin', 'reports.ingredient_audit'));
$routes->get('ingredient-audit/page/(:num)', 'IngredientAudit::index/$1', $gate('Admin', 'reports.ingredient_audit'));
$routes->get('ingredient-audit/filter/(:segment)', 'IngredientAudit::filtered/$1', $gate('Admin', 'reports.ingredient_audit'));
$routes->get('ingredient-audit/filter/(:segment)/page/(:num)', 'IngredientAudit::filtered/$1/$2', $gate('Admin', 'reports.ingredient_audit'));
$routes->post('ingredient-audit', 'IngredientAudit::search', $gate('Admin', 'reports.ingredient_audit'));
$routes->get('ingredient-audit/browse/(:segment)/(:segment)/(:num)', 'IngredientAudit::browse/$1/$2/$3', $gate('Admin', 'reports.ingredient_audit'));
$routes->post('product-audit', 'ProductAudit::search', $gate('Admin', 'reports.product_audit'));
$routes->get('product-audit/browse/(:segment)/(:segment)/(:num)', 'ProductAudit::browse/$1/$2/$3', $gate('Admin', 'reports.product_audit'));
$routes->get('product-audit', 'ProductAudit::index', $gate('Admin', 'reports.product_audit'));
$routes->get('product-audit/page/(:num)', 'ProductAudit::index/$1', $gate('Admin', 'reports.product_audit'));
$routes->get('product-audit/filter/(:segment)', 'ProductAudit::filtered/$1', $gate('Admin', 'reports.product_audit'));
$routes->get('product-audit/filter/(:segment)/page/(:num)', 'ProductAudit::filtered/$1/$2', $gate('Admin', 'reports.product_audit'));
$routes->get('ingredient-inventory/export', 'IngredientInventory::export', $gate('Admin', 'export.pdf'));
$routes->get('product-inventory/export', 'ProductInventory::export', $gate('Admin', 'export.pdf'));
$routes->get('sales-report/export/(:segment)/(:segment)', 'SalesReport::export/$1/$2', $gate('Admin', 'export.pdf'));

// Super Admin only
if (table_ready('account_history')) {
    $routes->get('account-reports', 'AccountReports::index', ['filter' => 'auth:Super Admin']);
    $routes->get('account-reports/page/(:num)', 'AccountReports::index/$1', ['filter' => 'auth:Super Admin']);
}
\App\Libraries\Support\Internal\Access\PermissionsPanel::routes($routes);
