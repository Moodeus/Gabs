<?php

/**
 * nginx on lsu-source.org does not pass PATH_INFO to PHP.
 * CodeIgniter must use QUERY_STRING URLs: index.php?/route
 */
(function (): void {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if ($uri === '' || str_contains($uri, '?/')) {
        return;
    }

    if (! preg_match('~^(.*?/index\.php)/([^?#]+)$~', $uri, $matches)) {
        return;
    }

    $route = ltrim($matches[2], '/');
    if ($route === '') {
        return;
    }

    $base = $matches[1];
    $_SERVER['REQUEST_URI'] = $base . '?' . '/' . $route;
    $_SERVER['QUERY_STRING'] = '/' . $route;
})();
