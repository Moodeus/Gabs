<?php
/**
 * Header shared by every printable document: Gab's Bakeshop's header image
 * (logo, address, Facebook page, email, phone) with the Gab Shawn Foods OPC
 * logo on the right, then a yellow rule with the document's title on the
 * left and a number/date badge on the right.
 * Expects $title and $badge; optionally $titleSize.
 */
$titleSize = $titleSize ?? '18pt';
?>
  <?= view('layouts/print_header_image') ?>
  <table style="width:100%;margin-top:6px;border-top:3px solid #FFB606;">
    <tr>
      <td style="padding-top:10px;vertical-align:middle;">
        <span style="font-size:<?= esc($titleSize) ?>;font-weight:bold;letter-spacing:2px;color:#ED1C24;line-height:1.1;"><?= esc($title) ?></span>
      </td>
      <td style="padding-top:10px;vertical-align:middle;text-align:right;">
        <span style="background:#FFB606;padding:4px 10px;font-size:9pt;font-weight:bold;letter-spacing:0.5px;white-space:nowrap;">
          <?= esc($badge) ?>
        </span>
      </td>
    </tr>
  </table>
