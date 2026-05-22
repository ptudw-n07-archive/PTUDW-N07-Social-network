document.addEventListener('DOMContentLoaded', function() {
    console.log("Archive Management Center đã sẵn sàng!");

    const adminModal = document.getElementById('adminModal');
    const adminModalTitle = adminModal.querySelector('.admin-modal-title');
    const adminModalMessage = adminModal.querySelector('.admin-modal-message');
    const adminModalConfirm = adminModal.querySelector('[data-admin-modal-confirm]');
    const adminModalCancel = adminModal.querySelector('[data-admin-modal-cancel]');
    const adminModalClose = adminModal.querySelector('[data-admin-modal-close]');
    const adminModalBackdrop = adminModal.querySelector('.admin-modal-backdrop');

    let modalResolve = null;

    const openModal = () => {
        adminModal.classList.remove('d-none');
        requestAnimationFrame(() => adminModal.classList.add('active'));
    };

    const closeModal = (result = false) => {
        adminModal.classList.remove('active');
        setTimeout(() => adminModal.classList.add('d-none'), 250);
        if (modalResolve) {
            modalResolve(result);
            modalResolve = null;
        }
    };

    const showConfirmModal = (message, title = 'Xác nhận', confirmText = 'Xác nhận', cancelText = 'Hủy') => {
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
        adminModalTitle.textContent = title;
        adminModalMessage.textContent = message;
        adminModalConfirm.textContent = confirmText;
        adminModalCancel.style.display = 'none';

        openModal();
        return new Promise(resolve => {
            modalResolve = resolve;
        });
    };

    adminModalConfirm.addEventListener('click', () => closeModal(true));
    adminModalCancel.addEventListener('click', () => closeModal(false));
    adminModalClose.addEventListener('click', () => closeModal(false));
    adminModalBackdrop.addEventListener('click', () => closeModal(false));

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
        if (!adminNoteModal) {
            if (adminNoteResolve) {
                adminNoteResolve(note);
                adminNoteResolve = null;
            }
            return;
        }

        adminNoteModal.hide();
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

    const editBtns = document.querySelectorAll('.btn-outline-brown');
    editBtns.forEach(btn => {
        btn.addEventListener('click', async function() {
            const row = this.closest('tr');
            const userName = row.querySelector('.fw-bold').textContent;
            await showAlertModal('Bạn đang chuẩn bị chỉnh sửa quyền hạn của: ' + userName, 'Chỉnh sửa thành viên', 'Tiếp tục');
            const newRole = prompt("Nhập vai trò mới cho " + userName + ":", "Thành viên");
            if (newRole) {
                row.cells[1].textContent = newRole;
                await showAlertModal("Đã cập nhật vai trò mới thành công!", 'Hoàn tất', 'Đóng');
            }
        });
    });

    const tabLinks = document.querySelectorAll('button[data-bs-toggle="tab"]');
    tabLinks.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function (event) {
            const targetId = event.target.getAttribute('data-bs-target');
            console.log("Đã chuyển sang phân hệ: " + targetId);
        });
    });

    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async function() {
            const confirmed = await showConfirmModal("Bạn chắc chắn muốn đăng xuất chứ?", 'Đăng xuất', 'Đăng xuất', 'Hủy');
            if (confirmed) {
                const logoutUrl = logoutBtn.dataset.logoutUrl;
                if (logoutUrl) {
                    window.location.href = logoutUrl;
                }
            }
        });
    }

    window.showConfirmModal = showConfirmModal;
    window.showAlertModal = showAlertModal;
    window.showAdminNoteModal = showAdminNoteModal;
});

