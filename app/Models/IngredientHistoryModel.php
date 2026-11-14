<?php

namespace App\Models;

use CodeIgniter\Model;

class IngredientHistoryModel extends Model
{
    protected $table = 'ingredient_history';
    protected $primaryKey = 'Ingredient_History_ID';
    protected $allowedFields = ['Ingredient_ID', 'Account_ID', 'Old_Stock', 'New_Stock', 'Action_Date', 'Remarks'];
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $deletedField = 'Deleted_At';

    public const CATEGORIES = [
        'created' => 'Created',
        'deleted' => 'Deleted',
        'edited' => 'Edited',
    ];

    /**
     * $category narrows the trail: Created/Deleted are the rows tagged that
     * way; Edited is every other stock change (Add, Minus, and production
     * deductions), which carry no remark - or only the optional note typed
     * into an Add/Minus modal. $search keeps only the rows whose ingredient
     * name contains that text (matched by the database).
     */
    public function recent(?int $limit = null, ?string $category = null, string $search = ''): array
    {
        $this->select('ingredient_history.*, i.Name AS ingredient_name, a.Name AS account_name')
            ->join('ingredients i', 'i.Ingredient_ID = ingredient_history.Ingredient_ID')
            ->join('accounts a', 'a.Account_ID = ingredient_history.Account_ID');

        match ($category) {
            'created' => $this->where('ingredient_history.Remarks', 'Created'),
            'deleted' => $this->where('ingredient_history.Remarks', 'Deleted'),
            'edited' => $this->where("(ingredient_history.Remarks IS NULL OR ingredient_history.Remarks NOT IN ('Created', 'Deleted'))", null, false),
            default => null,
        };

        if ($search !== '') {
            $this->where(like_contains($this->db, 'i.Name', $search), null, false);
        }

        $this->orderBy('Action_Date', 'DESC');

        if ($limit !== null) {
            $this->limit($limit);
        }

        return $this->find();
    }
}
