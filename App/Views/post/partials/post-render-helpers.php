<?php
if (!function_exists('archiveAssetPath')) {
    function archiveAssetPath($path, $default = ''): string {
        $path = trim((string) $path);

        if ($path === '') {
            return $default;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'Public/')) {
            return BASE_URL . $path;
        }

        if (str_starts_with($path, 'uploads/') || str_starts_with($path, 'assets/')) {
            return BASE_URL . 'Public/' . $path;
        }

        if (!str_contains($path, '/')) {
            return BASE_URL . 'Public/uploads/avatars/' . basename($path);
        }

        return BASE_URL . $path;
    }
}

if (!function_exists('archivePublicLocalPath')) {
    function archivePublicLocalPath($path): string {
        return __DIR__ . '/../../../../' . ltrim((string) $path, '/');
    }
}

if (!function_exists('archiveImagePath')) {
    function archiveImagePath($path): string {
        return archiveAssetPath($path, BASE_URL . 'Public/assets/img/default-avatar.jpg');
    }
}

if (!function_exists('archivePostMediaPath')) {
    function archivePostMediaPath($path): string {
        $path = trim((string) $path);

        if ($path === '') {
            return '';
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $cleanPath = ltrim($path, '/');
        $extension = strtolower(pathinfo(parse_url($cleanPath, PHP_URL_PATH) ?: $cleanPath, PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'heif', 'mp4', 'mov', 'webm'], true)) {
            return '';
        }

        if (str_starts_with($cleanPath, 'Public/')) {
            $localPath = archivePublicLocalPath($cleanPath);
            return is_file($localPath) ? app_url($cleanPath) : '';
        }

        if (str_starts_with($cleanPath, 'uploads/') || str_starts_with($cleanPath, 'assets/')) {
            $publicPath = 'Public/' . $cleanPath;
            $localPath = archivePublicLocalPath($publicPath);
            return is_file($localPath) ? app_url($publicPath) : '';
        }

        $publicPath = 'Public/uploads/posts/' . basename($cleanPath);
        $localPath = archivePublicLocalPath($publicPath);
        return is_file($localPath) ? app_url($publicPath) : '';
    }
}

if (!function_exists('archivePostMediaType')) {
    function archivePostMediaType($path): string {
        $extension = strtolower(pathinfo(parse_url((string) $path, PHP_URL_PATH) ?: (string) $path, PATHINFO_EXTENSION));

        if (in_array($extension, ['mp4', 'mov', 'webm'], true)) {
            return 'video';
        }

        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            return 'image';
        }

        if (in_array($extension, ['heic', 'heif'], true)) {
            return 'unsupported-image';
        }

        return 'file';
    }
}

if (!function_exists('archivePostMediaMimeType')) {
    function archivePostMediaMimeType($path): string {
        $extension = strtolower(pathinfo(parse_url((string) $path, PHP_URL_PATH) ?: (string) $path, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'heic' => 'image/heic',
            'heif' => 'image/heif',
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime',
            'webm' => 'video/webm',
            default => 'application/octet-stream'
        };
    }
}

if (!function_exists('archiveTimeAgo')) {
    function archiveTimeAgo($datetime, bool $detailFormat = false): string {
        if ($detailFormat && empty($datetime)) {
            return '';
        }

        $timestamp = strtotime((string) $datetime);
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return 'vừa xong';
        }

        if ($diff < 3600) {
            return floor($diff / 60) . ($detailFormat ? ' phút trước' : ' phút');
        }

        if ($diff < 86400) {
            return floor($diff / 3600) . ($detailFormat ? ' giờ trước' : ' giờ');
        }

        if ($detailFormat && $diff < 604800) {
            return floor($diff / 86400) . ' ngày trước';
        }

        return $detailFormat ? date('d/m/Y H:i', $timestamp) : date('d/m/Y', $timestamp);
    }
}

