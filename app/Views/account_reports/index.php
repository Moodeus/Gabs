<div class="page-content">
    <div class="dashboard-content">
        <h1>Account Reports</h1>

        <div class="dashboard-section">
            <div class="recent-table-wrap">
                <table class="recent-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Action</th>
                            <th>Account</th>
                            <th>By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $row): ?>
                            <tr>
                                <td><?= esc(date('M j, Y g:i A', strtotime($row['Action_Date']))) ?></td>
                                <td><?= esc($row['Remarks'] ?? '') ?></td>
                                <td><?= esc($row['target_name']) ?></td>
                                <td><?= esc($row['account_name']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($history)): ?>
                            <tr><td colspan="4">No account activity yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?= view('layouts/pagination', ['page' => $page, 'totalPages' => $total_pages, 'baseUrl' => 'account-reports']) ?>
        </div>
    </div>
</div>
