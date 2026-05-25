            <?php // --- Quản lý nội dung --- ?>
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
