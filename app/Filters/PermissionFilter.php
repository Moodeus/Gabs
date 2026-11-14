<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Blocks a page for a role whose access to it Super Admin has switched
 * off. Used as `perm:some.permission` alongside `auth:...` on a route.
 */
class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (empty($arguments) || can((string) $arguments[0])) {
            return;
        }

        session()->setFlashdata('error', "You don't have access to that. Ask a Super Admin if you need it.");

        return redirect()->to('dashboard');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
