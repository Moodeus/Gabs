<?php

namespace App\Controllers;

use App\Models\AccountModel;
use App\Models\OrderLineItemModel;
use App\Models\OrderModel;
use App\Models\OrdersUpdateHistoryModel;
use App\Models\ProductHistoryModel;
use App\Models\ProductModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Orders extends BaseController
{
    private const STATUSES = ['Pending', 'Approved', 'Delivered', 'Denied', 'Cancelled', 'Paid'];
    private const INVOICEABLE_STATUSES = ['Approved', 'Delivered', 'Paid'];

    /** The most of one product a single order line may ask for. */
    private const MAX_ORDER_QTY = 99999;

    /**
     * $statusFilter comes from the optional orders/status/{value} route (used
     * by the Dashboard's shortcut links) - not a query string, since this
     * app's index.php?/... fallback routing can't reliably carry one
     * alongside a route path in every hosting environment.
     */
    public function index(?string $statusFilter = null)
    {
        $role = (string) session()->get('role');
        $accountId = (int) session()->get('user_ID');

        if ($this->request->is('post')) {
            if ((string) $this->request->getPost('form_action') === 'search') {
                return redirect()->to(list_url('orders', (string) $this->request->getPost('filter'), (string) $this->request->getPost('q'), 1));
            }

            if (!$this->handlePost($role, $accountId, model(OrderModel::class), model(OrderLineItemModel::class), model(ProductModel::class))) {
                return redirect()->to('orders')->withInput();
            }

            // Denying or voiding an item brings the admin back to the same
            // filter, search and page, with that order's items window open
            // again, so they can see the result and carry on with the next item.
            if ((string) $this->request->getPost('form_action') === 'deny_item') {
                session()->setFlashdata('open_items', (int) $this->request->getPost('order_id'));

                return redirect()->to(list_url(
                    'orders',
                    $this->resolveStatus((string) $this->request->getPost('filter')) ?? 'all',
                    (string) $this->request->getPost('q'),
                    max(1, (int) $this->request->getPost('page'))
                ));
            }

            return redirect()->to('orders');
        }

        // Admin and Super Admin open on the Pending orders unless a shortcut
        // (e.g. the Dashboard's cards) asked for a particular status.
        $status = $this->resolveStatus($statusFilter);
        if ($statusFilter === null && in_array($role, ['Admin', 'Super Admin'], true)) {
            $status = 'Pending';
        }

        return $this->render($status, '', 1);
    }

    /**
     * orders/pending-summary/{after} - a few numbers, as JSON, that an open
     * Admin page asks for now and then to show new Pending orders without
     * reloading itself.
     */
    public function pendingSummary(int $after = 0)
    {
        return $this->response
            ->setHeader('Cache-Control', 'no-store')
            ->setJSON(model(OrderModel::class)->pendingSummary($after));
    }

    /** orders/browse/{status|all}/q{search}/{page} */
    public function browse(string $status, string $search, int $page)
    {
        return $this->render($this->resolveStatus($status), list_query_from_segment($search), $page);
    }

    /** Matches a status name case-insensitively; anything else (or 'all') is no filter. */
    private function resolveStatus(?string $value): ?string
    {
        foreach (self::STATUSES as $candidate) {
            if ($value !== null && strtolower($candidate) === strtolower($value)) {
                return $candidate;
            }
        }

        return null;
    }

    private function render(?string $status, string $query, int $page)
    {
        $role = (string) session()->get('role');
        $accountId = (int) session()->get('user_ID');
        $lineItemModel = model(OrderLineItemModel::class);

        [$orders, $total, $page, $totalPages] = model(OrderModel::class)->browse($role, $accountId, $status, $query, $page);
        foreach ($orders as &$order) {
            $order['items'] = $lineItemModel->forOrder((int) $order['Order_ID']);
            $order['denied_items'] = $this->labelRemovedItems(
                (int) $order['Order_ID'],
                $lineItemModel->deniedForOrder((int) $order['Order_ID'])
            );
        }
        unset($order);

        $content = view('orders/index', [
            'orders' => $orders,
            'products' => in_array($role, ['Branch', 'Super Admin'], true) ? model(ProductModel::class)->orderBy('Name')->findAll() : [],
            'role' => $role,
            'statuses' => self::STATUSES,
            'status' => $status,
            'query' => $query,
            'page' => $page,
            'total_pages' => $totalPages,
            'total' => $total,
            'msg' => session()->getFlashdata('msg'),
            'error' => session()->getFlashdata('error'),
            'open_items' => (int) session()->getFlashdata('open_items'),
        ]);

        return view('layouts/main', [
            'content' => $content,
            'page_title' => "Orders - Gab's Bakeshop",
            'page_css' => 'orders.css',
            'nav_role' => $role,
            'nav_current_page' => 'orders',
        ]);
    }

    /** An order number from the address, or a 404 if it can't be a real one (e.g. far too long). */
    private function orderIdFromUrl(string $value): int
    {
        if (!ctype_digit($value) || strlen($value) > 9) {
            throw PageNotFoundException::forPageNotFound();
        }

        return (int) $value;
    }

    /**
     * Half-page Acknowledgement Receipt for a branch's order: acknowledges
     * receipt of the order's current total as payment. It only makes sense
     * once the order is Paid, so any other status is a 404; and only Admin
     * or the branch that placed the order may open it.
     */
    public function billingStatement(string $orderId)
    {
        $orderId = $this->orderIdFromUrl($orderId);
        $role = (string) session()->get('role');
        $accountId = (int) session()->get('user_ID');

        $order = model(OrderModel::class)
            ->select('orders.*, a.Name AS branch_name, a.Email AS branch_email')
            ->join('accounts a', 'a.Account_ID = orders.Account_ID')
            ->find($orderId);

        if (!$order || $order['Status'] !== 'Paid') {
            throw PageNotFoundException::forPageNotFound();
        }

        if ($role === 'Branch' && (int) $order['Account_ID'] !== $accountId) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('orders/billing_statement', [
            'statement' => [
                'order_no' => $order['Name'],
                'order_date' => $order['Order_Date'],
                'branch_name' => $order['branch_name'],
                'branch_email' => $order['branch_email'],
                'total' => (float) $order['Order_Total'],
                'status' => $order['Status'],
            ],
        ]);
    }

    /**
     * Printable invoice for one order, built from its real line items.
     * Only available once an order has been Approved (i.e. is actually
     * going out), and only to Admin/Delivery or the branch that placed it.
     */
    public function invoice(string $orderId)
    {
        $orderId = $this->orderIdFromUrl($orderId);
        $role = (string) session()->get('role');
        $accountId = (int) session()->get('user_ID');

        $order = model(OrderModel::class)
            ->select('orders.*, a.Name AS branch_name, a.Email AS branch_email')
            ->join('accounts a', 'a.Account_ID = orders.Account_ID')
            ->find($orderId);

        if (!$order || !in_array($order['Status'], self::INVOICEABLE_STATUSES, true)) {
            throw PageNotFoundException::forPageNotFound();
        }

        if ($role === 'Branch' && (int) $order['Account_ID'] !== $accountId) {
            throw PageNotFoundException::forPageNotFound();
        }

        $items = array_map(static fn (array $item): array => [
            'description' => $item['Name'],
            'qty' => (float) $item['Qty'],
            'unit_price' => (float) $item['Price'],
        ], model(OrderLineItemModel::class)->forOrder($orderId));

        return view('orders/invoice', [
            'invoice' => [
                'invoice_no' => date('Y', strtotime($order['Order_Date'])) . '-' . str_pad((string) $orderId, 6, '0', STR_PAD_LEFT),
                'order_no' => $order['Name'],
                'order_date' => $order['Order_Date'],
                'payment_terms' => $order['Status'] === 'Paid' ? 'Paid' : 'Due on next delivery',
                'customer_name' => $order['branch_name'],
                'customer_address' => '—',
                'customer_contact' => $order['branch_email'] ?: '—',
                'items' => $items,
                'remarks' => $order['Details'] ?: '—',
                'prepared_by' => '',
                'delivered_by' => '',
                'received_by' => '',
                'approved_by' => '',
            ],
        ]);
    }

    private function handlePost(
        string $role,
        int $accountId,
        OrderModel $orderModel,
        OrderLineItemModel $lineItemModel,
        ProductModel $productModel
    ): bool {
        $action = (string) $this->request->getPost('form_action');

        if ($action === 'place' && in_array($role, ['Branch', 'Super Admin'], true)) {
            if (!can('orders.place')) {
                session()->setFlashdata('error', 'You are not allowed to place orders.');

                return false;
            }

            return $this->placeOrder($accountId, $orderModel, $lineItemModel, $productModel);
        }

        if ($action === 'status') {
            $this->changeStatus($role, $accountId, $orderModel, $lineItemModel, $productModel);
        }

        if ($action === 'deny_item') {
            $this->denyItem($role, $accountId, $orderModel, $lineItemModel);
        }

        return true;
    }

    /**
     * Removed items are all soft-deleted the same way, so the log tells a
     * denied item (while pending) from a voided one (after delivery): each
     * "Voided item: Name (xQty)" entry claims one matching removed item.
     *
     * @param list<array<string, mixed>> $removed
     * @return list<array<string, mixed>> the same rows, each with a 'voided' flag
     */
    private function labelRemovedItems(int $orderId, array $removed): array
    {
        if ($removed === []) {
            return [];
        }

        $logged = model(OrdersUpdateHistoryModel::class)
            ->where('Order_ID', $orderId)
            ->like('Remarks', 'Voided item: ', 'after')
            ->findAll();
        $remarks = array_column($logged, 'Remarks');

        foreach ($removed as &$item) {
            $prefix = 'Voided item: ' . $item['Name'] . ' (x' . (int) $item['Qty'] . ')';
            $item['voided'] = false;

            foreach ($remarks as $i => $remark) {
                if ($remark === $prefix || str_starts_with($remark, $prefix . ' - ')) {
                    $item['voided'] = true;
                    unset($remarks[$i]);
                    break;
                }
            }
        }
        unset($item);

        return $removed;
    }

    /**
     * Admin/Super Admin can take one product out of an order batch:
     *
     *  - Pending: the item is denied. It drops out of the order's total (and
     *    so out of stock deduction, the delivery receipt and sales figures
     *    once the rest is approved); if it was the last one left, the whole
     *    order becomes Denied.
     *  - Delivered (not yet paid): the item is voided, e.g. it spoiled or was
     *    forgotten. The admin chooses how many units to void: all of them, or
     *    just some (e.g. 2 of 5), in which case the rest stays on the order.
     *    The order total drops and so does what the branch owes
     *    (Outstanding_Balance); if every unit of the last item is voided, the
     *    whole order becomes Cancelled. Product stock is left alone, since a
     *    spoiled item is gone either way.
     *
     * Each change is logged to orders_update_history.
     */
    private function denyItem(string $role, int $accountId, OrderModel $orderModel, OrderLineItemModel $lineItemModel): void
    {
        if (!in_array($role, ['Admin', 'Super Admin'], true) || !can('orders.approve')) {
            session()->setFlashdata('error', 'You are not allowed to make that change.');

            return;
        }

        $orderId = (int) $this->request->getPost('order_id');
        $itemId = (int) $this->request->getPost('item_id');
        $order = $orderModel->find($orderId);

        if (!$order || !in_array($order['Status'], ['Pending', 'Delivered'], true)) {
            session()->setFlashdata('error', 'Items can only be denied while the order is pending, or voided once it is delivered and before it is paid.');

            return;
        }

        $voiding = $order['Status'] === 'Delivered';

        $item = null;
        foreach ($lineItemModel->forOrder($orderId) as $candidate) {
            if ((int) $candidate['Order_Line_Item_ID'] === $itemId) {
                $item = $candidate;
            }
        }

        if ($item === null) {
            session()->setFlashdata('error', 'That item is not part of this order.');

            return;
        }

        // How many units to void. Left blank (or when denying) it is the whole
        // line; otherwise a whole number from 1 up to the units still on it.
        $itemQty = (float) $item['Qty'];
        $voidQty = $itemQty;
        if ($voiding) {
            $rawQty = trim((string) $this->request->getPost('qty'));
            if ($rawQty !== '') {
                if (!ctype_digit($rawQty) || (int) $rawQty < 1 || (float) $rawQty > $itemQty) {
                    session()->setFlashdata('error', 'Enter a whole number from 1 to ' . (int) $itemQty . ' for how many to void.');

                    return;
                }
                $voidQty = (float) (int) $rawQty;
            }
        }
        $partial = $voiding && $voidQty < $itemQty;

        $db = db_connect();
        $db->transStart();

        if ($partial) {
            // Keep the rest on the order, and record the voided units as their
            // own removed line, so the Items list still shows what was voided.
            $lineItemModel->update($itemId, ['Qty' => $itemQty - $voidQty]);

            $voidedLine = [
                'Order_ID' => $orderId,
                'Product_ID' => $item['Product_ID'],
                'Name' => $item['Name'],
                'Qty' => $voidQty,
                'Price' => $item['Price'],
            ];
            if (column_ready('order_line_item', 'Cost') && isset($item['Cost'])) {
                $voidedLine['Cost'] = $item['Cost'];
            }
            $voidedId = $lineItemModel->insert($voidedLine, true);
            $lineItemModel->delete($voidedId);
        } else {
            $lineItemModel->delete($itemId);
        }

        $remainingTotal = 0.0;
        $remainingCount = 0;
        foreach ($lineItemModel->forOrder($orderId) as $remaining) {
            $remainingTotal += (float) $remaining['Qty'] * (float) $remaining['Price'];
            $remainingCount++;
        }

        $update = ['Order_Total' => $remainingTotal];
        if ($remainingCount === 0) {
            $update['Status'] = $voiding ? 'Cancelled' : 'Denied';
        }
        $orderModel->update($orderId, $update);

        if ($voiding) {
            // The branch was charged for the whole batch when it was
            // delivered, so take this item's amount back off what it owes.
            $accountModel = model(AccountModel::class);
            $branchAccount = $accountModel->find((int) $order['Account_ID']);
            if ($branchAccount) {
                $accountModel->update($order['Account_ID'], [
                    'Outstanding_Balance' => (float) $branchAccount['Outstanding_Balance'] - $voidQty * (float) $item['Price'],
                ]);
            }
        }

        $remarks = ($voiding ? 'Voided item: ' : 'Denied item: ') . $item['Name'] . ' (x' . (int) $voidQty . ')';
        $reason = trim((string) $this->request->getPost('reason'));
        if ($voiding && $reason !== '') {
            $remarks .= ' - ' . $reason;
        }

        model(OrdersUpdateHistoryModel::class)->insert([
            'Order_ID' => $orderId,
            'Account_ID' => $accountId,
            'Remarks' => mb_substr($remarks, 0, 255),
        ]);

        $db->transComplete();

        if ($voiding) {
            if ($remainingCount === 0) {
                $message = 'That was the last item, so the whole order was cancelled and the branch no longer owes for it.';
            } elseif ($partial) {
                $message = 'Voided ' . (int) $voidQty . ' of ' . (int) $itemQty . ' ' . $item['Name'] . '. The order total and the branch\'s balance were updated.';
            } else {
                $message = 'Item voided. The order total and the branch\'s balance were updated.';
            }
            session()->setFlashdata('msg', $message);

            return;
        }

        session()->setFlashdata('msg', $remainingCount === 0
            ? 'That was the last item, so the whole order was denied.'
            : 'Item denied. The order total was updated.');
    }

    private function placeOrder(
        int $accountId,
        OrderModel $orderModel,
        OrderLineItemModel $lineItemModel,
        ProductModel $productModel
    ): bool {
        $productIds = $this->request->getPost('product_id') ?? [];
        $qtys = $this->request->getPost('qty') ?? [];

        // The same product listed twice is one line with the quantities added.
        $lineItems = [];

        foreach ($productIds as $i => $productId) {
            // Branch orders in whole units only: blank or 0 means "not ordering
            // this one", anything else that isn't a whole number is refused
            // rather than quietly rounded.
            $raw = trim((string) ($qtys[$i] ?? ''));
            if ($raw === '' || (ctype_digit($raw) && (int) $raw === 0)) {
                continue;
            }

            if (!ctype_digit($raw) || (int) $raw > self::MAX_ORDER_QTY) {
                session()->setFlashdata('error', 'Quantities must be whole numbers, up to ' . number_format(self::MAX_ORDER_QTY) . ' of each product.');

                return false;
            }

            $product = $productModel->find((int) $productId);
            if (!$product) {
                session()->setFlashdata('error', 'One of the selected products is no longer available. Please reopen Place Order.');

                return false;
            }

            $key = (int) $product['Product_ID'];
            if (isset($lineItems[$key])) {
                $lineItems[$key]['Qty'] += (int) $raw;
                continue;
            }

            $line = [
                'Product_ID' => $product['Product_ID'],
                'Name' => $product['Name'],
                'Qty' => (int) $raw,
                'Price' => $product['Price'],
            ];
            // The product's cost at this moment, kept with the item like its
            // price, so the Sales Report can take it off the sale later.
            if (column_ready('order_line_item', 'Cost')) {
                $line['Cost'] = $product['Cost'];
            }
            $lineItems[$key] = $line;
        }

        $lineItems = array_values($lineItems);
        $total = 0.0;
        foreach ($lineItems as $line) {
            if ($line['Qty'] > self::MAX_ORDER_QTY) {
                session()->setFlashdata('error', 'Quantities must be whole numbers, up to ' . number_format(self::MAX_ORDER_QTY) . ' of each product.');

                return false;
            }
            $total += $line['Qty'] * (float) $line['Price'];
        }

        if (empty($lineItems)) {
            session()->setFlashdata('error', 'Select at least one product with a quantity.');

            return false;
        }

        if ($total > self::MAX_AMOUNT) {
            session()->setFlashdata('error', 'That order is too large. Please split it into smaller orders.');

            return false;
        }
        $duplicate = $this->findOpenDuplicate($accountId, $lineItems, $orderModel, $lineItemModel);
        if ($duplicate !== null) {
            session()->setFlashdata('error', 'You already have an open order with these exact items (' . $duplicate['Name'] . ', ' . $duplicate['Status'] . '). Cancel it first, or change the items or quantities.');

            return false;
        }

        $name = 'Order #' . date('YmdHis');
        $orderId = $orderModel->insert([
            'Account_ID' => $accountId,
            'Name' => $name,
            'Details' => mb_substr(trim((string) $this->request->getPost('details')), 0, 255),
            'Status' => 'Pending',
            'Order_Total' => $total,
        ], true);

        // Two orders placed in the same second would share a name; tell them
        // apart by adding the order's id to the later one.
        if ((int) $orderModel->where('Name', $name)->countAllResults() > 1) {
            $orderModel->update($orderId, ['Name' => $name . '-' . $orderId]);
        }

        foreach ($lineItems as $item) {
            $item['Order_ID'] = $orderId;
            $lineItemModel->insert($item);
        }

        session()->setFlashdata('msg', 'Order placed.');

        return true;
    }

    /**
     * A branch can't place an order whose products and quantities exactly
     * match one of its own orders that is still open (Pending, Approved, or
     * Delivered but unpaid). Cancelled, Denied and Paid orders are finished,
     * so repeating one of those is allowed.
     *
     * @param list<array<string, mixed>> $lineItems
     */
    private function findOpenDuplicate(
        int $accountId,
        array $lineItems,
        OrderModel $orderModel,
        OrderLineItemModel $lineItemModel
    ): ?array {
        $wanted = $this->quantitiesByProduct($lineItems);

        foreach ($orderModel->forRole('Branch', $accountId, ['Pending', 'Approved', 'Delivered']) as $existing) {
            $have = $this->quantitiesByProduct($lineItemModel->forOrder((int) $existing['Order_ID']));

            if ($have == $wanted) {
                return $existing;
            }
        }

        return null;
    }

    /**
     * @param list<array<string, mixed>> $lineItems
     * @return array<int, float> Product_ID => total quantity, so the same
     *                           product listed twice still compares equal.
     */
    private function quantitiesByProduct(array $lineItems): array
    {
        $byProduct = [];
        foreach ($lineItems as $item) {
            $productId = (int) $item['Product_ID'];
            $byProduct[$productId] = ($byProduct[$productId] ?? 0.0) + (float) $item['Qty'];
        }
        ksort($byProduct);

        return $byProduct;
    }

    private function changeStatus(
        string $role,
        int $accountId,
        OrderModel $orderModel,
        OrderLineItemModel $lineItemModel,
        ProductModel $productModel
    ): void {
        $orderId = (int) $this->request->getPost('order_id');
        $newStatus = (string) $this->request->getPost('status');

        if (!in_array($newStatus, self::STATUSES, true)) {
            session()->setFlashdata('error', 'Invalid status.');
            return;
        }

        $canChange = (in_array($role, ['Admin', 'Super Admin'], true) && in_array($newStatus, ['Approved', 'Denied'], true))
            || ($role === 'Delivery' && $newStatus === 'Delivered')
            || ($role === 'Branch' && $newStatus === 'Cancelled')
            || (in_array($role, ['Admin', 'Super Admin', 'Delivery'], true) && $newStatus === 'Paid');

        // Super Admin can switch each of these off per role (Permissions page).
        $permissionFor = [
            'Approved' => 'orders.approve',
            'Denied' => 'orders.approve',
            'Delivered' => 'orders.deliver',
            'Paid' => 'orders.pay',
            'Cancelled' => 'orders.cancel',
        ][$newStatus] ?? null;

        if ($canChange && $permissionFor !== null && !can($permissionFor)) {
            $canChange = false;
        }

        if (!$canChange) {
            session()->setFlashdata('error', 'You are not allowed to make that change.');
            return;
        }

        // Cancelling and denying are only for an order that hasn't been acted
        // on yet - once it is approved its stock is deducted, and once it is
        // delivered the branch owes for it, so neither can simply be undone
        // here. A branch can also only cancel its own orders.
        if (in_array($newStatus, ['Cancelled', 'Denied'], true)) {
            $order = $orderModel->find($orderId);

            if (!$order || ($newStatus === 'Cancelled' && (int) $order['Account_ID'] !== $accountId)) {
                session()->setFlashdata('error', 'Order not found.');

                return;
            }

            if ($order['Status'] !== 'Pending') {
                session()->setFlashdata('error', 'Only a pending order can be ' . strtolower($newStatus) . '.');

                return;
            }
        }

        if ($newStatus === 'Approved') {
            $this->approveOrder($orderId, $accountId, $orderModel, $lineItemModel, $productModel);
            return;
        }

        if ($newStatus === 'Paid') {
            $this->markPaid($orderId, $accountId, $orderModel);
            return;
        }

        if ($newStatus === 'Delivered') {
            $this->markDelivered($orderId, $orderModel);
            return;
        }

        $orderModel->update($orderId, ['Status' => $newStatus]);
        session()->setFlashdata('msg', 'Order updated.');
    }

    /**
     * Marking an order Delivered requires a proof photo and adds the order's
     * total to the branch's Outstanding_Balance - it's now owed, whether or
     * not the branch pays right away.
     */
    private function markDelivered(int $orderId, OrderModel $orderModel): void
    {
        $order = $orderModel->find($orderId);

        if (!$order || $order['Status'] !== 'Approved') {
            session()->setFlashdata('error', 'That order is not ready for delivery.');

            return;
        }

        $file = $this->request->getFile('proof_image');

        if (!$file || !$file->isValid() || $file->hasMoved()) {
            session()->setFlashdata('error', 'A proof-of-delivery photo is required to mark an order as delivered.');

            return;
        }

        // Only genuine images, judged by the file's contents (not the name or
        // type the browser claims), saved under a name we choose so an upload
        // can never carry a script extension.
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        $extension = $extensions[(string) $file->getMimeType()] ?? null;

        if ($extension === null) {
            session()->setFlashdata('error', 'The proof must be a photo (JPG, PNG, GIF or WEBP).');

            return;
        }

        if ($file->getSize() > 8 * 1024 * 1024) {
            session()->setFlashdata('error', 'The photo is too large. Please upload one under 8 MB.');

            return;
        }

        $newName = time() . '_' . bin2hex(random_bytes(10)) . '.' . $extension;
        $file->move(WRITEPATH . 'uploads', $newName);

        $accountModel = model(AccountModel::class);
        $branchAccount = $accountModel->find((int) $order['Account_ID']);

        $db = db_connect();
        $db->transStart();

        if ($branchAccount) {
            $accountModel->update($order['Account_ID'], [
                'Outstanding_Balance' => (float) $branchAccount['Outstanding_Balance'] + (float) $order['Order_Total'],
            ]);
        }

        $orderModel->update($orderId, ['Status' => 'Delivered', 'Proof_Image' => $newName]);
        $db->transComplete();

        session()->setFlashdata('msg', 'Order updated.');
    }

    /**
     * Marking a delivered order Paid subtracts its total back off the
     * branch's Outstanding_Balance and logs the payment to
     * orders_update_history. Requires the order still be Delivered (not
     * already Paid) so a duplicate/replayed request can't deduct the
     * balance twice.
     */
    private function markPaid(int $orderId, int $accountId, OrderModel $orderModel): void
    {
        $order = $orderModel->find($orderId);

        if (!$order || $order['Status'] !== 'Delivered') {
            session()->setFlashdata('error', 'Only delivered, unpaid orders can be marked paid.');

            return;
        }

        $accountModel = model(AccountModel::class);
        $branchAccount = $accountModel->find((int) $order['Account_ID']);

        $db = db_connect();
        $db->transStart();

        if ($branchAccount) {
            $accountModel->update($order['Account_ID'], [
                'Outstanding_Balance' => (float) $branchAccount['Outstanding_Balance'] - (float) $order['Order_Total'],
            ]);
        }

        $orderModel->update($orderId, ['Status' => 'Paid']);

        model(OrdersUpdateHistoryModel::class)->insert([
            'Order_ID' => $orderId,
            'Account_ID' => $accountId,
            'Remarks' => 'Marked Paid',
        ]);

        $db->transComplete();

        session()->setFlashdata('msg', 'Order marked as paid.');
    }

    /**
     * Approving a branch order deducts each ordered product's quantity from
     * Product Inventory and logs every deduction to Product Audit. Stock is
     * allowed to go negative - a shortage just means Admin still needs to
     * produce more of that product, tracked as "Needs Production" on
     * Product Inventory rather than blocking the approval. Requires the
     * order still be Pending so a duplicate/replayed request can't deduct
     * stock twice.
     */
    private function approveOrder(
        int $orderId,
        int $accountId,
        OrderModel $orderModel,
        OrderLineItemModel $lineItemModel,
        ProductModel $productModel
    ): void {
        $order = $orderModel->find($orderId);

        if (!$order || $order['Status'] !== 'Pending') {
            session()->setFlashdata('error', 'That order is no longer pending.');

            return;
        }

        $qtyByProduct = [];
        foreach ($lineItemModel->forOrder($orderId) as $item) {
            $productId = (int) $item['Product_ID'];
            $qtyByProduct[$productId] = ($qtyByProduct[$productId] ?? 0) + (float) $item['Qty'];
        }

        $products = [];
        foreach ($qtyByProduct as $productId => $qty) {
            $product = $productModel->find($productId);
            if ($product) {
                $products[$productId] = $product;
            }
        }

        $historyModel = model(ProductHistoryModel::class);
        $db = db_connect();
        $db->transStart();

        foreach ($qtyByProduct as $productId => $qty) {
            $product = $products[$productId] ?? null;
            if (!$product) {
                continue;
            }

            $newStock = (float) $product['Stock'] - $qty;

            $productModel->update($productId, ['Stock' => $newStock]);
            $historyModel->insert([
                'Product_ID' => $productId,
                'Account_ID' => $accountId,
                'Old_Stock' => $product['Stock'],
                'New_Stock' => $newStock,
                'Remarks' => 'Order #' . $orderId . ' approved' . ($newStock < 0 ? ' (needs production)' : ''),
            ]);
        }

        $orderModel->update($orderId, ['Status' => 'Approved']);
        $db->transComplete();

        session()->setFlashdata('msg', 'Order approved.');
    }
}
