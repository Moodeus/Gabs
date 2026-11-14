<?php
/** Footer shared by every printable document. Expects $note and $reference. */
?>
  <table style="width:100%;margin-top:12px;border-top:3px solid #FFB606;">
    <tr>
      <td style="padding-top:8px;font-size:7.5pt;color:#8A8D91;"><?= esc($note) ?></td>
      <td style="padding-top:8px;font-size:7.5pt;color:#8A8D91;text-align:right;white-space:nowrap;"><?= esc($reference) ?></td>
    </tr>
  </table>
