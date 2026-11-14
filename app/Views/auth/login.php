<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gab's Bakeshop</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/index.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/brand.css') ?>">
</head>
<body>
<div class="login-box">
    <div class="logo">
        <?= $this->include('layouts/brand_logo', ['brand_logo_file' => 'Gabs_Logo.svg']) ?>
    </div>
    <h2>Welcome!</h2>

    <?php if (!empty($error)): ?>
        <p class="error-message"><?= esc($error) ?></p>
    <?php endif; ?>

    <form action="<?= site_url('auth/login') ?>" method="POST">
        <div class="input-group">
            <label for="username_or_email">Username</label>
            <input type="text" id="username_or_email" name="username_or_email"
                placeholder="Enter your username or email" required
                value="<?= esc($username_or_email ?? '') ?>" aria-label="Username or Email">
        </div>
        <div class="input-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password"
                placeholder="Enter your password" required aria-label="Password">
        </div>
        <button type="submit" class="login-btn">Login</button>
    </form>
</div>
</body>
</html>
