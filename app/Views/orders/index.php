<?php $canDenyItems = in_array($role, ['Admin', 'Super Admin'], true) && can('orders.approve'); ?>
<div class="page-content">
    <div class="dashboard-content">
        <div class="page-header-row">
            <h1>Orders</h1>
            <?php if (in_array($role, ['Branch', 'Super Admin'], true) && can('orders.place')): ?>
                <button type="button" class="quick-btn quick-btn-primary" data-modal-open="place-order-modal">Place Order</button>
            <?php endif; ?>
        </div>

        <?php
        // After denying/voiding an item, that order's items window reopens and
        // shows the result itself, so the page-level message would only repeat it.
        $itemsReopenId = in_array((int) ($open_items ?? 0), array_map('intval', array_column($orders, 'Order_ID')), true) ? (int) $open_items : 0;
        ?>
        <?php if (!empty($msg) && !$itemsReopenId): ?>
            <div class="message success"><?= esc($msg) ?></div>
        <?php endif; ?>
        <?php $reopen = !empty($error) && old('form_action') === 'place'; // that form reopens with the message inside it ?>
        <?php if (!empty($error) && !$reopen && !$itemsReopenId): ?>
            <div class="message error"><?= esc($error) ?></div>
        <?php endif; ?>

        <div class="dashboard-section">
            <h2>Order List</h2>
            <?php $canFilter = in_array($role, ['Admin', 'Super Admin'], true); ?>
            <?= view('layouts/list_controls', [
                'searchAction' => 'orders',
                'listBase' => 'orders',
                'placeholder' => 'Search orders…',
                'query' => $query,
                'filter' => $status ?? 'all',
                'filterOptions' => $canFilter ? ['all' => 'All'] + array_combine($statuses, $statuses) : [],
            ]) ?>
            <div class="recent-table-wrap orders-table-wrap">
                <table class="recent-table" id="orders-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <?php if ($role !== 'Branch'): ?><th>Branch</th><?php endif; ?>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr data-status="<?= esc($order['Status']) ?>">
                                <td><?= esc($order['Name']) ?></td>
                                <?php if ($role !== 'Branch'): ?><td><?= esc($order['branch_name']) ?></td><?php endif; ?>
                                <td><?= esc(date('M j, Y g:i A', strtotime($order['Order_Date']))) ?></td>
                                <td>
                                    <button type="button" class="quick-btn" data-modal-open="order-items-modal-<?= esc($order['Order_ID']) ?>">Details</button>
                                </td>
                                <td>₱<?= esc(number_format((float) $order['Order_Total'], 2)) ?></td>
                                <td><span class="status-pill status-<?= esc(strtolower($order['Status'])) ?>"><?= esc($order['Status']) ?></span></td>
                                <td class="orders-action-cell"><?= view('orders/_actions', ['order' => $order, 'role' => $role]) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($orders)): ?>
                            <tr data-search-ignore><td colspan="7"><?= ($query !== '' || $status !== null) ? 'No orders match.' : 'No orders yet.' ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Phone-width view: each order is a tappable row that opens a
                 modal with its full details laid out as stacked rows,
                 instead of squeezing the wide table onto a small screen. -->
            <div class="mobile-order-list">
                <?php foreach ($orders as $order): ?>
                    <button type="button" class="mobile-order-row" data-status="<?= esc($order['Status']) ?>" data-modal-open="order-modal-<?= esc($order['Order_ID']) ?>">
                        <span class="mobile-order-row-main">
                            <strong><?= esc($order['Name']) ?></strong>
                            <?php if ($role !== 'Branch'): ?><span class="mobile-order-row-sub"><?= esc($order['branch_name']) ?></span><?php endif; ?>
                        </span>
                        <span class="mobile-order-row-side">
                            <span>₱<?= esc(number_format((float) $order['Order_Total'], 2)) ?></span>
                            <span class="status-pill status-<?= esc(strtolower($order['Status'])) ?>"><?= esc($order['Status']) ?></span>
                        </span>
                    </button>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?>
                    <p class="role-hint"><?= ($query !== '' || $status !== null) ? 'No orders match.' : 'No orders yet.' ?></p>
                <?php endif; ?>
            </div>
            <?= view('layouts/pagination', [
                'page' => $page,
                'totalPages' => $total_pages,
                'urlFor' => static fn (int $n): string => list_url('orders', $status ?? 'all', $query, $n),
            ]) ?>
        </div>
    </div>
