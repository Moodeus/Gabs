<?php

namespace App\Controllers;

use App\Models\DashboardModel;
use App\Models\OrderModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class SalesReport extends BaseController
{
    public function index(?int $page = null)
    {
        // Opening the report shows today's sales (and its later pages).
        return $this->render($page, database_today());
    }

    /**
     * Delivered orders and income for one calendar date, e.g. from the
     * date filter on the report, 25 to a page.
     */
    public function date(string $date, ?int $page = null)
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m) || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->render($page, $date);
    }

    /**
     * Standalone printable list (no layouts/main chrome), the same pattern
     * as the order invoice - a "Print" button triggers the browser's own
     * print dialog, which can save to PDF without needing a PDF library.
     */
    public function export(string $from, string $to)
    {
        $fromDate = $this->parseDate($from);
        $toDate = $this->parseDate($to);

        if ($fromDate === null || $toDate === null || $fromDate > $toDate) {
            throw PageNotFoundException::forPageNotFound();
        }

        $orders = model(OrderModel::class)->soldBetween($from, $to);

        $totals = model(OrderModel::class)->totals($orders);

        // The period is named after what the dates span: one day, one whole
        // calendar month, one whole calendar year, or anything else.
        if ($from === $to) {
            $kind = 'Daily';
            $period = $fromDate->format('F j, Y');
            $badge = strtoupper($fromDate->format('M j, Y'));
        } elseif ($fromDate->format('N') === '1' && $toDate->format('Y-m-d') === $fromDate->modify('+6 days')->format('Y-m-d')) {
            // A Monday through the following Sunday.
            $kind = 'Weekly';
            $period = $fromDate->format($fromDate->format('Y') === $toDate->format('Y') ? 'M j' : 'M j, Y') . ' – ' . $toDate->format('M j, Y');
            $badge = strtoupper('Week of ' . $fromDate->format('M j, Y'));
        } elseif ($fromDate->format('j') === '1' && $toDate->format('Y-m-d') === $fromDate->format('Y-m-t')) {
            $kind = 'Monthly';
            $period = $fromDate->format('F Y');
            $badge = strtoupper($fromDate->format('M Y'));
        } elseif ($fromDate->format('m-d') === '01-01' && $toDate->format('Y-m-d') === $fromDate->format('Y') . '-12-31') {
            $kind = 'Yearly';
            $period = $fromDate->format('Y');
            $badge = 'YEAR ' . $fromDate->format('Y');
        } else {
            $kind = 'Custom range';
            $period = $fromDate->format('M j, Y') . ' – ' . $toDate->format('M j, Y');
            $badge = strtoupper($period);
        }

        return view('sales_report/export', [
            'orders' => $orders,
            'sales_total' => $totals['net'],
            'gross_total' => $totals['gross'],
            'cost_total' => $totals['cost'],
            'kind' => $kind,
            'period' => $period,
            'badge' => $badge,
        ]);
    }

    private function parseDate(string $value): ?\DateTimeImmutable
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            return null;
        }

        return new \DateTimeImmutable($value);
    }

    private function render(?int $page, string $selectedDate)
    {
        $orderModel = model(OrderModel::class);

        $dateOrders = $orderModel->deliveredOnDate($selectedDate);
        $totals = $orderModel->totals($dateOrders);
        [$pageOrders, $page, $totalPages] = $this->paginateArray($dateOrders, $page);

        $content = view('sales_report/index', [
            'orders' => $pageOrders,
            'sales_total' => $totals['net'],
            'gross_total' => $totals['gross'],
            'cost_total' => $totals['cost'],
            'page' => $page,
            'total_pages' => $totalPages,
            'selected_date' => $selectedDate,
            'sales_chart' => model(DashboardModel::class)->getSalesChartData(),
        ]);

        return view('layouts/main', [
            'content' => $content,
            'page_title' => "Sales Report - Gab's Bakeshop",
            'page_css' => 'reports.css',
            'nav_role' => session()->get('role'),
            'nav_current_page' => 'sales-report',
        ]);
    }
}
