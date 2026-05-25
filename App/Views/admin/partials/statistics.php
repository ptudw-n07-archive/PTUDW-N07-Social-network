            <?php // --- Thống kê --- ?>
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
                            <small class="admin-last-updated">Cập nhật lần cuối: <strong id="statisticsLastUpdated">--:--:--</strong></small>
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
                                        <h6>Báo cáo mới nhất</h6>
                                        <div id="latestReportsInsight" class="statistics-list statistics-list-left loading-state">Đang tải...</div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
