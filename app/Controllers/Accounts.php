<?php

namespace App\Controllers;

use App\Models\AccountHistoryModel;
use App\Models\AccountModel;
use CodeIgniter\Database\Exceptions\DatabaseException;

class Accounts extends BaseController
{
    private const ROLES = ['Super Admin', 'Admin', 'Branch', 'Delivery'];

    public function index()
    {
        $accountModel = model(AccountModel::class);

        if ($this->request->is('post')) {
            $action = (string) $this->request->getPost('form_action');

            if ($action === 'search') {
                return redirect()->to(list_url('accounts', 'all', (string) $this->request->getPost('q'), 1));
            }

            if ($action === 'deactivate') {
                $this->deactivate($accountModel);

                return redirect()->to('accounts');
            }

            if (!$this->save($accountModel)) {
                return redirect()->to('accounts')->withInput();
            }

            return redirect()->to('accounts');
        }

        return $this->render('', 1);
    }

    /** accounts/browse/{filter}/q{search}/{page} - the filter segment is unused here. */
    public function browse(string $filter, string $search, int $page)
    {
        return $this->render(list_query_from_segment($search), $page);
    }

    private function render(string $query, int $page)
    {
        $currentRole = (string) session()->get('role');
        [$accounts, $total, $page, $totalPages] = model(AccountModel::class)->browse($query, $currentRole !== 'Super Admin', $page);

        $content = view('accounts/index', [
            'accounts' => $accounts,
            'query' => $query,
            'page' => $page,
            'total_pages' => $totalPages,
            'total' => $total,
            'roles' => $currentRole === 'Super Admin'
                ? self::ROLES
                : array_values(array_diff(self::ROLES, ['Super Admin'])),
            'current_account_id' => (int) session()->get('user_ID'),
            'current_role' => (string) session()->get('role'),
            'msg' => session()->getFlashdata('msg'),
            'error' => session()->getFlashdata('error'),
        ]);

        return view('layouts/main', [
            'content' => $content,
            'page_title' => "Accounts - Gab's Bakeshop",
            'page_css' => 'accounts.css',
            'nav_role' => session()->get('role'),
            'nav_current_page' => 'accounts',
        ]);
    }

