<?php

namespace App\Models;

use CodeIgniter\Model;

class RecipeModel extends Model
{
    protected $table = 'recipe';
    protected $primaryKey = 'Recipe_ID';
    protected $allowedFields = ['Ingredient_ID', 'Product_ID', 'Qty_Per_Unit'];
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $deletedField = 'Deleted_At';

    /**
     * @return array<int, float> Ingredient_ID => Qty_Per_Unit
     */
    public function forProduct(int $productId): array
    {
        $rows = $this->where('Product_ID', $productId)->find();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['Ingredient_ID']] = (float) $row['Qty_Per_Unit'];
        }

        return $map;
    }

    /**
     * Names of the (live) products whose recipe uses each ingredient.
     *
     * @return array<int, list<string>> Ingredient_ID => product names
     */
    public function productNamesByIngredient(): array
    {
        $rows = $this->db->table('recipe r')
            ->select('r.Ingredient_ID, p.Name')
            ->join('product p', 'p.Product_ID = r.Product_ID')
            ->where('r.Deleted_At', null)
            ->where('p.Deleted_At', null)
            ->orderBy('p.Name')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['Ingredient_ID']][] = $row['Name'];
        }

        return $map;
    }

    /**
     * Replace a product's whole recipe in one go.
     *
     * @param array<int, float> $ingredientQtyMap Ingredient_ID => Qty_Per_Unit (grams); 0 or missing means "not in the recipe".
     */
    public function replaceForProduct(int $productId, array $ingredientQtyMap): void
    {
        $this->where('Product_ID', $productId)->delete();

        $rows = [];
        foreach ($ingredientQtyMap as $ingredientId => $qty) {
            if ($qty > 0) {
                $rows[] = [
                    'Product_ID' => $productId,
                    'Ingredient_ID' => $ingredientId,
                    'Qty_Per_Unit' => $qty,
                ];
            }
        }

        if ($rows !== []) {
            $this->insertBatch($rows);
        }
    }
}
