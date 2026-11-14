<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderModel extends Model
{
    protected $table = 'orders';
    protected $primaryKey = 'Order_ID';
    protected $allowedFields = ['Account_ID', 'Name', 'Details', 'Status', 'Order_Total', 'Order_Date', 'Proof_Image'];
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $deletedField = 'Deleted_At';

    /**
     * Scope the order list to what a role is allowed to see.
     *
     * @param string|list<string>|null $status A single status, several
     *                                          (e.g. Delivered + Paid), or
     *                                          null for no extra filter.
     */
    public function forRole(string $role, int $accountId, string|array|null $status = null): array
    {
        $this->scopeForRole($role, $accountId, $status);

        return $this->orderBy('orders.Order_Date', 'DESC')->find();
    }

    /**
     * One page of the order list for a role, narrowed by status and a search
     * across the order number, branch, status, date and total, with the total
     * so the page count can be worked out.
     *
     * @return array{0: list<array<string, mixed>>, 1: int, 2: int, 3: int} rows, total, page, totalPages
     */
    public function browse(string $role, int $accountId, ?string $status, string $query, int $page, int $perPage = 25): array
    {
        $narrow = function () use ($role, $accountId, $status, $query): void {
            $this->scopeForRole($role, $accountId, $status);

            if ($query !== '') {
                $matches = array_map(
                    fn (string $expression): string => like_contains($this->db, $expression, $query),
                    [
                        'orders.Name',
                        'a.Name',
                        'orders.Status',
                        "DATE_FORMAT(orders.Order_Date, '%b %e, %Y %l:%i %p')",
                        'CAST(orders.Order_Total AS CHAR)',
                    ]
                );
                $this->where('(' . implode(' OR ', $matches) . ')', null, false);
            }
        };

        $narrow();
        $total = $this->countAllResults();
        [$page, $totalPages, $offset] = paged($total, $page, $perPage);

        $narrow();
        $rows = $this->orderBy('orders.Order_Date', 'DESC')->limit($perPage, $offset)->find();

        return [$rows, $total, $page, $totalPages];
    }

    private function scopeForRole(string $role, int $accountId, string|array|null $status): void
    {
        $this->select('orders.*, a.Name AS branch_name')
            ->join('accounts a', 'a.Account_ID = orders.Account_ID');

        if ($role === 'Branch') {
            $this->where('orders.Account_ID', $accountId);
        } elseif ($role === 'Delivery') {
            $this->whereIn('orders.Status', ['Approved', 'Delivered', 'Paid']);
        }

        if (is_array($status)) {
            $this->whereIn('orders.Status', $status);
        } elseif ($status !== null) {
            $this->where('orders.Status', $status);
        }
    }

    /**
     * For the Admin's live "new order" notice: how many orders are waiting for
     * approval, the id of the newest one, and how many Pending orders are
     * newer than $afterId (the newest one the open page already knew about).
     *
     * @return array{count: int, latest: int, newer: int}
     */
    public function pendingSummary(int $afterId = 0): array
    {
        $row = $this->db->table('orders')
            ->select('COUNT(*) AS n, COALESCE(MAX(Order_ID), 0) AS latest, COALESCE(SUM(Order_ID > ' . (int) $afterId . '), 0) AS newer', false)
            ->where('Status', 'Pending')
            ->where('Deleted_At', null)
            ->get()
            ->getRowArray();

        return ['count' => (int) $row['n'], 'latest' => (int) $row['latest'], 'newer' => (int) $row['newer']];
    }

    /**
     * SQL for what an order's products cost: quantity times the cost saved
     * on each item when it was ordered. Items taken off the order (denied
     * or voided) are skipped, like they are in its total. $orderIdColumn is
     * the order id in the surrounding query, e.g. 'orders.Order_ID'. Without
     * the Cost column (code deployed before the database update) the cost is
     * 0, so sales simply show without any deduction.
     */
    public static function costSql(string $orderIdColumn): string
    {
        if (!column_ready('order_line_item', 'Cost')) {
            return '0';
        }

        return '(SELECT COALESCE(SUM(oli.Qty * oli.Cost), 0) FROM order_line_item oli'
            . ' WHERE oli.Order_ID = ' . $orderIdColumn . ' AND oli.Deleted_At IS NULL)';
    }

    /**
     * Sales figures for a list of orders from soldBetween()/deliveredOnDate():
     * what was billed, what the products cost, and the difference (net sales).
     *
     * @param list<array<string, mixed>> $orders
     * @return array{gross: float, cost: float, net: float}
     */
    public function totals(array $orders): array
    {
        $gross = 0.0;
        $cost = 0.0;
        foreach ($orders as $order) {
            $gross += (float) $order['Order_Total'];
            $cost += (float) $order['Order_Cost'];
        }

        return ['gross' => $gross, 'cost' => $cost, 'net' => $gross - $cost];
    }

    /**
     * Sales between two calendar dates, both inclusive (Y-m-d): every order
     * that was delivered, including those since marked Paid - the same
     * definition the Dashboard and the Monthly/Yearly charts use.
     */
    public function soldBetween(string $from, string $to): array
    {
        return $this->select('orders.*, a.Name AS branch_name, ' . self::costSql('orders.Order_ID') . ' AS Order_Cost', false)
            ->join('accounts a', 'a.Account_ID = orders.Account_ID')
            ->whereIn('orders.Status', ['Delivered', 'Paid'])
            ->where('DATE(orders.Order_Date) >=', $from)
            ->where('DATE(orders.Order_Date) <=', $to)
            ->orderBy('orders.Order_Date', 'DESC')
            ->find();
    }

    /**
     * One day's sales (Y-m-d): orders that were delivered, including those
     * since marked Paid - the same definition as soldBetween().
     */
    public function deliveredOnDate(string $date): array
    {
        return $this->select('orders.*, a.Name AS branch_name, ' . self::costSql('orders.Order_ID') . ' AS Order_Cost', false)
            ->join('accounts a', 'a.Account_ID = orders.Account_ID')
            ->whereIn('orders.Status', ['Delivered', 'Paid'])
            ->where('DATE(orders.Order_Date) =', $date)
            ->orderBy('orders.Order_Date', 'DESC')
            ->find();
    }
}
