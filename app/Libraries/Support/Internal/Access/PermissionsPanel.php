<?php

namespace App\Libraries\Support\Internal\Access;

use App\Controllers\BaseController;
use App\Models\AccountModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Router\RouteCollection;

/**
 * Everything that changes who can do what, in one file: the routes, the
 * page(s) Super Admin uses to switch functions off for a role or a single
 * account, the saving, and the switch styling.
 *
 * What is switchable, and how a function is checked (`can()`), lives in
 * app/Helpers/permissions_helper.php; PermissionFilter and the controllers
 * only ever read those rules. This file is the only place that writes them
 * (tables `role_permissions` and `account_permissions`).
 *
 * Wired up by one line in app/Config/Routes.php:
 *     \App\Libraries\Support\Internal\Access\PermissionsPanel::routes($routes);
 */
class PermissionsPanel extends BaseController
{
    public static function routes(RouteCollection $routes): void
    {
        // Until the database has both permission tables, the panel simply
        // isn't there (its address gives a 404 and the menu link is hidden),
        // and every function stays allowed as it normally is.
        if (!permissions_panel_available()) {
            return;
        }

        $routes->match(['GET', 'POST'], 'permissions', '\\' . self::class . '::roles', ['filter' => 'auth:Super Admin']);
        $routes->match(['GET', 'POST'], 'permissions/account/(:num)', '\\' . self::class . '::account/$1', ['filter' => 'auth:Super Admin']);
    }

    /** Permissions for a whole role at once (the default target). */
    public function roles()
    {
        if ($this->request->is('post')) {
            $this->saveRoles();

            return redirect()->to('permissions');
        }

        return $this->page($this->rolesHtml(permissions_switched_off(true)), null);
    }

    /** Permissions for one specific account, on top of its role's. */
    public function account(int $accountId)
    {
        $account = model(AccountModel::class)->find($accountId);

        if (!$account || !in_array($account['Role'], permission_roles(), true)) {
            throw PageNotFoundException::forPageNotFound();
        }

        if ($this->request->is('post')) {
            $this->saveAccount($account);

            return redirect()->to('permissions/account/' . $accountId);
        }

        return $this->page(
            $this->accountHtml($account, permissions_switched_off(true), account_permissions_switched_off($accountId)),
            $accountId
        );
    }

    // ---------------------------------------------------------------
    // Saving
    // ---------------------------------------------------------------

    /**
     * A checked box means the role keeps that function, unchecked means
     * it's switched off. Only role/function pairs that exist by default are
     * stored - the form can't invent a permission a role never had.
     */
    private function saveRoles(): void
    {
        $posted = $this->request->getPost('perm');
        $posted = is_array($posted) ? $posted : [];

        $db = db_connect();
        $db->transStart();

        foreach (permission_defaults() as $permission => $defaultRoles) {
            foreach ($defaultRoles as $role) {
                if (!in_array($role, permission_roles(), true)) {
                    continue;
                }

                $db->table('role_permissions')->replace([
                    'Role' => $role,
                    'Permission' => $permission,
                    'Enabled' => isset($posted[$role][$permission]) ? 1 : 0,
                ]);
            }
        }

        $db->transComplete();

        session()->setFlashdata($db->transStatus() ? 'msg' : 'error', $db->transStatus() ? 'Permissions saved.' : 'Could not save permissions.');
    }

    /**
     * Functions the account's whole role has switched off are locked (they
     * can't be turned back on from here), so only the rest are stored.
     *
     * @param array<string, mixed> $account
     */
    private function saveAccount(array $account): void
    {
        $posted = $this->request->getPost('perm');
        $posted = is_array($posted) ? $posted : [];
        $roleOff = permissions_switched_off(true);

        $db = db_connect();
        $db->transStart();

        foreach (permission_defaults() as $permission => $defaultRoles) {
            if (!in_array($account['Role'], $defaultRoles, true) || isset($roleOff[$account['Role'] . '|' . $permission])) {
                continue;
            }

            $db->table('account_permissions')->replace([
                'Account_ID' => (int) $account['Account_ID'],
                'Permission' => $permission,
                'Enabled' => isset($posted[$permission]) ? 1 : 0,
            ]);
        }

        $db->transComplete();

        session()->setFlashdata(
            $db->transStatus() ? 'msg' : 'error',
            $db->transStatus() ? "Permissions saved for {$account['Name']}." : 'Could not save permissions.'
        );
    }

