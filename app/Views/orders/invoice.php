<?php
/**
 * Printable invoice for one approved order. Rendered standalone (no
 * layouts/main chrome) so it prints cleanly - reachable at
 * orders/{id}/invoice once an order is Approved, Delivered, or Paid.
 *
 * Layout is table-based and uses only inline styles, so it renders the
 * same in the browser's print dialog and in a PDF library (Dompdf /
 * mPDF / wkhtmltopdf) if one is ever wired in.
 */

// ---- Company details (edit once) ------------------------------------
$company = [
    'name'    => 'Gab Shawn Foods OPC',
    'address' => 'Company address line, City, Province, 0000',
    'phone'   => '(000) 000 0000',
    'email'   => 'orders@gabshawnfoods.com',
    'logo'    => base_url('assets/images/Gabs_Logo.svg'),
];

// ---- Helpers ---------------------------------------------------------
if (!function_exists('invoice_money')) {
    function invoice_money($v) { return number_format((float) $v, 2, '.', ','); }
}
if (!function_exists('invoice_fdate')) {
    function invoice_fdate($v) { return $v ? date('F j, Y', strtotime($v)) : '—'; }
}
if (!function_exists('invoice_peso_words')) {
    /** Amount in words, Philippine pesos. */
    function invoice_peso_words($amount) {
        $ones = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine',
                 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen',
                 'seventeen', 'eighteen', 'nineteen'];
        $tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];

        $under1000 = function ($n) use ($ones, $tens, &$under1000) {
            if ($n < 20)  return $ones[$n];
            if ($n < 100) return $tens[intdiv($n, 10)] . ($n % 10 ? '-' . $ones[$n % 10] : '');
            return $ones[intdiv($n, 100)] . ' hundred' . ($n % 100 ? ' ' . $under1000($n % 100) : '');
        };

        $pesos    = (int) floor($amount);
        $centavos = (int) round(($amount - $pesos) * 100);

        if ($pesos === 0) {
            $words = 'zero';
        } else {
            $parts  = [];
            $scales = [1000000000 => 'billion', 1000000 => 'million', 1000 => 'thousand'];
            foreach ($scales as $value => $label) {
                if ($pesos >= $value) {
                    $parts[] = $under1000(intdiv($pesos, $value)) . ' ' . $label;
                    $pesos  %= $value;
                }
            }
            if ($pesos > 0) $parts[] = $under1000($pesos);
            $words = implode(' ', $parts);
        }

        $out = ucfirst($words) . ' peso' . ($amount >= 2 ? 's' : '');
        if ($centavos > 0) {
            $out .= ' and ' . $under1000($centavos) . ' centavo' . ($centavos > 1 ? 's' : '');
        }
        return $out . ' only';
    }
}

// ---- Totals ------------------------------------------------------------
$items = $invoice['items'] ?? [];
foreach ($items as $i => $item) {
    $items[$i]['amount'] = $item['amount'] ?? ($item['qty'] * $item['unit_price']);
}
$total   = array_sum(array_column($items, 'amount'));
$inWords = $invoice['amount_in_words'] ?? invoice_peso_words($total);

// Pad the item list so short invoices still fill the page.
$minRows   = 8;
$blankRows = max(0, $minRows - count($items));

