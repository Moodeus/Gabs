<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductHistoryModel extends Model
{
    protected $table = 'product_history';
    protected $primaryKey = 'Product_History_ID';
    protected $allowedFields = ['Product_ID', 'Account_ID', 'Old_Stock', 'New_Stock', 'Action_Date', 'Remarks', 'Old_Recipe', 'New_Recipe'];
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $deletedField = 'Deleted_At';

    public const CATEGORIES = [
        'created' => 'Created',
        'deleted' => 'Deleted',
        'added' => 'Added',
        'deducted' => 'Deducted',
        'order-approved' => 'Order Approved',
        'recipe' => 'Recipe Changes',
    ];

    /**
     * $category narrows the trail. Added/Deducted match both the newer
     * rows that say so in their remark and older stock changes that have
     * no remark, told apart by whether the stock went up or down. $search
     * keeps only the rows whose product name contains that text (matched by
     * the database).
     */
    public function recent(?int $limit = null, ?string $category = null, string $search = ''): array
    {
        $this->select('product_history.*, p.Name AS product_name, a.Name AS account_name')
            ->join('product p', 'p.Product_ID = product_history.Product_ID')
            ->join('accounts a', 'a.Account_ID = product_history.Account_ID');

        match ($category) {
            'created' => $this->where('product_history.Remarks', 'Created'),
            'deleted' => $this->where('product_history.Remarks', 'Deleted'),
            'recipe' => $this->like('product_history.Remarks', 'Recipe: ', 'after'),
            'order-approved' => $this->like('product_history.Remarks', 'Order #', 'after'),
            'added' => $this->where(
                "(product_history.Remarks LIKE 'Added%' OR (product_history.Remarks IS NULL AND product_history.New_Stock > product_history.Old_Stock))",
                null,
                false
            ),
            'deducted' => $this->where(
                "(product_history.Remarks LIKE 'Deducted%' OR (product_history.Remarks IS NULL AND product_history.New_Stock < product_history.Old_Stock))",
                null,
                false
            ),
            default => null,
        };

        if ($search !== '') {
            $this->where(like_contains($this->db, 'p.Name', $search), null, false);
        }

        $this->orderBy('Action_Date', 'DESC');

        if ($limit !== null) {
            $this->limit($limit);
        }

        return $this->find();
    }
}
