document.addEventListener("DOMContentLoaded", function () {
    const APP_BASE_URL = window.APP_BASE_URL || `${window.location.origin}/`;
    const appUrl = path => APP_BASE_URL + String(path || "").replace(/^\/+/, "");
    const config = window.SEARCH_CONFIG || {};
    const searchUrl = config.searchUrl || appUrl("App/Controllers/SearchController.php");
    const followUrl = config.followUrl || appUrl("App/Controllers/FollowController.php?action=toggle");
    const baseUrl = config.baseUrl || APP_BASE_URL;

    const form = document.getElementById("searchForm");
    const input = document.getElementById("searchInput");
    const statusBox = document.getElementById("searchStatus");
    const historySection = document.getElementById("historySection");
    const historyList = document.getElementById("historyList");
    const clearHistoryBtn = document.getElementById("clearHistoryBtn");
    const userSection = document.getElementById("userSection");
    const userResults = document.getElementById("userResults");
    const hashtagSection = document.getElementById("hashtagSection");
    const hashtagResults = document.getElementById("hashtagResults");
    const postSection = document.getElementById("postSection");
    const postResults = document.getElementById("postResults");

    let debounceTimer = null;
    let lastKeyword = "";

    if (!form || !input) {
        return;
    }

    input.addEventListener("focus", function () {
        if (input.value.trim() === "") {
            loadHistory();
        }
    });

    input.addEventListener("input", function () {
        const keyword = input.value.trim();
        window.clearTimeout(debounceTimer);

        debounceTimer = window.setTimeout(function () {
            if (keyword.length >= 2) {
                runSearch(keyword);
                return;
            }

            clearResults();
            if (keyword.length === 0) {
                loadHistory();
            } else {
                showStatus("Nhập thêm ký tự để tìm kiếm.");
            }
        }, 300);
    });

    form.addEventListener("submit", function (event) {
        event.preventDefault();
        const keyword = input.value.trim();

        if (keyword.length < 2) {
            showStatus("Từ khóa cần ít nhất 2 ký tự.");
            return;
        }

        recordHistory(keyword);
        runSearch(keyword);
    });

    clearHistoryBtn.addEventListener("click", function () {
        const formData = new FormData();

        fetch(searchUrl + "?action=clear", {
            method: "POST",
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    showStatus(data.message || "Không thể xóa lịch sử.");
                    return;
                }

                historyList.innerHTML = "";
                clearHistoryBtn.classList.add("d-none");
                showStatus("Đã xóa lịch sử tìm kiếm.");
            })
            .catch(() => showStatus("Có lỗi khi xóa lịch sử."));
    });

    function runSearch(keyword) {
        lastKeyword = keyword;
        hideHistory();
        showStatus("Đang tìm kiếm...");

        fetch(searchUrl + "?action=search&q=" + encodeURIComponent(keyword))
            .then(response => response.json())
            .then(data => {
                if (keyword !== lastKeyword) {
                    return;
                }

                if (!data.success) {
                    clearResults();
                    showStatus(data.message || "Không thể tìm kiếm.");
                    return;
                }

                renderUsers(data.users || [], keyword);
                renderHashtags(data.hashtags || [], keyword);
                renderPosts(data.posts || [], keyword);

                const total = (data.users || []).length + (data.hashtags || []).length + (data.posts || []).length;
                showStatus(total > 0 ? "" : "Không tìm thấy kết quả phù hợp.");
            })
            .catch(() => {
                clearResults();
                showStatus("Có lỗi khi tìm kiếm.");
            });
    }

    function loadHistory() {
        clearResults();
        showStatus("Đang tải lịch sử...");

        fetch(searchUrl + "?action=history")
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    hideHistory();
                    showStatus(data.message || "Không thể tải lịch sử.");
                    return;
                }

                renderHistory(data.history || []);
            })
            .catch(() => {
                hideHistory();
                showStatus("Có lỗi khi tải lịch sử.");
            });
    }

    function renderHistory(items) {
        historyList.innerHTML = "";
        historySection.classList.remove("d-none");
        clearHistoryBtn.classList.toggle("d-none", items.length === 0);

        if (items.length === 0) {
            showStatus("Chưa có lịch sử tìm kiếm.");
            return;
        }

        showStatus("");

        items.forEach(item => {
            const row = document.createElement("div");
            row.className = "search-history-item";
            row.dataset.searchId = item.SearchID;

            const keyword = item.Keyword || "";
            row.innerHTML = `
                <button type="button" class="search-history-keyword">
                    <i class="bi bi-clock-history"></i>
                    <span>${escapeHTML(keyword)}</span>
                </button>
                <button type="button" class="search-history-delete" aria-label="Xóa lịch sử">
                    <i class="bi bi-x-lg"></i>
                </button>
            `;

            row.querySelector(".search-history-keyword").addEventListener("click", function () {
                input.value = keyword;
                recordHistory(keyword);
                runSearch(keyword);
            });

            row.querySelector(".search-history-delete").addEventListener("click", function (event) {
                event.stopPropagation();
                deleteHistoryItem(row, item.SearchID);
            });

            historyList.appendChild(row);
        });
    }

    function renderUsers(users, keyword) {
        userResults.innerHTML = "";
        userSection.classList.toggle("d-none", users.length === 0);

        users.forEach(user => {
            const profileHref = baseUrl + "profile?id=" + encodeURIComponent(user.UserID);
            const fullName = user.FullName || (user.Username ? `@${user.Username}` : "Người dùng");
            const username = user.Username || "";
            const bio = user.Bio || "Chưa cập nhật bio.";
            const avatar = normalizeImagePath(user.ProfilePictureUrl);
            const isFollowing = Number(user.IsFollowing) === 1;

            const row = document.createElement("div");
            row.className = "search-user-item";
            row.innerHTML = `
                <a href="${profileHref}" class="search-user-link">
                    <img src="${escapeHTML(avatar)}" class="avatar" alt="avatar" onerror="this.src='${baseUrl}Public/assets/img/default-avatar.jpg';">
                    <span class="search-user-meta">
                        <strong>${highlight(fullName, keyword)}</strong>
                        <small>@${highlight(username, keyword)}</small>
                        <span>${escapeHTML(truncate(bio, 96))}</span>
                    </span>
                </a>
                <button type="button" class="btn btn-sm ${isFollowing ? "btn-secondary" : "btn-pink"} search-follow-btn" data-user-id="${user.UserID}">
                    ${isFollowing ? "Đang theo dõi" : "Theo dõi"}
                </button>
            `;

            row.querySelector(".search-user-link").addEventListener("click", function (event) {
                event.preventDefault();
                recordHistory(input.value.trim()).finally(function () {
                    window.location.href = profileHref;
                });
            });

            row.querySelector(".search-follow-btn").addEventListener("click", function () {
                toggleFollow(this);
            });

            userResults.appendChild(row);
        });
    }

    function renderHashtags(hashtags, keyword) {
        hashtagResults.innerHTML = "";
        hashtagSection.classList.toggle("d-none", hashtags.length === 0);

        hashtags.forEach(item => {
            const tag = String(item.tag || "").replace(/^#/, "");
            const row = document.createElement("a");
            row.href = baseUrl + "hashtag?tag=" + encodeURIComponent(tag);
            row.className = "search-hashtag-item";
            row.innerHTML = `
                <span class="search-hashtag-icon"><i class="bi bi-hash"></i></span>
                <span>
                    <strong>${highlight(item.tag || "", keyword)}</strong>
                    <small>${Number(item.count || 0)} bài viết liên quan</small>
                </span>
            `;

            row.addEventListener("click", function () {
                recordHistory("#" + tag);
            });

            hashtagResults.appendChild(row);
        });
    }

    function renderPosts(posts, keyword) {
        postResults.innerHTML = "";
        postSection.classList.toggle("d-none", posts.length === 0);

        posts.forEach(post => {
            const profileHref = baseUrl + "profile?id=" + encodeURIComponent(post.UserID);
            const avatar = normalizeImagePath(post.ProfilePictureUrl);
            const fullName = post.FullName || (post.Username ? `@${post.Username}` : "Người dùng");

            const row = document.createElement("a");
            row.href = profileHref;
            row.className = "search-post-item";
            row.innerHTML = `
                <img src="${escapeHTML(avatar)}" class="avatar" alt="avatar" onerror="this.src='${baseUrl}Public/assets/img/default-avatar.jpg';">
                <span class="search-post-meta">
                    <strong>${escapeHTML(fullName)} <small>@${escapeHTML(post.Username || "")}</small></strong>
                    <span>${highlight(truncate(post.Content || "", 180), keyword)}</span>
                </span>
            `;

            row.addEventListener("click", function (event) {
                event.preventDefault();
                recordHistory(input.value.trim()).finally(function () {
                    window.location.href = profileHref;
                });
            });

            postResults.appendChild(row);
        });
    }

    function recordHistory(keyword) {
        keyword = String(keyword || "").trim();

        if (keyword.length < 2) {
            return Promise.resolve();
        }

        const formData = new FormData();
        formData.append("keyword", keyword);

        return fetch(searchUrl + "?action=record", {
            method: "POST",
            body: formData,
            keepalive: true
        }).catch(() => {});
    }

    function deleteHistoryItem(row, searchId) {
        const formData = new FormData();
        formData.append("searchId", searchId);

        fetch(searchUrl + "?action=delete", {
            method: "POST",
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    showStatus(data.message || "Không thể xóa mục này.");
                    return;
                }

                row.classList.add("is-removing");
                window.setTimeout(function () {
                    row.remove();

                    if (historyList.children.length === 0) {
                        clearHistoryBtn.classList.add("d-none");
                        showStatus("Chưa có lịch sử tìm kiếm.");
                    }
                }, 180);
            })
            .catch(() => showStatus("Có lỗi khi xóa lịch sử."));
    }

    function toggleFollow(btn) {
        const userId = btn.dataset.userId;

        if (!userId) {
            return;
        }

        const formData = new FormData();
        formData.append("userId", userId);
        btn.disabled = true;

        fetch(followUrl, {
            method: "POST",
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    showStatus(data.message || "Không thể xử lý theo dõi.");
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
            .catch(() => showStatus("Có lỗi khi theo dõi."))
            .finally(() => {
                btn.disabled = false;
            });
    }

    function clearResults() {
        userResults.innerHTML = "";
        hashtagResults.innerHTML = "";
        postResults.innerHTML = "";
        userSection.classList.add("d-none");
        hashtagSection.classList.add("d-none");
        postSection.classList.add("d-none");
    }

    function hideHistory() {
        historySection.classList.add("d-none");
        historyList.innerHTML = "";
    }

    function showStatus(message) {
        statusBox.textContent = message;
        statusBox.classList.toggle("d-none", message === "");
    }

    function normalizeImagePath(path) {
        if (!path) {
            return baseUrl + "Public/assets/img/default-avatar.jpg";
        }

        path = String(path).trim();

        if (path.startsWith("http://") || path.startsWith("https://")) {
            return path;
        }

        const cleanPath = path.replace(/^\/+/, "");

        if (cleanPath.startsWith("Public/")) {
            return baseUrl + cleanPath;
        }

        if (cleanPath.startsWith("uploads/") || cleanPath.startsWith("assets/")) {
            return baseUrl + "Public/" + cleanPath;
        }

        return baseUrl + cleanPath;
    }

    function highlight(text, keyword) {
        text = String(text || "");
        keyword = String(keyword || "").replace(/^#/, "");

        if (keyword === "") {
            return escapeHTML(text);
        }

        const escapedText = escapeHTML(text);
        const safeKeyword = keyword.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
        const regex = new RegExp("(" + safeKeyword + ")", "ig");

        return escapedText.replace(regex, "<mark>$1</mark>");
    }

    function truncate(text, length) {
        text = String(text || "").trim();

        if (text.length <= length) {
            return text;
        }

        return text.slice(0, length - 1).trim() + "…";
    }

    function escapeHTML(text) {
        const div = document.createElement("div");
        div.innerText = String(text || "");
        return div.innerHTML;
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
});
