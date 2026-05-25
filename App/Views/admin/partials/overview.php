            <?php // --- Tổng quan dashboard --- ?>
            <div class="tab-pane fade show active" id="overview" role="tabpanel">
                <?php
                    // Cấu hình card tổng quan để thêm/bớt chỉ cần sửa trong mảng này.
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
                    <div class="admin-report-actions">
                        <small>Cập nhật lần cuối: <strong id="overviewLastUpdated"><?php echo htmlspecialchars($stats['lastUpdated'] ?? ''); ?></strong></small>
                        <button type="button" class="btn btn-outline-brown btn-sm" id="printOverviewBtn"><i class="bi bi-printer me-1"></i>In báo cáo</button>
                        <button type="button" class="btn btn-pink-admin btn-sm" id="exportOverviewCsvBtn"><i class="bi bi-filetype-csv me-1"></i>Xuất CSV</button>
                    </div>
                </div>
                <div class="row g-3 d-flex align-items-stretch overview-stat-grid" id="overviewStatsGrid">
                    <?php foreach ($overviewCards as $card): ?>
                    <div class="col-12 col-sm-6 col-lg-3 overview-stat-col">
                        <div class="admin-stat-card overview-stat-card overview-detail-card" role="button" tabindex="0" data-overview-metric="<?php echo htmlspecialchars($card['key']); ?>" data-overview-title="<?php echo htmlspecialchars($card['label']); ?>">
                            <i class="bi <?php echo $card['icon']; ?> mb-2 <?php echo !empty($card['danger']) ? 'text-danger' : 'pink-icon'; ?>"></i>
                            <span class="stat-label"><?php echo htmlspecialchars($card['label']); ?></span>
                            <h2 class="stat-value <?php echo !empty($card['danger']) ? 'text-danger' : ''; ?>" data-overview-stat="<?php echo $card['key']; ?>"><?php echo number_format((int)$card['value']); ?></h2>
                            <small class="overview-kpi-indicator" data-overview-kpi="<?php echo $card['key']; ?>"><?php echo htmlspecialchars($stats['kpi'][$card['key']] ?? ''); ?></small>
                            <small class="overview-card-hint">Nhấn để xem chi tiết</small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
