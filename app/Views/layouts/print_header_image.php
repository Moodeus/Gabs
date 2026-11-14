<?php
/**
 * The image row at the top of every printable: Gab's Bakeshop's header (logo,
 * address, Facebook page, email, phone) on the left and the Gab Shawn Foods
 * OPC "GS" logo on the right. Shared by the letterhead and the Acknowledgement.
 */
?>
  <table style="width:100%;border-collapse:collapse;">
    <tr>
      <td style="width:77%;padding:0;vertical-align:middle;">
        <img src="<?= esc(base_url('assets/images/Gabs_Header.svg')) ?>"
             alt="Gab's Bakeshop - P-7 Banadero Sulod, Ozamiz City, Misamis Occidental - gabsbakeshop01@gmail.com - 0930-809-5856"
             style="display:block;width:100%;height:auto;" />
      </td>
      <td style="padding:0 0 0 2mm;vertical-align:middle;text-align:right;">
        <img src="<?= esc(base_url('assets/images/Gabs_Logo.svg')) ?>" alt="Gab Shawn Foods OPC"
             style="display:inline-block;width:1.45in;height:auto;" />
      </td>
    </tr>
  </table>
