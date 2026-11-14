<?php
/**
 * Filter dropdown above a paginated list (Ingredient Audit, Product Audit,
 * Delivery Receipts). Expects $baseUrl (e.g. 'product-audit'), $categories
 * (url slug => label) and $current (active slug, or null for All).
 * Choosing an option goes to that filter's page - filters are route
 * segments (baseUrl/filter/slug), not a query string, since this app's
 * index.php?/... fallback routing can't reliably carry one.
 * Optional $suffix (e.g. '/branch/3') is kept on every option's URL, for a
 * second filter that must survive a change here. Optional $query (a list's
 * search text, even when empty) switches the options to the searchable
 * list addresses (baseUrl/browse/slug/q.../1), so a search is kept too.
 */
$suffix = $suffix ?? '';
$query = $query ?? null;
$optionUrl = static function (?string $slug) use ($baseUrl, $suffix, $query): string {
    if ($query !== null) {
        return list_url($baseUrl, $slug ?? 'all', $query, 1);
    }

    return site_url($baseUrl . ($slug === null ? '' : '/filter/' . $slug) . $suffix);
};
?>
<div class="filter-tabs">
    <label for="list-filter">Filter</label>
    <select id="list-filter" onchange="window.location.href = this.value">
        <option value="<?= esc($optionUrl(null)) ?>"<?= empty($current) ? ' selected' : '' ?>>All</option>
        <?php foreach ($categories as $slug => $label): ?>
            <option value="<?= esc($optionUrl($slug)) ?>"<?= ($current ?? null) === $slug ? ' selected' : '' ?>><?= esc($label) ?></option>
        <?php endforeach; ?>
    </select>
</div>
