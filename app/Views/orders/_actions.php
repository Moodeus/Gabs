<?php
/**
 * Shared action buttons for one order - included from both the desktop
 * table's Action cell and the mobile per-order modal, so the two never
 * drift out of sync. Expects $order and $role.
 */
?>
<?php if (in_array($role, ['Admin', 'Super Admin'], true) && $order['Status'] === 'Pending' && can('orders.approve')): ?>
    <form method="post" action="<?= site_url('orders') ?>" class="inline-form">
        <input type="hidden" name="form_action" value="status">
        <input type="hidden" name="order_id" value="<?= esc($order['Order_ID']) ?>">
        <button type="submit" name="status" value="Approved" class="btn-approve">Approve</button>
        <button type="submit" name="status" value="Denied" class="btn-deny">Deny</button>
    </form>
<?php elseif ($role === 'Delivery' && $order['Status'] === 'Approved' && can('orders.deliver')): ?>
    <form method="post" action="<?= site_url('orders') ?>" enctype="multipart/form-data" class="inline-form">
        <input type="hidden" name="form_action" value="status">
        <input type="hidden" name="order_id" value="<?= esc($order['Order_ID']) ?>">
        <input type="hidden" name="status" value="Delivered">
        <label class="file-pick quick-btn">
            <span class="file-pick-text">Upload Proof</span>
            <input type="file" name="proof_image" accept="image/*" required>
        </label>
        <button type="submit" class="quick-btn quick-btn-primary">Mark Delivered</button>
    </form>
<?php elseif ($role === 'Branch' && $order['Status'] === 'Pending' && can('orders.cancel')): ?>
    <form method="post" action="<?= site_url('orders') ?>" class="inline-form">
        <input type="hidden" name="form_action" value="status">
        <input type="hidden" name="order_id" value="<?= esc($order['Order_ID']) ?>">
        <button type="submit" name="status" value="Cancelled" class="quick-btn">Cancel</button>
    </form>
<?php elseif (in_array($role, ['Admin', 'Super Admin', 'Delivery'], true) && $order['Status'] === 'Delivered' && can('orders.pay')): ?>
    <form method="post" action="<?= site_url('orders') ?>" class="inline-form">
        <input type="hidden" name="form_action" value="status">
        <input type="hidden" name="order_id" value="<?= esc($order['Order_ID']) ?>">
        <button type="submit" name="status" value="Paid" class="btn-paid">Paid</button>
    </form>
<?php endif; ?>
<?php if (in_array($order['Status'], ['Approved', 'Delivered', 'Paid'], true) && can('orders.invoice')): ?>
    <a class="quick-btn" href="<?= site_url('orders/' . $order['Order_ID'] . '/invoice') ?>" target="_blank" rel="noopener">Delivery</a>
<?php endif; ?>
<?php if ($order['Status'] === 'Paid' && can('orders.invoice')): ?>
    <a class="quick-btn" href="<?= site_url('orders/' . $order['Order_ID'] . '/billing-statement') ?>" target="_blank" rel="noopener">Acknowledgement</a>
<?php endif; ?>
