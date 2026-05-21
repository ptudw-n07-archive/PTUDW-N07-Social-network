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
});

async function handleReport(reportId, statusAction) {
    const confirmed = await window.showConfirmModal(`Bạn có chắc chắn muốn xử lý report này không?`, 'Xác nhận xử lý report', 'Xử lý', 'Hủy');
    if (!confirmed) {
        return;
    }

    const formData = new FormData();
    formData.append("reportId", reportId);
    formData.append("status", statusAction);

    // Gửi ngầm lên hệ thống
    fetch(window.ADMIN_PROCESS_REPORT_URL, {
        method: "POST",
        credentials: "same-origin",
        body: formData
    })
    .then(response => {
        if (!response.ok) throw new Error("Mạng lỗi hoặc đường dẫn sai.");
        return response.json();
    })
    .then(async data => {
        if (data.success) {
            // Cập nhật UI ngay lập tức trước khi hiện modal
            const reportElement = document.getElementById(`report-row-${data.reportId}`);
            if (reportElement) {
                const statusBadge = reportElement.querySelector('td:nth-child(4) .badge');
                const actionBtn = reportElement.querySelector('.btn-pink-admin');

                if (statusBadge) {
                    statusBadge.textContent = 'Đã xử lý';
                    statusBadge.className = 'badge rounded-pill bg-success text-white px-2.5 py-1 text-xs fw-medium';
                }

                if (actionBtn) {
                    actionBtn.disabled = true;
                    actionBtn.style.opacity = '0.7';
                    actionBtn.innerHTML = '<i class="bi bi-check2-all"></i> Hoàn tất';
                }
            }

            // Cập nhật con số tổng quan
            const reportStatElement = document.querySelector('#overview .stat-value.text-danger'); 
            if (reportStatElement) {
                let currentCount = parseInt(reportStatElement.textContent) || 0;
                if (currentCount > 0) reportStatElement.textContent = currentCount - 1;
            }

            await window.showAlertModal(data.message, 'Thành công', 'Đóng');
        } else {
            await window.showAlertModal("Lỗi: " + data.message, 'Lỗi', 'Đóng');
        }
    })
    .catch(async error => {
        console.error("AJAX Error:", error);
        await window.showAlertModal("Có lỗi xảy ra trong quá trình xử lý AJAX Admin.", 'Lỗi AJAX', 'Đóng');
    });
}