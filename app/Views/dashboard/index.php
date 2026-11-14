<div class="page-content">
    <div class="dashboard-content">
        <h1>Welcome to Gab's Bakeshop</h1>
        <p class="user-info">
            Good <?= esc($greeting_period) ?>, <strong><?= esc($user_name) ?></strong>
            <span class="role-badge"><?= esc($role) ?></span>
        </p>

        <?php if (!empty($msg)): ?>
            <?php if (strpos($msg, '<div') === false): ?>
                <div class="message success"><?= esc($msg) ?></div>
            <?php else: ?>
                <?= $msg ?>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <?php if (strpos($error, '<div') === false): ?>
                <div class="message error"><?= esc($error) ?></div>
            <?php else: ?>
                <?= $error ?>
            <?php endif; ?>
        <?php endif; ?>

        <?php
        // A card links to a page, so it only stays a link if this user may open
        // that page; otherwise it shows the same figure as plain, unclickable text.
        $pagePermission = [
            'orders' => 'orders.view',
            'sales-report' => 'reports.sales',
            'accounts' => 'accounts.view',
            'product-inventory' => 'inventory.view',
            'ingredient-inventory' => 'inventory.view',
            'delivery-receipts' => 'reports.receipts',
        ];
        ?>
        <div class="stat-grid">
            <?php foreach ($cards as $card): ?>
                <?php
                $firstSegment = explode('/', $card['url'])[0];
                $linked = !isset($pagePermission[$firstSegment]) || can($pagePermission[$firstSegment]);
                ?>
                <<?= $linked ? 'a href="' . esc(site_url($card['url'])) . '"' : 'div' ?> class="stat-card stat-<?= esc($card['tone']) ?><?= $linked ? '' : ' stat-card-static' ?>">
                    <span class="stat-value"<?= $card['label'] === 'Pending Orders' ? ' data-live-pending' : '' ?>><?= !empty($card['raw']) ? esc($card['value']) : esc((string) $card['value']) ?></span>
                    <span class="stat-label"><?= esc($card['label']) ?></span>
                </<?= $linked ? 'a' : 'div' ?>>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($sales_chart)): ?>
            <div class="dashboard-section">
                <h2>Monthly Sales</h2>
                <p class="role-hint">Net sales (billed, less the cost of the products) for delivered and paid orders, month by month. Click a month to see its most-ordered products.</p>
                <div class="sales-chart-nav">
                    <button type="button" class="quick-btn" id="sales-prev-year" aria-label="Previous year">&larr;</button>
                    <label class="sales-year-label" for="sales-year">Year</label>
                    <select id="sales-year" aria-label="Choose a year"></select>
                    <button type="button" class="quick-btn" id="sales-next-year" aria-label="Next year">&rarr;</button>
                    <span class="sales-year-total" id="sales-year-total"></span>
                </div>
                <div class="sales-chart-wrap">
                    <svg id="sales-chart" class="sales-chart" viewBox="0 0 960 330" role="group" aria-label="Monthly sales bar chart"></svg>
                </div>
            </div>
        <?php endif; ?>

        <div class="welcome-card">
            <p class="role-hint"><?= esc($role_hint) ?></p>
        </div>
    </div>
</div>

