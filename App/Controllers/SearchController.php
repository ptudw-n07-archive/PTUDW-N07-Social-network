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

            if ($this->keywordLength($keyword) < 2) {
                $this->json(true, "Nhap toi thieu 2 ky tu.", [
                    'users' => [],
                    'posts' => [],
                    'hashtags' => []
                ]);
                return;
            }

            $posts = $this->searchModel->searchPosts($keyword);

            $this->json(true, "OK", [
                'users' => $this->searchModel->searchUsers($userId, $keyword),
                'posts' => $posts,
                'hashtags' => $this->extractHashtags($posts, $keyword)
            ]);
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

    public function record(): void {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $userId = $this->requireLoginJson();
            $keyword = $this->normalizeKeyword($_POST['keyword'] ?? $_POST['q'] ?? '');

            if ($this->keywordLength($keyword) < 2) {
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

    private function keywordLength(string $keyword): int {
        return function_exists('mb_strlen') ? mb_strlen($keyword) : strlen($keyword);
    }

    private function lower(string $value): string {
        return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
    }

    private function extractHashtags(array $posts, string $keyword): array {
        $hashtags = [];
        $needle = ltrim($this->lower($keyword), '#');

        foreach ($posts as $post) {
            preg_match_all('/#[\p{L}\p{N}_]+/u', $post['Content'] ?? '', $matches);

            foreach ($matches[0] ?? [] as $tag) {
                $tagKey = $this->lower(ltrim($tag, '#'));

                if ($needle !== '' && !str_contains($tagKey, $needle)) {
                    continue;
                }

                if (!isset($hashtags[$tagKey])) {
                    $hashtags[$tagKey] = [
                        'tag' => $tag,
                        'count' => 0
                    ];
                }

                $hashtags[$tagKey]['count']++;
            }
        }

        usort($hashtags, fn($a, $b) => $b['count'] <=> $a['count']);

        return array_slice(array_values($hashtags), 0, 5);
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

    match ($_GET['action']) {
        'search' => $controller->search(),
        'history' => $controller->history(),
        'record' => $controller->record(),
        'delete' => $controller->delete(),
        'clear' => $controller->clear(),
        default => null
    };
}
?>
