<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderLineItemModel extends Model
{
    protected $table = 'order_line_item';
    protected $primaryKey = 'Order_Line_Item_ID';
    protected $allowedFields = ['Order_ID', 'Product_ID', 'Name', 'Qty', 'Details', 'Price', 'Cost'];
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $deletedField = 'Deleted_At';

    public function forOrder(int $orderId): array
    {
        return $this->where('Order_ID', $orderId)->find();
    }

    /**
     * Items Admin denied from a batch. A denied item is soft-deleted, so
     * forOrder() (and every stock, total, invoice and sales query that skips
     * deleted rows) ignores it automatically; nothing else deletes line items.
     */
    public function deniedForOrder(int $orderId): array
    {
        return $this->onlyDeleted()->where('Order_ID', $orderId)->findAll();
    }
}
