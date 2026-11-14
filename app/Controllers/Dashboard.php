<?php

namespace App\Controllers;

use App\Models\DashboardModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $role = preg_replace('/[^A-Za-z ]/', '', session()->get('role') ?? '');
        $userId = (int) session()->get('user_ID');

        $dashboardModel = model(DashboardModel::class);
        $data = $dashboardModel->getData($role, $userId);

        if (!can('dashboard.sales_chart')) {
            unset($data['sales_chart']);
        }

        $hour = (int) date('G');
        $greetingPeriod = $hour < 12 ? 'morning' : ($hour < 17 ? 'afternoon' : 'evening');

        $content = view('dashboard/index', array_merge($data, [
            'user_name' => session()->get('name') ?? 'User',
            'role' => $role ?: 'Admin',
            'greeting_period' => $greetingPeriod,
            'msg' => session()->getFlashdata('msg'),
            'error' => session()->getFlashdata('error'),
        ]));

        return view('layouts/main', [
            'content' => $content,
            'page_title' => "Dashboard - Gab's Bakeshop",
            'page_css' => 'welcome.css',
            'nav_role' => $role,
            'nav_current_page' => 'dashboard',
        ]);
    }
}
