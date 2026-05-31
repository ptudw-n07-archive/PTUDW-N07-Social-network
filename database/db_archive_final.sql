-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 31, 2026 at 08:37 PM
-- Server version: 10.4.32-MariaDB-log
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_archive`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `LogID` int(11) NOT NULL,
  `AdminUserID` int(11) NOT NULL,
  `Action` varchar(100) NOT NULL,
  `TargetType` varchar(50) DEFAULT NULL,
  `TargetID` int(11) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`LogID`, `AdminUserID`, `Action`, `TargetType`, `TargetID`, `Description`, `CreatedAt`) VALUES
(12, 13, 'LockUser', 'User', 27, 'Khóa user #27.', '2026-06-01 01:35:57'),
(13, 13, 'UnlockUser', 'User', 27, 'Mở khóa user #27.', '2026-06-01 01:35:59'),
(14, 13, 'ProcessReport', 'Report', 29, 'Xử lý report #29 với action warn.', '2026-06-01 01:36:39');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `CommentID` int(11) NOT NULL,
  `PostID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Content` longtext NOT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `ParentCommentID` int(11) DEFAULT NULL,
  `IsHidden` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`CommentID`, `PostID`, `UserID`, `Content`, `CreatedAt`, `ParentCommentID`, `IsHidden`) VALUES
(67, 88, 14, 'Tuyệt vời. Nhờ môn học này mà mình mới biết rõ về mô hình MVC', '2026-06-01 00:54:59', NULL, 0),
(68, 95, 14, 'Bài viết này khá hữu ích, nhất là phần liên quan đến học tập và định hướng nghề nghiệp.', '2026-06-01 01:25:26', NULL, 0),
(69, 94, 17, 'Mình cũng đang quan tâm chủ đề này, cảm ơn bạn đã chia sẻ.', '2026-06-01 01:25:26', NULL, 0),
(70, 96, 27, 'Nội dung nhìn giống một bài chia sẻ thật trên mạng xã hội, khá tự nhiên.', '2026-06-01 01:25:26', NULL, 0),
(71, 99, 34, 'Mình thích cách trình bày ngắn gọn như vậy.', '2026-06-01 01:25:26', NULL, 0),
(72, 97, 43, 'Đọc xong thấy có động lực học tiếp môn này hơn.', '2026-06-01 01:25:26', NULL, 0),
(73, 96, 44, 'Câu quote này hợp với giai đoạn chạy deadline thật sự.', '2026-06-01 01:25:26', NULL, 0),
(74, 83, 45, 'Thông tin này phù hợp để thảo luận trong nhóm học tập.', '2026-06-01 01:25:26', NULL, 0),
(75, 101, 46, 'Mình nghĩ chủ đề này có thể mở rộng thêm bằng ví dụ thực tế.', '2026-06-01 01:25:26', NULL, 0),
(76, 100, 47, 'Bài này làm feed nhìn sinh động hơn nhiều.', '2026-06-01 01:25:26', NULL, 0),
(77, 85, 14, 'Đúng rồi, có nhiều dạng nội dung thì hệ thống giống mạng xã hội hơn.', '2026-06-01 01:25:26', NULL, 0),
(78, 84, 17, 'Chúc mừng nhóm đã hoàn thành milestone quan trọng nha.', '2026-06-01 01:25:26', NULL, 0),
(79, 83, 27, 'Hard work pays off thật, nhìn kết quả là thấy xứng đáng.', '2026-06-01 01:25:26', NULL, 0),
(80, 83, 34, 'Môn này khó nhưng làm xong thì học được rất nhiều thứ.', '2026-06-01 01:25:26', NULL, 0),
(81, 83, 43, 'Mình đồng ý, đặc biệt là phần kết nối database và deploy.', '2026-06-01 01:25:26', NULL, 0),
(82, 84, 44, 'Deploy lên Railway xong cảm giác project thật hơn hẳn.', '2026-06-01 01:25:26', NULL, 0),
(83, 84, 45, 'Có thể thêm hướng dẫn deploy vào báo cáo luôn đó.', '2026-06-01 01:25:26', NULL, 0),
(84, 85, 46, 'Bài này tương tác tốt ghê, đúng chất hashtag Viral.', '2026-06-01 01:25:26', NULL, 0),
(85, 85, 47, 'Nội dung gần gũi nên dễ nhận được phản hồi hơn.', '2026-06-01 01:25:26', NULL, 0),
(86, 86, 14, 'Cuối kỳ đúng là nhiều việc nhưng hoàn thành được thì rất vui.', '2026-06-01 01:25:26', NULL, 0),
(87, 86, 17, 'RewardingSubject nghe hợp với môn này thật.', '2026-06-01 01:25:26', NULL, 0),
(88, 87, 27, 'Mình thấy nội dung về công nghệ và dữ liệu rất hợp với sinh viên BIT.', '2026-06-01 01:25:26', NULL, 0),
(89, 88, 34, 'Quote này nên lưu lại để nhắc bản thân cố gắng mỗi ngày.', '2026-06-01 01:25:26', NULL, 0),
(90, 89, 43, 'Một bài đăng nhẹ nhàng nhưng vẫn có giá trị chia sẻ.', '2026-06-01 01:25:26', NULL, 0),
(91, 90, 44, 'Hashtag trong bài giúp tìm kiếm nội dung dễ hơn.', '2026-06-01 01:25:26', NULL, 0),
(92, 91, 45, 'Nội dung này có thể dùng làm ví dụ cho chức năng tìm kiếm post.', '2026-06-01 01:25:26', NULL, 0),
(93, 92, 46, 'Mình thích các bài viết về kinh nghiệm học tập như thế này.', '2026-06-01 01:25:26', NULL, 0),
(94, 93, 47, 'Bài này phù hợp để demo phần comment và notification.', '2026-06-01 01:25:26', NULL, 0),
(95, 94, 14, 'Có thêm nhiều bài kiểu này thì feed sẽ tự nhiên hơn.', '2026-06-01 01:25:26', NULL, 0),
(96, 95, 17, 'Thông tin khá thực tế, nhất là với sinh viên đang làm đồ án.', '2026-06-01 01:25:26', NULL, 0),
(97, 96, 27, 'Cảm giác hệ thống đã có dữ liệu demo ổn hơn nhiều rồi.', '2026-06-01 01:25:26', NULL, 0),
(98, 97, 34, 'Mình nghĩ nên giữ các post này trong database nộp thầy.', '2026-06-01 01:25:26', NULL, 0),
(99, 98, 43, 'Bài này đọc ổn, không bị giống dữ liệu test.', '2026-06-01 01:25:26', NULL, 0),
(100, 99, 44, 'Có like và comment qua lại thì dashboard thống kê cũng đẹp hơn.', '2026-06-01 01:25:26', NULL, 0),
(101, 100, 45, 'Phần seed data này giúp thầy dễ hình dung hệ thống hơn.', '2026-06-01 01:25:26', NULL, 0),
(102, 101, 46, 'Nhìn feed có tương tác như vậy sẽ giống một mạng xã hội thật hơn.', '2026-06-01 01:25:26', NULL, 0),
(131, 90, 14, 'Yessirrr', '2026-06-01 01:26:27', NULL, 0),
(132, 89, 14, 'Cố lên tôi ơi sắp xong rồi', '2026-06-01 01:26:41', NULL, 0),
(133, 104, 14, 'đúng đúng', '2026-06-01 01:28:02', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `follows`
--

CREATE TABLE `follows` (
  `FollowerID` int(11) NOT NULL,
  `FollowedID` int(11) NOT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `follows`
--

INSERT INTO `follows` (`FollowerID`, `FollowedID`, `CreatedAt`) VALUES
(14, 44, '2026-06-01 01:18:02'),
(14, 46, '2026-06-01 01:18:01'),
(14, 50, '2026-06-01 01:18:00'),
(17, 13, '2026-05-27 17:41:05'),
(17, 14, '2026-05-27 20:32:00');

-- --------------------------------------------------------

--
-- Table structure for table `hashtags`
--

CREATE TABLE `hashtags` (
  `HashtagID` int(11) NOT NULL,
  `HashtagName` varchar(100) NOT NULL,
  `UsageCount` int(11) DEFAULT 0,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `IsHidden` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hashtags`