    // ---------------------------------------------------------------
    // Pages
    // ---------------------------------------------------------------

    private function page(string $body, ?int $targetAccountId)
    {
        $content = $this->styles() . $this->targetPicker($targetAccountId) . $this->flash() . $body;

        return view('layouts/main', [
            'content' => '<div class="page-content"><div class="dashboard-content"><h1>Permissions</h1>' . $content . '</div></div>',
            'page_title' => "Permissions - Gab's Bakeshop",
            'page_css' => 'producthistory.css',
            'nav_role' => session()->get('role'),
            'nav_current_page' => 'permissions',
        ]);
    }

    private function flash(): string
    {
        $out = '';

        if ($msg = session()->getFlashdata('msg')) {
            $out .= '<div class="message success">' . esc($msg) . '</div>';
        }
        if ($error = session()->getFlashdata('error')) {
            $out .= '<div class="message error">' . esc($error) . '</div>';
        }

        return $out;
    }

    /** "Who are you changing?" - the whole roles, or one specific account. */
    private function targetPicker(?int $targetAccountId): string
    {
        $accounts = model(AccountModel::class)
            ->whereIn('Role', permission_roles())
            ->orderBy('Role')
            ->orderBy('Name')
            ->findAll();

        $byRole = [];
        foreach ($accounts as $acc) {
            $byRole[$acc['Role']][] = $acc;
        }

        ob_start(); ?>
        <div class="filter-tabs permissions-target">
            <label for="permission-target">Change permissions for</label>
            <select id="permission-target" onchange="window.location.href = this.value">
                <option value="<?= esc(site_url('permissions')) ?>"<?= $targetAccountId === null ? ' selected' : '' ?>>Whole roles (Admin, Branch, Delivery)</option>
                <?php foreach ($byRole as $role => $list): ?>
                    <optgroup label="<?= esc($role) ?> accounts">
                        <?php foreach ($list as $acc): ?>
                            <option value="<?= esc(site_url('permissions/account/' . $acc['Account_ID'])) ?>"<?= $targetAccountId === (int) $acc['Account_ID'] ? ' selected' : '' ?>><?= esc($acc['Name']) ?> (<?= esc($acc['Username']) ?>)</option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
        </div>
        <?php

        return (string) ob_get_clean();
    }

    /** @param array<string, true> $switchedOff */
    private function rolesHtml(array $switchedOff): string
    {
        $roles = permission_roles();

        ob_start(); ?>
        <p class="user-info">Switch functions off for a role and they disappear from that role's menus and stop working, even from a saved link. Super Admin always keeps everything.</p>

        <form method="post" action="<?= site_url('permissions') ?>" id="permissions-form">
            <?php foreach (permission_registry() as $group => $permissions): ?>
                <div class="dashboard-section">
                    <h2><?= esc($group) ?></h2>
                    <div class="recent-table-wrap">
                        <table class="recent-table permissions-table">
                            <thead>
                                <tr>
                                    <th>Function</th>
                                    <?php foreach ($roles as $role): ?>
                                        <th><?= esc($role) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($permissions as $key => $def): ?>
                                    <tr>
                                        <td><?= esc($def['label']) ?></td>
                                        <?php foreach ($roles as $role): ?>
                                            <td>
                                                <?php if (in_array($role, $def['roles'], true)): ?>
                                                    <label class="switch" title="<?= esc($role . ': ' . $def['label']) ?>">
                                                        <input type="checkbox" name="perm[<?= esc($role) ?>][<?= esc($key) ?>]" value="1" aria-label="<?= esc($role . ' can: ' . $def['label']) ?>" <?= isset($switchedOff[$role . '|' . $key]) ? '' : 'checked' ?>>
                                                        <span class="switch-slider"></span>
                                                    </label>
                                                <?php else: ?>
                                                    <span class="permission-na" title="<?= esc($role) ?> doesn't have this function">&mdash;</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="modal-actions permissions-actions">
                <button type="submit" class="quick-btn quick-btn-primary">Save Permissions</button>
            </div>
        </form>
        <?php

        return ob_get_clean() . $this->confirmDialog('these permission changes', 'They take effect right away for everyone signed in as those roles.');
    }

