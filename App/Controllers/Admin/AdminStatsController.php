<?php
namespace App\Controllers\Admin;

use App\Controllers\AdminController;
use App\Models\AdminStatsModel;
use Exception;

class AdminStatsController {
    private AdminController $main;
    private AdminStatsModel $adminStatsModel;

    public function __construct(AdminController $main, AdminStatsModel $adminStatsModel) {
        $this->main = $main;
        $this->adminStatsModel = $adminStatsModel;
    }

    private function isAdmin(): bool {
        return $this->main->isAdmin();
    }

    private function jsonResponse(bool $success, string $message, $data = null): void {
        $this->main->jsonResponse($success, $message, $data);
    }

    public function overviewStats(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        try {
            $this->jsonResponse(true, 'Lấy thống kê tổng quan thành công', $this->adminStatsModel->getOverviewStats());
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Không thể lấy thống kê tổng quan.');
        }
    }

    public function overviewDetail(): void {
        header('Content-Type: application/json; charset=utf-8');

        if (!$this->isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền quản trị viên.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $metric = isset($_GET['metric']) ? trim((string)$_GET['metric']) : '';
        try {
            $detail = $this->adminStatsModel->getOverviewDetail($metric);
            if ($detail === null) {
                echo json_encode(['success' => false, 'message' => 'Metric không hợp lệ.'], JSON_UNESCAPED_UNICODE);
                return;
            }

            echo json_encode([
                'success' => true,
                'title' => $detail['title'],
                'columns' => $detail['columns'],
                'data' => $detail['data']
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Không thể lấy chi tiết tổng quan.'], JSON_UNESCAPED_UNICODE);
        }
    }

    public function statisticsRankings(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        try {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
            if (!in_array($limit, [5, 10, 15, 20], true)) {
                $limit = 5;
            }

            $this->jsonResponse(true, 'Lấy top ranking thành công', $this->adminStatsModel->getStatisticsTopRankings($limit));
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Không thể lấy top ranking.');
        }
    }

    public function statisticsCharts(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        try {
            $this->jsonResponse(true, 'Lấy dữ liệu biểu đồ thành công', $this->adminStatsModel->getStatisticsChartData());
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Không thể lấy dữ liệu biểu đồ.');
        }
    }

    public function statisticsInsights(): void {
        if (!$this->isAdmin()) {
            $this->jsonResponse(false, 'Bạn không có quyền quản trị viên.');
            return;
        }

        try {
            $this->jsonResponse(true, 'Lấy activity insights thành công', $this->adminStatsModel->getStatisticsActivityInsights());
        } catch (Exception $e) {
            $this->jsonResponse(false, 'Không thể lấy activity insights.');
        }
    }

}
?>
