<?php

/**
 * @see https://codeigniter.com/user_guide/extending/common.html
 */

if (! function_exists('gabs_fix_query_string_url')) {
    /**
     * Convert index.php/route paths to index.php?/route for QUERY_STRING servers.
     */
    function gabs_fix_query_string_url(string $url): string
    {
        $config = config(\Config\App::class);

        if ($config->uriProtocol !== 'QUERY_STRING') {
            return $url;
        }

        $fixed = preg_replace('~(/index\.php)/([^?#\\s]+)$~', '$1?/$2', $url);

        return is_string($fixed) ? $fixed : $url;
    }
}

if (! function_exists('site_url')) {
    function site_url($relativePath = '', ?string $scheme = null, ?\Config\App $config = null): string
    {
        if (! function_exists('service')) {
            return gabs_fix_query_string_url((string) $relativePath);
        }

        try {
            $request = service('request');
            if ($request === null) {
                return gabs_fix_query_string_url((string) $relativePath);
            }

            $currentURI = $request->getUri();
            if (! $currentURI instanceof \CodeIgniter\HTTP\SiteURI) {
                return gabs_fix_query_string_url((string) $relativePath);
            }

            return gabs_fix_query_string_url($currentURI->siteUrl($relativePath, $scheme, $config));
        } catch (Throwable) {
            return gabs_fix_query_string_url((string) $relativePath);
        }
    }
}
