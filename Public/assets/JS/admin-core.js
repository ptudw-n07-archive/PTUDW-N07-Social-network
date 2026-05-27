(function(window, document) {
    'use strict';

    const ensureToastContainer = () => {
        let container = document.getElementById('adminToastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'adminToastContainer';
            container.className = 'admin-toast-container';
            container.setAttribute('aria-live', 'polite');
            container.setAttribute('aria-atomic', 'true');
            document.body.appendChild(container);
        }
        return container;
    };

    const showToast = (message, type = 'info') => {
        const container = ensureToastContainer();
        const normalizedType = ['success', 'error', 'warning', 'info'].includes(type) ? type : 'info';
        const iconMap = {
            success: 'bi-check-circle-fill',
            error: 'bi-exclamation-triangle-fill',
            warning: 'bi-exclamation-circle-fill',
            info: 'bi-info-circle-fill'
        };
        const toast = document.createElement('div');
        toast.className = `admin-toast ${normalizedType}`;
        toast.setAttribute('role', 'status');
        toast.innerHTML = `
            <i class="bi ${iconMap[normalizedType]} admin-toast-icon"></i>
            <div class="admin-toast-message"></div>
            <button type="button" class="admin-toast-close" aria-label="Đóng"><i class="bi bi-x-lg"></i></button>
        `;
        const messageNode = toast.querySelector('.admin-toast-message');
        if (messageNode) messageNode.textContent = message || '';
        const closeToast = () => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 220);
        };
        const closeButton = toast.querySelector('.admin-toast-close');
        if (closeButton) closeButton.addEventListener('click', closeToast);
        container.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('show'));
        setTimeout(closeToast, 3800);
    };

    const modalState = {
        resolve: null
    };

    const getConfirmModalParts = () => {
        const modal = document.getElementById('adminModal');
        return {
            modal,
            title: modal ? modal.querySelector('.admin-modal-title') : null,
            message: modal ? modal.querySelector('.admin-modal-message') : null,
            confirm: modal ? modal.querySelector('[data-admin-modal-confirm]') : null,
            cancel: modal ? modal.querySelector('[data-admin-modal-cancel]') : null
        };
    };

    const openConfirmModal = modal => {
        if (!modal) return;
        modal.classList.remove('d-none');
        requestAnimationFrame(() => modal.classList.add('active'));
    };

    const closeConfirmModal = (result = false) => {
        const { modal } = getConfirmModalParts();
        if (!modal) return;

        modal.classList.remove('active');
        setTimeout(() => modal.classList.add('d-none'), 250);
        if (modalState.resolve) {
            modalState.resolve(result);
            modalState.resolve = null;
        }
    };

    const showConfirmModal = (message, title = 'Xác nhận', confirmText = 'Xác nhận', cancelText = 'Hủy') => {
        const parts = getConfirmModalParts();
        if (!parts.title || !parts.message || !parts.confirm || !parts.cancel) {
            showToast('Không thể mở hộp thoại xác nhận.', 'error');
            return Promise.resolve(false);
        }

        parts.title.textContent = title;
        parts.message.textContent = message;
        parts.confirm.textContent = confirmText;
        parts.cancel.textContent = cancelText;
        parts.cancel.style.display = 'inline-flex';
        openConfirmModal(parts.modal);

        return new Promise(resolve => {
            modalState.resolve = resolve;
        });
    };

    const bindConfirmModalEvents = () => {
        const modal = document.getElementById('adminModal');
        if (!modal || modal.dataset.adminCoreBound === '1') return;

        const confirm = modal.querySelector('[data-admin-modal-confirm]');
        const cancel = modal.querySelector('[data-admin-modal-cancel]');
        const close = modal.querySelector('.admin-modal-close');
        const backdrop = modal.querySelector('.admin-modal-backdrop');

        if (confirm) confirm.addEventListener('click', () => closeConfirmModal(true));
        if (cancel) cancel.addEventListener('click', () => closeConfirmModal(false));
        if (close) close.addEventListener('click', () => closeConfirmModal(false));
        if (backdrop) backdrop.addEventListener('click', () => closeConfirmModal(false));
        modal.dataset.adminCoreBound = '1';
    };

    document.addEventListener('keydown', event => {
        const modal = document.getElementById('adminModal');
        if (event.key === 'Escape' && modal && !modal.classList.contains('d-none')) {
            closeConfirmModal(false);
        }
    });

    bindConfirmModalEvents();

    // Escape dữ liệu trước khi render bằng innerHTML để hạn chế XSS.
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[char]));

    const ADMIN_DEFAULT_AVATAR = 'Public/assets/img/default-avatar.jpg';

    const adminBaseUrl = () => String(window.ADMIN_BASE_URL || window.APP_BASE_URL || `${window.location.origin}/`).replace(/\/+$/, '/');

    const adminUrl = path => {
        const value = String(path || '').replace(/^\/+/, '');
        return `${adminBaseUrl()}${value}`;
    };

    const normalizeAdminImagePath = (path, fallback = ADMIN_DEFAULT_AVATAR) => {
        let value = String(path ?? '').trim().replace(/\\/g, '/');

        if (value === '') return fallback === '' ? '' : adminUrl(fallback);
        if (/^\/\//.test(value)) return `${window.location.protocol}${value}`;
        if (/^https?:\/\//i.test(value)) return value;

        value = value.replace(/^\/+/, '');
        const withoutPublic = value.replace(/^(?:Public\/)+/i, '');

        if (/^https?:\/\//i.test(withoutPublic)) return withoutPublic;
        if (/^(assets|uploads)\//i.test(withoutPublic)) return adminUrl(`Public/${withoutPublic}`);
        return adminUrl(value);
    };

    const adminAvatarSrc = path => normalizeAdminImagePath(path, ADMIN_DEFAULT_AVATAR);

    const isAvatarImage = img => {
        const imageType = (img.dataset.adminImage || '').toLowerCase();
        const alt = (img.getAttribute('alt') || '').toLowerCase();
        return imageType === 'avatar'
            || alt.includes('avatar')
            || img.classList.contains('rank-avatar')
            || img.classList.contains('admin-profile-avatar')
            || img.classList.contains('admin-profile-avatar-large');
    };

    const handleImageError = img => {
        if (!img || img.tagName !== 'IMG') return;

        if (isAvatarImage(img)) {
            if (img.dataset.adminFallbackApplied !== '1') {
                img.dataset.adminFallbackApplied = '1';
                img.src = adminAvatarSrc('');
                return;
            }
            img.style.visibility = 'hidden';
            return;
        }

        img.dataset.adminImageFailed = '1';
        img.style.display = 'none';
    };

    document.addEventListener('error', event => {
        if (event.target && event.target.tagName === 'IMG') {
            handleImageError(event.target);
        }
    }, true);

    const emptyStateHtml = (message = 'Không có dữ liệu phù hợp.', icon = 'bi-inbox') => `
        <div class="admin-empty-state">
            <i class="bi ${icon}"></i>
            <span>${escapeHtml(message)}</span>
        </div>
    `;

    const tableEmptyRow = (colspan, message = 'Không có dữ liệu phù hợp.', icon = 'bi-inbox') => (
        `<tr><td colspan="${colspan}">${emptyStateHtml(message, icon)}</td></tr>`
    );

    const loadingStateHtml = (message = 'Đang tải dữ liệu...') => `
        <div class="admin-loading-state">
            <span class="admin-spinner"></span>
            <span>${escapeHtml(message)}</span>
        </div>
    `;

    const tableLoadingRow = (colspan, message = 'Đang tải dữ liệu...') => (
        `<tr><td colspan="${colspan}">${loadingStateHtml(message)}</td></tr>`
    );

    const formatClientTime = (date = new Date(), withDate = false) => {
        const time = date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        if (!withDate) return time;
        return `${time} · ${date.toLocaleDateString('vi-VN')}`;
    };

    const csrfToken = () => {
        const tokenInput = document.querySelector('input[name="csrf_token"]');
        return window.ADMIN_CSRF_TOKEN || (tokenInput ? tokenInput.value : '');
    };

    const formatAdminDateTime = value => {
        if (value === null || value === undefined || value === '') return '';
        if (value instanceof Date && !Number.isNaN(value.getTime())) {
            return value.toLocaleString('vi-VN', {
                timeZone: window.ADMIN_TIMEZONE || 'Asia/Ho_Chi_Minh',
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            });
        }

        const text = String(value).trim();
        const localMatch = text.match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?$/);
        if (localMatch) {
            const [, year, month, day, hour = '00', minute = '00', second = '00'] = localMatch;
            return `${day}/${month}/${year} ${hour}:${minute}:${second}`;
        }

        const parsed = new Date(text);
        if (!Number.isNaN(parsed.getTime())) {
            return formatAdminDateTime(parsed);
        }

        return text;
    };

    const normalizeReasonKey = value => String(value || '').trim().toLowerCase().replace(/\s+/g, ' ');
    const reportReasonMap = new Map(Object.entries({
        spam: 'Thư rác / spam',
        inappropriate: 'Nội dung không phù hợp',
        harassment: 'Quấy rối',
        violence: 'Bạo lực',
        misinformation: 'Thông tin sai lệch',
        'hate speech': 'Thù ghét/kích động',
        copyright: 'Vi phạm bản quyền',
        other: 'Khác',
        'bắt nạt hoặc quấy rối': 'Bắt nạt hoặc quấy rối',
        'nội dung nhạy cảm hoặc gây hại': 'Nội dung nhạy cảm hoặc gây hại',
        'bạo lực, thù ghét hoặc bóc lột': 'Bạo lực, thù ghét hoặc bóc lột',
        'thông tin sai lệch': 'Thông tin sai lệch',
        'vấn đề khác': 'Khác',
        'tôi không thích nội dung này': 'Tôi không thích nội dung này'
    }));
    const formatReportReason = value => reportReasonMap.get(normalizeReasonKey(value)) || String(value || '');

    const csvCell = value => `"${String(value ?? '').replace(/"/g, '""')}"`;

    const downloadCsv = (filename, headers, rows) => {
        // Tự tạo file CSV ở trình duyệt để admin xuất báo cáo nhanh, không cần gọi server.
        const csv = [
            headers.join(','),
            ...rows.map(row => headers.map(header => csvCell(row[header])).join(','))
        ].join('\r\n');
        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
        showToast('Đã xuất CSV.', 'success');
    };

    const reportDateSlug = () => new Date().toISOString().slice(0, 10);

    const ensurePrintArea = () => {
        let printArea = document.getElementById('adminPrintArea');
        if (!printArea) {
            printArea = document.createElement('div');
            printArea.id = 'adminPrintArea';
            document.body.appendChild(printArea);
        }
        return printArea;
    };

    const printTableReport = (title, headers, rows, introHtml = '') => {
        // Dồn dữ liệu cần in vào một vùng riêng để CSS print chỉ hiện phần báo cáo.
        const printArea = ensurePrintArea();
        printArea.innerHTML = `
            <h1>${escapeHtml(title)}</h1>
            <p>Thời gian xuất báo cáo: ${escapeHtml(new Date().toLocaleString('vi-VN'))}</p>
            ${introHtml}
            <table>
                <thead><tr>${headers.map(header => `<th>${escapeHtml(header)}</th>`).join('')}</tr></thead>
                <tbody>
                    ${rows.length
                        ? rows.map(row => `<tr>${headers.map(header => `<td>${escapeHtml(row[header])}</td>`).join('')}</tr>`).join('')
                        : `<tr><td colspan="${headers.length}">Không có dữ liệu phù hợp</td></tr>`}
                </tbody>
            </table>
        `;
        window.print();
        showToast('Đang mở hộp thoại in.', 'info');
    };

    window.AdminCore = {
        showToast,
        showConfirmModal,
        escapeHtml,
        normalizeAdminImagePath,
        adminAvatarSrc,
        handleImageError,
        ADMIN_DEFAULT_AVATAR,
        emptyStateHtml,
        tableEmptyRow,
        loadingStateHtml,
        tableLoadingRow,
        formatClientTime,
        formatAdminDateTime,
        formatReportReason,
        csrfToken,
        downloadCsv,
        reportDateSlug,
        printTableReport
    };
})(window, document);
