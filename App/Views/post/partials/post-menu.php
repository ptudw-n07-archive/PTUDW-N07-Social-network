<?php
if (!function_exists('archivePostImageList')) {
    function archivePostImageList($images): array {
        if (empty($images)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', (string) $images))));
    }
}

if (!function_exists('archivePostCardAttributes')) {
    function archivePostCardAttributes(array $post, int $currentUserId): string {
        $images = archivePostImageList($post['Images'] ?? '');

        return sprintf(
            'data-post-id="%d" data-owner-id="%d" data-is-owner="%d" data-post-content="%s" data-post-images="%s" data-post-privacy="%s"',
            (int) ($post['PostID'] ?? 0),
            (int) ($post['UserID'] ?? 0),
            (int) ((int) ($post['UserID'] ?? 0) === $currentUserId),
            htmlspecialchars((string) ($post['Content'] ?? ''), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars(json_encode($images, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) ($post['Privacy'] ?? 'public'), ENT_QUOTES, 'UTF-8')
        );
    }
}

if (!function_exists('archivePostPrivacyMeta')) {
    function archivePostPrivacyMeta($privacy): array {
        $privacy = in_array($privacy, ['public', 'followers', 'private'], true) ? $privacy : 'public';

        $labels = [
            'public' => 'Công khai',
            'followers' => 'Người theo dõi',
            'private' => 'Riêng tư'
        ];
        $icons = [
            'public' => 'bi-globe2',
            'followers' => 'bi-people',
            'private' => 'bi-lock'
        ];

        return [
            'value' => $privacy,
            'label' => $labels[$privacy],
            'icon' => $icons[$privacy]
        ];
    }
}

if (!function_exists('archiveRenderPrivacyBadge')) {
    function archiveRenderPrivacyBadge($privacy): void {
        $meta = archivePostPrivacyMeta((string) $privacy);
        ?>
        <span class="post-privacy-badge post-privacy-<?= htmlspecialchars($meta['value'], ENT_QUOTES, 'UTF-8') ?>" data-privacy-badge>
            <i class="bi <?= htmlspecialchars($meta['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
            <span><?= htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8') ?></span>
        </span>
        <?php
    }
}

if (!function_exists('archiveRenderPostMenu')) {
    function archiveRenderPostMenu(array $post, int $currentUserId): void {
        $postId = (int) ($post['PostID'] ?? 0);
        $ownerId = (int) ($post['UserID'] ?? 0);
        $isOwner = $ownerId === $currentUserId;
        ?>
        <div class="post-menu" data-post-id="<?= $postId ?>" data-owner-id="<?= $ownerId ?>">
            <button type="button" class="post-menu-toggle" aria-label="Mở menu bài viết">
                <i class="bi bi-three-dots-vertical"></i>
            </button>

            <div class="post-menu-dropdown" hidden>
                <?php if ($isOwner): ?>
                    <button type="button" class="post-menu-item" data-post-action="edit">
                        <i class="bi bi-pencil-square"></i>
                        <span>Chỉnh sửa bài viết</span>
                    </button>
                    <button type="button" class="post-menu-item text-danger" data-post-action="delete">
                        <i class="bi bi-trash3"></i>
                        <span>Xóa bài viết</span>
                    </button>
                    <button type="button" class="post-menu-item" data-post-action="privacy">
                        <i class="bi bi-shield-lock"></i>
                        <span>Quyền riêng tư</span>
                    </button>
                <?php else: ?>
                    <button type="button" class="post-menu-item" data-post-action="block">
                        <i class="bi bi-person-slash"></i>
                        <span>Chặn người dùng</span>
                    </button>
                    <button type="button" class="post-menu-item" data-post-action="report">
                        <i class="bi bi-flag"></i>
                        <span>Báo cáo bài viết</span>
                    </button>
                    <button type="button" class="post-menu-item" data-post-action="notInterested">
                        <i class="bi bi-eye-slash"></i>
                        <span>Không quan tâm</span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
?>
