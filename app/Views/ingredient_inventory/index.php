<div class="page-content">
    <div class="dashboard-content">
        <div class="page-header-row">
            <h1>Ingredient Inventory</h1>
            <div class="page-header-actions">
                <?php if (can('export.pdf')): ?>
                    <a href="<?= site_url('ingredient-inventory/export') ?>" class="quick-btn" target="_blank">Export to PDF</a>
                <?php endif; ?>
                <?php if (can('reports.ingredient_audit')): ?>
                    <a href="<?= site_url('ingredient-audit') ?>" class="quick-btn">Ingredient Audit</a>
                <?php endif; ?>
                <?php if (can('inventory.add_item')): ?>
                    <button type="button" class="quick-btn quick-btn-primary" data-modal-open="add-ingredient-modal">Add Ingredient</button>
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
                'searchAction' => 'ingredient-inventory',
                'listBase' => 'ingredient-inventory',
                'placeholder' => 'Search ingredients…',
                'query' => $query,
                'filter' => $type,
                'filterOptions' => ['all' => 'All', 'Dry' => 'Dry', 'Wet' => 'Wet'],
            ]) ?>
            <div class="unit-filter" id="unit-filter" role="radiogroup" aria-label="Display stock in" hidden>
                <span class="unit-filter-label">Show stock in:</span>
                <span class="unit-group" data-unit-group="weight">
                    <label><input type="radio" name="ingredient_unit" value="g" data-factor="1" checked> g</label>
                    <label><input type="radio" name="ingredient_unit" value="kg" data-factor="0.001"> kg</label>
                    <label><input type="radio" name="ingredient_unit" value="mg" data-factor="1000"> mg</label>
                </span>
                <span class="unit-group" data-unit-group="volume" hidden>
                    <label><input type="radio" name="ingredient_unit" value="mL" data-factor="1"> mL</label>
                    <label><input type="radio" name="ingredient_unit" value="L" data-factor="0.001"> L</label>
                    <label><input type="radio" name="ingredient_unit" value="gal" data-factor="0.000264172"> gal</label>
                </span>
            </div>
            <div class="recent-table-wrap">
                <table class="recent-table" id="ingredient-table">
                    <thead>
                        <tr>
                            <th>Ingredient</th>
                            <th>Type</th>
                            <th>Unit</th>
                            <th id="stock-col-header">Stock (g)</th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ingredients as $ing): ?>
                            <tr data-ingredient-type="<?= esc($ing['Type']) ?>">
                                <td>
                                    <?php if (can('inventory.edit_settings')): ?>
                                        <button type="button" class="name-link" title="Open this ingredient's settings" data-modal-open="settings-modal-<?= esc($ing['Ingredient_ID']) ?>"><?= esc($ing['Name']) ?></button>
                                    <?php else: ?>
                                        <?= esc($ing['Name']) ?>
                                    <?php endif; ?>
                                </td>
                                <td><span class="type-pill type-<?= esc(strtolower($ing['Type'])) ?>"><?= esc($ing['Type']) ?></span></td>
                                <td><?= esc($ing['Stock_Unit']) ?></td>
                                <?php $isLow = (float) $ing['Stock_In_Grams'] <= (float) $ing['Low_Stock_Threshold']; ?>
                                <td>
                                    <span class="stock-value<?= $isLow ? ' stock-low' : '' ?>" data-grams="<?= esc($ing['Stock_In_Grams']) ?>"><?= esc(number_format((float) $ing['Stock_In_Grams'], 2)) ?></span>
                                    <?php if ($isLow): ?>
                                        <span class="status-pill status-needs-production">Needs Restock</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (can('inventory.adjust_stock')): ?>
                                        <button type="button" class="quick-btn quick-btn-add" data-modal-open="add-stock-modal-<?= esc($ing['Ingredient_ID']) ?>">Add</button>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (can('inventory.adjust_stock')): ?>
                                        <button type="button" class="quick-btn quick-btn-minus" data-modal-open="minus-stock-modal-<?= esc($ing['Ingredient_ID']) ?>">Minus</button>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (can('inventory.edit_settings')): ?>
                                        <button type="button" class="quick-btn" data-modal-open="settings-modal-<?= esc($ing['Ingredient_ID']) ?>">Settings</button>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (can('inventory.delete')): ?>
                                        <?php $usedBy = $used_in[(int) $ing['Ingredient_ID']] ?? []; ?>
                                        <?php if ($usedBy !== []): ?>
                                            <button type="button" class="quick-btn btn-delete-in-use"
                                                data-ingredient-id="<?= esc($ing['Ingredient_ID']) ?>"
                                                data-ingredient-name="<?= esc($ing['Name']) ?>"
                                                data-products="<?= esc(json_encode($usedBy), 'attr') ?>">Delete</button>
                                        <?php else: ?>
                                            <form method="post" action="<?= site_url('ingredient-inventory') ?>" class="inline-form" onsubmit="return confirm('Remove <?= esc($ing['Name'], 'js') ?> from the ingredient list? It can be restored later, nothing is permanently deleted.');">
                                                <input type="hidden" name="form_action" value="delete">
                                                <input type="hidden" name="ingredient_id" value="<?= esc($ing['Ingredient_ID']) ?>">
                                                <button type="submit" class="quick-btn">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($ingredients)): ?>
                            <tr data-search-ignore><td colspan="8"><?= ($query !== '' || $type !== 'all') ? 'No ingredients match.' : 'No ingredients yet.' ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?= view('layouts/pagination', [
                'page' => $page,
                'totalPages' => $total_pages,
                'urlFor' => static fn (int $n): string => list_url('ingredient-inventory', $type, $query, $n),
            ]) ?>
        </div>
    </div>
