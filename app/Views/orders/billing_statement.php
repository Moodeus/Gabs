<?php
/**
 * Printable Acknowledgement Receipt for one paid order - the same wording and
 * layout as Gab's Bakeshop's paper form: it acknowledges receipt of the order's
 * total as payment, with a "Received by" line, the cash/check lines to fill in
 * by hand, and the date. A half-A4 sheet (the top half of the page).
 * Rendered standalone like the other printables; "Print / Save as PDF" uses
 * the browser's print dialog.
 * Expects $statement: order_no, order_date, branch_name, total.
 */
$company = document_company();
$issued = mb_strtoupper(date('F j, Y', strtotime(database_now())));
$amountWords = mb_strtoupper(document_peso_words($statement['total']));
$amount = 'P ' . document_money($statement['total']);
$orderDate = mb_strtoupper(document_date($statement['order_date']));
// Order names start with "Order" (e.g. "Order #2026..."), and the sentence already says "the order".
$orderRef = trim(preg_replace('/^order\s*/i', '', $statement['order_no']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>Acknowledgement Receipt <?= esc($statement['order_no']) ?> · <?= esc($company['name']) ?></title>
<style>
<?= view('layouts/print_styles') ?>
  /* The form is set in a serif face, like the paper one. */
  body { font-family: "Times New Roman", Times, serif; font-size: 11pt; color: #000; }
  /* Half of an A4 page: the content is held to the top half of the printable
     area (148.5mm from the top edge of the paper, less the 25.4mm margin). */
  .half { min-height: 118mm; }  /* never clipped: a very long amount just runs a little lower */
  @media screen {
    .sheet { min-height: 0; height: 148.5mm; padding-bottom: 0; }
  }
  .title { text-align: center; font-weight: bold; font-size: 16pt; margin: 12px 0 6px; letter-spacing: 0.3px; }
  .text { line-height: 1.9; text-align: justify; margin: 0; }
  .u { font-weight: bold; text-decoration: underline; }
  .line { display: inline-block; border-bottom: 1px solid #000; }
  .fields td { padding: 2px 0; vertical-align: bottom; }
</style>
</head>
<body>

<div class="no-print">
  <button type="button" onclick="window.print()"
    style="font:inherit;font-weight:bold;padding:8px 18px;border:0;background:#FFB606;cursor:pointer;">
    Print / Save as PDF
  </button>
</div>

<div class="sheet">
  <div class="half">

    <!-- Gab's Bakeshop header on the left, the Gab Shawn Foods OPC logo on the right -->
    <?= view('layouts/print_header_image') ?>

    <div class="title">ACKNOWLEDGEMENT RECEIPT</div>

    <p class="text">
      This is to acknowledge receipt of the total amount of
      <span class="u"><?= esc($amountWords) ?> (<?= esc($amount) ?>)</span>,
      as payment for the order <span class="u"><?= esc(mb_strtoupper($orderRef)) ?></span>
      dated <span class="u"><?= esc($orderDate) ?></span>
      from <span class="u"><?= esc(mb_strtoupper($statement['branch_name'])) ?></span>.
    </p>

    <table class="fields" style="margin-top:14px;width:100%;">
      <tr>
        <td style="width:24mm;">Received by:</td>
        <td><span class="line" style="width:75mm;">&nbsp;</span></td>
      </tr>
    </table>

    <table class="fields" style="margin-top:8px;">
      <tr><td style="width:34mm;">Cash Payment:</td><td><span class="line" style="width:38mm;">&nbsp;</span></td></tr>
      <tr><td>Reference No.:</td><td><span class="line" style="width:52mm;">&nbsp;</span></td></tr>
      <tr><td>Bank:</td><td><span class="line" style="width:42mm;">&nbsp;</span></td></tr>
      <tr><td>Date of Check:</td><td><span class="line" style="width:38mm;">&nbsp;</span></td></tr>
    </table>

    <p style="margin:10px 0 0;">Date : <span class="u">&nbsp;<?= esc($issued) ?></span></p>

  </div>
</div>
</body>
</html>
