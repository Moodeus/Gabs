<?php

/**
 * Searchable, paged lists (Ingredient Inventory, Product Inventory, Orders,
 * Accounts). The search and filter live in the address so a page can be
 * bookmarked and Prev/Next keep them:
 *
 *   {base}/browse/{filter}/q{search}/{page}      e.g. orders/browse/Pending/qbXVmZmlu/2
 *
 * 'all' means no filter, and a bare 'q' means no search - the leading q keeps
 * an empty search from leaving a blank segment in the path. Segments, not a
 * query string, because this app's index.php?/... routing can't reliably
 * carry one.
 */

if (!function_exists('list_url')) {
    function list_url(string $base, string $filter, string $query, int $page = 1): string
    {
        // The search text is carried as URL-safe base64 (A-Z a-z 0-9 - _), so
        // any text - quotes, &, accents, slashes - survives the framework's
        // whitelist of characters allowed in a path.
        $token = rtrim(strtr(base64_encode(mb_substr(trim($query), 0, 100)), '+/', '-_'), '=');

        return site_url($base . '/browse/' . rawurlencode($filter === '' ? 'all' : $filter) . '/q' . $token . '/' . max(1, $page));
    }
}

if (!function_exists('list_query_from_segment')) {
    /** Turns the "q..." URL segment back into the search text. */
    function list_query_from_segment(string $segment): string
    {
        $decoded = base64_decode(strtr(substr($segment, 1), '-_', '+/'), true);

        return $decoded === false ? '' : trim(mb_substr($decoded, 0, 100));
    }
}

if (!function_exists('paged')) {
    /**
     * Works out the page window for a result set: the page number kept in
     * range, how many pages there are, and the row offset to start from.
     *
     * @return array{0: int, 1: int, 2: int} [page, totalPages, offset]
     */
    function paged(int $total, int $page, int $perPage = 25): array
    {
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($totalPages, max(1, $page));

        return [$page, $totalPages, ($page - 1) * $perPage];
    }
}

if (!function_exists('like_contains')) {
    /**
     * SQL for "this column contains this text", with % and _ in the text
     * matched literally (not as LIKE wildcards) and the text safely quoted.
     * $expression is trusted SQL written in the code, never user input.
     */
    function like_contains(\CodeIgniter\Database\BaseConnection $db, string $expression, string $text): string
    {
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $text);

        return $expression . ' LIKE ' . $db->escape('%' . $escaped . '%');
    }
}