</div>

<?php foreach ($orders as $order): ?>
    <dialog id="order-modal-<?= esc($order['Order_ID']) ?>" class="modal">
        <div class="modal-header">
            <h2><?= esc($order['Name']) ?></h2>
            <button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button>
        </div>
        <div class="order-detail-body">
            <?php if ($role !== 'Branch'): ?>
                <div class="detail-row"><span>Branch</span><span><?= esc($order['branch_name']) ?></span></div>
            <?php endif; ?>
            <div class="detail-row"><span>Date</span><span><?= esc(date('M j, Y g:i A', strtotime($order['Order_Date']))) ?></span></div>
            <div class="detail-row">
                <span>Items</span>
                <span><button type="button" class="quick-btn" data-modal-open="order-items-modal-<?= esc($order['Order_ID']) ?>">Details</button></span>
            </div>
            <div class="detail-row"><span>Total</span><span>₱<?= esc(number_format((float) $order['Order_Total'], 2)) ?></span></div>
            <div class="detail-row"><span>Status</span><span class="status-pill status-<?= esc(strtolower($order['Status'])) ?>"><?= esc($order['Status']) ?></span></div>
            <div class="detail-row-actions"><?= view('orders/_actions', ['order' => $order, 'role' => $role]) ?></div>
        </div>
    </dialog>

    <dialog id="order-items-modal-<?= esc($order['Order_ID']) ?>" class="modal modal-lg">
        <div class="modal-header">
            <h2>Items: <?= esc($order['Name']) ?></h2>
            <button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button>
        </div>
        <?php if ((int) $order['Order_ID'] === $itemsReopenId && (!empty($msg) || !empty($error))): ?>
            <div class="message <?= !empty($error) ? 'error' : 'success' ?>" style="margin:0 24px 12px;"><?= esc(!empty($error) ? $error : $msg) ?></div>
        <?php endif; ?>
        <div class="recent-table-wrap" style="padding:0 24px 20px;">
            <table class="recent-table">
                <?php
                $isDelivered = $order['Status'] === 'Delivered';
                $showDenyColumn = $canDenyItems && in_array($order['Status'], ['Pending', 'Delivered'], true) && !empty($order['items']);
                ?>
                <thead>
                    <tr><th>Product</th><th>Qty</th><th>Price</th><th>Amount</th><?php if ($showDenyColumn): ?><th>Action</th><?php endif; ?></tr>
                </thead>
                <tbody>
                    <?php foreach ($order['items'] as $item): ?>
                        <tr>
                            <td><?= esc($item['Name']) ?></td>
                            <td><?= esc((int) $item['Qty']) ?></td>
                            <td>₱<?= esc(number_format((float) $item['Price'], 2)) ?></td>
                            <td>₱<?= esc(number_format((float) $item['Qty'] * (float) $item['Price'], 2)) ?></td>
                            <?php if ($showDenyColumn): ?>
                                <td>
                                    <button type="button" class="btn-deny btn-deny-item"
                                        data-order-id="<?= esc($order['Order_ID']) ?>"
                                        data-item-id="<?= esc($item['Order_Line_Item_ID']) ?>"
                                        data-item-name="<?= esc($item['Name']) ?>"
                                        data-mode="<?= $isDelivered ? 'void' : 'deny' ?>"
                                        data-qty="<?= (int) $item['Qty'] ?>"
                                        data-price="<?= esc((float) $item['Price']) ?>"
                                        data-is-last="<?= count($order['items']) === 1 ? '1' : '0' ?>"><?= $isDelivered ? 'Void' : 'Deny' ?></button>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    <?php foreach ($order['denied_items'] as $item): ?>
                        <tr class="item-denied">
                            <td><?= esc($item['Name']) ?> <span class="status-pill status-denied"><?= !empty($item['voided']) ? 'Voided' : 'Denied' ?></span></td>
                            <td><?= esc((int) $item['Qty']) ?></td>
                            <td>₱<?= esc(number_format((float) $item['Price'], 2)) ?></td>
                            <td>₱<?= esc(number_format((float) $item['Qty'] * (float) $item['Price'], 2)) ?></td>
                            <?php if ($showDenyColumn): ?><td></td><?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($order['items']) && empty($order['denied_items'])): ?>
                        <tr><td colspan="4">No items.</td></tr>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($order['items']) || !empty($order['denied_items'])): ?>
                    <tfoot>
                        <tr>
                            <th colspan="3">Total<?= !empty($order['denied_items']) ? ' (denied and voided items excluded)' : '' ?></th>
                            <th>₱<?= esc(number_format((float) $order['Order_Total'], 2)) ?></th>
                            <?php if ($showDenyColumn): ?><th></th><?php endif; ?>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </dialog>
