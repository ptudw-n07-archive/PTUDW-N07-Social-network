<?php

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

if (!function_exists('app_url')) {
    function app_url(string $path = ''): string {
        return app_base_url() . ltrim($path, '/');
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
    public $conn;

    public function __construct() {
        $this->host = app_env('DB_HOST', '100.76.147.122') ?? '100.76.147.122';
        $this->db_name = app_env('DB_NAME', 'db_archive') ?? 'db_archive';
        $this->username = app_env('DB_USER', 'root') ?? 'root';
        $this->password = app_env('DB_PASSWORD', '') ?? '';
        $this->port = app_env('DB_PORT');
    }

    public function connect() {
        $this->conn = null;

        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->db_name . ';charset=utf8mb4';
        if ($this->port !== null && $this->port !== '') {
            $dsn .= ';port=' . $this->port;
        }

        try {
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }

        return $this->conn;
    }
}
?>
