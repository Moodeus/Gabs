<?php

namespace App\Controllers;

use App\Models\AccountModel;
use App\Models\OrderModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class DeliveryReceipts extends BaseController
{
    private const CATEGORIES = ['paid' => 'Paid', 'unpaid' => 'Unpaid'];

    public function index(?int $page = null)
    {
        return $this->render(null, null, $page);
    }

    public function filtered(string $category, ?int $page = null)
    {
        return $this->render($this->validCategory($category), null, $page);
    }

    public function branch(int $branchId, ?int $page = null)
    {
        return $this->render(null, $branchId, $page);
    }

    public function filteredBranch(string $category, int $branchId, ?int $page = null)
    {
        return $this->render($this->validCategory($category), $branchId, $page);
    }

    private function validCategory(string $category): string
    {
        if (!array_key_exists($category, self::CATEGORIES)) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $category;
    }

    /**
     * Every receipt is a Delivered or Paid order, so "unpaid" is simply
     * Delivered and "paid" is Paid. Admin and Super Admin can also narrow
     * the list to one branch (a Branch only ever sees its own receipts).
     */
    private function render(?string $category, ?int $branchId, ?int $page)
    {
        $role = (string) session()->get('role');
        $accountId = (int) session()->get('user_ID');
        $canPickBranch = in_array($role, ['Admin', 'Super Admin'], true);

        $statuses = match ($category) {
            'paid' => ['Paid'],
            'unpaid' => ['Delivered'],
            default => ['Delivered', 'Paid'],
        };

        // Deactivated branches stay in the list, since their receipts still exist.
        $branches = [];
        if ($canPickBranch) {
            foreach (model(AccountModel::class)->withDeleted()->where('Role', 'Branch')->orderBy('Name')->findAll() as $branch) {
                $branches[(int) $branch['Account_ID']] = $branch['Name'] . ($branch['Deleted_At'] !== null ? ' (deactivated)' : '');
            }
        }

        if ($branchId !== null && (!$canPickBranch || !isset($branches[$branchId]))) {
            throw PageNotFoundException::forPageNotFound();
        }

        $allOrders = model(OrderModel::class)->forRole($role, $accountId, $statuses);
        if ($branchId !== null) {
            $allOrders = array_values(array_filter(
                $allOrders,
                static fn (array $order): bool => (int) $order['Account_ID'] === $branchId
            ));
        }
        [$orders, $page, $totalPages] = $this->paginateArray($allOrders, $page);

        $content = view('delivery_receipts/index', [
            'orders' => $orders,
            'page' => $page,
            'total_pages' => $totalPages,
            'category' => $category,
            'categories' => self::CATEGORIES,
            'branches' => $branches,
            'branch_id' => $branchId,
        ]);

        return view('layouts/main', [
            'content' => $content,
            'page_title' => "Delivery Receipts - Gab's Bakeshop",
            'page_css' => 'receipt.css',
            'nav_role' => $role,
            'nav_current_page' => 'delivery-receipts',
        ]);
    }

    public function image(string $filename)
    {
        $filename = basename($filename);
        $path = WRITEPATH . 'uploads/' . $filename;

        if (!is_file($path)) {
            throw PageNotFoundException::forPageNotFound();
        }

        // A branch may only open the photos of its own orders.
        if ((string) session()->get('role') === 'Branch') {
            $own = model(OrderModel::class)
                ->where('Proof_Image', $filename)
                ->where('Account_ID', (int) session()->get('user_ID'))
                ->first();

            if (!$own) {
                throw PageNotFoundException::forPageNotFound();
            }
        }

        // Only ever served as an image, never as a page or script, whatever
        // the file turns out to contain.
        $type = (string) mime_content_type($path);
        if (!in_array($type, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->response
            ->setContentType($type)
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody(file_get_contents($path));
    }
}
