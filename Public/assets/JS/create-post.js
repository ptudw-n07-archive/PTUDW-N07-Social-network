const APP_BASE_URL = (function () {
    if (window.APP_BASE_URL) {
        return String(window.APP_BASE_URL).replace(/\/?$/, "/");
    }

    const script = document.currentScript || document.querySelector('script[src*="Public/assets/JS/create-post.js"]');
    const scriptSrc = script ? script.getAttribute("src") || "" : "";
    const marker = "Public/assets/JS/create-post.js";
    const markerIndex = scriptSrc.indexOf(marker);

    if (markerIndex >= 0) {
        return scriptSrc.slice(0, markerIndex).replace(/\/?$/, "/");
    }

    return `${window.location.origin}/`;
})();

function appUrl(path = "") {
    return APP_BASE_URL + String(path).replace(/^\/+/, "");
}

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

function createPost() {
    const form = document.getElementById("postForm");

    if (!form) {
        return;
    }

    if (window.syncArchiveContentEditors) {
        window.syncArchiveContentEditors(form);
    }

    const formData = new FormData(form);
    const content = formData.get("content") ? formData.get("content").trim() : "";

    const imageInput = document.getElementById("postImages");
    const images = imageInput ? imageInput.files : [];

    if (content === "" && images.length === 0) {
        showToast("Bạn hãy nhập nội dung hoặc chọn ảnh.");
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
            showToast(data.message || "Không thể đăng bài.");
            return;
        }

        if (Array.isArray(data.uploadErrors) && data.uploadErrors.length > 0) {
            showToast(data.uploadErrors[0]);
        }

        sessionStorage.setItem("post_success", "Đăng bài thành công!");
        window.location.href = appUrl("feed");
    })
    .catch(error => {
        console.error(error);
        showToast("Có lỗi xảy ra trong quá trình đăng bài.");
    });
}

const postImagesInput = document.getElementById("postImages");
const previewContainer = document.getElementById("preview-container");
let selectedPostFiles = [];

if (postImagesInput) {
    postImagesInput.addEventListener("change", function () {
        const newFiles = Array.from(this.files || []);
        selectedPostFiles = selectedPostFiles.concat(
            newFiles.filter(function (file) {
                return !selectedPostFiles.some(function (selectedFile) {
                    return getFileSignature(selectedFile) === getFileSignature(file);
                });
            })
        );
        syncSelectedFilesToInput();
        renderSelectedPostPreviews();
    });
}

function renderSelectedPostPreviews() {
    if (!previewContainer) {
        return;
    }

    previewContainer.innerHTML = "";

    if (selectedPostFiles.length === 0) {
        return;
    }

    selectedPostFiles.forEach(function (file, index) {
        const wrapper = document.createElement("div");
        wrapper.className = "preview-item";

        const removeButton = document.createElement("button");
        removeButton.type = "button";
        removeButton.className = "preview-remove-btn";
        removeButton.dataset.index = String(index);
        removeButton.setAttribute("aria-label", "Xoa anh da chon");
        removeButton.innerHTML = "&times;";
        removeButton.addEventListener("click", function () {
            removeSelectedPostFile(Number(this.dataset.index));
        });

        wrapper.appendChild(removeButton);

        const extension = getMediaExtension(file.name);
        if (["heic", "heif"].includes(extension)) {
            const item = document.createElement("div");
            item.className = "preview-file";
            item.innerText = `${file.name}\nHEIC/HEIF se duoc chuyen doi sau khi dang neu server ho tro.`;
            wrapper.appendChild(item);
            previewContainer.appendChild(wrapper);
            return;
        }

        const mediaType = file.type.startsWith("video/") ? "video" : "image";
        previewContainer.appendChild(wrapper);
        const reader = new FileReader();

        reader.onload = function (e) {
            if (mediaType === "video") {
                const video = document.createElement("video");
                video.src = e.target.result;
                video.className = "preview-video";
                video.controls = true;
                wrapper.appendChild(video);
                return;
            }

            const img = document.createElement("img");
            img.src = e.target.result;
            img.className = "preview-image";
            img.alt = file.name;
            wrapper.appendChild(img);
        };

        reader.readAsDataURL(file);
    });
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
    if (!postImagesInput) {
        return;
    }

    const dataTransfer = new DataTransfer();
    selectedPostFiles.forEach(function (file) {
        dataTransfer.items.add(file);
    });
    postImagesInput.files = dataTransfer.files;
}

function getFileSignature(file) {
    return [file.name, file.size, file.lastModified].join(":");
}

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
