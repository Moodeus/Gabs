<?php

/**
 * Role permissions: what each role can do, and which of those Super Admin
 * has switched off.
 *
 * The registry below lists every function that can be switched, grouped
 * for the Permissions page, with the roles that have it by default. A
 * function is allowed unless Super Admin turned it off (a row in
 * role_permissions with Enabled = 0). Super Admin always has everything.
 */

if (!function_exists('table_ready')) {
    /**
     * Whether a database table exists, checked once per request. Lets
     * features that need a newer table (permissions, account history) switch
     * themselves off cleanly if the code was deployed before the database
     * was updated, instead of erroring. A failed check counts as "not there".
     */
    function table_ready(string $table): bool
    {
        static $known = [];

        if (!isset($known[$table])) {
            try {
                $known[$table] = db_connect()->tableExists($table);
            } catch (Throwable $e) {
                $known[$table] = false;
            }
        }

        return $known[$table];
    }
}

if (!function_exists('column_ready')) {
    /**
     * Whether a table has a given column, checked once per request - the
     * column equivalent of table_ready(), so a newer column (e.g. the
     * Settings page's phone number) switches itself off cleanly if the code
     * was deployed before the database was updated.
     */
    function column_ready(string $table, string $column): bool
    {
        static $known = [];

        $key = $table . '.' . $column;
        if (!isset($known[$key])) {
            try {
                $known[$key] = db_connect()->fieldExists($column, $table);
            } catch (Throwable $e) {
                $known[$key] = false;
            }
        }

        return $known[$key];
    }
}

if (!function_exists('permissions_panel_available')) {
    /** The Permissions panel only exists once both of its tables do. */
    function permissions_panel_available(): bool
    {
        return table_ready('role_permissions') && table_ready('account_permissions');
    }
}

if (!function_exists('permission_roles')) {
    /** Roles whose functions Super Admin can switch. */
    function permission_roles(): array
    {
        return ['Admin', 'Branch', 'Delivery'];
    }
}

if (!function_exists('permission_registry')) {
    /**
     * @return array<string, array<string, array{label: string, roles: list<string>}>>
     */
    function permission_registry(): array
    {
        $admin = ['Admin'];

        return [
            'Pages' => [
                'orders.view' => ['label' => 'Orders page', 'roles' => ['Admin', 'Branch', 'Delivery']],
                'inventory.view' => ['label' => 'Inventory pages (Ingredient and Product)', 'roles' => $admin],
                'reports.sales' => ['label' => 'Sales Report', 'roles' => $admin],
                'reports.ingredient_audit' => ['label' => 'Ingredient Audit', 'roles' => $admin],
                'reports.product_audit' => ['label' => 'Product Audit', 'roles' => $admin],
                'reports.receipts' => ['label' => 'Delivery Receipts', 'roles' => ['Admin', 'Branch']],
                'accounts.view' => ['label' => 'Accounts page', 'roles' => $admin],
                'dashboard.sales_chart' => ['label' => 'Dashboard monthly sales chart', 'roles' => $admin],
            ],
            'Orders' => [
                'orders.place' => ['label' => 'Place orders', 'roles' => ['Branch']],
                'orders.cancel' => ['label' => 'Cancel orders', 'roles' => ['Branch']],
                'orders.approve' => ['label' => 'Approve or deny orders, and void items on delivered orders', 'roles' => $admin],
                'orders.deliver' => ['label' => 'Mark orders delivered (with proof photo)', 'roles' => ['Delivery']],
                'orders.pay' => ['label' => 'Mark orders paid', 'roles' => ['Admin', 'Delivery']],
                'orders.invoice' => ['label' => 'View and print delivery receipts and acknowledgements', 'roles' => ['Admin', 'Branch']],
            ],
            'Inventory' => [
                'inventory.add_item' => ['label' => 'Add ingredients and products', 'roles' => $admin],
                'inventory.adjust_stock' => ['label' => 'Add or minus stock', 'roles' => $admin],
                'inventory.edit_settings' => ['label' => 'Edit settings (cost, price, low stock warning, type)', 'roles' => $admin],
                'inventory.recipe' => ['label' => 'Edit product recipes', 'roles' => $admin],
                'inventory.delete' => ['label' => 'Delete ingredients and products', 'roles' => $admin],
                'export.pdf' => ['label' => 'Export to PDF (inventory and Sales Report)', 'roles' => $admin],
            ],
            'Accounts' => [
                'accounts.create' => ['label' => 'Create accounts', 'roles' => $admin],
                'accounts.edit' => ['label' => 'Edit accounts', 'roles' => $admin],
                'accounts.deactivate' => ['label' => 'Deactivate accounts', 'roles' => $admin],
            ],
        ];
    }
}

if (!function_exists('permission_defaults')) {
    /** key => roles that have it by default, flattened across groups. */
    function permission_defaults(): array
    {
        $flat = [];
        foreach (permission_registry() as $group) {
            foreach ($group as $key => $def) {
                $flat[$key] = $def['roles'];
            }
        }

        return $flat;
    }
}

if (!function_exists('permissions_switched_off')) {
    /**
     * "Role|permission" pairs Super Admin has turned off, read once per
     * request. If the table isn't there yet (e.g. before the database
     * update is applied), nothing is switched off rather than everything
     * breaking.
     *
     * @return array<string, true>
     */
    function permissions_switched_off(bool $refresh = false): array
    {
        static $off = null;

        if ($off === null || $refresh) {
            $off = [];

            if (!table_ready('role_permissions')) {
                return $off;
            }

            try {
                $rows = db_connect()->table('role_permissions')
                    ->select('Role, Permission')
                    ->where('Enabled', 0)
                    ->get()
                    ->getResultArray();

                foreach ($rows as $row) {
                    $off[$row['Role'] . '|' . $row['Permission']] = true;
                }
            } catch (Throwable $e) {
                $off = [];
            }
        }

        return $off;
    }
}

if (!function_exists('account_permissions_switched_off')) {
    /**
     * Permissions Super Admin switched off for one specific account, as
     * a set (permission => true). Same failure behaviour as the role
     * version: a missing table means nothing is switched off.
     *
     * @return array<string, true>
     */
    function account_permissions_switched_off(int $accountId): array
    {
        if (!table_ready('account_permissions')) {
            return [];
        }

        try {
            $rows = db_connect()->table('account_permissions')
                ->select('Permission')
                ->where('Account_ID', $accountId)
                ->where('Enabled', 0)
                ->get()
                ->getResultArray();
        } catch (Throwable $e) {
            return [];
        }

        return array_fill_keys(array_column($rows, 'Permission'), true);
    }
}

if (!function_exists('can')) {
    /**
     * Whether the signed-in user may use this function. Super Admin always
     * may. Anyone else needs it by default for their role, and it must be
     * on both for their role and for their own account (Super Admin can
     * switch either off on the Permissions page).
     */
    function can(string $permission): bool
    {
        static $accountOff = null;

        $role = (string) session()->get('role');

        if ($role === 'Super Admin') {
            return true;
        }

        $defaults = permission_defaults();

        if (!isset($defaults[$permission]) || !in_array($role, $defaults[$permission], true)) {
            return false;
        }

        if (isset(permissions_switched_off()[$role . '|' . $permission])) {
            return false;
        }

        $accountOff ??= account_permissions_switched_off((int) session()->get('user_ID'));

        return !isset($accountOff[$permission]);
    }
}
