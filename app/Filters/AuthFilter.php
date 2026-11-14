<?php

namespace App\Filters;

use App\Models\AccountModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        if (!$session->get('user_ID')) {
            return redirect()->to('auth/login');
        }

        // The session only remembers who signed in, so check the account is
        // still there on every request: one that was deactivated stops
        // working at once, and a changed role (or name) takes effect without
        // waiting for a fresh sign-in.
        $account = model(AccountModel::class)->select('Role, Name')->find((int) $session->get('user_ID'));

        if (!$account) {
            $session->destroy();

            return redirect()->to('auth/login');
        }

        if ($session->get('role') !== $account['Role'] || $session->get('name') !== $account['Name']) {
            $session->set(['role' => $account['Role'], 'name' => $account['Name']]);
        }

        if (!empty($arguments)) {
            $role = $session->get('role');
            // Super Admin can do everything Admin can, so it satisfies any
            // route gated for Admin without every route needing to list
            // both roles.
            $inheritsAdmin = $role === 'Super Admin' && in_array('Admin', $arguments, true);

            if (!$role || (!in_array($role, $arguments, true) && !$inheritsAdmin)) {
                return redirect()->to('dashboard');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
