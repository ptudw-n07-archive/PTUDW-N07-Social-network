const APP_BASE_URL = window.APP_BASE_URL || `${window.location.origin}/`;
let pendingCommentDelete = null;
let commentDeleteModalInstance = null;

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
            showPostToast(data.message || "Like thất bại.");
            return;
        }

        const icon = btn.querySelector("i");
        const count = btn.querySelector(".like-count");

        const isLiked = Boolean(data.isLiked ?? data.liked ?? data.status === "liked");

        count.innerText = data.likeCount;

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
        showPostToast("Có lỗi khi like bài viết.");
    });
}


// =======================
// POST - ĐĂNG BÀI AJAX
// =======================
function createPost() {
    const form = document.getElementById("postForm");

    if (!form) {
        const createUrl = window.FEED_CREATE_POST_URL || appUrl("App/Views/post/createpost.php");
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
    .then(response => {
        if (!response.ok) {
            throw new Error("Không thể kết nối tới server.");
        }

        return response.json();
    })
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
        showPostToast("Có lỗi xảy ra trong quá trình đăng bài.");
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

    return {
        source: match[1].trim(),
        content: (match[2] || "").replace(/^\s+/, "")
    };
}

function renderPostMediaHtml(images, wrapperClass = "post-media-list") {
    let html = "";

    images.forEach(img => {
        const imageSrc = normalizeImagePath(img);
        const mediaType = getMediaType(img);

        if (mediaType === "video") {
            html += `
                <video controls class="repost-embed-image no-post-nav">
                    <source src="${escapeHTML(imageSrc)}" type="${escapeHTML(getMediaMimeType(img))}">
                    Trình duyệt không hỗ trợ video này.
                </video>
            `;
            return;
        }

        if (mediaType === "image") {
            html += `
                <img
                    src="${escapeHTML(imageSrc)}"
                    class="repost-embed-image"
                    alt="post image"
                    onerror="this.style.display='none';"
                >
            `;
            return;
        }

        html += `<a href="${escapeHTML(imageSrc)}" target="_blank" class="small d-block no-post-nav">Mở file ảnh</a>`;
    });

    return html ? `<div class="${escapeHTML(wrapperClass)}">${html}</div>` : "";
}

function renderRepostEmbedHtml(content, images) {
    const repost = parseRepostContent(content);

    if (!repost) {
        return "";
    }

    const body = repost.content.trim()
        ? renderContentWithHashtags(repost.content)
        : '<span class="text-muted">Bài viết gốc không có nội dung văn bản.</span>';

    return `
        <div class="repost-source-label">
            <i class="bi bi-arrow-repeat"></i>
            <span>Đăng lại bài viết</span>
        </div>
        <div class="repost-embed no-post-nav">
            <div class="repost-embed-header">
                <div class="repost-embed-author">
                    <span class="repost-embed-avatar"><i class="bi bi-person"></i></span>
                    <span>${escapeHTML(repost.source)}</span>
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
    const repost = parseRepostContent(post.Content || "");
    const postBodyHtml = repost
        ? renderRepostEmbedHtml(post.Content || "", images)
        : `<p class="post-text"></p>${renderPostMediaHtml(images, "post-media-list")}`;

    const avatarSrc = normalizeImagePath(post.ProfilePictureUrl || "Public/assets/img/default-avatar.jpg");
    const fullName = post.FullName || (post.Username ? `@${post.Username}` : "Bạn");
    const profileHref = appUrl(`App/Views/profile/profile.php?id=${encodeURIComponent(post.UserID || "")}`);
    const postDetailHref = appUrl(`App/Views/post/post-detail.php?id=${encodeURIComponent(post.PostID || "")}`);
    const imagesJson = JSON.stringify(images);
    const privacy = post.Privacy || "public";

    const newPost = document.createElement("div");
    newPost.className = `bg-white post-card mb-3${repost ? " repost-card" : ""}`;
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
                            <span class="like-count">0</span>
                        </button>

                        <button type="button" class="no-post-nav" onclick="toggleCommentBox(this)">
                            <i class="bi bi-chat"></i>
                            <span class="comment-count">0</span>
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

        link.href = appUrl(`App/Views/hashtags/hashtag.php?tag=${encodeURIComponent(tag)}`);
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
                <button type="button" class="comment-action-btn" onclick="showReplyForm(this)">Trả lời</button>
                ${commentData.canEdit ? '<button type="button" class="comment-action-btn" onclick="showEditCommentForm(this)">Sửa</button>' : ''}
                ${commentData.canDelete ? '<button type="button" class="comment-action-btn text-danger" onclick="deleteComment(this)">Xóa</button>' : ''}
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
    const formSlot = comment.querySelector(".comment-inline-form");
    const postId = comment.dataset.postId || (postCard ? postCard.dataset.postId : "");
    const parentCommentId = comment.dataset.rootCommentId || comment.dataset.commentId;

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
    const formSlot = comment.querySelector(".comment-inline-form");

    formSlot.innerHTML = `
        <div class="comment-report-form">
            <select class="form-select comment-report-reason">
                <option value="Spam">Spam</option>
                <option value="Harassment">Quấy rối</option>
                <option value="Inappropriate">Nội dung không phù hợp</option>
                <option value="Other">Khác</option>
            </select>
            <input type="text" class="form-control comment-report-details" placeholder="Mô tả thêm nếu cần">
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-pink comment-submit" onclick="reportComment(this)">Gửi báo cáo</button>
                <button type="button" class="btn btn-light comment-cancel-btn">Hủy</button>
            </div>
        </div>
    `;
    formSlot.querySelector(".comment-cancel-btn").onclick = function () {
        formSlot.innerHTML = "";
    };
}

function reportComment(btn) {
    const comment = btn.closest(".comment-item");
    const form = btn.closest(".comment-report-form");
    const reason = form.querySelector(".comment-report-reason").value;
    const details = form.querySelector(".comment-report-details").value.trim();

    postForm(appUrl("App/Controllers/PostController.php?action=reportComment"), {
        commentId: comment.dataset.commentId,
        reason: reason,
        details: details
    })
    .then(function (data) {
        if (!data.success) throw new Error(data.message || "Không thể báo cáo bình luận.");
        comment.querySelector(".comment-inline-form").innerHTML = "";
        showPostToast("Đã gửi báo cáo bình luận.");
    })
    .catch(showPostError);
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
        return `<a class="hashtag-link" href="${appUrl(`App/Views/hashtags/hashtag.php?tag=${encodeURIComponent(tag)}`)}">#${tag}</a>`;
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
            const createUrl = entry.dataset.createPostUrl || window.FEED_CREATE_POST_URL || appUrl("App/Views/post/createpost.php");
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
    postForm(appUrl("App/Controllers/PostController.php?action=createReport"), {
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
