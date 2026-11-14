<?php $logoFile = $brand_logo_file ?? 'Logo_Dashboard.svg'; ?>
<img src="<?= esc(function_exists('base_url') ? base_url('Photos/' . $logoFile) : '/public/Photos/' . $logoFile) ?>"
     alt="Gab's Bakeshop"
     class="brand-logo">