<?php if (!empty($sales_chart)): ?>
    <dialog id="month-products-modal" class="modal modal-lg">
        <div class="modal-header">
            <h2 id="month-products-title">Most ordered products</h2>
            <button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button>
        </div>
        <div class="order-detail-body">
            <p class="role-hint" id="month-products-summary"></p>
            <div id="month-products-bars" class="hbar-list"></div>
        </div>
    </dialog>

    <script type="application/json" id="sales-chart-data"><?= json_encode($sales_chart, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var data = JSON.parse(document.getElementById('sales-chart-data').textContent);
            var totals = Array.isArray(data.totals) ? {} : data.totals;
            var products = Array.isArray(data.products) ? {} : data.products;
            var years = data.years;
            var MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

            var svg = document.getElementById('sales-chart');
            var yearSelect = document.getElementById('sales-year');
            var prevBtn = document.getElementById('sales-prev-year');
            var nextBtn = document.getElementById('sales-next-year');
            var yearTotal = document.getElementById('sales-year-total');
            var NS = 'http://www.w3.org/2000/svg';
            var W = 960, H = 330, left = 70, right = 16, top = 30, bottom = 52;
            var plotW = W - left - right, plotH = H - top - bottom;

            function el(name, attrs, text) {
                var n = document.createElementNS(NS, name);
                Object.keys(attrs || {}).forEach(function (k) { n.setAttribute(k, attrs[k]); });
                if (text !== undefined) { n.textContent = text; }
                return n;
            }
            function peso(v) { return '₱' + Math.round(v).toLocaleString('en-US'); }
            function compact(v) { return v >= 1000 ? '₱' + (v / 1000).toLocaleString('en-US', { maximumFractionDigits: 1 }) + 'k' : '₱' + v; }

            years.forEach(function (yr) {
                var o = document.createElement('option');
                o.value = yr;
                o.textContent = yr;
                yearSelect.appendChild(o);
            });
            var currentYear = years[years.length - 1];
            yearSelect.value = currentYear;

            function monthsOf(year) {
                return MONTHS.map(function (name, i) {
                    var key = year + '-' + String(i + 1).padStart(2, '0');
                    var t = totals[key] || { total: 0, orders: 0 };
                    return { key: key, label: name.slice(0, 3), full: name + ' ' + year, total: t.total, orders: t.orders };
                });
            }

            function draw(year) {
                var months = monthsOf(year);
                var maxTotal = months.reduce(function (m, x) { return Math.max(m, x.total); }, 0);
                var yearSum = months.reduce(function (s, x) { return s + x.total; }, 0);

                // Round the axis top up to a "nice" step so the ticks read cleanly.
                var step = 1000;
                if (maxTotal > 0) {
                    var raw = maxTotal / 4, mag = Math.pow(10, Math.floor(Math.log10(raw)));
                    var norm = raw / mag;
                    step = (norm <= 1 ? 1 : norm <= 2 ? 2 : norm <= 5 ? 5 : 10) * mag;
                }
                var ticks = Math.max(1, Math.ceil(maxTotal / step));
                var axisMax = step * ticks;
                function y(v) { return top + plotH - (v / axisMax) * plotH; }

                svg.textContent = '';
                svg.setAttribute('aria-label', 'Monthly sales bar chart for ' + year);
                yearTotal.textContent = year + ' total: ' + peso(yearSum);
                prevBtn.disabled = year <= years[0];
                nextBtn.disabled = year >= currentYear;

                for (var t = 0; t <= ticks; t++) {
                    var tv = step * t;
                    svg.appendChild(el('line', { x1: left, x2: W - right, y1: y(tv), y2: y(tv), class: t === 0 ? 'sc-axis' : 'sc-grid' }));
                    svg.appendChild(el('text', { x: left - 8, y: y(tv) + 4, class: 'sc-tick', 'text-anchor': 'end' }, compact(tv)));
                }

                var slot = plotW / months.length, barW = Math.min(46, slot * 0.62);

                months.forEach(function (m, i) {
                    var cx = left + slot * i + slot / 2;
                    var isTop = maxTotal > 0 && m.total === maxTotal;
                    var g = el('g', { class: 'sc-month' + (isTop ? ' sc-top' : ''), tabindex: 0, role: 'button',
                        'aria-label': m.full + ': ' + peso(m.total) + ' in net sales. Show most-ordered products.' });

                    // Full-height hit area, so short bars and the label are easy to click.
                    g.appendChild(el('rect', { x: cx - slot / 2, y: top, width: slot, height: plotH + bottom - 6, class: 'sc-hit' }));
                    if (m.total > 0) {
                        var bh = plotH - (y(m.total) - top);
                        g.appendChild(el('rect', { x: cx - barW / 2, y: y(m.total), width: barW, height: bh, rx: 3, class: 'sc-bar' }));
                        g.appendChild(el('text', { x: cx, y: y(m.total) - 6, class: 'sc-value', 'text-anchor': 'middle' }, peso(m.total)));
                    }
                    g.appendChild(el('text', { x: cx, y: H - 30, class: 'sc-month-label', 'text-anchor': 'middle' }, m.label));
                    g.appendChild(el('text', { x: cx, y: H - 15, class: 'sc-year-label', 'text-anchor': 'middle' }, String(year)));

                    g.addEventListener('click', function () { openMonth(m); });
                    g.addEventListener('keydown', function (e) {
                        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openMonth(m); }
                    });
                    svg.appendChild(g);
                });
            }

            function go(year) {
                year = Math.min(currentYear, Math.max(years[0], year));
                yearSelect.value = year;
                draw(year);
            }
            yearSelect.addEventListener('change', function () { go(parseInt(yearSelect.value, 10)); });
            prevBtn.addEventListener('click', function () { go(parseInt(yearSelect.value, 10) - 1); });
            nextBtn.addEventListener('click', function () { go(parseInt(yearSelect.value, 10) + 1); });

            var dialog = document.getElementById('month-products-modal');
            var title = document.getElementById('month-products-title');
            var summary = document.getElementById('month-products-summary');
            var bars = document.getElementById('month-products-bars');

            function openMonth(m) {
                var list = products[m.key] || [];
                title.textContent = 'Most ordered products — ' + m.full;
                summary.textContent = m.orders === 0
                    ? 'No delivered orders this month.'
                    : m.orders + (m.orders === 1 ? ' order' : ' orders') + ' · ' + peso(m.total) + ' in net sales';

                bars.textContent = '';
                var maxQty = list.reduce(function (a, p) { return Math.max(a, p.qty); }, 0);
                list.forEach(function (p, i) {
                    var row = document.createElement('div');
                    row.className = 'hbar-row';
                    var name = document.createElement('span');
                    name.className = 'hbar-label';
                    name.textContent = p.name;
                    name.title = p.name;
                    var track = document.createElement('div');
                    track.className = 'hbar-track';
                    var fill = document.createElement('div');
                    fill.className = 'hbar-fill' + (i === 0 ? ' hbar-first' : '');
                    fill.style.width = (maxQty > 0 ? (p.qty / maxQty) * 100 : 0) + '%';
                    track.appendChild(fill);
                    var val = document.createElement('span');
                    val.className = 'hbar-value';
                    val.textContent = Number(p.qty).toLocaleString('en-US', { maximumFractionDigits: 2 }) + ' ordered';
                    val.title = peso(p.amount) + ' in net sales';
                    row.appendChild(name);
                    row.appendChild(track);
                    row.appendChild(val);
                    bars.appendChild(row);
                });
                dialog.showModal();
            }

            draw(currentYear);
        });
    </script>
<?php endif; ?>
