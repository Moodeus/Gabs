<?php
/**
 * Page and table styles shared by the printable reports (Sales, Ingredient
 * and Product exports) - the same A4 sheet, margins and table look as the
 * order invoice. Output goes inside a <style> element.
 */
?>
  @page { size: A4 portrait; margin: 25.4mm; }
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
  .report-table { width: 100%; margin-top: 18px; font-size: 9pt; }
  .report-table th { padding: 7px 8px; font-size: 7.5pt; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; color: #ffffff; background: #1F2124; text-align: left; }
  .report-table td { padding: 7px 8px; border-bottom: 1px solid #E6E7E9; }
  .report-table td.idx { color: #8A8D91; width: 0.45in; }
  .report-table td.strong { font-weight: bold; }
  .report-table td.num, .report-table th.num { text-align: right; }
  .report-table td.empty { text-align: center; color: #8A8D91; padding: 18px 8px; }
  .low { color: #ED1C24; font-weight: bold; }
  @media screen {
    body { background: #eceded; padding: 24px 0; }
    .sheet {
      width: 210mm; min-height: 297mm; box-sizing: border-box;
      margin: 0 auto; padding: 25.4mm;
      background: #ffffff; box-shadow: 0 2px 10px rgba(0,0,0,.15);
    }
    .no-print { text-align: center; margin: 0 auto 16px; font-size: 9pt; }
  }
  @media print { .no-print { display: none; } }
