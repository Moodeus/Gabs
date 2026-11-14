<?php

namespace App\Models;

use CodeIgniter\Model;

class OrdersUpdateHistoryModel extends Model
{
    protected $table = 'orders_update_history';
    protected $primaryKey = 'Orders_Update_History_ID';
    protected $allowedFields = ['Order_ID', 'Account_ID', 'Action_Date', 'Remarks'];
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $deletedField = 'Deleted_At';
}
