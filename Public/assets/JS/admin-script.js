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
                <td class="small text-muted member-role">${escapeHtml(member.RoleName || '')}</td>
                <td class="small">
                    <span class="member-count-pill"><i class="bi bi-file-earmark-post"></i>${member.PostCount}</span>
                    <span class="member-count-pill danger"><i class="bi bi-flag"></i>${member.ReportCount}</span>
                </td>
                <td class="small">${escapeHtml(member.joined || member.CreatedAt || '')}</td>
                <td class="member-status">${statusBadgeHtml(member.IsActive)}</td>
                <td class="text-end">
                    <div class="d-flex gap-2 justify-content-end flex-wrap">
                        <button type="button" class="btn btn-outline-brown btn-sm btn-member-detail" data-user-id="${member.UserID}"><i class="bi bi-eye"></i> Chi tiết</button>
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
                    statusBadge.className = 'badge rounded-pill bg-success text-white px-2.5 py-1 text-xs fw-medium';
                }

                if (actionsCell) {
                    actionsCell.innerHTML = '<span class="report-action-completed"><i class="bi bi-check2-all"></i> Hoàn tất</span>';
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
        const row = document.getElementById(`report-row-${reportId}`);
        if (!row) {
            showAlertModal('Không tìm thấy báo cáo.', 'Chi tiết báo cáo', 'Đóng');
            return;
        }

        const details = row.dataset.details || '';
        const message = details.trim() !== '' ? details : 'Không có chi tiết';
        showAlertModal(message, 'Chi tiết báo cáo', 'Đóng');
    }

    const tabLinks = document.querySelectorAll('button[data-bs-toggle="tab"]');
    tabLinks.forEach(tab => {
        tab.addEventListener('shown.bs.tab', event => {
            console.log('Đã chuyển sang phân hệ: ' + event.target.getAttribute('data-bs-target'));
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
    fetchMembers();

    window.showConfirmModal = showConfirmModal;
    window.showAlertModal = showAlertModal;
    window.showAdminNoteModal = showAdminNoteModal;
    window.handleReportAction = handleReportAction;
    window.showReportDetails = showReportDetails;
});
