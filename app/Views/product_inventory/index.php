<div class="page-content">
    <div class="dashboard-content">
        <div class="page-header-row">
            <h1>Product Inventory</h1>
            <div class="page-header-actions">
                <?php if (can('export.pdf')): ?>
                    <a href="<?= site_url('product-inventory/export') ?>" class="quick-btn" target="_blank">Export to PDF</a>
                <?php endif; ?>
                <?php if (can('reports.product_audit')): ?>
                    <a href="<?= site_url('product-audit') ?>" class="quick-btn">Product Audit</a>
                <?php endif; ?>
                <?php if (can('inventory.add_item')): ?>
                    <button type="button" class="quick-btn quick-btn-primary" data-modal-open="add-product-modal">Add Product</button>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="message success"><?= esc($msg) ?></div>
        <?php endif; ?>
        <?php $reopen = !empty($error) && old('form_action') === 'create'; // that form reopens with the message inside it ?>
        <?php if (!empty($error) && !$reopen): ?>
            <div class="message error"><?= esc($error) ?></div>
        <?php endif; ?>

        <div class="dashboard-section">
            <h2>Current Stock</h2>
            <?= view('layouts/list_controls', [
                'searchAction' => 'product-inventory',
                'listBase' => 'product-inventory',
                'placeholder' => 'Search products…',
                'query' => $query,
                'filter' => 'all',
            ]) ?>
            <div class="recent-table-wrap">
                <table class="recent-table" id="product-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Stock</th>
                            <th>Cost</th>
                            <th>Price</th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td>
                                    <?php if (can('inventory.recipe')): ?>
                                        <button type="button" class="name-link" title="View or edit this product's recipe" data-modal-open="recipe-modal-<?= esc($p['Product_ID']) ?>"><?= esc($p['Name']) ?></button>
                                    <?php else: ?>
                                        <?= esc($p['Name']) ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php $isLow = (float) $p['Stock'] <= (float) $p['Low_Stock_Threshold']; ?>
                                    <span class="<?= $isLow ? 'stock-low' : '' ?>"><?= esc(number_format((float) $p['Stock'], 2)) ?></span>
                                    <?php if ($isLow): ?>
                                        <span class="status-pill status-needs-production">Needs Production</span>
                                    <?php endif; ?>
                                </td>
                                <td>₱<?= esc(number_format((float) $p['Cost'], 2)) ?></td>
                                <td>₱<?= esc(number_format((float) $p['Price'], 2)) ?></td>
                                <td>
                                    <?php if (can('inventory.adjust_stock')): ?>
                                        <button type="button" class="quick-btn quick-btn-add" data-modal-open="add-stock-modal-<?= esc($p['Product_ID']) ?>">Add</button>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (can('inventory.adjust_stock')): ?>
                                        <button type="button" class="quick-btn quick-btn-minus" data-modal-open="minus-stock-modal-<?= esc($p['Product_ID']) ?>">Minus</button>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (can('inventory.edit_settings')): ?>
                                        <button type="button" class="quick-btn" data-modal-open="settings-modal-<?= esc($p['Product_ID']) ?>">Settings</button>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (can('inventory.delete')): ?>
                                        <form method="post" action="<?= site_url('product-inventory') ?>" class="inline-form" onsubmit="return confirm('Remove <?= esc($p['Name'], 'js') ?> from the product list? It can be restored later, nothing is permanently deleted.');">
                                            <input type="hidden" name="form_action" value="delete">
                                            <input type="hidden" name="product_id" value="<?= esc($p['Product_ID']) ?>">
                                            <button type="submit" class="quick-btn">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($products)): ?>
                            <tr data-search-ignore><td colspan="8"><?= $query !== '' ? 'No products match.' : 'No products yet.' ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?= view('layouts/pagination', [
                'page' => $page,
                'totalPages' => $total_pages,
                'urlFor' => static fn (int $n): string => list_url('product-inventory', 'all', $query, $n),
            ]) ?>
        </div>
    </div>
</div>

<dialog id="add-product-modal" class="modal" <?= $reopen ? 'open' : '' ?>>
    <div class="modal-header">
        <h2>Add Product</h2>
        <button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button>
    </div>
    <form method="post" action="<?= site_url('product-inventory') ?>">
        <?php if ($reopen): ?>
            <div class="message error"><?= esc($error) ?></div>
        <?php endif; ?>
        <input type="hidden" name="form_action" value="create">
        <label>Name <input type="text" name="name" value="<?= old('name') ?>" required></label>
        <label>Starting Stock <input type="number" step="1" min="0" inputmode="numeric" name="stock" value="<?= old('stock') ?? '0' ?>"></label>
        <label>Cost <input type="number" step="0.01" min="0" name="cost" value="<?= old('cost') ?>" required></label>
        <label>Price <input type="number" step="0.01" min="0" name="price" value="<?= old('price') ?>" required></label>
        <label>Low Stock Warning <input type="number" step="0.01" min="0" name="low_stock_threshold" value="<?= old('low_stock_threshold') ?? '10' ?>"></label>
        <div class="modal-actions">
            <button type="button" class="quick-btn" data-modal-close>Cancel</button>
            <button type="submit" class="quick-btn quick-btn-primary">Add Product</button>
        </div>
    </form>
