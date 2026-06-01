<?php
namespace App\Controllers;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../Config/Database.php';
require_once __DIR__ . '/../Models/SearchModel.php';

use App\Models\SearchModel;
use Database;
use Exception;

class SearchController {
    private const MIN_SEARCH_KEYWORD_LENGTH = 2;
    private const DEFAULT_SEARCH_LIMIT = 5;

    private SearchModel $searchModel;

    public function __construct() {
        $database = new Database();
        $db = $database->connect();
        $this->searchModel = new SearchModel($db);
    }

    public function search(): void {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $userId = $this->requireLoginJson();
            $keyword = $this->normalizeKeyword($_GET['q'] ?? $_POST['q'] ?? '');
            $type = $_GET['type'] ?? 'all';
            $page = max(1, (int) ($_GET['page'] ?? 1));

            if ($this->keywordLength($keyword) < self::MIN_SEARCH_KEYWORD_LENGTH) {
                $this->json(true, "Nhap toi thieu 2 ky tu.", [
                    'users' => [],
                    'posts' => [],
                    'hashtags' => [],
                    'pagination' => ['hasMore' => false, 'total' => 0]
                ]);
                return;
            }

            $perPage = $this->perPageForType($type);
            $offset = ($page - 1) * $perPage;

            $result = match ($type) {
                'users' => $this->searchTypedUsers($userId, $keyword, $perPage, $offset),
                'posts' => $this->searchTypedPosts($userId, $keyword, $perPage, $offset),
                'hashtags' => $this->searchTypedHashtags($keyword, $perPage, $offset),
                default => [
                    'users' => $this->searchModel->searchUsers($userId, $keyword, self::DEFAULT_SEARCH_LIMIT, 0),
                    'posts' => $this->searchModel->searchPosts($keyword, $userId, self::DEFAULT_SEARCH_LIMIT, 0),
                    'hashtags' => $this->searchModel->searchHashtags($keyword, self::DEFAULT_SEARCH_LIMIT, 0),
                    'pagination' => ['hasMore' => false, 'total' => 0]
                ]
            };

            $this->json(true, "OK", $result);
        } catch (Exception $e) {
            $this->json(false, $e->getMessage());
        }
    }

    public function history(): void {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $userId = $this->requireLoginJson();
            $this->json(true, "OK", [
                'history' => $this->searchModel->getHistory($userId)
            ]);
        } catch (Exception $e) {
            $this->json(false, $e->getMessage());
        }
    }

    public function suggest(): void {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $userId = $this->requireLoginJson();
            $keyword = $this->normalizeKeyword($_GET['q'] ?? '');

            if ($this->keywordLength($keyword) < 1) {
                $this->json(true, "OK", ['users' => [], 'hashtags' => []]);
                return;
            }

            $this->json(true, "OK", [
                'users' => $this->searchModel->suggestUsers($keyword, $userId),
                'hashtags' => $this->searchModel->suggestHashtags($keyword, 8)
            ]);
        } catch (Exception $e) {
            $this->json(false, $e->getMessage(), ['users' => [], 'hashtags' => []]);
        }
    }

    public function suggestHashtags(): void {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $this->requireLoginJson();
            $keyword = $this->normalizeHashtagKeyword($_GET['q'] ?? $_POST['q'] ?? $_GET['keyword'] ?? $_POST['keyword'] ?? '');

            if ($keyword === '') {
                $this->json(true, "OK", ['hashtags' => []]);
                return;
            }

            $this->json(true, "OK", [
                'hashtags' => $this->searchModel->suggestHashtags($keyword, 8)
            ]);
        } catch (Exception $e) {
            $this->json(false, $e->getMessage(), ['hashtags' => []]);
        }
    }

    public function record(): void {
        header('Content-Type: application/json; charset=utf-8');

        if (!\App\Services\CsrfService::validateRequest()) {
            $this->json(false, "Yêu cầu không hợp lệ.");
            return;
        }

        try {
            $userId = $this->requireLoginJson();
            $keyword = $this->normalizeKeyword($_POST['keyword'] ?? $_POST['q'] ?? '');

            if ($this->keywordLength($keyword) < self::MIN_SEARCH_KEYWORD_LENGTH) {
                $this->json(false, "Tu khoa can toi thieu 2 ky tu.");
                return;
            }

            $this->searchModel->saveHistory($userId, $keyword);
            $this->json(true, "Da luu lich su.");
        } catch (Exception $e) {
            $this->json(false, $e->getMessage());
        }
    }

    public function delete(): void {
        header('Content-Type: application/json; charset=utf-8');

        if (!\App\Services\CsrfService::validateRequest()) {
            $this->json(false, "Yêu cầu không hợp lệ.");
            return;
        }

        try {
            $userId = $this->requireLoginJson();
            $searchId = filter_var($_POST['searchId'] ?? null, FILTER_VALIDATE_INT);

            if (!$searchId) {
                $this->json(false, "Thieu SearchID.");
                return;
            }

            $this->searchModel->deleteHistoryItem($userId, (int) $searchId);
            $this->json(true, "Da xoa lich su.");
        } catch (Exception $e) {
            $this->json(false, $e->getMessage());
        }
    }

    public function clear(): void {
        header('Content-Type: application/json; charset=utf-8');

        if (!\App\Services\CsrfService::validateRequest()) {
            $this->json(false, "Yêu cầu không hợp lệ.");
            return;
        }

        try {
            $userId = $this->requireLoginJson();
            $this->searchModel->clearHistory($userId);
            $this->json(true, "Da xoa toan bo lich su.");
        } catch (Exception $e) {
            $this->json(false, $e->getMessage());
        }
    }

    private function requireLoginJson(): int {
        $userId = $_SESSION['user_id'] ?? null;

        if (!$userId) {
            throw new Exception("Ban chua dang nhap.");
        }

        return (int) $userId;
    }

    private function normalizeKeyword(string $keyword): string {
        return trim(preg_replace('/\s+/', ' ', $keyword) ?? '');
    }

    private function normalizeHashtagKeyword(string $keyword): string {
        $keyword = ltrim(trim($keyword), '#');
        $keyword = preg_replace('/[^\p{L}\p{N}_]/u', '', $keyword) ?? '';

        if (function_exists('mb_substr')) {
            return mb_substr($keyword, 0, 80);
        }

        return substr($keyword, 0, 80);
    }

    private function keywordLength(string $keyword): int {
        return function_exists('mb_strlen') ? mb_strlen($keyword) : strlen($keyword);
    }

    private function perPageForType(string $type): int {
        return match ($type) {
            'users' => 12,
            'posts', 'hashtags' => 10,
            default => self::DEFAULT_SEARCH_LIMIT
        };
    }

    private function searchTypedUsers(int $userId, string $keyword, int $perPage, int $offset): array {
        $total = $this->searchModel->countUsers($keyword, $userId);

        return [
            'users' => $this->searchModel->searchUsers($userId, $keyword, $perPage, $offset),
            'posts' => [],
            'hashtags' => [],
            'pagination' => $this->pagination($total, $perPage, $offset)
        ];
    }

    private function searchTypedPosts(int $userId, string $keyword, int $perPage, int $offset): array {
        $total = $this->searchModel->countPosts($keyword, $userId);

        return [
            'users' => [],
            'posts' => $this->searchModel->searchPosts($keyword, $userId, $perPage, $offset),
            'hashtags' => [],
            'pagination' => $this->pagination($total, $perPage, $offset)
        ];
    }

    private function searchTypedHashtags(string $keyword, int $perPage, int $offset): array {
        $total = $this->searchModel->countHashtags($keyword);

        return [
            'users' => [],
            'posts' => [],
            'hashtags' => $this->searchModel->searchHashtags($keyword, $perPage, $offset),
            'pagination' => $this->pagination($total, $perPage, $offset)
        ];
    }

    private function pagination(int $total, int $perPage, int $offset): array {
        return [
            'hasMore' => ($offset + $perPage) < $total,
            'total' => $total
        ];
    }

    private function json(bool $success, string $message, array $extra = []): void {
        echo json_encode(array_merge([
            'success' => $success,
            'message' => $message
        ], $extra), JSON_UNESCAPED_UNICODE);
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '') && isset($_GET['action'])) {
    $controller = new SearchController();

    match ((string) $_GET['action']) {
        'search' => $controller->search(),
        'suggest' => $controller->suggest(),
        'suggestHashtags' => $controller->suggestHashtags(),
        'history' => $controller->history(),
        'getHistory' => $controller->history(),
        'record' => $controller->record(),
        'delete' => $controller->delete(),
        'deleteHistory' => $controller->delete(),
        'clear' => $controller->clear(),
        'clearHistory' => $controller->clear(),
        default => null
    };
}
?>
