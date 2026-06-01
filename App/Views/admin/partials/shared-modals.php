    <?php // --- Modal dùng chung cho các thao tác admin --- ?>
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
                        <div class="admin-note-chip-list mb-3" aria-label="Gợi ý ghi chú xử lý">
                            <button type="button" class="admin-note-chip" data-note-chip="Spam quảng cáo">Spam quảng cáo</button>
                            <button type="button" class="admin-note-chip" data-note-chip="Ngôn từ xúc phạm">Ngôn từ xúc phạm</button>
                            <button type="button" class="admin-note-chip" data-note-chip="Quấy rối người dùng">Quấy rối người dùng</button>
                            <button type="button" class="admin-note-chip" data-note-chip="Nội dung sai sự thật">Nội dung sai sự thật</button>
                            <button type="button" class="admin-note-chip" data-note-chip="Nội dung phản cảm">Nội dung phản cảm</button>
                            <button type="button" class="admin-note-chip" data-note-chip="Vi phạm tiêu chuẩn cộng đồng">Vi phạm tiêu chuẩn cộng đồng</button>
                            <button type="button" class="admin-note-chip" data-note-chip="Báo cáo không hợp lệ">Báo cáo không hợp lệ</button>
                            <button type="button" class="admin-note-chip" data-note-chip="Tái phạm nhiều lần">Tái phạm nhiều lần</button>
                        </div>
                        <textarea id="adminNoteTextarea" class="form-control admin-control" rows="4" placeholder="Nhập ghi chú xử lý báo cáo..."></textarea>
                        <div id="adminNoteError" class="admin-note-error d-none">Vui lòng nhập ghi chú xử lý.</div>
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

    <div class="modal fade" id="overviewDetailModal" tabindex="-1" aria-labelledby="overviewDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content admin-bootstrap-modal overview-detail-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="overviewDetailModalLabel">Chi tiết tổng quan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div id="overviewDetailLoading" class="text-center text-muted py-4 d-none">Đang tải dữ liệu...</div>
                    <div id="overviewDetailError" class="alert alert-danger d-none" role="alert"></div>
                    <div id="overviewDetailBody"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="notificationDetailModal" tabindex="-1" aria-labelledby="notificationDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content admin-bootstrap-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="notificationDetailModalLabel">Chi tiết thông báo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div id="notificationDetailLoading" class="text-center text-muted py-4 d-none">Đang tải chi tiết thông báo...</div>
                    <div id="notificationDetailError" class="alert alert-danger d-none" role="alert"></div>
                    <div id="notificationDetailBody" class="notification-detail-grid"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="sendNotificationModal" tabindex="-1" aria-labelledby="sendNotificationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content admin-bootstrap-modal">
                <div class="modal-header">
                    <h5 class="modal-title" id="sendNotificationModalLabel">Gửi thông báo hệ thống</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <form id="sendNotificationForm">
                    <?= \App\Services\CsrfService::hiddenField() ?>
                    <div class="modal-body">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="sendNotificationAllCheckbox">
                            <label class="form-check-label" for="sendNotificationAllCheckbox">Gửi cho tất cả thành viên đang hoạt động</label>
                        </div>
                        <div class="mb-3" id="singleReceiverWrap">
                            <label for="notificationReceiverSearch" class="form-label">Người nhận</label>
                            <input type="search" id="notificationReceiverSearch" class="form-control admin-control mb-2" placeholder="Tìm Username, FullName hoặc Email">
                            <input type="hidden" id="notificationReceiverId" value="">
                            <small class="text-muted d-block mb-2">Có thể chọn một hoặc nhiều người nhận từ kết quả tìm kiếm.</small>
                            <div id="notificationReceiverSelected" class="notification-receiver-selected d-none"></div>
                            <div id="notificationReceiverResults" class="notification-receiver-results d-none"></div>
                        </div>
                        <div class="mb-3">
                            <label for="systemNotificationMessage" class="form-label">Message</label>
                            <textarea id="systemNotificationMessage" class="form-control admin-control" rows="5" maxlength="1000" placeholder="Nhập nội dung thông báo..." required></textarea>
                            <small class="text-muted"><span id="systemNotificationMessageCount">0</span>/1000 ký tự</small>
                        </div>
                        <div id="sendNotificationError" class="alert alert-danger d-none" role="alert"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button>
                        <button type="submit" class="btn btn-pink-admin" id="sendNotificationSubmitBtn"><i class="bi bi-send me-1"></i>Gửi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
