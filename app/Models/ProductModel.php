<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table = 'product';
    protected $primaryKey = 'Product_ID';
    protected $allowedFields = ['Name', 'Stock', 'Cost', 'Price', 'Low_Stock_Threshold'];
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $deletedField = 'Deleted_At';

    /**
     * One page of the product list, filtered by a name search, with the
     * total so the page count can be worked out.
     *
     * @return array{0: list<array<string, mixed>>, 1: int, 2: int, 3: int} rows, total, page, totalPages
     */
    public function browse(string $query, int $page, int $perPage = 25): array
    {
        $narrow = function () use ($query): void {
            if ($query !== '') {
                $this->where(like_contains($this->db, 'Name', $query), null, false);
            }
        };

        $narrow();
        $total = $this->countAllResults();
        [$page, $totalPages, $offset] = paged($total, $page, $perPage);

        $narrow();
        $rows = $this->orderBy('Name')->limit($perPage, $offset)->find();

        return [$rows, $total, $page, $totalPages];
    }
}
