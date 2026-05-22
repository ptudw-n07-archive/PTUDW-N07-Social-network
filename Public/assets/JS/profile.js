document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("updateProfileForm");
    const avatarInput = document.getElementById("avatarInput");
    const avatarModalPreview = document.getElementById("avatarModalPreview");
    const alertBox = document.getElementById("profileUpdateAlert");

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

    if (!form) {
        return;
    }

    form.addEventListener("submit", function (event) {
        event.preventDefault();

        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton ? submitButton.innerHTML : "";

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang lưu';
        }

        fetch(window.PROFILE_UPDATE_URL || "/App/Controllers/ProfileController.php?action=update", {
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

    function showProfileAlert(message, type) {
        if (!alertBox) {
            alert(message);
            return;
        }

        alertBox.className = "alert alert-" + type;
        alertBox.textContent = message;
    }
});
