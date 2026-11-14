<?php

namespace App\Controllers;

use App\Models\AccountHistoryModel;

class AccountReports extends BaseController
{
    public function index(?int $page = null)
    {
        $history = model(AccountHistoryModel::class)->recent();
        [$pageHistory, $page, $totalPages] = $this->paginateArray($history, $page);

        $content = view('account_reports/index', [
            'history' => $pageHistory,
            'page' => $page,
            'total_pages' => $totalPages,
        ]);

        return view('layouts/main', [
            'content' => $content,
            'page_title' => "Account Reports - Gab's Bakeshop",
            'page_css' => 'producthistory.css',
            'nav_role' => session()->get('role'),
            'nav_current_page' => 'account-reports',
        ]);
    }
}
