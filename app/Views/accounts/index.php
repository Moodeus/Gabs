<div class="page-content">
    <div class="dashboard-content">
        <div class="page-header-row">
            <h1>Accounts</h1>
            <?php if (can('accounts.create')): ?>
                <button type="button" class="quick-btn quick-btn-primary" data-modal-open="add-account-modal">Add Account</button>
            <?php endif; ?>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="message success"><?= esc($msg) ?></div>
        <?php endif; ?>
        <?php
        // Add Account / Edit Account reopen after an error and show the message inside themselves.
        $reopen = !empty($error) && old('account_id') === '0';
        $editShown = !empty($error) && in_array((string) old('account_id'), array_map('strval', array_column($accounts, 'Account_ID')), true);
        ?>
        <?php if (!empty($error) && !$reopen && !$editShown): ?>
            <div class="message error"><?= esc($error) ?></div>
        <?php endif; ?>

        <?php $showNumber = column_ready('accounts', 'Account_Phone'); ?>
        <div class="dashboard-section">
            <h2>All Accounts</h2>
            <?= view('layouts/list_controls', [
                'searchAction' => 'accounts',
                'listBase' => 'accounts',
                'placeholder' => 'Search accounts…',
                'query' => $query,
                'filter' => 'all',
            ]) ?>
            <div class="recent-table-wrap">
                <table class="recent-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <?php if ($showNumber): ?><th>Number</th><?php endif; ?>
                            <th>Role</th>
                            <th>Outstanding Balance</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($accounts as $acc): ?>
                            <tr>
                                <td><?= esc($acc['Name']) ?></td>
                                <td><?= esc($acc['Username']) ?></td>
                                <td><?= esc($acc['Email']) ?></td>
                                <?php if ($showNumber): ?><td><?= !empty($acc['Account_Phone']) ? esc($acc['Account_Phone']) : '—' ?></td><?php endif; ?>
                                <td><span class="status-pill"><?= esc($acc['Role']) ?></span></td>
                                <td>₱<?= esc(number_format((float) $acc['Outstanding_Balance'], 2)) ?></td>
                                <td>
                                    <?php if (can('accounts.edit')): ?>
                                        <button type="button" class="quick-btn" data-modal-open="edit-account-modal-<?= esc($acc['Account_ID']) ?>">Edit</button>
                                    <?php endif; ?>
                                    <?php if ((int) $acc['Account_ID'] !== $current_account_id && can('accounts.deactivate')): ?>
                                        <button type="button" class="quick-btn" data-modal-open="deactivate-modal-<?= esc($acc['Account_ID']) ?>">Deactivate</button>
                                    <?php endif; ?>

                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($accounts)): ?>
                            <tr><td colspan="<?= $showNumber ? 7 : 6 ?>"><?= $query !== '' ? 'No accounts match.' : 'No accounts yet.' ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?= view('layouts/pagination', [
                'page' => $page,
                'totalPages' => $total_pages,
                'urlFor' => static fn (int $n): string => list_url('accounts', 'all', $query, $n),
            ]) ?>
        </div>
    </div>
</div>

<dialog id="add-account-modal" class="modal" <?= $reopen ? 'open' : '' ?>>
    <div class="modal-header">
        <h2>Add Account</h2>
        <button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button>
    </div>
    <form method="post" action="<?= site_url('accounts') ?>">
        <?php if ($reopen): ?>
            <div class="message error"><?= esc($error) ?></div>
        <?php endif; ?>
        <input type="hidden" name="account_id" value="0">
        <label>Full Name <input type="text" name="name" value="<?= old('name') ?>" required></label>
        <label>Email <input type="email" name="email" value="<?= old('email') ?>" pattern="[^@\s]+@[^@\s]+\.[^@\s]+" title="Enter an email like name@example.com" required></label>
        <label>Username <input type="text" name="username" value="<?= old('username') ?>" required></label>
        <label>Role
            <select name="role" required>
                <?php foreach ($roles as $r): ?>
                    <option value="<?= esc($r) ?>" <?= old('role') === $r ? 'selected' : '' ?>><?= esc($r) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Password <input type="password" name="password" autocomplete="new-password" required></label>
        <div class="modal-actions">
            <button type="button" class="quick-btn" data-modal-close>Cancel</button>
            <button type="submit" class="quick-btn quick-btn-primary">Add Account</button>
        </div>
    </form>
