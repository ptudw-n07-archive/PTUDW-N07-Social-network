const APP_BASE_URL = (function () {
    if (window.APP_BASE_URL) {
        return String(window.APP_BASE_URL).replace(/\/?$/, "/");
    }

    const script = document.currentScript || document.querySelector('script[src*="Public/assets/JS/feed.js"]');
    const scriptSrc = script ? script.getAttribute("src") || "" : "";
    const marker = "Public/assets/JS/feed.js";
    const markerIndex = scriptSrc.indexOf(marker);

    if (markerIndex >= 0) {
        return scriptSrc.slice(0, markerIndex).replace(/\/?$/, "/");
    }

    return `${window.location.origin}/`;
})();
let pendingCommentDelete = null;
let commentDeleteModalInstance = null;
let feedUpdatesPollingInFlight = false;
const FEED_UPDATES_POLL_INTERVAL = 30000;

function appUrl(path = "") {
    return APP_BASE_URL + String(path).replace(/^\/+/, "");
}

function getPrivacyLabel(privacy) {
    if (privacy === "followers") return "Người theo dõi";
    if (privacy === "private") return "Riêng tư";
    return "Công khai";
}

function getPrivacyIconClass(privacy) {
    if (privacy === "followers") return "bi-people";
    if (privacy === "private") return "bi-lock";
    return "bi-globe2";
}

function renderPrivacyBadge(privacy) {
    const safePrivacy = ["public", "followers", "private"].includes(privacy) ? privacy : "public";

    return `
        <span class="post-privacy-badge post-privacy-${safePrivacy}" data-privacy-badge>
            <i class="bi ${getPrivacyIconClass(safePrivacy)}"></i>
            <span>${getPrivacyLabel(safePrivacy)}</span>
        </span>
    `;
}

function updatePrivacyBadge(postCard, privacy) {
    if (!postCard) return;

    const safePrivacy = ["public", "followers", "private"].includes(privacy) ? privacy : "public";
    const badge = postCard.querySelector("[data-privacy-badge]");

    postCard.dataset.postPrivacy = safePrivacy;

    if (!badge) {
        return;
    }

    badge.className = `post-privacy-badge post-privacy-${safePrivacy}`;
    badge.dataset.privacyBadge = "";
    badge.innerHTML = `
        <i class="bi ${getPrivacyIconClass(safePrivacy)}"></i>
        <span>${getPrivacyLabel(safePrivacy)}</span>
    `;
}

function getFeedCsrfToken() {
    return window.FEED_CSRF_TOKEN || "";
}

function appendFeedCsrfToken(formData) {
    const token = getFeedCsrfToken();
    if (token && !formData.has("csrf_token")) {
        formData.append("csrf_token", token);
    }
}

function autoResizeCommentTextarea(textarea) {
    if (!textarea) return;

    textarea.style.height = "auto";
    textarea.style.height = `${Math.min(textarea.scrollHeight, 160)}px`;
}

function bindCommentTextareaAutoResize(scope = document) {
    scope.querySelectorAll("textarea.comment-input, textarea.comment-edit-input").forEach(function (textarea) {
        if (textarea.dataset.autoResizeBound === "1") {
            autoResizeCommentTextarea(textarea);
            return;
        }

        textarea.dataset.autoResizeBound = "1";
        textarea.addEventListener("input", function () {
            autoResizeCommentTextarea(textarea);
        });
        autoResizeCommentTextarea(textarea);
    });
}

function focusPostDetailComment() {
    const params = new URLSearchParams(window.location.search);
    const commentId = params.get("comment_id") || params.get("comment");

    if (!commentId || !/^\d+$/.test(commentId)) {
        return;
    }

    const target = document.getElementById(`comment-${commentId}`);
    if (!target) {
        return;
    }

    target.scrollIntoView({ behavior: "smooth", block: "center" });
    target.classList.add("comment-highlight");

    window.setTimeout(function () {
        target.classList.remove("comment-highlight");
    }, 3000);
}

