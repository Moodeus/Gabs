<?php

namespace App\Controllers;

use App\Models\IngredientHistoryModel;
use App\Models\IngredientModel;
use App\Models\RecipeModel;

class IngredientInventory extends BaseController
{
    /** The optional note from an Add/Minus modal, or null when left blank. */
    private function note(): ?string
    {
        $note = trim((string) $this->request->getPost('note'));

        return $note === '' ? null : mb_substr($note, 0, 255);
    }

    public function index()
    {
        $ingredientModel = model(IngredientModel::class);
        $historyModel = model(IngredientHistoryModel::class);
        $accountId = (int) session()->get('user_ID');

        if ($this->request->is('post')) {
            $action = (string) $this->request->getPost('form_action');

            if ($action === 'search') {
                return redirect()->to(list_url('ingredient-inventory', (string) $this->request->getPost('filter'), (string) $this->request->getPost('q'), 1));
            }

            // Super Admin can switch each of these off per role (Permissions page).
            $permissionFor = [
                'create' => 'inventory.add_item',
                'add_stock' => 'inventory.adjust_stock',
                'minus_stock' => 'inventory.adjust_stock',
                'edit_settings' => 'inventory.edit_settings',
                'delete' => 'inventory.delete',
            ][$action] ?? null;

            if ($permissionFor !== null && !can($permissionFor)) {
                session()->setFlashdata('error', 'You are not allowed to do that.');

                return redirect()->to('ingredient-inventory');
            }

            if ($action === 'create') {
                $name = trim((string) $this->request->getPost('name'));
                if ($name === '') {
                    session()->setFlashdata('error', 'Ingredient name is required.');

                    return redirect()->to('ingredient-inventory')->withInput();
                } else {
                    $startingStock = $this->postedNumber('stock_in_grams', 0.0, 0.0);
                    $unitToGrams = $this->postedNumber('unit_to_grams', 0.01);
                    $threshold = $this->postedNumber('low_stock_threshold', 0.0, 5.0);
                    $unit = trim((string) $this->request->getPost('stock_unit'));

                    if ($startingStock === false || !is_float($unitToGrams) || $threshold === false) {
                        session()->setFlashdata('error', 'Check the numbers: stock and the low stock warning cannot be negative, and the unit conversion must be above 0 (all up to 99,999,999.99).');

                        return redirect()->to('ingredient-inventory')->withInput();
                    }

                    if (mb_strlen($name) > 100 || mb_strlen($unit) > 20) {
                        session()->setFlashdata('error', 'The name can be up to 100 characters and the unit up to 20.');

                        return redirect()->to('ingredient-inventory')->withInput();
                    }

                    $type = (string) $this->request->getPost('type');
                    $newId = $ingredientModel->insert([
                        'Name' => $name,
                        'Stock_Unit' => $unit,
                        'Unit_To_Grams' => $unitToGrams,
                        'Stock_In_Grams' => $startingStock,
                        'Low_Stock_Threshold' => $threshold,
                        'Type' => in_array($type, ['Dry', 'Wet'], true) ? $type : 'Dry',
                    ], true);
                    $historyModel->insert([
                        'Ingredient_ID' => $newId,
                        'Account_ID' => $accountId,
                        'Old_Stock' => 0,
                        'New_Stock' => $startingStock,
                        'Remarks' => 'Created',
                    ]);
                    session()->setFlashdata('msg', 'Ingredient added.');
                }
            } elseif ($action === 'add_stock') {
                $id = (int) $this->request->getPost('ingredient_id');
                $qty = $this->postedNumber('qty', 0.01);
                $ingredient = $ingredientModel->find($id);

                if ($ingredient && is_float($qty) && (float) $ingredient['Stock_In_Grams'] + $qty > self::MAX_AMOUNT) {
                    session()->setFlashdata('error', 'That would take the stock above 99,999,999.99g.');
                } elseif ($ingredient && is_float($qty)) {
                    $newStock = (float) $ingredient['Stock_In_Grams'] + $qty;
                    $ingredientModel->update($id, ['Stock_In_Grams' => $newStock]);
                    $historyModel->insert([
                        'Ingredient_ID' => $id,
                        'Account_ID' => $accountId,
                        'Old_Stock' => $ingredient['Stock_In_Grams'],
                        'New_Stock' => $newStock,
                        'Remarks' => $this->note(),
                    ]);
                    session()->setFlashdata('msg', 'Added ' . number_format($qty, 2) . 'g to stock.');
                } else {
                    session()->setFlashdata('error', 'Enter a valid quantity to add.');
                }
            } elseif ($action === 'minus_stock') {
                $id = (int) $this->request->getPost('ingredient_id');
                $qty = $this->postedNumber('qty', 0.01);
                $ingredient = $ingredientModel->find($id);

                if (!$ingredient || !is_float($qty)) {
                    session()->setFlashdata('error', 'Enter a valid quantity to remove.');
                } elseif ($qty > (float) $ingredient['Stock_In_Grams']) {
                    session()->setFlashdata('error', 'Cannot remove more than the current stock.');
                } else {
                    $newStock = (float) $ingredient['Stock_In_Grams'] - $qty;
                    $ingredientModel->update($id, ['Stock_In_Grams' => $newStock]);
                    $historyModel->insert([
                        'Ingredient_ID' => $id,
                        'Account_ID' => $accountId,
                        'Old_Stock' => $ingredient['Stock_In_Grams'],
                        'New_Stock' => $newStock,
                        'Remarks' => $this->note(),
                    ]);
                    session()->setFlashdata('msg', 'Removed ' . number_format($qty, 2) . 'g from stock.');
                }
            } elseif ($action === 'edit_settings') {
                $id = (int) $this->request->getPost('ingredient_id');
                $threshold = $this->postedNumber('low_stock_threshold', 0.0);
                $type = (string) $this->request->getPost('type');
                $ingredient = $ingredientModel->find($id);

                if ($ingredient && !is_float($threshold)) {
                    session()->setFlashdata('error', 'The low stock warning must be a number from 0 to 99,999,999.99.');
                } elseif ($ingredient) {
                    $ingredientModel->update($id, [
                        'Low_Stock_Threshold' => $threshold,
                        'Type' => in_array($type, ['Dry', 'Wet'], true) ? $type : $ingredient['Type'],
                    ]);
                    session()->setFlashdata('msg', 'Settings updated.');
                }
            } elseif ($action === 'delete') {
                $id = (int) $this->request->getPost('ingredient_id');
                $ingredient = $ingredientModel->find($id);

                if ($ingredient) {
                    $ingredientModel->delete($id);
                    $historyModel->insert([
                        'Ingredient_ID' => $id,
                        'Account_ID' => $accountId,
                        'Old_Stock' => $ingredient['Stock_In_Grams'],
                        'New_Stock' => $ingredient['Stock_In_Grams'],
                        'Remarks' => 'Deleted',
                    ]);
                    session()->setFlashdata('msg', 'Ingredient removed.');
                }
            }

            return redirect()->to('ingredient-inventory');
        }

        return $this->render('all', '', 1);
    }

