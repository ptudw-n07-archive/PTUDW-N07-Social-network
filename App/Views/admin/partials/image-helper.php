<?php

if (!function_exists('admin_image_url')) {
    function admin_image_url($path, string $fallback = 'Public/assets/img/default-avatar.jpg'): string {
        $path = str_replace('\\', '/', trim((string)($path ?? '')));

        if ($path === '') {
            return admin_image_absolute_url($fallback);
        }

        if (preg_match('#^//#', $path)) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https:' : 'http:';
            return $scheme . $path;
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $path = ltrim($path, '/');
        $pathWithoutPublic = preg_replace('#^(?:Public/)+#i', '', $path);

        if (preg_match('#^https?://#i', $pathWithoutPublic)) {
            return $pathWithoutPublic;
        }

        if (preg_match('#^(assets|uploads)/#i', $pathWithoutPublic)) {
            return admin_image_absolute_url('Public/' . $pathWithoutPublic);
        }

        return admin_image_absolute_url($path);
    }

    function admin_image_absolute_url(string $path): string {
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
    }

    function admin_avatar_error_attr(): string {
        $fallback = htmlspecialchars(admin_image_url(''), ENT_QUOTES);
        return 'data-admin-image="avatar" onerror="if(!this.dataset.adminFallbackApplied){this.dataset.adminFallbackApplied=\'1\';this.src=\'' . $fallback . '\';}else{this.style.visibility=\'hidden\';}"';
    }
}
