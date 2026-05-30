<?php

require_once __DIR__ . '/../App/Services/CsrfService.php';

if (!function_exists('app_load_env')) {
    function app_load_env(): array {
        static $env = null;

        if ($env !== null) {
            return $env;
        }

        $env = [];
        $envPath = dirname(__DIR__) . '/.env';

        if (is_file($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

            foreach ($lines as $line) {
                $line = trim($line);

                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }

                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                if ($key === '') {
                    continue;
                }

                $env[$key] = trim($value, "\"'");
            }
        }

        return $env;
    }
}

if (!function_exists('app_env')) {
    function app_env(string $key, ?string $default = null): ?string {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value !== false && $value !== null && $value !== '') {
            return is_string($value) ? $value : (string) $value;
        }

        $env = app_load_env();
        $value = $env[$key] ?? $default;

        return $value === null ? null : (string) $value;
    }
}

if (!function_exists('app_env_first')) {
    function app_env_first(array $keys, ?string $default = null): ?string {
        foreach ($keys as $key) {
            $value = app_env((string) $key);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return $default;
    }
}

if (!function_exists('app_is_railway_runtime')) {
    function app_is_railway_runtime(): bool {
        return app_env('RAILWAY_ENVIRONMENT') !== null
            || app_env('RAILWAY_PROJECT_ID') !== null
            || app_env('RAILWAY_SERVICE_ID') !== null;
    }
}

if (!function_exists('app_base_url')) {
    function app_base_url(): string {
        $configuredUrl = app_env('APP_URL');

        if (is_string($configuredUrl) && $configuredUrl !== '') {
            return rtrim($configuredUrl, '/') . '/';
        }

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null;
        $https = $_SERVER['HTTPS'] ?? null;
        $scheme = ($forwardedProto === 'https' || ($https !== null && $https !== 'off')) ? 'https' : 'http';

        return $scheme . '://' . $host . '/';
    }
}

if (!function_exists('app_route_path')) {
    function app_route_path(string $path = ''): string {
        $path = ltrim($path, '/');

        if ($path === '') {
            return '';
        }

        $fragment = '';
        $fragmentPosition = strpos($path, '#');
        if ($fragmentPosition !== false) {
            $fragment = substr($path, $fragmentPosition);
            $path = substr($path, 0, $fragmentPosition);
        }

        $query = '';
        $queryPosition = strpos($path, '?');
        if ($queryPosition !== false) {
            $query = substr($path, $queryPosition);
            $path = substr($path, 0, $queryPosition);
        }

        $routes = [
            'Public/index.php' => '',
            'Public/home.php' => 'home',
            'App/Views/auth/login.php' => 'login',
            'App/Views/auth/register.php' => 'register',
            'App/Views/auth/forgot-password.php' => 'forgot-password',
            'App/Views/auth/forgotpassword.php' => 'forgot-password',
            'App/Views/auth/reset-password.php' => 'reset-password',
            'App/Views/auth/account_locked.php' => 'account-locked',
            'App/Views/post/feed.php' => 'feed',
            'App/Views/post/createpost.php' => 'create-post',
            'App/Views/post/post-detail.php' => 'post-detail',
            'App/Views/profile/profile.php' => 'profile',
            'App/Views/notifications/notifications.php' => 'notifications',
            'App/Views/notifications/detail.php' => 'notification-detail',
            'App/Views/search/search.php' => 'search',
            'App/Views/hashtags/hashtag.php' => 'hashtag',
            'App/Views/admin/dashboard.php' => 'admin',
            'App/Views/admin/index.php' => 'admin',
            'App/Views/admin/profile.php' => 'admin/profile',
            'App/Views/admin/admin-profile.php' => 'admin/profile',
        ];

        if (array_key_exists($path, $routes)) {
            return $routes[$path] . $query . $fragment;
        }

        return $path . $query . $fragment;
    }
}

if (!function_exists('app_url')) {
    function app_url(string $path = ''): string {
        return app_base_url() . app_route_path($path);
    }
}

if (!function_exists('app_path')) {
    function app_path(string $path = ''): string {
        $root = dirname(__DIR__);
        if ($path === '') {
            return $root;
        }

        return $root . '/' . ltrim($path, '/');
    }
}

if (!function_exists('app_uploads_root')) {
    function app_uploads_root(string $path = ''): string {
        $configuredRoot = app_env('UPLOADS_ROOT', 'storage/uploads') ?? 'storage/uploads';

        if (str_starts_with($configuredRoot, '/')) {
            $root = rtrim($configuredRoot, '/');
        } else {
            $root = rtrim(app_path($configuredRoot), '/');
        }

        if ($path === '') {
            return $root;
        }

        return $root . '/' . ltrim($path, '/');
    }
}

if (!defined('BASE_URL')) {
    define('BASE_URL', app_base_url());
}

class Database {
    private string $host;
    private string $db_name;
    private string $username;
    private string $password;
    private ?string $port;
    private ?string $socket;
    public $conn;

    public function __construct() {
        $railwayRuntime = app_is_railway_runtime();

        $hostKeys = $railwayRuntime
            ? ['MYSQLHOST', 'MYSQL_HOST', 'DB_HOST']
            : ['DB_HOST', 'MYSQLHOST', 'MYSQL_HOST'];
        $portKeys = $railwayRuntime
            ? ['MYSQLPORT', 'MYSQL_PORT', 'DB_PORT']
            : ['DB_PORT', 'MYSQLPORT', 'MYSQL_PORT'];
        $databaseKeys = $railwayRuntime
            ? ['MYSQLDATABASE', 'MYSQL_DATABASE', 'DB_NAME', 'DB_DATABASE']
            : ['DB_NAME', 'DB_DATABASE', 'MYSQLDATABASE', 'MYSQL_DATABASE'];
        $userKeys = $railwayRuntime
            ? ['MYSQLUSER', 'MYSQL_USER', 'DB_USER', 'DB_USERNAME']
            : ['DB_USER', 'DB_USERNAME', 'MYSQLUSER', 'MYSQL_USER'];
        $passwordKeys = $railwayRuntime
            ? ['MYSQLPASSWORD', 'MYSQL_PASSWORD', 'DB_PASSWORD']
            : ['DB_PASSWORD', 'MYSQLPASSWORD', 'MYSQL_PASSWORD'];
        $socketKeys = $railwayRuntime
            ? ['MYSQL_SOCKET', 'DB_SOCKET']
            : ['DB_SOCKET', 'MYSQL_SOCKET'];

        $this->host = app_env_first($hostKeys, '100.76.147.122') ?? '100.76.147.122';
        $this->db_name = app_env_first($databaseKeys, 'db_archive') ?? 'db_archive';
        $this->username = app_env_first($userKeys, 'root') ?? 'root';
        $this->password = app_env_first($passwordKeys, '') ?? '';
        $this->port = app_env_first($portKeys);
        $this->socket = app_env_first($socketKeys);
    }

    public function connect() {
        $this->conn = null;

        if ($this->socket !== null && $this->socket !== '') {
            $dsn = 'mysql:unix_socket=' . $this->socket . ';dbname=' . $this->db_name . ';charset=utf8mb4';
        } else {
            $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=utf8mb4';
        }

        if (($this->socket === null || $this->socket === '') && $this->port !== null && $this->port !== '') {
            $dsn .= ';port=' . $this->port;
        }

        try {
            $this->conn = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_TIMEOUT => 5,
            ]);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }

        return $this->conn;
    }
}
?>