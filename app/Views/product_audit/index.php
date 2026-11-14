<div class="page-content">
    <div class="dashboard-content">
        <h1>Product Audit</h1>

        <div class="dashboard-section">
            <?= view('layouts/list_controls', [
                'searchAction' => 'product-audit',
                'listBase' => 'product-audit',
                'placeholder' => 'Search by product name…',
                'query' => $query,
                'filter' => $category ?? 'all',
            ]) ?>
            <?= view('layouts/filter_tabs', ['baseUrl' => 'product-audit', 'categories' => $categories, 'current' => $category, 'query' => $query]) ?>
            <div class="recent-table-wrap">
                <table class="recent-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Product</th>
                            <th>Old Stock</th>
                            <th>New Stock</th>
                            <th>Changed By</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $row): ?>
                            <tr>
                                <td><?= esc(date('M j, Y g:i A', strtotime($row['Action_Date']))) ?></td>
                                <td><?= esc($row['product_name']) ?></td>
                                <td><?= esc(number_format((float) $row['Old_Stock'], 2)) ?></td>
                                <td><?= esc(number_format((float) $row['New_Stock'], 2)) ?></td>
                                <td><?= esc($row['account_name']) ?></td>
                                <td>
                                    <?php if ($row['Remarks'] === 'Deleted'): ?>
                                        <span class="audit-remark-deleted">Deleted</span>
                                    <?php elseif ($row['Remarks'] === 'Created'): ?>
                                        <span class="audit-remark-created">Created</span>
                                    <?php elseif (str_starts_with((string) $row['Remarks'], 'Recipe: ')): ?>
                                        <?php if ($row['New_Recipe'] !== null): ?>
                                            <button type="button" class="audit-remark-recipe audit-remark-recipe-btn" data-modal-open="recipe-diff-modal-<?= esc($row['Product_History_ID']) ?>">Recipe</button>
                                        <?php else: ?>
                                            <span class="audit-remark-recipe">Recipe</span>
                                        <?php endif; ?>
                                    <?php elseif (str_starts_with((string) $row['Remarks'], 'Order #')): ?>
                                        <span class="audit-remark-order">Order Approved</span>
                                    <?php elseif (str_starts_with((string) $row['Remarks'], 'Added ')): ?>
                                        <span class="audit-remark-added">Added</span>
                                    <?php elseif (str_starts_with((string) $row['Remarks'], 'Deducted ')): ?>
                                        <span class="audit-remark-deducted">Deducted</span>
                                    <?php elseif ($row['Remarks'] === null && (float) $row['New_Stock'] > (float) $row['Old_Stock']): ?>
                                        <span class="audit-remark-added">Added</span>
                                    <?php elseif ($row['Remarks'] === null && (float) $row['New_Stock'] < (float) $row['Old_Stock']): ?>
                                        <span class="audit-remark-deducted">Deducted</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['Remarks'] === 'Deleted' || $row['Remarks'] === 'Created'): ?>
                                    <?php elseif (str_starts_with((string) $row['Remarks'], 'Recipe: ')): ?>
                                        <?= esc(substr($row['Remarks'], 8)) ?>
                                    <?php elseif (str_starts_with((string) $row['Remarks'], 'Order #')): ?>
                                        <?= esc(str_replace(' approved', '', $row['Remarks'])) ?>
                                    <?php elseif (str_starts_with((string) $row['Remarks'], 'Added ')): ?>
                                        <?= esc(substr($row['Remarks'], 6)) ?>
                                    <?php elseif (str_starts_with((string) $row['Remarks'], 'Deducted ')): ?>
                                        <?= esc(substr($row['Remarks'], 9)) ?>
                                    <?php elseif ($row['Remarks'] === null && (float) $row['New_Stock'] > (float) $row['Old_Stock']): ?>
                                        <?= esc(rtrim(rtrim(number_format((float) $row['New_Stock'] - (float) $row['Old_Stock'], 2), '0'), '.')) ?> units
                                    <?php elseif ($row['Remarks'] === null && (float) $row['New_Stock'] < (float) $row['Old_Stock']): ?>
                                        <?= esc(rtrim(rtrim(number_format((float) $row['Old_Stock'] - (float) $row['New_Stock'], 2), '0'), '.')) ?> units
                                    <?php else: ?>
                                        <?= esc($row['Remarks'] ?? '') ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($history)): ?>
                            <tr><td colspan="7"><?= ($category === null && $query === '') ? 'No history yet.' : 'No matching history.' ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?= view('layouts/pagination', [
                'page' => $page,
                'totalPages' => $total_pages,
                'urlFor' => static fn (int $n): string => list_url('product-audit', $category ?? 'all', $query, $n),
            ]) ?>
        </div>
    </div>
</div>

<?php foreach ($history as $row): ?>
    <?php if ($row['New_Recipe'] === null) continue; ?>
    <?php
        $newRecipe = json_decode((string) $row['New_Recipe'], true) ?: [];
        $oldRecipe = json_decode((string) ($row['Old_Recipe'] ?? '[]'), true) ?: [];
    ?>
    <dialog id="recipe-diff-modal-<?= esc($row['Product_History_ID']) ?>" class="modal modal-lg">
        <div class="modal-header">
            <h2>Recipe Change: <?= esc($row['product_name']) ?></h2>
            <button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button>
        </div>
        <div class="order-detail-body">
            <p class="role-hint"><?= esc(substr((string) $row['Remarks'], 8)) ?></p>
            <div class="recipe-diff-grid">
                <?php if ($oldRecipe !== []): ?>
                    <div class="recipe-diff-col">
                        <div class="recipe-section-title">Old Recipe</div>
                        <div class="recent-table-wrap">
                            <table class="recent-table">
                                <thead>
                                    <tr><th>Ingredient</th><th>Grams</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($oldRecipe as $item): ?>
                                        <tr>
                                            <td><?= esc($item['name']) ?></td>
                                            <td><?= esc(rtrim(rtrim(number_format((float) $item['qty'], 2), '0'), '.')) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="recipe-diff-col">
                    <div class="recipe-section-title">New Recipe</div>
                    <div class="recent-table-wrap">
                        <table class="recent-table">
                            <thead>
                                <tr><th>Ingredient</th><th>Grams</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($newRecipe as $item): ?>
                                    <tr>
                                        <td><?= esc($item['name']) ?></td>
                                        <td><?= esc(rtrim(rtrim(number_format((float) $item['qty'], 2), '0'), '.')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($newRecipe)): ?>
                                    <tr><td colspan="2">No ingredients.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </dialog>
<?php endforeach; ?>
