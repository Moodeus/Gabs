<?php
/**
 * Prev/Next page nav, reused across Sales Report, Ingredient Audit,
 * Product Audit, Delivery Receipts and the searchable lists. Expects $page,
 * $totalPages, and either $baseUrl (the route path without a page number,
 * e.g. 'sales-report') or $urlFor, a function from a page number to its full
 * URL (used by lists that also carry a search and filter).
 * Links use a route segment (baseUrl/page/N), not a query string - this
 * app's index.php?/... fallback routing can't reliably carry one.
 */
$pageUrl = $urlFor ?? static fn (int $n): string => site_url($baseUrl . '/page/' . $n);
?>
<?php if ($totalPages > 1): ?>
    <nav class="pagination">
        <a class="quick-btn<?= $page <= 1 ? ' quick-btn-disabled' : '' ?>" href="<?= $pageUrl(max(1, $page - 1)) ?>">&laquo; Prev</a>
        <span class="pagination-status">Page <?= esc($page) ?> of <?= esc($totalPages) ?></span>
        <a class="quick-btn<?= $page >= $totalPages ? ' quick-btn-disabled' : '' ?>" href="<?= $pageUrl(min($totalPages, $page + 1)) ?>">Next &raquo;</a>
    </nav>
<?php endif; ?>
