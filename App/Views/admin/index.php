<?php
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

    <main class="container py-5">
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
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#reports" type="button" role="tab"><i class="bi bi-shield-check me-2"></i>Kiểm duyệt</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#members" type="button" role="tab"><i class="bi bi-person-badge me-2"></i>Thành viên</button>
                </li>
            </ul>
        </div>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="overview" role="tabpanel">
                <div class="row g-4 d-flex align-items-stretch">
                    <div class="col-md-3">
                        <div class="admin-stat-card">
                            <i class="bi bi-people mb-3"></i>
                            <span class="stat-label">Thành viên</span>
                            <h2 class="stat-value"><?php echo $stats['users']; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="admin-stat-card">
                            <i class="bi bi-exclamation-octagon mb-3 text-danger"></i>
                            <span class="stat-label">Báo cáo mới</span>
                            <h2 class="stat-value text-danger"><?php echo $stats['reports']; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="admin-stat-card">
                            <i class="bi bi-file-earmark-post mb-3"></i>
                            <span class="stat-label">Bài viết</span>
                            <h2 class="stat-value"><?php echo $stats['posts']; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="admin-stat-card">
                            <i class="bi bi-heart-pulse mb-3 pink-icon"></i>
                            <span class="stat-label">Hoạt động</span>
                            <h2 class="stat-value"><?php echo $stats['activity']; ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="reports" role="tabpanel">
                <div class="admin-table-container">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Đối tượng bị báo cáo</th>
                                <th>Lý do vi phạm</th>
                                <th>Nội dung báo cáo</th>
                                <th>Thời gian gửi</th>
                                <th>Trạng thái</th>
                                <th class="text-end">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($reports)): ?>
                                <?php foreach($reports as $r): ?>
                                <tr id="report-row-<?php echo $r['id']; ?>" data-report-id="<?php echo $r['id']; ?>" data-details="<?php echo htmlspecialchars($r['details'] ?? '', ENT_QUOTES); ?>">
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
                                    <td><span class="small"><?php echo htmlspecialchars($r['reason']); ?></span></td>
                                    <td>
                                        <?php $detailText = trim($r['details'] ?? ''); ?>
                                        <?php if ($detailText !== ''): ?>
                                            <span onclick="showReportDetails(<?php echo $r['id']; ?>)" class="small report-details-text text-truncate" style="max-width:220px; display:inline-block; cursor:pointer;" title="<?php echo htmlspecialchars($detailText, ENT_QUOTES); ?>"><?php echo htmlspecialchars(mb_strimwidth($detailText, 0, 80, '...')); ?></span>
                                        <?php else: ?>
                                            <span class="small text-muted">Không có chi tiết</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted"><?php echo htmlspecialchars($r['time']); ?></td>
                                    <td>
                                        <?php if ($r['status'] === 'Chờ duyệt'): ?>
                                            <span class="badge rounded-pill bg-warning text-dark px-2.5 py-1 text-xs fw-medium">Chờ duyệt</span>
                                        <?php else: ?>
                                            <span class="badge rounded-pill bg-success text-white px-2.5 py-1 text-xs fw-medium">Đã xử lý</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end report-actions">
                                        <?php if ($r['status'] === 'Chờ duyệt'): ?>
                                            <div class="d-flex justify-content-end gap-2">
                                                <button class="btn btn-outline-secondary btn-sm" onclick="handleReportAction(<?php echo $r['id']; ?>, 'ignore')">Bỏ qua</button>
                                                <button class="btn btn-danger btn-sm" onclick="handleReportAction(<?php echo $r['id']; ?>, 'hide')">Ẩn nội dung</button>
                                                <button class="btn btn-warning btn-sm text-white" onclick="handleReportAction(<?php echo $r['id']; ?>, 'warn')">Cảnh cáo</button>
                                            </div>
                                        <?php else: ?>
                                            <span class="report-action-completed"><i class="bi bi-check2-all"></i> Hoàn tất</span>
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

            <div class="tab-pane fade" id="members" role="tabpanel">
                <div class="admin-table-container">
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

                    <div class="table-responsive">
                        <table class="table align-middle member-table">
                            <thead>
                                <tr>
                                    <th>Thành viên</th>
                                    <th>Vai trò</th>
                                    <th>Thống kê</th>
                                    <th>Ngày tạo</th>
                                    <th>Trạng thái</th>
                                    <th class="text-end">Thao tác</th>
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
                                        <td class="small text-muted member-role"><?php echo htmlspecialchars($m['RoleName']); ?></td>
                                        <td class="small">
                                            <span class="member-count-pill"><i class="bi bi-file-earmark-post"></i><?php echo (int)$m['PostCount']; ?></span>
                                            <span class="member-count-pill danger"><i class="bi bi-flag"></i><?php echo (int)$m['ReportCount']; ?></span>
                                        </td>
                                        <td class="small"><?php echo htmlspecialchars($m['joined']); ?></td>
                                        <td class="member-status">
                                            <?php if ((int)$m['IsActive'] === 0): ?>
                                                <span class="badge rounded-pill bg-danger text-white px-2.5 py-1 text-xs fw-medium">Bị khóa</span>
                                            <?php else: ?>
                                                <span class="badge rounded-pill bg-success text-white px-2.5 py-1 text-xs fw-medium">Hoạt động</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex gap-2 justify-content-end flex-wrap">
                                                <button type="button" class="btn btn-outline-brown btn-sm btn-member-detail" data-member='<?php echo htmlspecialchars(json_encode($m, JSON_UNESCAPED_UNICODE), ENT_QUOTES); ?>'><i class="bi bi-eye"></i> Chi tiết</button>
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

    <script>
        window.ADMIN_PROCESS_REPORT_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=processReport";
        window.ADMIN_UPDATE_USER_ROLE_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=updateUserRole";
        window.ADMIN_TOGGLE_USER_ACTIVE_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=toggleUserActive";
        window.ADMIN_LIST_MEMBERS_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=listMembers";
        window.ADMIN_CURRENT_USER_ID = <?php echo (int)($currentAdminId ?? 0); ?>;
        window.ADMIN_BASE_URL = "<?php echo BASE_URL; ?>";
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>Public/assets/JS/admin-script.js"></script>
</body>
</html>
