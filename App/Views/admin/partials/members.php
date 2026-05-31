            <?php // --- Quản lý thành viên --- ?>
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
                                                    <img src="<?php echo htmlspecialchars(admin_image_url($m['avatar'] ?? ''), ENT_QUOTES); ?>" alt="avatar" class="rounded-circle border admin-avatar-sm" <?php echo admin_avatar_error_attr(); ?>>
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
