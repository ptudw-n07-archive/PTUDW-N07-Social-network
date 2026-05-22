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
        refreshTrendingHashtags();

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
    const imagesJson = JSON.stringify(images);
    const privacy = post.Privacy || "public";

    const newPost = document.createElement("div");
    newPost.className = "bg-white post-card mb-3";
    newPost.id = `post-${post.PostID}`;
    newPost.dataset.postId = post.PostID || "";
    newPost.dataset.ownerId = post.UserID || "";
    newPost.dataset.isOwner = "1";
    newPost.dataset.postContent = post.Content || "";
    newPost.dataset.postImages = imagesJson;
    newPost.dataset.postPrivacy = privacy;
    newPost.innerHTML = `
        <div class="p-3">
            <div class="d-flex gap-3">
                <a href="${profileHref}">
                    <img src="${escapeHTML(avatarSrc)}" class="avatar" alt="avatar" onerror="this.src='/Public/assets/img/default-avatar.jpg';">
                </a>

                <div class="flex-grow-1">
                    <div class="post-card-header">
                        <div class="fw-semibold">
                        <a href="${profileHref}" class="text-decoration-none text-dark">${escapeHTML(fullName)}</a>
                        • vừa xong
                        </div>
                        ${renderPostMenu(post.PostID, post.UserID, true)}
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

    newPost.querySelector(".post-text").innerHTML = renderContentWithHashtags(post.Content || "");
    postsList.prepend(newPost);
}

function refreshTrendingHashtags() {
    const container = document.getElementById("trendingHashtagsContainer");

    if (!container) {
        return;
    }

    const card = container.closest(".trending-hashtags-card");
    const endpoint = card && card.dataset.trendingEndpoint
        ? card.dataset.trendingEndpoint
        : "/App/Controllers/PostController.php?action=trendingHashtags";

    fetch(endpoint, {
        headers: {
            "Accept": "application/json"
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error("Khong the tai chu de noi bat.");
        }

        return response.json();
    })
    .then(data => {
        if (!data.success || !Array.isArray(data.hashtags)) {
            return;
        }

        renderTrendingHashtags(data.hashtags);
    })
    .catch(error => {
        console.error(error);
    });
}

function renderTrendingHashtags(hashtags) {
    const container = document.getElementById("trendingHashtagsContainer");

    if (!container) {
        return;
    }

    const validHashtags = hashtags.filter(item => String(item.tag || "").trim() !== "");

    if (validHashtags.length === 0) {
        container.innerHTML = '<p class="text-muted mb-0">Chưa có chủ đề nổi bật.</p>';
        return;
    }

    const list = document.createElement("div");
    list.className = "d-flex flex-column gap-2";

    validHashtags.forEach(item => {
        const tag = String(item.tag || "").trim();
        const postCount = Number(item.post_count || 0);
        const link = document.createElement("a");

        link.href = `/App/Views/hashtag.php?tag=${encodeURIComponent(tag)}`;
        link.className = "trending-hashtag-item";
        link.innerHTML = `
            <span>#${escapeHTML(tag)}</span>
            <small>${postCount} bài viết</small>
        `;

        list.appendChild(link);
    });

    container.innerHTML = "";
    container.appendChild(list);
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

        const emptyState = commentList.querySelector(".post-detail-empty-comments");
        if (emptyState) {
            emptyState.remove();
        }

        const comment = document.createElement("div");
        const isPostDetail = Boolean(postCard.querySelector(".post-detail-text"));

        if (isPostDetail) {
            comment.className = "post-detail-comment";
            comment.id = data.comment.commentId ? `comment-${data.comment.commentId}` : "";
            comment.innerHTML = `
                <img src="/Public/assets/img/default-avatar.jpg" class="avatar" alt="avatar">
                <div>
                    <div class="fw-semibold">${escapeHTML(data.comment.fullName)}</div>
                    <div>${escapeHTML(data.comment.content)}</div>
                </div>
            `;
        } else {
            comment.className = "small mt-2";
            comment.innerHTML = `<strong>${escapeHTML(data.comment.fullName)}</strong>: ${escapeHTML(data.comment.content)}`;
        }

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

function renderContentWithHashtags(text) {
    return escapeHTML(text).replace(/#([\p{L}\p{N}_]+)/gu, function (match, tag) {
        return `<a class="hashtag-link" href="/App/Views/hashtag.php?tag=${encodeURIComponent(tag)}">#${tag}</a>`;
    }).replace(/\n/g, "<br>");
}

