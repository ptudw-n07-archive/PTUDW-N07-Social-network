-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 23, 2026 at 11:11 AM
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
(1, 1, 2, 'Đặt gạch hóng MV mới của anh trai nhen! 🔥', '2026-05-16 21:55:28', NULL, 0),
(2, 1, 4, 'Trời ơi hot quá em trai ơi, nhớ rủ anh đóng chung nha kkk! ❤️', '2026-05-16 21:55:28', NULL, 0),
(3, 2, 3, 'Chú Hiếu viết code bằng PDO giống anh nè, ôm một cái đỡ mệt mỏi.', '2026-05-16 21:55:28', NULL, 0),
(4, 3, 1, 'Cảm ơn anh Lâm đã flex giùm tụi em nhen haha!', '2026-05-16 21:55:28', NULL, 0),
(5, 9, 1, 'chào Tùng, ước được feat với anh một bài thì hay biết mấy', '2026-05-17 20:54:37', NULL, 0),
(6, 9, 1, 'Chào Tùng', '2026-05-17 21:01:43', NULL, 0),
(7, 9, 1, 'Chào tùng', '2026-05-17 21:06:50', NULL, 0),
(8, 9, 1, 'Chào ngày mới tùng', '2026-05-17 21:07:03', NULL, 0),
(9, 4, 1, 'Chào Lâm', '2026-05-17 21:07:22', NULL, 0),
(10, 9, 3, 'Chào Tùng', '2026-05-17 21:09:42', NULL, 0),
(11, 1, 3, 'quào chờ quá đi', '2026-05-17 21:11:04', NULL, 0),
(12, 13, 17, 'cho toi 1 tra sen vang', '2026-05-21 13:58:00', NULL, 0),
(13, 13, 14, '@hân nguyễn', '2026-05-21 13:58:55', NULL, 0),
(14, 32, 17, 'cứu', '2026-05-22 14:41:05', NULL, 0),
(15, 30, 17, 'tào lao', '2026-05-22 17:20:17', NULL, 0),
(16, 18, 17, 'tôi đi với mai mặt vuông', '2026-05-22 18:33:19', NULL, 0),
(17, 30, 17, 'mía lao', '2026-05-22 18:33:32', NULL, 0),
(18, 33, 23, 'miu lê is the best', '2026-05-22 22:21:32', NULL, 0),
(19, 36, 20, '???', '2026-05-22 22:24:04', NULL, 0),
(20, 34, 20, 'viết tào lao cái gì vậy', '2026-05-22 22:24:16', NULL, 0),
(21, 33, 20, 'thì sao', '2026-05-22 22:24:28', NULL, 0),
(22, 32, 20, 'fix đc chưa my bro', '2026-05-22 22:24:37', NULL, 0),
(23, 37, 20, 'ko được dùng hashtag nhé', '2026-05-22 22:25:21', NULL, 0),
(24, 41, 15, 'tào lao :P', '2026-05-23 03:03:47', NULL, 0),
(25, 38, 15, 'Kujj cx hế', '2026-05-23 03:03:57', NULL, 0);

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
(1, 2, '2026-05-16 21:55:28'),
(1, 14, '2026-05-17 20:36:45'),
(2, 1, '2026-05-16 21:55:28'),
(3, 1, '2026-05-16 21:55:28'),
(4, 1, '2026-05-16 21:55:28'),
(15, 17, '2026-05-23 03:05:01'),
(15, 23, '2026-05-23 03:04:58'),
(17, 15, '2026-05-23 15:54:41'),
(17, 20, '2026-05-22 17:56:16'),
(20, 16, '2026-05-22 17:42:21'),
(20, 17, '2026-05-23 00:25:27'),
(23, 20, '2026-05-22 22:21:48');

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
(1, 'Comeback', 2, '2026-05-21 14:57:38', 0),
(2, 'Music', 3, '2026-05-21 14:57:38', 0),
(3, 'Showbiz', 2, '2026-05-21 14:57:38', 0),
(4, 'Cafe', 2, '2026-05-21 14:57:38', 0),
(5, 'Chill', 3, '2026-05-21 14:57:38', 0),
(6, 'Review', 1, '2026-05-21 14:57:38', 0),
(7, 'Mood', 5, '2026-05-21 14:57:38', 0),
(8, 'Sad', 1, '2026-05-21 14:57:38', 0),
(9, 'Life', 3, '2026-05-21 14:57:38', 0),
(10, 'Camau', 2, '2026-05-21 14:57:38', 0),
(11, 'Travel', 1, '2026-05-21 14:57:38', 0),
(12, 'Study', 4, '2026-05-21 14:57:38', 0),
(13, 'Deadline', 3, '2026-05-21 14:57:38', 0),
(14, 'Archive', 4, '2026-05-21 14:57:38', 0),
(15, 'Friendship', 5, '2026-05-21 14:57:38', 0),
(17, 'MATUY', 1, '2026-05-22 18:34:12', 0),
(18, 'maimatvuong', 1, '2026-05-22 18:37:23', 0),
(19, 'hacker', 1, '2026-05-22 22:23:47', 0),
(20, 'mệtmỏi', 1, '2026-05-22 22:26:04', 0),
(21, 'việtnam', 2, '2026-05-22 23:00:02', 0),
(22, 'hope', 1, '2026-05-23 00:40:56', 0),
(23, 'Bullshit', 1, '2026-05-23 03:04:52', 0),
(24, 'BadPlatform', 1, '2026-05-23 03:07:50', 0);

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
(1, 2, '2026-05-16 21:55:28'),
(1, 3, '2026-05-16 21:55:28'),
(1, 7, '2026-05-17 19:53:51'),
(1, 9, '2026-05-17 21:10:22'),
(2, 1, '2026-05-16 21:55:28'),
(3, 1, '2026-05-16 21:55:28'),
(3, 9, '2026-05-17 21:10:09'),
(4, 1, '2026-05-16 21:55:28'),
(4, 2, '2026-05-16 21:55:28'),
(14, 13, '2026-05-21 13:58:08'),
(14, 17, '2026-05-21 13:58:04'),
(15, 29, '2026-05-23 03:06:28'),
(15, 38, '2026-05-23 03:03:49'),
(15, 41, '2026-05-23 03:03:22'),
(15, 42, '2026-05-23 03:06:25'),
(17, 17, '2026-05-21 13:57:51'),
(17, 28, '2026-05-22 15:42:22'),
(17, 30, '2026-05-22 17:20:01'),
(17, 31, '2026-05-22 15:42:21'),
(20, 7, '2026-05-22 17:41:38'),
(20, 13, '2026-05-22 17:41:32'),
(20, 17, '2026-05-22 17:41:30'),
(20, 18, '2026-05-22 17:41:34'),
(20, 19, '2026-05-21 14:06:15'),
(20, 22, '2026-05-21 14:43:01'),
(20, 30, '2026-05-22 17:41:22'),
(20, 32, '2026-05-22 16:30:04'),
(23, 35, '2026-05-22 22:21:41');

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
  `CreatedAt` datetime NOT NULL DEFAULT current_timestamp(),
  `IsRead` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`NotificationID`, `NotificationTypeID`, `ReceiverUserID`, `SenderUserID`, `PostID`, `CommentID`, `CreatedAt`, `IsRead`) VALUES
