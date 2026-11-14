<?php

namespace App\Models;

use CodeIgniter\Model;

class AccountHistoryModel extends Model
{
    protected $table = 'account_history';
    protected $primaryKey = 'Account_History_ID';
    protected $allowedFields = ['Target_Account_ID', 'Account_ID', 'Action_Date', 'Remarks'];
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $deletedField = 'Deleted_At';

    public function recent(?int $limit = null): array
    {
        $this->select('account_history.*, t.Name AS target_name, a.Name AS account_name')
            ->join('accounts t', 't.Account_ID = account_history.Target_Account_ID')
            ->join('accounts a', 'a.Account_ID = account_history.Account_ID')
            ->orderBy('Action_Date', 'DESC');

        if ($limit !== null) {
            $this->limit($limit);
        }

        return $this->find();
    }
}