if (!function_exists('archiveProfileUrl')) {
    function archiveProfileUrl($userId): string {
        return BASE_URL . 'App/Views/profile/profile.php?id=' . urlencode((string) $userId);
    }
}

if (!function_exists('archivePostDetailUrl')) {
    function archivePostDetailUrl($postId): string {
        return BASE_URL . 'App/Views/post/post-detail.php?id=' . urlencode((string) $postId);
    }
}

if (!function_exists('archiveHashtagUrl')) {
    function archiveHashtagUrl($tag): string {
        return BASE_URL . 'App/Views/hashtags/hashtag.php?tag=' . urlencode((string) $tag);
    }
}

if (!function_exists('archivePrivacyLabel')) {
    function archivePrivacyLabel($privacy): string {
        return match ($privacy) {
            'followers' => 'Người theo dõi',
            'private' => 'Riêng tư',
            default => 'Công khai'
        };
    }
}

if (!function_exists('archivePrivacyIcon')) {
    function archivePrivacyIcon($privacy): string {
        return match ($privacy) {
            'followers' => 'bi-people',
            'private' => 'bi-lock',
            default => 'bi-globe2'
        };
    }
}

if (!function_exists('archiveRenderPrivacyBadge')) {
    function archiveRenderPrivacyBadge($privacy): string {
        $privacy = in_array($privacy, ['public', 'followers', 'private'], true) ? $privacy : 'public';

        return '<span class="post-privacy-badge post-privacy-' . htmlspecialchars($privacy, ENT_QUOTES, 'UTF-8') . '" data-privacy-badge>'
            . '<i class="bi ' . htmlspecialchars(archivePrivacyIcon($privacy), ENT_QUOTES, 'UTF-8') . '"></i>'
            . '<span>' . htmlspecialchars(archivePrivacyLabel($privacy), ENT_QUOTES, 'UTF-8') . '</span>'
            . '</span>';
    }
}