--

INSERT INTO `hashtags` (`HashtagID`, `HashtagName`, `UsageCount`, `CreatedAt`, `IsHidden`) VALUES
(27, 'Viral', 3, '2026-05-27 14:54:35', 0),
(28, 'BIT', 1, '2026-05-27 16:12:34', 0),
(29, 'hardworkpaysoff', 1, '2026-05-27 16:12:34', 0),
(30, 'RewardingSubject', 1, '2026-05-27 16:12:34', 0),
(39, 'Archive', 0, '2026-06-01 00:25:48', 0),
(40, 'UEH', 0, '2026-06-01 00:25:48', 0),
(41, 'PTUDWeb', 0, '2026-06-01 00:25:48', 0),
(42, 'SocialNetwork', 0, '2026-06-01 00:25:48', 0),
(43, 'StudentLife', 0, '2026-06-01 00:25:48', 0),
(44, 'Technology', 0, '2026-06-01 00:25:48', 0),
(45, 'ManifestARCHIVE10', 1, '2026-06-01 00:57:41', 0),
(46, 'manifestARCHIVE10đ', 1, '2026-06-01 00:57:48', 0);

-- --------------------------------------------------------

--
-- Table structure for table `likes`
--

CREATE TABLE `likes` (
  `UserID` int(11) NOT NULL,
  `PostID` int(11) NOT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `likes`
--

INSERT INTO `likes` (`UserID`, `PostID`, `CreatedAt`) VALUES
(14, 71, '2026-05-28 16:02:02'),
(14, 72, '2026-05-28 16:06:20'),
(14, 79, '2026-06-01 00:09:21'),
(14, 81, '2026-06-01 00:09:11'),
(14, 82, '2026-06-01 00:53:53'),
(14, 85, '2026-06-01 00:53:53'),
(14, 87, '2026-06-01 00:53:53'),
(14, 88, '2026-06-01 00:54:37'),
(14, 90, '2026-06-01 00:53:53'),
(14, 92, '2026-06-01 00:49:06'),
(14, 93, '2026-06-01 00:53:53'),
(14, 95, '2026-06-01 01:17:50'),
(14, 96, '2026-06-01 00:53:53'),
(14, 99, '2026-06-01 00:53:53'),
(14, 106, '2026-06-01 00:54:39'),
(17, 71, '2026-06-01 00:53:53'),
(17, 79, '2026-06-01 00:53:53'),
(17, 82, '2026-06-01 00:53:53'),
(17, 86, '2026-06-01 00:53:53'),
(17, 90, '2026-06-01 00:53:53'),
(17, 93, '2026-06-01 00:53:53'),
(17, 96, '2026-06-01 00:53:53'),
(17, 99, '2026-06-01 00:53:53'),
(27, 71, '2026-06-01 00:53:53'),
(27, 80, '2026-06-01 00:53:53'),
(27, 82, '2026-06-01 00:53:53'),
(27, 85, '2026-06-01 00:53:53'),
(27, 88, '2026-06-01 00:53:53'),
(27, 91, '2026-06-01 00:53:53'),
(27, 94, '2026-06-01 00:53:53'),
(27, 97, '2026-06-01 00:53:53'),
(27, 100, '2026-06-01 00:53:53'),
(34, 71, '2026-06-01 00:53:53'),
(34, 80, '2026-06-01 00:53:53'),
(34, 83, '2026-06-01 00:53:53'),
(34, 85, '2026-06-01 00:53:53'),
(34, 88, '2026-06-01 00:53:53'),
(34, 92, '2026-06-01 00:53:53'),
(34, 94, '2026-06-01 00:53:53'),
(34, 97, '2026-06-01 00:53:53'),
(34, 100, '2026-06-01 00:53:53'),
(43, 72, '2026-06-01 00:53:53'),
(43, 80, '2026-06-01 00:53:53'),
(43, 83, '2026-06-01 00:53:53'),
(43, 85, '2026-06-01 00:53:53'),
(43, 88, '2026-06-01 00:53:53'),
(43, 91, '2026-06-01 00:53:53'),
(43, 94, '2026-06-01 00:53:53'),
(43, 97, '2026-06-01 00:53:53'),
(43, 100, '2026-06-01 00:53:53'),
(44, 72, '2026-06-01 00:53:53'),
(44, 81, '2026-06-01 00:53:53'),
(44, 83, '2026-06-01 00:53:53'),
(44, 86, '2026-06-01 00:53:53'),
(44, 89, '2026-06-01 00:53:53'),
(44, 91, '2026-06-01 00:53:53'),
(44, 95, '2026-06-01 00:53:53'),
(44, 98, '2026-06-01 00:53:53'),
(44, 101, '2026-06-01 00:53:53'),
(45, 72, '2026-06-01 00:53:53'),
(45, 81, '2026-06-01 00:53:53'),
(45, 84, '2026-06-01 00:53:53'),
(45, 86, '2026-06-01 00:53:53'),
(45, 89, '2026-06-01 00:53:53'),
(45, 92, '2026-06-01 00:53:53'),
(45, 95, '2026-06-01 00:53:53'),
(45, 98, '2026-06-01 00:53:53'),
(45, 101, '2026-06-01 00:53:53'),
(46, 79, '2026-06-01 00:53:53'),
(46, 81, '2026-06-01 00:53:53'),
(46, 84, '2026-06-01 00:53:53'),
(46, 87, '2026-06-01 00:53:53'),
(46, 89, '2026-06-01 00:53:53'),
(46, 92, '2026-06-01 00:53:53'),
(46, 95, '2026-06-01 00:53:53'),
(46, 98, '2026-06-01 00:53:53'),
(46, 101, '2026-06-01 00:53:53'),
(47, 82, '2026-06-01 00:53:53'),
(47, 84, '2026-06-01 00:53:53'),
(47, 87, '2026-06-01 00:53:53'),
(47, 90, '2026-06-01 00:53:53'),
(47, 93, '2026-06-01 00:53:53'),
(47, 96, '2026-06-01 00:53:53'),
(47, 99, '2026-06-01 00:53:53');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `NotificationID` int(11) NOT NULL,
  `NotificationTypeID` int(11) NOT NULL,
  `ReceiverUserID` int(11) NOT NULL,
  `SenderUserID` int(11) NOT NULL,
  `PostID` int(11) DEFAULT NULL,
  `CommentID` int(11) DEFAULT NULL,
  `Message` text DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `IsRead` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`NotificationID`, `NotificationTypeID`, `ReceiverUserID`, `SenderUserID`, `PostID`, `CommentID`, `Message`, `CreatedAt`, `IsRead`) VALUES
