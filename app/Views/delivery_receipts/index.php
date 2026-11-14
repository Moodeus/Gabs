<div class="page-content">
    <div class="dashboard-content">
        <h1>Delivery Receipts</h1>
        <p class="user-info">Proof-of-delivery photos uploaded by Delivery when an order is marked Delivered.</p>

        <div class="dashboard-section">
            <?php
            $branchSegment = $branch_id !== null ? '/branch/' . $branch_id : '';
            $categorySegment = $category !== null ? '/filter/' . $category : '';
            ?>
            <div class="filter-row">
                <?= view('layouts/filter_tabs', ['baseUrl' => 'delivery-receipts', 'categories' => $categories, 'current' => $category, 'suffix' => $branchSegment]) ?>
                <?php if (!empty($branches)): ?>
                    <div class="filter-tabs">
                        <label for="branch-filter">Branch</label>
                        <select id="branch-filter" onchange="window.location.href = this.value">
                            <option value="<?= esc(site_url('delivery-receipts' . $categorySegment)) ?>"<?= $branch_id === null ? ' selected' : '' ?>>All branches</option>
                            <?php foreach ($branches as $id => $name): ?>
                                <option value="<?= esc(site_url('delivery-receipts' . $categorySegment . '/branch/' . $id)) ?>"<?= $branch_id === $id ? ' selected' : '' ?>><?= esc($name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
            </div>
            <div class="recent-table-wrap">
                <table class="recent-table">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Branch</th>
                            <th>Delivered</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Proof</th>
                            <th>Print</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?= esc($order['Name']) ?></td>
                                <td><?= esc($order['branch_name']) ?></td>
                                <td><?= esc(date('M j, Y g:i A', strtotime($order['Order_Date']))) ?></td>
                                <td>₱<?= esc(number_format((float) $order['Order_Total'], 2)) ?></td>
                                <td>
                                    <?php if ($order['Status'] === 'Paid'): ?>
                                        <span class="status-pill status-paid">Paid</span>
                                    <?php else: ?>
                                        <span class="status-pill status-warning">Unpaid</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($order['Proof_Image'])): ?>
                                        <a href="<?= site_url('delivery-receipts/image/' . $order['Proof_Image']) ?>" target="_blank" rel="noopener">View Photo</a>
                                    <?php else: ?>
                                        <span class="status-pill status-warning">Not uploaded</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (can('orders.invoice')): ?>
                                        <a class="quick-btn" href="<?= site_url('orders/' . $order['Order_ID'] . '/invoice') ?>" target="_blank" rel="noopener">Delivery</a>
                                        <?php if ($order['Status'] === 'Paid'): ?>
                                            <a class="quick-btn" href="<?= site_url('orders/' . $order['Order_ID'] . '/billing-statement') ?>" target="_blank" rel="noopener">Acknowledgement</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="7"><?= ($category === null && $branch_id === null) ? 'No delivered orders yet.' : 'No matching receipts.' ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?= view('layouts/pagination', [
                'page' => $page,
                'totalPages' => $total_pages,
                'urlFor' => static fn (int $n): string => site_url('delivery-receipts' . $categorySegment . $branchSegment . '/page/' . $n),
            ]) ?>
        </div>
    </div>
</div>
