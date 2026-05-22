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

    fetch("/Controllers/PostController.php?action=like", {
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

    fetch("/Controllers/PostController.php?action=create", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert(data.message);
            return;
        }

        addPostToUI(data.post);
        form.reset();
    })
    .catch(error => {
        console.error(error);
        alert("Có lỗi xảy ra khi đăng bài.");
    });
}


// =======================
// THÊM BÀI MỚI LÊN GIAO DIỆN
// =======================
function addPostToUI(post) {
    const postsList = document.getElementById("posts-list");

    if (!postsList) {
        alert("Không tìm thấy danh sách bài viết.");
        return;
    }

    let imageHtml = "";

    if (post.Images && post.Images.length > 0) {
        post.Images.forEach(img => {
            imageHtml += `
                <img 
                    src="/${img}" 
                    class="img-fluid rounded-4 mb-3"
                    style="max-height: 450px; object-fit: cover;"
                    alt="post image"
                >
            `;
        });
    }

    let avatarSrc = "/Public/assets/img/default-avatar.jpg";

    if (post.ProfilePictureUrl && post.ProfilePictureUrl.trim() !== "") {
        if (
            post.ProfilePictureUrl.startsWith("http://") ||
            post.ProfilePictureUrl.startsWith("https://")
        ) {
            avatarSrc = post.ProfilePictureUrl;
        } else {
            avatarSrc = "/" + post.ProfilePictureUrl;
        }
    }

    const fullName = post.FullName || post.Username || "Bạn";

    const newPost = document.createElement("div");
    newPost.className = "bg-white post-card mb-3";

    newPost.innerHTML = `
        <div class="p-3">
            <div class="d-flex gap-3">
                <img src="${avatarSrc}" class="avatar" alt="avatar">

                <div class="flex-grow-1">
                    <div class="fw-semibold">
                        ${escapeHTML(fullName)} • vừa xong
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

    fetch("/Controllers/PostController.php?action=comment", {
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

    fetch("/Controllers/FollowController.php?action=toggle", {
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