if (!function_exists('archiveRenderPostContentWithHashtags')) {
    function archiveRenderPostContentWithHashtags($content): string {
        $parts = preg_split('/(#[\p{L}\p{N}_]+)/u', (string) $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        $html = '';

        foreach ($parts as $part) {
            if (preg_match('/^#([\p{L}\p{N}_]+)$/u', $part, $matches)) {
                $tag = $matches[1];
                $html .= '<a class="hashtag-link" href="' . htmlspecialchars(archiveHashtagUrl($tag), ENT_QUOTES, 'UTF-8') . '">#' . htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') . '</a>';
                continue;
            }

            $html .= nl2br(htmlspecialchars($part, ENT_QUOTES, 'UTF-8'));
        }

        return $html;
    }
}

if (!function_exists('archiveParseRepostContent')) {
    function archiveParseRepostContent($content): ?array {
        if (!preg_match('/^Đăng lại từ\s+(@[^\s:]+):\s*(.*)$/su', (string) $content, $matches)) {
            return null;
        }

        $source = trim($matches[1]);
        $nestedContent = ltrim((string) ($matches[2] ?? ''));

        while (preg_match('/^Đăng lại từ\s+(@[^\s:]+):\s*(.*)$/su', $nestedContent, $nestedMatches)) {
            $source = trim($nestedMatches[1]);
            $nestedContent = ltrim((string) ($nestedMatches[2] ?? ''));
        }

        return [
            'source' => $source,
            'content' => $nestedContent
        ];
    }
}

if (!function_exists('archiveRenderPostMediaList')) {
    function archiveRenderPostMediaList($images, string $wrapperClass = 'post-media-list'): string {
        $imageItems = array_values(array_filter(array_map('trim', explode(',', (string) $images))));

        if (empty($imageItems)) {
            return '';
        }

        $mediaItems = [];

        foreach ($imageItems as $img) {
            $mediaSrc = archivePostMediaPath($img);
            if ($mediaSrc === '') {
                continue;
            }

            $mediaItems[] = [
                'path' => $img,
                'src' => $mediaSrc,
                'type' => archivePostMediaType($img)
            ];
        }

        if (empty($mediaItems)) {
            return '';
        }

        $totalItems = count($mediaItems);
        $isCarousel = $totalItems > 1;
        $classes = trim($wrapperClass . ' post-media-scroll media-count-' . min($totalItems, 4) . ' media-total-' . $totalItems . ($isCarousel ? ' has-multiple-media' : ' has-single-media'));
        $html = '<div class="' . htmlspecialchars($classes, ENT_QUOTES, 'UTF-8') . '">';

        if ($isCarousel) {
            $html .= '<button type="button" class="post-media-nav post-media-prev no-post-nav" onclick="scrollPostMedia(this, -1)" aria-label="Ảnh trước"><i class="bi bi-chevron-left"></i></button>';
        }

        $html .= '<div class="post-media-track">';

        foreach ($mediaItems as $media) {
            $html .= '<div class="post-media-slide">';

            if ($media['type'] === 'video') {
                $html .= '<video controls class="post-media-video no-post-nav">'
                    . '<source src="' . htmlspecialchars($media['src'], ENT_QUOTES, 'UTF-8') . '" type="' . htmlspecialchars(archivePostMediaMimeType($media['path']), ENT_QUOTES, 'UTF-8') . '">'
                    . 'Trình duyệt không hỗ trợ video này.'
                    . '</video>';
                $html .= '</div>';
                continue;
            }

            if ($media['type'] === 'image') {
                $html .= '<img src="' . htmlspecialchars($media['src'], ENT_QUOTES, 'UTF-8') . '" class="post-media-image" alt="post image" loading="lazy" onerror="this.style.display=\'none\';">';
                $html .= '</div>';
                continue;
            }

            $html .= '<a href="' . htmlspecialchars($media['src'], ENT_QUOTES, 'UTF-8') . '" target="_blank" class="post-media-file no-post-nav">Mở file ảnh</a>';
            $html .= '</div>';
        }

        $html .= '</div>';

        if ($isCarousel) {
            $html .= '<button type="button" class="post-media-nav post-media-next no-post-nav" onclick="scrollPostMedia(this, 1)" aria-label="Ảnh tiếp theo"><i class="bi bi-chevron-right"></i></button>';
            $html .= '<span class="post-media-counter">' . $totalItems . ' ảnh</span>';
        }

        return $html . '</div>';
    }
}

if (!function_exists('archiveRenderRepostEmbed')) {
    function archiveRenderRepostEmbed(array $post): string {
        $repost = archiveParseRepostContent($post['Content'] ?? '');

        if (!$repost && empty($post['OriginalPostID'])) {
            return '';
        }

        $originalContent = trim((string) ($post['OriginalContent'] ?? ''));
        $fallbackContent = $repost['content'] ?? '';
        $displayContent = $originalContent !== '' ? $originalContent : $fallbackContent;
        $nestedRepost = archiveParseRepostContent($displayContent);
        if ($nestedRepost) {
            $displayContent = $nestedRepost['content'];
        }

        $sourceName = trim((string) ($post['OriginalFullName'] ?? ''));
        if ($sourceName === '') {
            $sourceUsername = trim((string) ($post['OriginalUsername'] ?? ''));
            $sourceName = $sourceUsername !== '' ? '@' . $sourceUsername : ($repost['source'] ?? '@nguoi-dung');
        }

        $sourceAvatar = archiveImagePath($post['OriginalProfilePictureUrl'] ?? '');
        $mediaList = !empty($post['OriginalImages']) ? $post['OriginalImages'] : ($post['Images'] ?? '');
        $contentHtml = trim($displayContent) !== ''
            ? archiveRenderPostContentWithHashtags($displayContent)
            : '<span class="text-muted">Bài viết gốc không có nội dung văn bản.</span>';

        return '<div class="repost-source-label"><i class="bi bi-arrow-repeat"></i><span>Đăng lại bài viết</span></div>'
            . '<div class="repost-embed no-post-nav">'
            . '<div class="repost-embed-header">'
            . '<div class="repost-embed-author"><img src="' . htmlspecialchars($sourceAvatar, ENT_QUOTES, 'UTF-8') . '" class="repost-embed-avatar" alt="avatar" onerror="this.src=\'' . BASE_URL . 'Public/assets/img/default-avatar.jpg\';"><span>' . htmlspecialchars($sourceName, ENT_QUOTES, 'UTF-8') . '</span></div>'
            . '<div class="repost-embed-meta">Bài viết gốc</div>'
            . '</div>'
            . '<div class="repost-embed-content post-text">' . $contentHtml . '</div>'
            . archiveRenderPostMediaList($mediaList, 'repost-embed-media')
            . '</div>';
    }
}

if (!function_exists('archiveRenderComment')) {
    function archiveRenderComment(array $comment, array $post, int $currentUserId, bool $isReply = false, int $highlightCommentId = 0, bool $detailFormat = false): void {
        $commentId = (int) ($comment['CommentID'] ?? 0);
        $commentOwnerId = (int) ($comment['UserID'] ?? 0);
        $postOwnerId = (int) ($post['UserID'] ?? 0);
        $parentCommentId = !empty($comment['ParentCommentID']) ? (int) $comment['ParentCommentID'] : 0;
        $rootCommentId = $isReply && $parentCommentId > 0 ? $parentCommentId : $commentId;
        $canEdit = $commentOwnerId === $currentUserId;
        $canDelete = $canEdit || $postOwnerId === $currentUserId;
        $canReport = $commentOwnerId !== $currentUserId;
        $displayName = !empty($comment['FullName']) ? $comment['FullName'] : '@' . ($comment['Username'] ?? '');
        ?>
        <div
            class="comment-item<?= $isReply ? ' comment-reply' : '' ?><?= $commentId === $highlightCommentId ? ' highlight' : '' ?>"
            id="comment-<?= $commentId ?>"
            data-comment-id="<?= $commentId ?>"
            data-post-id="<?= (int) ($post['PostID'] ?? 0) ?>"
            data-owner-id="<?= $commentOwnerId ?>"
            data-parent-comment-id="<?= $parentCommentId ?>"
            data-root-comment-id="<?= $rootCommentId ?>"
            data-can-edit="<?= $canEdit ? '1' : '0' ?>"
            data-can-delete="<?= $canDelete ? '1' : '0' ?>"
            data-can-report="<?= $canReport ? '1' : '0' ?>"
        >
            <img
                src="<?= htmlspecialchars(archiveImagePath($comment['ProfilePictureUrl'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                class="comment-avatar"
                alt="avatar"
                onerror="this.src='<?= BASE_URL ?>Public/assets/img/default-avatar.jpg';"
            >
            <div class="comment-body">
                <div class="comment-bubble">
                    <div class="comment-meta">
                        <strong class="comment-author"><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></strong>
                        <span class="comment-time">• <?= htmlspecialchars(archiveTimeAgo($comment['CreatedAt'] ?? '', $detailFormat), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="comment-content"><?= htmlspecialchars($comment['Content'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <div class="comment-actions">
                    <?php if (!$isReply): ?>
                        <button type="button" class="comment-action-btn" onclick="showReplyForm(this)">Trả lời</button>
                    <?php endif; ?>
                    <?php if ($canEdit): ?>
                        <button type="button" class="comment-action-btn" onclick="showEditCommentForm(this)">Sửa</button>
                    <?php endif; ?>
                    <?php if ($canDelete): ?>
                        <button type="button" class="comment-action-btn comment-delete-action" onclick="deleteComment(this)">Xóa</button>
                    <?php endif; ?>
                    <?php if ($canReport): ?>
                        <button type="button" class="comment-action-btn" onclick="showReportCommentForm(this)">Báo cáo</button>
                    <?php endif; ?>
                </div>
                <div class="comment-inline-form"></div>
            </div>
        </div>
        <?php
    }
}
?>
