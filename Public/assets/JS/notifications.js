const APP_BASE_URL = window.APP_BASE_URL || `${window.location.origin}/`;

function appUrl(path = "") {
    return APP_BASE_URL + String(path).replace(/^\/+/, "");
}

document.addEventListener("DOMContentLoaded", function () {
    const markAllReadBtn = document.getElementById("markAllReadBtn");

    if (markAllReadBtn) {
        markAllReadBtn.addEventListener("click", function () {
            fetch(appUrl("App/Controllers/NotificationController.php?action=markAllAsRead"), {
                method: "POST"
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

                    const badge = document.querySelector(".notification-badge");
                    if (badge && (data.unreadCount || 0) > 0) {
                        badge.innerText = Math.min(data.unreadCount, 99);
                    } else if (badge) {
                        badge.remove();
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

            const formData = new FormData();
            formData.append("notificationId", notificationId);

            fetch(appUrl("App/Controllers/NotificationController.php?action=markAsRead"), {
                method: "POST",
                body: formData
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

                    const badge = document.querySelector(".notification-badge");
                    if (badge) {
                        if ((data.unreadCount || 0) > 0) {
                            badge.innerText = Math.min(data.unreadCount, 99);
                        } else {
                            badge.remove();
                        }
                    }
                });
        });
    });
});
