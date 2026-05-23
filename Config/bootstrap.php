<?php

if (!function_exists('env_value')) {
    function env_value($keys, $default = null) {
        $keys = is_array($keys) ? $keys : [$keys];

        foreach ($keys as $key) {
            if (!is_string($key) || $key === '') {
                continue;
            }

            $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
            if ($value !== false && $value !== null && $value !== '') {
                return $value;
            }
        }

        return $default;
    }
}

if (!function_exists('app_base_url')) {
    function app_base_url() {
        $configuredUrl = env_value(['APP_URL']);
        if (is_string($configuredUrl)) {
            $configuredUrl = trim($configuredUrl);
        }

        if (is_string($configuredUrl) && $configuredUrl !== '') {
            if (!preg_match('#^https?://#i', $configuredUrl)) {
                $configuredUrl = 'https://' . $configuredUrl;
            }

            return rtrim($configuredUrl, '/') . '/';
        }

        $railwayDomain = env_value(['RAILWAY_PUBLIC_DOMAIN']);
        if (is_string($railwayDomain) && $railwayDomain !== '') {
            if (!preg_match('#^https?://#i', $railwayDomain)) {
                $railwayDomain = 'https://' . $railwayDomain;
            }

            return rtrim($railwayDomain, '/') . '/';
        }

        $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null;
        $forwardedHost = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? null;
        $https = $_SERVER['HTTPS'] ?? null;
        $scheme = ($forwardedProto && $forwardedProto !== 'off') || ($https && $https !== 'off')
            ? 'https'
            : 'http';
        $host = $forwardedHost ?: ($_SERVER['HTTP_HOST'] ?? 'localhost:3000');

        if ($scheme === 'http' && is_string($host) && str_contains($host, 'railway.app')) {
            $scheme = 'https';
        }

        return $scheme . '://' . $host . '/';
    }
}

if (!defined('BASE_URL')) {
    define('BASE_URL', app_base_url());
}