    /**
     * @param array<string, mixed> $account
     * @param array<string, true>  $roleOff
     * @param array<string, true>  $accountOff
     */
    private function accountHtml(array $account, array $roleOff, array $accountOff): string
    {
        ob_start(); ?>
        <p class="user-info">Switch functions off for just <strong><?= esc($account['Name']) ?></strong> (<?= esc($account['Username']) ?>, <?= esc($account['Role']) ?>). A function only works if it's on for the whole <?= esc($account['Role']) ?> role <em>and</em> on for this account.</p>

        <form method="post" action="<?= site_url('permissions/account/' . $account['Account_ID']) ?>" id="permissions-form">
            <?php foreach (permission_registry() as $group => $permissions): ?>
                <?php
                $rows = array_filter($permissions, static fn (array $def): bool => in_array($account['Role'], $def['roles'], true));
                if ($rows === []) {
                    continue;
                }
                ?>
                <div class="dashboard-section">
                    <h2><?= esc($group) ?></h2>
                    <div class="recent-table-wrap">
                        <table class="recent-table permissions-table">
                            <thead>
                                <tr>
                                    <th>Function</th>
                                    <th><?= esc($account['Name']) ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $key => $def): ?>
                                    <tr>
                                        <td><?= esc($def['label']) ?></td>
                                        <td>
                                            <?php if (isset($roleOff[$account['Role'] . '|' . $key])): ?>
                                                <span class="status-pill status-cancelled" title="Switch it back on for the whole role first">Off for all <?= esc($account['Role']) ?> accounts</span>
                                            <?php else: ?>
                                                <label class="switch" title="<?= esc($account['Name'] . ': ' . $def['label']) ?>">
                                                    <input type="checkbox" name="perm[<?= esc($key) ?>]" value="1" aria-label="<?= esc($account['Name'] . ' can: ' . $def['label']) ?>" <?= isset($accountOff[$key]) ? '' : 'checked' ?>>
                                                    <span class="switch-slider"></span>
                                                </label>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="modal-actions permissions-actions">
                <button type="submit" class="quick-btn quick-btn-primary">Save Permissions</button>
            </div>
        </form>
        <?php

        return ob_get_clean() . $this->confirmDialog('these permission changes for ' . $account['Name'], 'They take effect right away, even if this account is signed in.');
    }

    /** "Are you sure?" step: the form's submit is held until confirmed. */
    private function confirmDialog(string $what, string $hint): string
    {
        ob_start(); ?>
        <dialog id="confirm-permissions-modal" class="modal">
            <div class="modal-header">
                <h2>Save Permissions</h2>
                <button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button>
            </div>
            <div class="order-detail-body">
                <p>Are you sure you want to save <?= esc($what) ?>?</p>
                <p class="role-hint"><?= esc($hint) ?></p>
                <div class="modal-actions">
                    <button type="button" class="quick-btn" data-modal-close>Cancel</button>
                    <button type="button" class="quick-btn quick-btn-primary" id="confirm-permissions-yes">Yes, save</button>
                </div>
            </div>
        </dialog>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var form = document.getElementById('permissions-form');
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    document.getElementById('confirm-permissions-modal').showModal();
                });
                document.getElementById('confirm-permissions-yes').addEventListener('click', function () {
                    form.submit();
                });
            });
        </script>
        <?php

        return (string) ob_get_clean();
    }

    private function styles(): string
    {
        return <<<'HTML'
        <style>
            .switch { position: relative; display: inline-block; width: 42px; height: 24px; }
            .switch input { position: absolute; opacity: 0; width: 100%; height: 100%; margin: 0; cursor: pointer; z-index: 1; }
            .switch-slider { position: absolute; inset: 0; background: #c9c9c9; border-radius: 24px; transition: background 0.15s; }
            .switch-slider::before { content: ""; position: absolute; left: 3px; top: 3px; width: 18px; height: 18px; background: #fff; border-radius: 50%; transition: transform 0.15s; }
            .switch input:checked + .switch-slider { background: #5f8b4c; }
            .switch input:checked + .switch-slider::before { transform: translateX(18px); }
            .switch input:focus-visible + .switch-slider { outline: 2px solid #d88e49; outline-offset: 2px; }
            .permission-na { color: #aaa; }
            .permissions-actions { margin-top: 20px; }
        </style>
        HTML;
    }
}
