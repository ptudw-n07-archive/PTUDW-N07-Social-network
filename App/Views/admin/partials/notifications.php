            <?php // --- Quản lý thông báo --- ?>
            <div class="tab-pane fade" id="notifications" role="tabpanel">
                <div class="admin-table-container notification-admin-container">
                    <div class="admin-tab-toolbar mb-3">
                        <div class="content-toolbar mb-0">
                            <div class="content-search-wrap">
                                <i class="bi bi-search"></i>
                                <input type="search" id="notificationSearchInput" class="form-control admin-control" placeholder="Tìm người nhận hoặc nội dung thông báo">
                            </div>
                            <select id="notificationTypeFilter" class="form-select admin-control content-filter">
                                <option value="">Tất cả loại</option>
                                <option value="Like">Like</option>
                                <option value="Comment">Comment</option>
                                <option value="Follow">Follow</option>
                                <option value="ReportWarning">ReportWarning</option>
                                <option value="ContentHidden">ContentHidden</option>
                                <option value="RoleChanged">RoleChanged</option>
                                <option value="AccountLocked">AccountLocked</option>
                                <option value="AccountUnlocked">AccountUnlocked</option>
                                <option value="System">System</option>
                            </select>
                            <select id="notificationReadFilter" class="form-select admin-control content-filter">
                                <option value="">Tất cả trạng thái</option>
                                <option value="1">Đã đọc</option>
                                <option value="0">Chưa đọc</option>
                            </select>
                        </div>
                        <div class="admin-report-actions">
                            <small class="admin-last-updated">Cập nhật lần cuối: <strong id="notificationLastUpdated">--:--:--</strong></small>
                            <button type="button" class="btn btn-pink-admin btn-sm" id="openSendNotificationBtn"><i class="bi bi-send me-1"></i>Gửi thông báo</button>
                            <button type="button" class="btn btn-outline-brown btn-sm" id="printNotificationsBtn"><i class="bi bi-printer me-1"></i>In báo cáo</button>
                            <button type="button" class="btn btn-pink-admin btn-sm" id="exportNotificationsCsvBtn"><i class="bi bi-filetype-csv me-1"></i>Xuất CSV</button>
                        </div>
                    </div>
                    <div id="notificationAlert" class="alert d-none" role="alert"></div>
                    <div class="table-responsive notification-table-responsive">
                        <table class="table align-middle notification-table">
                            <thead>
                                <tr>
                                    <th>NotificationID</th>
                                    <th>Loại</th>
                                    <th>Người nhận</th>
                                    <th>Người gửi</th>
                                    <th>Message</th>
                                    <th>Liên kết</th>
                                    <th>Trạng thái</th>
                                    <th>CreatedAt</th>
                                    <th class="text-end">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="notificationsTableBody">
                                <tr><td colspan="9" class="text-center text-muted py-4">Đang tải dữ liệu...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
