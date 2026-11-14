<?php
/**
 * The block under the letterhead on a printable report, mirroring the
 * invoice's "Billed to / order details": what the report is on the left,
 * when it was generated and by whom on the right.
 * Expects $heading, $subheading and $facts (a list of [label, value]).
 */
$label = 'font-size:7.5pt;font-weight:bold;letter-spacing:1.2px;text-transform:uppercase;color:#8A8D91;';
?>
  <table style="width:100%;margin-top:16px;">
    <tr>
      <td style="width:52%;padding-right:22px;vertical-align:top;">
        <div style="<?= $label ?>">Report</div>
        <div style="font-size:11pt;font-weight:bold;"><?= esc($heading) ?></div>
        <div style="color:#55585C;"><?= esc($subheading) ?></div>
      </td>
      <td style="vertical-align:top;">
        <table style="width:100%;font-size:9pt;">
          <?php foreach ($facts as [$factLabel, $factValue]): ?>
            <tr>
              <td style="color:#8A8D91;font-weight:bold;padding:2px 0;"><?= esc($factLabel) ?></td>
              <td style="text-align:right;padding:2px 0;"><?= esc($factValue) ?></td>
            </tr>
          <?php endforeach; ?>
        </table>
      </td>
    </tr>
  </table>
