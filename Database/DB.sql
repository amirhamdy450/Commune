-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 28, 2026 at 11:30 PM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `commune`
--

-- --------------------------------------------------------

--
-- Table structure for table `blocked_users`
--

CREATE TABLE `blocked_users` (
  `id` int NOT NULL,
  `BlockerUID` int NOT NULL COMMENT 'User who is doing the blocking',
  `BlockedUID` int NOT NULL COMMENT 'User who is being blocked',
  `BlockedOn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `id` int NOT NULL,
  `comment` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `LikeCounter` int NOT NULL DEFAULT '0',
  `ReplyCounter` int NOT NULL DEFAULT '0',
  `PostID` int NOT NULL,
  `UID` int NOT NULL,
  `Date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comments_likes`
--

CREATE TABLE `comments_likes` (
  `id` int NOT NULL,
  `CommentID` int NOT NULL,
  `UID` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comments_replies`
--

CREATE TABLE `comments_replies` (
  `id` int NOT NULL,
  `CommentID` int NOT NULL,
  `UID` int NOT NULL,
  `Reply` text NOT NULL,
  `Tagged` int DEFAULT NULL COMMENT 'id of the user tagged in this reply if any',
  `LikeCounter` int NOT NULL DEFAULT '0',
  `Date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comments_replies_likes`
--

CREATE TABLE `comments_replies_likes` (
  `id` int NOT NULL,
  `UID` int NOT NULL,
  `ReplyID` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `id` int NOT NULL,
  `code` char(2) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `followers`
--

CREATE TABLE `followers` (
  `id` int NOT NULL,
  `FollowerID` int NOT NULL,
  `UserID` int NOT NULL,
  `FollowedOn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `likes`
--

CREATE TABLE `likes` (
  `id` int NOT NULL,
  `PostID` int NOT NULL,
  `UID` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `ToUID` int NOT NULL COMMENT 'The user receiving the notification',
  `FromUID` int DEFAULT NULL COMMENT 'The actor. NULL = System Notification',
  `Type` int NOT NULL COMMENT '1=Like, 2=Comment, 3=Reply, 4=Follow, 7=Mention, 10=System, 11=Security',
  `ReferenceID` int DEFAULT NULL COMMENT 'ID of Post/Comment. NULL for general alerts',
  `MetaInfo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Custom text or JSON for system messages',
  `IsRead` tinyint(1) NOT NULL DEFAULT '0',
  `Date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires` bigint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `email_verif_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `verification_requests`
--

CREATE TABLE `verification_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `UID` int NOT NULL,
  `Reason` text NOT NULL,
  `Status` tinyint NOT NULL DEFAULT '0' COMMENT '0=pending, 1=approved, 2=rejected',
  `SubmittedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ReviewedAt` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_verif_uid` (`UID`),
  CONSTRAINT `fk_verif_uid` FOREIGN KEY (`UID`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int NOT NULL,
  `Content` varchar(600) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Type` int NOT NULL COMMENT '1 for text only , 2 for images and 3 for documents\r\n',
  `MediaFolder` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `LikeCounter` int NOT NULL DEFAULT '0',
  `CommentCounter` int NOT NULL DEFAULT '0',
  `Date` datetime NOT NULL,
  `Status` int NOT NULL,
  `UID` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `saved_posts`
--

CREATE TABLE `saved_posts` (
  `id` int NOT NULL,
  `UID` int NOT NULL COMMENT 'User who saved the post',
  `PostID` int NOT NULL,
  `SavedOn` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tokens`
--

CREATE TABLE `tokens` (
  `id` int NOT NULL,
  `UID` int NOT NULL,
  `Token` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Token_2` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `IP` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `UserAgent` text COLLATE utf8mb4_general_ci NOT NULL,
  `UpdatedOn` bigint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `topic_cache`
--

CREATE TABLE `topic_cache` (
  `id` int NOT NULL,
  `Query` varchar(255) NOT NULL,
  `Results` text NOT NULL,
  `LastCalculated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `Fname` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `Lname` varchar(40) COLLATE utf8mb4_general_ci NOT NULL,
  `Username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `Email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `Password` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `BirthDay` date DEFAULT NULL,
  `Gender` tinyint NOT NULL,
  `Bio` text COLLATE utf8mb4_general_ci,
  `CountryID` int NOT NULL,
  `ProfilePic` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `CoverPhoto` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Followers` int NOT NULL DEFAULT '0',
  `Following` int NOT NULL DEFAULT '0',
  `Privilege` int NOT NULL DEFAULT '0',
  `IsVerified` tinyint NOT NULL DEFAULT '0',
  `IsBlueTick` tinyint NOT NULL DEFAULT '0',
  `IsBanned` tinyint NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `user_bans`
-- Type: 0=Warning, 1=Temporary Ban, 2=Permanent Ban
--

CREATE TABLE `user_bans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `UID` int NOT NULL,
  `Type` tinyint NOT NULL DEFAULT '1' COMMENT '0=Warning, 1=TempBan, 2=PermanentBan',
  `Reason` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `IssuedBy` int NOT NULL,
  `StartDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `EndDate` datetime DEFAULT NULL COMMENT 'NULL = permanent, only applies to TempBan',
  `IsActive` tinyint NOT NULL DEFAULT '1',
  `RefPosts` json DEFAULT NULL COMMENT 'Array of post IDs referenced in this action',
  `RefComments` json DEFAULT NULL COMMENT 'Array of comment IDs referenced in this action',
  PRIMARY KEY (`id`),
  KEY `UID` (`UID`),
  KEY `IsActive` (`IsActive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `blocked_users`
--
ALTER TABLE `blocked_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UserBlock` (`BlockerUID`,`BlockedUID`),
  ADD KEY `commune_blocked_BlockerUID` (`BlockerUID`),
  ADD KEY `commune_blocked_BlockedUID` (`BlockedUID`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commune_comments_PostID` (`PostID`),
  ADD KEY `commune_comments_UID` (`UID`);

--
-- Indexes for table `comments_likes`
--
ALTER TABLE `comments_likes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `Commune_comments_likes_CommentID` (`CommentID`),
  ADD KEY `Commune_comments_likes_UID` (`UID`);

--
-- Indexes for table `comments_replies`
--
ALTER TABLE `comments_replies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commune_comments_replies_CommentID` (`CommentID`),
  ADD KEY `commune_comments_replies_UID` (`UID`),
  ADD KEY `commune_comments_replies_Tagged` (`Tagged`);

--
-- Indexes for table `comments_replies_likes`
--
ALTER TABLE `comments_replies_likes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commune_commens_replies_likes_UserID` (`UID`),
  ADD KEY `commune_commens_replies_likes_ReplyID` (`ReplyID`);

--
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `followers`
--
ALTER TABLE `followers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commune_followers_follower_id` (`FollowerID`),
  ADD KEY `commune_followers_user_id` (`UserID`);

--
-- Indexes for table `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `Commune_Likes_PostID` (`PostID`),
  ADD KEY `Commune_Likes_UID` (`UID`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notif_to` (`ToUID`),
  ADD KEY `idx_notif_from` (`FromUID`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `token` (`token`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commune_posts_UID` (`UID`);
ALTER TABLE `posts` ADD FULLTEXT KEY `ft_content` (`Content`);

--
-- Indexes for table `saved_posts`
--
ALTER TABLE `saved_posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UserSave` (`UID`,`PostID`),
  ADD KEY `commune_saved_posts_UID` (`UID`),
  ADD KEY `commune_saved_posts_PostID` (`PostID`);

--
-- Indexes for table `tokens`
--
ALTER TABLE `tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commune_tokens_UID` (`UID`);

--
-- Indexes for table `topic_cache`
--
ALTER TABLE `topic_cache`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_query` (`Query`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commune_users_CountryID` (`CountryID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `blocked_users`
--
ALTER TABLE `blocked_users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comments_likes`
--
ALTER TABLE `comments_likes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comments_replies`
--
ALTER TABLE `comments_replies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comments_replies_likes`
--
ALTER TABLE `comments_replies_likes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `followers`
--
ALTER TABLE `followers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `likes`
--
ALTER TABLE `likes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `saved_posts`
--
ALTER TABLE `saved_posts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tokens`
--
ALTER TABLE `tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `topic_cache`
--
ALTER TABLE `topic_cache`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `blocked_users`
--
ALTER TABLE `blocked_users`
  ADD CONSTRAINT `commune_blocked_BlockedUID` FOREIGN KEY (`BlockedUID`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `commune_blocked_BlockerUID` FOREIGN KEY (`BlockerUID`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `commune_comments_PostID` FOREIGN KEY (`PostID`) REFERENCES `posts` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `commune_comments_UID` FOREIGN KEY (`UID`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `comments_likes`
--
ALTER TABLE `comments_likes`
  ADD CONSTRAINT `Commune_comments_likes_CommentID` FOREIGN KEY (`CommentID`) REFERENCES `comments` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `Commune_comments_likes_UID` FOREIGN KEY (`UID`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `comments_replies`
--
ALTER TABLE `comments_replies`
  ADD CONSTRAINT `commune_comments_replies_CommentID` FOREIGN KEY (`CommentID`) REFERENCES `comments` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `commune_comments_replies_Tagged` FOREIGN KEY (`Tagged`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `commune_comments_replies_UID` FOREIGN KEY (`UID`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `comments_replies_likes`
--
ALTER TABLE `comments_replies_likes`
  ADD CONSTRAINT `commune_commens_replies_likes_ReplyID` FOREIGN KEY (`ReplyID`) REFERENCES `comments_replies` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `commune_commens_replies_likes_UserID` FOREIGN KEY (`UID`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `followers`
--
ALTER TABLE `followers`
  ADD CONSTRAINT `commune_followers_follower_id` FOREIGN KEY (`FollowerID`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `commune_followers_user_id` FOREIGN KEY (`UserID`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `likes`
--
ALTER TABLE `likes`
  ADD CONSTRAINT `commune_Likes_PostID` FOREIGN KEY (`PostID`) REFERENCES `posts` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  ADD CONSTRAINT `commune_Likes_UID` FOREIGN KEY (`UID`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_from` FOREIGN KEY (`FromUID`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_notif_to` FOREIGN KEY (`ToUID`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `commune_posts_UID` FOREIGN KEY (`UID`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `saved_posts`
--
ALTER TABLE `saved_posts`
  ADD CONSTRAINT `commune_saved_posts_PostID` FOREIGN KEY (`PostID`) REFERENCES `posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `commune_saved_posts_UID` FOREIGN KEY (`UID`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tokens`
--
ALTER TABLE `tokens`
  ADD CONSTRAINT `commune_tokens_UID` FOREIGN KEY (`UID`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `commune_users_CountryID` FOREIGN KEY (`CountryID`) REFERENCES `countries` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
