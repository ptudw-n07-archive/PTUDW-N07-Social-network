function createPost() {
    const form = document.getElementById("postForm");

    if (!form) {
        return;
    }

    const formData = new FormData(form);
    const content = formData.get("content") ? formData.get("content").trim() : "";

    const imageInput = document.getElementById("postImages");
    const images = imageInput ? imageInput.files : [];

    if (content === "" && images.length === 0) {
        alert("Bạn hãy nhập nội dung hoặc chọn ảnh.");
        return;
    }

    fetch("/App/Controllers/PostController.php?action=create", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert(data.message || "Không thể đăng bài.");
            return;
        }

        sessionStorage.setItem("post_success", "Đăng bài thành công!");
window.location.href = "/App/Views/feed.php";
    })
    .catch(error => {
        console.error(error);
        alert("Có lỗi xảy ra trong quá trình đăng bài.");
    });
}

const postImagesInput = document.getElementById("postImages");

if (postImagesInput) {
    postImagesInput.addEventListener("change", function () {
        const previewContainer = document.getElementById("preview-container");

        if (!previewContainer) {
            return;
        }

        previewContainer.innerHTML = "";

        const files = this.files;

        if (!files || files.length === 0) {
            return;
        }

        Array.from(files).forEach(file => {
            if (!file.type.startsWith("image/")) {
                return;
            }

            const reader = new FileReader();

            reader.onload = function (e) {
                const img = document.createElement("img");
                img.src = e.target.result;
                img.className = "preview-image";
                previewContainer.appendChild(img);
            };

            reader.readAsDataURL(file);
        });
    });
}