<?php endforeach; ?>

<?php if ($canDenyItems): ?>
    <dialog id="deny-item-modal" class="modal">
        <div class="modal-header">
            <h2 id="deny-item-title">Deny Item</h2>
            <button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button>
        </div>
        <form method="post" action="<?= site_url('orders') ?>">
            <input type="hidden" name="form_action" value="deny_item">
            <?php /* where the admin is now, so they land back here afterwards */ ?>
            <input type="hidden" name="filter" value="<?= esc($status ?? 'all') ?>">
            <input type="hidden" name="q" value="<?= esc($query) ?>">
            <input type="hidden" name="page" value="<?= esc($page) ?>">
            <input type="hidden" name="order_id" id="deny-item-order">
            <input type="hidden" name="item_id" id="deny-item-id">
            <p id="deny-item-text" class="role-hint"></p>
            <label id="deny-item-qty-label" style="display:none;">How many to void? <small id="deny-item-qty-hint"></small>
                <input type="number" name="qty" id="deny-item-qty" min="1" step="1" inputmode="numeric" required disabled>
            </label>
            <label id="deny-item-reason-label" style="display:none;">Reason (optional) <input type="text" name="reason" id="deny-item-reason" maxlength="150" placeholder="e.g. Spoiled, forgotten"></label>
            <div class="modal-actions">
                <button type="button" class="quick-btn" data-modal-close>No, keep it</button>
                <button type="submit" class="btn-deny" id="deny-item-submit">Yes, deny it</button>
            </div>
        </form>
    </dialog>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var dialog = document.getElementById('deny-item-modal');
            var text = document.getElementById('deny-item-text');
            var qtyLabel = document.getElementById('deny-item-qty-label');
            var qtyInput = document.getElementById('deny-item-qty');
            var qtyHint = document.getElementById('deny-item-qty-hint');
            var submit = document.getElementById('deny-item-submit');
            var current = null;

            function peso(n) {
                return '\u20B1' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            // The explanation under the title follows how many are being voided.
            function explain() {
                var b = current;
                var name = b.dataset.itemName;
                var last = b.dataset.isLast === '1';

                if (b.dataset.mode !== 'void') {
                    text.textContent = last
                        ? 'Deny "' + name + '"? It is the last item in this order, so the whole order will be denied.'
                        : 'Deny "' + name + '" from this order? The rest of the order stays pending and the total is updated.';
                    return;
                }

                var max = parseInt(b.dataset.qty, 10);
                var n = parseInt(qtyInput.value, 10);
                var valid = n >= 1 && n <= max;

                if (!valid) {
                    text.textContent = 'Enter a whole number from 1 to ' + max + '.';
                    submit.textContent = 'Yes, void it';
                } else if (n === max) {
                    text.textContent = last
                        ? 'Void "' + name + '"? It is the last item in this order, so the whole order will be cancelled and the branch will no longer owe for it.'
                        : 'Void all ' + max + ' of "' + name + '" from this delivered order? The total and the branch\'s balance go down by ' + peso(n * parseFloat(b.dataset.price)) + '. Product stock is not changed.';
                    submit.textContent = 'Yes, void it';
                } else {
                    text.textContent = 'Void ' + n + ' of ' + max + ' "' + name + '"? The other ' + (max - n) + ' stay on the order. The total and the branch\'s balance go down by ' + peso(n * parseFloat(b.dataset.price)) + '. Product stock is not changed.';
                    submit.textContent = 'Yes, void ' + n;
                }
            }

            qtyInput.addEventListener('input', function () { if (current) { explain(); } });

            document.querySelectorAll('.btn-deny-item').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var voiding = btn.dataset.mode === 'void';
                    current = btn;

                    document.getElementById('deny-item-order').value = btn.dataset.orderId;
                    document.getElementById('deny-item-id').value = btn.dataset.itemId;
                    document.getElementById('deny-item-reason').value = '';
                    document.getElementById('deny-item-reason-label').style.display = voiding ? '' : 'none';
                    document.getElementById('deny-item-title').textContent = voiding ? 'Void Item' : 'Deny Item';
                    submit.textContent = voiding ? 'Yes, void it' : 'Yes, deny it';

                    // Only a void asks "how many"; it starts on all of them.
                    qtyLabel.style.display = voiding ? '' : 'none';
                    qtyInput.disabled = !voiding;
                    if (voiding) {
                        qtyInput.max = btn.dataset.qty;
                        qtyInput.value = btn.dataset.qty;
                        qtyHint.textContent = '(of ' + btn.dataset.qty + ')';
                    }

                    explain();
                    dialog.showModal();
                });
            });
        });
    </script>
