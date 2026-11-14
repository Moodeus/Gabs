<?php

namespace App\Models;

use CodeIgniter\Model;

class IngredientModel extends Model
{
    protected $table = 'ingredients';
    protected $primaryKey = 'Ingredient_ID';
    protected $allowedFields = ['Name', 'Stock_Unit', 'Unit_To_Grams', 'Stock_In_Grams', 'Low_Stock_Threshold', 'Type'];
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $deletedField = 'Deleted_At';

    /**
     * One page of the ingredient list, filtered by type (Dry/Wet) and a name
     * search, with the total so the page count can be worked out.
     *
     * @return array{0: list<array<string, mixed>>, 1: int, 2: int, 3: int} rows, total, page, totalPages
     */
    public function browse(string $type, string $query, int $page, int $perPage = 25): array
    {
        $narrow = function () use ($type, $query): void {
            if (in_array($type, ['Dry', 'Wet'], true)) {
                $this->where('Type', $type);
            }
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