</dialog>

<?php foreach ($products as $p): ?>
    <dialog id="add-stock-modal-<?= esc($p['Product_ID']) ?>" class="modal">
        <div class="modal-header">
            <h2>Add Stock: <?= esc($p['Name']) ?></h2>
            <button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button>
        </div>
        <form method="post" action="<?= site_url('product-inventory') ?>">
            <input type="hidden" name="form_action" value="add_stock">
            <input type="hidden" name="product_id" value="<?= esc($p['Product_ID']) ?>">
            <p class="role-hint">Production: deducts this recipe's ingredients from Ingredient Inventory.</p>
            <label>How many units to add? <input type="number" step="1" min="1" inputmode="numeric" name="qty_to_add" autofocus required></label>
            <label>Note (optional) <input type="text" name="note" maxlength="150" placeholder="e.g. Morning batch"></label>
            <div class="modal-actions">
                <button type="button" class="quick-btn" data-modal-close>Cancel</button>
                <button type="submit" class="quick-btn quick-btn-primary">Add</button>
            </div>
        </form>
    </dialog>

    <dialog id="minus-stock-modal-<?= esc($p['Product_ID']) ?>" class="modal">
        <div class="modal-header">
            <h2>Remove Stock: <?= esc($p['Name']) ?></h2>
            <button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button>
        </div>
        <form method="post" action="<?= site_url('product-inventory') ?>">
            <input type="hidden" name="form_action" value="minus_stock">
            <input type="hidden" name="product_id" value="<?= esc($p['Product_ID']) ?>">
            <p class="role-hint">Manual decrease (e.g. spoilage/waste). This does not touch ingredients.</p>
            <label>How many units to remove? <input type="number" step="1" min="1" inputmode="numeric" name="qty" autofocus required></label>
            <label>Note (optional) <input type="text" name="note" maxlength="150" placeholder="e.g. Burnt batch"></label>
            <div class="modal-actions">
                <button type="button" class="quick-btn" data-modal-close>Cancel</button>
                <button type="submit" class="quick-btn quick-btn-primary">Remove</button>
            </div>
        </form>
    </dialog>

    <dialog id="settings-modal-<?= esc($p['Product_ID']) ?>" class="modal">
        <div class="modal-header">
            <h2>Settings: <?= esc($p['Name']) ?></h2>
            <button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button>
        </div>
        <form method="post" action="<?= site_url('product-inventory') ?>">
            <input type="hidden" name="form_action" value="edit_settings">
            <input type="hidden" name="product_id" value="<?= esc($p['Product_ID']) ?>">
            <label>Cost <input type="number" step="0.01" min="0" name="cost" value="<?= esc($p['Cost']) ?>" required></label>
            <label>Price <input type="number" step="0.01" min="0" name="price" value="<?= esc($p['Price']) ?>" required></label>
            <label>Low Stock Warning
                <small>Flag this product as low stock on the Dashboard once it drops to or below this amount.</small>
                <input type="number" step="0.01" min="0" name="low_stock_threshold" value="<?= esc($p['Low_Stock_Threshold']) ?>" required>
            </label>
            <div class="modal-actions">
                <button type="button" class="quick-btn" data-modal-close>Cancel</button>
                <button type="submit" class="quick-btn quick-btn-primary">Save</button>
            </div>
        </form>
    </dialog>
<?php endforeach; ?>

<?php foreach ($products as $p): ?>
    <?php $productRecipe = $recipes[$p['Product_ID']] ?? []; ?>
    <dialog id="recipe-modal-<?= esc($p['Product_ID']) ?>" class="modal modal-lg">
        <div class="modal-header">
            <h2>Recipe: <?= esc($p['Name']) ?></h2>
            <button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button>
        </div>
        <form method="post" action="<?= site_url('product-inventory') ?>">
            <input type="hidden" name="form_action" value="save_recipe">
            <input type="hidden" name="product_id" value="<?= esc($p['Product_ID']) ?>">
            <?php if (empty($ingredients)): ?>
                <p>No ingredients yet — add some in Ingredient Inventory first.</p>
            <?php else: ?>
                <p class="role-hint">Grams of each ingredient needed to make one unit of this product. Leave at 0 to leave it out of the recipe.</p>
                <div class="recent-table-wrap">
                    <table class="recent-table">
                        <thead>
                            <tr>
                                <th>Ingredient</th>
                                <th>Grams per Unit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ingredients as $ing): ?>
                                <tr>
                                    <td>
                                        <?= esc($ing['Name']) ?>
                                        <input type="hidden" name="ingredient_id[]" value="<?= esc($ing['Ingredient_ID']) ?>">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="qty_per_unit[]" value="<?= esc($productRecipe[$ing['Ingredient_ID']] ?? 0) ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            <div class="modal-actions">
                <button type="button" class="quick-btn" data-modal-close>Cancel</button>
                <button type="submit" class="quick-btn quick-btn-primary">Save Recipe</button>
            </div>
        </form>
    </dialog>
<?php endforeach; ?>
