<?php

namespace App\Controllers;

use App\Models\IngredientHistoryModel;
use App\Models\IngredientModel;
use App\Models\ProductHistoryModel;
use App\Models\ProductModel;
use App\Models\RecipeModel;

class ProductInventory extends BaseController
{
    /**
     * A posted quantity that must be a whole number of units (1, 2, 3...).
     * Anything else - decimals, text, zero, negatives - comes back as 0, which
     * callers treat as invalid.
     */
    private function wholeUnits(string $field): int
    {
        $raw = trim((string) $this->request->getPost($field));

        return ctype_digit($raw) ? (int) $raw : 0;
    }

    /** Appends the optional note from an Add/Minus modal to a history remark. */
    private function withNote(string $remark): string
    {
        $note = trim((string) $this->request->getPost('note'));

        return $note === '' ? $remark : mb_substr($remark . ' — ' . $note, 0, 255);
    }

    public function index()
    {
        $productModel = model(ProductModel::class);
        $historyModel = model(ProductHistoryModel::class);
        $recipeModel = model(RecipeModel::class);
        $accountId = (int) session()->get('user_ID');

        if ($this->request->is('post')) {
            $action = (string) $this->request->getPost('form_action');

            if ($action === 'search') {
                return redirect()->to(list_url('product-inventory', 'all', (string) $this->request->getPost('q'), 1));
            }

            // Super Admin can switch each of these off per role (Permissions page).
            $permissionFor = [
                'create' => 'inventory.add_item',
                'add_stock' => 'inventory.adjust_stock',
                'minus_stock' => 'inventory.adjust_stock',
                'edit_settings' => 'inventory.edit_settings',
                'save_recipe' => 'inventory.recipe',
                'delete' => 'inventory.delete',
            ][$action] ?? null;

            if ($permissionFor !== null && !can($permissionFor)) {
                session()->setFlashdata('error', 'You are not allowed to do that.');

                return redirect()->to('product-inventory');
            }

            if ($action === 'create') {
                $name = trim((string) $this->request->getPost('name'));
                if ($name === '') {
                    session()->setFlashdata('error', 'Product name is required.');

                    return redirect()->to('product-inventory')->withInput();
                } else {
                    $startingStock = $this->postedNumber('stock', 0.0, 0.0);
                    $cost = $this->postedNumber('cost', 0.0);
                    $price = $this->postedNumber('price', 0.0);
                    $threshold = $this->postedNumber('low_stock_threshold', 0.0, 10.0);

                    if ($startingStock === false || $startingStock != floor($startingStock) || !is_float($cost) || !is_float($price) || $threshold === false) {
                        session()->setFlashdata('error', 'Check the numbers: starting stock must be a whole number, and stock, cost, price and the low stock warning cannot be negative (all up to 99,999,999.99).');

                        return redirect()->to('product-inventory')->withInput();
                    }

                    if (mb_strlen($name) > 100) {
                        session()->setFlashdata('error', 'The name can be up to 100 characters.');

                        return redirect()->to('product-inventory')->withInput();
                    }

                    $newId = $productModel->insert([
                        'Name' => $name,
                        'Stock' => $startingStock,
                        'Cost' => $cost,
                        'Price' => $price,
                        'Low_Stock_Threshold' => $threshold,
                    ], true);
                    $historyModel->insert([
                        'Product_ID' => $newId,
                        'Account_ID' => $accountId,
                        'Old_Stock' => 0,
                        'New_Stock' => $startingStock,
                        'Remarks' => 'Created',
                    ]);
                    session()->setFlashdata('msg', 'Product added.');
                }
            } elseif ($action === 'add_stock') {
                $this->addStock($productModel, $historyModel, $recipeModel, $accountId);
            } elseif ($action === 'minus_stock') {
                $id = (int) $this->request->getPost('product_id');
                $qty = $this->wholeUnits('qty');
                $product = $productModel->find($id);

                if (!$product || $qty <= 0) {
                    session()->setFlashdata('error', 'Enter a whole number of units to remove (no decimals).');
                } else {
                    $newStock = (float) $product['Stock'] - $qty;
                    $productModel->update($id, ['Stock' => $newStock]);
                    $historyModel->insert([
                        'Product_ID' => $id,
                        'Account_ID' => $accountId,
                        'Old_Stock' => $product['Stock'],
                        'New_Stock' => $newStock,
                        'Remarks' => $this->withNote('Deducted ' . $this->formatGrams($qty) . ' units (manual)'),
                    ]);
                    session()->setFlashdata('msg', 'Removed ' . $qty . ' from stock.');
                }
            } elseif ($action === 'edit_settings') {
                $id = (int) $this->request->getPost('product_id');
                $cost = $this->postedNumber('cost', 0.0);
                $price = $this->postedNumber('price', 0.0);
                $threshold = $this->postedNumber('low_stock_threshold', 0.0);
                $product = $productModel->find($id);

                if ($product && (!is_float($cost) || !is_float($price) || !is_float($threshold))) {
                    session()->setFlashdata('error', 'Cost, price and the low stock warning must be numbers from 0 to 99,999,999.99.');
                } elseif ($product) {
                    $productModel->update($id, ['Cost' => $cost, 'Price' => $price, 'Low_Stock_Threshold' => $threshold]);
                    session()->setFlashdata('msg', 'Settings updated.');
                }
            } elseif ($action === 'save_recipe') {
                $this->saveRecipe($productModel, $historyModel, $recipeModel, $accountId);
            } elseif ($action === 'delete') {
                $id = (int) $this->request->getPost('product_id');
                $product = $productModel->find($id);

                if ($product) {
                    $productModel->delete($id);
                    $historyModel->insert([
                        'Product_ID' => $id,
                        'Account_ID' => $accountId,
                        'Old_Stock' => $product['Stock'],
                        'New_Stock' => $product['Stock'],
                        'Remarks' => 'Deleted',
                    ]);
                    session()->setFlashdata('msg', 'Product removed.');
                }
            }

            return redirect()->to('product-inventory');
        }

        return $this->render('', 1);
    }

