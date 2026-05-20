<?php
/** @var array $stats Đã được truyền từ AdminController */
/** @var array $reports Đã được truyền từ AdminController */
/** @var array $members Đã được truyền từ AdminController */
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
                    <button id="logoutBtn" class="header-logout-btn" onclick="window.location.href='<?php echo BASE_URL; ?>App/Views/auth/login.php'">
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
                                <th>Thời gian gửi</th>
                                <th>Trạng thái</th>
                                <th class="text-end">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($reports)): ?>
                                <?php foreach($reports as $r): ?>
                                <tr>
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
                                    <td class="small text-muted"><?php echo htmlspecialchars($r['time']); ?></td>
                                    <td>
                                        <?php if (($r['status'] ?? '') === 'Chờ duyệt'): ?>
                                            <span class="badge rounded-pill bg-warning text-dark px-2.5 py-1 text-xs fw-medium">Chờ duyệt</span>
                                        <?php else: ?>
                                            <span class="badge rounded-pill bg-success text-white px-2.5 py-1 text-xs fw-medium">Đã xử lý</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-pink-admin" <?php echo ($r['status'] == 'Đã xử lý') ? 'disabled style="opacity: 0.7;"' : ''; ?>>
                                            <?php echo ($r['status'] == 'Đã xử lý') ? '<i class="bi bi-check2-all"></i> Hoàn tất' : 'Xử lý'; ?>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">Hiện tại hệ thống sạch sẽ, chưa có báo cáo vi phạm nào!</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="members" role="tabpanel">
                <div class="admin-table-container">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Thành viên</th>
                                <th>Vai trò</th>
                                <th>Ngày tham gia</th>
                                <th>Trạng thái</th>
                                <th class="text-end">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($members)): ?>
                                <?php foreach($members as $m): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <img src="<?php echo BASE_URL . $m['avatar']; ?>" alt="avatar" class="rounded-circle border" style="width: 40px; height: 40px; object-fit: cover; border-color: rgba(121, 91, 74, 0.15) !important;">
                                            </div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($m['name']); ?></div>
                                        </div>
                                    </td>
                                    <td class="small text-muted"><?php echo htmlspecialchars($m['role']); ?></td>
                                    <td class="small"><?php echo htmlspecialchars($m['joined']); ?></td>
                                    <td>
                                        <?php if (($m['status'] ?? '') === 'Đã khóa'): ?>
                                            <span class="badge rounded-pill bg-danger text-white px-2.5 py-1 text-xs fw-medium">Đã khóa</span>
                                        <?php else: ?>
                                            <span class="badge rounded-pill bg-success text-white px-2.5 py-1 text-xs fw-medium">Hoạt động</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end"><button class="btn btn-outline-brown">Sửa</button></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">Chưa có thành viên nào tham gia mạng xã hội.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>Public/assets/JS/admin-script.js"></script>
</body>
</html>