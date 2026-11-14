<?php

namespace App\Controllers;

use App\Models\AccountModel;

class Settings extends BaseController
{
    public function index()
    {
        $accountModel = model(AccountModel::class);
        $accountId = (int) session()->get('user_ID');

        if ($this->request->is('post')) {
            if ((string) $this->request->getPost('form_action') === 'password') {
                $newPassword = (string) $this->request->getPost('new_password');

                $problem = AccountModel::passwordProblem($newPassword);
                if ($problem !== null) {
                    session()->setFlashdata('error', $problem);
                    return redirect()->to('settings');
                }

                $accountModel->update($accountId, ['Password' => password_hash($newPassword, PASSWORD_DEFAULT)]);
                session()->setFlashdata('msg', 'Password updated.');

                return redirect()->to('settings');
            }

            $name = trim((string) $this->request->getPost('name'));
            $email = trim((string) $this->request->getPost('email'));

            if ($name === '' || $email === '') {
                session()->setFlashdata('error', 'Name and email are required.');
                return redirect()->to('settings');
            }

            $emailProblem = $accountModel->emailProblem($email, $accountId);
            if ($emailProblem !== null) {
                session()->setFlashdata('error', $emailProblem);
                return redirect()->to('settings');
            }

            $data = ['Name' => $name, 'Email' => $email];

            // The phone number is optional; leaving it blank clears it. Only
            // handled once the database has the Account_Phone column.
            if (column_ready('accounts', 'Account_Phone')) {
                $phone = trim((string) $this->request->getPost('phone'));

                if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,30}$/', $phone)) {
                    session()->setFlashdata('error', 'Enter a valid number: digits, spaces, + - ( ) only, at least 7 characters.');
                    return redirect()->to('settings');
                }

                $data['Account_Phone'] = $phone === '' ? null : $phone;
            }

            $accountModel->update($accountId, $data);
            session()->set('name', $name);
            session()->setFlashdata('msg', 'Profile updated.');

            return redirect()->to('settings');
        }

        $role = (string) session()->get('role');

        $content = view('settings/index', [
            'account' => $accountModel->find($accountId),
            'msg' => session()->getFlashdata('msg'),
            'error' => session()->getFlashdata('error'),
        ]);

        return view('layouts/main', [
            'content' => $content,
            'page_title' => "Settings - Gab's Bakeshop",
            'page_css' => 'settings.css',
            'nav_role' => $role,
            'nav_current_page' => 'settings',
        ]);
    }
}