</dialog>

<?php foreach ($accounts as $acc): ?>
    <?php $isReopen = !empty($error) && old('account_id') === (string) $acc['Account_ID']; ?>
    <dialog id="edit-account-modal-<?= esc($acc['Account_ID']) ?>" class="modal" <?= $isReopen ? 'open' : '' ?>>
        <div class="modal-header">
            <h2>Edit Account: <?= esc($acc['Name']) ?></h2>
            <button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button>
        </div>
        <form method="post" action="<?= site_url('accounts') ?>" id="edit-account-form-<?= esc($acc['Account_ID']) ?>" data-confirm-dialog="confirm-edit-modal-<?= esc($acc['Account_ID']) ?>">
            <?php if ($isReopen): ?>
                <div class="message error"><?= esc($error) ?></div>
            <?php endif; ?>
            <input type="hidden" name="account_id" value="<?= esc($acc['Account_ID']) ?>">
            <label>Full Name <input type="text" name="name" value="<?= esc($isReopen ? old('name', '', false) : $acc['Name']) ?>" required></label>
            <label>Email <input type="email" name="email" value="<?= esc($isReopen ? old('email', '', false) : $acc['Email']) ?>" pattern="[^@\s]+@[^@\s]+\.[^@\s]+" title="Enter an email like name@example.com" required></label>
            <label>Username <input type="text" name="username" value="<?= esc($isReopen ? old('username', '', false) : $acc['Username']) ?>" required></label>
            <label>Role
                <?php $currentRole = $isReopen ? old('role', '', false) : $acc['Role']; ?>
                <select name="role" required>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= esc($r) ?>" <?= $currentRole === $r ? 'selected' : '' ?>><?= esc($r) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>New Password <small>(leave blank to keep the current password)</small>
                <input type="password" name="password" autocomplete="new-password">
            </label>
            <div class="modal-actions">
                <button type="button" class="quick-btn" data-modal-close>Cancel</button>
                <button type="submit" class="quick-btn quick-btn-primary">Save</button>
            </div>
        </form>
    </dialog>

    <dialog id="confirm-edit-modal-<?= esc($acc['Account_ID']) ?>" class="modal">
        <div class="modal-header">
            <h2>Save Changes</h2>
            <button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button>
        </div>
        <div class="order-detail-body">
            <p>Are you sure you want to save changes to <strong><?= esc($acc['Name']) ?></strong> (<?= esc($acc['Username']) ?>)?</p>
            <div class="modal-actions">
                <button type="button" class="quick-btn" data-modal-close>Cancel</button>
                <button type="button" class="quick-btn quick-btn-primary" data-confirm-submit="edit-account-form-<?= esc($acc['Account_ID']) ?>">Yes, save changes</button>
            </div>
        </div>
    </dialog>

    <?php if ((int) $acc['Account_ID'] !== $current_account_id): ?>
        <dialog id="deactivate-modal-<?= esc($acc['Account_ID']) ?>" class="modal">
            <div class="modal-header">
                <h2>Deactivate Account</h2>
                <button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button>
            </div>
            <form method="post" action="<?= site_url('accounts') ?>">
                <input type="hidden" name="form_action" value="deactivate">
                <input type="hidden" name="account_id" value="<?= esc($acc['Account_ID']) ?>">
                <p>Are you sure you want to deactivate <strong><?= esc($acc['Name']) ?></strong> (<?= esc($acc['Username']) ?>)?</p>
                <p class="role-hint">They will no longer be able to log in. Nothing is permanently deleted.</p>
                <div class="modal-actions">
                    <button type="button" class="quick-btn" data-modal-close>Cancel</button>
                    <button type="submit" class="quick-btn quick-btn-minus">Yes, deactivate</button>
                </div>
            </form>
        </dialog>
    <?php endif; ?>
<?php endforeach; ?>

<script>
    // Saving an account edit asks for confirmation first: the edit form's
    // submit is held, and only "Yes, save changes" actually sends it.
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[data-confirm-dialog]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                document.getElementById(form.getAttribute('data-confirm-dialog')).showModal();
            });
        });

        document.querySelectorAll('[data-confirm-submit]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById(btn.getAttribute('data-confirm-submit')).submit();
            });
        });
    });
</script>