function safeCommentSelectorValue(value) {
    const stringValue = String(value || "");

    if (window.CSS && typeof window.CSS.escape === "function") {
        return window.CSS.escape(stringValue);
    }

    return stringValue.replace(/["\\]/g, "\\$&");
}

// =======================
// LIKE BUTTON - AJAX THẬT
// =======================
function getVisiblePostIds() {
    const ids = [];

    document.querySelectorAll(".post-card[data-post-id]").forEach(function (card) {
        const postId = parseInt(card.dataset.postId || "0", 10);
        if (postId > 0 && !ids.includes(postId)) {
            ids.push(postId);
        }
    });

    return ids;
}

function setPostLikeState(card, isLiked) {
    const likeButton = card ? card.querySelector('button[onclick*="toggleLike"]') : null;
    const icon = likeButton ? likeButton.querySelector("i") : null;

    if (!likeButton || !icon) {
        return;
    }

    if (isLiked) {
        icon.classList.remove("bi-heart");
        icon.classList.add("bi-heart-fill");
        likeButton.classList.add("liked");
        likeButton.setAttribute("aria-pressed", "true");
        return;
    }

    icon.classList.remove("bi-heart-fill");
    icon.classList.add("bi-heart");
    likeButton.classList.remove("liked");
    likeButton.setAttribute("aria-pressed", "false");
}

function applyPostUpdate(update) {
    const postId = parseInt(update && update.PostID ? update.PostID : "0", 10);

    if (!postId) {
        return;
    }

    const selectorPostId = safeCommentSelectorValue(postId);
    document.querySelectorAll(`.post-card[data-post-id="${selectorPostId}"]`).forEach(function (card) {
        const likeCount = card.querySelector("[data-like-count], .like-count");
        const commentCount = card.querySelector("[data-comment-count], .comment-count");

        if (likeCount && update.LikeCount !== undefined) {
            likeCount.innerText = parseInt(update.LikeCount || 0, 10);
        }

        if (commentCount && update.CommentCount !== undefined) {
            commentCount.innerText = parseInt(update.CommentCount || 0, 10);
        }

        if (update.IsLiked !== undefined) {
            setPostLikeState(card, Boolean(update.IsLiked));
        }
    });
}

function pollPostUpdates() {
    if (document.hidden || feedUpdatesPollingInFlight) {
        return;
    }

    const postIds = getVisiblePostIds();
    if (!postIds.length) {
        return;
    }

    const params = new URLSearchParams();
    postIds.slice(0, 100).forEach(function (postId) {
        params.append("postIds[]", postId);
    });

    feedUpdatesPollingInFlight = true;

    fetch(`${appUrl("App/Controllers/PostController.php?action=getPostUpdates")}&${params.toString()}`, {
        method: "GET",
        headers: {
            "Accept": "application/json"
        }
    })
        .then(function (response) {
            return response.ok ? response.json() : null;
        })
        .then(function (data) {
            if (!data || !data.success || !Array.isArray(data.posts)) {
                return;
            }

            data.posts.forEach(applyPostUpdate);
        })
        .catch(function () {
        })
        .finally(function () {
            feedUpdatesPollingInFlight = false;
        });
}

function initPostUpdatesPolling() {
    if (window.__archivePostUpdatesPollingStarted || !getVisiblePostIds().length) {
        return;
    }

    window.__archivePostUpdatesPollingStarted = true;
    window.setInterval(function () {
        if (!document.hidden) {
            pollPostUpdates();
        }
    }, FEED_UPDATES_POLL_INTERVAL);
}

function openPostDetail(element, event) {
    if (event) {
        const interactiveTarget = event.target.closest("a, button, input, textarea, select, label, video, .no-post-nav");

        if (interactiveTarget) {
            return;
        }
    }

    const url = element.dataset.postUrl;

    if (url) {
        window.location.href = url;
    }
}

function handlePostClickableKeydown(element, event) {
    if (event.key !== "Enter" && event.key !== " ") {
        return;
    }

    event.preventDefault();
    openPostDetail(element, event);
}

function toggleLike(btn) {
    const postId = btn.dataset.postId;

    if (!postId) {
        showPostToast("Không tìm thấy PostID.");
        return;
    }

    const icon = btn.querySelector("i");
    const countEl = btn.querySelector(".like-count");

    const prevLiked = btn.classList.contains("liked");
    const prevCount = parseInt(countEl.innerText, 10) || 0;

    const newCount = prevLiked ? prevCount - 1 : prevCount + 1;
    countEl.innerText = newCount;

    if (!prevLiked) {
        icon.classList.remove("bi-heart");
        icon.classList.add("bi-heart-fill");
        btn.classList.add("liked");
        btn.setAttribute("aria-pressed", "true");
        // Heartbeat animation
        btn.classList.remove("animate-like");
        void btn.offsetWidth;
        btn.classList.add("animate-like");
        setTimeout(() => btn.classList.remove("animate-like"), 500);
    } else {
        icon.classList.remove("bi-heart-fill");
        icon.classList.add("bi-heart");
        btn.classList.remove("liked");
        btn.setAttribute("aria-pressed", "false");
    }

    const formData = new FormData();
    formData.append("postId", postId);
    appendFeedCsrfToken(formData);

    fetch(appUrl("App/Controllers/PostController.php?action=like"), {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            rollbackLike(btn, prevLiked, prevCount);
            showPostToast(data.message || "Like thất bại.");
            return;
        }

        countEl.innerText = data.likeCount;
        const isLiked = Boolean(data.isLiked ?? data.liked ?? data.status === "liked");
        if (isLiked) {
            icon.classList.remove("bi-heart");
            icon.classList.add("bi-heart-fill");
            btn.classList.add("liked");
            btn.setAttribute("aria-pressed", "true");
        } else {
            icon.classList.remove("bi-heart-fill");
            icon.classList.add("bi-heart");
            btn.classList.remove("liked");
            btn.setAttribute("aria-pressed", "false");
        }
    })
    .catch(error => {
        console.error(error);
        rollbackLike(btn, prevLiked, prevCount);
        showPostToast("Có lỗi khi like bài viết.");
    });
}

function rollbackLike(btn, prevLiked, prevCount) {
    const icon = btn.querySelector("i");
    const countEl = btn.querySelector(".like-count");

    countEl.innerText = prevCount;

    if (prevLiked) {
        icon.classList.remove("bi-heart");
        icon.classList.add("bi-heart-fill");
        btn.classList.add("liked");
        btn.setAttribute("aria-pressed", "true");
    } else {
        icon.classList.remove("bi-heart-fill");
        icon.classList.add("bi-heart");
        btn.classList.remove("liked");
        btn.setAttribute("aria-pressed", "false");
    }
}


// =======================
// POST - ĐĂNG BÀI AJAX
// =======================
function createPost() {
    const form = document.getElementById("postForm");

    if (!form) {
        const createUrl = window.FEED_CREATE_POST_URL || appUrl("create-post");
        window.location.href = createUrl;
        return;
    }

    const formData = new FormData(form);
    appendFeedCsrfToken(formData);
    const content = formData.get("content") ? formData.get("content").trim() : "";
    const imageInput = document.getElementById("postImages");
    const images = imageInput ? imageInput.files : [];

    if (content === "" && images.length === 0) {
        showPostToast("Bạn hãy nhập nội dung hoặc chọn ảnh.");
        return;
    }

    fetch(appUrl("App/Controllers/PostController.php?action=create"), {
        method: "POST",
        body: formData
    })
    .then(parseCreatePostResponse)
    .then(data => {
        if (!data.success) {
            showPostToast(data.message || "Không thể đăng bài.");
            return;
        }

        addPostToUI(data.post);
        refreshTrendingHashtags();

        if (Array.isArray(data.uploadErrors) && data.uploadErrors.length > 0) {
            showPostToast(data.uploadErrors[0]);
        }

        form.reset();

        const previewContainer = document.getElementById("preview-container");
        if (previewContainer) {
            previewContainer.innerHTML = "";
        }
    })
    .catch(error => {
        console.error(error);
        showPostToast(error.message || "Có lỗi xảy ra trong quá trình đăng bài.");
    });
}

function parseCreatePostResponse(response) {
    return response.text().then(function (text) {
        let data = null;

        try {
            data = text ? JSON.parse(text) : null;
        } catch (error) {
            console.error("Non-JSON create post response:", text);
            throw new Error("Server trả về phản hồi không hợp lệ.");
        }

        if (!response.ok) {
            throw new Error((data && data.message) || "Không thể kết nối tới server.");
        }

        return data || { success: false, message: "Server không trả dữ liệu." };
    });
}

function repostPost(btn) {
    const postId = btn.dataset.postId;

    if (!postId) {
        showPostToast("Không tìm thấy bài viết để đăng lại.");
        return;
    }

    btn.disabled = true;

    postForm(appUrl("App/Controllers/PostController.php?action=repost"), {
        postId: postId
    })
    .then(function (data) {
        if (!data.success) {
            throw new Error(data.message || "Không thể đăng lại bài viết.");
        }

        const newPost = data.post || (data.data && data.data.post);
        if (newPost) {
            addPostToUI(newPost);
        }

        showPostToast(data.message || "Đã đăng lại bài viết.");
    })
    .catch(showPostError)
    .finally(function () {
        btn.disabled = false;
    });
}

function parseRepostContent(content) {
    const match = String(content || "").match(/^Đăng lại từ\s+(@[^\s:]+):\s*([\s\S]*)$/u);

    if (!match) {
        return null;
    }

    let source = match[1].trim();
    let nestedContent = (match[2] || "").replace(/^\s+/, "");
    let nestedMatch = nestedContent.match(/^Đăng lại từ\s+(@[^\s:]+):\s*([\s\S]*)$/u);

    while (nestedMatch) {
        source = nestedMatch[1].trim();
        nestedContent = (nestedMatch[2] || "").replace(/^\s+/, "");
        nestedMatch = nestedContent.match(/^Đăng lại từ\s+(@[^\s:]+):\s*([\s\S]*)$/u);
    }

    return {
        source: source,
        content: nestedContent
    };
}

function renderPostMediaHtml(images, wrapperClass = "post-media-list") {
    const mediaItems = (Array.isArray(images) ? images : []).map(img => {
        const imageSrc = normalizeImagePath(img);

        if (!imageSrc) {
            return null;
        }

        return {
            path: img,
            src: imageSrc,
            type: getMediaType(img)
        };
    }).filter(Boolean);

    if (!mediaItems.length) {
        return "";
    }

    const isCarousel = mediaItems.length > 1;
    let html = "";

    mediaItems.forEach(media => {
        let mediaHtml = "";

        if (media.type === "video") {
            mediaHtml = `
                <video controls class="post-media-video no-post-nav">
                    <source src="${escapeHTML(media.src)}" type="${escapeHTML(getMediaMimeType(media.path))}">
                    Trình duyệt không hỗ trợ video này.
                </video>
            `;
        } else if (media.type === "image") {
            mediaHtml = `
                <img
                    src="${escapeHTML(media.src)}"
                    class="post-media-image"
                    alt="post image"
                    loading="lazy"
                    onerror="this.style.display='none';"
                >
            `;
        } else {
            mediaHtml = `<a href="${escapeHTML(media.src)}" target="_blank" class="post-media-file no-post-nav">Mở file ảnh</a>`;
        }

        html += `<div class="post-media-slide">${mediaHtml}</div>`;
    });

    const prevButton = isCarousel
        ? `<button type="button" class="post-media-nav post-media-prev no-post-nav" onclick="scrollPostMedia(this, -1)" aria-label="Ảnh trước"><i class="bi bi-chevron-left"></i></button>`
        : "";
    const nextButton = isCarousel
        ? `<button type="button" class="post-media-nav post-media-next no-post-nav" onclick="scrollPostMedia(this, 1)" aria-label="Ảnh tiếp theo"><i class="bi bi-chevron-right"></i></button><span class="post-media-counter">${mediaItems.length} ảnh</span>`
        : "";

    return `<div class="${escapeHTML(wrapperClass)} post-media-scroll media-count-${Math.min(mediaItems.length, 4)} media-total-${mediaItems.length}${isCarousel ? " has-multiple-media" : " has-single-media"}">${prevButton}<div class="post-media-track">${html}</div>${nextButton}</div>`;
}

function scrollPostMedia(button, direction) {
    const mediaWrap = button.closest(".post-media-scroll");
    const track = mediaWrap ? mediaWrap.querySelector(".post-media-track") : null;
    const slide = track ? track.querySelector(".post-media-slide") : null;

    if (!track || !slide) {
        return;
    }

    const gap = 12;
    const scrollAmount = slide.getBoundingClientRect().width + gap;
    track.scrollBy({
        left: scrollAmount * direction,
        behavior: "smooth"
    });
}

function initPostDetailPhotoModal() {
    const photoModalElement = document.getElementById("postDetailPhotoModal");
    const photoModalImage = document.getElementById("postDetailPhotoModalImage");
    const photoModalLabel = document.getElementById("postDetailPhotoModalLabel");

    if (!photoModalElement || !photoModalImage) {
        return;
    }

    const postDetailMedia = document.querySelector(".post-detail-card .post-detail-media");
    const postDetailImages = postDetailMedia
        ? Array.from(postDetailMedia.querySelectorAll(".post-media-image")).filter(function (image) {
            return Boolean(image.currentSrc || image.src);
        })
        : [];

    if (postDetailImages.length === 0) {
        return;
    }

    const postDetailPhotos = postDetailImages.map(function (image) {
        return {
            src: image.currentSrc || image.src,
            alt: image.alt || "Ảnh bài viết"
        };
    });
    const prevPhotoButton = document.querySelector("[data-post-detail-photo-prev]");
    const nextPhotoButton = document.querySelector("[data-post-detail-photo-next]");
    let activePhotoIndex = 0;

    function openPostDetailPhoto(index) {
        if (!window.bootstrap) {
            return;
        }

        activePhotoIndex = (index + postDetailPhotos.length) % postDetailPhotos.length;
        const photo = postDetailPhotos[activePhotoIndex] || {};

        photoModalImage.src = photo.src || "";
        photoModalImage.alt = photo.alt || "Ảnh bài viết";

        if (photoModalLabel) {
            photoModalLabel.textContent = `Ảnh bài viết ${activePhotoIndex + 1}/${postDetailPhotos.length}`;
        }

        if (prevPhotoButton) {
            prevPhotoButton.hidden = postDetailPhotos.length <= 1;
        }

        if (nextPhotoButton) {
            nextPhotoButton.hidden = postDetailPhotos.length <= 1;
        }

        window.bootstrap.Modal.getOrCreateInstance(photoModalElement).show();
    }

    postDetailImages.forEach(function (image, index) {
        image.classList.add("post-detail-photo-trigger", "no-post-nav");
        image.setAttribute("role", "button");
        image.setAttribute("tabindex", "0");
        image.setAttribute("aria-label", `Xem ảnh ${index + 1}/${postDetailPhotos.length}`);

        image.addEventListener("click", function (event) {
            event.preventDefault();
            event.stopPropagation();
            openPostDetailPhoto(index);
        });

        image.addEventListener("keydown", function (event) {
            if (event.key !== "Enter" && event.key !== " ") {
                return;
            }

            event.preventDefault();
            openPostDetailPhoto(index);
        });
    });

    if (prevPhotoButton) {
        prevPhotoButton.addEventListener("click", function () {
            openPostDetailPhoto(activePhotoIndex - 1);
        });
    }

    if (nextPhotoButton) {
        nextPhotoButton.addEventListener("click", function () {
            openPostDetailPhoto(activePhotoIndex + 1);
        });
    }

    photoModalElement.addEventListener("keydown", function (event) {
        if (postDetailPhotos.length <= 1) {
            return;
        }

        if (event.key === "ArrowLeft") {
            openPostDetailPhoto(activePhotoIndex - 1);
        }

        if (event.key === "ArrowRight") {
            openPostDetailPhoto(activePhotoIndex + 1);
        }
    });
}

function renderRepostEmbedHtml(post) {
    const repost = parseRepostContent(post.Content || "");

    if (!repost && !post.OriginalPostID) {
        return "";
    }

    const originalContent = String(post.OriginalContent || "").trim();
    let displayContent = originalContent || (repost ? repost.content : "");
    const nestedRepost = parseRepostContent(displayContent);
    if (nestedRepost) {
        displayContent = nestedRepost.content;
    }

    const sourceUsername = String(post.OriginalUsername || "").trim();
    const sourceName = String(post.OriginalFullName || "").trim()
        || (sourceUsername ? `@${sourceUsername}` : (repost ? repost.source : "@nguoi-dung"));
    const sourceAvatar = normalizeImagePath(post.OriginalProfilePictureUrl || "Public/assets/img/default-avatar.jpg");
    const images = Array.isArray(post.OriginalImages)
        ? post.OriginalImages
        : (typeof post.OriginalImages === "string" && post.OriginalImages.trim() !== ""
            ? post.OriginalImages.split(",").map(item => item.trim()).filter(Boolean)
            : (Array.isArray(post.Images) ? post.Images : []));

    const body = displayContent.trim()
        ? renderContentWithHashtags(displayContent)
        : '<span class="text-muted">Bài viết gốc không có nội dung văn bản.</span>';

    return `
        <div class="repost-source-label">
            <i class="bi bi-arrow-repeat"></i>
            <span>Đăng lại bài viết</span>
        </div>
        <div class="repost-embed no-post-nav">
            <div class="repost-embed-header">
                <div class="repost-embed-author">
                    <img src="${escapeHTML(sourceAvatar)}" class="repost-embed-avatar" alt="avatar" onerror="this.src='${appUrl("Public/assets/img/default-avatar.jpg")}';">
                    <span>${escapeHTML(sourceName)}</span>
                </div>
                <div class="repost-embed-meta">Bài viết gốc</div>
            </div>
            <div class="repost-embed-content post-text">${body}</div>
            ${renderPostMediaHtml(images, "repost-embed-media")}
        </div>
    `;
}


// =======================
// THÊM BÀI MỚI LÊN FEED
// =======================
function addPostToUI(post) {
    const postsList = document.getElementById("posts-list");

    if (!postsList) {
        return;
    }

    const images = Array.isArray(post.Images) ? post.Images : [];
    const repost = parseRepostContent(post.Content || "") || post.OriginalPostID;
    const postBodyHtml = repost
        ? renderRepostEmbedHtml(post)
        : `<p class="post-text"></p>${renderPostMediaHtml(images, "post-media-list")}`;

    const avatarSrc = normalizeImagePath(post.ProfilePictureUrl || "Public/assets/img/default-avatar.jpg");
    const fullName = post.FullName || (post.Username ? `@${post.Username}` : "Bạn");
    const profileHref = appUrl(`profile?id=${encodeURIComponent(post.UserID || "")}`);
    const postDetailHref = appUrl(`post-detail?id=${encodeURIComponent(post.PostID || "")}`);
    const imagesJson = JSON.stringify(images);
    const privacy = post.Privacy || "public";

    const newPost = document.createElement("div");
    newPost.className = `bg-white post-card mb-3${repost ? " repost-card" : ""} post-card-new`;
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
                    <img src="${escapeHTML(avatarSrc)}" class="avatar" alt="avatar" onerror="this.src='${appUrl("Public/assets/img/default-avatar.jpg")}';">
                </a>

                <div class="flex-grow-1">
                    <div class="post-card-header">
                        <div class="fw-semibold post-meta-line">
                        <a href="${profileHref}" class="text-decoration-none text-dark">${escapeHTML(fullName)}</a>
                        <span class="post-time">• vừa xong</span>
                        ${renderPrivacyBadge(privacy)}
                        </div>
                        ${renderPostMenu(post.PostID, post.UserID, true)}
                    </div>

                    <div
                        class="post-clickable"
                        role="link"
                        tabindex="0"
                        data-post-url="${postDetailHref}"
                        onclick="openPostDetail(this, event)"
                        onkeydown="handlePostClickableKeydown(this, event)"
                    >
                        ${postBodyHtml}
                    </div>

                    <div class="post-actions d-flex gap-4">
                        <button type="button" class="feed-like-btn no-post-nav" onclick="toggleLike(this)" data-post-id="${post.PostID}" aria-pressed="false">
                            <i class="bi bi-heart"></i>
                            <span class="like-count" data-like-count>0</span>
                        </button>

                        <button type="button" class="no-post-nav" onclick="toggleCommentBox(this)">
                            <i class="bi bi-chat"></i>
                            <span class="comment-count" data-comment-count>0</span>
                        </button>

                        <button type="button" class="no-post-nav repost-btn" onclick="repostPost(this)" data-post-id="${post.PostID}" title="Đăng lại bài viết">
                            <i class="bi bi-arrow-repeat"></i>
                        </button>
                    </div>

                    <div class="comment-box mt-3 d-none no-post-nav">
                        <div class="comment-form d-flex gap-2">
                            <textarea
                                class="form-control comment-input"
                                placeholder="Viết bình luận..."
                                rows="1"
                            ></textarea>

                            <button
                                type="button"
                                class="btn btn-pink comment-submit"
                                onclick="sendComment(this)"
                                data-post-id="${post.PostID}"
                            >
                                Gửi
                            </button>
                        </div>

                        <div class="comment-list"></div>
                    </div>
                </div>
            </div>
        </div>
    `;

    const textElement = newPost.querySelector(".post-clickable > .post-text");
    if (textElement && !repost) {
        textElement.innerHTML = renderContentWithHashtags(post.Content || "");
    }
    postsList.prepend(newPost);
    bindCommentTextareaAutoResize(newPost);
    initPostUpdatesPolling();
}

function refreshTrendingHashtags() {
    const container = document.getElementById("trendingHashtagsContainer");

    if (!container) {
        return;
    }

    const card = container.closest(".trending-hashtags-card");
    const endpoint = card && card.dataset.trendingEndpoint
        ? card.dataset.trendingEndpoint
        : appUrl("App/Controllers/PostController.php?action=trendingHashtags");

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

        link.href = appUrl(`hashtag?tag=${encodeURIComponent(tag)}`);
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
        const wasHidden = commentBox.classList.contains("d-none");
        commentBox.classList.toggle("d-none");
        if (wasHidden) {
            commentBox.classList.remove("comment-box-enter");
            void commentBox.offsetWidth;
            commentBox.classList.add("comment-box-enter");
        }
    }
}


// =======================
// COMMENT ACTIONS
// =======================
function sendComment(btn) {
    const postId = btn.dataset.postId;
    const postCard = btn.closest(".post-card");
    const form = btn.closest(".comment-form, .comment-reply-form");
    const input = form ? form.querySelector(".comment-input") : postCard.querySelector(".comment-input");
    const commentList = postCard.querySelector(".comment-list");
    const commentCount = postCard.querySelector(".comment-count");
    const parentCommentId = btn.dataset.parentCommentId || "";
    const content = input.value.trim();

    if (parentCommentId) {
        const sourceComment = btn.closest(".comment-item");
        if (!sourceComment || sourceComment.classList.contains("comment-reply") || sourceComment.dataset.parentCommentId !== "0") {
            showPostToast("Chỉ có thể trả lời bình luận gốc.");
            return;
        }
    }

    if (content === "") {
        showPostToast("Bạn chưa nhập bình luận.");
        return;
    }

    const formData = new FormData();
    formData.append("postId", postId);
    formData.append("content", content);
    if (parentCommentId) {
        formData.append("parentCommentId", parentCommentId);
    }
    appendFeedCsrfToken(formData);

    fetch(appUrl("App/Controllers/PostController.php?action=comment"), {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            showPostToast(data.message || "Không thể bình luận.");
            return;
        }

        const emptyState = commentList.querySelector(".post-detail-empty-comments");
        if (emptyState) {
            emptyState.remove();
        }

        const isPostDetail = Boolean(postCard.querySelector(".post-detail-text"));
        const comment = isPostDetail ? renderPostDetailComment(data.comment) : renderComment(data.comment);

        if (data.comment.parentCommentId) {
            insertReplyComment(commentList, comment, data.comment.parentCommentId);
        } else {
            commentList.appendChild(comment);
        }

        input.value = "";
        autoResizeCommentTextarea(input);
        const inlineForm = btn.closest(".comment-inline-form");
        if (inlineForm) {
            inlineForm.innerHTML = "";
        }

        if (commentCount) {
            commentCount.innerText = parseInt(commentCount.innerText || "0", 10) + 1;
        }
    })
    .catch(error => {
        console.error(error);
        showPostToast("Có lỗi khi gửi bình luận.");
    });
}

function insertReplyComment(commentList, comment, rootCommentId) {
    const rootId = String(rootCommentId || "");
    const selectorRootId = safeCommentSelectorValue(rootId);
    const threadedComments = Array.from(commentList.querySelectorAll(
        `.comment-item[data-root-comment-id="${selectorRootId}"]`
    ));
    const lastThreadComment = threadedComments[threadedComments.length - 1];

    if (lastThreadComment) {
        lastThreadComment.insertAdjacentElement("afterend", comment);
        return;
    }

    const parentComment = commentList.querySelector(`[data-comment-id="${selectorRootId}"]`);
    if (parentComment) {
        parentComment.insertAdjacentElement("afterend", comment);
        return;
    }

    commentList.appendChild(comment);
}

function renderPostDetailComment(commentData) {
    return renderComment(commentData);
}

function renderComment(commentData) {
    const comment = document.createElement("div");
    const commentAvatar = normalizeImagePath(commentData.profilePictureUrl || "Public/assets/img/default-avatar.jpg");
    const isReply = Boolean(commentData.parentCommentId);
    const rootCommentId = isReply ? commentData.parentCommentId : commentData.commentId;

    comment.className = `comment-item${isReply ? " comment-reply" : ""}`;
    comment.id = commentData.commentId ? `comment-${commentData.commentId}` : "";
    comment.dataset.commentId = commentData.commentId || "";
    comment.dataset.postId = commentData.postId || "";
    comment.dataset.ownerId = commentData.userId || "";
    comment.dataset.parentCommentId = commentData.parentCommentId || "0";
    comment.dataset.rootCommentId = rootCommentId || commentData.commentId || "";
    comment.dataset.canEdit = commentData.canEdit ? "1" : "0";
    comment.dataset.canDelete = commentData.canDelete ? "1" : "0";
    comment.dataset.canReport = commentData.canReport ? "1" : "0";
    comment.innerHTML = `
        <img src="${escapeHTML(commentAvatar)}" class="comment-avatar" alt="avatar" onerror="this.src='${appUrl("Public/assets/img/default-avatar.jpg")}';">
        <div class="comment-body">
            <div class="comment-bubble">
                <div class="comment-meta">
                    <strong class="comment-author">${escapeHTML(commentData.fullName || "Bạn")}</strong>
                    <span class="comment-time">• vừa xong</span>
                </div>
                <div class="comment-content">${escapeHTML(commentData.content || "")}</div>
            </div>
            <div class="comment-actions">
                ${!isReply ? '<button type="button" class="comment-action-btn" onclick="showReplyForm(this)">Trả lời</button>' : ''}
                ${commentData.canEdit ? '<button type="button" class="comment-action-btn" onclick="showEditCommentForm(this)">Sửa</button>' : ''}
                ${commentData.canDelete ? '<button type="button" class="comment-action-btn comment-delete-action" onclick="deleteComment(this)">Xóa</button>' : ''}
                ${commentData.canReport ? '<button type="button" class="comment-action-btn" onclick="showReportCommentForm(this)">Báo cáo</button>' : ''}
            </div>
            <div class="comment-inline-form"></div>
        </div>
    `;

    return comment;
}

function showReplyForm(btn) {
    const comment = btn.closest(".comment-item");
    const postCard = btn.closest(".post-card");

    if (!comment || comment.classList.contains("comment-reply") || comment.dataset.parentCommentId !== "0") {
        showPostToast("Chỉ có thể trả lời bình luận gốc.");
        return;
    }

    const formSlot = comment.querySelector(".comment-inline-form");
    const postId = comment.dataset.postId || (postCard ? postCard.dataset.postId : "");
    const parentCommentId = comment.dataset.commentId;

    formSlot.innerHTML = `
        <div class="comment-reply-form d-flex gap-2">
            <textarea class="form-control comment-input" placeholder="Viết phản hồi..." rows="1"></textarea>
            <button type="button" class="btn btn-pink comment-submit" onclick="sendComment(this)" data-post-id="${escapeHTML(postId)}" data-parent-comment-id="${escapeHTML(parentCommentId)}">Gửi</button>
            <button type="button" class="btn btn-light comment-cancel-btn">Hủy</button>
        </div>
    `;
    formSlot.querySelector(".comment-cancel-btn").onclick = function () {
        formSlot.innerHTML = "";
    };
    bindCommentTextareaAutoResize(formSlot);
    formSlot.querySelector(".comment-input").focus();
}

function showEditCommentForm(btn) {
    const comment = btn.closest(".comment-item");
    const formSlot = comment.querySelector(".comment-inline-form");
    const content = comment.querySelector(".comment-content").innerText;

    formSlot.innerHTML = `
        <div class="comment-edit-form d-flex gap-2">
            <textarea class="form-control comment-edit-input" rows="1">${escapeHTML(content)}</textarea>
            <button type="button" class="btn btn-pink comment-submit" onclick="submitEditComment(this)">Lưu</button>
            <button type="button" class="btn btn-light comment-cancel-btn">Hủy</button>
        </div>
    `;
    formSlot.querySelector(".comment-cancel-btn").onclick = function () {
        formSlot.innerHTML = "";
    };
    bindCommentTextareaAutoResize(formSlot);
    formSlot.querySelector(".comment-edit-input").focus();
}

function submitEditComment(btn) {
    const comment = btn.closest(".comment-item");
    const input = comment.querySelector(".comment-edit-input");
    const content = input.value.trim();

    if (!content) {
        showPostToast("Bình luận không được để trống.");
        return;
    }

    postForm(appUrl("App/Controllers/PostController.php?action=updateComment"), {
        commentId: comment.dataset.commentId,
        content: content
    })
    .then(function (data) {
        if (!data.success) throw new Error(data.message || "Không thể sửa bình luận.");
        comment.querySelector(".comment-content").innerText = data.comment && data.comment.content ? data.comment.content : content;
        comment.querySelector(".comment-inline-form").innerHTML = "";
        showPostToast("Đã cập nhật bình luận.");
    })
    .catch(showPostError);
}

function deleteComment(btn) {
    const comment = btn.closest(".comment-item");
    const postCard = btn.closest(".post-card");
    const modalElement = document.getElementById("deleteCommentModal");

    if (!comment || !postCard || !modalElement || !window.bootstrap) {
        showPostToast("Không thể mở xác nhận xóa bình luận.");
        return;
    }

    pendingCommentDelete = {
        comment,
        postCard,
        commentId: comment.dataset.commentId
    };

    const confirmButton = modalElement.querySelector("[data-confirm-delete-comment]");
    if (confirmButton) {
        confirmButton.disabled = false;
        confirmButton.innerText = "Xóa";
    }

    commentDeleteModalInstance = window.bootstrap.Modal.getOrCreateInstance(modalElement);
    commentDeleteModalInstance.show();
}

function confirmPendingCommentDelete() {
    if (!pendingCommentDelete) {
        return;
    }

    const modalElement = document.getElementById("deleteCommentModal");
    const confirmButton = modalElement ? modalElement.querySelector("[data-confirm-delete-comment]") : null;
    const { comment, postCard, commentId } = pendingCommentDelete;
    const commentCount = postCard ? postCard.querySelector(".comment-count") : null;

    if (confirmButton) {
        confirmButton.disabled = true;
        confirmButton.innerText = "Đang xóa...";
    }

    postForm(appUrl("App/Controllers/PostController.php?action=deleteComment"), {
        commentId: commentId
    })
    .then(function (data) {
        if (!data.success) throw new Error(data.message || "Không thể xóa bình luận.");
        const replySelector = `.comment-item[data-parent-comment-id="${commentId}"]`;
        const childReplies = postCard ? Array.from(postCard.querySelectorAll(replySelector)) : [];
        const removedCount = 1 + childReplies.length;
        childReplies.forEach(function (reply) {
            reply.remove();
        });
        comment.remove();
        if (commentCount) {
            commentCount.innerText = Math.max(0, parseInt(commentCount.innerText || "0", 10) - removedCount);
        }
        if (commentDeleteModalInstance) {
            commentDeleteModalInstance.hide();
        }
        pendingCommentDelete = null;
        showPostToast("Đã xóa bình luận.");
    })
    .catch(function (error) {
        if (confirmButton) {
            confirmButton.disabled = false;
            confirmButton.innerText = "Xóa";
        }
        showPostError(error);
    });
}

function initCommentDeleteModal() {
    const modalElement = document.getElementById("deleteCommentModal");

    if (!modalElement) {
        return;
    }

    const confirmButton = modalElement.querySelector("[data-confirm-delete-comment]");
    if (confirmButton) {
        confirmButton.addEventListener("click", confirmPendingCommentDelete);
    }

    modalElement.addEventListener("hidden.bs.modal", function () {
        pendingCommentDelete = null;
        const button = modalElement.querySelector("[data-confirm-delete-comment]");
        if (button) {
            button.disabled = false;
            button.innerText = "Xóa";
        }
    });
}

function showReportCommentForm(btn) {
    const comment = btn.closest(".comment-item");

    if (!comment || !comment.dataset.commentId) {
        showPostToast("Không tìm thấy bình luận để báo cáo.");
        return;
    }

    openReportModal({
        kind: "comment",
        commentId: comment.dataset.commentId
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
        return `<a class="hashtag-link" href="${appUrl(`hashtag?tag=${encodeURIComponent(tag)}`)}">#${tag}</a>`;
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
        return appUrl("Public/assets/img/default-avatar.jpg");
    }

    path = String(path).trim();

    if (path.startsWith("http://") || path.startsWith("https://")) {
        return path;
    }

    const cleanPath = path.replace(/^\/+/, "");

    if (cleanPath.startsWith("Public/")) {
        return appUrl(cleanPath);
    }

    if (cleanPath.startsWith("uploads/") || cleanPath.startsWith("assets/")) {
        return appUrl("Public/" + cleanPath);
    }

    return appUrl(cleanPath);
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
        showPostToast("Không tìm thấy UserID.");
        return;
    }

    const formData = new FormData();
    formData.append("userId", userId);
    appendFeedCsrfToken(formData);

    fetch(appUrl("App/Controllers/FollowController.php?action=toggle"), {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            showPostToast(data.message || "Không thể xử lý theo dõi.");
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
        showPostToast("Có lỗi khi theo dõi.");
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

function initFeedCreateEntry() {
    document.querySelectorAll("[data-create-post-url]").forEach(function (entry) {
        const openCreatePost = function () {
            const createUrl = entry.dataset.createPostUrl || window.FEED_CREATE_POST_URL || appUrl("create-post");
            window.location.href = createUrl;
        };

        entry.addEventListener("click", openCreatePost);
        entry.addEventListener("keydown", function (event) {
            if (event.key !== "Enter" && event.key !== " ") {
                return;
            }

            event.preventDefault();
            openCreatePost();
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
    initFeedCreateEntry();
    initCommentDeleteModal();
    bindCommentTextareaAutoResize();
    focusPostDetailComment();
    initPostDetailPhotoModal();
    initHashtagComposerSuggestions();
    initPostUpdatesPolling();
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
            postForm(appUrl("App/Controllers/PostController.php?action=blockUser"), { userId: card.dataset.ownerId })
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
        postForm(appUrl("App/Controllers/PostController.php?action=markNotInterested"), { postId: card.dataset.postId })
            .then(function (data) {
                if (!data.success) throw new Error(data.message || "Không thể ẩn bài viết.");
                hidePostCard(card);
            })
            .catch(showPostError);
        return;
    }

    if (action === "delete") {
        openConfirmModal("Bạn có chắc muốn xóa bài viết này không?", function () {
            postForm(appUrl("App/Controllers/PostController.php?action=deletePost"), { postId: card.dataset.postId })
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
    appendFeedCsrfToken(formData);

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

function openReportModal(target) {
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

                if (reason === "Vấn đề khác") {
                    renderOtherDetails();
                    return;
                }

                const details = REPORT_DETAIL_OPTIONS[reason];
                if (!details) {
                    submitReport(target, reason, reason);
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
                submitReport(target, reason, button.dataset.detail);
            };
        });
    }

    function renderOtherDetails() {
        openBaseModal("Báo cáo", `
            <form class="post-action-report-form">
                <div class="post-action-question">Mô tả vấn đề</div>
                <textarea
                    class="post-action-report-textarea"
                    rows="5"
                    maxlength="500"
                    placeholder="Mô tả vấn đề bạn đang gặp phải..."
                ></textarea>
                <div class="post-action-report-error" role="alert" hidden></div>
                <div class="post-action-modal-actions">
                    <button type="button" class="btn post-action-secondary">Hủy</button>
                    <button type="submit" class="btn btn-pink">Gửi báo cáo</button>
                </div>
            </form>
        `, { onBack: renderReasons });

        const layer = document.getElementById("postActionModalLayer");
        const form = layer.querySelector(".post-action-report-form");
        const textarea = layer.querySelector(".post-action-report-textarea");
        const errorBox = layer.querySelector(".post-action-report-error");

        layer.querySelector(".post-action-secondary").onclick = closePostActionModal;
        textarea.focus();

        form.onsubmit = function (event) {
            event.preventDefault();
            const details = textarea.value.trim();

            if (details === "") {
                errorBox.innerText = "Vui lòng mô tả vấn đề bạn muốn báo cáo.";
                errorBox.hidden = false;
                return;
            }

            if (details.length < 5) {
                errorBox.innerText = "Mô tả quá ngắn. Vui lòng nhập rõ hơn.";
                errorBox.hidden = false;
                return;
            }

            errorBox.hidden = true;
            submitReport(target, "Vấn đề khác", details);
        };
    }

    renderReasons();
}

function submitReport(target, reason, details) {
    const isCommentReport = target && target.kind === "comment";
    const endpoint = isCommentReport
        ? appUrl("App/Controllers/PostController.php?action=reportComment")
        : appUrl("App/Controllers/PostController.php?action=createReport");
    const values = {
        reason: reason,
        details: details
    };

    if (isCommentReport) {
        values.commentId = target.commentId;
    } else {
        values.postId = target.dataset.postId;
    }

    postForm(endpoint, values)
    .then(function (data) {
        if (!data.success) throw new Error(data.message || "Không thể gửi báo cáo.");
        closePostActionModal();
        showPostToast(isCommentReport ? "Đã gửi báo cáo bình luận." : "Đã gửi báo cáo.");
    })
    .catch(showPostError);
}

function openPrivacyModal(card) {
    const current = card.dataset.postPrivacy || "public";
    const options = [
        { value: "public", label: "Công khai", icon: "bi-globe2" },
        { value: "followers", label: "Người theo dõi", icon: "bi-people" },
        { value: "private", label: "Riêng tư", icon: "bi-lock" }
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
            postForm(appUrl("App/Controllers/PostController.php?action=updatePostPrivacy"), {
                postId: card.dataset.postId,
                privacy: privacy
            })
            .then(function (data) {
                if (!data.success) throw new Error(data.message || "Không thể cập nhật quyền riêng tư.");
                updatePrivacyBadge(card, privacy);
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
            <textarea id="editPostContent" name="content" hidden></textarea>
            <div
                id="editPostEditor"
                class="form-control post-edit-textarea post-content-editor"
                contenteditable="true"
                role="textbox"
                aria-multiline="true"
                data-content-target="editPostContent"
            ></div>
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
    const editor = layer.querySelector("#editPostEditor");
    const postContent = normalizeRawPostContent(card.dataset.postContent || "");
    textarea.value = postContent;
    editor.textContent = postContent;
    if (window.initArchiveHashtagSuggestions) {
        window.initArchiveHashtagSuggestions(layer);
    }
    layer.querySelector(".post-action-secondary").onclick = closePostActionModal;
    form.onsubmit = function (event) {
        event.preventDefault();
        if (window.syncArchiveContentEditors) {
            window.syncArchiveContentEditors(form);
        }
        const formData = new FormData(form);
        const removeImages = Array.from(form.querySelectorAll("input[name='removeImage']:checked")).map(input => input.value);
        formData.append("postId", card.dataset.postId);
        formData.append("removeImages", JSON.stringify(removeImages));
        appendFeedCsrfToken(formData);

        fetch(appUrl("App/Controllers/PostController.php?action=updatePost"), {
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
    if (window.initArchiveHashtagSuggestions) {
        window.initArchiveHashtagSuggestions(document);
        return;
    }

    const form = document.getElementById("postForm");
    const textarea = form ? form.querySelector("textarea[name='content']") : null;
    const box = document.getElementById("hashtagSuggestionBox");

    if (!textarea || !box) {
        return;
    }

    const endpoint = box.dataset.endpoint || appUrl("App/Controllers/SearchController.php?action=suggestHashtags");
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
                <span class="hashtag-suggestion-name">#${escapeHTML(item.name)}</span>
                <span class="hashtag-suggestion-meta">${item.isNew ? "Tạo hashtag mới" : item.usageCount + " bài viết"}</span>
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