(17, 7, 27, 13, NULL, NULL, NULL, '2026-05-27 15:39:41', 0),
(18, 8, 27, 13, NULL, NULL, NULL, '2026-05-27 15:39:42', 0),
(34, 3, 13, 17, NULL, NULL, NULL, '2026-05-27 17:41:05', 0),
(42, 9, 17, 13, NULL, NULL, 'Chào mừng đến với thế giới của các nàng tiên', '2026-05-27 17:51:11', 1),
(59, 3, 14, 17, NULL, NULL, 'Hân Nguyễn đã theo dõi bạn', '2026-05-27 20:32:00', 1),
(61, 3, 34, 14, NULL, NULL, 'Trần Hồng Mai đã theo dõi bạn', '2026-05-28 14:15:40', 0),
(62, 3, 27, 14, NULL, NULL, 'Trần Hồng Mai đã theo dõi bạn', '2026-05-28 14:15:47', 0),
(63, 7, 14, 13, NULL, NULL, NULL, '2026-05-28 14:56:51', 1),
(64, 8, 14, 13, NULL, NULL, NULL, '2026-05-28 14:56:55', 1),
(65, 9, 14, 13, NULL, NULL, 'CẦN CẨN THẬN', '2026-05-28 14:57:48', 1),
(66, 9, 17, 13, NULL, NULL, 'CẦN CẨN THẬN', '2026-05-28 14:57:48', 0),
(67, 9, 27, 13, NULL, NULL, 'CẦN CẨN THẬN', '2026-05-28 14:57:48', 0),
(68, 9, 34, 13, NULL, NULL, 'CẦN CẨN THẬN', '2026-05-28 14:57:48', 0),
(71, 2, 17, 14, 72, NULL, 'Trần Hồng Mai đã đăng lại bài viết của bạn', '2026-05-28 16:02:17', 0),
(73, 3, 17, 14, NULL, NULL, 'Trần Hồng Mai đã theo dõi bạn', '2026-06-01 00:08:25', 0),
(74, 1, 17, 14, 81, NULL, NULL, '2026-06-01 00:09:11', 0),
(75, 9, 14, 13, NULL, NULL, 'Chào mừng bạn đến với ARCHIVE.', '2026-06-01 00:26:54', 0),
(76, 9, 17, 13, NULL, NULL, 'Hệ thống đã được cập nhật phiên bản mới.', '2026-06-01 00:26:54', 0),
(77, 9, 14, 13, NULL, NULL, 'Đây là thông báo mẫu để trình diễn chức năng thông báo.', '2026-06-01 00:26:54', 0),
(78, 1, 17, 14, 92, NULL, NULL, '2026-06-01 00:49:06', 0),
(79, 1, 17, 14, 88, NULL, NULL, '2026-06-01 00:54:37', 0),
(80, 1, 17, 14, 106, NULL, NULL, '2026-06-01 00:54:39', 0),
(81, 2, 17, 14, 88, 67, NULL, '2026-06-01 00:54:59', 0),
(82, 2, 43, 14, 109, NULL, 'Trần Hồng Mai đã đăng lại bài viết của bạn', '2026-06-01 00:58:51', 0),
(83, 2, 43, 14, 110, NULL, 'Trần Hồng Mai đã đăng lại bài viết của bạn', '2026-06-01 00:58:56', 0),
(84, 1, 43, 14, 95, NULL, NULL, '2026-06-01 01:17:50', 0),
(85, 3, 50, 14, NULL, NULL, 'Trần Hồng Mai đã theo dõi bạn', '2026-06-01 01:18:00', 0),
(86, 3, 46, 14, NULL, NULL, 'Trần Hồng Mai đã theo dõi bạn', '2026-06-01 01:18:01', 0),
(87, 3, 44, 14, NULL, NULL, 'Trần Hồng Mai đã theo dõi bạn', '2026-06-01 01:18:02', 0),
(88, 2, 17, 14, 90, 131, NULL, '2026-06-01 01:26:27', 0),
(89, 2, 43, 14, 104, 133, NULL, '2026-06-01 01:28:02', 0),
(90, 7, 27, 13, NULL, NULL, NULL, '2026-06-01 01:35:57', 0),
(91, 8, 27, 13, NULL, NULL, NULL, '2026-06-01 01:35:59', 0),
(92, 4, 14, 13, NULL, NULL, NULL, '2026-06-01 01:36:39', 0);

