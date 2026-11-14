<div class="page-content">
    <div class="dashboard-content">
        <h1>Ingredient Audit</h1>

        <div class="dashboard-section">
            <?= view('layouts/list_controls', [
                'searchAction' => 'ingredient-audit',
                'listBase' => 'ingredient-audit',
                'placeholder' => 'Search by ingredient name…',
                'query' => $query,
                'filter' => $category ?? 'all',
            ]) ?>
            <?= view('layouts/filter_tabs', ['baseUrl' => 'ingredient-audit', 'categories' => $categories, 'current' => $category, 'query' => $query]) ?>
            <div class="recent-table-wrap">
                <table class="recent-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Ingredient</th>
                            <th>Old Stock (g)</th>
                            <th>New Stock (g)</th>
                            <th>Changed By</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $row): ?>
                            <tr>
                                <td><?= esc(date('M j, Y g:i A', strtotime($row['Action_Date']))) ?></td>
                                <td><?= esc($row['ingredient_name']) ?></td>
                                <td><?= esc(number_format((float) $row['Old_Stock'], 2)) ?></td>
                                <td><?= esc(number_format((float) $row['New_Stock'], 2)) ?></td>
                                <td><?= esc($row['account_name']) ?></td>
                                <td>
                                    <?php if ($row['Remarks'] === 'Deleted'): ?>
                                        <span class="audit-remark-deleted">Deleted</span>
                                    <?php elseif ($row['Remarks'] === 'Created'): ?>
                                        <span class="audit-remark-created">Created</span>
                                    <?php else: ?>
                                        <?= esc($row['Remarks'] ?? '') ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($history)): ?>
                            <tr><td colspan="6"><?= ($category === null && $query === '') ? 'No history yet.' : 'No matching history.' ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?= view('layouts/pagination', [
                'page' => $page,
                'totalPages' => $total_pages,
                'urlFor' => static fn (int $n): string => list_url('ingredient-audit', $category ?? 'all', $query, $n),
            ]) ?>
        </div>
    </div>
</div>
