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