</div>

<?php if (can('inventory.delete')): ?>
    <dialog id="delete-in-use-modal" class="modal">
        <div class="modal-header">
            <h2>Ingredient Is In Use</h2>
            <button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button>
        </div>
        <form method="post" action="<?= site_url('ingredient-inventory') ?>">
            <input type="hidden" name="form_action" value="delete">
            <input type="hidden" name="ingredient_id" id="delete-in-use-id">
            <p class="warning-text"><strong id="delete-in-use-name"></strong> is needed for these products:</p>
            <ul id="delete-in-use-products" class="warning-list"></ul>
            <p class="role-hint">If you delete it, it will no longer be deducted from stock when these products are produced. It can be restored later; nothing is permanently deleted.</p>
            <div class="modal-actions">
                <button type="button" class="quick-btn" data-modal-close>Cancel</button>
                <button type="submit" class="btn-deny">Delete anyway</button>
            </div>
        </form>
    </dialog>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var dialog = document.getElementById('delete-in-use-modal');
            var list = document.getElementById('delete-in-use-products');
            document.querySelectorAll('.btn-delete-in-use').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    document.getElementById('delete-in-use-id').value = btn.dataset.ingredientId;
                    document.getElementById('delete-in-use-name').textContent = btn.dataset.ingredientName;
                    list.textContent = '';
                    JSON.parse(btn.dataset.products).forEach(function (name) {
                        var li = document.createElement('li');
                        li.textContent = name;
                        list.appendChild(li);
                    });
                    dialog.showModal();
                });
            });
        });
    </script>
<?php endif; ?>

