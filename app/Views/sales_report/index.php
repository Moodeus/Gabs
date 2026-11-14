<?php
$today = database_today();
$todayMonth = substr($today, 0, 7);
$todayYear = substr($today, 0, 4);
?>
<div class="page-content">
    <div class="dashboard-content">
        <div class="page-header-row">
            <h1>Sales Report</h1>
            <div class="page-header-actions">
                <?php if (can('export.pdf')): ?>
                    <button type="button" class="quick-btn" data-modal-open="export-sales-modal">Export to PDF</button>
                <?php endif; ?>
            </div>
        </div>

        <div class="report-view-picker">
            <label for="report-view-select">Report Type</label>
            <select id="report-view-select">
                <option value="daily">Daily Sales Report</option>
                <option value="monthly">Monthly Sales Report</option>
                <option value="yearly">Yearly Sales Report</option>
            </select>
        </div>

        <?php $isToday = ($selected_date ?? null) === $today; ?>
        <div id="report-view-daily">
            <?php $dayLabel = !empty($selected_date) && !$isToday ? esc(date('M j, Y', strtotime($selected_date))) : 'Today'; ?>
            <div class="stat-grid">
                <div class="stat-card stat-accent">
                    <span class="stat-value">₱<?= esc(number_format($sales_total, 2)) ?></span>
                    <span class="stat-label">Net Sales (<?= $dayLabel ?>)</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value">₱<?= esc(number_format($gross_total, 2)) ?></span>
                    <span class="stat-label">Billed to Branches</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value">₱<?= esc(number_format($cost_total, 2)) ?></span>
                    <span class="stat-label">Cost of Products</span>
                </div>
            </div>

            <div class="dashboard-section">
                <h2>Filter by Date</h2>
                <form id="date-filter-form" class="inline-form">
                    <button type="button" id="prev-date-btn" class="quick-btn" aria-label="Previous day">&larr;</button>
                    <input type="date" id="report-date" name="date" value="<?= esc($selected_date ?? '') ?>" max="<?= esc($today) ?>">
                    <button type="button" id="next-date-btn" class="quick-btn" aria-label="Next day">&rarr;</button>
                    <button type="submit" class="quick-btn quick-btn-primary">View</button>
                    <?php if (!empty($selected_date) && !$isToday): ?>
                        <a class="quick-btn" href="<?= site_url('sales-report') ?>">Today</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="dashboard-section">
                <h2><?= $isToday ? "Today's Orders" : 'Orders' ?><?= !empty($selected_date) ? ' — ' . esc(date('F j, Y', strtotime($selected_date))) : '' ?></h2>
                <div class="recent-table-wrap">
                    <table class="recent-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Branch</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Cost</th>
                                <th>Net</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?= esc($order['Name']) ?></td>
                                    <td><?= esc($order['branch_name']) ?></td>
                                    <td><?= esc(date('M j, Y g:i A', strtotime($order['Order_Date']))) ?></td>
                                    <td>₱<?= esc(number_format((float) $order['Order_Total'], 2)) ?></td>
                                    <td>₱<?= esc(number_format((float) $order['Order_Cost'], 2)) ?></td>
                                    <td><strong>₱<?= esc(number_format((float) $order['Order_Total'] - (float) $order['Order_Cost'], 2)) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($orders)): ?>
                                <tr><td colspan="6"><?= !empty($selected_date) ? ($isToday ? 'No sales yet today.' : 'No sales on this date.') : 'No delivered orders yet.' ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?= view('layouts/pagination', ['page' => $page, 'totalPages' => $total_pages, 'baseUrl' => !empty($selected_date) ? 'sales-report/date/' . $selected_date : 'sales-report']) ?>
            </div>
        </div>

        <div id="report-view-monthly" hidden>
            <div class="dashboard-section">
                <h2>Monthly Sales</h2>
                <p class="role-hint">Net sales (billed, less the cost of the products) for delivered and paid orders, month by month. Click a month to see its most-ordered products.</p>
                <div class="sales-chart-nav">
                    <button type="button" class="quick-btn" id="sr-sales-prev-year" aria-label="Previous year">&larr;</button>
                    <label class="sales-year-label" for="sr-sales-year">Year</label>
                    <select id="sr-sales-year" aria-label="Choose a year"></select>
                    <button type="button" class="quick-btn" id="sr-sales-next-year" aria-label="Next year">&rarr;</button>
                    <span class="sales-year-total" id="sr-sales-year-total"></span>
                </div>
                <div class="sales-chart-wrap">
                    <svg id="sr-sales-chart" class="sales-chart" viewBox="0 0 960 330" role="group" aria-label="Monthly sales bar chart"></svg>
                </div>
            </div>
        </div>

        <div id="report-view-yearly" hidden>
            <div class="dashboard-section">
                <h2>Yearly Sales</h2>
                <p class="role-hint">Net sales (billed, less the cost of the products) for delivered and paid orders, totaled by year.</p>
                <div class="sales-chart-wrap">
                    <svg id="sr-yearly-chart" class="sales-chart" viewBox="0 0 960 340" role="img" aria-label="Yearly sales line chart"></svg>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (can('export.pdf')): ?>
    <dialog id="export-sales-modal" class="modal">
        <div class="modal-header">
            <h2>Export Sales Report</h2>
            <button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button>
        </div>
        <form id="export-sales-form" onsubmit="return false;">
            <label>Report for
                <select id="export-period">
                    <option value="day">A day</option>
                    <option value="week">A week</option>
                    <option value="month">A month</option>
                    <option value="year">A year</option>
                    <option value="range">A custom date range</option>
                </select>
            </label>
            <div class="export-field" data-for="day">
                <label>Date <input type="date" id="export-day" max="<?= esc($today) ?>" value="<?= esc($selected_date ?? $today) ?>"></label>
            </div>
            <div class="export-field" data-for="week" hidden>
                <label>Any day in that week <input type="date" id="export-week" max="<?= esc($today) ?>" value="<?= esc($today) ?>"></label>
                <p class="role-hint" id="export-week-range"></p>
            </div>
            <div class="export-field" data-for="month" hidden>
                <label>Month <input type="month" id="export-month" max="<?= esc($todayMonth) ?>" value="<?= esc($todayMonth) ?>"></label>
            </div>
            <div class="export-field" data-for="year" hidden>
                <label>Year <input type="number" id="export-year" min="2000" max="<?= esc($todayYear) ?>" step="1" value="<?= esc($todayYear) ?>"></label>
            </div>
            <div class="export-field export-range" data-for="range" hidden>
                <label>From <input type="date" id="export-from" max="<?= esc($today) ?>" value="<?= esc(($todayMonth . '-01')) ?>"></label>
                <label>To <input type="date" id="export-to" max="<?= esc($today) ?>" value="<?= esc($today) ?>"></label>
            </div>
            <p class="role-hint" id="export-error" hidden></p>
            <div class="modal-actions">
                <button type="button" class="quick-btn" data-modal-close>Cancel</button>
                <button type="button" class="quick-btn quick-btn-primary" id="export-go">Open Printable Report</button>
            </div>
        </form>
    </dialog>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var period = document.getElementById('export-period');
            var fields = document.querySelectorAll('#export-sales-form .export-field');
            var errorBox = document.getElementById('export-error');
            var exportUrl = <?= json_encode(site_url('sales-report/export/')) ?>;

            function pad(n) { return String(n).padStart(2, '0'); }

            function iso(date) {
                return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
            }

            // Monday to Sunday of the week containing the chosen day. Built
            // from local date parts (not toISOString) so no timezone shifts it.
            function weekOf(value) {
                if (!value) { return null; }
                var d = new Date(value + 'T00:00:00');
                var monday = new Date(d.getFullYear(), d.getMonth(), d.getDate() - ((d.getDay() + 6) % 7));
                var sunday = new Date(monday.getFullYear(), monday.getMonth(), monday.getDate() + 6);
                return [iso(monday), iso(sunday)];
            }

            var weekInput = document.getElementById('export-week');
            var weekHint = document.getElementById('export-week-range');
            function showWeek() {
                var w = weekOf(weekInput.value);
                weekHint.textContent = w ? 'Monday ' + w[0] + ' to Sunday ' + w[1] : '';
            }
            weekInput.addEventListener('input', showWeek);
            showWeek();

            function showFields() {
                fields.forEach(function (f) { f.hidden = f.getAttribute('data-for') !== period.value; });
                errorBox.hidden = true;
            }
            period.addEventListener('change', showFields);

            // Turns the chosen period into a from/to pair of YYYY-MM-DD dates.
            function chosenRange() {
                if (period.value === 'day') {
                    var d = document.getElementById('export-day').value;
                    return d ? [d, d] : null;
                }
                if (period.value === 'week') {
                    return weekOf(weekInput.value);
                }
                if (period.value === 'month') {
                    var m =document.getElementById('export-month').value;
                    if (!m) { return null; }
                    var y = parseInt(m.slice(0, 4), 10), mo = parseInt(m.slice(5, 7), 10);
                    return [m + '-01', m + '-' + pad(new Date(y, mo, 0).getDate())];
                }
                if (period.value === 'year') {
                    var yr = parseInt(document.getElementById('export-year').value, 10);
                    return yr >= 1000 && yr <= 9999 ? [yr + '-01-01', yr + '-12-31'] : null;
                }
                var from = document.getElementById('export-from').value;
                var to = document.getElementById('export-to').value;
                return from && to ? [from, to] : null;
            }

            document.getElementById('export-go').addEventListener('click', function () {
                var range = chosenRange();
                if (!range) {
                    errorBox.textContent = 'Please fill in the date first.';
                    errorBox.hidden = false;
                    return;
                }
                if (range[0] > range[1]) {
                    errorBox.textContent = 'The "From" date must be on or before the "To" date.';
                    errorBox.hidden = false;
                    return;
                }
                window.open(exportUrl + range[0] + '/' + range[1], '_blank');
                document.getElementById('export-sales-modal').close();
            });
        });
    </script>
