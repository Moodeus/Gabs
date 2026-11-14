<?php

namespace App\Models;

use CodeIgniter\Model;

class AccountModel extends Model
{
    protected $table = 'accounts';
    protected $primaryKey = 'Account_ID';
    protected $allowedFields = ['Name', 'Email', 'Account_Phone', 'Username', 'Password', 'Role', 'Outstanding_Balance'];
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $deletedField = 'Deleted_At';

    /**
     * The OR has to be grouped - otherwise the soft-delete filter that
     * first() appends (`... AND Deleted_At IS NULL`) only binds to the
     * Email branch, and a deactivated account can still be found by
     * Username thanks to SQL's AND-before-OR precedence.
     */
    public function findByUsernameOrEmail(string $value): ?array
    {
        return $this->groupStart()
                ->where('Username', $value)
                ->orWhere('Email', $value)
            ->groupEnd()
            ->first();
    }

    /**
     * One page of the account list, searched across name, username, email,
     * role and (when the database has it) number. $hideSuperAdmin leaves
     * Super Admin accounts out for viewers who may not see them.
     *
     * @return array{0: list<array<string, mixed>>, 1: int, 2: int, 3: int} rows, total, page, totalPages
     */
    public function browse(string $query, bool $hideSuperAdmin, int $page, int $perPage = 25): array
    {
        $columns = ['Name', 'Username', 'Email', 'Role'];
        if (column_ready('accounts', 'Account_Phone')) {
            $columns[] = 'Account_Phone';
        }

        $narrow = function () use ($query, $hideSuperAdmin, $columns): void {
            if ($hideSuperAdmin) {
                $this->where('Role !=', 'Super Admin');
            }
            if ($query !== '') {
                $matches = array_map(fn (string $column): string => like_contains($this->db, 'accounts.' . $column, $query), $columns);
                $this->where('(' . implode(' OR ', $matches) . ')', null, false);
            }
        };

        $narrow();
        $total = $this->countAllResults();
        [$page, $totalPages, $offset] = paged($total, $page, $perPage);

        $narrow();
        $rows = $this->orderBy('Name')->limit($perPage, $offset)->find();

        return [$rows, $total, $page, $totalPages];
    }

    /** What is wrong with a new password, or null when it is acceptable. */
    public static function passwordProblem(string $password): ?string
    {
        if (trim($password) === '') {
            return 'Enter a password.';
        }

        if (mb_strlen($password) < 8) {
            return 'The password must be at least 8 characters.';
        }

        return null;
    }

    /**
     * What is wrong with an email address, or null when it is acceptable: it
     * has to look like an address, and no other active account may use it
     * (login accepts the email too, so two accounts sharing one is ambiguous).
     */
    public function emailProblem(string $email, int $exceptAccountId = 0): ?string
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            return 'Enter a valid email address.';
        }

        $taken = $this->where('Email', $email)->where('Account_ID !=', $exceptAccountId)->first();

        return $taken ? 'That email is already in use by an active account.' : null;
    }

    public function getRoleName(int $accountId): ?string
    {
        $row = $this->select('Role')->find($accountId);

        return $row['Role'] ?? null;
    }
}