    /** product-inventory/browse/{filter}/q{search}/{page} - the filter segment is unused here. */
    public function browse(string $filter, string $search, int $page)
    {
        return $this->render(list_query_from_segment($search), $page);
    }

    private function render(string $query, int $page)
    {
        [$products, $total, $page, $totalPages] = model(ProductModel::class)->browse($query, $page);

        $recipes = [];
        foreach ($products as $product) {
            $recipes[$product['Product_ID']] = model(RecipeModel::class)->forProduct((int) $product['Product_ID']);
        }

        $content = view('product_inventory/index', [
            'products' => $products,
            'ingredients' => model(IngredientModel::class)->orderBy('Name')->findAll(),
            'recipes' => $recipes,
            'query' => $query,
            'page' => $page,
            'total_pages' => $totalPages,
            'total' => $total,
            'msg' => session()->getFlashdata('msg'),
            'error' => session()->getFlashdata('error'),
        ]);

        return view('layouts/main', [
            'content' => $content,
            'page_title' => "Product Inventory - Gab's Bakeshop",
            'page_css' => 'productinventory.css',
            'nav_role' => session()->get('role'),
            'nav_current_page' => 'product-inventory',
        ]);
    }

    /**
     * Standalone printable list (no layouts/main chrome), the same pattern
     * as the order invoice - a "Print" button triggers the browser's own
     * print dialog, which can save to PDF without needing a PDF library.
     */
    public function export()
    {
        return view('product_inventory/export', [
            'products' => model(ProductModel::class)->orderBy('Name')->findAll(),
        ]);
    }

    /**
     * Add produced units to a product's stock, deducting the ingredients
     * its recipe calls for (Qty_Per_Unit x quantity added) from Ingredient
     * Inventory. Refuses the whole operation if any ingredient would go
     * negative, and records both sides in their history tables.
     */
    private function addStock(
        ProductModel $productModel,
        ProductHistoryModel $historyModel,
        RecipeModel $recipeModel,
        int $accountId
    ): void {
        $id = (int) $this->request->getPost('product_id');
        $qtyToAdd = $this->wholeUnits('qty_to_add');
        $product = $productModel->find($id);

        if (!$product || $qtyToAdd <= 0) {
            session()->setFlashdata('error', 'Enter a whole number of units to add (no decimals).');

            return;
        }

        $ingredientModel = model(IngredientModel::class);
        $ingredientHistoryModel = model(IngredientHistoryModel::class);
        $recipe = $recipeModel->forProduct($id);

        $needed = [];
        $shortages = [];
        foreach ($recipe as $ingredientId => $qtyPerUnit) {
            $ingredient = $ingredientModel->find($ingredientId);
            if (!$ingredient) {
                continue;
            }

            $amountNeeded = $qtyPerUnit * $qtyToAdd;
            $needed[$ingredientId] = ['ingredient' => $ingredient, 'amount' => $amountNeeded];

            if ((float) $ingredient['Stock_In_Grams'] < $amountNeeded) {
                $shortages[] = sprintf(
                    '%s (need %sg, have %sg)',
                    $ingredient['Name'],
                    number_format($amountNeeded, 2),
                    number_format((float) $ingredient['Stock_In_Grams'], 2)
                );
            }
        }

        if ($shortages !== []) {
            session()->setFlashdata('error', 'Not enough ingredients: ' . implode('; ', $shortages));

            return;
        }

        $db = db_connect();
        $db->transStart();

        foreach ($needed as $ingredientId => $info) {
            $newIngredientStock = (float) $info['ingredient']['Stock_In_Grams'] - $info['amount'];
            $ingredientModel->update($ingredientId, ['Stock_In_Grams' => $newIngredientStock]);
            $ingredientHistoryModel->insert([
                'Ingredient_ID' => $ingredientId,
                'Account_ID' => $accountId,
                'Old_Stock' => $info['ingredient']['Stock_In_Grams'],
                'New_Stock' => $newIngredientStock,
            ]);
        }

        $newProductStock = (float) $product['Stock'] + $qtyToAdd;
        $productModel->update($id, ['Stock' => $newProductStock]);
        $historyModel->insert([
            'Product_ID' => $id,
            'Account_ID' => $accountId,
            'Old_Stock' => $product['Stock'],
            'New_Stock' => $newProductStock,
            'Remarks' => $this->withNote('Added ' . $this->formatGrams($qtyToAdd) . ' units (production)'),
        ]);

        $db->transComplete();

        session()->setFlashdata('msg', 'Added ' . $qtyToAdd . ' to stock.');
    }

