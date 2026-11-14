<?php
/**
 * The block under a printable report's table, mirroring the invoice's
 * "Amount in words / Total due": a note on the left, the yellow total box on
 * the right. Expects $noteLabel, $noteText, $totalLabel and $totalValue;
 * optionally $words, shown above the note.
 */
$label = 'font-size:7.5pt;font-weight:bold;letter-spacing:1.2px;text-transform:uppercase;color:#8A8D91;';
?>
    <table style="width:100%;margin-top:16px;">
      <tr>
        <td style="padding-right:22px;vertical-align:top;">
          <?php if (!empty($words)): ?>
            <div style="<?= $label ?>">Amount in words</div>
            <div style="font-weight:bold;border-bottom:1px solid #C9CBCE;padding-bottom:3px;"><?= esc($words) ?></div>
          <?php endif; ?>
          <?php if (!empty($noteLabel)): ?>
            <div style="<?= $label ?><?= !empty($words) ? 'margin-top:10px;' : '' ?>"><?= esc($noteLabel) ?></div>
            <div style="color:#55585C;"><?= esc($noteText) ?></div>
          <?php endif; ?>
        </td>
        <td style="width:2.7in;vertical-align:top;">
          <table style="width:100%;background:#FFB606;">
            <tr>
              <td style="padding:9px 10px;font-size:8pt;font-weight:bold;letter-spacing:1px;text-transform:uppercase;"><?= esc($totalLabel) ?></td>
              <td style="padding:9px 10px;text-align:right;font-size:14pt;font-weight:bold;"><?= esc($totalValue) ?></td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