-- --------------------------------------------------------

--
-- Table structure for table `notificationtypes`
--

CREATE TABLE `notificationtypes` (
  `NotificationTypeID` int(11) NOT NULL,
  `TypeName` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notificationtypes`
--

INSERT INTO `notificationtypes` (`NotificationTypeID`, `TypeName`) VALUES
(7, 'AccountLocked'),
(8, 'AccountUnlocked'),
(2, 'Comment'),
(5, 'ContentHidden'),
(3, 'Follow'),
(1, 'Like'),
(4, 'ReportWarning'),
(6, 'RoleChanged'),
(9, 'System');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `posthashtags`
--

CREATE TABLE `posthashtags` (
  `PostID` int(11) NOT NULL,
  `HashtagID` int(11) NOT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posthashtags`
--

INSERT INTO `posthashtags` (`PostID`, `HashtagID`, `CreatedAt`) VALUES
(71, 27, '2026-05-28 15:49:57'),
(107, 45, '2026-06-01 00:57:41'),
(108, 46, '2026-06-01 00:57:48');

-- --------------------------------------------------------

--
-- Table structure for table `postimages`
--

CREATE TABLE `postimages` (
  `PostImageID` int(11) NOT NULL,
  `PostID` int(11) NOT NULL,
  `ImageUrl` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `postimages`
--

INSERT INTO `postimages` (`PostImageID`, `PostID`, `ImageUrl`) VALUES
(43, 80, 'Public/uploads/posts/post_6a1b3f80ecbae5.01479396.jpg'),
(44, 81, 'Public/uploads/posts/post_6a1bf105303fa0.58986929.png'),
(45, 82, 'Public/uploads/posts/post_6a1bf34c87bb17.72972953.jpg'),
(46, 83, 'Public/uploads/posts/post_6a1c02b72448b5.46003413.png'),
(47, 84, 'Public/uploads/posts/post_6a1c08d52d7213.89454624.png'),
(48, 86, 'Public/uploads/posts/post_6a1c6df88419c3.19090905.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `postpreferences`
--

CREATE TABLE `postpreferences` (
  `PreferenceID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `PostID` int(11) NOT NULL,
  `PreferenceType` varchar(50) NOT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `postpreferences`
--

INSERT INTO `postpreferences` (`PreferenceID`, `UserID`, `PostID`, `PreferenceType`, `CreatedAt`) VALUES
(1, 14, 101, 'not_interested', '2026-06-01 00:58:15');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `PostID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Content` longtext NOT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `IsHidden` tinyint(1) NOT NULL DEFAULT 0,
  `Privacy` varchar(20) NOT NULL DEFAULT 'public',
  `OriginalPostID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`PostID`, `UserID`, `Content`, `CreatedAt`, `IsHidden`, `Privacy`, `OriginalPostID`) VALUES
(71, 14, 'Chào mừng đến với ARCHIVE - mạng xã hội mini dành cho việc chia sẻ câu chuyện, hình ảnh và tương tác với bạn bè. #Archive #PTUDWeb', '2026-05-28 15:49:57', 0, 'public', NULL),
(72, 14, 'Hôm nay mình thử đăng bài đầu tiên trên ARCHIVE. Giao diện đơn giản, dễ dùng và có thể tương tác qua like, comment, follow. #UEH #Archive', '2026-05-28 16:02:17', 0, 'public', NULL),
(79, 14, 'Một bài viết mẫu dùng để kiểm thử chức năng đăng lại bài viết trong hệ thống.', '2026-05-31 02:42:17', 0, 'public', 82),
(80, 14, 'Bài viết này dùng để minh họa chức năng kiểm duyệt nội dung trong trang quản trị.', '2026-05-31 02:50:23', 0, 'public', NULL),
(81, 17, 'ARCHIVE hỗ trợ hashtag để người dùng dễ dàng tìm kiếm và phân loại nội dung. #Archive #SocialNetwork', '2026-05-31 15:27:48', 0, 'public', NULL),
(82, 14, 'Việt Nam muôn năm', '2026-05-31 15:37:36', 0, 'public', NULL),
(83, 17, 'Môn Phát triển Ứng dụng Web đúng là thử thách nhưng cũng rất đáng giá. Nhờ dự án này mà mình hiểu rõ hơn về MVC, database và quy trình triển khai thực tế. #RewardingSubject', '2026-05-31 16:43:18', 0, 'public', NULL),
(84, 17, 'Hôm nay nhóm vừa deploy thành công phiên bản mới lên Railway. Cảm giác nhìn ứng dụng chạy trên môi trường thực tế thật sự rất vui. #BIT #hardworkpaysoff', '2026-05-31 17:09:24', 0, 'public', NULL),
(85, 14, 'Không ngờ một bài chia sẻ về quá trình làm đồ án lại nhận được nhiều lượt tương tác như vậy. Cảm ơn mọi người đã góp ý và ủng hộ. #Viral', '2026-06-01 00:08:59', 0, 'public', NULL),
(86, 14, 'Những tuần cuối học kỳ luôn là khoảng thời gian bận rộn nhất. Vừa hoàn thiện báo cáo, vừa kiểm thử hệ thống nhưng thành quả đạt được hoàn toàn xứng đáng. #BIT #RewardingSubject #hardworkpaysoff', '2026-06-01 00:21:00', 0, 'public', NULL),
(87, 14, 'Chào mừng đến với ARCHIVE. Đây là bài viết mẫu dùng để trình diễn chức năng đăng bài của hệ thống.', '2026-06-01 00:26:04', 0, 'public', NULL),
(88, 17, 'ARCHIVE được xây dựng theo mô hình MVC và triển khai trên Railway.', '2026-06-01 00:26:04', 0, 'public', NULL),
(89, 14, 'Hôm nay mình đang hoàn thiện báo cáo môn Phát triển Ứng dụng Web. #PTUDWeb', '2026-06-01 00:26:04', 0, 'public', NULL),
(90, 17, 'Một ngày học tập hiệu quả tại UEH. #StudentLife', '2026-06-01 00:26:04', 0, 'public', NULL),
(91, 14, 'Tính năng báo cáo nội dung giúp hệ thống kiểm duyệt hiệu quả hơn.', '2026-06-01 00:26:04', 0, 'public', NULL),
(92, 17, 'Đây là bài viết dùng để thử nghiệm chức năng bình luận và lượt thích.', '2026-06-01 00:26:04', 0, 'public', NULL),
(93, 14, 'Mạng xã hội ARCHIVE hỗ trợ hashtag, follow và notification.', '2026-06-01 00:26:04', 0, 'public', NULL),
(94, 17, 'Kiểm thử giao diện quản trị viên sau khi triển khai lên Railway.', '2026-06-01 00:26:04', 0, 'public', NULL),
(95, 43, 'Theo báo cáo mới về chuyển đổi số, nhu cầu nhân lực trong lĩnh vực dữ liệu và trí tuệ nhân tạo đang tăng rất nhanh trong những năm gần đây. Đây cũng là cơ hội lớn cho sinh viên công nghệ thông tin và hệ thống thông tin. #BIT', '2026-06-01 00:48:31', 0, 'public', NULL),
(96, 45, '“Success is the sum of small efforts, repeated day in and day out.” - Robert Collier. Đôi khi chỉ cần tiến bộ một chút mỗi ngày là đủ để tạo nên sự khác biệt lớn. #hardworkpaysoff', '2026-06-01 00:48:31', 0, 'public', NULL),
(97, 44, 'Hôm nay nhóm vừa hoàn thành phiên bản đầu tiên của hệ thống ARCHIVE. Tuy còn nhiều thứ cần cải thiện nhưng đây là một cột mốc đáng nhớ. #RewardingSubject', '2026-06-01 00:48:31', 0, 'public', NULL),
(98, 47, 'Mọi người thường đánh giá thấp sức mạnh của việc ghi chú. Một hệ thống ghi chú tốt có thể giúp tiết kiệm rất nhiều thời gian khi ôn tập hoặc làm dự án. #BIT', '2026-06-01 00:48:31', 0, 'public', NULL),
(99, 46, 'Tin vui cho sinh viên UEH: thư viện đang bổ sung thêm nhiều đầu sách mới liên quan đến dữ liệu, AI và quản trị hệ thống. #UEH', '2026-06-01 00:48:31', 0, 'public', NULL),
(100, 47, 'Đôi khi điều khó nhất không phải là bắt đầu, mà là duy trì sự kiên trì đủ lâu để nhìn thấy kết quả. #hardworkpaysoff', '2026-06-01 00:48:31', 0, 'public', NULL),
(101, 45, 'Một trong những kỹ năng quan trọng nhất khi làm dự án nhóm là giao tiếp. Công nghệ có thể học dần, nhưng khả năng phối hợp sẽ quyết định hiệu quả của cả nhóm. #RewardingSubject', '2026-06-01 00:48:31', 0, 'public', NULL),
(103, 14, 'Bài học lớn nhất sau khi hoàn thành đồ án không phải là code được bao nhiêu chức năng, mà là cách giải quyết vấn đề khi gặp lỗi. #hardworkpaysoff', '2026-06-01 00:48:31', 0, 'public', NULL),
(104, 43, 'Nếu phải chọn một môn học mang lại nhiều trải nghiệm thực tế nhất trong học kỳ này, mình sẽ chọn Phát triển Ứng dụng Web. #RewardingSubject', '2026-06-01 00:48:31', 0, 'public', NULL),
(105, 44, 'Một số doanh nghiệp hiện nay đã bắt đầu áp dụng AI để hỗ trợ tuyển dụng, chăm sóc khách hàng và phân tích dữ liệu kinh doanh. #Technology', '2026-06-01 00:48:31', 0, 'public', NULL),
(106, 17, 'Không phải mọi bài đăng đều cần mang tính học thuật. Đôi khi chia sẻ một khoảnh khắc tích cực trong ngày cũng đủ tạo nên sự kết nối với mọi người. #Viral', '2026-06-01 00:48:31', 0, 'public', NULL),
(107, 17, '#ManifestARCHIVE10', '2026-06-01 00:57:41', 0, 'public', NULL),
(108, 14, '#manifestARCHIVE10đ', '2026-06-01 00:57:48', 0, 'public', NULL),
(109, 14, 'Đăng lại từ @user_demo01:\n\nTheo báo cáo mới về chuyển đổi số, nhu cầu nhân lực trong lĩnh vực dữ liệu và trí tuệ nhân tạo đang tăng rất nhanh trong những năm gần đây. Đây cũng là cơ hội lớn cho sinh viên công nghệ thông tin và hệ thống thông tin. #BIT', '2026-06-01 00:58:50', 0, 'public', 95),
(110, 14, 'Đăng lại từ @user_demo01:\n\nTheo báo cáo mới về chuyển đổi số, nhu cầu nhân lực trong lĩnh vực dữ liệu và trí tuệ nhân tạo đang tăng rất nhanh trong những năm gần đây. Đây cũng là cơ hội lớn cho sinh viên công nghệ thông tin và hệ thống thông tin. #BIT', '2026-06-01 00:58:55', 0, 'public', 95);

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `ReportID` int(11) NOT NULL,
  `ReporterUserID` int(11) NOT NULL,
  `ReportedUserID` int(11) DEFAULT NULL,
  `PostID` int(11) DEFAULT NULL,
  `CommentID` int(11) DEFAULT NULL,
  `Reason` varchar(255) NOT NULL,
  `Details` longtext DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `Status` varchar(20) NOT NULL DEFAULT 'Pending',
  `AdminNote` text DEFAULT NULL,
  `ResolvedAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`ReportID`, `ReporterUserID`, `ReportedUserID`, `PostID`, `CommentID`, `Reason`, `Details`, `CreatedAt`, `Status`, `AdminNote`, `ResolvedAt`) VALUES
(29, 17, 14, 80, NULL, 'Nội dung không phù hợp', 'Bài viết có nội dung cần quản trị viên kiểm tra.', '2026-06-01 00:38:28', 'Resolved', 'Tái phạm nhiều lần', '2026-06-01 01:36:39'),
(30, 17, 14, 103, NULL, 'Bạo lực, thù ghét hoặc bóc lột', 'Thù ghét hoặc biểu tượng thù ghét', '2026-06-01 00:51:53', 'Pending', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `RoleID` int(11) NOT NULL,
  `RoleName` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`RoleID`, `RoleName`) VALUES
(1, 'Admin'),
(2, 'User');

-- --------------------------------------------------------

--
-- Table structure for table `search_history`
--

CREATE TABLE `search_history` (
  `SearchID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `Keyword` varchar(255) NOT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `userblocks`
--

CREATE TABLE `userblocks` (
  `BlockID` int(11) NOT NULL,
  `BlockerUserID` int(11) NOT NULL,
  `BlockedUserID` int(11) NOT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` int(11) NOT NULL,
  `RoleID` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `FullName` varchar(100) DEFAULT NULL,
  `Bio` varchar(500) DEFAULT NULL,
  `ProfilePictureUrl` varchar(255) DEFAULT NULL,
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `IsActive` tinyint(1) NOT NULL DEFAULT 1,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `email_verified_at` datetime DEFAULT NULL,
  `verification_token` varchar(255) DEFAULT NULL,
  `verification_expires_at` datetime DEFAULT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `avatar_url` text DEFAULT NULL,
  `auth_provider` varchar(50) NOT NULL DEFAULT 'local'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `RoleID`, `Username`, `Email`, `PasswordHash`, `FullName`, `Bio`, `ProfilePictureUrl`, `CreatedAt`, `IsActive`, `is_verified`, `email_verified_at`, `verification_token`, `verification_expires_at`, `google_id`, `avatar_url`, `auth_provider`) VALUES
(13, 1, 'super_admin', 'admin@archive.local', '$2y$10$I5t2wk4MWup32onfxnADp.jQO39aXpMusvQZ6waeCweQF7yfs0ZGG', 'Tổng Cục Kiểm Duyệt', 'Tài khoản chuyên làm nhiệm vụ hệ thống 🛡️', 'Public/assets/img/avatar-admin.jpg', '2026-01-01 00:00:00', 1, 1, '2026-01-01 00:00:00', NULL, NULL, NULL, NULL, 'local'),
(14, 2, 'maitrhog', 'user01@archive.local', '$2y$12$Dfr.b2RHYJTwfUrfq0aJ.OdL9iCfOyzEuQaXpSXscz5Pj4gdxFG42', 'Trần Hồng Mai', NULL, NULL, '2026-05-17 18:50:17', 1, 1, '2026-06-01 00:30:08', NULL, NULL, NULL, NULL, 'local'),
(17, 2, 'ghan_ngn', 'user02@archive.local', '$2y$12$Dfr.b2RHYJTwfUrfq0aJ.OdL9iCfOyzEuQaXpSXscz5Pj4gdxFG42', 'Hân Nguyễn', 'siu nhân gao', 'Public/uploads/avatars/avatar_6a1c75d72cb247.73051999.jpeg', '2026-05-20 13:46:34', 1, 1, '2026-06-01 00:30:08', NULL, NULL, '115754676997934767325', 'https://lh3.googleusercontent.com/a/ACg8ocJVMLc0jFP4m1onokgPZknqkY6Qpu-yIIkFYOC6zgzG5QLg3Q=s96-c', 'google'),
(27, 2, 'miki', 'user27@archive.local', '$2y$12$Dfr.b2RHYJTwfUrfq0aJ.OdL9iCfOyzEuQaXpSXscz5Pj4gdxFG42', 'Ky Nguyen', NULL, NULL, '2026-05-26 04:01:41', 1, 1, '2026-06-01 00:30:21', NULL, NULL, NULL, NULL, 'local'),
(34, 2, 'mai123', 'user34@archive.local', '$2y$12$Dfr.b2RHYJTwfUrfq0aJ.OdL9iCfOyzEuQaXpSXscz5Pj4gdxFG42', 'Trần Hồng Mai', NULL, NULL, '2026-05-26 15:56:08', 1, 1, '2026-06-01 00:30:21', NULL, NULL, NULL, NULL, 'local'),
(43, 2, 'user_demo01', 'user43@archive.local', '$2y$12$Dfr.b2RHYJTwfUrfq0aJ.OdL9iCfOyzEuQaXpSXscz5Pj4gdxFG42', 'Nguyễn Minh Anh', 'Yêu thích công nghệ và mạng xã hội.', NULL, '2026-06-01 00:25:28', 1, 1, '2026-06-01 00:30:21', NULL, NULL, NULL, NULL, 'local'),
(44, 2, 'user_demo02', 'user44@archive.local', '$2y$12$Dfr.b2RHYJTwfUrfq0aJ.OdL9iCfOyzEuQaXpSXscz5Pj4gdxFG42', 'Trần Khánh Linh', 'Sinh viên UEH.', NULL, '2026-06-01 00:25:28', 1, 1, '2026-06-01 00:30:21', NULL, NULL, NULL, NULL, 'local'),
(45, 2, 'user_demo03', 'user45@archive.local', '$2y$12$Dfr.b2RHYJTwfUrfq0aJ.OdL9iCfOyzEuQaXpSXscz5Pj4gdxFG42', 'Lê Quốc Huy', 'Đam mê lập trình web.', NULL, '2026-06-01 00:25:28', 1, 1, '2026-06-01 00:30:21', NULL, NULL, NULL, NULL, 'local'),
(46, 2, 'user_demo04', 'user46@archive.local', '$2y$12$Dfr.b2RHYJTwfUrfq0aJ.OdL9iCfOyzEuQaXpSXscz5Pj4gdxFG42', 'Phạm Gia Hân', 'Thích chia sẻ cuộc sống hằng ngày.', NULL, '2026-06-01 00:25:28', 1, 1, '2026-06-01 00:30:21', NULL, NULL, NULL, NULL, 'local'),
(47, 2, 'user_demo05', 'user47@archive.local', '$2y$12$Dfr.b2RHYJTwfUrfq0aJ.OdL9iCfOyzEuQaXpSXscz5Pj4gdxFG42', 'Võ Thanh Tùng', 'Người dùng thử nghiệm hệ thống.', NULL, '2026-06-01 00:25:28', 1, 1, '2026-06-01 00:30:21', NULL, NULL, NULL, NULL, 'local'),
(50, 2, 'hannguyen.31231024972', 'hannguyen.31231024972@st.ueh.edu.vn', '$2y$12$k7rGcvbnfV6carXCKzEl6ejkcKbAjlvLceSdTi1u8gNIgv68W2lKq', 'HÂN NGUYỄN GIA', NULL, 'https://lh3.googleusercontent.com/a/ACg8ocKNVvK1K1iNwYAxUGEvXufZW0KlT-_az1kw1I3htAtCQbgt6w=s96-c', '2026-06-01 01:02:57', 1, 1, '2026-06-01 01:02:57', NULL, NULL, '116594184386159458139', 'https://lh3.googleusercontent.com/a/ACg8ocKNVvK1K1iNwYAxUGEvXufZW0KlT-_az1kw1I3htAtCQbgt6w=s96-c', 'google');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`LogID`),
  ADD KEY `fk_admin_logs_admin` (`AdminUserID`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`CommentID`),
  ADD KEY `ParentCommentID` (`ParentCommentID`),
  ADD KEY `comments_ibfk_2` (`UserID`),
  ADD KEY `idx_comments_postid` (`PostID`);

--
-- Indexes for table `follows`
--
ALTER TABLE `follows`
  ADD PRIMARY KEY (`FollowerID`,`FollowedID`),
  ADD KEY `FollowedID` (`FollowedID`),
  ADD KEY `idx_follows_follower_followed` (`FollowerID`,`FollowedID`);

--
-- Indexes for table `hashtags`
--
ALTER TABLE `hashtags`
  ADD PRIMARY KEY (`HashtagID`),
  ADD UNIQUE KEY `HashtagName` (`HashtagName`);

--
-- Indexes for table `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`UserID`,`PostID`),
  ADD KEY `idx_likes_postid` (`PostID`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`NotificationID`),
  ADD KEY `ReceiverUserID` (`ReceiverUserID`),
  ADD KEY `SenderUserID` (`SenderUserID`),
  ADD KEY `notifications_ibfk_1` (`NotificationTypeID`),
  ADD KEY `notifications_ibfk_4` (`PostID`),
  ADD KEY `notifications_ibfk_5` (`CommentID`);

--
-- Indexes for table `notificationtypes`
--
ALTER TABLE `notificationtypes`
  ADD PRIMARY KEY (`NotificationTypeID`),
  ADD UNIQUE KEY `TypeName` (`TypeName`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_password_reset_token_hash` (`token_hash`),
  ADD KEY `idx_password_reset_user_id` (`user_id`),
  ADD KEY `idx_password_reset_expires_at` (`expires_at`);

--
-- Indexes for table `posthashtags`
--
ALTER TABLE `posthashtags`
  ADD PRIMARY KEY (`PostID`,`HashtagID`),
  ADD KEY `fk_posthashtags_hashtag` (`HashtagID`),
  ADD KEY `idx_posthashtags_postid` (`PostID`);

--
-- Indexes for table `postimages`
--
ALTER TABLE `postimages`
  ADD PRIMARY KEY (`PostImageID`),
  ADD KEY `idx_postimages_postid` (`PostID`);

--
-- Indexes for table `postpreferences`
--
ALTER TABLE `postpreferences`
  ADD PRIMARY KEY (`PreferenceID`),
  ADD UNIQUE KEY `unique_post_preference` (`UserID`,`PostID`,`PreferenceType`),
  ADD KEY `fk_postpreferences_post` (`PostID`),
  ADD KEY `idx_postpreferences_user_post_type` (`UserID`,`PostID`,`PreferenceType`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`PostID`),
  ADD KEY `idx_posts_userid` (`UserID`),
  ADD KEY `idx_posts_createdat` (`CreatedAt`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`ReportID`),
  ADD KEY `FK_Reports_Comments` (`CommentID`),
  ADD KEY `FK_Reports_Posts` (`PostID`),
  ADD KEY `FK_Reports_ReportedUser` (`ReportedUserID`),
  ADD KEY `FK_Reports_Reporter` (`ReporterUserID`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`RoleID`),
  ADD UNIQUE KEY `RoleName` (`RoleName`);

--
-- Indexes for table `search_history`
--
ALTER TABLE `search_history`
  ADD PRIMARY KEY (`SearchID`),
  ADD KEY `fk_search_user` (`UserID`);

--
-- Indexes for table `userblocks`
--
ALTER TABLE `userblocks`
  ADD PRIMARY KEY (`BlockID`),
  ADD UNIQUE KEY `unique_user_block` (`BlockerUserID`,`BlockedUserID`),
  ADD KEY `idx_userblocks_blocker_blocked` (`BlockerUserID`,`BlockedUserID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `FK_Users_Roles` (`RoleID`),
  ADD KEY `idx_users_google_id` (`google_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `LogID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `CommentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=134;

--
-- AUTO_INCREMENT for table `hashtags`
--
ALTER TABLE `hashtags`
  MODIFY `HashtagID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `NotificationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=93;

--
-- AUTO_INCREMENT for table `notificationtypes`
--
ALTER TABLE `notificationtypes`
  MODIFY `NotificationTypeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `postimages`
--
ALTER TABLE `postimages`
  MODIFY `PostImageID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `postpreferences`
--
ALTER TABLE `postpreferences`
  MODIFY `PreferenceID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `PostID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `ReportID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `RoleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `search_history`
--
ALTER TABLE `search_history`
  MODIFY `SearchID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `userblocks`
--
ALTER TABLE `userblocks`
  MODIFY `BlockID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `fk_admin_logs_admin` FOREIGN KEY (`AdminUserID`) REFERENCES `users` (`UserID`) ON UPDATE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`PostID`) REFERENCES `posts` (`PostID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `comments_ibfk_3` FOREIGN KEY (`ParentCommentID`) REFERENCES `comments` (`CommentID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `follows`
--
ALTER TABLE `follows`
  ADD CONSTRAINT `follows_ibfk_1` FOREIGN KEY (`FollowerID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `follows_ibfk_2` FOREIGN KEY (`FollowedID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `likes_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `likes_ibfk_2` FOREIGN KEY (`PostID`) REFERENCES `posts` (`PostID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`NotificationTypeID`) REFERENCES `notificationtypes` (`NotificationTypeID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`ReceiverUserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_3` FOREIGN KEY (`SenderUserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_4` FOREIGN KEY (`PostID`) REFERENCES `posts` (`PostID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_5` FOREIGN KEY (`CommentID`) REFERENCES `comments` (`CommentID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `fk_password_reset_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `posthashtags`
--
ALTER TABLE `posthashtags`
  ADD CONSTRAINT `fk_posthashtags_hashtag` FOREIGN KEY (`HashtagID`) REFERENCES `hashtags` (`HashtagID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_posthashtags_post` FOREIGN KEY (`PostID`) REFERENCES `posts` (`PostID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `postimages`
--
ALTER TABLE `postimages`
  ADD CONSTRAINT `postimages_ibfk_1` FOREIGN KEY (`PostID`) REFERENCES `posts` (`PostID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `postpreferences`
--
ALTER TABLE `postpreferences`
  ADD CONSTRAINT `fk_postpreferences_post` FOREIGN KEY (`PostID`) REFERENCES `posts` (`PostID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_postpreferences_user` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON UPDATE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `FK_Reports_Comments` FOREIGN KEY (`CommentID`) REFERENCES `comments` (`CommentID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_Reports_Posts` FOREIGN KEY (`PostID`) REFERENCES `posts` (`PostID`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_Reports_ReportedUser` FOREIGN KEY (`ReportedUserID`) REFERENCES `users` (`UserID`) ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_Reports_Reporter` FOREIGN KEY (`ReporterUserID`) REFERENCES `users` (`UserID`) ON UPDATE CASCADE;

--
-- Constraints for table `search_history`
--
ALTER TABLE `search_history`
  ADD CONSTRAINT `fk_search_user` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `FK_Users_Roles` FOREIGN KEY (`RoleID`) REFERENCES `roles` (`RoleID`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
