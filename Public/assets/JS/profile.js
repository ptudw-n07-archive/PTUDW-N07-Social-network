document.addEventListener("DOMContentLoaded", function () {
    const APP_BASE_URL = window.APP_BASE_URL || `${window.location.origin}/`;
    const appUrl = path => APP_BASE_URL + String(path || "").replace(/^\/+/, "");
    const form = document.getElementById("updateProfileForm");
    const avatarInput = document.getElementById("avatarInput");
    const avatarModalPreview = document.getElementById("avatarModalPreview");
    const alertBox = document.getElementById("profileUpdateAlert");
    const followButton = document.getElementById("profileFollowButton");
    const followAlert = document.getElementById("profileFollowAlert");
    const followerCount = document.getElementById("profileFollowerCount");

    if (followButton) {
        followButton.addEventListener("click", function () {
            const userId = followButton.dataset.userId;
            const originalHTML = followButton.innerHTML;

            if (!userId) {
                showFollowAlert("Thiếu UserID.");
                return;
            }

            followButton.disabled = true;
            followButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xử lý';

            const formData = new FormData();
            formData.append("userId", userId);

            fetch(window.PROFILE_FOLLOW_URL || appUrl("App/Controllers/FollowController.php?action=toggle"), {
                method: "POST",
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        showFollowAlert(data.message || "Không thể cập nhật theo dõi.");
                        return;
                    }

                    updateFollowButton(Boolean(data.isFollowing));

                    if (followerCount && typeof data.followerCount !== "undefined") {
                        followerCount.textContent = formatProfileNumber(data.followerCount);
                    }

                    showFollowAlert(data.message || "Đã cập nhật theo dõi.");
                })
                .catch(error => {
                    console.error(error);
                    followButton.innerHTML = originalHTML;
                    showFollowAlert("Có lỗi khi cập nhật theo dõi.");
                })
                .finally(() => {
                    followButton.disabled = false;
                });
        });
    }

    if (avatarInput && avatarModalPreview) {
        avatarInput.addEventListener("change", function () {
            const file = this.files && this.files[0] ? this.files[0] : null;

            if (!file) {
                return;
            }

            if (!["image/jpeg", "image/png", "image/webp"].includes(file.type)) {
                showProfileAlert("Avatar chỉ hỗ trợ jpg, jpeg, png hoặc webp.", "danger");
                this.value = "";
                return;
            }

            avatarModalPreview.src = URL.createObjectURL(file);
        });
    }

    if (form) {
        form.addEventListener("submit", function (event) {
            event.preventDefault();

            const submitButton = form.querySelector('button[type="submit"]');
            const originalText = submitButton ? submitButton.innerHTML : "";

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang lưu';
            }

            fetch(window.PROFILE_UPDATE_URL || appUrl("App/Controllers/ProfileController.php?action=update"), {
                method: "POST",
                body: new FormData(form)
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        showProfileAlert(data.message || "Không thể cập nhật hồ sơ.", "danger");
                        return;
                    }

                    showProfileAlert(data.message || "Cập nhật hồ sơ thành công.", "success");
                    setTimeout(function () {
                        window.location.reload();
                    }, 700);
                })
                .catch(error => {
                    console.error(error);
                    showProfileAlert("Có lỗi khi cập nhật hồ sơ.", "danger");
                })
                .finally(() => {
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalText;
                    }
                });
        });
    }

    function showProfileAlert(message, type) {
        if (!alertBox) {
            alert(message);
            return;
        }

        alertBox.className = "alert alert-" + type;
        alertBox.textContent = message;
    }

    function updateFollowButton(isFollowing) {
        followButton.dataset.isFollowing = isFollowing ? "1" : "0";
        followButton.classList.toggle("btn-pink", !isFollowing);
        followButton.classList.toggle("profile-follow-btn", !isFollowing);
        followButton.classList.toggle("profile-unfollow-btn", isFollowing);
        followButton.innerHTML = isFollowing
            ? '<i class="bi bi-person-check-fill me-1"></i><span>Đã theo dõi</span>'
            : '<i class="bi bi-person-plus me-1"></i><span>Theo dõi</span>';
    }

    function showFollowAlert(message) {
        if (!followAlert) {
            return;
        }

        followAlert.textContent = message;
        followAlert.classList.add("is-visible");

        window.clearTimeout(showFollowAlert.timer);
        showFollowAlert.timer = window.setTimeout(function () {
            followAlert.classList.remove("is-visible");
        }, 1800);
    }

    function formatProfileNumber(value) {
        const number = Number.parseInt(value, 10);

        if (Number.isNaN(number)) {
            return "0";
        }

        return number.toLocaleString("en-US");
    }
});
