<?php
/**
 * Live "new order" notice for Admins. Every 30 seconds (and when the tab is
 * brought back to the front) the open page quietly asks the server for a few
 * numbers: how many orders are Pending and how many are newer than the newest
 * one the page knew about. Nothing on the page is reloaded or redrawn, so an
 * open Details window or any other pop-up is left alone. It only:
 *   - updates the "Pending Orders" number on the Dashboard,
 *   - shows a small notice (bottom right) that stays until it is closed or
 *     used: "Refresh list" on the Orders page, "View orders" elsewhere.
 * Loaded only for Admin / Super Admin accounts that may approve orders.
 */
$pending = model(\App\Models\OrderModel::class)->pendingSummary();
?>
<script>
    (function () {
        var cfg = {
            summaryUrl: <?= json_encode(site_url('orders/pending-summary')) ?>,
            ordersUrl: <?= json_encode(site_url('orders')) ?>,
            onOrdersPage: <?= json_encode(($nav_current_page ?? '') === 'orders') ?>,
            count: <?= (int) $pending['count'] ?>,
            latest: <?= (int) $pending['latest'] ?>,
            every: 30000
        };

        var since = cfg.latest;      // newest Pending order id the page has accounted for
        var lastLatest = cfg.latest; // newest id seen in the most recent answer
        var failures = 0;
        var busy = false;
        var toast = null;

        function showCount(n) {
            document.querySelectorAll('[data-live-pending]').forEach(function (el) { el.textContent = n; });
        }

        function dismiss() {
            if (toast) { toast.remove(); toast = null; }
            since = lastLatest; // later orders are counted from here
        }

        function showNotice(newer, total) {
            if (!toast) {
                toast = document.createElement('div');
                toast.className = 'live-toast';
                toast.setAttribute('role', 'status');
                toast.setAttribute('aria-live', 'polite');
                document.body.appendChild(toast);
            }
            toast.textContent = '';

            var text = document.createElement('span');
            text.textContent = (newer === 1 ? '1 new order' : newer + ' new orders') + ' waiting for approval (' + total + ' pending).';

            var go = document.createElement('button');
            go.type = 'button';
            go.className = 'live-toast-go';
            go.textContent = cfg.onOrdersPage ? 'Refresh list' : 'View orders';
            go.addEventListener('click', function () {
                if (cfg.onOrdersPage) { window.location.reload(); } else { window.location.href = cfg.ordersUrl; }
            });

            var close = document.createElement('button');
            close.type = 'button';
            close.className = 'live-toast-close';
            close.setAttribute('aria-label', 'Dismiss');
            close.textContent = '×';
            close.addEventListener('click', dismiss);

            toast.appendChild(text);
            toast.appendChild(go);
            toast.appendChild(close);
        }

        function check() {
            if (busy || document.hidden) { return; }
            busy = true;

            fetch(cfg.summaryUrl + '/' + since, { credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    if (typeof d.count !== 'number') { throw new Error('unexpected answer'); }
                    failures = 0;
                    lastLatest = d.latest;
                    showCount(d.count);
                    if (d.newer > 0) { showNotice(d.newer, d.count); }
                })
                .catch(function () {
                    // A sign-out or a hiccup returns something that is not JSON; stop after a few tries.
                    failures++;
                    if (failures >= 3) { clearInterval(timer); }
                })
                .then(function () { busy = false; });
        }

        showCount(cfg.count);
        var timer = setInterval(check, cfg.every);
        document.addEventListener('visibilitychange', function () { if (!document.hidden) { check(); } });
    })();
</script>