// Shared style fragments
$label = 'font-size:7.5pt;font-weight:bold;letter-spacing:1.2px;text-transform:uppercase;color:#8A8D91;';
$th    = 'padding:7px 8px;font-size:7.5pt;font-weight:bold;letter-spacing:1px;text-transform:uppercase;color:#ffffff;';
$td    = 'padding:7px 8px;border-bottom:1px solid #E6E7E9;';
$sign  = 'border-top:1px solid #1F2124;padding-top:4px;font-size:8pt;font-weight:bold;';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>Delivery Receipt <?= esc($invoice['invoice_no']) ?> · <?= esc($company['name']) ?></title>
<style>
  @page { size: A4 portrait; margin: 13mm 14mm 11mm; }
  html, body { margin: 0; padding: 0; }
  body {
    font-family: Helvetica, Arial, sans-serif;
    font-size: 9.5pt; line-height: 1.45; color: #1F2124;
    background: #ffffff;
  }
  table { border-collapse: collapse; }
  thead { display: table-header-group; }  /* repeats on every printed page */
  tr, img { page-break-inside: avoid; break-inside: avoid; }
  .sheet { width: 100%; }
  @media screen {
    body { background: #eceded; padding: 24px 0; }
    .sheet {
      width: 210mm; min-height: 297mm; box-sizing: border-box;
      margin: 0 auto; padding: 13mm 14mm 11mm;
      background: #ffffff; box-shadow: 0 2px 10px rgba(0,0,0,.15);
    }
    .no-print { text-align: center; margin: 0 auto 16px; font-size: 9pt; }
  }
  @media print { .no-print { display: none; } }
</style>
</head>
<body>

<div class="no-print">
  <button type="button" onclick="window.print()"
    style="font:inherit;font-weight:bold;padding:8px 18px;border:0;background:#FFB606;cursor:pointer;">
    Print delivery receipt
  </button>
</div>

<div class="sheet">

  <!-- Header: Gab's Bakeshop header image, then the title and number -->
  <?= view('layouts/print_letterhead', [
      'title' => 'DELIVERY RECEIPT',
      'badge' => 'NO. ' . $invoice['invoice_no'],
      'titleSize' => '18pt',
  ]) ?>

  <!-- Billed to / order meta -->
  <table style="width:100%;margin-top:16px;">
    <tr>
      <td style="width:52%;padding-right:22px;vertical-align:top;">
        <div style="<?= $label ?>">Billed to</div>
        <div style="font-size:11pt;font-weight:bold;"><?= esc($invoice['customer_name']) ?></div>
        <div style="color:#55585C;"><?= esc($invoice['customer_address']) ?></div>
        <div style="color:#55585C;">Contact: <?= esc($invoice['customer_contact']) ?></div>
      </td>
      <td style="vertical-align:top;">
        <table style="width:100%;font-size:9pt;">
          <tr>
            <td style="color:#8A8D91;font-weight:bold;padding:2px 0;">Order No.</td>
            <td style="text-align:right;font-weight:bold;padding:2px 0;"><?= esc($invoice['order_no']) ?></td>
          </tr>
          <tr>
            <td style="color:#8A8D91;font-weight:bold;padding:2px 0;">Order Date</td>
            <td style="text-align:right;padding:2px 0;"><?= esc(invoice_fdate($invoice['order_date'])) ?></td>
          </tr>
          <tr>
            <td style="color:#8A8D91;font-weight:bold;padding:2px 0;">Payment Terms</td>
            <td style="text-align:right;padding:2px 0;"><?= esc($invoice['payment_terms']) ?></td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  <!-- Line items -->
  <table style="width:100%;margin-top:18px;font-size:9pt;">
    <thead>
      <tr style="background:#1F2124;">
        <th style="<?= $th ?>text-align:left;width:0.45in;">#</th>
        <th style="<?= $th ?>text-align:left;">Item description</th>
        <th style="<?= $th ?>text-align:right;width:0.6in;">Qty</th>
        <th style="<?= $th ?>text-align:right;width:1.05in;">Unit price</th>
        <th style="<?= $th ?>text-align:right;width:1.2in;">Amount</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $i => $item): ?>
      <tr>
        <td style="<?= $td ?>color:#8A8D91;"><?= $i + 1 ?></td>
        <td style="<?= $td ?>font-weight:bold;"><?= esc($item['description']) ?></td>
        <td style="<?= $td ?>text-align:right;"><?= esc($item['qty']) ?></td>
        <td style="<?= $td ?>text-align:right;"><?= invoice_money($item['unit_price']) ?></td>
        <td style="<?= $td ?>text-align:right;font-weight:bold;"><?= invoice_money($item['amount']) ?></td>
      </tr>
      <?php endforeach; ?>

      <?php for ($r = 0; $r < $blankRows; $r++): ?>
      <tr>
        <td style="<?= $td ?>">&nbsp;</td>
        <td style="<?= $td ?>"></td>
        <td style="<?= $td ?>"></td>
        <td style="<?= $td ?>"></td>
        <td style="<?= $td ?>"></td>
      </tr>
      <?php endfor; ?>
    </tbody>
  </table>

  <!-- Amount in words, Total, and Signatures are kept as one block so a
       print/PDF page break can only fall before all of it, never in the
       middle - never separating the total from its signatures. -->
  <div style="page-break-inside:avoid;break-inside:avoid;">

    <!-- Words + totals -->
    <table style="width:100%;margin-top:16px;">
      <tr>
        <td style="padding-right:22px;vertical-align:top;">
          <div style="<?= $label ?>">Amount in words</div>
          <div style="font-weight:bold;border-bottom:1px solid #C9CBCE;padding-bottom:3px;"><?= esc($inWords) ?></div>
          <div style="<?= $label ?>margin-top:10px;">Remarks</div>
          <div style="color:#55585C;"><?= esc($invoice['remarks'] ?: '—') ?></div>
        </td>
        <td style="width:2.7in;vertical-align:top;">
          <table style="width:100%;background:#FFB606;">
            <tr>
              <td style="padding:9px 10px;font-size:8pt;font-weight:bold;letter-spacing:1px;text-transform:uppercase;">Total due</td>
              <td style="padding:9px 10px;text-align:right;font-size:14pt;font-weight:bold;">&#8369; <?= invoice_money($total) ?></td>
            </tr>
          </table>
        </td>
      </tr>
    </table>

    <!-- Signatures -->
    <table style="width:100%;margin-top:28px;border-top:1px solid #E6E7E9;">
      <tr>
        <td style="width:25%;padding:14px 9px 0 0;vertical-align:bottom;">
          <div style="height:0.42in;"></div>
          <div style="<?= $sign ?>">Prepared by:</div>
          <div style="font-size:7.5pt;color:#8A8D91;"><?= esc($invoice['prepared_by'] ?: 'Name & signature') ?></div>
        </td>
        <td style="width:25%;padding:14px 9px 0;vertical-align:bottom;">
          <div style="height:0.42in;"></div>
          <div style="<?= $sign ?>">Delivered by:</div>
          <div style="font-size:7.5pt;color:#8A8D91;"><?= esc($invoice['delivered_by'] ?: 'Name & signature · Date') ?></div>
        </td>
        <td style="width:25%;padding:14px 9px 0;vertical-align:bottom;">
          <div style="height:0.42in;"></div>
          <div style="<?= $sign ?>">Received by:</div>
          <div style="font-size:7.5pt;color:#8A8D91;"><?= esc($invoice['received_by'] ?: 'Name & signature · Date') ?></div>
        </td>
        <td style="width:25%;padding:14px 0 0 9px;vertical-align:bottom;">
          <div style="height:0.42in;"></div>
          <div style="border-top:1px solid #ED1C24;padding-top:4px;font-size:8pt;font-weight:bold;">Approved by:</div>
          <div style="font-size:7.5pt;color:#8A8D91;"><?= esc($invoice['approved_by'] ?: 'Name & signature · Date') ?></div>
        </td>
      </tr>
    </table>

  </div>

  <!-- Footer -->
  <table style="width:100%;margin-top:12px;border-top:3px solid #FFB606;">
    <tr>
      <td style="padding-top:8px;font-size:7.5pt;color:#8A8D91;">
        This document is not to be used as an official receipt or invoice.
      </td>
      <td style="padding-top:8px;font-size:7.5pt;color:#8A8D91;text-align:right;white-space:nowrap;">
        Delivery Receipt <?= esc($invoice['invoice_no']) ?>
      </td>
    </tr>
  </table>

</div>
</body>
</html>
