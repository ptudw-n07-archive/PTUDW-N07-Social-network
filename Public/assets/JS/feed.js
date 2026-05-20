// =======================
// LIKE BUTTON - AJAX THẬT
// =======================
function toggleLike(btn) {
    const postId = btn.dataset.postId;

    if (!postId) {
        alert("Không tìm thấy PostID.");
        return;
    }

    const formData = new FormData();
    formData.append("postId", postId);

    fetch("/App/Controllers/PostController.php?action=like", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert(data.message || "Like thất bại.");
            return;
        }

        const icon = btn.querySelector("i");
        const count = btn.querySelector(".like-count");

        count.innerText = data.likeCount;

        if (data.status === "liked") {
            icon.classList.remove("bi-heart");
            icon.classList.add("bi-heart-fill");
            btn.classList.add("liked");
            btn.style.color = "red";
        } else {
            icon.classList.remove("bi-heart-fill");
            icon.classList.add("bi-heart");
            btn.classList.remove("liked");
            btn.style.color = "";
        }
    })
    .catch(error => {
        console.error(error);
        alert("Có lỗi khi like bài viết.");
    });
}


// =======================
// POST - ĐĂNG BÀI AJAX
// =======================
function createPost() {
    const form = document.getElementById("postForm");

    if (!form) {
        alert("Không tìm thấy form đăng bài.");
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
    .then(response => {
        if (!response.ok) {
            throw new Error("Không thể kết nối tới server.");
        }

        return response.json();
    })
    .then(data => {
        if (!data.success) {
            alert(data.message || "Không thể đăng bài.");
            return;
        }

        addPostToUI(data.post);

        if (Array.isArray(data.uploadErrors) && data.uploadErrors.length > 0) {
            alert(data.uploadErrors[0]);
        }

        form.reset();

        const previewContainer = document.getElementById("preview-container");
        if (previewContainer) {
            previewContainer.innerHTML = "";
        }
    })
    .catch(error => {
        console.error(error);
        alert("Có lỗi xảy ra trong quá trình đăng bài.");
    });
}


// =======================
// THÊM BÀI MỚI LÊN FEED
// =======================
function addPostToUI(post) {
    const postsList = document.getElementById("posts-list");

    if (!postsList) {
        return;
    }

    let imageHtml = "";
    const images = Array.isArray(post.Images) ? post.Images : [];

    images.forEach(img => {
        const imageSrc = normalizeImagePath(img);
        const mediaType = getMediaType(img);

        if (mediaType === "video") {
            imageHtml += `
                <video
                    controls
                    class="img-fluid rounded-4 mb-3"
                    style="max-height: 450px; object-fit: cover;"
                >
                    <source src="${escapeHTML(imageSrc)}" type="${escapeHTML(getMediaMimeType(img))}">
                    Trình duyệt không hỗ trợ video này.
                </video>
                <a href="${escapeHTML(imageSrc)}" target="_blank" class="small d-block mb-3">Mở file video</a>
            `;
            return;
        }

        if (mediaType === "image") {
            imageHtml += `
                <img
                    src="${escapeHTML(imageSrc)}"
                    class="img-fluid rounded-4 mb-3"
                    style="max-height: 450px; object-fit: cover;"
                    alt="post image"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
                >
                <a href="${escapeHTML(imageSrc)}" target="_blank" class="small mb-3" style="display:none;">Mở file ảnh</a>
            `;
            return;
        }

        imageHtml += `<a href="${escapeHTML(imageSrc)}" target="_blank" class="small d-block mb-3">Mở file ảnh</a>`;
    });

    const avatarSrc = normalizeImagePath(post.ProfilePictureUrl || "Public/assets/img/default-avatar.jpg");
    const fullName = post.FullName || post.Username || "Bạn";
    const profileHref = `/App/Views/profile.php?id=${encodeURIComponent(post.UserID || "")}`;

    const newPost = document.createElement("div");
    newPost.className = "bg-white post-card mb-3";
    newPost.innerHTML = `
        <div class="p-3">
            <div class="d-flex gap-3">
                <a href="${profileHref}">
                    <img src="${escapeHTML(avatarSrc)}" class="avatar" alt="avatar" onerror="this.src='/Public/assets/img/default-avatar.jpg';">
                </a>

                <div class="flex-grow-1">
                    <div class="fw-semibold">
                        <a href="${profileHref}" class="text-decoration-none text-dark">${escapeHTML(fullName)}</a>
                        • vừa xong
                    </div>

                    <p class="post-text"></p>

                    ${imageHtml}

                    <div class="post-actions d-flex gap-4">
                        <button onclick="toggleLike(this)" data-post-id="${post.PostID}">
                            <i class="bi bi-heart"></i>
                            <span class="like-count">0</span>
                        </button>

                        <button onclick="toggleCommentBox(this)">
                            <i class="bi bi-chat"></i>
                            <span class="comment-count">0</span>
                        </button>

                        <button>
                            <i class="bi bi-arrow-repeat"></i>
                        </button>
                    </div>

                    <div class="comment-box mt-3 d-none">
                        <div class="d-flex gap-2">
                            <input
                                type="text"
                                class="form-control comment-input"
                                placeholder="Viết bình luận..."
                            >

                            <button
                                type="button"
                                class="btn btn-pink"
                                onclick="sendComment(this)"
                                data-post-id="${post.PostID}"
                            >
                                Gửi
                            </button>
                        </div>

                        <div class="comment-list mt-2"></div>
                    </div>
                </div>
            </div>
        </div>
    `;

    newPost.querySelector(".post-text").innerText = post.Content || "";
    postsList.prepend(newPost);
}


// =======================
// COMMENT BOX
// =======================
function toggleCommentBox(btn) {
    const postCard = btn.closest(".post-card");
    const commentBox = postCard.querySelector(".comment-box");

    if (commentBox) {
        commentBox.classList.toggle("d-none");
    }
}


// =======================
// GỬI COMMENT AJAX
// =======================
function sendComment(btn) {
    const postId = btn.dataset.postId;
    const postCard = btn.closest(".post-card");
    const input = postCard.querySelector(".comment-input");
    const commentList = postCard.querySelector(".comment-list");
    const commentCount = postCard.querySelector(".comment-count");

    const content = input.value.trim();

    if (content === "") {
        alert("Bạn chưa nhập bình luận.");
        return;
    }

    const formData = new FormData();
    formData.append("postId", postId);
    formData.append("content", content);

    fetch("/App/Controllers/PostController.php?action=comment", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert("Không thể bình luận.");
            return;
        }

        const comment = document.createElement("div");
        comment.className = "small mt-2";
        comment.innerHTML = `<strong>${escapeHTML(data.comment.fullName)}</strong>: ${escapeHTML(data.comment.content)}`;

        commentList.appendChild(comment);
        input.value = "";

        if (commentCount) {
            commentCount.innerText = parseInt(commentCount.innerText) + 1;
        }
    })
    .catch(error => {
        console.error(error);
        alert("Có lỗi khi gửi bình luận.");
    });
}


