<?php

namespace App\Controllers;

use App\Models\AccountModel;

class Auth extends BaseController
{
    public function login()
    {
        if (session()->get('user_ID')) {
            return redirect()->to('dashboard');
        }

        if ($this->request->is('post')) {
            return $this->attemptLogin();
        }

        return view('auth/login', [
            'error' => '',
            'username_or_email' => '',
        ]);
    }

    private function attemptLogin()
    {
        $usernameOrEmail = trim((string) $this->request->getPost('username_or_email'));
        $password = (string) $this->request->getPost('password');

        if ($usernameOrEmail === '' || $password === '') {
            return view('auth/login', [
                'error' => 'Username/Email and Password are required.',
                'username_or_email' => $usernameOrEmail,
            ]);
        }

        $accountModel = model(AccountModel::class);
        $user = $accountModel->findByUsernameOrEmail($usernameOrEmail);

        if (!$user || !password_verify($password, $user['Password'])) {
            return view('auth/login', [
                'error' => 'Invalid username/email or password.',
                'username_or_email' => $usernameOrEmail,
            ]);
        }

        $role = $accountModel->getRoleName((int) $user['Account_ID']);
        if (!$role) {
            return view('auth/login', [
                'error' => 'Login successful, but user role not assigned. Please contact administrator.',
                'username_or_email' => $usernameOrEmail,
            ]);
        }

        if (password_needs_rehash($user['Password'], PASSWORD_DEFAULT)) {
            $accountModel->update($user['Account_ID'], [
                'Password' => password_hash($password, PASSWORD_DEFAULT),
            ]);
        }

        // A fresh session id at sign-in, so one planted before login is useless.
        session()->regenerate();
        session()->set([
            'user_ID' => $user['Account_ID'],
            'name' => $user['Name'],
            'role' => $role,
        ]);

        return redirect()->to('dashboard');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('auth/login');
    }
}