    private function save(AccountModel $accountModel): bool
    {
        $id = (int) $this->request->getPost('account_id');
        $name = trim((string) $this->request->getPost('name'));
        $email = trim((string) $this->request->getPost('email'));
        $username = trim((string) $this->request->getPost('username'));
        $role = (string) $this->request->getPost('role');
        $password = (string) $this->request->getPost('password');

        if (!can($id > 0 ? 'accounts.edit' : 'accounts.create')) {
            session()->setFlashdata('error', 'You are not allowed to ' . ($id > 0 ? 'edit' : 'create') . ' accounts.');

            return false;
        }

        if ($name === '' || $email === '' || $username === '' || !in_array($role, self::ROLES, true)) {
            session()->setFlashdata('error', 'Please fill in all required fields.');

            return false;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            session()->setFlashdata('error', 'Enter a valid email address.');

            return false;
        }

        // A password is required for a new account; when editing, blank keeps
        // the current one. Either way, anything typed has to be acceptable.
        if ($password !== '' || $id === 0) {
            $passwordProblem = $password === '' ? 'Password is required for new accounts.' : AccountModel::passwordProblem($password);
            if ($passwordProblem !== null) {
                session()->setFlashdata('error', $passwordProblem);

                return false;
            }
        }

        $data = ['Name' => $name, 'Email' => $email, 'Username' => $username, 'Role' => $role];
        $actorId = (int) session()->get('user_ID');

        // Username and Email only have to be unique among active accounts:
        // the model's soft-delete filter skips deactivated ones, so their
        // username/email can be reused. The database has plain indexes on
        // these columns, not unique keys, so this is the only enforcement.
        $duplicate = $accountModel
            ->groupStart()
                ->where('Username', $username)
                ->orWhere('Email', $email)
            ->groupEnd()
            ->where('Account_ID !=', $id)
            ->first();

        if ($duplicate) {
            $field = strcasecmp($duplicate['Username'], $username) === 0 ? 'username' : 'email';
            session()->setFlashdata('error', "That {$field} is already in use by an active account.");

            return false;
        }

        $isSuperAdmin = (string) session()->get('role') === 'Super Admin';

        if (!$isSuperAdmin && $role === 'Super Admin') {
            session()->setFlashdata('error', 'Only Super Admin can assign the Super Admin role.');

            return false;
        }

        if ($id > 0) {
            $existing = $accountModel->find($id);
            if (!$existing || (!$isSuperAdmin && $existing['Role'] === 'Super Admin')) {
                session()->setFlashdata('error', 'Account not found.');

                return false;
            }

            // Changing the last Super Admin to another role would leave nobody
            // able to manage accounts or permissions, same as deactivating it.
            if ($existing['Role'] === 'Super Admin' && $role !== 'Super Admin'
                && $accountModel->where('Role', 'Super Admin')->countAllResults() <= 1) {
                session()->setFlashdata('error', 'Cannot change the role of the last remaining Super Admin account.');

                return false;
            }

            if ($password !== '') {
                $data['Password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            try {
                $saved = $accountModel->update($id, $data);
            } catch (DatabaseException $e) {
                $saved = false;
            }

            if (!$saved) {
                session()->setFlashdata('error', self::DUPLICATE_HINT);

                return false;
            }

            $this->logAction($id, $actorId, "Updated account '{$username}'");
            session()->setFlashdata('msg', "Account '{$username}' updated.");

            return true;
        }

        if ($password === '') {
            session()->setFlashdata('error', 'Password is required for new accounts.');

            return false;
        }

        $data['Password'] = password_hash($password, PASSWORD_DEFAULT);

        try {
            $newId = $accountModel->insert($data, true);
        } catch (DatabaseException $e) {
            $newId = false;
        }

        if (!$newId) {
            session()->setFlashdata('error', self::DUPLICATE_HINT);

            return false;
        }

        $this->logAction((int) $newId, $actorId, "Created account '{$username}' ({$role})");
        session()->setFlashdata('msg', "Account '{$username}' created.");

        return true;
    }

    /**
     * Shown when the database itself refuses a username/email. That only
     * happens while it still has the older unique keys on those columns
     * (which also count deactivated accounts), so it's most likely a
     * deactivated account holding the name.
     */
    private const DUPLICATE_HINT = 'That username or email is already taken, possibly by a deactivated account. Try a different one.';

    /**
     * Records who did what to which account (feeds Account Reports). Does
     * nothing if the account_history table isn't in the database yet, and
     * a failure here never blocks the account change itself.
     */
    private function logAction(int $targetId, int $actorId, string $remarks): void
    {
        if (!table_ready('account_history')) {
            return;
        }

        try {
            model(AccountHistoryModel::class)->insert([
                'Target_Account_ID' => $targetId,
                'Account_ID' => $actorId,
                'Remarks' => $remarks,
            ]);
        } catch (DatabaseException $e) {
            log_message('error', 'Could not write account history: ' . $e->getMessage());
        }
    }

    /**
     * Deactivate is a soft delete (AccountModel sets Deleted_At, nothing is
     * removed from the database) - a deactivated account simply can no
     * longer be found by login. Admin and Super Admin can do this, but only
     * Super Admin can touch a Super Admin account. Refuses to deactivate
     * the account you're currently signed in as, or the last remaining
     * Super Admin account, so the ability to manage accounts at all can't
     * be locked out of.
     */
    private function deactivate(AccountModel $accountModel): void
    {
        if (!can('accounts.deactivate')) {
            session()->setFlashdata('error', 'You are not allowed to deactivate accounts.');

            return;
        }

        $id = (int) $this->request->getPost('account_id');
        $account = $accountModel->find($id);

        if (!$account || ((string) session()->get('role') !== 'Super Admin' && $account['Role'] === 'Super Admin')) {
            session()->setFlashdata('error', 'Account not found.');

            return;
        }

        if ($id === (int) session()->get('user_ID')) {
            session()->setFlashdata('error', 'You cannot deactivate your own account.');

            return;
        }

        if ($account['Role'] === 'Super Admin' && $accountModel->where('Role', 'Super Admin')->countAllResults() <= 1) {
            session()->setFlashdata('error', 'Cannot deactivate the last remaining Super Admin account.');

            return;
        }

        $accountModel->delete($id);
        $this->logAction($id, (int) session()->get('user_ID'), "Deactivated account '{$account['Username']}'");
        session()->setFlashdata('msg', "Account '{$account['Username']}' deactivated.");
    }
}
