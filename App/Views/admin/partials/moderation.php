            <?php // --- Kiểm duyệt báo cáo --- ?>
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
                                        'TargetType' => $r['targetType'] ?? '',
                                        'Reason' => $r['reason'] ?? '',
                                        'Status' => $r['status'] ?? '',
                                        'StatusKey' => $r['statusKey'] ?? '',
                                        'CreatedAt' => $r['time'] ?? ''
                                    ];
                                ?>
                                <?php
                                    $targetType = $r['targetType'] ?? 'post';
                                    $hideActionText = $targetType === 'account' ? 'Khóa' : 'Ẩn';
                                    $hideActionLabel = $targetType === 'account' ? 'Khóa tài khoản bị báo cáo' : 'Ẩn nội dung bị báo cáo';
                                ?>
                                <tr id="report-row-<?php echo $r['id']; ?>" data-report-id="<?php echo $r['id']; ?>" data-report-target-type="<?php echo htmlspecialchars($targetType, ENT_QUOTES); ?>" data-report='<?php echo htmlspecialchars(json_encode($reportExportData, JSON_UNESCAPED_UNICODE), ENT_QUOTES); ?>' data-details="<?php echo htmlspecialchars($r['details'] ?? '', ENT_QUOTES); ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <img src="<?php echo htmlspecialchars(admin_image_url($r['avatar'] ?? ''), ENT_QUOTES); ?>" alt="avatar" class="rounded-circle admin-avatar-sm" <?php echo admin_avatar_error_attr(); ?>>
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
                                                <button type="button" class="btn btn-outline-secondary btn-sm btn-report-action" data-report-id="<?php echo $r['id']; ?>" data-report-action="ignore">Bỏ qua</button>
                                                <button type="button" class="btn btn-danger btn-sm btn-report-action" data-report-id="<?php echo $r['id']; ?>" data-report-action="hide" data-report-target-type="<?php echo htmlspecialchars($targetType, ENT_QUOTES); ?>" data-report-action-label="<?php echo htmlspecialchars($hideActionLabel, ENT_QUOTES); ?>"><?php echo htmlspecialchars($hideActionText); ?></button>
                                                <button type="button" class="btn btn-warning btn-sm text-white btn-report-action" data-report-id="<?php echo $r['id']; ?>" data-report-action="warn">Cảnh cáo</button>
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
