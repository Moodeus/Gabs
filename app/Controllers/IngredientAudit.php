<?php

namespace App\Controllers;

use App\Models\IngredientHistoryModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class IngredientAudit extends BaseController
{
    public function index(?int $page = null)
    {
        return $this->render(null, '', $page);
    }

    public function filtered(string $category, ?int $page = null)
    {
        return $this->render($this->validCategory($category), '', $page);
    }

    /** The search box posts here; the page it redirects to does the searching. */
    public function search()
    {
        return redirect()->to(list_url('ingredient-audit', (string) $this->request->getPost('filter'), (string) $this->request->getPost('q'), 1));
    }

    /** ingredient-audit/browse/{category|all}/q{search}/{page} */
    public function browse(string $filter, string $search, int $page)
    {
        $category = $filter === 'all' ? null : $this->validCategory($filter);

        return $this->render($category, list_query_from_segment($search), $page);
    }

    private function validCategory(string $category): string
    {
        if (!array_key_exists($category, IngredientHistoryModel::CATEGORIES)) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $category;
    }

    private function render(?string $category, string $query, ?int $page)
    {
        $history = model(IngredientHistoryModel::class)->recent(null, $category, $query);
        [$pageHistory, $page, $totalPages] = $this->paginateArray($history, $page);

        $content = view('ingredient_audit/index', [
            'history' => $pageHistory,
            'page' => $page,
            'total_pages' => $totalPages,
            'category' => $category,
            'categories' => IngredientHistoryModel::CATEGORIES,
            'query' => $query,
        ]);

        return view('layouts/main', [
            'content' => $content,
            'page_title' => "Ingredient Audit - Gab's Bakeshop",
            'page_css' => 'ingredienthistory.css',
            'nav_role' => session()->get('role'),
            'nav_current_page' => 'ingredient-audit',
        ]);
    }
}
