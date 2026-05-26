const APP_BASE_URL = window.APP_BASE_URL || `${window.location.origin}/`;

function appUrl(path = "") {
    return APP_BASE_URL + String(path).replace(/^\/+/, "");
}

<<<<<<< HEAD
=======
function showToast(message) {
    let toast = document.getElementById("pageToast");
    if (!toast) {
        toast = document.createElement("div");
        toast.id = "pageToast";
        toast.className = "page-toast";
        document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.classList.add("show");
    window.clearTimeout(toast._timer);
    toast._timer = window.setTimeout(function () {
        toast.classList.remove("show");
    }, 2600);
}

>>>>>>> 31a4974 (Update feed features and UI)
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
<<<<<<< HEAD
        alert("Bạn hãy nhập nội dung hoặc chọn ảnh.");
=======
        showToast("Bạn hãy nhập nội dung hoặc chọn ảnh.");
>>>>>>> 31a4974 (Update feed features and UI)
        return;
    }

    fetch(appUrl("App/Controllers/PostController.php?action=create"), {
        method: "POST",
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error("Không thể kết nối tới server.");
        }

        return response.json();
    })
    .then(data => {
        if (!data.success) {
<<<<<<< HEAD
            alert(data.message || "Không thể đăng bài.");
=======
            showToast(data.message || "Không thể đăng bài.");
>>>>>>> 31a4974 (Update feed features and UI)
            return;
        }

        if (Array.isArray(data.uploadErrors) && data.uploadErrors.length > 0) {
<<<<<<< HEAD
            alert(data.uploadErrors[0]);
        }

        sessionStorage.setItem("post_success", "Đăng bài thành công!");
        window.location.href = appUrl("App/Views/post/feed.php");
    })
    .catch(error => {
        console.error(error);
        alert("Có lỗi xảy ra trong quá trình đăng bài.");
=======
            showToast(data.uploadErrors[0]);
        }

        sessionStorage.setItem("post_success", "Đăng bài thành công!");
        window.location.href = appUrl("App/Views/post/feed.php");
    })
    .catch(error => {
        console.error(error);
        showToast("Có lỗi xảy ra trong quá trình đăng bài.");
>>>>>>> 31a4974 (Update feed features and UI)
    });
}

const postImagesInput = document.getElementById("postImages");
<<<<<<< HEAD

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
            const mediaType = file.type.startsWith("video/") ? "video" : "image";

            const reader = new FileReader();

            reader.onload = function (e) {
                if (mediaType === "video") {
                    const video = document.createElement("video");
                    video.src = e.target.result;
                    video.className = "preview-image";
                    video.controls = true;
                    previewContainer.appendChild(video);
                    return;
                }

                const extension = getMediaExtension(file.name);
                if (["heic", "heif"].includes(extension)) {
                    const item = document.createElement("div");
                    item.className = "preview-file";
                    item.innerText = `${file.name}\nHEIC/HEIF sẽ được chuyển đổi sau khi đăng nếu server hỗ trợ.`;
                    previewContainer.appendChild(item);
                    return;
                }

                const img = document.createElement("img");
                img.src = e.target.result;
                img.className = "preview-image";
                previewContainer.appendChild(img);
            };

            reader.readAsDataURL(file);
        });
    });
}

=======
let selectedPostFiles = [];

if (postImagesInput) {
    postImagesInput.addEventListener("change", function () {
        selectedPostFiles = Array.from(this.files || []);
        renderSelectedPostPreviews();
    });
}

function renderSelectedPostPreviews() {
    const previewContainer = document.getElementById("preview-container");

    if (!previewContainer) {
        return;
    }

    previewContainer.innerHTML = "";

    selectedPostFiles.forEach(function (file, index) {
        const wrapper = document.createElement("div");
        wrapper.className = "preview-item";

        const removeButton = document.createElement("button");
        removeButton.type = "button";
        removeButton.className = "preview-remove-btn";
        removeButton.dataset.index = String(index);
        removeButton.setAttribute("aria-label", "Xóa ảnh khỏi bài viết");
        removeButton.innerHTML = '<i class="bi bi-x"></i>';
        removeButton.addEventListener("click", function () {
            removeSelectedPostFile(Number(this.dataset.index));
        });

        wrapper.appendChild(createPreviewMedia(file));
        wrapper.appendChild(removeButton);
        previewContainer.appendChild(wrapper);
    });
}

function createPreviewMedia(file) {
    const extension = getMediaExtension(file.name);

    if (["heic", "heif"].includes(extension)) {
        const item = document.createElement("div");
        item.className = "preview-file";
        item.innerText = `${file.name}\nHEIC/HEIF sẽ được chuyển đổi sau khi đăng nếu server hỗ trợ.`;
        return item;
    }

    const objectUrl = URL.createObjectURL(file);

    if (file.type.startsWith("video/")) {
        const video = document.createElement("video");
        video.src = objectUrl;
        video.className = "preview-video";
        video.controls = true;
        return video;
    }

    const img = document.createElement("img");
    img.src = objectUrl;
    img.className = "preview-image";
    img.alt = file.name || "Ảnh xem trước";
    img.addEventListener("load", function () {
        URL.revokeObjectURL(objectUrl);
    }, { once: true });
    return img;
}

function removeSelectedPostFile(index) {
    if (index < 0 || index >= selectedPostFiles.length) {
        return;
    }

    selectedPostFiles.splice(index, 1);
    syncSelectedFilesToInput();
    renderSelectedPostPreviews();
}

function syncSelectedFilesToInput() {
    if (!postImagesInput || typeof DataTransfer === "undefined") {
        return;
    }

    const dataTransfer = new DataTransfer();
    selectedPostFiles.forEach(function (file) {
        dataTransfer.items.add(file);
    });
    postImagesInput.files = dataTransfer.files;
}

>>>>>>> 31a4974 (Update feed features and UI)
function getMediaExtension(path) {
    const cleanPath = String(path || "").split("?")[0].split("#")[0];
    const parts = cleanPath.split(".");
    return parts.length > 1 ? parts.pop().toLowerCase() : "";
}

const moreButton = document.getElementById("moreButton");
const moreDropdown = document.getElementById("moreDropdown");

if (moreButton && moreDropdown) {
    moreButton.addEventListener("click", function (event) {
        event.stopPropagation();
        const isOpen = moreDropdown.classList.toggle("show");
        moreButton.setAttribute("aria-expanded", isOpen ? "true" : "false");
    });

    moreDropdown.addEventListener("click", function (event) {
        event.stopPropagation();
    });

    document.addEventListener("click", function () {
        moreDropdown.classList.remove("show");
        moreButton.setAttribute("aria-expanded", "false");
    });
}