function renderPostMenu(postId, ownerId, isOwner) {
    const ownerItems = `
        <button type="button" class="post-menu-item" data-post-action="edit"><i class="bi bi-pencil-square"></i><span>Chỉnh sửa bài viết</span></button>
        <button type="button" class="post-menu-item text-danger" data-post-action="delete"><i class="bi bi-trash3"></i><span>Xóa bài viết</span></button>
        <button type="button" class="post-menu-item" data-post-action="privacy"><i class="bi bi-shield-lock"></i><span>Quyền riêng tư</span></button>
    `;
    const otherItems = `
        <button type="button" class="post-menu-item" data-post-action="block"><i class="bi bi-person-slash"></i><span>Chặn người dùng</span></button>
        <button type="button" class="post-menu-item" data-post-action="report"><i class="bi bi-flag"></i><span>Báo cáo bài viết</span></button>
        <button type="button" class="post-menu-item" data-post-action="notInterested"><i class="bi bi-eye-slash"></i><span>Không quan tâm</span></button>
    `;

    return `
        <div class="post-menu" data-post-id="${escapeHTML(postId)}" data-owner-id="${escapeHTML(ownerId)}">
            <button type="button" class="post-menu-toggle" aria-label="Mở menu bài viết"><i class="bi bi-three-dots-vertical"></i></button>
            <div class="post-menu-dropdown" hidden>${isOwner ? ownerItems : otherItems}</div>
        </div>
    `;
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

document.addEventListener("DOMContentLoaded", function () {
    initHashtagComposerSuggestions();
    initPostMenu();
});

function initPostMenu() {
    ensurePostActionModals();

    document.addEventListener("click", function (event) {
        const toggle = event.target.closest(".post-menu-toggle");
        const actionButton = event.target.closest("[data-post-action]");

        if (toggle) {
            event.preventDefault();
            event.stopPropagation();
            closePostMenus(toggle.closest(".post-menu"));
            const dropdown = toggle.closest(".post-menu").querySelector(".post-menu-dropdown");
            dropdown.hidden = !dropdown.hidden;
            return;
        }

        if (actionButton) {
            event.preventDefault();
            event.stopPropagation();
            handlePostAction(actionButton.dataset.postAction, actionButton.closest(".post-card"));
            closePostMenus();
            return;
        }

        if (!event.target.closest(".post-menu")) {
            closePostMenus();
        }
    });
}

function closePostMenus(exceptMenu) {
    document.querySelectorAll(".post-menu-dropdown").forEach(function (dropdown) {
        if (!exceptMenu || !exceptMenu.contains(dropdown)) {
            dropdown.hidden = true;
        }
    });
}

function handlePostAction(action, card) {
    if (!card) {
        return;
    }

    if (action === "report") {
        openReportModal(card);
        return;
    }

    if (action === "block") {
        openConfirmModal("Bạn có chắc muốn chặn người dùng này không?", function () {
            postForm("/App/Controllers/PostController.php?action=blockUser", { userId: card.dataset.ownerId })
                .then(function (data) {
                    if (!data.success) throw new Error(data.message || "Không thể chặn người dùng.");
                    document.querySelectorAll(`.post-card[data-owner-id="${card.dataset.ownerId}"]`).forEach(hidePostCard);
                    showPostToast("Đã chặn người dùng.");
                })
                .catch(showPostError);
        });
        return;
    }

    if (action === "notInterested") {
        postForm("/App/Controllers/PostController.php?action=markNotInterested", { postId: card.dataset.postId })
            .then(function (data) {
                if (!data.success) throw new Error(data.message || "Không thể ẩn bài viết.");
                hidePostCard(card);
            })
            .catch(showPostError);
        return;
    }

    if (action === "delete") {
        openConfirmModal("Bạn có chắc muốn xóa bài viết này không?", function () {
            postForm("/App/Controllers/PostController.php?action=deletePost", { postId: card.dataset.postId })
                .then(function (data) {
                    if (!data.success) throw new Error(data.message || "Không thể xóa bài viết.");
                    hidePostCard(card);
                    refreshTrendingHashtags();
                    showPostToast("Đã xóa bài viết.");
                })
                .catch(showPostError);
        });
        return;
    }

    if (action === "edit") {
        openEditPostModal(card);
        return;
    }

    if (action === "privacy") {
        openPrivacyModal(card);
    }
}

function postForm(url, values) {
    const formData = new FormData();
    Object.keys(values).forEach(function (key) {
        formData.append(key, values[key]);
    });

    return fetch(url, { method: "POST", body: formData }).then(function (response) {
        return response.json();
    });
}

function hidePostCard(card) {
    card.style.transition = "opacity 0.2s ease, transform 0.2s ease";
    card.style.opacity = "0";
    card.style.transform = "translateY(6px)";
    window.setTimeout(function () {
        card.remove();
    }, 220);
}

function ensurePostActionModals() {
    if (document.getElementById("postActionModalLayer")) {
        return;
    }

    const layer = document.createElement("div");
    layer.id = "postActionModalLayer";
    layer.className = "post-action-modal-layer";
    layer.hidden = true;
    layer.innerHTML = `
        <div class="post-action-modal" role="dialog" aria-modal="true">
            <div class="post-action-modal-header">
                <button type="button" class="post-action-back" hidden><i class="bi bi-arrow-left"></i></button>
                <h3 class="post-action-title"></h3>
                <button type="button" class="post-action-close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="post-action-modal-body"></div>
        </div>
    `;
    document.body.appendChild(layer);

    layer.addEventListener("click", function (event) {
        if (event.target === layer || event.target.closest(".post-action-close")) {
            closePostActionModal();
        }
    });
}

function openBaseModal(title, bodyHtml, options) {
    ensurePostActionModals();
    const layer = document.getElementById("postActionModalLayer");
    const modal = layer.querySelector(".post-action-modal");
    layer.querySelector("h3").innerText = title;
    layer.querySelector(".post-action-modal-body").innerHTML = bodyHtml;
    modal.className = "post-action-modal";
    if (options && options.modalClass) {
        modal.classList.add(options.modalClass);
    }
    const back = layer.querySelector(".post-action-back");
    const hasBack = Boolean(options && options.onBack);
    back.hidden = !hasBack;
    back.onclick = hasBack ? options.onBack : null;
    layer.querySelector(".post-action-modal-header").classList.toggle("has-back", hasBack);
    layer.hidden = false;
}

function closePostActionModal() {
    const layer = document.getElementById("postActionModalLayer");
    if (layer) {
        layer.hidden = true;
    }
}

function openConfirmModal(message, onConfirm) {
    openBaseModal("Xác nhận", `
        <p class="post-action-confirm-text">${escapeHTML(message)}</p>
        <div class="post-action-modal-actions">
            <button type="button" class="btn post-action-secondary">Hủy</button>
            <button type="button" class="btn btn-pink post-action-confirm">Xác nhận</button>
        </div>
    `);

    const layer = document.getElementById("postActionModalLayer");
    layer.querySelector(".post-action-secondary").onclick = closePostActionModal;
    layer.querySelector(".post-action-confirm").onclick = function () {
        closePostActionModal();
        onConfirm();
    };
}

const REPORT_DETAIL_OPTIONS = {
    "Bắt nạt hoặc quấy rối": [
        "Quấy rối tôi",
        "Quấy rối người khác",
        "Lời nói xúc phạm hoặc hạ thấp người khác",
        "Đe dọa hoặc làm phiền"
    ],
    "Nội dung nhạy cảm hoặc gây hại": [
        "Nội dung gây khó chịu hoặc không phù hợp",
        "Nội dung liên quan đến hành vi gây hại",
        "Nội dung sức khỏe nhạy cảm"
    ],
    "Bạo lực, thù ghét hoặc bóc lột": [
        "Đe dọa an toàn",
        "Thù ghét hoặc biểu tượng thù ghét",
        "Kêu gọi bạo lực",
        "Bóc lột hoặc hành vi nguy hiểm"
    ],
    "Thông tin sai lệch": [
        "Thông tin sai sự thật",
        "Gây hiểu nhầm",
        "Giả mạo hoặc lừa đảo"
    ]
};

function openReportModal(card) {
    const reasons = [
        "Tôi không thích nội dung này",
        "Bắt nạt hoặc quấy rối",
        "Nội dung nhạy cảm hoặc gây hại",
        "Bạo lực, thù ghét hoặc bóc lột",
        "Spam",
        "Thông tin sai lệch",
        "Vấn đề khác"
    ];

    function renderReasons() {
        openBaseModal("Báo cáo", `

            <div class="post-action-option-list">
                ${reasons.map(reason => `<button type="button" class="post-action-option" data-reason="${escapeHTML(reason)}"><span>${escapeHTML(reason)}</span><i class="bi bi-chevron-right"></i></button>`).join("")}
            </div>
        `);

        document.querySelectorAll(".post-action-option[data-reason]").forEach(function (button) {
            button.onclick = function () {
                const reason = button.dataset.reason;
                const details = REPORT_DETAIL_OPTIONS[reason];
                if (!details) {
                    submitReport(card, reason, reason);
                    return;
                }

                renderDetails(reason, details);
            };
        });
    }

    function renderDetails(reason, details) {
        openBaseModal("Báo cáo", `
            <div class="post-action-question">${escapeHTML(reason)}</div>
            <div class="post-action-option-list">
                ${details.map(detail => `<button type="button" class="post-action-option" data-detail="${escapeHTML(detail)}"><span>${escapeHTML(detail)}</span><i class="bi bi-chevron-right"></i></button>`).join("")}
            </div>
        `, { onBack: renderReasons });

        document.querySelectorAll(".post-action-option[data-detail]").forEach(function (button) {
            button.onclick = function () {
                submitReport(card, reason, button.dataset.detail);
            };
        });
    }

    renderReasons();
}

function submitReport(card, reason, details) {
    postForm("/App/Controllers/PostController.php?action=createReport", {
        postId: card.dataset.postId,
        reason: reason,
        details: details
    })
    .then(function (data) {
        if (!data.success) throw new Error(data.message || "Không thể gửi báo cáo.");
        openBaseModal("Báo cáo", `
            <div class="post-action-success">
                <i class="bi bi-check-circle-fill"></i>
                <p>Cảm ơn bạn đã báo cáo. Báo cáo của bạn đã được gửi đến quản trị viên để xem xét.</p>
            </div>
        `);
    })
    .catch(showPostError);
}

function openPrivacyModal(card) {
    const current = card.dataset.postPrivacy || "public";
    const options = [
        { value: "public", label: "Công khai", icon: "bi-globe2" },
        { value: "private", label: "Chỉ mình tôi", icon: "bi-lock-fill" },
        { value: "followers", label: "Người theo dõi", icon: "bi-people-fill" }
    ];

    openBaseModal("Quyền riêng tư", `
        <div class="post-action-option-list">
            ${options.map(option => `
                <button type="button" class="post-action-option" data-privacy="${option.value}">
                    <span><i class="bi ${option.icon} me-2"></i>${option.label}</span>
                    ${option.value === current ? '<i class="bi bi-check2"></i>' : ""}
                </button>
            `).join("")}
        </div>
    `);

    document.querySelectorAll("[data-privacy]").forEach(function (button) {
        button.onclick = function () {
            const privacy = button.dataset.privacy;
            postForm("/App/Controllers/PostController.php?action=updatePostPrivacy", {
                postId: card.dataset.postId,
                privacy: privacy
            })
            .then(function (data) {
                if (!data.success) throw new Error(data.message || "Không thể cập nhật quyền riêng tư.");
                card.dataset.postPrivacy = privacy;
                closePostActionModal();
                showPostToast("Đã cập nhật quyền riêng tư.");
            })
            .catch(showPostError);
        };
    });
}

function openEditPostModal(card) {
    const images = safeJsonParse(card.dataset.postImages, []);
    openBaseModal("Chỉnh sửa bài viết", `
        <form id="editPostForm" class="post-edit-form">
            <textarea id="editPostContent" class="form-control post-edit-textarea" name="content" rows="8" maxlength="5000"></textarea>
            <div class="post-edit-images">
                ${images.map(image => `
                    <label class="post-edit-image-item">
                        <input type="checkbox" name="removeImage" value="${escapeHTML(image)}">
                        <span>Xóa</span>
                        <small>${escapeHTML(image.split("/").pop())}</small>
                    </label>
                `).join("")}
            </div>
            <label class="post-edit-upload">
                <i class="bi bi-image"></i>
                <span>Thêm ảnh</span>
                <input type="file" name="images[]" accept=".jpg,.jpeg,.png,.webp,.gif,.heic,.heif,.mp4,.mov,.webm,image/*,video/*" multiple>
            </label>
            <div class="post-action-modal-actions">
                <button type="button" class="btn post-action-secondary">Hủy</button>
                <button type="submit" class="btn btn-pink">Lưu</button>
            </div>
        </form>
    `, { modalClass: "edit-post-modal" });

    const layer = document.getElementById("postActionModalLayer");
    const form = layer.querySelector("#editPostForm");
    const textarea = layer.querySelector("#editPostContent");
    textarea.value = normalizeRawPostContent(card.dataset.postContent || "");
    layer.querySelector(".post-action-secondary").onclick = closePostActionModal;
    form.onsubmit = function (event) {
        event.preventDefault();
        const formData = new FormData(form);
        const removeImages = Array.from(form.querySelectorAll("input[name='removeImage']:checked")).map(input => input.value);
        formData.append("postId", card.dataset.postId);
        formData.append("removeImages", JSON.stringify(removeImages));

        fetch("/App/Controllers/PostController.php?action=updatePost", {
            method: "POST",
            body: formData
        })
        .then(parseJsonResponse)
        .then(function (data) {
            if (!data.success) throw new Error(data.message || "Không thể cập nhật bài viết.");
            const post = data.post || (data.data && data.data.post ? data.data.post : null);
            const newContent = formData.get("content") || "";
            card.dataset.postContent = newContent;
            card.querySelector(".post-text").innerHTML = renderContentWithHashtags(newContent);
            if (post && typeof post.Images === "string") {
                card.dataset.postImages = JSON.stringify(post.Images ? post.Images.split(",").filter(Boolean) : []);
            }
            closePostActionModal();
            refreshTrendingHashtags();
            showPostToast("Đã cập nhật bài viết.");
        })
        .catch(showPostError);
    };
}

function normalizeRawPostContent(text) {
    const textarea = document.createElement("textarea");
    textarea.innerHTML = String(text || "");
    let value = textarea.value;

    value = value
        .replace(/<br\s*\/?>/gi, "\n")
        .replace(/<\/p>\s*<p>/gi, "\n")
        .replace(/<\/?p[^>]*>/gi, "")
        .replace(/<\/?a[^>]*>/gi, "")
        .replace(/&nbsp;/gi, " ");

    const scratch = document.createElement("div");
    scratch.innerHTML = value;
    value = scratch.textContent || scratch.innerText || value;

    return value.replace(/\r\n/g, "\n");
}

function parseJsonResponse(response) {
    return response.text().then(function (text) {
        try {
            return JSON.parse(text);
        } catch (error) {
            throw new Error("Phản hồi từ server không phải JSON hợp lệ.");
        }
    });
}

function safeJsonParse(text, fallback) {
    try {
        return JSON.parse(text || "[]");
    } catch (error) {
        return fallback;
    }
}

function showPostToast(message) {
    let toast = document.getElementById("postActionToast");
    if (!toast) {
        toast = document.createElement("div");
        toast.id = "postActionToast";
        toast.className = "post-action-toast";
        document.body.appendChild(toast);
    }

    toast.innerText = message;
    toast.classList.add("show");
    window.clearTimeout(toast._timer);
    toast._timer = window.setTimeout(function () {
        toast.classList.remove("show");
    }, 2600);
}

function showPostError(error) {
    console.error(error);
    showPostToast(error.message || "Có lỗi xảy ra.");
}

function initHashtagComposerSuggestions() {
    const form = document.getElementById("postForm");
    const textarea = form ? form.querySelector("textarea[name='content']") : null;
    const box = document.getElementById("hashtagSuggestionBox");

    if (!textarea || !box) {
        return;
    }

    const endpoint = box.dataset.endpoint || "/App/Controllers/SearchController.php?action=suggestHashtags";
    let debounceTimer = null;
    let activeIndex = -1;
    let suggestions = [];
    let activeToken = null;
    let lastKeyword = "";

    textarea.addEventListener("input", function () {
        window.clearTimeout(debounceTimer);
        activeToken = getActiveHashtagToken(textarea);

        if (!activeToken || activeToken.keyword.length === 0) {
            hideHashtagSuggestions();
            return;
        }

        debounceTimer = window.setTimeout(function () {
            fetchHashtagSuggestions(activeToken.keyword);
        }, 220);
    });

    textarea.addEventListener("keydown", function (event) {
        if (box.hidden) {
            return;
        }

        if (event.key === "ArrowDown") {
            event.preventDefault();
            moveHashtagSelection(1);
            return;
        }

        if (event.key === "ArrowUp") {
            event.preventDefault();
            moveHashtagSelection(-1);
            return;
        }

        if (event.key === "Enter") {
            event.preventDefault();

            if (activeIndex >= 0 && suggestions[activeIndex]) {
                insertHashtagSuggestion(suggestions[activeIndex].name);
            }

            return;
        }

        if (event.key === "Escape") {
            event.preventDefault();
            hideHashtagSuggestions();
        }
    });

    document.addEventListener("click", function (event) {
        if (!box.contains(event.target) && event.target !== textarea) {
            hideHashtagSuggestions();
        }
    });

    function fetchHashtagSuggestions(keyword) {
        lastKeyword = keyword;

        const separator = endpoint.includes("?") ? "&" : "?";

        fetch(endpoint + separator + "keyword=" + encodeURIComponent(keyword))
            .then(function (response) {
                return response.json();
            })
            .then(function (items) {
                if (keyword !== lastKeyword) {
                    return;
                }

                const normalizedItems = Array.isArray(items) ? items : [];
                renderHashtagSuggestions(keyword, normalizedItems);
            })
            .catch(function () {
                hideHashtagSuggestions();
            });
    }

    function renderHashtagSuggestions(keyword, items) {
        const keywordLower = keyword.toLowerCase();
        const hasExact = items.some(function (item) {
            return String(item.name || "").toLowerCase() === keywordLower;
        });

        suggestions = items.map(function (item) {
            return {
                name: String(item.name || ""),
                usageCount: Number(item.usage_count || 0)
            };
        }).filter(function (item) {
            return item.name !== "";
        });

        if (!hasExact) {
            suggestions.unshift({
                name: keyword,
                usageCount: 0,
                isNew: true
            });
        }

        if (suggestions.length === 0) {
            hideHashtagSuggestions();
            return;
        }

        activeIndex = 0;
        box.innerHTML = "";

        suggestions.slice(0, 10).forEach(function (item, index) {
            const button = document.createElement("button");
            button.type = "button";
            button.className = "hashtag-suggestion-item" + (index === activeIndex ? " active" : "");
            button.innerHTML = `
                <span>#${escapeHTML(item.name)}</span>
                <small>${item.isNew ? "Tạo hashtag mới" : item.usageCount + " bài viết"}</small>
            `;

            button.addEventListener("mousedown", function (event) {
                event.preventDefault();
                insertHashtagSuggestion(item.name);
            });

            box.appendChild(button);
        });

        box.hidden = false;
    }

    function moveHashtagSelection(direction) {
        const items = box.querySelectorAll(".hashtag-suggestion-item");

        if (items.length === 0) {
            return;
        }

        activeIndex = (activeIndex + direction + items.length) % items.length;

        items.forEach(function (item, index) {
            item.classList.toggle("active", index === activeIndex);
        });
    }

    function insertHashtagSuggestion(name) {
        activeToken = getActiveHashtagToken(textarea);

        if (!activeToken) {
            return;
        }

        const value = textarea.value;
        const before = value.slice(0, activeToken.start);
        const after = value.slice(activeToken.end);
        const inserted = "#" + name + " ";

        textarea.value = before + inserted + after;
        textarea.focus();
        textarea.selectionStart = textarea.selectionEnd = before.length + inserted.length;
        hideHashtagSuggestions();
    }

    function hideHashtagSuggestions() {
        box.hidden = true;
        box.innerHTML = "";
        suggestions = [];
        activeIndex = -1;
    }
}

function getActiveHashtagToken(textarea) {
    const value = textarea.value;
    const caret = textarea.selectionStart || 0;
    const beforeCaret = value.slice(0, caret);
    const hashIndex = beforeCaret.lastIndexOf("#");

    if (hashIndex < 0) {
        return null;
    }

    const prefix = hashIndex === 0 ? "" : beforeCaret.charAt(hashIndex - 1);

    if (prefix && !/\s/.test(prefix)) {
        return null;
    }

    const keyword = beforeCaret.slice(hashIndex + 1);

    if (!/^[\p{L}\p{N}_]*$/u.test(keyword)) {
        return null;
    }

    return {
        start: hashIndex,
        end: caret,
        keyword: keyword
    };
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
