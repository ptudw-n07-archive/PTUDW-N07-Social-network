<?php
namespace App\Models;

require_once __DIR__ . '/AdminStatsModel.php';
require_once __DIR__ . '/AdminReportModel.php';
require_once __DIR__ . '/AdminMemberModel.php';
require_once __DIR__ . '/AdminNotificationModel.php';
require_once __DIR__ . '/AdminContentModel.php';
require_once __DIR__ . '/AdminProfileModel.php';

use PDO;

class AdminModel {
    private PDO $conn;
    private AdminStatsModel $statsModel;
    private AdminReportModel $reportModel;
    private AdminMemberModel $memberModel;
    private AdminContentModel $contentModel;
    private AdminNotificationModel $notificationModel;
    private AdminProfileModel $profileModel;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
        $this->statsModel = new AdminStatsModel($db_connection);
        $this->reportModel = new AdminReportModel($db_connection);
        $this->memberModel = new AdminMemberModel($db_connection);
        $this->notificationModel = new AdminNotificationModel($db_connection);
        $this->contentModel = new AdminContentModel($db_connection, $this->notificationModel);
        $this->profileModel = new AdminProfileModel($db_connection);
    }

    // --- Facade giữ lại các hàm cũ để không làm gãy controller/điểm gọi hiện tại ---

    public function getOverviewStats() {
        return $this->statsModel->getOverviewStats();
    }

    public function getOverviewDetail(string $metric): ?array {
        return $this->statsModel->getOverviewDetail($metric);
    }

    public function getStatisticsTopRankings($limit = 5) {
        return $this->statsModel->getStatisticsTopRankings($limit);
    }

    public function getStatisticsChartData() {
        return $this->statsModel->getStatisticsChartData();
    }

    public function getStatisticsActivityInsights() {
        return $this->statsModel->getStatisticsActivityInsights();
    }

    public function getReportsList() {
        return $this->reportModel->getReportsList();
    }

    public function getReportById($reportId) {
        return $this->reportModel->getReportById($reportId);
    }

    public function getReportDetailById($reportId) {
        return $this->reportModel->getReportDetailById($reportId);
    }

    public function getPostImagesByPostId($postId) {
        return $this->reportModel->getPostImagesByPostId($postId);
    }

    public function markReportResolved($reportId, $adminNote = null) {
        return $this->reportModel->markReportResolved($reportId, $adminNote);
    }

    public function resolvePendingReportsByPostId($postId, $adminNote): array {
        return $this->reportModel->resolvePendingReportsByPostId($postId, $adminNote);
    }

    public function resolvePendingReportsByCommentId($commentId, $adminNote): array {
        return $this->reportModel->resolvePendingReportsByCommentId($commentId, $adminNote);
    }

    public function resolvePendingReportsByReportedUserId($userId, $adminNote): array {
        return $this->reportModel->resolvePendingReportsByReportedUserId($userId, $adminNote);
    }

    public function getPendingReportIdsByPostId($postId): array {
        return $this->reportModel->getPendingReportIdsByPostId($postId);
    }

    public function getPendingReportIdsByCommentId($commentId): array {
        return $this->reportModel->getPendingReportIdsByCommentId($commentId);
    }

    public function getPendingReportIdsByReportedUserId($userId): array {
        return $this->reportModel->getPendingReportIdsByReportedUserId($userId);
    }

    public function hidePostById($postId) {
        return $this->reportModel->hidePostById($postId);
    }

    public function hideCommentById($commentId) {
        return $this->reportModel->hideCommentById($commentId);
    }

    public function getMembersList($keyword = '', $roleId = '') {
        return $this->memberModel->getMembersList($keyword, $roleId);
    }

    public function getAllRoles() {
        return $this->memberModel->getAllRoles();
    }

    public function updateUserRole($userId, $roleId) {
        return $this->memberModel->updateUserRole($userId, $roleId);
    }

    public function updateUserActiveStatus($userId, $isActive) {
        return $this->memberModel->updateUserActiveStatus($userId, $isActive);
    }

    public function getUserById($userId) {
        return $this->memberModel->getUserById($userId);
    }

    public function getRoleById($roleId) {
        return $this->memberModel->getRoleById($roleId);
    }

    public function getAdminProfileById($userId) {
        return $this->profileModel->getAdminProfileById($userId);
    }

    public function getUserPasswordHash($userId): ?string {
        return $this->profileModel->getUserPasswordHash($userId);
    }

    public function updateAdminFullName($userId, $fullName): bool {
        return $this->profileModel->updateAdminFullName($userId, $fullName);
    }

    public function updateAdminBio($userId, $bio): bool {
        return $this->profileModel->updateAdminBio($userId, $bio);
    }

    public function updateAdminAvatar($userId, $avatarPath): bool {
        return $this->profileModel->updateAdminAvatar($userId, $avatarPath);
    }

    public function updateAdminPassword($userId, $passwordHash): bool {
        return $this->profileModel->updateAdminPassword($userId, $passwordHash);
    }

    public function addAdminLog($adminUserId, $action, $targetType, $targetId, $description): bool {
        return $this->profileModel->addAdminLog($adminUserId, $action, $targetType, $targetId, $description);
    }

    public function getAdminLogs($adminUserId, $keyword = '', $action = '', $limit = 30): array {
        return $this->profileModel->getAdminLogs($adminUserId, $keyword, $action, $limit);
    }

    public function getAdminLogActions($adminUserId): array {
        return $this->profileModel->getAdminLogActions($adminUserId);
    }

    public function getNotificationTypeIdByName($typeName) {
        return $this->notificationModel->getNotificationTypeIdByName($typeName);
    }

    public function createNotification($receiverUserId, $senderUserId, $postId, $commentId, $notificationTypeId) {
        return $this->notificationModel->createNotification($receiverUserId, $senderUserId, $postId, $commentId, $notificationTypeId);
    }

    public function createNotificationByType($receiverUserId, $senderUserId, $typeName) {
        return $this->notificationModel->createNotificationByType($receiverUserId, $senderUserId, $typeName);
    }

    public function getAdminNotifications($keyword = '', $typeName = '', $isRead = ''): array {
        return $this->notificationModel->getAdminNotifications($keyword, $typeName, $isRead);
    }

    public function getAdminNotificationDetail($notificationId) {
        return $this->notificationModel->getAdminNotificationDetail($notificationId);
    }

    public function deleteAdminNotification($notificationId): bool {
        return $this->notificationModel->deleteAdminNotification($notificationId);
    }

    public function searchNotificationReceivers($keyword = '', $limit = 20): array {
        return $this->notificationModel->searchNotificationReceivers($keyword, $limit);
    }

    public function getActiveNotificationReceiverById($userId) {
        return $this->notificationModel->getActiveNotificationReceiverById($userId);
    }

    public function createSystemNotification($receiverUserId, $senderUserId, $message): bool {
        return $this->notificationModel->createSystemNotification($receiverUserId, $senderUserId, $message);
    }

    public function createSystemNotificationsForActiveUsers($senderUserId, $message): int {
        return $this->notificationModel->createSystemNotificationsForActiveUsers($senderUserId, $message);
    }

    public function getAdminContentPosts($keyword = '', $status = '', $privacy = '') {
        return $this->contentModel->getAdminContentPosts($keyword, $status, $privacy);
    }

    public function getAdminContentPostDetail($postId) {
        return $this->contentModel->getAdminContentPostDetail($postId);
    }

    public function getPostOwnerAndStatus($postId) {
        return $this->contentModel->getPostOwnerAndStatus($postId);
    }

    public function updatePostHiddenStatus($postId, $isHidden, $adminUserId = null) {
        return $this->contentModel->updatePostHiddenStatus($postId, $isHidden, $adminUserId);
    }

    public function deleteAdminContentPost($postId, $adminUserId = null) {
        return $this->contentModel->deleteAdminContentPost($postId, $adminUserId);
    }

    public function getAdminContentComments($keyword = '', $status = '') {
        return $this->contentModel->getAdminContentComments($keyword, $status);
    }

    public function getAdminContentCommentDetail($commentId) {
        return $this->contentModel->getAdminContentCommentDetail($commentId);
    }

    public function getCommentOwnerAndStatus($commentId) {
        return $this->contentModel->getCommentOwnerAndStatus($commentId);
    }

    public function updateCommentHiddenStatus($commentId, $isHidden, $adminUserId = null) {
        return $this->contentModel->updateCommentHiddenStatus($commentId, $isHidden, $adminUserId);
    }

    public function deleteAdminContentComment($commentId, $adminUserId = null) {
        return $this->contentModel->deleteAdminContentComment($commentId, $adminUserId);
    }

    public function getAdminContentHashtags($keyword = '', $status = '') {
        return $this->contentModel->getAdminContentHashtags($keyword, $status);
    }

    public function getHashtagById($hashtagId) {
        return $this->contentModel->getHashtagById($hashtagId);
    }

    public function updateHashtagHiddenStatus($hashtagId, $isHidden) {
        return $this->contentModel->updateHashtagHiddenStatus($hashtagId, $isHidden);
    }

    public function deleteAdminContentHashtag($hashtagId) {
        return $this->contentModel->deleteAdminContentHashtag($hashtagId);
    }
}
?>
