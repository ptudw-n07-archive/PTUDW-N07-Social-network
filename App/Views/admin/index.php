<?php
require_once __DIR__ . '/../../../Config/Database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id']) || (int)($_SESSION['role_id'] ?? 0) !== 1) {
    header('Location: ' . app_url('App/Views/auth/login.php'));
    exit();
}

/** @var array $stats */
/** @var array $reports */
/** @var array $members */
/** @var array $roles */
/** @var int|null $currentAdminId */
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive - Management Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
    <link class="router-css" rel="stylesheet" href="<?php echo BASE_URL; ?>Public/assets/CSS/style.css">
    <link class="router-css" rel="stylesheet" href="<?php echo BASE_URL; ?>Public/assets/CSS/admin-style.css">
</head>

<body class="admin-body">
    <header class="archive-header">
        <div class="container-fluid px-4 px-lg-5">
            <div class="row align-items-center py-3">
                <div class="col-4 d-flex align-items-center">
                    <div class="brand-logo">ARCHIVE</div>
                </div>
                <div class="col-4 d-flex justify-content-center align-items-center">
                    <div class="header-badge"><i class="bi bi-stars"></i></div>
                </div>
                <div class="col-4 d-flex justify-content-end align-items-center gap-3">
                    <div class="d-none d-md-flex align-items-center gap-2 me-2">
                        <span class="text-muted small fw-bold">Quản trị viên</span>
                        <div class="admin-profile-icon"><i class="bi bi-person-badge-fill"></i></div>
                    </div>
                    <button id="logoutBtn" class="header-logout-btn" type="button" data-logout-url="<?php echo BASE_URL; ?>App/Controllers/AuthController.php?action=logout">
                        <i class="bi bi-box-arrow-right"></i> <span>Đăng xuất</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <main class="container-fluid admin-main py-5">
        <div class="text-center mb-5">
            <h1 class="management-title">Trung tâm điều khiển</h1>
            <p class="management-subtitle">Nơi điều phối và lưu giữ những khoảnh khắc của Archive.</p>
        </div>

        <div class="d-flex justify-content-center mb-5">
            <ul class="nav nav-pills custom-admin-tabs" id="adminTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab"><i class="bi bi-grid-1x2 me-2"></i>Tổng quan</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#statistics" type="button" role="tab"><i class="bi bi-bar-chart-line me-2"></i>Thống kê</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#reports" type="button" role="tab"><i class="bi bi-shield-check me-2"></i>Kiểm duyệt</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#content" type="button" role="tab"><i class="bi bi-collection me-2"></i>Nội dung</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#members" type="button" role="tab"><i class="bi bi-person-badge me-2"></i>Thành viên</button>
                </li>
            </ul>
        </div>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="overview" role="tabpanel">
                <?php
                    $overviewCards = [
                        ['key' => 'totalUsers', 'label' => 'Tổng thành viên', 'icon' => 'bi-people', 'value' => $stats['totalUsers'] ?? 0],
                        ['key' => 'activeUsers', 'label' => 'Tài khoản hoạt động', 'icon' => 'bi-person-check', 'value' => $stats['activeUsers'] ?? 0],
                        ['key' => 'lockedUsers', 'label' => 'Tài khoản bị khóa', 'icon' => 'bi-person-lock', 'value' => $stats['lockedUsers'] ?? 0],
                        ['key' => 'totalPosts', 'label' => 'Tổng bài viết', 'icon' => 'bi-file-earmark-post', 'value' => $stats['totalPosts'] ?? 0],
                        ['key' => 'visiblePosts', 'label' => 'Bài viết hiển thị', 'icon' => 'bi-eye', 'value' => $stats['visiblePosts'] ?? 0],
                        ['key' => 'hiddenPosts', 'label' => 'Bài viết đã ẩn', 'icon' => 'bi-eye-slash', 'value' => $stats['hiddenPosts'] ?? 0],
                        ['key' => 'totalComments', 'label' => 'Tổng bình luận', 'icon' => 'bi-chat-dots', 'value' => $stats['totalComments'] ?? 0],
                        ['key' => 'hiddenComments', 'label' => 'Bình luận đã ẩn', 'icon' => 'bi-chat-square-text', 'value' => $stats['hiddenComments'] ?? 0],
                        ['key' => 'pendingReports', 'label' => 'Report chờ duyệt', 'icon' => 'bi-exclamation-octagon', 'value' => $stats['pendingReports'] ?? ($stats['reports'] ?? 0), 'danger' => true],
                        ['key' => 'totalHashtags', 'label' => 'Tổng hashtag', 'icon' => 'bi-hash', 'value' => $stats['totalHashtags'] ?? 0],
                    ];
                ?>
                <div class="overview-header">
                    <div>
                        <h5>Tổng quan hệ thống</h5>
                    </div>
                    <div class="admin-report-actions">
                        <small>Cập nhật lần cuối: <strong id="overviewLastUpdated"><?php echo htmlspecialchars($stats['lastUpdated'] ?? ''); ?></strong></small>
                        <button type="button" class="btn btn-outline-brown btn-sm" id="printOverviewBtn"><i class="bi bi-printer me-1"></i>In báo cáo</button>
                        <button type="button" class="btn btn-pink-admin btn-sm" id="exportOverviewCsvBtn"><i class="bi bi-filetype-csv me-1"></i>Xuất CSV</button>
                    </div>
                </div>
                <div class="row g-3 d-flex align-items-stretch overview-stat-grid" id="overviewStatsGrid">
                    <?php foreach ($overviewCards as $card): ?>
                    <div class="col-12 col-sm-6 col-lg-3 overview-stat-col">
                        <div class="admin-stat-card overview-stat-card">
                            <i class="bi <?php echo $card['icon']; ?> mb-2 <?php echo !empty($card['danger']) ? 'text-danger' : 'pink-icon'; ?>"></i>
                            <span class="stat-label"><?php echo htmlspecialchars($card['label']); ?></span>
                            <h2 class="stat-value <?php echo !empty($card['danger']) ? 'text-danger' : ''; ?>" data-overview-stat="<?php echo $card['key']; ?>"><?php echo number_format((int)$card['value']); ?></h2>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="statistics" role="tabpanel">
                <div class="statistics-shell">
                    <div class="admin-tab-toolbar">
                        <ul class="nav nav-pills statistics-subtabs" id="statisticsSubTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#statistics-ranking" type="button" role="tab">Top Ranking</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#statistics-charts" type="button" role="tab">Biểu đồ</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#statistics-insights" type="button" role="tab">Activity Insights</button>
                            </li>
                        </ul>
                        <div class="admin-report-actions">
                            <button type="button" class="btn btn-outline-brown btn-sm" id="printStatisticsBtn"><i class="bi bi-printer me-1"></i>In báo cáo</button>
                            <button type="button" class="btn btn-pink-admin btn-sm" id="exportStatisticsCsvBtn"><i class="bi bi-filetype-csv me-1"></i>Xuất CSV</button>
                        </div>
                    </div>

                    <div class="tab-content statistics-subtab-content">
                        <div class="tab-pane fade show active" id="statistics-ranking" role="tabpanel">
                            <section class="statistics-section">
                                <div class="statistics-section-heading">
                                    <div>
                                        <span>Dữ liệu nổi bật theo tương tác và báo cáo</span>
                                    </div>
                                    <select id="statisticsRankingLimit" class="form-select admin-control statistics-limit-select" aria-label="Chọn số lượng top ranking">
                                        <option value="5" selected>Top 5</option>
                                        <option value="10">Top 10</option>
                                        <option value="15">Top 15</option>
                                        <option value="20">Top 20</option>
                                    </select>
                                </div>
                                <div class="statistics-ranking-grid">
                                    <div class="statistics-panel statistics-panel-wide">
                                        <h6>Top bài viết theo lượt like</h6>
                                        <div id="topPostsRanking" class="statistics-list statistics-list-left loading-state">Đang tải...</div>
                                    </div>
                                    <div class="statistics-ranking-row">
                                        <div class="statistics-panel">
                                            <h6>Top user theo followers</h6>
                                            <div id="topUsersRanking" class="statistics-list statistics-list-left loading-state">Đang tải...</div>
                                        </div>
                                        <div class="statistics-panel">
                                            <h6>Top hashtag trending</h6>
                                            <div id="topHashtagsRanking" class="statistics-list statistics-list-left loading-state">Đang tải...</div>
                                        </div>
                                        <div class="statistics-panel">
                                            <h6>Top user bị report</h6>
                                            <div id="topReportedUsersRanking" class="statistics-list statistics-list-left loading-state">Đang tải...</div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <div class="tab-pane fade" id="statistics-charts" role="tabpanel">
                            <section class="statistics-section">
                                <div class="statistics-section-heading">
                                    <div>
                                        <span>Theo dõi xu hướng trong 7 ngày gần nhất</span>
                                    </div>
                                </div>
                                <div class="statistics-chart-grid">
                                    <div class="statistics-panel chart-panel">
                                        <h6>Bài viết theo ngày</h6>
                                        <canvas id="postsByDayChart"></canvas>
                                    </div>
                                    <div class="statistics-panel chart-panel">
                                        <h6>Người dùng đăng ký theo ngày</h6>
                                        <canvas id="usersByDayChart"></canvas>
                                    </div>
                                    <div class="statistics-panel chart-panel">
                                        <h6>Tỷ lệ trạng thái report</h6>
                                        <canvas id="reportStatusChart"></canvas>
                                    </div>
                                    <div class="statistics-panel chart-panel">
                                        <h6>Tỷ lệ bài viết hiển thị/đã ẩn</h6>
                                        <canvas id="postVisibilityChart"></canvas>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <div class="tab-pane fade" id="statistics-insights" role="tabpanel">
                            <section class="statistics-section">
                                <div class="statistics-section-heading">
                                    <div>
                                        <span>Các tín hiệu hoạt động đáng chú ý</span>
                                    </div>
                                </div>
                                <div class="statistics-insight-grid">
                                    <div class="statistics-panel">
                                        <h6>User hoạt động nhiều nhất</h6>
                                        <div id="mostActiveUsersInsight" class="statistics-list statistics-list-left loading-state">Đang tải...</div>
                                    </div>
                                    <div class="statistics-panel">
                                        <h6>Khung giờ đăng bài cao nhất</h6>
                                        <div id="peakPostHourInsight" class="statistics-list statistics-list-left loading-state">Đang tải...</div>
                                    </div>
                                    <div class="statistics-panel">
                                        <h6>Hashtag nổi bật gần đây</h6>
                                        <div id="recentHashtagsInsight" class="statistics-list statistics-list-left loading-state">Đang tải...</div>
                                    </div>
                                    <div class="statistics-panel">
                                        <h6>Report mới nhất</h6>
                                        <div id="latestReportsInsight" class="statistics-list statistics-list-left loading-state">Đang tải...</div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="reports" role="tabpanel">
                <div class="admin-table-container report-table-container">
                    <div class="admin-tab-toolbar mb-3">
                        <div class="content-toolbar mb-0">
                            <div class="content-search-wrap">
                                <i class="bi bi-search"></i>
                                <input type="search" id="reportSearchInput" class="form-control admin-control" placeholder="Tìm ReportID, người báo cáo, đối tượng, lý do">
                            </div>
                            <select id="reportStatusFilter" class="form-select admin-control content-filter">
                                <option value="">Tất cả trạng thái</option>
                                <option value="pending">Chờ duyệt</option>
                                <option value="resolved">Đã xử lý</option>
                            </select>
                        </div>
                        <div class="admin-report-actions">
                            <button type="button" class="btn btn-outline-brown btn-sm" id="printReportsBtn"><i class="bi bi-printer me-1"></i>In báo cáo</button>
                            <button type="button" class="btn btn-pink-admin btn-sm" id="exportReportsCsvBtn"><i class="bi bi-filetype-csv me-1"></i>Xuất CSV</button>
                        </div>
                    </div>
                    <div class="table-responsive report-table-responsive">
                    <table class="table align-middle report-table">
                        <colgroup>
                            <col class="report-col-target">
                            <col class="report-col-reason">
                            <col class="report-col-details">
                            <col class="report-col-time">
                            <col class="report-col-status">
                            <col class="report-col-actions">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>Đối tượng bị báo cáo</th>
                                <th>Lý do vi phạm</th>
                                <th>Nội dung báo cáo</th>
                                <th class="text-center">Thời gian gửi</th>
                                <th class="text-center">Trạng thái</th>
                                <th class="text-center">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($reports)): ?>
                                <?php foreach($reports as $r): ?>
                                <?php
                                    $reportExportData = [
                                        'ReportID' => $r['id'],
                                        'Reporter' => $r['reporter'] ?? '',
                                        'ReportedUser' => $r['user'] ?? '',
                                        'ReportType' => $r['type'] ?? '',
                                        'Reason' => $r['reason'] ?? '',
                                        'Status' => $r['status'] ?? '',
                                        'StatusKey' => $r['statusKey'] ?? '',
                                        'CreatedAt' => $r['time'] ?? ''
                                    ];
                                ?>
                                <tr id="report-row-<?php echo $r['id']; ?>" data-report-id="<?php echo $r['id']; ?>" data-report='<?php echo htmlspecialchars(json_encode($reportExportData, JSON_UNESCAPED_UNICODE), ENT_QUOTES); ?>' data-details="<?php echo htmlspecialchars($r['details'] ?? '', ENT_QUOTES); ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <img src="<?php echo BASE_URL . $r['avatar']; ?>" alt="avatar" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($r['user']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($r['type']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="report-reason-cell"><span class="small"><?php echo htmlspecialchars($r['reason']); ?></span></td>
                                    <td class="report-detail-cell">
                                        <?php $detailText = trim($r['details'] ?? ''); ?>
                                        <?php if ($detailText !== ''): ?>
                                            <button type="button" class="small report-details-text text-truncate btn-report-detail-link" data-report-id="<?php echo $r['id']; ?>" title="<?php echo htmlspecialchars($detailText, ENT_QUOTES); ?>"><?php echo htmlspecialchars(mb_strimwidth($detailText, 0, 120, '...')); ?></button>
                                        <?php else: ?>
                                            <button type="button" class="small report-details-text btn-report-detail-link" data-report-id="<?php echo $r['id']; ?>">Không có chi tiết</button>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted report-time-cell"><?php echo htmlspecialchars($r['time']); ?></td>
                                    <td class="report-status-cell">
                                        <?php if ($r['status'] === 'Chờ duyệt'): ?>
                                            <span class="badge rounded-pill bg-warning text-dark report-status-badge">Chờ duyệt</span>
                                        <?php else: ?>
                                            <span class="badge rounded-pill bg-success text-white report-status-badge">Đã xử lý</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center report-actions">
                                        <?php if ($r['status'] === 'Chờ duyệt'): ?>
                                            <div class="report-actions-group">
                                                <button type="button" class="btn btn-outline-brown btn-sm btn-report-detail btn-icon-detail" data-report-id="<?php echo $r['id']; ?>" title="Xem chi tiết" aria-label="Xem chi tiết"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-outline-secondary btn-sm" onclick="handleReportAction(<?php echo $r['id']; ?>, 'ignore')">Bỏ qua</button>
                                                <button class="btn btn-danger btn-sm" onclick="handleReportAction(<?php echo $r['id']; ?>, 'hide')">Ẩn nội dung</button>
                                                <button class="btn btn-warning btn-sm text-white" onclick="handleReportAction(<?php echo $r['id']; ?>, 'warn')">Cảnh cáo</button>
                                            </div>
                                        <?php else: ?>
                                            <div class="report-actions-group is-completed">
                                                <button type="button" class="btn btn-outline-brown btn-sm btn-report-detail btn-icon-detail" data-report-id="<?php echo $r['id']; ?>" title="Xem chi tiết" aria-label="Xem chi tiết"><i class="bi bi-eye"></i></button>
                                                <span class="report-action-completed"><i class="bi bi-check2-all"></i> Hoàn tất</span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">Hiện tại hệ thống sạch sẽ, chưa có báo cáo vi phạm nào!</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="content" role="tabpanel">
                <div class="admin-table-container content-admin-container">
                    <ul class="nav nav-pills content-admin-tabs mb-3" id="contentAdminTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#content-posts" type="button" role="tab">Bài viết</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#content-comments" type="button" role="tab">Bình luận</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#content-hashtags" type="button" role="tab">Hashtag</button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="content-posts" role="tabpanel">
                            <div class="content-toolbar">
                                <div class="content-search-wrap">
                                    <i class="bi bi-search"></i>
                                    <input type="search" id="contentPostSearch" class="form-control admin-control" placeholder="Tìm Username, họ tên, email hoặc nội dung bài viết">
                                </div>
                                <select id="contentPostStatusFilter" class="form-select admin-control content-filter">
                                    <option value="">Tất cả trạng thái</option>
                                    <option value="0">Hiển thị</option>
                                    <option value="1">Đã ẩn</option>
                                </select>
                                <select id="contentPostPrivacyFilter" class="form-select admin-control content-filter">
                                    <option value="">Tất cả quyền riêng tư</option>
                                    <option value="public">public</option>
                                    <option value="private">private</option>
                                </select>
                                <div class="admin-report-actions ms-lg-auto">
                                    <button type="button" class="btn btn-outline-brown btn-sm" id="printContentPostsBtn"><i class="bi bi-printer me-1"></i>In báo cáo</button>
                                    <button type="button" class="btn btn-pink-admin btn-sm" id="exportContentPostsCsvBtn"><i class="bi bi-filetype-csv me-1"></i>Xuất CSV</button>
                                </div>
                            </div>
                            <div class="table-responsive content-table-responsive">
                                <table class="table align-middle content-table">
                                    <thead>
                                        <tr>
                                            <th>PostID</th>
                                            <th>Tác giả</th>
                                            <th>Nội dung</th>
                                            <th>Ảnh</th>
                                            <th>CreatedAt</th>
                                            <th>Privacy</th>
                                            <th>Trạng thái</th>
                                            <th>Tương tác</th>
                                            <th class="text-end">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody id="contentPostsTableBody">
                                        <tr><td colspan="9" class="text-center text-muted py-4">Đang tải dữ liệu...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="content-comments" role="tabpanel">
                            <div class="content-toolbar">
                                <div class="content-search-wrap">
                                    <i class="bi bi-search"></i>
                                    <input type="search" id="contentCommentSearch" class="form-control admin-control" placeholder="Tìm người bình luận, nội dung comment hoặc bài viết gốc">
                                </div>
                                <select id="contentCommentStatusFilter" class="form-select admin-control content-filter">
                                    <option value="">Tất cả trạng thái</option>
                                    <option value="0">Hiển thị</option>
                                    <option value="1">Đã ẩn</option>
                                </select>
                                <div class="admin-report-actions ms-lg-auto">
                                    <button type="button" class="btn btn-outline-brown btn-sm" id="printContentCommentsBtn"><i class="bi bi-printer me-1"></i>In báo cáo</button>
                                    <button type="button" class="btn btn-pink-admin btn-sm" id="exportContentCommentsCsvBtn"><i class="bi bi-filetype-csv me-1"></i>Xuất CSV</button>
                                </div>
                            </div>
                            <div class="table-responsive content-table-responsive">
                                <table class="table align-middle content-table">
                                    <thead>
                                        <tr>
                                            <th>CommentID</th>
                                            <th>Người bình luận</th>
                                            <th>Bình luận</th>
                                            <th>Bài viết gốc</th>
                                            <th>Tác giả bài viết</th>
                                            <th>CreatedAt</th>
                                            <th>Parent</th>
                                            <th>Trạng thái</th>
                                            <th class="text-end">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody id="contentCommentsTableBody">
                                        <tr><td colspan="9" class="text-center text-muted py-4">Đang tải dữ liệu...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="content-hashtags" role="tabpanel">
                            <div class="content-toolbar">
                                <div class="content-search-wrap">
                                    <i class="bi bi-search"></i>
                                    <input type="search" id="contentHashtagSearch" class="form-control admin-control" placeholder="Tìm HashtagName">
                                </div>
                                <select id="contentHashtagStatusFilter" class="form-select admin-control content-filter">
                                    <option value="">Tất cả trạng thái</option>
                                    <option value="0">Hiển thị</option>
                                    <option value="1">Đã ẩn</option>
                                </select>
                                <div class="admin-report-actions ms-lg-auto">
                                    <button type="button" class="btn btn-outline-brown btn-sm" id="printContentHashtagsBtn"><i class="bi bi-printer me-1"></i>In báo cáo</button>
                                    <button type="button" class="btn btn-pink-admin btn-sm" id="exportContentHashtagsCsvBtn"><i class="bi bi-filetype-csv me-1"></i>Xuất CSV</button>
                                </div>
                            </div>
                            <div class="table-responsive content-table-responsive">
                                <table class="table align-middle content-table hashtag-admin-table">
                                    <thead>
                                        <tr>
                                            <th>HashtagID</th>
                                            <th>HashtagName</th>
                                            <th>UsageCount</th>
                                            <th>CreatedAt</th>
                                            <th>Trạng thái</th>
                                            <th>Số bài viết</th>
                                            <th class="text-end">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody id="contentHashtagsTableBody">
                                        <tr><td colspan="7" class="text-center text-muted py-4">Đang tải dữ liệu...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="members" role="tabpanel">
                <div class="admin-table-container member-table-container">
                    <div class="member-toolbar d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center mb-3">
                        <div class="d-flex flex-column flex-md-row gap-2 flex-grow-1">
                            <div class="member-search-wrap">
                                <i class="bi bi-search"></i>
                                <input type="search" id="memberSearchInput" class="form-control admin-control member-search-input" placeholder="Tìm Username, họ tên hoặc email">
                            </div>
                            <select id="memberRoleFilter" class="form-select admin-control member-role-filter">
                                <option value="">Tất cả vai trò</option>
                                <?php foreach (($roles ?? []) as $role): ?>
                                    <option value="<?php echo $role['RoleID']; ?>"><?php echo htmlspecialchars($role['RoleName']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="d-flex gap-2 justify-content-end">
                            <button type="button" class="btn btn-outline-brown btn-sm" id="printMembersBtn"><i class="bi bi-printer me-1"></i>In báo cáo</button>
                            <button type="button" class="btn btn-pink-admin btn-sm" id="exportMembersCsvBtn"><i class="bi bi-filetype-csv me-1"></i>Xuất CSV</button>
                        </div>
                    </div>

                    <div class="table-responsive member-table-responsive">
                        <table class="table align-middle member-table">
                            <thead>
                                <tr>
                                    <th>Thành viên</th>
                                    <th class="text-center">Vai trò</th>
                                    <th class="text-center">Thống kê</th>
                                    <th class="text-center">Ngày tạo</th>
                                    <th class="text-center">Trạng thái</th>
                                    <th class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="membersTableBody">
                                <?php if(!empty($members)): ?>
                                    <?php foreach($members as $m): ?>
                                    <?php $isSelf = (int)$m['UserID'] === (int)($currentAdminId ?? 0); ?>
                                    <tr id="member-row-<?php echo $m['UserID']; ?>" data-user-id="<?php echo $m['UserID']; ?>" data-is-active="<?php echo (int)$m['IsActive']; ?>">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    <img src="<?php echo BASE_URL . $m['avatar']; ?>" alt="avatar" class="rounded-circle border" style="width: 40px; height: 40px; object-fit: cover; border-color: rgba(121, 91, 74, 0.15) !important;">
                                                </div>
                                                <div>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($m['name']); ?></div>
                                                    <small class="text-muted">@<?php echo htmlspecialchars($m['Username']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="small text-muted member-role text-center"><?php echo htmlspecialchars($m['RoleName']); ?></td>
                                        <td class="small text-center member-stats-cell">
                                            <span class="member-count-pill"><i class="bi bi-file-earmark-post"></i><?php echo (int)$m['PostCount']; ?></span>
                                            <span class="member-count-pill danger"><i class="bi bi-flag"></i><?php echo (int)$m['ReportCount']; ?></span>
                                        </td>
                                        <td class="small text-center"><?php echo htmlspecialchars($m['joined']); ?></td>
                                        <td class="member-status text-center">
                                            <?php if ((int)$m['IsActive'] === 0): ?>
                                                <span class="badge rounded-pill bg-danger text-white px-2.5 py-1 text-xs fw-medium">Bị khóa</span>
                                            <?php else: ?>
                                                <span class="badge rounded-pill bg-success text-white px-2.5 py-1 text-xs fw-medium">Hoạt động</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="member-actions-group">
                                                <button type="button" class="btn btn-outline-brown btn-sm btn-member-detail btn-icon-detail" data-member='<?php echo htmlspecialchars(json_encode($m, JSON_UNESCAPED_UNICODE), ENT_QUOTES); ?>' title="Xem chi tiết" aria-label="Xem chi tiết"><i class="bi bi-eye"></i></button>
                                                <button type="button" class="btn btn-outline-brown btn-sm btn-edit-role" data-user-id="<?php echo $m['UserID']; ?>" data-user-name="<?php echo htmlspecialchars($m['name'], ENT_QUOTES); ?>" data-role-id="<?php echo $m['RoleID']; ?>" data-role-name="<?php echo htmlspecialchars($m['RoleName'], ENT_QUOTES); ?>" <?php echo $isSelf ? 'disabled title="Không thể thao tác với chính tài khoản đang đăng nhập"' : ''; ?>>Sửa</button>
                                                <button type="button" class="btn btn-sm btn-toggle-active <?php echo (int)$m['IsActive'] === 1 ? 'btn-outline-danger' : 'btn-pink-admin'; ?>" data-user-id="<?php echo $m['UserID']; ?>" data-user-name="<?php echo htmlspecialchars($m['name'], ENT_QUOTES); ?>" data-is-active="<?php echo (int)$m['IsActive']; ?>" <?php echo $isSelf ? 'disabled title="Không thể thao tác với chính tài khoản đang đăng nhập"' : ''; ?>><?php echo (int)$m['IsActive'] === 1 ? 'Khóa' : 'Mở khóa'; ?></button>
                                            </div>
                                            <?php if ($isSelf): ?>
                                                <small class="text-muted d-block mt-1">Không thể thao tác với chính tài khoản đang đăng nhập</small>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center text-muted py-4">Không tìm thấy thành viên phù hợp</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <div id="adminModal" class="admin-modal d-none" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="admin-modal-backdrop" data-admin-modal-close></div>
        <div class="admin-modal-container">
            <div class="admin-modal-card">
                <div class="admin-modal-header">
                    <h5 class="admin-modal-title">Thông báo</h5>
                    <button type="button" class="admin-modal-close" data-admin-modal-close aria-label="Đóng">&times;</button>
                </div>
                <div class="admin-modal-body">
                    <p class="admin-modal-message">Nội dung sẽ hiển thị tại đây.</p>
                </div>
                <div class="admin-modal-actions">
                    <button type="button" class="btn btn-outline-brown admin-modal-cancel" data-admin-modal-cancel>Hủy</button>
                    <button type="button" class="btn btn-pink-admin admin-modal-confirm" data-admin-modal-confirm>Xác nhận</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="adminNoteModal" tabindex="-1" aria-labelledby="adminNoteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content admin-bootstrap-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="adminNoteModalLabel">Ghi chú xử lý báo cáo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="adminNoteTextarea" class="form-label">Ghi chú của quản trị viên</label>
                        <textarea id="adminNoteTextarea" class="form-control admin-control" rows="4" placeholder="Nhập ghi chú xử lý báo cáo..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-pink-admin" id="adminNoteSaveBtn">Xác nhận</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="reportDetailModal" tabindex="-1" aria-labelledby="reportDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content admin-bootstrap-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="reportDetailModalLabel">Chi tiết báo cáo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div id="reportDetailLoading" class="text-center text-muted py-4 d-none">Đang tải chi tiết báo cáo...</div>
                    <div id="reportDetailError" class="alert alert-danger d-none" role="alert"></div>
                    <div id="reportDetailContent" class="report-detail-content"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editRoleModal" tabindex="-1" aria-labelledby="editRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content admin-bootstrap-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="editRoleModalLabel">Cập nhật vai trò thành viên</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Đang chỉnh sửa: <strong id="editRoleUserName"></strong></p>
                    <div class="mb-3">
                        <label for="editRoleSelect" class="form-label">Chọn vai trò</label>
                        <select id="editRoleSelect" class="form-select admin-control">
                            <?php foreach (($roles ?? []) as $role): ?>
                                <option value="<?php echo $role['RoleID']; ?>"><?php echo htmlspecialchars((int)$role['RoleID'] === 2 ? 'Thành viên' : $role['RoleName']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="editRoleError" class="alert alert-danger d-none" role="alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-pink-admin" id="editRoleSaveBtn">Lưu thay đổi</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="memberDetailModal" tabindex="-1" aria-labelledby="memberDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content admin-bootstrap-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="memberDetailModalLabel">Chi tiết thành viên</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div id="memberDetailContent" class="member-detail-grid"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="contentDetailModal" tabindex="-1" aria-labelledby="contentDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content admin-bootstrap-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="contentDetailModalLabel">Chi tiết nội dung</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div id="contentDetailLoading" class="text-center text-muted py-4 d-none">Đang tải chi tiết...</div>
                    <div id="contentDetailError" class="alert alert-danger d-none" role="alert"></div>
                    <div id="contentDetailBody" class="content-detail-body"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.ADMIN_PROCESS_REPORT_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=processReport";
        window.ADMIN_OVERVIEW_STATS_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=overviewStats";
        window.ADMIN_STATISTICS_RANKINGS_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=statisticsRankings";
        window.ADMIN_STATISTICS_CHARTS_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=statisticsCharts";
        window.ADMIN_STATISTICS_INSIGHTS_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=statisticsInsights";
        window.ADMIN_REPORT_DETAIL_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=getReportDetail";
        window.ADMIN_UPDATE_USER_ROLE_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=updateUserRole";
        window.ADMIN_TOGGLE_USER_ACTIVE_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=toggleUserActive";
        window.ADMIN_LIST_MEMBERS_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=listMembers";
        window.ADMIN_LIST_CONTENT_POSTS_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=listContentPosts";
        window.ADMIN_CONTENT_POST_DETAIL_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=getContentPostDetail";
        window.ADMIN_TOGGLE_CONTENT_POST_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=toggleContentPostHidden";
        window.ADMIN_DELETE_CONTENT_POST_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=deleteContentPost";
        window.ADMIN_LIST_CONTENT_COMMENTS_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=listContentComments";
        window.ADMIN_CONTENT_COMMENT_DETAIL_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=getContentCommentDetail";
        window.ADMIN_TOGGLE_CONTENT_COMMENT_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=toggleContentCommentHidden";
        window.ADMIN_DELETE_CONTENT_COMMENT_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=deleteContentComment";
        window.ADMIN_LIST_CONTENT_HASHTAGS_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=listContentHashtags";
        window.ADMIN_TOGGLE_CONTENT_HASHTAG_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=toggleContentHashtagHidden";
        window.ADMIN_DELETE_CONTENT_HASHTAG_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=deleteContentHashtag";
        window.ADMIN_CURRENT_USER_ID = <?php echo (int)($currentAdminId ?? 0); ?>;
        window.ADMIN_BASE_URL = "<?php echo BASE_URL; ?>";
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <script src="<?php echo BASE_URL; ?>Public/assets/JS/admin-script.js"></script>
</body>
</html>
