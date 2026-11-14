<div class="page-content">
    <div class="dashboard-content">
        <h1>Settings</h1>

        <?php if (!empty($msg)): ?>
            <div class="message success"><?= esc($msg) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="message error"><?= esc($error) ?></div>
        <?php endif; ?>

        <div class="settings-grid">
            <section class="settings-card">
                <h2>Profile</h2>
                <dl class="settings-facts">
                    <div><dt>Username</dt><dd><?= esc($account['Username'] ?? '') ?></dd></div>
                    <div><dt>Role</dt><dd><?= esc($account['Role'] ?? '') ?></dd></div>
                </dl>
                <form method="post" action="<?= site_url('settings') ?>">
                    <input type="hidden" name="form_action" value="profile">
                    <label>Full Name
                        <input type="text" name="name" value="<?= esc($account['Name'] ?? '') ?>" required>
                    </label>
                    <label>Email
                        <input type="email" name="email" value="<?= esc($account['Email'] ?? '') ?>" pattern="[^@\s]+@[^@\s]+\.[^@\s]+" title="Enter an email like name@example.com" required>
                    </label>
                    <?php if (column_ready('accounts', 'Account_Phone')): ?>
                        <label><span>Number <small>(optional)</small></span>
                            <input type="tel" name="phone" value="<?= esc($account['Account_Phone'] ?? '') ?>" maxlength="30" placeholder="e.g. 0917 123 4567" autocomplete="tel">
                        </label>
                    <?php endif; ?>
                    <button type="submit" class="quick-btn quick-btn-primary">Save Profile</button>
                </form>
            </section>

            <section class="settings-card">
                <h2>Password</h2>
                <p class="role-hint">Choose a new password for signing in.</p>
                <form method="post" action="<?= site_url('settings') ?>">
                    <input type="hidden" name="form_action" value="password">
                    <label>New Password
                        <input type="password" name="new_password" autocomplete="new-password" required>
                    </label>
                    <button type="submit" class="quick-btn quick-btn-primary">Update Password</button>
                </form>
            </section>
        </div>
    </div>
</div>
