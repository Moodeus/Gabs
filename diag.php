<?php
/**
 * Deployment diagnostics — delete after fixing the site.
 * Visit: https://gabsbakeshop.lsu-source.org/public/diag.php
 */
header('Content-Type: text/plain; charset=utf-8');

$root = __DIR__;
$checks = [];

$checks['php_version'] = PHP_VERSION;
$checks['server'] = [
    'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? '(none)',
    'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'] ?? '(none)',
    'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'] ?? '(none)',
];

$writableDirs = ['session', 'cache', 'logs', 'uploads'];
foreach ($writableDirs as $dir) {
    $path = $root . '/writable/' . $dir;
    $checks['writable/' . $dir] = [
        'exists' => file_exists($path),
        'writable' => is_dir($path) && is_writable($path),
    ];
}

$paths = [
    'vendor/autoload.php' => $root . '/vendor/autoload.php',
    'app/Config/Paths.php' => $root . '/app/Config/Paths.php',
    '.env' => $root . '/.env',
    'app/Views/auth/login.php' => $root . '/app/Views/auth/login.php',
];

foreach ($paths as $label => $path) {
    $checks[$label] = [
        'exists' => file_exists($path),
        'readable' => is_readable($path),
    ];
}

$envLines = [];
if (is_file($root . '/.env')) {
    $envLines = file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $checks['env_keys'] = [];
    foreach ($envLines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        [$key] = array_pad(explode('=', $line, 2), 2, '');
        $checks['env_keys'][] = trim($key);
    }
}

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'gabsbakeshopoz';

foreach ($envLines as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || ! str_contains($line, '=')) {
        continue;
    }
    [$k, $v] = array_map('trim', explode('=', $line, 2));
    $v = trim($v, " \t\"'");
    match ($k) {
        'DB_HOST' => $dbHost = $v,
        'DB_USER' => $dbUser = $v,
        'DB_PASS' => $dbPass = $v,
        'DB_NAME' => $dbName = $v,
        default => null,
    };
}

try {
    $mysqli = @new mysqli($dbHost, $dbUser, $dbPass, $dbName);
    $checks['database'] = $mysqli->connect_error
        ? 'FAIL: ' . $mysqli->connect_error
        : 'OK';
    if (! $mysqli->connect_error) {
        $mysqli->close();
    }
} catch (Throwable $e) {
    $checks['database'] = 'FAIL: ' . $e->getMessage();
}

try {
    require $root . '/vendor/autoload.php';
    require $root . '/app/Config/Paths.php';
    $checks['ci_autoload'] = 'OK';
} catch (Throwable $e) {
    $checks['ci_autoload'] = 'FAIL: ' . $e->getMessage();
}

$logFiles = glob($root . '/writable/logs/log-*.log') ?: [];
if ($logFiles !== []) {
    rsort($logFiles);
    $latestLog = $logFiles[0];
    $checks['latest_log'] = basename($latestLog);
    $lines = file($latestLog) ?: [];
    $checks['recent_log_lines'] = array_slice($lines, -25);
}

echo "GABS deployment diagnostics\n";
echo str_repeat('=', 40) . "\n";
foreach ($checks as $key => $value) {
    if ($key === 'recent_log_lines') {
        echo "\n--- last 25 log lines ({$checks['latest_log']}) ---\n";
        echo implode('', $value);
        continue;
    }
    if (is_array($value)) {
        echo $key . ":\n";
        foreach ($value as $k => $v) {
            if (is_array($v)) {
                echo "  {$k}:\n";
                foreach ($v as $k2 => $v2) {
                    echo "    {$k2}: " . (is_bool($v2) ? ($v2 ? 'yes' : 'no') : (string) $v2) . "\n";
                }
            } else {
                echo "  {$k}: " . (is_bool($v) ? ($v ? 'yes' : 'no') : (string) $v) . "\n";
            }
        }
    } else {
        echo $key . ': ' . $value . "\n";
    }
}

echo "\nIf the site still shows Whoops, read recent_log_lines above.\n";