    /** ingredient-inventory/browse/{type}/q{search}/{page} */
    public function browse(string $type, string $search, int $page)
    {
        return $this->render($type, list_query_from_segment($search), $page);
    }

    private function render(string $type, string $query, int $page)
    {
        $type = in_array($type, ['Dry', 'Wet'], true) ? $type : 'all';
        [$ingredients, $total, $page, $totalPages] = model(IngredientModel::class)->browse($type, $query, $page);

        $content = view('ingredient_inventory/index', [
            'ingredients' => $ingredients,
            'used_in' => model(RecipeModel::class)->productNamesByIngredient(),
            'type' => $type,
            'query' => $query,
            'page' => $page,
            'total_pages' => $totalPages,
            'total' => $total,
            'msg' => session()->getFlashdata('msg'),
            'error' => session()->getFlashdata('error'),
        ]);

        return view('layouts/main', [
            'content' => $content,
            'page_title' => "Ingredient Inventory - Gab's Bakeshop",
            'page_css' => 'inventory.css',
            'nav_role' => session()->get('role'),
            'nav_current_page' => 'ingredient-inventory',
        ]);
    }

    /**
     * Standalone printable list (no layouts/main chrome), the same pattern
     * as the order invoice - a "Print" button triggers the browser's own
     * print dialog, which can save to PDF without needing a PDF library.
     */
    public function export()
    {
        return view('ingredient_inventory/export', [
            'ingredients' => model(IngredientModel::class)->orderBy('Name')->findAll(),
        ]);
    }
}
