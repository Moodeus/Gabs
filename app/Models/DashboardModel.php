<?php

namespace App\Models;

use CodeIgniter\Model;

class DashboardModel extends Model
{
    public function getData(string $role, int $userId): array
    {
        return match ($role) {
            'Branch' => $this->getBranchData($userId),
            'Delivery' => $this->getDeliveryData(),
            default => $this->getAdminData(),
        };
    }

    private function getAdminData(): array
    {
        $pending = $this->countOrdersByStatus('Pending');
        $approved = $this->countOrdersByStatus('Approved');
        $deliveredToday = $this->countOrdersByStatus('Delivered', true);
        $lowProducts = $this->countLowStockProducts();
        $lowIngredients = $this->countLowStockIngredients();
        $branches = $this->countActiveAccounts('Branch');
        $deliveryAccounts = $this->countActiveAccounts('Delivery');
        $todaySales = $this->getTodaySales();

        $cards = [
            ['label' => 'Pending Orders', 'value' => $pending, 'url' => 'orders/status/pending', 'tone' => 'warning'],
            ['label' => 'Approved Orders', 'value' => $approved, 'url' => 'orders/status/approved', 'tone' => 'info'],
            ['label' => 'Delivered Today', 'value' => $deliveredToday, 'url' => 'orders/status/delivered', 'tone' => 'success'],
            ['label' => "Today's Net Sales", 'value' => '₱' . number_format($todaySales, 2), 'url' => 'sales-report', 'tone' => 'accent', 'raw' => true],
            ['label' => 'Active Branches', 'value' => $branches, 'url' => 'accounts', 'tone' => 'neutral'],
            ['label' => 'Active Delivery', 'value' => $deliveryAccounts, 'url' => 'accounts', 'tone' => 'neutral'],
            ['label' => 'Low Stock Products', 'value' => $lowProducts, 'url' => 'product-inventory', 'tone' => $lowProducts > 0 ? 'danger' : 'neutral'],
            ['label' => 'Low Stock Ingredients', 'value' => $lowIngredients, 'url' => 'ingredient-inventory', 'tone' => $lowIngredients > 0 ? 'danger' : 'neutral'],
        ];

        return [
            'cards' => $cards,
            'sales_chart' => $this->getSalesChartData(),
            'role_hint' => 'You have full access to manage inventory, approve orders, generate reports, handle accounts, and configure settings.',
        ];
    }

    private function getBranchData(int $userId): array
    {
        $pending = $this->countOrdersByStatus('Pending', false, $userId);
        $approved = $this->countOrdersByStatus('Approved', false, $userId);
        $deliveredWeek = $this->countDeliveredThisWeek($userId);
        $availableProducts = $this->countAvailableProducts();
        $outstandingBalance = $this->getOutstandingBalance($userId);

        $cards = [
            ['label' => 'My Pending Orders', 'value' => $pending, 'url' => 'orders/status/pending', 'tone' => 'warning'],
            ['label' => 'Approved (In Transit)', 'value' => $approved, 'url' => 'orders/status/approved', 'tone' => 'info'],
            ['label' => 'Delivered This Week', 'value' => $deliveredWeek, 'url' => 'orders/status/delivered', 'tone' => 'success'],
            ['label' => 'Products Available', 'value' => $availableProducts, 'url' => 'orders', 'tone' => 'accent'],
            ['label' => 'Outstanding Balance', 'value' => '₱' . number_format($outstandingBalance, 2), 'url' => 'delivery-receipts', 'tone' => $outstandingBalance > 0 ? 'danger' : 'neutral', 'raw' => true],
        ];

        return [
            'cards' => $cards,
            'role_hint' => 'You can place orders for your branch and track their delivery receipts.',
        ];
    }

    private function getDeliveryData(): array
    {
        $ready = $this->countOrdersByStatus('Approved');
        $deliveredToday = $this->countOrdersByStatus('Delivered', true);

        $cards = [
            ['label' => 'Ready for Delivery', 'value' => $ready, 'url' => 'orders/status/approved', 'tone' => 'warning'],
            ['label' => 'Delivered Today', 'value' => $deliveredToday, 'url' => 'orders/status/delivered', 'tone' => 'success'],
        ];

        return [
            'cards' => $cards,
            'role_hint' => 'You can view approved orders and mark them as delivered after uploading proof.',
        ];
    }

    private function countOrdersByStatus(string $status, bool $todayOnly = false, ?int $branchId = null): int
    {
        $builder = $this->db->table('orders')
            ->where('Status', $status)
            ->where('Deleted_At', null);

        if ($branchId !== null) {
            $builder->where('Account_ID', $branchId);
        }

        if ($todayOnly) {
            $builder->where('DATE(Order_Date) = CURDATE()', null, false);
        }

        return (int) $builder->countAllResults();
    }