(1, 1, 1, 2, 1, NULL, '2026-05-16 21:55:29', 0),
(2, 2, 1, 4, 1, 2, '2026-05-16 21:55:29', 0),
(8, 1, 17, 20, 28, NULL, '2026-05-22 17:19:28', 1),
(9, 1, 20, 17, 30, NULL, '2026-05-22 17:20:01', 1),
(10, 2, 20, 17, 30, 15, '2026-05-22 17:20:17', 1),
(11, 3, 20, 17, NULL, NULL, '2026-05-22 17:20:22', 1),
(12, 3, 17, 20, NULL, NULL, '2026-05-23 00:25:28', 0),
(13, 1, 17, 20, 31, NULL, '2026-05-22 17:41:21', 1),
(14, 1, 17, 20, 17, NULL, '2026-05-22 17:41:30', 1),
(15, 1, 14, 20, 13, NULL, '2026-05-22 17:41:32', 0),
(16, 1, 17, 20, 18, NULL, '2026-05-22 17:41:34', 1),
(17, 1, 1, 20, 7, NULL, '2026-05-22 17:41:38', 0),
(18, 3, 16, 20, NULL, NULL, '2026-05-22 17:42:21', 0),
(19, 2, 20, 17, 30, 17, '2026-05-22 18:33:32', 1),
(20, 7, 22, 13, NULL, NULL, '2026-05-22 22:17:41', 0),
(21, 2, 23, 20, 36, 19, '2026-05-22 22:24:05', 0),
(22, 2, 17, 20, 34, 20, '2026-05-22 22:24:17', 0),
(23, 2, 17, 20, 33, 21, '2026-05-22 22:24:28', 0),
(24, 2, 17, 20, 32, 22, '2026-05-22 22:24:38', 0),
(25, 2, 23, 20, 37, 23, '2026-05-22 22:25:21', 0),
(26, 1, 17, 20, 33, NULL, '2026-05-23 00:25:07', 0),
(27, 1, 20, 15, 41, NULL, '2026-05-23 03:03:22', 0),
(28, 2, 20, 15, 41, 24, '2026-05-23 03:03:47', 0),
(29, 1, 20, 15, 38, NULL, '2026-05-23 03:03:49', 0),
(30, 2, 20, 15, 38, 25, '2026-05-23 03:03:57', 0),
(31, 3, 23, 15, NULL, NULL, '2026-05-23 03:04:58', 0),
(32, 3, 17, 15, NULL, NULL, '2026-05-23 03:05:01', 0),
(33, 4, 7, 13, NULL, NULL, '2026-05-23 03:09:28', 0),
(34, 7, 16, 13, NULL, NULL, '2026-05-23 03:11:11', 0),
(35, 3, 15, 17, NULL, NULL, '2026-05-23 15:54:41', 0);

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
(6, 'RoleChanged');

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
(1, 1, '2026-05-21 14:59:22'),
(1, 2, '2026-05-21 14:59:22'),
(1, 3, '2026-05-21 14:59:22'),
(2, 2, '2026-05-21 14:59:22'),
(2, 13, '2026-05-21 14:59:22'),
(3, 12, '2026-05-21 14:59:22'),
(3, 14, '2026-05-21 14:59:22'),
(4, 1, '2026-05-21 14:59:22'),
(4, 3, '2026-05-21 14:59:22'),
(5, 4, '2026-05-21 14:59:22'),
(5, 5, '2026-05-21 14:59:22'),
(5, 6, '2026-05-21 14:59:22'),
(6, 7, '2026-05-21 14:59:22'),
(6, 8, '2026-05-21 14:59:22'),
(6, 9, '2026-05-21 14:59:22'),
(7, 7, '2026-05-21 14:59:22'),
(7, 9, '2026-05-21 14:59:22'),
(8, 14, '2026-05-21 14:59:22'),
(8, 15, '2026-05-21 14:59:22'),
(9, 12, '2026-05-21 14:59:22'),
(9, 13, '2026-05-21 14:59:22'),
(13, 7, '2026-05-21 14:59:22'),
(13, 9, '2026-05-21 14:59:22'),
(17, 14, '2026-05-21 14:59:22'),
(17, 15, '2026-05-21 14:59:22'),
(18, 5, '2026-05-21 14:59:22'),
(18, 10, '2026-05-21 14:59:22'),
(18, 11, '2026-05-21 14:59:22'),
(19, 2, '2026-05-21 14:59:22'),
(19, 5, '2026-05-21 14:59:22'),
(20, 7, '2026-05-21 14:59:22'),
(20, 12, '2026-05-21 14:59:22'),
(20, 13, '2026-05-21 14:59:22'),
(21, 4, '2026-05-21 14:59:22'),
(21, 12, '2026-05-21 14:59:22'),
(21, 15, '2026-05-21 14:59:22'),
(22, 7, '2026-05-21 14:59:22'),
(22, 14, '2026-05-21 14:59:22'),
(30, 10, '2026-05-23 00:48:35'),
(30, 15, '2026-05-23 00:48:35'),
(33, 17, '2026-05-22 18:34:12'),
(35, 18, '2026-05-22 18:37:23'),
(37, 19, '2026-05-22 22:23:47'),
(38, 20, '2026-05-22 22:26:04'),
(41, 22, '2026-05-23 00:48:26'),
(42, 15, '2026-05-23 03:04:52'),
(42, 23, '2026-05-23 03:04:52'),
(44, 24, '2026-05-23 03:07:50');

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
(1, 1, 'Public/assets/img/sontung_comeback.png'),
(2, 2, 'Public/assets/img/hieu_show.jpg'),
(3, 3, 'Public/assets/img/leduongbaolam.png'),
(4, 6, 'Public/uploads/posts/post_6a099ca588ce56.35110958.JPG'),
(5, 7, 'Public/assets/img/posts/post_6a09b83a489190.54408140.jpg'),
(6, 18, 'Public/uploads/posts/post_6a0ead7a2af5a0.24763358.jpg'),
(7, 19, 'Public/uploads/posts/post_6a0eaedf4337b8.98471048.png'),
(8, 20, 'Public/uploads/posts/post_6a0eaf0b5faf29.90512648.jpg'),
(9, 21, 'Public/uploads/posts/post_6a0eb5c0f03fc5.32268441.jpg'),
(10, 22, 'Public/uploads/posts/post_6a0eb5e00080b1.53450111.jpg'),
(11, 23, 'Public/uploads/posts/post_6a0ebc77c73b95.97189052.png'),
(14, 26, 'Public/uploads/posts/post_6a0ebe680ca574.70069493.jpg'),
(15, 27, 'Public/uploads/posts/post_6a0ebea8e0b113.53133391.png'),
(16, 28, 'Public/uploads/posts/post_6a0ebeef0ee2e0.18859086.jpg'),
(17, 29, 'Public/assets/img/posts/post_6a0ec003866321.67146672.jpg'),
(18, 31, 'Public/uploads/posts/post_6a0eca11346742.40528399.png');

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
(1, 20, 9, 'not_interested', '2026-05-23 00:48:49');

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
  `Privacy` varchar(20) NOT NULL DEFAULT 'public'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`PostID`, `UserID`, `Content`, `CreatedAt`, `IsHidden`, `Privacy`) VALUES
