<?php
/**
 * Search box (plus optional filter links) for a searchable, paged list.
 * The search is a normal form: it posts to the page, which redirects to the
 * matching address, so the database does the searching - no script involved.
 *
 * Expects: $searchAction (the page's address), $placeholder, $query, $filter,
 *          $listBase (e.g. 'orders'), and optionally $filterOptions
 *          (value => label) for the filter links.
 */
$filterOptions = $filterOptions ?? [];
$active = $filter === '' ? 'all' : $filter;
?>
<form method="post" action="<?= site_url($searchAction) ?>" class="list-search">
    <input type="hidden" name="form_action" value="search">
    <input type="hidden" name="filter" value="<?= esc($active) ?>">
    <input type="search" name="q" value="<?= esc($query) ?>" placeholder="<?= esc($placeholder) ?>" aria-label="<?= esc($placeholder) ?>" maxlength="100">
    <button type="submit" class="quick-btn quick-btn-primary">Search</button>
    <?php if ($query !== ''): ?>
        <a class="quick-btn" href="<?= list_url($listBase, $active, '', 1) ?>">Clear</a>
    <?php endif; ?>
</form>

<?php if ($filterOptions !== []): ?>
    <div class="filter-pills" role="group" aria-label="Filter">
        <?php foreach ($filterOptions as $value => $label): ?>
            <a class="filter-pill<?= (string) $value === (string) $active ? ' active' : '' ?>" href="<?= list_url($listBase, (string) $value, $query, 1) ?>"><?= esc($label) ?></a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