async function handleReportAction(reportId, action) {
    let titleMap = {
        'ignore': 'Bỏ qua báo cáo',
        'hide': 'Ẩn nội dung được báo cáo',
        'warn': 'Cảnh cáo người dùng'
    };

    const confirmed = await window.showConfirmModal(`Bạn có chắc chắn muốn thực hiện: ${titleMap[action] || action}?`, 'Xác nhận hành động', 'Thực hiện', 'Hủy');
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

        if (!res.ok) throw new Error('Network response not ok');
        const data = await res.json();
        if (data.success) {
            const reportElement = document.getElementById(`report-row-${data.reportId}`);
            if (reportElement) {
                const statusBadge = reportElement.querySelector('td:nth-child(5) .badge');
                const actionsCell = reportElement.querySelector('.report-actions');

                if (statusBadge) {
                    statusBadge.textContent = 'Đã xử lý';
                    statusBadge.className = 'badge rounded-pill bg-success text-white px-2.5 py-1 text-xs fw-medium';
                }

                if (actionsCell) {
                    actionsCell.innerHTML = '<span class="report-action-completed">✓ Hoàn tất</span>';
                }
            }

            const reportStatElement = document.querySelector('#overview .stat-value.text-danger');
            if (reportStatElement) {
                let currentCount = parseInt(reportStatElement.textContent) || 0;
                if (currentCount > 0) reportStatElement.textContent = currentCount - 1;
            }

            await window.showAlertModal(data.message, 'Thành công', 'Đóng');
        } else {
            await window.showAlertModal('Lỗi: ' + data.message, 'Lỗi', 'Đóng');
        }
    } catch (err) {
        console.error('AJAX Error:', err);
        await window.showAlertModal('Có lỗi xảy ra khi gọi API xử lý report.', 'Lỗi AJAX', 'Đóng');
    }
}
async function handleReportAction(reportId, action) {
    let titleMap = {
        'ignore': 'Bỏ qua báo cáo',
        'hide': 'Ẩn nội dung được báo cáo',
        'warn': 'Cảnh cáo người dùng'
    };

    const confirmed = await window.showConfirmModal(`Bạn có chắc chắn muốn thực hiện: ${titleMap[action] || action}?`, 'Xác nhận hành động', 'Thực hiện', 'Hủy');
    if (!confirmed) return;

    const adminNote = await window.showAdminNoteModal();
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

        if (!res.ok) throw new Error('Network response not ok');
        const data = await res.json();
        if (data.success) {
            const reportElement = document.getElementById(`report-row-${data.reportId}`);
            if (reportElement) {
                const statusBadge = reportElement.querySelector('td:nth-child(5) .badge');
                const actionsCell = reportElement.querySelector('.report-actions');

                if (statusBadge) {
                    statusBadge.textContent = 'Đã xử lý';
                    statusBadge.className = 'badge rounded-pill bg-success text-white px-2.5 py-1 text-xs fw-medium';
                }

                if (actionsCell) {
                    actionsCell.innerHTML = '<span class="report-action-completed">✓ Hoàn tất</span>';
                }
            }

            const reportStatElement = document.querySelector('#overview .stat-value.text-danger');
            if (reportStatElement) {
                let currentCount = parseInt(reportStatElement.textContent) || 0;
                if (currentCount > 0) reportStatElement.textContent = currentCount - 1;
            }

            await window.showAlertModal(data.message, 'Thành công', 'Đóng');
        } else {
            await window.showAlertModal('Lỗi: ' + data.message, 'Lỗi', 'Đóng');
        }
    } catch (err) {
        console.error('AJAX Error:', err);
        await window.showAlertModal('Có lỗi xảy ra khi gọi API xử lý report.', 'Lỗi AJAX', 'Đóng');
    }
}

function showReportDetails(reportId) {
    const row = document.getElementById(`report-row-${reportId}`);
    if (!row) return window.showAlertModal('Không tìm thấy báo cáo.', 'Chi tiết báo cáo');
    const details = row.dataset.details || '';
    const message = details.trim() !== '' ? details : 'Không có chi tiết';
    window.showAlertModal(message, 'Chi tiết báo cáo', 'Đóng');
}