(1, 1, 'Chuẩn bị comeback với MV mới quay tại Landmark 81 #Comeback #Music #Showbiz', '2026-05-16 21:55:28', 0, 'public'),
(2, 2, 'Mới đi diễn về mệt lả người nhưng vẫn phải mở XAMPP fix bug #Music #Deadline', '2026-05-16 21:55:28', 0, 'public'),
(3, 4, 'Mọi người ơi, tui mới phát hiện ra một nhóm sinh viên làm app Archive xịn quá #Archive #Study', '2026-05-16 21:55:28', 0, 'public'),
(4, 4, 'Sơn Tùng chuẩn bị comeback hot quá quý vị ơi! #Comeback #Showbiz', '2026-05-16 22:24:06', 0, 'public'),
(5, 5, 'Review quán cafe chill quận 1 nè #Cafe #Chill #Review', '2026-05-16 22:24:06', 0, 'public'),
(6, 1, 'Hôm nay tôi buồn #Mood #Sad #Life', '2026-05-17 17:47:01', 0, 'public'),
(7, 1, 'Mai Mặt Vuông #Mood #Life', '2026-05-17 19:44:41', 0, 'public'),
(8, 1, 'Xin chào mọi người trên Archive #Archive #Friendship', '2026-05-17 20:43:28', 0, 'public'),
(9, 1, 'Chào các bạn, hôm nay học hơi đuối nhưng vẫn ổn #Study #Deadline', '2026-05-17 20:46:16', 0, 'public'),
(13, 14, 'Xin chào, hôm nay trời đẹp ghê #Mood #Life', '2026-05-21 13:54:32', 0, 'public'),
(17, 17, 'Hello mọi người, tôi mới quay lại Archive #Archive #Friendship', '2026-05-21 13:57:26', 0, 'public'),
(18, 17, 'Chúng tôi đã có mặt tại Cà Mau #Camau #Travel #Chill', '2026-05-21 14:00:09', 0, 'public'),
(19, 17, 'Một ngày nhẹ nhàng để nghe nhạc và nghỉ ngơi #Music #Chill', '2026-05-21 14:06:06', 0, 'public'),
(20, 20, 'Deadline dí nhưng vẫn phải sống tích cực #Deadline #Study #Mood', '2026-05-21 14:07:02', 0, 'public'),
(21, 20, 'Đi cafe làm bài nhóm cũng vui phết #Cafe #Study #Friendship', '2026-05-21 14:35:40', 0, 'public'),
(22, 20, 'Archive hôm nay nhìn ổn áp hơn rồi đó #Archive #Mood', '2026-05-21 14:36:11', 0, 'public'),
(23, 20, '', '2026-05-21 15:04:19', 0, 'public'),
(26, 20, '', '2026-05-21 15:12:35', 0, 'public'),
(27, 17, '', '2026-05-21 15:13:28', 0, 'public'),
(28, 17, '', '2026-05-21 15:14:38', 0, 'public'),
(29, 15, 'đời cho vai phải diễn, phùuuuuu', '2026-05-21 15:19:15', 0, 'public'),
(30, 20, '#Camau sáng nắng c\r\nhiều mưa #Friendship', '2026-05-21 15:48:36', 0, 'public'),
(31, 17, '', '2026-05-21 16:02:08', 0, 'public'),
(32, 17, '22/5 lỗi tùm lum', '2026-05-22 14:40:56', 0, 'public'),
(33, 17, '#MATUY', '2026-05-22 18:34:12', 0, 'public'),
(34, 17, 'jredscvrfhuefcberfnbeuihfriuhcfiuerwhndfiuewhndewfcefc\r\nèewjfnewfbiuerhfuiehrgferg\r\nregg\r\nt\r\nhythyt\r\nh\r\ntrg\r\ne\r\nfe\r\nfd\r\ne\r\ndcfe\r\nrgh\r\nyt\r\njyu\r\n\r\nythrgfcqwedsa', '2026-05-22 18:36:07', 0, 'public'),
(35, 17, '#maimatvuong', '2026-05-22 18:37:23', 0, 'public'),
(36, 23, 'sulele là selulu', '2026-05-22 22:23:24', 0, 'public'),
(37, 23, 'cái này hay quá ae ơi #hacker', '2026-05-22 22:23:47', 0, 'public'),
(38, 20, '#mệtmỏi mệt quá', '2026-05-22 22:26:04', 0, 'public'),
(41, 20, 'Xin chào ngày mới, hôm nay tôi vẫn đang ngồi sửa code, tôi sẽ sửa tiếp cái phần hashtag, chỉ cần lấy top thôi, ko cần update theo ngày tại thấy xàm. Hi vọng mai làm được. \r\nTodo list ngày mai: \r\n\r\n- Làm lesson summary \r\n- Học gì đó\r\n- Sửa lại code. Ê với lại cái trang chỉnh sửa chưa ổn. \r\n#hope', '2026-05-23 00:40:23', 0, 'public'),
(42, 15, 'AI DÁM XÓA POST CON CARE BARE CỦA KAO???? #Friendship #Bullshit', '2026-05-23 03:04:52', 0, 'public'),
(43, 15, 'Tại sao Like rồi vẫn được Like bài nữa? MXH hài hước ghê', '2026-05-23 03:06:50', 0, 'public'),
(44, 15, 'Chỉnh cái cục 3 gạch ngang More ở feed lại tiếng việt đi trờiiiiiii!! việt việt anh anh nhức đầu quá!! #BadPlatform', '2026-05-23 03:07:50', 0, 'public');

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
(1, 3, 4, 3, NULL, 'Nội dung nhạy cảm', 'Anh Dương Lâm comment viết chữ IN HOA hết nhìn mỏi mắt quá admin ơi cứu tui!', '2026-05-16 21:55:29', 'Pending', NULL, NULL),
(3, 6, 5, 2, NULL, 'Ngôn từ gây hấn', 'Bình luận thô tục trong bài viết rap', '2026-05-16 21:24:06', 'Resolved', '', '2026-05-23 03:10:36'),
(4, 4, 6, 4, NULL, 'Spam quảng cáo', 'Tài khoản Hoàng Nam đăng link rác', '2026-05-16 19:24:06', 'Resolved', '', '2026-05-23 03:10:11'),
(5, 5, 7, NULL, NULL, 'Mạo danh', 'Tài khoản Bảo Trân dùng hình ảnh giả', '2026-05-16 17:24:06', 'Resolved', 'chắc chắn giả', '2026-05-23 03:09:28'),
(6, 6, 8, NULL, NULL, 'Vi phạm bản quyền', 'Hình ảnh của Anh Thư dùng chưa xin phép', '2026-05-15 22:24:06', 'Resolved', NULL, NULL),
(7, 20, 17, 35, NULL, 'Tôi không thích nội dung này', 'Tôi không thích nội dung này', '2026-05-23 00:06:22', 'Pending', NULL, NULL),
(8, 20, 17, 34, NULL, 'Bắt nạt hoặc quấy rối', 'Spam', '2026-05-23 00:08:10', 'Pending', NULL, NULL),
(9, 20, 17, 34, NULL, 'Thông tin sai lệch', 'Thông tin sai lệch', '2026-05-23 00:08:17', 'Pending', NULL, NULL),
(10, 20, 17, 34, NULL, 'Bạo lực, thù ghét hoặc bóc lột', 'Kêu gọi bạo lực', '2026-05-23 00:08:26', 'Pending', NULL, NULL),
(11, 20, 17, 33, NULL, 'Spam', 'Spam', '2026-05-23 00:08:38', 'Pending', NULL, NULL),
(12, 20, 17, 34, NULL, 'Spam', 'Spam', '2026-05-23 00:31:19', 'Pending', NULL, NULL),
(13, 20, 17, 34, NULL, 'Thông tin sai lệch', 'Gây hiểu nhầm', '2026-05-23 00:31:57', 'Pending', NULL, NULL),
(14, 20, 17, 32, NULL, 'Tôi không thích nội dung này', 'Tôi không thích nội dung này', '2026-05-23 00:33:40', 'Pending', NULL, NULL),
(15, 20, 17, 35, NULL, 'Tôi không thích nội dung này', 'Tôi không thích nội dung này', '2026-05-23 00:37:31', 'Pending', NULL, NULL);

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