    private function countDeliveredThisWeek(int $branchId): int
    {
        return (int) $this->db->table('orders')
            ->where('Account_ID', $branchId)
            ->where('Status', 'Delivered')
            ->where('Order_Date >= (CURDATE() - INTERVAL WEEKDAY(CURDATE()) DAY)', null, false)
            ->where('Deleted_At', null)
            ->countAllResults();
    }

    private function getOutstandingBalance(int $branchId): float
    {
        $row = $this->db->table('accounts')
            ->select('Outstanding_Balance')
            ->where('Account_ID', $branchId)
            ->get()
            ->getRowArray();

        return (float) ($row['Outstanding_Balance'] ?? 0);
    }

    /**
     * Low stock is now a per-product threshold (set on Product Inventory's
     * Edit modal) rather than one fixed number for every product.
     */
    private function countLowStockProducts(): int
    {
        return (int) $this->db->table('product')
            ->where('Stock <= Low_Stock_Threshold', null, false)
            ->where('Deleted_At', null)
            ->countAllResults();
    }

    /**
     * Same as countLowStockProducts(), but per-ingredient.
     */
    private function countLowStockIngredients(): int
    {
        return (int) $this->db->table('ingredients')
            ->where('Stock_In_Grams <= Low_Stock_Threshold', null, false)
            ->where('Deleted_At', null)
            ->countAllResults();
    }

    private function countAvailableProducts(): int
    {
        return (int) $this->db->table('product')
            ->where('Stock >', 0)
            ->where('Deleted_At', null)
            ->countAllResults();
    }

    private function countActiveAccounts(string $role): int
    {
        return (int) $this->db->table('accounts')
            ->where('Role', $role)
            ->where('Deleted_At', null)
            ->countAllResults();
    }

    private function getTodaySales(): float
    {
        $row = $this->db->table('orders o')
            ->select('COALESCE(SUM(o.Order_Total - ' . OrderModel::costSql('o.Order_ID') . '), 0) AS total', false)
            ->whereIn('o.Status', ['Delivered', 'Paid'])
            ->where('DATE(o.Order_Date) = CURDATE()', null, false)
            ->where('o.Deleted_At', null)
            ->get()
            ->getRowArray();

        return (float) ($row['total'] ?? 0);
    }

    /**
     * Net sales for every month there are delivered orders in, plus each
     * month's ten most ordered products, so the chart can page through
     * years. "Sales" counts orders that were delivered - both Delivered
     * and Paid (Paid is Delivered once the branch has settled it) - less
     * the cost of the products in them.
     *
     * @return array{totals: array<string, array<string, mixed>>, products: array<string, list<array<string, mixed>>>, years: list<int>}
     */
    public function getSalesChartData(): array
    {
        $statuses = ['Delivered', 'Paid'];

        $rows = $this->db->table('orders o')
            ->select("DATE_FORMAT(o.Order_Date, '%Y-%m') AS ym, SUM(o.Order_Total - " . OrderModel::costSql('o.Order_ID') . ') AS total, COUNT(*) AS orders', false)
            ->whereIn('o.Status', $statuses)
            ->where('o.Deleted_At', null)
            ->groupBy('ym')
            ->get()
            ->getResultArray();

        $totals = [];
        foreach ($rows as $row) {
            $totals[$row['ym']] = ['total' => (float) $row['total'], 'orders' => (int) $row['orders']];
        }

        // Every year from the first one with sales up to this one, so a year
        // with no sales in between is still reachable.
        $currentYear = (int) date('Y');
        $firstYear = $totals === [] ? $currentYear : min($currentYear, (int) substr((string) min(array_keys($totals)), 0, 4));
        $years = range($firstYear, $currentYear);

        $lines = $this->db->table('order_line_item li')
            ->select("DATE_FORMAT(o.Order_Date, '%Y-%m') AS ym, MAX(li.Name) AS name, SUM(li.Qty) AS qty, SUM(li.Qty * li.Price" . (column_ready('order_line_item', 'Cost') ? ' - li.Qty * li.Cost' : '') . ') AS amount', false)
            ->join('orders o', 'o.Order_ID = li.Order_ID')
            ->whereIn('o.Status', $statuses)
            ->where('o.Deleted_At', null)
            ->where('li.Deleted_At', null)
            ->groupBy(['ym', 'li.Product_ID'])
            ->orderBy('qty', 'DESC')
            ->get()
            ->getResultArray();

        $products = [];
        foreach ($lines as $row) {
            $products[$row['ym']] ??= [];
            if (count($products[$row['ym']]) < 10) {
                $products[$row['ym']][] = [
                    'name' => $row['name'],
                    'qty' => (float) $row['qty'],
                    'amount' => (float) $row['amount'],
                ];
            }
        }

        return ['totals' => $totals, 'products' => $products, 'years' => $years];
    }
}
