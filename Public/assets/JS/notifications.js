const APP_BASE_URL = window.APP_BASE_URL || `${window.location.origin}/`;

function appUrl(path = "") {
    return APP_BASE_URL + String(path).replace(/^\/+/, "");
}

function notificationFormData(values = {}) {
    const formData = new FormData();
    Object.keys(values).forEach(key => formData.append(key, values[key]));

    if (window.NOTIFICATION_CSRF_TOKEN) {
        formData.append("csrf_token", window.NOTIFICATION_CSRF_TOKEN);
    }

    return formData;
}

document.addEventListener("DOMContentLoaded", function () {
    const markAllReadBtn = document.getElementById("markAllReadBtn");
    document.querySelectorAll(".notification-badge, .bottom-nav-badge").forEach(badge => badge.remove());

    if (markAllReadBtn) {
        markAllReadBtn.addEventListener("click", function () {
            fetch(appUrl("App/Controllers/NotificationController.php?action=markAllAsRead"), {
                method: "POST",
                body: notificationFormData()
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        return;
                    }

                    document.querySelectorAll(".activity-item.unread").forEach(item => {
                        item.classList.remove("unread");
                    });

                    document.querySelectorAll(".activity-unread-dot").forEach(item => {
                        item.remove();
                    });

                    const subtitle = document.querySelector(".activity-subtitle");
                    if (subtitle) {
                        subtitle.innerText = `${data.unreadCount || 0} thông báo chưa đọc`;
                    }

                    if ((data.unreadCount || 0) === 0) {
                        markAllReadBtn.remove();
                    }
                });
        });
    }

    document.querySelectorAll(".activity-unread-dot").forEach(button => {
        button.addEventListener("click", function (event) {
            event.preventDefault();
            event.stopPropagation();

            const item = button.closest(".activity-item");
            const notificationId = item ? item.dataset.notificationId : null;

            if (!notificationId) {
                return;
            }

            fetch(appUrl("App/Controllers/NotificationController.php?action=markAsRead"), {
                method: "POST",
                body: notificationFormData({ notificationId: notificationId })
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        return;
                    }

                    item.classList.remove("unread");
                    button.remove();

                    const subtitle = document.querySelector(".activity-subtitle");
                    if (subtitle) {
                        subtitle.innerText = `${data.unreadCount || 0} thông báo chưa đọc`;
                    }

                });
        });
    });
});