--
-- Dumping data for table `search_history`
--

INSERT INTO `search_history` (`SearchID`, `UserID`, `Keyword`, `CreatedAt`) VALUES
(6, 15, 'mai', '2026-05-23 03:08:09');

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

--
-- Dumping data for table `userblocks`
--

INSERT INTO `userblocks` (`BlockID`, `BlockerUserID`, `BlockedUserID`, `CreatedAt`) VALUES
(1, 20, 23, '2026-05-23 00:06:14'),
(2, 20, 14, '2026-05-23 00:48:45');

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
  `IsActive` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `RoleID`, `Username`, `Email`, `PasswordHash`, `FullName`, `Bio`, `ProfilePictureUrl`, `CreatedAt`, `IsActive`) VALUES
(1, 2, 'tung_mtp', 'sontung@mtp.vn', '$2y$10$GxhVZ7I2KSsA5F3rfJroOezLiQC.M9hycbbuBzrWufe7taVfeZO.S', 'Nguyễn Thanh Tùng', 'Nơi này có anh... và đống post triệu view 🎵', 'Public/assets/img/avatar_sontung.jpg', '2026-05-16 21:55:28', 1),
(2, 2, 'hieuthuhai', 'hieu2@gmail.com', '$2y$10$GxhVZ7I2KSsA5F3rfJroOezLiQC.M9hycbbuBzrWufe7taVfeZO.S', 'Trần Minh Hiếu', 'Nghe nói đồ án nhóm 7 đang hot lắm đúng không? 🔥', 'Public/assets/img/avatar_hieu.jpg', '2026-05-16 21:55:28', 1),
(3, 2, 'den_vau', 'denvau@gmail.com', '$2y$10$GxhVZ7I2KSsA5F3rfJroOezLiQC.M9hycbbuBzrWufe7taVfeZO.S', 'Nguyễn Đức Cường', 'Đưa nhau đi trốn khỏi đống bug PHP thuần...', 'Public/assets/img/avatar_den.jpg', '2026-05-16 21:55:28', 1),
(4, 2, 'le_duong_bao_lam', 'dongnai_pro@gmail.com', '$2y$10$GxhVZ7I2KSsA5F3rfJroOezLiQC.M9hycbbuBzrWufe7taVfeZO.S', 'Lê Dương Bảo Lâm', 'Ra dẻ quá à! Đổ data mượt mà luôn coi coi!', 'Public/assets/img/avatar_lam.jpg', '2026-05-16 21:55:28', 1),
(5, 2, 'linh_chi', 'linhchi@gmail.com', '$2y$10$GxhVZ7I2KSsA5F3rfJroOezLiQC.M9hycbbuBzrWufe7taVfeZO.S', 'Linh Chi', 'A cooking lover 🍰', 'Public/assets/img/avatar-101.jpg', '2026-04-12 00:00:00', 1),
(6, 2, 'minh_nhat', 'nhatminh@gmail.com', '$2y$10$GxhVZ7I2KSsA5F3rfJroOezLiQC.M9hycbbuBzrWufe7taVfeZO.S', 'Minh Nhật', 'I make stuff', 'Public/assets/img/avatar-102.jpg', '2026-01-01 00:00:00', 1),
(7, 2, 'hoang_nam', 'namhoang@gmail.com', '$2y$10$GxhVZ7I2KSsA5F3rfJroOezLiQC.M9hycbbuBzrWufe7taVfeZO.S', 'Hoàng Nam', 'Diễn viên nhí Hoàng Nam', 'Public/assets/img/avatar-103.jpg', '2026-04-18 00:00:00', 1),
(8, 2, 'bao_tran', 'tranbao@gmail.com', '$2y$10$GxhVZ7I2KSsA5F3rfJroOezLiQC.M9hycbbuBzrWufe7taVfeZO.S', 'Bảo Trân', 'Work Life balance :)', 'Public/assets/img/avatar-104.jpg', '2026-02-15 00:00:00', 1),
(9, 2, 'anh_thu', 'thuanh@gmail.com', '$2y$10$GxhVZ7I2KSsA5F3rfJroOezLiQC.M9hycbbuBzrWufe7taVfeZO.S', 'Anh Thư', 'Vlogging is my everything', 'Public/assets/img/avatar-105.jpg', '2026-05-10 00:00:00', 1),
(10, 2, 'khanh_linh', 'khanhlinh@gmail.com', '$2y$10$GxhVZ7I2KSsA5F3rfJroOezLiQC.M9hycbbuBzrWufe7taVfeZO.S', 'Khánh Linh', 'Fashion & Lifestyle 🌸', 'Public/assets/img/avatar-106.jpg', '2026-04-12 00:00:00', 1),
(11, 1, 'bao_han', 'baohan@gmail.com', '$2y$10$GxhVZ7I2KSsA5F3rfJroOezLiQC.M9hycbbuBzrWufe7taVfeZO.S', 'Bảo Hân', 'Quản trị viên', 'Public/assets/img/avatar-107.jpg', '2026-01-01 00:00:00', 1),
(12, 2, 'nguoi_tinh_mua_dong', 'anthanh@gmail.com', '$2y$10$GxhVZ7I2KSsA5F3rfJroOezLiQC.M9hycbbuBzrWufe7taVfeZO.S', 'Như Quỳnh', 'Tài khoản này đã bị khóa vì lừa đảo tiền qua mạng', 'Public/assets/img/avatar-banned.jpg', '2026-04-18 00:00:00', 1),
(13, 1, 'super_admin', 'admin@archive.vn', '$2y$10$DZ/8w2wc3Vco2dILGtI2he.9TLoF7LpAKM272umPGOPrkCmRb5Rqq', 'Tổng Cục Kiểm Duyệt', 'Tài khoản hệ thống - Chuyên xử lý vi phạm và bảo trì Archive 🛡️', 'Public/assets/img/avatar-admin.jpg', '2026-01-01 00:00:00', 1),
(14, 2, 'maitrhog', 'trhogmai07@gmail.com', '0987654321', 'Trần Hồng Mai', NULL, NULL, '2026-05-17 18:50:17', 1),
(15, 2, 'myky10', 'kyoct@gmail.com', 'mykyoi0114', 'Ky Nguyen', 'Thủ khoa khối A (lime)', 'Public/uploads/avatars/avatar_6a10b73c25b616.94780470.jpeg', '2026-05-18 01:19:56', 1),
(16, 2, 'meomeo', 'meomeo@gmail.com', 'meomeocute', 'Tuyền Lùn', '', 'Public/uploads/avatars/avatar_6a0dede3c8be11.57400080.jpg', '2026-05-18 08:13:30', 0),
(17, 2, 'ghan_ngn', 'nguyengiahan2202@gmail.com', '$2y$10$ECybNr.kzjIE2rLoUclkV.WplNiV/XxFr9mboqf7KTs.3uA4XNFEm', 'Hân Nguyễn', 'siu nhân gao', 'Public/uploads/avatars/avatar_6a0eca28476a75.27111313.jpeg', '2026-05-20 13:46:34', 1),
(20, 2, 'xinchao', 'trhoai07@gmail.com', '$2y$10$Waz3dqVuJcsxM69I67wGUOo0EK3FecksHP2QvVUGxPuXFUcVPXrnO', 'Trần Hồng Mai', 'Xin chào những người thương của mai', 'Public/uploads/avatars/avatar_6a0ec03fbdb2b8.90031045.jpg', '2026-05-20 14:17:30', 1),
(21, 2, 'phucminh', 'trmihphuc07@gmail.com', '$2y$10$FiOi3XATl.kSjD0ga50q7u.IYvmy4F68fshlRvxl39ghv8wQ3gwhK', 'Trần Minh Phúc', '', 'Public/uploads/avatars/avatar_6a0d6962ec7700.27355360.png', '2026-05-20 14:52:26', 1),
(22, 2, 'ponpon', 'phong123@gmail.com', '$2y$10$AtR7uydfr.BPUMSNPi5nDeHR.HZhUPVfAqzKMZlZ/RjTigzWxiwNq', 'Đỗ Tuấn Phong', NULL, NULL, '2026-05-20 15:14:08', 0),
(23, 2, 'sulele', 'sulele@gmail.com', '$2y$10$x8aL0Zl2akDC1X1BJJ90f.SJd8tS0KmangDotBuF5X8I/PIY4bnU6', 'Lê Quan Su', NULL, NULL, '2026-05-22 22:20:04', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`CommentID`),
  ADD KEY `PostID` (`PostID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `ParentCommentID` (`ParentCommentID`);

--
-- Indexes for table `follows`
--
ALTER TABLE `follows`
  ADD PRIMARY KEY (`FollowerID`,`FollowedID`),
  ADD KEY `FollowedID` (`FollowedID`);

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
  ADD KEY `PostID` (`PostID`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`NotificationID`),
  ADD KEY `NotificationTypeID` (`NotificationTypeID`),
  ADD KEY `ReceiverUserID` (`ReceiverUserID`),
  ADD KEY `SenderUserID` (`SenderUserID`),
  ADD KEY `PostID` (`PostID`),
  ADD KEY `CommentID` (`CommentID`);

--
-- Indexes for table `notificationtypes`
--
ALTER TABLE `notificationtypes`
  ADD PRIMARY KEY (`NotificationTypeID`),
  ADD UNIQUE KEY `TypeName` (`TypeName`);

--
-- Indexes for table `posthashtags`
--
ALTER TABLE `posthashtags`
  ADD PRIMARY KEY (`PostID`,`HashtagID`),
  ADD KEY `fk_posthashtags_hashtag` (`HashtagID`);

--
-- Indexes for table `postimages`
--
ALTER TABLE `postimages`
  ADD PRIMARY KEY (`PostImageID`),
  ADD KEY `PostID` (`PostID`);

--
-- Indexes for table `postpreferences`
--
ALTER TABLE `postpreferences`
  ADD PRIMARY KEY (`PreferenceID`),
  ADD UNIQUE KEY `unique_post_preference` (`UserID`,`PostID`,`PreferenceType`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`PostID`),
  ADD KEY `posts_ibfk_1` (`UserID`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`ReportID`),
  ADD KEY `FK_Reports_Reporter` (`ReporterUserID`),
  ADD KEY `FK_Reports_ReportedUser` (`ReportedUserID`),
  ADD KEY `FK_Reports_Posts` (`PostID`),
  ADD KEY `FK_Reports_Comments` (`CommentID`);

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
  ADD UNIQUE KEY `unique_user_block` (`BlockerUserID`,`BlockedUserID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `FK_Users_Roles` (`RoleID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `CommentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `hashtags`
--
ALTER TABLE `hashtags`
  MODIFY `HashtagID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `NotificationID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `notificationtypes`
--
ALTER TABLE `notificationtypes`
  MODIFY `NotificationTypeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `postimages`
--
ALTER TABLE `postimages`
  MODIFY `PostImageID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `postpreferences`
--
ALTER TABLE `postpreferences`
  MODIFY `PreferenceID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `PostID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `ReportID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `RoleID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `search_history`
--
ALTER TABLE `search_history`
  MODIFY `SearchID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `userblocks`
--
ALTER TABLE `userblocks`
  MODIFY `BlockID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`PostID`) REFERENCES `posts` (`PostID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE,
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
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`NotificationTypeID`) REFERENCES `notificationtypes` (`NotificationTypeID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`ReceiverUserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_3` FOREIGN KEY (`SenderUserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_4` FOREIGN KEY (`PostID`) REFERENCES `posts` (`PostID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_5` FOREIGN KEY (`CommentID`) REFERENCES `comments` (`CommentID`) ON DELETE CASCADE ON UPDATE CASCADE;

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
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `FK_Reports_Comments` FOREIGN KEY (`CommentID`) REFERENCES `comments` (`CommentID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_Reports_Posts` FOREIGN KEY (`PostID`) REFERENCES `posts` (`PostID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_Reports_ReportedUser` FOREIGN KEY (`ReportedUserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_Reports_Reporter` FOREIGN KEY (`ReporterUserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `search_history`
--
ALTER TABLE `search_history`
  ADD CONSTRAINT `fk_search_user` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `FK_Users_Roles` FOREIGN KEY (`RoleID`) REFERENCES `roles` (`RoleID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