<?php endif; ?>

<dialog id="sr-month-products-modal" class="modal modal-lg">
    <div class="modal-header">
        <h2 id="sr-month-products-title">Most ordered products</h2>
        <button type="button" class="modal-close" data-modal-close aria-label="Close">&times;</button>
    </div>
    <div class="order-detail-body">
        <p class="role-hint" id="sr-month-products-summary"></p>
        <div id="sr-month-products-bars" class="hbar-list"></div>
    </div>
</dialog>

<script type="application/json" id="sr-sales-chart-data"><?= json_encode($sales_chart, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>

<script>
    (function () {
        var dateInput = document.getElementById('report-date');
        var todayIso = <?= json_encode($today) ?>;
        var dateUrl = <?= json_encode(site_url('sales-report/date/')) ?>;

        document.getElementById('date-filter-form').addEventListener('submit', function (e) {
            e.preventDefault();
            var date = dateInput.value;
            window.location.href = date ? dateUrl + date : <?= json_encode(site_url('sales-report')) ?>;
        });

        function toIsoDate(d) {
            var m = String(d.getMonth() + 1).padStart(2, '0');
            var day = String(d.getDate()).padStart(2, '0');
            return d.getFullYear() + '-' + m + '-' + day;
        }

        function shiftDate(days) {
            // Built from local getFullYear/getMonth/getDate, not
            // toISOString() - that converts to UTC first, which silently
            // shifts the calendar date for any timezone ahead of UTC.
            var base = dateInput.value || todayIso;
            var d = new Date(base + 'T00:00:00');
            d.setDate(d.getDate() + days);
            var iso = toIsoDate(d);
            if (iso > todayIso) {
                iso = todayIso;
            }
            window.location.href = dateUrl + iso;
        }

        document.getElementById('prev-date-btn').addEventListener('click', function () { shiftDate(-1); });
        document.getElementById('next-date-btn').addEventListener('click', function () { shiftDate(1); });
    })();
</script>

<script>
    (function () {
        var data = JSON.parse(document.getElementById('sr-sales-chart-data').textContent);
        var totals = Array.isArray(data.totals) ? {} : data.totals;
        var products = Array.isArray(data.products) ? {} : data.products;
        var years = data.years;
        var MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        var NS = 'http://www.w3.org/2000/svg';

        function el(name, attrs, text) {
            var n = document.createElementNS(NS, name);
            Object.keys(attrs || {}).forEach(function (k) { n.setAttribute(k, attrs[k]); });
            if (text !== undefined) { n.textContent = text; }
            return n;
        }
        function peso(v) { return '₱' + Math.round(v).toLocaleString('en-US'); }
        function compact(v) { return v >= 1000 ? '₱' + (v / 1000).toLocaleString('en-US', { maximumFractionDigits: 1 }) + 'k' : '₱' + v; }
        function niceStep(maxTotal) {
            if (maxTotal <= 0) { return 1000; }
            var raw = maxTotal / 4, mag = Math.pow(10, Math.floor(Math.log10(raw))), norm = raw / mag;
            return (norm <= 1 ? 1 : norm <= 2 ? 2 : norm <= 5 ? 5 : 10) * mag;
        }

        // ---- Daily / Monthly / Yearly switcher ----
        var select = document.getElementById('report-view-select');
        var views = {
            daily: document.getElementById('report-view-daily'),
            monthly: document.getElementById('report-view-monthly'),
            yearly: document.getElementById('report-view-yearly'),
        };
        var drawn = { monthly: false, yearly: false };

        function showView(name) {
            Object.keys(views).forEach(function (key) { views[key].hidden = key !== name; });
            if (name === 'monthly' && !drawn.monthly) { drawn.monthly = true; drawMonthly(currentYear); }
            if (name === 'yearly' && !drawn.yearly) { drawn.yearly = true; drawYearly(); }
        }
        select.addEventListener('change', function () { showView(select.value); });

        // ---- Monthly Sales bar chart (same chart as the Dashboard) ----
        var svg = document.getElementById('sr-sales-chart');
        var yearSelect = document.getElementById('sr-sales-year');
        var prevBtn = document.getElementById('sr-sales-prev-year');
        var nextBtn = document.getElementById('sr-sales-next-year');
        var yearTotalEl = document.getElementById('sr-sales-year-total');
        var W = 960, H = 330, left = 70, right = 16, top = 30, bottom = 52;
        var plotW = W - left - right, plotH = H - top - bottom;

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

        function drawMonthly(year) {
            var months = monthsOf(year);
            var maxTotal = months.reduce(function (m, x) { return Math.max(m, x.total); }, 0);
            var yearSum = months.reduce(function (s, x) { return s + x.total; }, 0);
            var step = niceStep(maxTotal);
            var ticks = Math.max(1, Math.ceil(maxTotal / step));
            var axisMax = step * ticks;
            function y(v) { return top + plotH - (v / axisMax) * plotH; }

            svg.textContent = '';
            svg.setAttribute('aria-label', 'Monthly sales bar chart for ' + year);
            yearTotalEl.textContent = year + ' total: ' + peso(yearSum);
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

        function goMonthly(year) {
            year = Math.min(currentYear, Math.max(years[0], year));
            yearSelect.value = year;
            drawMonthly(year);
        }
        yearSelect.addEventListener('change', function () { goMonthly(parseInt(yearSelect.value, 10)); });
        prevBtn.addEventListener('click', function () { goMonthly(parseInt(yearSelect.value, 10) - 1); });
        nextBtn.addEventListener('click', function () { goMonthly(parseInt(yearSelect.value, 10) + 1); });

        var dialog = document.getElementById('sr-month-products-modal');
        var title = document.getElementById('sr-month-products-title');
        var summary = document.getElementById('sr-month-products-summary');
        var bars = document.getElementById('sr-month-products-bars');

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

        // ---- Yearly Sales line chart ----
        var ySvg = document.getElementById('sr-yearly-chart');
        var yW = 960, yH = 340, yLeft = 90, yRight = 20, yTop = 30, yBottom = 60;
        var yPlotW = yW - yLeft - yRight, yPlotH = yH - yTop - yBottom;

        function yearTotal(yr) {
            var sum = 0;
            for (var m = 1; m <= 12; m++) {
                var key = yr + '-' + String(m).padStart(2, '0');
                if (totals[key]) { sum += totals[key].total; }
            }
            return sum;
        }

        function drawYearly() {
            var points = years.map(function (yr) { return { year: yr, total: yearTotal(yr) }; });
            var maxTotal = points.reduce(function (m, p) { return Math.max(m, p.total); }, 0);
            var step = niceStep(maxTotal);
            var ticks = Math.max(1, Math.ceil(maxTotal / step));
            var axisMax = step * ticks;
            function y(v) { return yTop + yPlotH - (v / axisMax) * yPlotH; }
            function x(i) { return points.length <= 1 ? yLeft + yPlotW / 2 : yLeft + (yPlotW * i) / (points.length - 1); }

            ySvg.textContent = '';
            ySvg.setAttribute('aria-label', 'Yearly sales line chart');

            for (var t = 0; t <= ticks; t++) {
                var tv = step * t;
                ySvg.appendChild(el('line', { x1: yLeft, x2: yW - yRight, y1: y(tv), y2: y(tv), class: t === 0 ? 'sc-axis' : 'sc-grid' }));
                ySvg.appendChild(el('text', { x: yLeft - 8, y: y(tv) + 4, class: 'sc-tick', 'text-anchor': 'end' }, compact(tv)));
            }
            ySvg.appendChild(el('line', { x1: yLeft, x2: yLeft, y1: yTop, y2: yTop + yPlotH, class: 'sc-axis' }));

            if (points.length > 0) {
                var path = points.map(function (p, i) { return (i === 0 ? 'M' : 'L') + x(i) + ' ' + y(p.total); }).join(' ');
                ySvg.appendChild(el('path', { d: path, class: 'sc-line', fill: 'none' }));
            }

            points.forEach(function (p, i) {
                ySvg.appendChild(el('circle', { cx: x(i), cy: y(p.total), r: 4, class: 'sc-point' }));
                ySvg.appendChild(el('text', { x: x(i), y: y(p.total) - 10, class: 'sc-value', 'text-anchor': 'middle' }, peso(p.total)));
                ySvg.appendChild(el('text', { x: x(i), y: yH - yBottom + 22, class: 'sc-month-label', 'text-anchor': 'middle' }, String(p.year)));
            });

            ySvg.appendChild(el('text', {
                x: 18, y: yTop + yPlotH / 2, class: 'sc-axis-title', 'text-anchor': 'middle',
                transform: 'rotate(-90 18 ' + (yTop + yPlotH / 2) + ')',
            }, 'Income'));
            ySvg.appendChild(el('text', { x: yLeft + yPlotW / 2, y: yH - 8, class: 'sc-axis-title', 'text-anchor': 'middle' }, 'Year'));
        }

        showView('daily');
    })();
</script>
