<?php

namespace App\Controllers;

use App\Models\ProductHistoryModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class ProductAudit extends BaseController
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
        return redirect()->to(list_url('product-audit', (string) $this->request->getPost('filter'), (string) $this->request->getPost('q'), 1));
    }

    /** product-audit/browse/{category|all}/q{search}/{page} */
    public function browse(string $filter, string $search, int $page)
    {
        $category = $filter === 'all' ? null : $this->validCategory($filter);

        return $this->render($category, list_query_from_segment($search), $page);
    }

    private function validCategory(string $category): string
    {
        if (!array_key_exists($category, ProductHistoryModel::CATEGORIES)) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $category;
    }

    private function render(?string $category, string $query, ?int $page)
    {
        $history = model(ProductHistoryModel::class)->recent(null, $category, $query);
        [$pageHistory, $page, $totalPages] = $this->paginateArray($history, $page);

        $content = view('product_audit/index', [
            'history' => $pageHistory,
            'page' => $page,
            'total_pages' => $totalPages,
            'category' => $category,
            'categories' => ProductHistoryModel::CATEGORIES,
            'query' => $query,
        ]);

        return view('layouts/main', [
            'content' => $content,
            'page_title' => "Product Audit - Gab's Bakeshop",
            'page_css' => 'producthistory.css',
            'nav_role' => session()->get('role'),
            'nav_current_page' => 'product-audit',
        ]);
    }
}