<dialog id="add-ingredient-modal" class="modal" <?= $reopen ? 'open' : '' ?>>
    <div class="modal-header">
        <h2>Add Ingredient</h2>
        <button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button>
    </div>
    <form method="post" action="<?= site_url('ingredient-inventory') ?>">
        <?php if ($reopen): ?>
            <div class="message error"><?= esc($error) ?></div>
        <?php endif; ?>
        <input type="hidden" name="form_action" value="create">
        <label>Name <input type="text" name="name" value="<?= old('name') ?>" required></label>
        <label>
            Stock Unit
            <span class="info-icon" tabindex="0" data-tooltip="The unit your supplier uses when you restock this ingredient, e.g. kg, pcs, L, ml." aria-label="The unit your supplier uses when you restock this ingredient, e.g. kg, pcs, L, ml.">i</span>
            <input type="text" name="stock_unit" value="<?= old('stock_unit') ?>" placeholder="e.g. kg, pcs" required>
        </label>
        <label>
            Unit to Grams
            <span class="info-icon" tabindex="0" data-tooltip="How many grams are in one Stock Unit above. Example: if Stock Unit is 'kg', enter 1000, since 1 kg = 1000 g. Recipes and stock are tracked in grams behind the scenes, so this conversion is what makes that work." aria-label="How many grams are in one Stock Unit above. Example: if Stock Unit is 'kg', enter 1000, since 1 kg = 1000 g. Recipes and stock are tracked in grams behind the scenes, so this conversion is what makes that work.">i</span>
            <input type="number" step="0.01" min="0.01" name="unit_to_grams" value="<?= old('unit_to_grams') ?>" required>
        </label>
        <label>
            Starting Stock (grams)
            <span class="info-icon" tabindex="0" data-tooltip="How much of this ingredient you have right now, already converted into grams (not Stock Units)." aria-label="How much of this ingredient you have right now, already converted into grams (not Stock Units).">i</span>
            <input type="number" step="0.01" min="0" name="stock_in_grams" value="<?= old('stock_in_grams') ?? '0' ?>">
        </label>
        <label>
            Low Stock Warning (grams)
            <span class="info-icon" tabindex="0" data-tooltip="Flag this ingredient as low stock on the Dashboard once it drops to or below this amount, in grams." aria-label="Flag this ingredient as low stock on the Dashboard once it drops to or below this amount, in grams.">i</span>
            <input type="number" step="0.01" min="0" name="low_stock_threshold" value="<?= old('low_stock_threshold') ?? '5' ?>">
        </label>
        <label>
            Type
            <span class="info-icon" tabindex="0" data-tooltip="Used to filter the list below. Dry: flour, sugar, powders, etc. Wet: liquids and perishables like milk, eggs, butter." aria-label="Used to filter the list below. Dry: flour, sugar, powders, etc. Wet: liquids and perishables like milk, eggs, butter.">i</span>
        </label>
        <div class="radio-group">
            <label><input type="radio" name="type" value="Dry" <?= old('type', 'Dry') === 'Dry' ? 'checked' : '' ?>> Dry</label>
            <label><input type="radio" name="type" value="Wet" <?= old('type') === 'Wet' ? 'checked' : '' ?>> Wet</label>
        </div>
        <div class="modal-actions">
            <button type="button" class="quick-btn" data-modal-close>Cancel</button>
            <button type="submit" class="quick-btn quick-btn-primary">Add Ingredient</button>
        </div>
    </form>
</dialog>