// =======================
// ESCAPE HTML
// =======================
function escapeHTML(text) {
    const div = document.createElement("div");
    div.innerText = text;
    return div.innerHTML;
}

function normalizeImagePath(path) {
    if (!path) {
        return "/Public/assets/img/default-avatar.jpg";
    }

    path = String(path).trim();

    if (path.startsWith("http://") || path.startsWith("https://")) {
        return path;
    }

    const cleanPath = path.replace(/^\/+/, "");

    if (cleanPath.startsWith("Public/")) {
        return "/" + cleanPath;
    }

    if (cleanPath.startsWith("uploads/") || cleanPath.startsWith("assets/")) {
        return "/Public/" + cleanPath;
    }

    return "/" + cleanPath;
}

function getMediaExtension(path) {
    const cleanPath = String(path || "").split("?")[0].split("#")[0];
    const parts = cleanPath.split(".");
    return parts.length > 1 ? parts.pop().toLowerCase() : "";
}

function getMediaType(path) {
    const extension = getMediaExtension(path);

    if (["mp4", "mov", "webm"].includes(extension)) {
        return "video";
    }

    if (["jpg", "jpeg", "png", "webp", "gif"].includes(extension)) {
        return "image";
    }

    if (["heic", "heif"].includes(extension)) {
        return "unsupported-image";
    }

    return "file";
}

function getMediaMimeType(path) {
    const extension = getMediaExtension(path);

    const mimeTypes = {
        jpg: "image/jpeg",
        jpeg: "image/jpeg",
        png: "image/png",
        webp: "image/webp",
        gif: "image/gif",
        heic: "image/heic",
        heif: "image/heif",
        mp4: "video/mp4",
        mov: "video/quicktime",
        webm: "video/webm"
    };

    return mimeTypes[extension] || "application/octet-stream";
}
// =======================
// FOLLOW - AJAX THẬT
// =======================
function toggleFollow(btn) {
    const userId = btn.dataset.userId;

    if (!userId) {
        alert("Không tìm thấy UserID.");
        return;
    }

    const formData = new FormData();
    formData.append("userId", userId);

    fetch("/App/Controllers/FollowController.php?action=toggle", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert(data.message || "Không thể xử lý theo dõi.");
            return;
        }

        if (data.status === "followed") {
            btn.innerText = "Đang theo dõi";
            btn.classList.remove("btn-pink");
            btn.classList.add("btn-secondary");
        } else {
            btn.innerText = "Theo dõi";
            btn.classList.remove("btn-secondary");
            btn.classList.add("btn-pink");
        }
    })
    .catch(error => {
        console.error(error);
        alert("Có lỗi khi theo dõi.");
    });
}

// =======================
// IMAGE PREVIEW
// =======================
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

document.addEventListener("DOMContentLoaded", function () {
    const successMessage = sessionStorage.getItem("post_success");

    if (!successMessage) {
        return;
    }

    const alertBox = document.getElementById("post-success-alert");
    const alertMessage = document.getElementById("post-success-message");

    if (alertBox && alertMessage) {
        alertMessage.innerText = successMessage;
        alertBox.classList.remove("d-none");

        setTimeout(function () {
            alertBox.classList.add("d-none");
        }, 3500);
    }

    sessionStorage.removeItem("post_success");
});

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