    /**
     * Replace a product's recipe and log a human-readable diff (added,
     * removed, or changed ingredient quantities) to Product Audit, so
     * editing a recipe is never a silent change. Also snapshots the full
     * before/after ingredient lists as JSON, so Product Audit can show
     * "Old Recipe" -> "New Recipe" rather than just the diff text.
     */
    private function saveRecipe(
        ProductModel $productModel,
        ProductHistoryModel $historyModel,
        RecipeModel $recipeModel,
        int $accountId
    ): void {
        $id = (int) $this->request->getPost('product_id');
        $ingredientIds = $this->request->getPost('ingredient_id') ?? [];
        $qtys = $this->request->getPost('qty_per_unit') ?? [];

        $newMap = [];
        foreach ($ingredientIds as $i => $ingredientId) {
            $newMap[(int) $ingredientId] = (float) ($qtys[$i] ?? 0);
        }

        $product = $productModel->find($id);

        if (!$product) {
            session()->setFlashdata('error', 'Product not found.');

            return;
        }

        foreach ($newMap as $qty) {
            if ($qty < 0 || $qty > self::MAX_AMOUNT) {
                session()->setFlashdata('error', 'Each ingredient amount must be from 0 to 99,999,999.99 grams.');

                return;
            }
        }

        $oldMap = $recipeModel->forProduct($id);
        $recipeModel->replaceForProduct($id, $newMap);

        if ($product) {
            $allIds = array_unique(array_merge(array_keys($oldMap), array_keys($newMap)));
            $names = $allIds === [] ? [] : model(IngredientModel::class)->whereIn('Ingredient_ID', $allIds)->findAll();
            $nameById = array_column($names, 'Name', 'Ingredient_ID');

            $summary = $this->summarizeRecipeChanges($oldMap, $newMap, $nameById);

            if ($summary !== '') {
                $historyModel->insert([
                    'Product_ID' => $id,
                    'Account_ID' => $accountId,
                    'Old_Stock' => $product['Stock'],
                    'New_Stock' => $product['Stock'],
                    'Remarks' => substr('Recipe: ' . $summary, 0, 255),
                    'Old_Recipe' => json_encode($this->snapshotRecipe($oldMap, $nameById)),
                    'New_Recipe' => json_encode($this->snapshotRecipe($newMap, $nameById)),
                ]);
            }
        }

        session()->setFlashdata('msg', 'Recipe saved.');
    }

    /**
     * @param array<int, float>    $map      Ingredient_ID => Qty_Per_Unit
     * @param array<int, string>   $nameById Ingredient_ID => Name
     * @return list<array{name: string, qty: float}>
     */
    private function snapshotRecipe(array $map, array $nameById): array
    {
        $snapshot = [];
        foreach ($map as $ingredientId => $qty) {
            if ($qty > 0) {
                $snapshot[] = ['name' => $nameById[$ingredientId] ?? "#{$ingredientId}", 'qty' => $qty];
            }
        }

        return $snapshot;
    }

    /**
     * @param array<int, float>  $oldMap   Ingredient_ID => Qty_Per_Unit before the save
     * @param array<int, float>  $newMap   Ingredient_ID => Qty_Per_Unit after the save
     * @param array<int, string> $nameById Ingredient_ID => Name
     */
    private function summarizeRecipeChanges(array $oldMap, array $newMap, array $nameById): string
    {
        $ingredientIds = array_unique(array_merge(array_keys($oldMap), array_keys($newMap)));
        if ($ingredientIds === []) {
            return '';
        }

        $changes = [];
        foreach ($ingredientIds as $ingredientId) {
            $old = $oldMap[$ingredientId] ?? 0.0;
            $new = $newMap[$ingredientId] ?? 0.0;

            if (abs($old - $new) < 0.001) {
                continue;
            }

            $name = $nameById[$ingredientId] ?? "#{$ingredientId}";

            if ($old == 0.0) {
                $changes[] = "{$name} added ({$this->formatGrams($new)}g)";
            } elseif ($new == 0.0) {
                $changes[] = "{$name} removed";
            } else {
                $changes[] = "{$name} {$this->formatGrams($old)}g\u{2192}{$this->formatGrams($new)}g";
            }
        }

        return implode(', ', $changes);
    }

    private function formatGrams(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2), '0'), '.');
    }
}
