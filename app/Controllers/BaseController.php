<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        $this->helpers = ['url', 'form'];
        parent::initController($request, $response, $logger);
    }

    /** The largest amount the database's decimal(10,2) columns can hold. */
    protected const MAX_AMOUNT = 99999999.99;

    /**
     * A posted number within $min..MAX_AMOUNT. Returns $default when the field
     * was left blank (null if there is no default), and false when it was
     * filled in with something that isn't a number or is out of range - so a
     * bad value is refused instead of being silently clamped by the database.
     */
    protected function postedNumber(string $field, float $min = 0.0, ?float $default = null): float|false|null
    {
        $raw = trim((string) $this->request->getPost($field));

        if ($raw === '') {
            return $default;
        }

        if (!is_numeric($raw)) {
            return false;
        }

        $value = (float) $raw;

        return $value >= $min && $value <= self::MAX_AMOUNT ? $value : false;
    }

    /**
     * Slices an already-fetched result set into one page. Pagination is
     * done in PHP (not the DB query) since every caller so far already
     * fetches its full, capped result set in one go - this just avoids
     * re-plumbing each model for LIMIT/OFFSET.
     *
     * @param list<array<string, mixed>> $items
     * @return array{0: list<array<string, mixed>>, 1: int, 2: int} [pageItems, currentPage, totalPages]
     */
    protected function paginateArray(array $items, ?int $page, int $perPage = 25): array
    {
        $totalPages = max(1, (int) ceil(count($items) / $perPage));
        $page = min($totalPages, max(1, (int) ($page ?? 1)));

        return [array_slice($items, ($page - 1) * $perPage, $perPage), $page, $totalPages];
    }
}