<?php endif; ?>

<?php if (in_array($role, ['Branch', 'Super Admin'], true) && can('orders.place')): ?>
    <dialog id="place-order-modal" class="modal modal-lg" <?= $reopen ? 'open' : '' ?>>
        <div class="modal-header">
            <h2>Place Order</h2>
            <button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button>
        </div>
        <form method="post" action="<?= site_url('orders') ?>">
            <?php if ($reopen): ?>
                <div class="message error"><?= esc($error) ?></div>
            <?php endif; ?>
            <input type="hidden" name="form_action" value="place">
            <div class="search-bar">
                <input type="search" data-table-search="place-order-table" placeholder="Search products&hellip;" aria-label="Search products">
            </div>
            <div class="recent-table-wrap">
                <table class="recent-table" id="place-order-table">
                    <thead>
                        <tr><th>Product</th><th>Price</th><th>Quantity</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $i => $p): ?>
                            <tr>
                                <td>
                                    <?= esc($p['Name']) ?>
                                    <input type="hidden" name="product_id[]" value="<?= esc($p['Product_ID']) ?>">
                                </td>
                                <td>₱<?= esc(number_format((float) $p['Price'], 2)) ?></td>
                                <td><input type="number" step="1" min="0" name="qty[]" value="<?= $reopen ? (old('qty.' . $i) ?: '0') : '0' ?>"></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($products)): ?>
                            <tr data-search-ignore><td colspan="3">No products available yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <label>Notes <input type="text" name="details"></label>
            <div class="modal-actions">
                <button type="button" class="quick-btn" data-modal-close>Cancel</button>
                <button type="submit" class="quick-btn quick-btn-primary">Place Order</button>
            </div>
        </form>
    </dialog>
<?php endif; ?>

<?php if ($itemsReopenId): ?>
    <script>
        // Back from denying or voiding an item: show that order's items again.
        document.addEventListener('DOMContentLoaded', function () {
            var dialog = document.getElementById('order-items-modal-<?= (int) $itemsReopenId ?>');
            if (dialog && !dialog.open) {
                dialog.showModal();
            }
        });
    </script>
<?php endif; ?>
