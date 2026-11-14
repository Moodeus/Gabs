<?php
/**
 * Quick check that nginx points to this "public" folder and PHP works.
 * Visit: https://gabsbakeshop.lsu-source.org/health.php
 * Delete this file after deployment is confirmed.
 */
header('Content-Type: text/plain; charset=utf-8');
echo "OK\n";
echo 'PHP: ' . PHP_VERSION . "\n";
echo 'Document root: ' . (__DIR__) . "\n";
echo 'index.php exists: ' . (is_file(__DIR__ . '/index.php') ? 'yes' : 'no') . "\n";
