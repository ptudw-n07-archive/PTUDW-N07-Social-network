document.addEventListener('DOMContentLoaded', function() {
    const adminModal = document.getElementById('adminModal');
    const adminModalTitle = adminModal ? adminModal.querySelector('.admin-modal-title') : null;
    const adminModalMessage = adminModal ? adminModal.querySelector('.admin-modal-message') : null;
    const adminModalConfirm = adminModal ? adminModal.querySelector('[data-admin-modal-confirm]') : null;
    const adminModalCancel = adminModal ? adminModal.querySelector('[data-admin-modal-cancel]') : null;
    const adminModalClose = adminModal ? adminModal.querySelector('[data-admin-modal-close]') : null;
    const adminModalBackdrop = adminModal ? adminModal.querySelector('.admin-modal-backdrop') : null;
    let modalResolve = null;

    const openModal = () => {
        if (!adminModal) return;
        adminModal.classList.remove('d-none');
        requestAnimationFrame(() => adminModal.classList.add('active'));
    };

    const closeModal = (result = false) => {
        if (!adminModal) return;
        adminModal.classList.remove('active');
        setTimeout(() => adminModal.classList.add('d-none'), 250);
        if (modalResolve) {
            modalResolve(result);
            modalResolve = null;
        }
    };

    const showConfirmModal = (message, title = 'Xác nhận', confirmText = 'Xác nhận', cancelText = 'Hủy') => {
        if (!adminModalTitle || !adminModalMessage || !adminModalConfirm || !adminModalCancel) {
            return Promise.resolve(false);
        }

        adminModalTitle.textContent = title;
        adminModalMessage.textContent = message;
        adminModalConfirm.textContent = confirmText;
        adminModalCancel.textContent = cancelText;
        adminModalCancel.style.display = 'inline-flex';
        openModal();

        return new Promise(resolve => {
            modalResolve = resolve;
        });
    };

    const showAlertModal = (message, title = 'Thông báo', confirmText = 'Đóng') => {
        if (!adminModalTitle || !adminModalMessage || !adminModalConfirm || !adminModalCancel) {
            return Promise.resolve(false);
        }

        adminModalTitle.textContent = title;
        adminModalMessage.textContent = message;
        adminModalConfirm.textContent = confirmText;
        adminModalCancel.style.display = 'none';
        openModal();

        return new Promise(resolve => {
            modalResolve = resolve;
        });
    };

    if (adminModalConfirm) adminModalConfirm.addEventListener('click', () => closeModal(true));
    if (adminModalCancel) adminModalCancel.addEventListener('click', () => closeModal(false));
    if (adminModalClose) adminModalClose.addEventListener('click', () => closeModal(false));
    if (adminModalBackdrop) adminModalBackdrop.addEventListener('click', () => closeModal(false));

    const adminNoteModalEl = document.getElementById('adminNoteModal');
    const adminNoteTextarea = document.getElementById('adminNoteTextarea');
    const adminNoteSaveBtn = document.getElementById('adminNoteSaveBtn');
    const adminNoteModal = adminNoteModalEl ? new bootstrap.Modal(adminNoteModalEl) : null;
    let adminNoteResolve = null;

    const showAdminNoteModal = () => {
        if (!adminNoteModal || !adminNoteTextarea) {
            return Promise.resolve('');
        }

        adminNoteTextarea.value = '';
        adminNoteModal.show();

        return new Promise(resolve => {
            adminNoteResolve = resolve;
        });
    };

    const closeAdminNoteModal = (note = null) => {
        if (adminNoteModal) {
            adminNoteModal.hide();
        }

        if (adminNoteResolve) {
            adminNoteResolve(note);
            adminNoteResolve = null;
        }
    };

    if (adminNoteSaveBtn) {
        adminNoteSaveBtn.addEventListener('click', () => {
            closeAdminNoteModal(adminNoteTextarea.value.trim());
        });
    }

    if (adminNoteModalEl) {
        adminNoteModalEl.addEventListener('hidden.bs.modal', () => {
            if (adminNoteResolve) {
                adminNoteResolve(null);
                adminNoteResolve = null;
            }
        });
    }

    const reportDetailModalEl = document.getElementById('reportDetailModal');
    const reportDetailContent = document.getElementById('reportDetailContent');
    const reportDetailLoading = document.getElementById('reportDetailLoading');
    const reportDetailError = document.getElementById('reportDetailError');
    const reportDetailModal = reportDetailModalEl ? new bootstrap.Modal(reportDetailModalEl) : null;

    const membersTableBody = document.getElementById('membersTableBody');
    const memberSearchInput = document.getElementById('memberSearchInput');
    const memberRoleFilter = document.getElementById('memberRoleFilter');
    const printMembersBtn = document.getElementById('printMembersBtn');
    const exportMembersCsvBtn = document.getElementById('exportMembersCsvBtn');
    const editRoleModalEl = document.getElementById('editRoleModal');
    const editRoleSelect = document.getElementById('editRoleSelect');
    const editRoleSaveBtn = document.getElementById('editRoleSaveBtn');
    const editRoleUserName = document.getElementById('editRoleUserName');
    const editRoleError = document.getElementById('editRoleError');
    const editRoleModal = editRoleModalEl ? new bootstrap.Modal(editRoleModalEl) : null;
    const memberDetailModalEl = document.getElementById('memberDetailModal');
    const memberDetailContent = document.getElementById('memberDetailContent');
    const memberDetailModal = memberDetailModalEl ? new bootstrap.Modal(memberDetailModalEl) : null;
    let currentMembers = [];
    let currentEditUserId = null;
    let searchTimer = null;

    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[char]));

    const normalizeAssetPath = path => {
        if (!path) return '';
        if (/^https?:\/\//i.test(path)) return path;
        return `${window.ADMIN_BASE_URL || ''}${path}`;
    };

    const normalizeMember = member => {
        const isActive = Number(member.IsActive);
        return {
            ...member,
            UserID: Number(member.UserID),
            RoleID: Number(member.RoleID),
            IsActive: isActive === 1 ? 1 : 0,
            PostCount: Number(member.PostCount || 0),
            ReportCount: Number(member.ReportCount || 0),
            name: member.name || member.FullName || member.Username || 'Thành viên',
            status: isActive === 1 ? 'Hoạt động' : 'Bị khóa'
        };
    };

    const memberById = userId => currentMembers.find(member => Number(member.UserID) === Number(userId));
    const isSelf = userId => Number(userId) === Number(window.ADMIN_CURRENT_USER_ID || 0);

    const avatarSrc = member => {
        const avatar = member.avatar || member.ProfilePictureUrl || '';
        if (!avatar) return `${window.ADMIN_BASE_URL || ''}Public/assets/images/default-avatar.png`;
        if (/^https?:\/\//i.test(avatar)) return avatar;
        return `${window.ADMIN_BASE_URL || ''}${avatar}`;
    };

    const statusBadgeHtml = isActive => Number(isActive) === 1
        ? '<span class="badge rounded-pill bg-success text-white px-2.5 py-1 text-xs fw-medium">Hoạt động</span>'
        : '<span class="badge rounded-pill bg-danger text-white px-2.5 py-1 text-xs fw-medium">Bị khóa</span>';

    const actionDisabledAttrs = userId => isSelf(userId)
        ? 'disabled title="Không thể thao tác với chính tài khoản đang đăng nhập"'
        : '';

    const renderMemberRow = rawMember => {
        const member = normalizeMember(rawMember);
        const locked = member.IsActive === 0;
        const selfText = isSelf(member.UserID)
            ? '<small class="text-muted d-block mt-1">Không thể thao tác với chính tài khoản đang đăng nhập</small>'
            : '';

        return `
            <tr id="member-row-${member.UserID}" data-user-id="${member.UserID}" data-is-active="${member.IsActive}">
                <td>
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <img src="${escapeHtml(avatarSrc(member))}" alt="avatar" class="rounded-circle border" style="width: 40px; height: 40px; object-fit: cover; border-color: rgba(121, 91, 74, 0.15) !important;">
                        </div>
                        <div>
                            <div class="fw-bold">${escapeHtml(member.name)}</div>
                            <small class="text-muted">@${escapeHtml(member.Username || '')}</small>
                        </div>
                    </div>
                </td>
                <td class="small text-muted member-role text-center">${escapeHtml(member.RoleName || '')}</td>
                <td class="small text-center member-stats-cell">
                    <span class="member-count-pill"><i class="bi bi-file-earmark-post"></i>${member.PostCount}</span>
                    <span class="member-count-pill danger"><i class="bi bi-flag"></i>${member.ReportCount}</span>
                </td>
                <td class="small text-center">${escapeHtml(member.joined || member.CreatedAt || '')}</td>
                <td class="member-status text-center">${statusBadgeHtml(member.IsActive)}</td>
                <td class="text-center">
                    <div class="member-actions-group">
                        <button type="button" class="btn btn-outline-brown btn-sm btn-member-detail btn-icon-detail" data-user-id="${member.UserID}" title="Xem chi tiết" aria-label="Xem chi tiết"><i class="bi bi-eye"></i></button>
                        <button type="button" class="btn btn-outline-brown btn-sm btn-edit-role" data-user-id="${member.UserID}" data-user-name="${escapeHtml(member.name)}" data-role-id="${member.RoleID}" data-role-name="${escapeHtml(member.RoleName || '')}" ${actionDisabledAttrs(member.UserID)}>Sửa</button>
                        <button type="button" class="btn btn-sm btn-toggle-active ${locked ? 'btn-pink-admin' : 'btn-outline-danger'}" data-user-id="${member.UserID}" data-user-name="${escapeHtml(member.name)}" data-is-active="${member.IsActive}" ${actionDisabledAttrs(member.UserID)}>${locked ? 'Mở khóa' : 'Khóa'}</button>
                    </div>
                    ${selfText}
                </td>
            </tr>
        `;
    };

    const renderMembers = members => {
        if (!membersTableBody) return;
        currentMembers = (members || []).map(normalizeMember);

        if (currentMembers.length === 0) {
            membersTableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Không tìm thấy thành viên phù hợp</td></tr>';
            return;
        }

        membersTableBody.innerHTML = currentMembers.map(renderMemberRow).join('');
    };

    const setEditRoleError = (message = '') => {
        if (!editRoleError) return;
        editRoleError.textContent = message;
        editRoleError.classList.toggle('d-none', message === '');
    };

    const fetchMembers = async () => {
        if (!window.ADMIN_LIST_MEMBERS_URL) return;

        const url = new URL(window.ADMIN_LIST_MEMBERS_URL, window.location.href);
        url.searchParams.set('keyword', memberSearchInput ? memberSearchInput.value.trim() : '');
        url.searchParams.set('roleId', memberRoleFilter ? memberRoleFilter.value : '');

        try {
            const res = await fetch(url.toString(), {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();

            if (!res.ok || !data.success) {
                await showAlertModal(data.message || 'Không thể tải danh sách thành viên.', 'Lỗi', 'Đóng');
                return;
            }

            renderMembers((data.data && data.data.members) || []);
        } catch (err) {
            console.error('List members error:', err);
            await showAlertModal('Có lỗi khi tải danh sách thành viên.', 'Lỗi AJAX', 'Đóng');
        }
    };

    const scheduleFetchMembers = () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(fetchMembers, 300);
    };

    const openEditRoleModal = button => {
        if (!button || !editRoleModal || !editRoleSelect || !editRoleUserName) return;
        currentEditUserId = Number(button.dataset.userId);

        editRoleUserName.textContent = button.dataset.userName || 'Thành viên';
        editRoleSelect.value = String(button.dataset.roleId || '');
        setEditRoleError('');
        editRoleModal.show();
    };

    const mergeMember = updatedMember => {
        if (!updatedMember || !updatedMember.UserID) return;
        const index = currentMembers.findIndex(member => Number(member.UserID) === Number(updatedMember.UserID));
        if (index >= 0) {
            currentMembers[index] = normalizeMember({ ...currentMembers[index], ...updatedMember });
        }
    };

    const updateRenderedRow = userId => {
        const member = memberById(userId);
        const row = document.getElementById(`member-row-${userId}`);
        if (member && row) {
            row.outerHTML = renderMemberRow(member);
        }
    };

    async function saveRoleChange(userId, roleId) {
        try {
            const res = await fetch(window.ADMIN_UPDATE_USER_ROLE_URL, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    UserID: Number(userId),
                    RoleID: Number(roleId)
                })
            });

            const data = await res.json();
            if (!res.ok || !data.success) {
                setEditRoleError(data.message || 'Không thể cập nhật vai trò.');
                return;
            }

            const updated = data.data || {};
            mergeMember({
                ...(updated.member || {}),
                UserID: updated.UserID,
                RoleID: updated.RoleID,
                RoleName: updated.RoleName
            });
            updateRenderedRow(updated.UserID);

            if (editRoleModal) editRoleModal.hide();
            await showAlertModal(data.message || 'Cập nhật vai trò thành công', 'Thành công', 'Đóng');
        } catch (err) {
            console.error('Update role error:', err);
            setEditRoleError('Có lỗi khi gửi yêu cầu cập nhật vai trò.');
        }
    }

    async function toggleUserActive(button) {
        const userId = Number(button.dataset.userId);
        const userName = button.dataset.userName || 'thành viên này';
        const currentActive = Number(button.dataset.isActive);
        const nextActive = currentActive === 1 ? 0 : 1;
        const confirmed = await showConfirmModal(
            nextActive === 1 ? `Mở khóa tài khoản ${userName}?` : `Khóa tài khoản ${userName}?`,
            nextActive === 1 ? 'Mở khóa tài khoản' : 'Khóa tài khoản',
            nextActive === 1 ? 'Mở khóa' : 'Khóa',
            'Hủy'
        );

        if (!confirmed) return;

        button.disabled = true;
        try {
            const res = await fetch(window.ADMIN_TOGGLE_USER_ACTIVE_URL, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    UserID: userId,
                    IsActive: nextActive
                })
            });
            const data = await res.json();

            if (!res.ok || !data.success) {
                await showAlertModal(data.message || 'Không thể cập nhật trạng thái tài khoản.', 'Lỗi', 'Đóng');
                return;
            }

            const updated = data.data || {};
            mergeMember({
                ...(updated.member || {}),
                UserID: updated.UserID,
                IsActive: updated.IsActive
            });
            updateRenderedRow(updated.UserID);
            await showAlertModal(data.message || 'Cập nhật trạng thái tài khoản thành công', 'Thành công', 'Đóng');
        } catch (err) {
            console.error('Toggle active error:', err);
            await showAlertModal('Có lỗi khi cập nhật trạng thái tài khoản.', 'Lỗi AJAX', 'Đóng');
        } finally {
            button.disabled = false;
        }
    }

    const openMemberDetailModal = userId => {
        const member = memberById(userId);
        if (!member || !memberDetailModal || !memberDetailContent) return;

        const rows = [
            ['UserID', member.UserID],
            ['Username', member.Username],
            ['FullName', member.FullName],
            ['Email', member.Email],
            ['RoleName', member.RoleName],
            ['Trạng thái', member.IsActive === 1 ? 'Hoạt động' : 'Bị khóa'],
            ['CreatedAt', member.CreatedAt],
            ['Tổng bài viết', member.PostCount],
            ['Tổng report bị nhận', member.ReportCount]
        ];

        memberDetailContent.innerHTML = rows.map(([label, value]) => `
            <div class="member-detail-item">
                <span>${escapeHtml(label)}</span>
                <strong>${escapeHtml(value || value === 0 ? value : '-')}</strong>
            </div>
        `).join('');
        memberDetailModal.show();
    };

    const currentReportRows = () => currentMembers.map(member => ({
        UserID: member.UserID,
        Username: member.Username || '',
        FullName: member.FullName || '',
        Email: member.Email || '',
        RoleName: member.RoleName || '',
        IsActive: member.IsActive === 1 ? 'Hoạt động' : 'Bị khóa',
        CreatedAt: member.CreatedAt || '',
        PostCount: member.PostCount,
        ReportCount: member.ReportCount
    }));

    const exportMembersCsv = () => {
        const rows = currentReportRows();
        const headers = ['UserID', 'Username', 'FullName', 'Email', 'RoleName', 'IsActive', 'CreatedAt', 'PostCount', 'ReportCount'];
        const csv = [
            headers.join(','),
            ...rows.map(row => headers.map(header => `"${String(row[header] ?? '').replace(/"/g, '""')}"`).join(','))
        ].join('\r\n');
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `archive-members-${new Date().toISOString().slice(0, 10)}.csv`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    };

    const printMembersReport = () => {
        const rows = currentReportRows();
        let printArea = document.getElementById('membersPrintArea');
        if (!printArea) {
            printArea = document.createElement('div');
            printArea.id = 'membersPrintArea';
            document.body.appendChild(printArea);
        }

        printArea.innerHTML = `
            <h1>Báo cáo quản lý thành viên - Archive</h1>
            <p>Thời gian xuất báo cáo: ${escapeHtml(new Date().toLocaleString('vi-VN'))}</p>
            <table>
                <thead>
                    <tr>
                        ${Object.keys(currentReportRows()[0] || {
                            UserID: '', Username: '', FullName: '', Email: '', RoleName: '', IsActive: '', CreatedAt: '', PostCount: '', ReportCount: ''
                        }).map(key => `<th>${escapeHtml(key)}</th>`).join('')}
                    </tr>
                </thead>
                <tbody>
                    ${rows.map(row => `<tr>${Object.values(row).map(value => `<td>${escapeHtml(value)}</td>`).join('')}</tr>`).join('')}
                </tbody>
            </table>
        `;
        window.print();
    };

    if (membersTableBody) {
        membersTableBody.addEventListener('click', event => {
            const detailButton = event.target.closest('.btn-member-detail');
            const editButton = event.target.closest('.btn-edit-role');
            const toggleButton = event.target.closest('.btn-toggle-active');

            if (detailButton) openMemberDetailModal(detailButton.dataset.userId);
            if (editButton) openEditRoleModal(editButton);
            if (toggleButton) toggleUserActive(toggleButton);
        });
    }

    if (editRoleSaveBtn) {
        editRoleSaveBtn.addEventListener('click', async () => {
            const selectedRoleId = editRoleSelect ? editRoleSelect.value : '';
            if (!currentEditUserId || !selectedRoleId) {
                setEditRoleError('Vui lòng chọn vai trò.');
                return;
            }

            editRoleSaveBtn.disabled = true;
            setEditRoleError('');
            try {
                await saveRoleChange(currentEditUserId, selectedRoleId);
            } finally {
                editRoleSaveBtn.disabled = false;
            }
        });
    }

    if (memberSearchInput) memberSearchInput.addEventListener('input', scheduleFetchMembers);
    if (memberRoleFilter) memberRoleFilter.addEventListener('change', fetchMembers);
    if (exportMembersCsvBtn) exportMembersCsvBtn.addEventListener('click', exportMembersCsv);
    if (printMembersBtn) printMembersBtn.addEventListener('click', printMembersReport);

    const detailValue = value => escapeHtml(value || value === 0 ? value : '-');

    const detailItem = (label, value) => `
        <div class="report-detail-item">
            <span>${escapeHtml(label)}</span>
            <strong>${detailValue(value)}</strong>
        </div>
    `;

    const reportTypeText = type => ({
        post: 'Bài viết',
        comment: 'Bình luận',
        user: 'Tài khoản'
    }[type] || type || '-');

    const personName = person => {
        if (!person) return '-';
        return person.FullName || person.Username || (person.UserID ? `User #${person.UserID}` : '-');
    };

    const renderPersonSummary = person => {
        if (!person || !person.UserID) return '<p class="text-muted mb-0">Không có dữ liệu.</p>';
        return `
            <div class="report-person-box">
                <strong>${escapeHtml(personName(person))}</strong>
                <span>@${escapeHtml(person.Username || '-')}</span>
                <span>UserID: ${escapeHtml(person.UserID)}</span>
                ${person.Email ? `<span>${escapeHtml(person.Email)}</span>` : ''}
            </div>
        `;
    };

    const renderReportDetail = detail => {
        const post = detail.post || null;
        const comment = detail.comment || null;
        const reportedUser = detail.reportedUser || null;
        const reporter = detail.reporter || null;
        const images = Array.isArray(detail.images) ? detail.images : [];
        const contentBlocks = [];

        if (detail.reportType === 'post' && post) {
            contentBlocks.push(`
                <div class="report-detail-panel">
                    <h6>Nội dung bài viết bị báo cáo</h6>
                    <div class="report-detail-grid">
                        ${detailItem('PostID', post.PostID)}
                        ${detailItem('Tác giả bài viết', personName(post.author))}
                        ${detailItem('CreatedAt', post.CreatedAt)}
                    </div>
                    <div class="report-content-box">${detailValue(post.Content)}</div>
                    ${images.length ? `<div class="report-image-list">${images.map(image => `<img src="${escapeHtml(normalizeAssetPath(image))}" alt="post image">`).join('')}</div>` : ''}
                </div>
            `);
        }

        if (detail.reportType === 'comment') {
            contentBlocks.push(`
                <div class="report-detail-panel">
                    <h6>Bài viết gốc</h6>
                    <div class="report-detail-grid">
                        ${detailItem('PostID', post ? post.PostID : null)}
                        ${detailItem('Tác giả bài viết', post ? personName(post.author) : null)}
                        ${detailItem('CreatedAt', post ? post.CreatedAt : null)}
                    </div>
                    <div class="report-content-box">${detailValue(post ? post.Content : null)}</div>
                </div>
                <div class="report-detail-panel">
                    <h6>Bình luận bị báo cáo</h6>
                    <div class="report-detail-grid">
                        ${detailItem('CommentID', comment ? comment.CommentID : null)}
                        ${detailItem('Tác giả bình luận', comment ? personName(comment.author) : null)}
                        ${detailItem('CreatedAt', comment ? comment.CreatedAt : null)}
                    </div>
                    <div class="report-content-box">${detailValue(comment ? comment.Content : null)}</div>
                </div>
            `);
        }

        if (detail.reportType === 'user') {
            contentBlocks.push(`
                <div class="report-detail-panel">
                    <h6>Tài khoản bị báo cáo</h6>
                    <div class="report-detail-grid">
                        ${detailItem('UserID', reportedUser ? reportedUser.UserID : null)}
                        ${detailItem('Username', reportedUser ? reportedUser.Username : null)}
                        ${detailItem('FullName', reportedUser ? reportedUser.FullName : null)}
                        ${detailItem('Email', reportedUser ? reportedUser.Email : null)}
                        ${detailItem('RoleName', reportedUser ? reportedUser.RoleName : null)}
                        ${detailItem('CreatedAt', reportedUser ? reportedUser.CreatedAt : null)}
                        ${detailItem('IsActive', reportedUser && Number(reportedUser.IsActive) === 1 ? 'Hoạt động' : 'Bị khóa')}
                    </div>
                </div>
            `);
        }

        return `
            <div class="report-detail-section">
                <h6>Thông tin báo cáo</h6>
                <div class="report-detail-grid">
                    ${detailItem('ReportID', detail.ReportID)}
                    ${detailItem('Loại đối tượng', reportTypeText(detail.reportType))}
                    ${detailItem('Reason', detail.Reason)}
                    ${detailItem('CreatedAt', detail.CreatedAt)}
                    ${detailItem('Status', detail.Status)}
                    ${detailItem('Details', detail.Details)}
                </div>
            </div>
            <div class="report-detail-section">
                <h6>Người liên quan</h6>
                <div class="report-people-grid">
                    <div>
                        <span class="report-detail-label">Người báo cáo</span>
                        ${renderPersonSummary(reporter)}
                    </div>
                    <div>
                        <span class="report-detail-label">Đối tượng bị báo cáo</span>
                        ${renderPersonSummary(reportedUser)}
                    </div>
                </div>
            </div>
            <div class="report-detail-section">
                <h6>Nội dung bị báo cáo</h6>
                ${contentBlocks.join('') || '<p class="text-muted mb-0">Không có nội dung liên quan.</p>'}
            </div>
        `;
    };

    async function openReportDetail(reportId) {
        if (!reportDetailModal || !reportDetailContent || !window.ADMIN_REPORT_DETAIL_URL) return;

        reportDetailContent.innerHTML = '';
        if (reportDetailError) {
            reportDetailError.classList.add('d-none');
            reportDetailError.textContent = '';
        }
        if (reportDetailLoading) reportDetailLoading.classList.remove('d-none');
        reportDetailModal.show();

        try {
            const url = new URL(window.ADMIN_REPORT_DETAIL_URL, window.location.href);
            url.searchParams.set('reportId', reportId);
            const res = await fetch(url.toString(), {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();

            if (!res.ok || !data.success) {
                throw new Error(data.message || 'Không thể lấy chi tiết báo cáo.');
            }

            reportDetailContent.innerHTML = renderReportDetail(data.data || {});
        } catch (err) {
            if (reportDetailError) {
                reportDetailError.textContent = err.message || 'Không thể lấy chi tiết báo cáo.';
                reportDetailError.classList.remove('d-none');
            }
        } finally {
            if (reportDetailLoading) reportDetailLoading.classList.add('d-none');
        }
    }

    document.addEventListener('click', event => {
        const detailButton = event.target.closest('.btn-report-detail, .btn-report-detail-link');
        if (detailButton) {
            openReportDetail(detailButton.dataset.reportId);
        }
    });

    async function handleReportAction(reportId, action) {
        const titleMap = {
            ignore: 'Bỏ qua báo cáo',
            hide: 'Ẩn nội dung được báo cáo',
            warn: 'Cảnh cáo người dùng'
        };

        const confirmed = await showConfirmModal(
            `Bạn có chắc chắn muốn thực hiện: ${titleMap[action] || action}?`,
            'Xác nhận hành động',
            'Thực hiện',
            'Hủy'
        );

        if (!confirmed) return;

        const adminNote = await showAdminNoteModal();
        if (adminNote === null) return;

        const formData = new FormData();
        formData.append('reportId', reportId);
        formData.append('action', action);
        formData.append('adminNote', adminNote);

        try {
            const res = await fetch(window.ADMIN_PROCESS_REPORT_URL, {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });

            const data = await res.json();
            if (!res.ok || !data.success) {
                await showAlertModal(data.message || 'Không thể xử lý báo cáo.', 'Lỗi', 'Đóng');
                return;
            }

            const reportIdValue = data.data && data.data.reportId ? data.data.reportId : reportId;
            const reportElement = document.getElementById(`report-row-${reportIdValue}`);
            if (reportElement) {
                const statusBadge = reportElement.querySelector('td:nth-child(5) .badge');
                const actionsCell = reportElement.querySelector('.report-actions');

                if (statusBadge) {
                    statusBadge.textContent = 'Đã xử lý';
                    statusBadge.className = 'badge rounded-pill bg-success text-white report-status-badge';
                }

                if (actionsCell) {
                    actionsCell.innerHTML = `<div class="report-actions-group is-completed">
                        <button type="button" class="btn btn-outline-brown btn-sm btn-report-detail btn-icon-detail" data-report-id="${reportIdValue}" title="Xem chi tiết" aria-label="Xem chi tiết"><i class="bi bi-eye"></i></button>
                        <span class="report-action-completed"><i class="bi bi-check2-all"></i> Hoàn tất</span>
                    </div>`;
                }
            }

            const reportStatElement = document.querySelector('#overview .stat-value.text-danger');
            if (reportStatElement) {
                const currentCount = parseInt(reportStatElement.textContent, 10) || 0;
                if (currentCount > 0) reportStatElement.textContent = currentCount - 1;
            }

            await showAlertModal(data.message, 'Thành công', 'Đóng');
        } catch (err) {
            console.error('Report action error:', err);
            await showAlertModal('Có lỗi xảy ra khi gọi API xử lý báo cáo.', 'Lỗi AJAX', 'Đóng');
        }
    }

    function showReportDetails(reportId) {
        openReportDetail(reportId);
    }

    const contentPostsTableBody = document.getElementById('contentPostsTableBody');
    const contentCommentsTableBody = document.getElementById('contentCommentsTableBody');
    const contentHashtagsTableBody = document.getElementById('contentHashtagsTableBody');
    const contentPostSearch = document.getElementById('contentPostSearch');
    const contentPostStatusFilter = document.getElementById('contentPostStatusFilter');
    const contentPostPrivacyFilter = document.getElementById('contentPostPrivacyFilter');
    const contentCommentSearch = document.getElementById('contentCommentSearch');
    const contentCommentStatusFilter = document.getElementById('contentCommentStatusFilter');
    const contentHashtagSearch = document.getElementById('contentHashtagSearch');
    const contentHashtagStatusFilter = document.getElementById('contentHashtagStatusFilter');
    const contentDetailModalEl = document.getElementById('contentDetailModal');
    const contentDetailModalLabel = document.getElementById('contentDetailModalLabel');
    const contentDetailBody = document.getElementById('contentDetailBody');
    const contentDetailLoading = document.getElementById('contentDetailLoading');
    const contentDetailError = document.getElementById('contentDetailError');
    const contentDetailModal = contentDetailModalEl ? new bootstrap.Modal(contentDetailModalEl) : null;
    let contentPostTimer = null;
    let contentCommentTimer = null;
    let contentHashtagTimer = null;
    let contentLoaded = false;

    const contentEmptyRow = colspan => `<tr><td colspan="${colspan}" class="text-center text-muted py-4">Không tìm thấy dữ liệu phù hợp</td></tr>`;
    const compactText = (value, max = 120) => {
        const text = String(value || '').replace(/\s+/g, ' ').trim();
        return text.length > max ? `${text.slice(0, max - 3)}...` : text;
    };
    const contentPersonName = item => item.FullName || item.Username || 'Người dùng';
    const contentAvatarSrc = item => {
        const avatar = item.ProfilePictureUrl || item.avatar || '';
        if (!avatar) return `${window.ADMIN_BASE_URL || ''}Public/assets/img/default-avatar.jpg`;
        if (/^https?:\/\//i.test(avatar)) return avatar;
        return `${window.ADMIN_BASE_URL || ''}${avatar}`;
    };
    const contentHiddenBadgeHtml = isHidden => Number(isHidden) === 1
        ? '<span class="badge rounded-pill content-status-badge is-hidden">Đã ẩn</span>'
        : '<span class="badge rounded-pill content-status-badge is-visible">Hiển thị</span>';
    const contentToggleButtonHtml = (type, id, isHidden) => {
        const hidden = Number(isHidden) === 1;
        return `<button type="button" class="btn btn-sm ${hidden ? 'btn-pink-admin' : 'btn-outline-danger'} btn-content-toggle" data-content-type="${type}" data-id="${id}" data-is-hidden="${hidden ? 1 : 0}">${hidden ? 'Hiện' : 'Ẩn'}</button>`;
    };
    const contentActionsHtml = (type, id, isHidden, withDetail = true) => `
        <div class="content-actions">
            ${withDetail ? `<button type="button" class="btn btn-outline-brown btn-sm btn-icon-detail btn-content-detail" data-content-type="${type}" data-id="${id}" title="Xem chi tiết" aria-label="Xem chi tiết"><i class="bi bi-eye"></i></button>` : ''}
            ${contentToggleButtonHtml(type, id, isHidden)}
            <button type="button" class="btn btn-outline-danger btn-sm btn-content-delete" data-content-type="${type}" data-id="${id}" title="Xóa" aria-label="Xóa"><i class="bi bi-trash"></i></button>
        </div>
    `;

    const fetchJson = async (url, options = {}) => {
        const res = await fetch(url, {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json', ...(options.headers || {}) },
            ...options
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
            throw new Error(data.message || 'Yêu cầu không thành công.');
        }
        return data;
    };

    const formatNumber = value => new Intl.NumberFormat('vi-VN').format(Number(value || 0));
    const rosePalette = ['#d69096', '#795d4a', '#e8b4c3', '#8aa889', '#f0c88a', '#c75d72'];
    let statisticsLoaded = false;
    const chartInstances = {};
    const statisticsRankingLimit = document.getElementById('statisticsRankingLimit');

    const updateOverviewStats = stats => {
        if (!stats) return;
        document.querySelectorAll('[data-overview-stat]').forEach(element => {
            const key = element.dataset.overviewStat;
            if (Object.prototype.hasOwnProperty.call(stats, key)) {
                element.textContent = formatNumber(stats[key]);
            }
        });

        const lastUpdated = document.getElementById('overviewLastUpdated');
        if (lastUpdated && stats.lastUpdated) {
            lastUpdated.textContent = stats.lastUpdated;
        }
    };

    const loadOverviewStats = async () => {
        if (!window.ADMIN_OVERVIEW_STATS_URL) return;
        try {
            const data = await fetchJson(window.ADMIN_OVERVIEW_STATS_URL);
            updateOverviewStats(data.data || {});
        } catch (err) {
            console.error('Overview stats error:', err);
        }
    };

    const statisticsEmpty = message => `<div class="statistics-empty">${escapeHtml(message || 'Không có dữ liệu phù hợp')}</div>`;
    const visibilityBadge = isHidden => Number(isHidden) === 1
        ? '<span class="badge rounded-pill content-status-badge is-hidden">Đã ẩn</span>'
        : '<span class="badge rounded-pill content-status-badge is-visible">Hiển thị</span>';
    const activeBadge = isActive => Number(isActive) === 1
        ? '<span class="badge rounded-pill content-status-badge is-visible">Hoạt động</span>'
        : '<span class="badge rounded-pill content-status-badge is-hidden">Bị khóa</span>';
    const personDisplayName = item => item.FullName || item.Username || 'Người dùng';

    const renderTopPosts = posts => {
        const target = document.getElementById('topPostsRanking');
        if (!target) return;
        target.classList.remove('loading-state');
        if (!posts || posts.length === 0) {
            target.innerHTML = statisticsEmpty('Chưa có bài viết để xếp hạng');
            return;
        }

        target.innerHTML = posts.map((post, index) => `
            <div class="statistics-rank-item">
                <span class="rank-number">${index + 1}</span>
                ${post.ThumbnailUrl ? `<img class="rank-thumb" src="${escapeHtml(normalizeAssetPath(post.ThumbnailUrl))}" alt="thumbnail">` : '<div class="rank-thumb rank-thumb-empty"><i class="bi bi-image"></i></div>'}
                <div class="rank-main">
                    <strong>#${escapeHtml(post.PostID)} ${escapeHtml(compactText(post.Content, 80) || 'Bài viết không có nội dung')}</strong>
                    <span>${escapeHtml(personDisplayName(post))} · ${escapeHtml(post.CreatedAt || '')}</span>
                </div>
                <div class="rank-metrics">
                    <span><i class="bi bi-heart"></i>${formatNumber(post.LikeCount)}</span>
                    <span><i class="bi bi-chat"></i>${formatNumber(post.CommentCount)}</span>
                </div>
            </div>
        `).join('');
    };

    const renderTopUsers = users => {
        const target = document.getElementById('topUsersRanking');
        if (!target) return;
        target.classList.remove('loading-state');
        if (!users || users.length === 0) {
            target.innerHTML = statisticsEmpty('Chưa có dữ liệu follower');
            return;
        }

        target.innerHTML = users.map((user, index) => `
            <div class="statistics-rank-item">
                <span class="rank-number">${index + 1}</span>
                <img class="rank-avatar" src="${escapeHtml(contentAvatarSrc(user))}" alt="avatar">
                <div class="rank-main">
                    <strong>${escapeHtml(personDisplayName(user))}</strong>
                    <span>@${escapeHtml(user.Username || '')} · ${formatNumber(user.PostCount)} bài viết</span>
                </div>
                <div class="rank-metrics wide">
                    <span><i class="bi bi-person-heart"></i>${formatNumber(user.FollowerCount)}</span>
                    ${activeBadge(user.IsActive)}
                </div>
            </div>
        `).join('');
    };

    const renderTopHashtags = hashtags => {
        const target = document.getElementById('topHashtagsRanking');
        if (!target) return;
        target.classList.remove('loading-state');
        if (!hashtags || hashtags.length === 0) {
            target.innerHTML = statisticsEmpty('Chưa có hashtag để xếp hạng');
            return;
        }

        target.innerHTML = hashtags.map((hashtag, index) => `
            <div class="statistics-rank-item compact">
                <span class="rank-number">${index + 1}</span>
                <div class="rank-main">
                    <strong>#${escapeHtml(hashtag.HashtagName || '')}</strong>
                    <span>UsageCount: ${formatNumber(hashtag.UsageCount)}</span>
                </div>
                <div class="rank-metrics wide">
                    <span><i class="bi bi-file-earmark-post"></i>${formatNumber(hashtag.PostCount)}</span>
                    ${visibilityBadge(hashtag.IsHidden)}
                </div>
            </div>
        `).join('');
    };

    const renderTopReportedUsers = users => {
        const target = document.getElementById('topReportedUsersRanking');
        if (!target) return;
        target.classList.remove('loading-state');
        if (!users || users.length === 0) {
            target.innerHTML = statisticsEmpty('Chưa có user bị report');
            return;
        }

        target.innerHTML = users.map((user, index) => `
            <div class="statistics-rank-item compact">
                <span class="rank-number">${index + 1}</span>
                <div class="rank-main">
                    <strong>${escapeHtml(personDisplayName(user))}</strong>
                    <span>@${escapeHtml(user.Username || '')} · ${escapeHtml(user.RoleName || '-')}</span>
                </div>
                <div class="rank-metrics wide">
                    <span><i class="bi bi-flag"></i>${formatNumber(user.ReportCount)}</span>
                    ${activeBadge(user.IsActive)}
                </div>
            </div>
        `).join('');
    };

    const renderMostActiveUsers = users => {
        const target = document.getElementById('mostActiveUsersInsight');
        if (!target) return;
        target.classList.remove('loading-state');
        if (!users || users.length === 0) {
            target.innerHTML = statisticsEmpty('Chưa có hoạt động người dùng');
            return;
        }

        target.innerHTML = users.map((user, index) => `
            <div class="statistics-rank-item">
                <span class="rank-number">${index + 1}</span>
                <img class="rank-avatar" src="${escapeHtml(contentAvatarSrc(user))}" alt="avatar">
                <div class="rank-main">
                    <strong>${escapeHtml(personDisplayName(user))}</strong>
                    <span>${formatNumber(user.PostCount)} bài · ${formatNumber(user.CommentCount)} bình luận · ${formatNumber(user.LikeCount)} like</span>
                </div>
                <div class="rank-metrics"><span>${formatNumber(user.ActivityScore)} điểm</span></div>
            </div>
        `).join('');
    };

    const renderPeakHour = peak => {
        const target = document.getElementById('peakPostHourInsight');
        if (!target) return;
        target.classList.remove('loading-state');
        if (!peak) {
            target.innerHTML = statisticsEmpty('Chưa có bài viết để tính khung giờ');
            return;
        }

        target.innerHTML = `
            <div class="insight-highlight">
                <span class="insight-kicker">Khung giờ cao điểm</span>
                <strong>${escapeHtml(peak.label || '-')}</strong>
                <small>${formatNumber(peak.postCount)} bài viết được đăng</small>
            </div>
        `;
    };

    const renderRecentHotHashtags = hashtags => {
        const target = document.getElementById('recentHashtagsInsight');
        if (!target) return;
        target.classList.remove('loading-state');
        if (!hashtags || hashtags.length === 0) {
            target.innerHTML = statisticsEmpty('Chưa có hashtag nổi bật');
            return;
        }

        target.innerHTML = hashtags.map(hashtag => `
            <div class="insight-list-item">
                <div class="insight-list-main">
                    <strong>#${escapeHtml(hashtag.HashtagName || '')}</strong>
                    <span>${formatNumber(hashtag.RecentPostCount)} bài trong 7 ngày · UsageCount ${formatNumber(hashtag.UsageCount)}</span>
                </div>
                <div class="insight-list-badge">${visibilityBadge(hashtag.IsHidden)}</div>
            </div>
        `).join('');
    };

    const renderLatestReports = reports => {
        const target = document.getElementById('latestReportsInsight');
        if (!target) return;
        target.classList.remove('loading-state');
        if (!reports || reports.length === 0) {
            target.innerHTML = statisticsEmpty('Chưa có report mới');
            return;
        }

        target.innerHTML = reports.map(report => `
            <div class="insight-list-item">
                <div class="insight-list-main">
                    <strong>${escapeHtml(report.Reason || 'Không rõ lý do')}</strong>
                    <span>${escapeHtml(report.ReporterFullName || report.ReporterUsername || 'Ẩn danh')} báo cáo ${escapeHtml(report.ReportedFullName || report.ReportedUsername || 'nội dung')} · ${escapeHtml(report.CreatedAt || '')}</span>
                </div>
                <div class="insight-list-badge"><span class="content-pill">${escapeHtml(report.Status || '-')}</span></div>
            </div>
        `).join('');
    };

    const safeChartData = chartData => {
        const labels = Array.isArray(chartData && chartData.labels) ? chartData.labels : [];
        const values = Array.isArray(chartData && chartData.values) ? chartData.values.map(value => Number(value || 0)) : [];
        return labels.length ? { labels, values } : { labels: ['Không có dữ liệu'], values: [0] };
    };

    const createOrUpdateChart = (canvasId, type, chartData, label) => {
        const canvas = document.getElementById(canvasId);
        if (!canvas || !window.Chart) return;

        const normalized = safeChartData(chartData);
        if (chartInstances[canvasId]) {
            chartInstances[canvasId].destroy();
        }

        const isLine = type === 'line';
        chartInstances[canvasId] = new Chart(canvas, {
            type,
            data: {
                labels: normalized.labels,
                datasets: [{
                    label,
                    data: normalized.values,
                    borderColor: '#c97b95',
                    backgroundColor: isLine ? 'rgba(217, 140, 163, 0.16)' : rosePalette,
                    pointBackgroundColor: '#d69096',
                    pointBorderColor: '#fff',
                    borderWidth: isLine ? 2 : 0,
                    tension: 0.35,
                    fill: isLine
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: !isLine,
                        labels: {
                            color: '#5b433e',
                            usePointStyle: true,
                            boxWidth: 9
                        }
                    }
                },
                scales: isLine ? {
                    x: { ticks: { color: '#795d4a' }, grid: { color: 'rgba(121, 91, 74, 0.08)' } },
                    y: { beginAtZero: true, ticks: { color: '#795d4a', precision: 0 }, grid: { color: 'rgba(121, 91, 74, 0.08)' } }
                } : {}
            }
        });
    };

    const setRankingLoading = () => {
        ['topPostsRanking', 'topUsersRanking', 'topHashtagsRanking', 'topReportedUsersRanking'].forEach(id => {
            const target = document.getElementById(id);
            if (target) {
                target.classList.add('loading-state');
                target.innerHTML = 'Đang tải...';
            }
        });
    };

    const clearRankingLoading = () => {
        ['topPostsRanking', 'topUsersRanking', 'topHashtagsRanking', 'topReportedUsersRanking'].forEach(id => {
            const target = document.getElementById(id);
            if (target) target.classList.remove('loading-state');
        });
    };

    const loadStatisticsRankings = async () => {
        const url = new URL(window.ADMIN_STATISTICS_RANKINGS_URL, window.location.href);
        url.searchParams.set('limit', statisticsRankingLimit ? statisticsRankingLimit.value : '5');
        const data = await fetchJson(url.toString());
        const rankings = data.data || {};
        renderTopPosts(rankings.topPostsByLikes || []);
        renderTopUsers(rankings.topUsersByFollowers || []);
        renderTopHashtags(rankings.topHashtags || []);
        renderTopReportedUsers(rankings.topReportedUsers || []);
        clearRankingLoading();
    };

    const loadStatisticsCharts = async () => {
        const data = await fetchJson(window.ADMIN_STATISTICS_CHARTS_URL);
        const charts = data.data || {};
        createOrUpdateChart('postsByDayChart', 'line', charts.postsByDay, 'Bài viết');
        createOrUpdateChart('usersByDayChart', 'line', charts.usersByDay, 'Người dùng');
        createOrUpdateChart('reportStatusChart', 'doughnut', charts.reportStatus, 'Report');
        createOrUpdateChart('postVisibilityChart', 'doughnut', charts.postVisibility, 'Bài viết');
    };

    const loadStatisticsInsights = async () => {
        const data = await fetchJson(window.ADMIN_STATISTICS_INSIGHTS_URL);
        const insights = data.data || {};
        renderMostActiveUsers(insights.mostActiveUsers || []);
        renderPeakHour(insights.peakPostHour || null);
        renderRecentHotHashtags(insights.recentHotHashtags || []);
        renderLatestReports(insights.latestReports || []);
    };

    const loadStatistics = async () => {
        if (statisticsLoaded) return;
        statisticsLoaded = true;
        try {
            await Promise.all([
                loadStatisticsRankings(),
                loadStatisticsCharts(),
                loadStatisticsInsights()
            ]);
        } catch (err) {
            console.error('Statistics load error:', err);
            document.querySelectorAll('#statistics .loading-state').forEach(element => {
                element.innerHTML = statisticsEmpty('Không thể tải dữ liệu thống kê');
            });
        }
    };

    if (statisticsRankingLimit) {
        statisticsRankingLimit.addEventListener('change', async () => {
            setRankingLoading();
            try {
                await loadStatisticsRankings();
            } catch (err) {
                console.error('Statistics ranking limit error:', err);
                clearRankingLoading();
                ['topPostsRanking', 'topUsersRanking', 'topHashtagsRanking', 'topReportedUsersRanking'].forEach(id => {
                    const target = document.getElementById(id);
                    if (target) target.innerHTML = statisticsEmpty('Không thể tải dữ liệu ranking');
                });
            }
        });
    }

    document.querySelectorAll('#statisticsSubTab button[data-bs-toggle="pill"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', event => {
            if (event.target.getAttribute('data-bs-target') === '#statistics-charts') {
                setTimeout(() => {
                    Object.values(chartInstances).forEach(chart => chart.resize());
                }, 60);
            }
        });
    });

    const renderContentPostRow = post => {
        const thumbnail = post.ThumbnailUrl
            ? `<img class="content-thumbnail" src="${escapeHtml(normalizeAssetPath(post.ThumbnailUrl))}" alt="thumbnail">`
            : '<span class="text-muted small">Không có</span>';
        return `
            <tr id="content-post-row-${post.PostID}" data-id="${post.PostID}">
                <td class="fw-bold">#${escapeHtml(post.PostID)}</td>
                <td><div class="content-user-cell"><img src="${escapeHtml(contentAvatarSrc(post))}" alt="avatar"><div><strong>${escapeHtml(contentPersonName(post))}</strong><span>@${escapeHtml(post.Username || '')}</span></div></div></td>
                <td><span class="content-clamp" title="${escapeHtml(post.Content || '')}">${escapeHtml(compactText(post.Content, 130))}</span></td>
                <td>${thumbnail}</td>
                <td class="small text-muted">${escapeHtml(post.CreatedAt || '')}</td>
                <td><span class="content-pill">${escapeHtml(post.Privacy || 'public')}</span></td>
                <td class="content-status">${contentHiddenBadgeHtml(post.IsHidden)}</td>
                <td class="small"><span class="member-count-pill"><i class="bi bi-heart"></i>${Number(post.LikeCount || 0)}</span><span class="member-count-pill"><i class="bi bi-chat"></i>${Number(post.CommentCount || 0)}</span></td>
                <td class="text-end">${contentActionsHtml('post', post.PostID, post.IsHidden)}</td>
            </tr>
        `;
    };

    const renderContentCommentRow = comment => `
        <tr id="content-comment-row-${comment.CommentID}" data-id="${comment.CommentID}">
            <td class="fw-bold">#${escapeHtml(comment.CommentID)}</td>
            <td><div class="content-user-cell"><img src="${escapeHtml(contentAvatarSrc(comment))}" alt="avatar"><div><strong>${escapeHtml(contentPersonName(comment))}</strong><span>@${escapeHtml(comment.Username || '')}</span></div></div></td>
            <td><span class="content-clamp" title="${escapeHtml(comment.Content || '')}">${escapeHtml(compactText(comment.Content, 110))}</span></td>
            <td><span class="content-clamp" title="${escapeHtml(comment.PostContent || '')}">${escapeHtml(compactText(comment.PostContent, 95))}</span></td>
            <td class="small">${escapeHtml(comment.PostAuthorFullName || comment.PostAuthorUsername || '-')}</td>
            <td class="small text-muted">${escapeHtml(comment.CreatedAt || '')}</td>
            <td class="small">${comment.ParentCommentID ? `#${escapeHtml(comment.ParentCommentID)}` : '-'}</td>
            <td class="content-status">${contentHiddenBadgeHtml(comment.IsHidden)}</td>
            <td class="text-end">${contentActionsHtml('comment', comment.CommentID, comment.IsHidden)}</td>
        </tr>
    `;

    const renderContentHashtagRow = hashtag => `
        <tr id="content-hashtag-row-${hashtag.HashtagID}" data-id="${hashtag.HashtagID}">
            <td class="fw-bold">#${escapeHtml(hashtag.HashtagID)}</td>
            <td><span class="content-hashtag-name">#${escapeHtml(hashtag.HashtagName || '')}</span></td>
            <td>${Number(hashtag.UsageCount || 0)}</td>
            <td class="small text-muted">${escapeHtml(hashtag.CreatedAt || '')}</td>
            <td class="content-status">${contentHiddenBadgeHtml(hashtag.IsHidden)}</td>
            <td>${Number(hashtag.PostCount || 0)}</td>
            <td class="text-end">${contentActionsHtml('hashtag', hashtag.HashtagID, hashtag.IsHidden, false)}</td>
        </tr>
    `;

    const loadContentPosts = async () => {
        if (!contentPostsTableBody || !window.ADMIN_LIST_CONTENT_POSTS_URL) return;
        const url = new URL(window.ADMIN_LIST_CONTENT_POSTS_URL, window.location.href);
        url.searchParams.set('keyword', contentPostSearch ? contentPostSearch.value.trim() : '');
        url.searchParams.set('status', contentPostStatusFilter ? contentPostStatusFilter.value : '');
        url.searchParams.set('privacy', contentPostPrivacyFilter ? contentPostPrivacyFilter.value : '');
        try {
            const data = await fetchJson(url.toString());
            const posts = (data.data && data.data.posts) || [];
            contentPostsTableBody.innerHTML = posts.length ? posts.map(renderContentPostRow).join('') : contentEmptyRow(9);
        } catch (err) {
            contentPostsTableBody.innerHTML = `<tr><td colspan="9" class="text-center text-danger py-4">${escapeHtml(err.message)}</td></tr>`;
        }
    };

    const loadContentComments = async () => {
        if (!contentCommentsTableBody || !window.ADMIN_LIST_CONTENT_COMMENTS_URL) return;
        const url = new URL(window.ADMIN_LIST_CONTENT_COMMENTS_URL, window.location.href);
        url.searchParams.set('keyword', contentCommentSearch ? contentCommentSearch.value.trim() : '');
        url.searchParams.set('status', contentCommentStatusFilter ? contentCommentStatusFilter.value : '');
        try {
            const data = await fetchJson(url.toString());
            const comments = (data.data && data.data.comments) || [];
            contentCommentsTableBody.innerHTML = comments.length ? comments.map(renderContentCommentRow).join('') : contentEmptyRow(9);
        } catch (err) {
            contentCommentsTableBody.innerHTML = `<tr><td colspan="9" class="text-center text-danger py-4">${escapeHtml(err.message)}</td></tr>`;
        }
    };

    const loadContentHashtags = async () => {
        if (!contentHashtagsTableBody || !window.ADMIN_LIST_CONTENT_HASHTAGS_URL) return;
        const url = new URL(window.ADMIN_LIST_CONTENT_HASHTAGS_URL, window.location.href);
        url.searchParams.set('keyword', contentHashtagSearch ? contentHashtagSearch.value.trim() : '');
        url.searchParams.set('status', contentHashtagStatusFilter ? contentHashtagStatusFilter.value : '');
        try {
            const data = await fetchJson(url.toString());
            const hashtags = (data.data && data.data.hashtags) || [];
            contentHashtagsTableBody.innerHTML = hashtags.length ? hashtags.map(renderContentHashtagRow).join('') : contentEmptyRow(7);
        } catch (err) {
            contentHashtagsTableBody.innerHTML = `<tr><td colspan="7" class="text-center text-danger py-4">${escapeHtml(err.message)}</td></tr>`;
        }
    };

    const loadAllContent = () => {
        contentLoaded = true;
        loadContentPosts();
        loadContentComments();
        loadContentHashtags();
    };

    const detailGridItem = (label, value) => `<div class="report-detail-item"><span>${escapeHtml(label)}</span><strong>${escapeHtml(value || value === 0 ? value : '-')}</strong></div>`;
    const contentBox = (title, value) => `<div class="report-detail-panel"><h6>${escapeHtml(title)}</h6><div class="report-content-box">${escapeHtml(value || '-')}</div></div>`;
    const renderPostDetail = post => {
        const images = Array.isArray(post.images) ? post.images : [];
        return `
            <div class="report-detail-section"><h6>Thông tin bài viết</h6><div class="report-detail-grid">
                ${detailGridItem('PostID', post.PostID)}${detailGridItem('Tác giả', contentPersonName(post))}
                ${detailGridItem('Username', post.Username ? `@${post.Username}` : '-')}${detailGridItem('CreatedAt', post.CreatedAt)}
                ${detailGridItem('Privacy', post.Privacy)}${detailGridItem('Trạng thái', Number(post.IsHidden) === 1 ? 'Đã ẩn' : 'Hiển thị')}
                ${detailGridItem('Số like', Number(post.LikeCount || 0))}${detailGridItem('Số comment', Number(post.CommentCount || 0))}
            </div></div>
            ${contentBox('Nội dung đầy đủ', post.Content)}
            <div class="report-detail-panel"><h6>Ảnh bài viết</h6>${images.length ? `<div class="report-image-list">${images.map(image => `<img src="${escapeHtml(normalizeAssetPath(image))}" alt="post image">`).join('')}</div>` : '<p class="text-muted mb-0">Không có ảnh.</p>'}</div>
        `;
    };
    const renderCommentDetail = comment => `
        <div class="report-detail-section"><h6>Thông tin bình luận</h6><div class="report-detail-grid">
            ${detailGridItem('CommentID', comment.CommentID)}${detailGridItem('Người bình luận', contentPersonName(comment))}
            ${detailGridItem('Username', comment.Username ? `@${comment.Username}` : '-')}${detailGridItem('CreatedAt', comment.CreatedAt)}
            ${detailGridItem('Trạng thái', Number(comment.IsHidden) === 1 ? 'Đã ẩn' : 'Hiển thị')}${detailGridItem('ParentCommentID', comment.ParentCommentID || '-')}
            ${detailGridItem('PostID', comment.PostID)}${detailGridItem('Tác giả bài viết', comment.PostAuthorFullName || comment.PostAuthorUsername || '-')}
        </div></div>
        ${contentBox('Nội dung comment', comment.Content)}
        ${contentBox('Bài viết gốc', comment.PostContent)}
        ${comment.ParentCommentID ? contentBox(`Comment cha #${comment.ParentCommentID} - ${comment.ParentFullName || comment.ParentUsername || '-'}`, comment.ParentContent) : ''}
    `;

    const openContentDetail = async (type, id) => {
        if (!contentDetailModal || !contentDetailBody) return;
        contentDetailBody.innerHTML = '';
        if (contentDetailError) {
            contentDetailError.textContent = '';
            contentDetailError.classList.add('d-none');
        }
        if (contentDetailLoading) contentDetailLoading.classList.remove('d-none');
        if (contentDetailModalLabel) contentDetailModalLabel.textContent = type === 'post' ? 'Chi tiết bài viết' : 'Chi tiết bình luận';
        contentDetailModal.show();
        try {
            const detailUrl = type === 'post' ? window.ADMIN_CONTENT_POST_DETAIL_URL : window.ADMIN_CONTENT_COMMENT_DETAIL_URL;
            const url = new URL(detailUrl, window.location.href);
            url.searchParams.set(type === 'post' ? 'postId' : 'commentId', id);
            const data = await fetchJson(url.toString());
            contentDetailBody.innerHTML = type === 'post' ? renderPostDetail(data.data || {}) : renderCommentDetail(data.data || {});
        } catch (err) {
            if (contentDetailError) {
                contentDetailError.textContent = err.message;
                contentDetailError.classList.remove('d-none');
            }
        } finally {
            if (contentDetailLoading) contentDetailLoading.classList.add('d-none');
        }
    };

    const rerenderContentRow = (type, item) => {
        const rowId = type === 'post' ? `content-post-row-${item.PostID}` : type === 'comment' ? `content-comment-row-${item.CommentID}` : `content-hashtag-row-${item.HashtagID}`;
        const row = document.getElementById(rowId);
        if (!row) return;
        row.outerHTML = type === 'post' ? renderContentPostRow(item) : type === 'comment' ? renderContentCommentRow(item) : renderContentHashtagRow(item);
    };

    const toggleContentHidden = async button => {
        const type = button.dataset.contentType;
        const id = Number(button.dataset.id);
        const nextHidden = Number(button.dataset.isHidden) === 1 ? 0 : 1;
        const urlMap = { post: window.ADMIN_TOGGLE_CONTENT_POST_URL, comment: window.ADMIN_TOGGLE_CONTENT_COMMENT_URL, hashtag: window.ADMIN_TOGGLE_CONTENT_HASHTAG_URL };
        const keyMap = { post: 'PostID', comment: 'CommentID', hashtag: 'HashtagID' };
        button.disabled = true;
        try {
            const data = await fetchJson(urlMap[type], {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ [keyMap[type]]: id, IsHidden: nextHidden })
            });
            const item = data.data && (data.data.post || data.data.comment || data.data.hashtag);
            if (item) rerenderContentRow(type, item);
        } catch (err) {
            await showAlertModal(err.message || 'Không thể cập nhật trạng thái.', 'Lỗi', 'Đóng');
            button.disabled = false;
        }
    };

    const deleteContentItem = async button => {
        const type = button.dataset.contentType;
        const id = Number(button.dataset.id);
        const labelMap = { post: 'bài viết', comment: 'bình luận', hashtag: 'hashtag' };
        const urlMap = { post: window.ADMIN_DELETE_CONTENT_POST_URL, comment: window.ADMIN_DELETE_CONTENT_COMMENT_URL, hashtag: window.ADMIN_DELETE_CONTENT_HASHTAG_URL };
        const keyMap = { post: 'PostID', comment: 'CommentID', hashtag: 'HashtagID' };
        const rowId = type === 'post' ? `content-post-row-${id}` : type === 'comment' ? `content-comment-row-${id}` : `content-hashtag-row-${id}`;
        const confirmed = await showConfirmModal(`Xóa vĩnh viễn ${labelMap[type]} #${id}? Thao tác này không thể hoàn tác.`, `Xóa ${labelMap[type]}`, 'Xóa', 'Hủy');
        if (!confirmed) return;
        button.disabled = true;
        try {
            const data = await fetchJson(urlMap[type], {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ [keyMap[type]]: id })
            });
            if (type === 'comment' && data.data && Array.isArray(data.data.DeletedCommentIDs)) {
                data.data.DeletedCommentIDs.forEach(commentId => {
                    const row = document.getElementById(`content-comment-row-${commentId}`);
                    if (row) row.remove();
                });
            } else {
                const row = document.getElementById(rowId);
                if (row) row.remove();
            }
        } catch (err) {
            await showAlertModal(err.message || `Không thể xóa ${labelMap[type]}.`, 'Lỗi', 'Đóng');
            button.disabled = false;
        }
    };

    if (contentPostSearch) contentPostSearch.addEventListener('input', () => { clearTimeout(contentPostTimer); contentPostTimer = setTimeout(loadContentPosts, 300); });
    if (contentCommentSearch) contentCommentSearch.addEventListener('input', () => { clearTimeout(contentCommentTimer); contentCommentTimer = setTimeout(loadContentComments, 300); });
    if (contentHashtagSearch) contentHashtagSearch.addEventListener('input', () => { clearTimeout(contentHashtagTimer); contentHashtagTimer = setTimeout(loadContentHashtags, 300); });
    if (contentPostStatusFilter) contentPostStatusFilter.addEventListener('change', loadContentPosts);
    if (contentPostPrivacyFilter) contentPostPrivacyFilter.addEventListener('change', loadContentPosts);
    if (contentCommentStatusFilter) contentCommentStatusFilter.addEventListener('change', loadContentComments);
    if (contentHashtagStatusFilter) contentHashtagStatusFilter.addEventListener('change', loadContentHashtags);

    document.addEventListener('click', event => {
        const detailButton = event.target.closest('.btn-content-detail');
        const toggleButton = event.target.closest('.btn-content-toggle');
        const deleteButton = event.target.closest('.btn-content-delete');
        if (detailButton) openContentDetail(detailButton.dataset.contentType, detailButton.dataset.id);
        if (toggleButton) toggleContentHidden(toggleButton);
        if (deleteButton) deleteContentItem(deleteButton);
    });

    const tabLinks = document.querySelectorAll('button[data-bs-toggle="tab"]');
    tabLinks.forEach(tab => {
        tab.addEventListener('shown.bs.tab', event => {
            console.log('Đã chuyển sang phân hệ: ' + event.target.getAttribute('data-bs-target'));
            if (event.target.getAttribute('data-bs-target') === '#content' && !contentLoaded) {
                loadAllContent();
            }
            if (event.target.getAttribute('data-bs-target') === '#statistics') {
                loadStatistics();
            }
        });
    });

    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async () => {
            const confirmed = await showConfirmModal(
                'Bạn chắc chắn muốn đăng xuất chứ?',
                'Đăng xuất',
                'Đăng xuất',
                'Hủy'
            );

            if (confirmed && logoutBtn.dataset.logoutUrl) {
                window.location.href = logoutBtn.dataset.logoutUrl;
            }
        });
    }

    renderMembers(Array.from(document.querySelectorAll('#membersTableBody tr[id^="member-row-"]')).map(row => ({
        UserID: row.dataset.userId,
        IsActive: row.dataset.isActive,
        Username: row.querySelector('small.text-muted') ? row.querySelector('small.text-muted').textContent.replace(/^@/, '') : '',
        FullName: row.querySelector('.fw-bold') ? row.querySelector('.fw-bold').textContent : '',
        RoleID: row.querySelector('.btn-edit-role') ? row.querySelector('.btn-edit-role').dataset.roleId : '',
        RoleName: row.querySelector('.member-role') ? row.querySelector('.member-role').textContent : '',
        PostCount: row.querySelector('.member-count-pill') ? row.querySelector('.member-count-pill').textContent.trim() : 0,
        ReportCount: row.querySelector('.member-count-pill.danger') ? row.querySelector('.member-count-pill.danger').textContent.trim() : 0,
        CreatedAt: row.children[3] ? row.children[3].textContent.trim() : '',
        joined: row.children[3] ? row.children[3].textContent.trim() : ''
    })));
    loadOverviewStats();
    fetchMembers();

    window.showConfirmModal = showConfirmModal;
    window.showAlertModal = showAlertModal;
    window.showAdminNoteModal = showAdminNoteModal;
    window.handleReportAction = handleReportAction;
    window.showReportDetails = showReportDetails;
});