<?php foreach ($ingredients as $ing): ?>
    <dialog id="add-stock-modal-<?= esc($ing['Ingredient_ID']) ?>" class="modal">
        <div class="modal-header">
            <h2>Add Stock: <?= esc($ing['Name']) ?></h2>
            <button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button>
        </div>
        <form method="post" action="<?= site_url('ingredient-inventory') ?>">
            <input type="hidden" name="form_action" value="add_stock">
            <input type="hidden" name="ingredient_id" value="<?= esc($ing['Ingredient_ID']) ?>">
            <label>How many grams to add? <input type="number" step="0.01" min="0.01" name="qty" autofocus required></label>
            <label>Note (optional) <input type="text" name="note" maxlength="150" placeholder="e.g. Delivered by supplier"></label>
            <div class="modal-actions">
                <button type="button" class="quick-btn" data-modal-close>Cancel</button>
                <button type="submit" class="quick-btn quick-btn-primary">Add</button>
            </div>
        </form>
    </dialog>

    <dialog id="minus-stock-modal-<?= esc($ing['Ingredient_ID']) ?>" class="modal">
        <div class="modal-header">
            <h2>Remove Stock: <?= esc($ing['Name']) ?></h2>
            <button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button>
        </div>
        <form method="post" action="<?= site_url('ingredient-inventory') ?>">
            <input type="hidden" name="form_action" value="minus_stock">
            <input type="hidden" name="ingredient_id" value="<?= esc($ing['Ingredient_ID']) ?>">
            <label>How many grams to remove? <input type="number" step="0.01" min="0.01" max="<?= esc($ing['Stock_In_Grams']) ?>" name="qty" autofocus required></label>
            <label>Note (optional) <input type="text" name="note" maxlength="150" placeholder="e.g. Spoiled or expired"></label>
            <div class="modal-actions">
                <button type="button" class="quick-btn" data-modal-close>Cancel</button>
                <button type="submit" class="quick-btn quick-btn-primary">Remove</button>
            </div>
        </form>
    </dialog>

    <dialog id="settings-modal-<?= esc($ing['Ingredient_ID']) ?>" class="modal">
        <div class="modal-header">
            <h2>Settings: <?= esc($ing['Name']) ?></h2>
            <button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button>
        </div>
        <form method="post" action="<?= site_url('ingredient-inventory') ?>">
            <input type="hidden" name="form_action" value="edit_settings">
            <input type="hidden" name="ingredient_id" value="<?= esc($ing['Ingredient_ID']) ?>">
            <label>
                Low Stock Warning (grams)
                <span class="info-icon" tabindex="0" data-tooltip="Flag this ingredient as low stock on the Dashboard once it drops to or below this amount, in grams." aria-label="Flag this ingredient as low stock on the Dashboard once it drops to or below this amount, in grams.">i</span>
                <input type="number" step="0.01" min="0" name="low_stock_threshold" value="<?= esc($ing['Low_Stock_Threshold']) ?>" required>
            </label>
            <label>
                Type
                <span class="info-icon" tabindex="0" data-tooltip="Used to filter the ingredient list. Dry: flour, sugar, powders, etc. Wet: liquids and perishables like milk, eggs, butter." aria-label="Used to filter the ingredient list. Dry: flour, sugar, powders, etc. Wet: liquids and perishables like milk, eggs, butter.">i</span>
            </label>
            <div class="radio-group">
                <label><input type="radio" name="type" value="Dry" <?= $ing['Type'] === 'Dry' ? 'checked' : '' ?>> Dry</label>
                <label><input type="radio" name="type" value="Wet" <?= $ing['Type'] === 'Wet' ? 'checked' : '' ?>> Wet</label>
            </div>
            <div class="modal-actions">
                <button type="button" class="quick-btn" data-modal-close>Cancel</button>
                <button type="submit" class="quick-btn quick-btn-primary">Save</button>
            </div>
        </form>
    </dialog>
<?php endforeach; ?>

<script>
    // Display-only unit conversion for the Stock column. The database always
    // stores grams (data-grams on each cell); switching units here just
    // re-renders that same number, it never touches Add/Minus/Settings, which
    // stay in grams. Dry and Wet each offer their own units, so the picker only
    // appears once the list is narrowed to one of them.
    document.addEventListener('DOMContentLoaded', function () {
        var table = document.getElementById('ingredient-table');
        var type = <?= json_encode($type) ?>;
        var stockHeader = document.getElementById('stock-col-header');
        var unitFilter = document.getElementById('unit-filter');
        var weightGroup = document.querySelector('[data-unit-group="weight"]');
        var volumeGroup = document.querySelector('[data-unit-group="volume"]');
        var unitRadios = document.querySelectorAll('input[name="ingredient_unit"]');
        var stockCells = table.querySelectorAll('.stock-value');

        function applyUnit() {
            var checked = document.querySelector('input[name="ingredient_unit"]:checked');
            if (!checked) {
                return;
            }
            var factor = parseFloat(checked.getAttribute('data-factor'));
            stockCells.forEach(function (cell) {
                var grams = parseFloat(cell.getAttribute('data-grams'));
                cell.textContent = (grams * factor).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            });
            if (stockHeader) {
                stockHeader.textContent = 'Stock (' + checked.value + ')';
            }
        }

        unitRadios.forEach(function (radio) {
            radio.addEventListener('change', applyUnit);
        });

        if (type === 'all') {
            return;
        }

        unitFilter.hidden = false;
        var showVolume = type === 'Wet';
        weightGroup.hidden = showVolume;
        volumeGroup.hidden = !showVolume;
        var defaultRadio = (showVolume ? volumeGroup : weightGroup).querySelector('input[type="radio"]');
        if (defaultRadio) {
            defaultRadio.checked = true;
        }
        applyUnit();
    });
</script>
