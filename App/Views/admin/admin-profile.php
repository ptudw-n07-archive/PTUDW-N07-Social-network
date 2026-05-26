<?php
require_once __DIR__ . '/../../../Config/Database.php';
require_once __DIR__ . '/partials/image-helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id']) || (int)($_SESSION['role_id'] ?? 0) !== 1) {
    header('Location: ' . app_url('App/Views/auth/login.php'));
    exit();
}

if (!isset($admin) || !is_array($admin)) {
    header('Location: ' . app_url('App/Views/admin/profile.php'));
    exit();
}

$adminAvatar = admin_image_url($admin['ProfilePictureUrl'] ?? '');
$adminName = $admin['FullName'] ?: ($admin['Username'] ?? 'Quản trị viên');
$adminBio = trim((string)($admin['Bio'] ?? ''));
$adminCreatedAt = !empty($admin['CreatedAt']) ? date('d/m/Y H:i', strtotime($admin['CreatedAt'])) : 'Chưa rõ';
$adminStatus = (int)($admin['IsActive'] ?? 0) === 1 ? 'Hoạt động' : 'Bị khóa';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive - Admin Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <?php include __DIR__ . '/../partials/fonts.php'; ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Public/assets/CSS/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>Public/assets/CSS/admin-style.css">
</head>
<body class="admin-body admin-profile-page">
    <header class="archive-header">
        <div class="container-fluid px-4 px-lg-5">
            <div class="row align-items-center py-3">
                <div class="col-4 d-flex align-items-center">
                    <a href="<?php echo BASE_URL; ?>App/Views/admin/dashboard.php#overview" class="brand-logo text-decoration-none">ARCHIVE</a>
                </div>
                <div class="col-4 d-flex justify-content-center align-items-center">
                    <div class="header-badge"><i class="bi bi-person-badge"></i></div>
                    <div class="header-clock d-none d-lg-inline-flex ms-2"><i class="bi bi-clock"></i><span data-admin-clock>--:--:-- · --/--/----</span></div>
                </div>
                <div class="col-4 d-flex justify-content-end align-items-center gap-2">
                    <a href="<?php echo BASE_URL; ?>App/Views/admin/dashboard.php" class="btn btn-outline-brown btn-sm"><i class="bi bi-arrow-left me-1"></i>Dashboard</a>
                    <button id="logoutBtn" class="header-logout-btn" type="button" data-logout-url="<?php echo BASE_URL; ?>App/Controllers/AuthController.php?action=logout">
                        <i class="bi bi-box-arrow-right"></i> <span>Đăng xuất</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <main class="container-fluid admin-profile-main py-5">
        <div class="admin-profile-layout">
            <section class="admin-profile-card">
                <div class="admin-profile-identity">
                    <img id="adminProfileAvatarLarge" class="admin-profile-avatar-large" src="<?php echo htmlspecialchars($adminAvatar, ENT_QUOTES); ?>" alt="Admin avatar" <?php echo admin_avatar_error_attr(); ?>>
                    <div>
                        <span class="admin-profile-kicker">Admin Profile</span>
                        <h1 id="adminProfileNameText"><?php echo htmlspecialchars($adminName); ?></h1>
                        <p>@<?php echo htmlspecialchars($admin['Username'] ?? ''); ?></p>
                    </div>
                </div>

                <div id="adminProfileAlert" class="alert d-none mt-4" role="alert"></div>

                <div class="admin-profile-meta-grid mt-4">
                    <div class="admin-profile-meta-item">
                        <span>Email</span>
                        <strong><?php echo htmlspecialchars($admin['Email'] ?? ''); ?></strong>
                    </div>
                    <div class="admin-profile-meta-item">
                        <span>Vai trò</span>
                        <strong><?php echo htmlspecialchars($admin['RoleName'] ?? 'Admin'); ?></strong>
                    </div>
                    <div class="admin-profile-meta-item">
                        <span>Ngày tạo</span>
                        <strong><?php echo htmlspecialchars($adminCreatedAt); ?></strong>
                    </div>
                    <div class="admin-profile-meta-item">
                        <span>Trạng thái</span>
                        <strong><?php echo htmlspecialchars($adminStatus); ?></strong>
                    </div>
                    <div class="admin-profile-meta-item admin-profile-meta-wide">
                        <span>Bio</span>
                        <strong id="adminProfileBioText"><?php echo htmlspecialchars($adminBio !== '' ? $adminBio : 'Chưa có bio.'); ?></strong>
                    </div>
                </div>
            </section>

            <section class="admin-profile-tools">
                <div class="admin-profile-panel">
                    <div class="admin-profile-panel-heading">
                        <h2>Thông tin hiển thị</h2>
                    </div>
                    <form id="adminProfileNameForm" class="admin-profile-form">
                        <?= \App\Services\CsrfService::hiddenField() ?>
                        <label for="adminFullNameInput" class="form-label">FullName</label>
                        <div class="admin-profile-inline-form">
                            <input id="adminFullNameInput" name="FullName" class="form-control admin-control" maxlength="100" value="<?php echo htmlspecialchars($admin['FullName'] ?? '', ENT_QUOTES); ?>" required>
                            <button type="submit" class="btn btn-pink-admin">Lưu</button>
                        </div>
                    </form>
                </div>

                <div class="admin-profile-panel">
                    <div class="admin-profile-panel-heading">
                        <h2>Bio</h2>
                    </div>
                    <form id="adminProfileBioForm" class="admin-profile-form">
                        <?= \App\Services\CsrfService::hiddenField() ?>
                        <label for="adminBioInput" class="form-label">Bio quản trị viên</label>
                        <textarea id="adminBioInput" name="Bio" class="form-control admin-control" rows="4" maxlength="500" placeholder="Viết một mô tả ngắn về bạn..."><?php echo htmlspecialchars($admin['Bio'] ?? '', ENT_QUOTES); ?></textarea>
                        <div class="admin-profile-form-footer">
                            <small class="text-muted"><span id="adminBioCount"><?php echo mb_strlen((string)($admin['Bio'] ?? '')); ?></span>/500 ký tự</small>
                            <button type="submit" class="btn btn-pink-admin"><i class="bi bi-pencil-square me-1"></i>Lưu bio</button>
                        </div>
                    </form>
                </div>

                <div class="admin-profile-panel">
                    <div class="admin-profile-panel-heading">
                        <h2>Avatar</h2>
                    </div>
                    <form id="adminAvatarForm" class="admin-profile-form" enctype="multipart/form-data">
                        <?= \App\Services\CsrfService::hiddenField() ?>
                        <input id="adminAvatarInput" name="avatar" type="file" class="form-control admin-control" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required>
                        <small class="text-muted">Hỗ trợ jpg, jpeg, png, webp. Tối đa 5MB.</small>
                        <button type="submit" class="btn btn-pink-admin align-self-start"><i class="bi bi-upload me-1"></i>Cập nhật avatar</button>
                    </form>
                </div>

                <div class="admin-profile-panel">
                    <div class="admin-profile-panel-heading">
                        <h2>Đổi mật khẩu</h2>
                    </div>
                    <form id="adminPasswordForm" class="admin-profile-form">
                        <?= \App\Services\CsrfService::hiddenField() ?>
                        <input id="adminCurrentPassword" type="password" class="form-control admin-control" placeholder="Mật khẩu hiện tại" autocomplete="current-password" required>
                        <input id="adminNewPassword" type="password" class="form-control admin-control" placeholder="Mật khẩu mới" autocomplete="new-password" required>
                        <input id="adminConfirmPassword" type="password" class="form-control admin-control" placeholder="Xác nhận mật khẩu mới" autocomplete="new-password" required>
                        <button type="submit" class="btn btn-pink-admin align-self-start"><i class="bi bi-shield-lock me-1"></i>Đổi mật khẩu</button>
                    </form>
                </div>
            </section>

            <section class="admin-profile-logs">
                <div class="admin-profile-panel">
                    <div class="admin-profile-panel-heading admin-profile-logs-heading">
                        <div>
                            <h2>Admin logs gần đây</h2>
                            <p>Chỉ hiển thị hoạt động của tài khoản admin hiện tại.</p>
                        </div>
                    </div>
                    <div class="admin-profile-log-toolbar">
                        <div class="content-search-wrap">
                            <i class="bi bi-search"></i>
                            <input id="adminLogsSearch" type="search" class="form-control admin-control" placeholder="Tìm Action, TargetType, Description">
                        </div>
                        <select id="adminLogsActionFilter" class="form-select admin-control content-filter">
                            <option value="">Tất cả action</option>
                            <?php foreach (($logActions ?? []) as $action): ?>
                                <option value="<?php echo htmlspecialchars($action, ENT_QUOTES); ?>"><?php echo htmlspecialchars($action); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="adminLogsTableBody" class="admin-log-timeline">
                        <div class="admin-loading-state">
                            <span class="admin-spinner"></span>
                            <span>Đang tải logs...</span>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <div id="adminToastContainer" class="admin-toast-container" aria-live="polite" aria-atomic="true"></div>

    <script>
        // Các endpoint riêng của trang profile admin.
        window.ADMIN_PROFILE_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=getAdminProfile";
        window.ADMIN_UPDATE_PROFILE_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=updateAdminFullName";
        window.ADMIN_UPDATE_BIO_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=updateAdminBio";
        window.ADMIN_UPDATE_AVATAR_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=updateAdminAvatar";
        window.ADMIN_CHANGE_PASSWORD_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=changeAdminPassword";
        window.ADMIN_LOGS_URL = "<?php echo BASE_URL; ?>App/Controllers/AdminController.php?action=adminLogs";
        window.ADMIN_BASE_URL = "<?php echo BASE_URL; ?>";
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>Public/assets/JS/admin-core.js"></script>
    <script src="<?php echo BASE_URL; ?>Public/assets/JS/admin-script.js"></script>
</body>
</html>
