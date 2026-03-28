-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 24, 2025 at 03:08 PM
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

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `comment`, `LikeCounter`, `ReplyCounter`, `PostID`, `UID`, `Date`) VALUES
(1, 'What year is this? LOL', 1, 1, 9, 2, '2025-08-01 16:38:30'),
(2, 'BEAST MODE', 0, 0, 11, 1, '2025-08-01 16:38:30'),
(3, 'LOOKS DELICIOUS !\r\n', 1, 0, 6, 1, '2025-08-01 16:38:30'),
(4, 'hahahah\r\n', 1, 0, 1, 4, '2025-08-01 16:38:30'),
(5, 'sdf', 1, 2, 13, 4, '2025-08-01 16:38:30'),
(6, 'this looks cool', 0, 0, 12, 1, '2025-08-01 16:38:30'),
(17, 'asd', 0, 0, 16, 1, '2025-08-01 16:38:30'),
(18, 'second comment', 0, 8, 16, 1, '2025-08-01 16:38:30'),
(27, 'asd', 0, 0, 16, 1, '2025-08-01 16:38:30'),
(28, 'asdasdasd', 0, 0, 16, 1, '2025-08-01 16:38:30'),
(29, 'test', 0, 0, 13, 1, '2025-08-01 16:38:30'),
(30, 'fc', 0, 0, 13, 1, '2025-08-01 16:38:30'),
(31, 'dsd', 0, 0, 13, 1, '2025-08-01 16:38:30'),
(32, 'the big bang', 0, 0, 13, 1, '2025-08-01 16:38:30'),
(33, 'the big bang is a hoax !', 0, 0, 9, 2, '2025-08-01 16:38:30'),
(34, 'hery', 0, 0, 16, 1, '2025-08-02 23:38:30'),
(36, 'first try', 0, 0, 12, 1, '2025-08-17 02:17:07'),
(37, 'hio\r\n', 0, 0, 46, 12, '2025-09-09 02:11:03'),
(38, 'commenting on my own post', 0, 0, 44, 12, '2025-10-11 14:18:01'),
(39, 'just commenting to test something that is also long but this time in the comment section', 0, 0, 48, 12, '2025-10-16 02:22:50'),
(42, 'test', 0, 0, 1, 12, '2025-10-21 02:54:59'),
(43, 'test', 0, 1, 72, 12, '2025-11-15 02:28:54'),
(47, 'testing notifications', 0, 0, 98, 12, '2025-11-23 02:32:16');

-- --------------------------------------------------------

--
-- Table structure for table `comments_likes`
--

CREATE TABLE `comments_likes` (
  `id` int NOT NULL,
  `CommentID` int NOT NULL,
  `UID` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `comments_likes`
--

INSERT INTO `comments_likes` (`id`, `CommentID`, `UID`) VALUES
(165, 5, 1),
(192, 1, 1),
(194, 3, 12),
(197, 4, 12);

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

--
-- Dumping data for table `comments_replies`
--

INSERT INTO `comments_replies` (`id`, `CommentID`, `UID`, `Reply`, `Tagged`, `LikeCounter`, `Date`) VALUES
(1, 18, 1, 'test comment 1', NULL, 1, '2025-08-09 03:30:50'),
(2, 18, 3, 'replying to amir', 1, 0, '2025-08-09 03:37:04'),
(3, 18, 1, 'replying back', 3, 0, '2025-08-15 02:52:29'),
(8, 18, 12, 'unfazed', 1, 0, '2025-08-26 23:05:36'),
(9, 5, 12, 'hello teacher', NULL, 0, '2025-10-14 21:32:24'),
(10, 5, 12, 'hello teacher', NULL, 0, '2025-10-14 21:32:33'),
(11, 1, 12, 'wydc , it looks cringe anyways', NULL, 0, '2025-10-14 21:34:10'),
(16, 43, 12, 'test reply', NULL, 0, '2025-11-15 02:29:11');

-- --------------------------------------------------------

--
-- Table structure for table `comments_replies_likes`
--

CREATE TABLE `comments_replies_likes` (
  `id` int NOT NULL,
  `UID` int NOT NULL,
  `ReplyID` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `comments_replies_likes`
--

INSERT INTO `comments_replies_likes` (`id`, `UID`, `ReplyID`) VALUES
(16, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `id` int NOT NULL,
  `code` char(2) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` (`id`, `code`, `name`) VALUES
(1, 'AF', 'Afghanistan'),
(2, 'AX', 'Åland Islands'),
(3, 'AL', 'Albania'),
(4, 'DZ', 'Algeria'),
(5, 'AS', 'American Samoa'),
(6, 'AD', 'Andorra'),
(7, 'AO', 'Angola'),
(8, 'AI', 'Anguilla'),
(9, 'AQ', 'Antarctica'),
(10, 'AG', 'Antigua & Barbuda'),
(11, 'AR', 'Argentina'),
(12, 'AM', 'Armenia'),
(13, 'AW', 'Aruba'),
(14, 'AU', 'Australia'),
(15, 'AT', 'Austria'),
(16, 'AZ', 'Azerbaijan'),
(17, 'BS', 'Bahamas'),
(18, 'BH', 'Bahrain'),
(19, 'BD', 'Bangladesh'),
(20, 'BB', 'Barbados'),
(21, 'BY', 'Belarus'),
(22, 'BE', 'Belgium'),
(23, 'BZ', 'Belize'),
(24, 'BJ', 'Benin'),
(25, 'BM', 'Bermuda'),
(26, 'BT', 'Bhutan'),
(27, 'BO', 'Bolivia'),
(28, 'BA', 'Bosnia & Herzegovina'),
(29, 'BW', 'Botswana'),
(30, 'BV', 'Bouvet Island'),
(31, 'BR', 'Brazil'),
(32, 'IO', 'British Indian Ocean Territory'),
(33, 'BN', 'Brunei'),
(34, 'BG', 'Bulgaria'),
(35, 'BF', 'Burkina Faso'),
(36, 'BI', 'Burundi'),
(37, 'CV', 'Cape Verde'),
(38, 'KH', 'Cambodia'),
(39, 'CM', 'Cameroon'),
(40, 'CA', 'Canada'),
(41, 'BQ', 'Caribbean Netherlands'),
(42, 'KY', 'Cayman Islands'),
(43, 'CF', 'Central African Republic'),
(44, 'TD', 'Chad'),
(45, 'CL', 'Chile'),
(46, 'CN', 'China'),
(47, 'CX', 'Christmas Island'),
(48, 'CC', 'Cocos (Keeling) Islands'),
(49, 'CO', 'Colombia'),
(50, 'KM', 'Comoros'),
(51, 'CG', 'Congo - Brazzaville'),
(52, 'CD', 'Congo - Kinshasa'),
(53, 'CK', 'Cook Islands'),
(54, 'CR', 'Costa Rica'),
(55, 'HR', 'Croatia'),
(56, 'CU', 'Cuba'),
(57, 'CW', 'Curaçao'),
(58, 'CY', 'Cyprus'),
(59, 'CZ', 'Czechia'),
(60, 'CI', 'Côte d’Ivoire'),
(61, 'DK', 'Denmark'),
(62, 'DJ', 'Djibouti'),
(63, 'DM', 'Dominica'),
(64, 'DO', 'Dominican Republic'),
(65, 'EC', 'Ecuador'),
(66, 'EG', 'Egypt'),
(67, 'SV', 'El Salvador'),
(68, 'GQ', 'Equatorial Guinea'),
(69, 'ER', 'Eritrea'),
(70, 'EE', 'Estonia'),
(71, 'SZ', 'Eswatini'),
(72, 'ET', 'Ethiopia'),
(73, 'FK', 'Falkland Islands (Islas Malvinas)'),
(74, 'FO', 'Faroe Islands'),
(75, 'FJ', 'Fiji'),
(76, 'FI', 'Finland'),
(77, 'FR', 'France'),
(78, 'GF', 'French Guiana'),
(79, 'PF', 'French Polynesia'),
(80, 'TF', 'French Southern Territories'),
(81, 'GA', 'Gabon'),
(82, 'GM', 'Gambia'),
(83, 'GE', 'Georgia'),
(84, 'DE', 'Germany'),
(85, 'GH', 'Ghana'),
(86, 'GI', 'Gibraltar'),
(87, 'GR', 'Greece'),
(88, 'GL', 'Greenland'),
(89, 'GD', 'Grenada'),
(90, 'GP', 'Guadeloupe'),
(91, 'GU', 'Guam'),
(92, 'GT', 'Guatemala'),
(93, 'GG', 'Guernsey'),
(94, 'GN', 'Guinea'),
(95, 'GW', 'Guinea-Bissau'),
(96, 'GY', 'Guyana'),
(97, 'HT', 'Haiti'),
(98, 'HM', 'Heard & McDonald Islands'),
(99, 'HN', 'Honduras'),
(100, 'HK', 'Hong Kong'),
(101, 'HU', 'Hungary'),
(102, 'IS', 'Iceland'),
(103, 'IN', 'India'),
(104, 'ID', 'Indonesia'),
(105, 'IR', 'Iran'),
(106, 'IQ', 'Iraq'),
(107, 'IE', 'Ireland'),
(108, 'IM', 'Isle of Man'),
(109, 'IL', 'Israel'),
(110, 'IT', 'Italy'),
(111, 'JM', 'Jamaica'),
(112, 'JP', 'Japan'),
(113, 'JE', 'Jersey'),
(114, 'JO', 'Jordan'),
(115, 'KZ', 'Kazakhstan'),
(116, 'KE', 'Kenya'),
(117, 'KI', 'Kiribati'),
(118, 'KP', 'North Korea'),
(119, 'KR', 'South Korea'),
(120, 'XK', 'Kosovo'),
(121, 'KW', 'Kuwait'),
(122, 'KG', 'Kyrgyzstan'),
(123, 'LA', 'Laos'),
(124, 'LV', 'Latvia'),
(125, 'LB', 'Lebanon'),
(126, 'LS', 'Lesotho'),
(127, 'LR', 'Liberia'),
(128, 'LY', 'Libya'),
(129, 'LI', 'Liechtenstein'),
(130, 'LT', 'Lithuania'),
(131, 'LU', 'Luxembourg'),
(132, 'MO', 'Macao'),
(133, 'MK', 'North Macedonia'),
(134, 'MG', 'Madagascar'),
(135, 'MW', 'Malawi'),
(136, 'MY', 'Malaysia'),
(137, 'MV', 'Maldives'),
(138, 'ML', 'Mali'),
(139, 'MT', 'Malta'),
(140, 'MH', 'Marshall Islands'),
(141, 'MQ', 'Martinique'),
(142, 'MR', 'Mauritania'),
(143, 'MU', 'Mauritius'),
(144, 'YT', 'Mayotte'),
(145, 'MX', 'Mexico'),
(146, 'FM', 'Micronesia'),
(147, 'MD', 'Moldova'),
(148, 'MC', 'Monaco'),
(149, 'MN', 'Mongolia'),
(150, 'ME', 'Montenegro'),
(151, 'MS', 'Montserrat'),
(152, 'MA', 'Morocco'),
(153, 'MZ', 'Mozambique'),
(154, 'MM', 'Myanmar (Burma)'),
(155, 'NA', 'Namibia'),
(156, 'NR', 'Nauru'),
(157, 'NP', 'Nepal'),
(158, 'NL', 'Netherlands'),
(159, 'AN', 'Curaçao'),
(160, 'NC', 'New Caledonia'),
(161, 'NZ', 'New Zealand'),
(162, 'NI', 'Nicaragua'),
(163, 'NE', 'Niger'),
(164, 'NG', 'Nigeria'),
(165, 'NU', 'Niue'),
(166, 'NF', 'Norfolk Island'),
(167, 'MP', 'Northern Mariana Islands'),
(168, 'NO', 'Norway'),
(169, 'OM', 'Oman'),
(170, 'PK', 'Pakistan'),
(171, 'PW', 'Palau'),
(172, 'PS', 'Palestine'),
(173, 'PA', 'Panama'),
(174, 'PG', 'Papua New Guinea'),
(175, 'PY', 'Paraguay'),
(176, 'PE', 'Peru'),
(177, 'PH', 'Philippines'),
(178, 'PN', 'Pitcairn Islands'),
(179, 'PL', 'Poland'),
(180, 'PT', 'Portugal'),
(181, 'PR', 'Puerto Rico'),
(182, 'QA', 'Qatar'),
(183, 'RE', 'Réunion'),
(184, 'RO', 'Romania'),
(185, 'RU', 'Russia'),
(186, 'RW', 'Rwanda'),
(187, 'BL', 'St. Barthélemy'),
(188, 'SH', 'St. Helena'),
(189, 'KN', 'St. Kitts & Nevis'),
(190, 'LC', 'St. Lucia'),
(191, 'MF', 'St. Martin'),
(192, 'PM', 'St. Pierre & Miquelon'),
(193, 'VC', 'St. Vincent & Grenadines'),
(194, 'WS', 'Samoa'),
(195, 'SM', 'San Marino'),
(196, 'ST', 'São Tomé & Príncipe'),
(197, 'SA', 'Saudi Arabia'),
(198, 'SN', 'Senegal'),
(199, 'RS', 'Serbia'),
(200, 'CS', 'Serbia'),
(201, 'SC', 'Seychelles'),
(202, 'SL', 'Sierra Leone'),
(203, 'SG', 'Singapore'),
(204, 'SX', 'Sint Maarten'),
(205, 'SK', 'Slovakia'),
(206, 'SI', 'Slovenia'),
(207, 'SB', 'Solomon Islands'),
(208, 'SO', 'Somalia'),
(209, 'ZA', 'South Africa'),
(210, 'GS', 'South Georgia & South Sandwich Islands'),
(211, 'SS', 'South Sudan'),
(212, 'ES', 'Spain'),
(213, 'LK', 'Sri Lanka'),
(214, 'SD', 'Sudan'),
(215, 'SR', 'Suriname'),
(216, 'SJ', 'Svalbard & Jan Mayen'),
(217, 'SE', 'Sweden'),
(218, 'CH', 'Switzerland'),
(219, 'SY', 'Syria'),
(220, 'TW', 'Taiwan'),
(221, 'TJ', 'Tajikistan'),
(222, 'TZ', 'Tanzania'),
(223, 'TH', 'Thailand'),
(224, 'TL', 'Timor-Leste'),
(225, 'TG', 'Togo'),
(226, 'TK', 'Tokelau'),
(227, 'TO', 'Tonga'),
(228, 'TT', 'Trinidad & Tobago'),
(229, 'TN', 'Tunisia'),
(230, 'TR', 'Türkiye'),
(231, 'TM', 'Turkmenistan'),
(232, 'TC', 'Turks & Caicos Islands'),
(233, 'TV', 'Tuvalu'),
(234, 'UM', 'U.S. Outlying Islands'),
(235, 'UG', 'Uganda'),
(236, 'UA', 'Ukraine'),
(237, 'AE', 'United Arab Emirates'),
(238, 'GB', 'United Kingdom'),
(239, 'US', 'United States'),
(240, 'UY', 'Uruguay'),
(241, 'UZ', 'Uzbekistan'),
(242, 'VU', 'Vanuatu'),
(243, 'VA', 'Vatican City'),
(244, 'VE', 'Venezuela'),
(245, 'VN', 'Vietnam'),
(246, 'VG', 'British Virgin Islands'),
(247, 'VI', 'U.S. Virgin Islands'),
(248, 'WF', 'Wallis & Futuna'),
(249, 'EH', 'Western Sahara'),
(250, 'YE', 'Yemen'),
(251, 'ZM', 'Zambia'),
(252, 'ZW', 'Zimbabwe');

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

--
-- Dumping data for table `followers`
--

INSERT INTO `followers` (`id`, `FollowerID`, `UserID`, `FollowedOn`) VALUES
(1, 3, 12, '2025-10-11 15:58:50'),
(2, 2, 12, '2025-10-11 23:07:32'),
(3, 12, 2, '2025-10-12 00:48:33'),
(25, 12, 4, '2025-10-28 03:12:53'),
(27, 12, 1, '2025-11-22 18:43:30');

-- --------------------------------------------------------

--
-- Table structure for table `likes`
--

CREATE TABLE `likes` (
  `id` int NOT NULL,
  `PostID` int NOT NULL,
  `UID` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `likes`
--

INSERT INTO `likes` (`id`, `PostID`, `UID`) VALUES
(115, 12, 2),
(133, 9, 1),
(134, 6, 1),
(158, 1, 1),
(161, 3, 1),
(165, 13, 1),
(170, 8, 1),
(191, 1, 12),
(210, 47, 12),
(213, 9, 12),
(227, 7, 12),
(231, 42, 12),
(232, 46, 12),
(238, 98, 12);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `ToUID` int NOT NULL COMMENT 'The user receiving the notification',
  `FromUID` int DEFAULT NULL COMMENT 'The actor. NULL = System Notification',
  `Type` int NOT NULL COMMENT '1=Like, 2=Comment, 3=Reply, 4=Follow, 10=System, 11=Security',
  `ReferenceID` int DEFAULT NULL COMMENT 'ID of Post/Comment. NULL for general alerts',
  `MetaInfo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Custom text or JSON for system messages',
  `IsRead` tinyint(1) NOT NULL DEFAULT '0',
  `Date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `ToUID`, `FromUID`, `Type`, `ReferenceID`, `MetaInfo`, `IsRead`, `Date`) VALUES
(3, 1, 12, 1, 98, NULL, 1, '2025-11-23 02:32:00'),
(4, 1, 12, 2, 98, NULL, 1, '2025-11-23 02:32:16');

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

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `Content`, `Type`, `MediaFolder`, `LikeCounter`, `CommentCounter`, `Date`, `Status`, `UID`) VALUES
(1, 'Look at my new house !', 2, 'MediaFolders/posts/1725282195', 2, 2, '2024-09-02 16:03:15', 1, 1),
(3, 'الحساب يا غالي', 3, 'MediaFolders/posts/1725282423', 1, 0, '2024-09-02 16:07:03', 1, 1),
(4, 'hi new here , like please!', 1, '', 0, 0, '2024-09-02 16:12:03', 1, 3),
(6, 'Eating out ! el pizza gamda', 2, 'MediaFolders/posts/1725283922', 1, 1, '2024-09-02 16:32:02', 1, 2),
(7, 'Ai  creation', 2, 'MediaFolders/posts/1725488099', 1, 0, '2024-09-05 01:14:59', 1, 4),
(8, 'LE 2', 2, 'MediaFolders/posts/1725488144', 1, 0, '2024-09-05 01:15:44', 1, 4),
(9, 'Creations of AI', 2, 'MediaFolders/posts/1725488164', 2, 2, '2024-09-05 01:16:04', 1, 1),
(10, 'Newsletter', 3, 'MediaFolders/posts/1725488465', 0, 0, '2024-09-05 01:21:05', 1, 1),
(11, 'supplement discount', 2, 'MediaFolders/posts/1725488549', 0, 1, '2024-09-05 01:22:29', 1, 3),
(12, 'tyest', 2, 'MediaFolders/posts/1725959096', 1, 2, '2024-09-10 12:04:56', 1, 4),
(13, 'ghugygu', 3, 'MediaFolders/posts/1733824399', 1, 5, '2024-12-10 11:53:19', 1, 4),
(16, 'post', 1, 'MediaFolders/posts/1753361268', 0, 5, '2025-07-24 15:47:48', 1, 1),
(19, 'asasd', 2, 'MediaFolders/posts/1753616489', 0, 0, '2025-07-27 14:41:29', 0, 4),
(20, 'ghh', 2, 'MediaFolders/posts/1753616577', 0, 0, '2025-07-27 14:42:57', 0, 4),
(34, 'checking functionality', 1, 'MediaFolders/posts/17550465871689be2bbc4917', 0, 0, '2025-08-13 02:56:27', 1, 1),
(36, 'i hacked saikoro and that is the result:', 3, 'MediaFolders/posts/17550752051689c5285d0ba5', 0, 0, '2025-08-13 10:53:25', 1, 1),
(42, 'my  first public post !', 1, 'MediaFolders/posts/17562418211268ae1f9ddb016', 1, 0, '2025-08-26 22:57:01', 0, 12),
(43, 'image tst', 2, 'MediaFolders/posts/17562462391268ae30df98551', 0, 0, '2025-08-27 00:10:39', 0, 12),
(44, 'TEST', 2, 'MediaFolders/posts/17562476671268ae3673c939d', 0, 1, '2025-08-27 00:34:27', 1, 12),
(45, 'test 3', 2, 'MediaFolders/posts/17562509431268ae433f01dfe', 0, 0, '2025-08-27 01:29:03', 1, 12),
(46, 'aasd', 3, 'MediaFolders/posts/17562527331268ae4a3ddd7ed', 1, 1, '2025-08-27 01:58:53', 1, 12),
(47, 'asad', 1, 'MediaFolders/posts/17604758531268eebacd148d5', 1, 0, '2025-10-14 23:04:13', 0, 12),
(48, 'I\'m testing writing a bigger post than the usual to see how the UI would look like with it after i made some CSS changes in order to make it look more adaptative and overall more responsive so i\'m just writing this long unnecessary post', 1, 'MediaFolders/posts/17605686091268f02521e7e19', 0, 1, '2025-10-16 00:50:09', 0, 12),
(57, 'Google has a new app called \"SMART AI\"', 1, 'MediaFolders/posts/176278752112691200c11286c', 0, 0, '2025-11-10 17:12:01', 1, 12),
(58, 'Google new Smart AI app is crazy', 1, 'MediaFolders/posts/176278753412691200ce56b32', 0, 0, '2025-11-10 17:12:14', 1, 1),
(59, 'The new Google app is huge', 1, 'MediaFolders/posts/176278754512691200d9921bd', 0, 0, '2025-11-10 17:12:25', 1, 3),
(60, 'Google Smart AI breaks all limits', 1, 'MediaFolders/posts/176278756512691200ede9cfa', 0, 0, '2025-11-10 17:12:45', 1, 4),
(61, 'Google released a new app called Smart AI and its just fascinating', 1, 'MediaFolders/posts/1762787595126912010b922de', 0, 0, '2025-11-10 17:13:15', 1, 7),
(62, 'The New AI app developed by Google is called Smart AI and it can have 1.5 million tokens which is 500k more tokens than gemini pro', 1, 'MediaFolders/posts/1762787726126912018e18d7c', 0, 0, '2025-11-10 17:15:26', 1, 15),
(71, 'Apple just dropped Vision Pro 2 — the display clarity is unreal.', 1, 'MediaFolders/posts/176300001126912401aaa12b', 0, 0, '2025-11-11 13:01:01', 1, 3),
(72, 'Vision Pro 2 now supports eye-tracking in multiplayer apps. Apple is seriously ahead.', 1, 'MediaFolders/posts/176300002426912401ab239f', 0, 1, '2025-11-11 13:01:24', 1, 12),
(73, 'Anyone tested the new Vision headset from Apple? Rumor says it’s lighter and better balanced.', 1, 'MediaFolders/posts/176300003726912401ac19a5', 0, 0, '2025-11-11 13:01:37', 1, 1),
(74, 'The second-gen Vision Pro makes AR feel like real life. The details are insane.', 1, 'MediaFolders/posts/176300004926912401ad1837', 0, 0, '2025-11-11 13:01:49', 1, 7),
(75, 'A supposed Tesla “Model Z” prototype was spotted near Fremont — looks futuristic.', 1, 'MediaFolders/posts/176300011126912401b2187a', 0, 0, '2025-11-11 13:02:11', 1, 4),
(76, 'Model Z might be Tesla’s new SUV flagship. Rumor mill says 900km range.', 1, 'MediaFolders/posts/176300012426912401b3368b', 0, 0, '2025-11-11 13:02:24', 1, 15),
(77, 'So Elon hinted about “something beyond Y” in the last Q&A. That must be the Model Z, right?', 1, 'MediaFolders/posts/176300013726912401b41abc', 0, 0, '2025-11-11 13:02:37', 1, 1),
(78, 'Leaked shots show Tesla working on a new electric SUV — could this be the Z project?', 1, 'MediaFolders/posts/176300014926912401b514da', 0, 0, '2025-11-11 13:02:49', 1, 3),
(79, 'SpaceX revealed its first Mars habitat mockup, complete with radiation shielding.', 1, 'MediaFolders/posts/176300021126912401b912ab', 0, 0, '2025-11-11 13:03:11', 1, 7),
(80, 'The Mars base design looks like something from a sci-fi film. SpaceX going all in.', 1, 'MediaFolders/posts/176300022426912401ba2639', 0, 0, '2025-11-11 13:03:24', 1, 4),
(81, 'Is anyone else following SpaceX’s “Haven One” module project? Mars life might be closer than we think.', 1, 'MediaFolders/posts/176300023726912401bb1e32', 0, 0, '2025-11-11 13:03:37', 1, 12),
(82, 'Apparently, SpaceX is already testing pressure domes for their Mars habitat prototype.', 1, 'MediaFolders/posts/176300024926912401bc129a', 0, 0, '2025-11-11 13:03:49', 1, 15),
(83, 'Meta is rumored to be working on a smartphone that merges AR lenses directly in the display.', 1, 'MediaFolders/posts/176300041126912401c612b3', 0, 0, '2025-11-11 13:09:01', 1, 15),
(84, 'The Horizon Phone might be Meta’s boldest hardware play yet — think AR without goggles.', 1, 'MediaFolders/posts/176300042426912401c713f2', 0, 0, '2025-11-11 13:09:14', 1, 7),
(85, 'Some leaked renders of a “Meta Horizon Phone” just surfaced. Looks too futuristic to be fake.', 1, 'MediaFolders/posts/176300043726912401c81da4', 0, 0, '2025-11-11 13:09:27', 1, 3),
(86, 'Meta’s upcoming phone could integrate AR directly into social feeds — imagine seeing posts pop in real space.', 1, 'MediaFolders/posts/176300044926912401c91cbb', 0, 0, '2025-11-11 13:09:39', 1, 1),
(87, 'If the Horizon Phone actually works like they claim, it’ll crush the smartphone market.', 1, 'MediaFolders/posts/176300045926912401ca12b1', 0, 0, '2025-11-11 13:09:51', 1, 4),
(88, 'Rumors suggest Meta’s new phone uses holographic projection instead of a normal screen.', 1, 'MediaFolders/posts/176300046926912401cb12a9', 0, 0, '2025-11-11 13:10:03', 1, 12),
(89, 'Horizon might not just be a phone — it’s rumored to double as a mini AR hub for glasses.', 1, 'MediaFolders/posts/176300047926912401cc12a3', 0, 0, '2025-11-11 13:10:15', 1, 15),
(90, 'Meta entering the AR phone market could shake up everything Apple’s doing with Vision Pro.', 1, 'MediaFolders/posts/176300048926912401cd128e', 0, 0, '2025-11-11 13:10:27', 1, 3),
(91, 'SpaceX’s Starship just completed its 2025 high-orbit test flight!', 1, 'MediaFolders/posts/176290201812691221b2c7a01', 0, 0, '2025-11-12 13:08:11', 1, 1),
(92, 'Starship finally made a full re-entry without disintegration this time', 1, 'MediaFolders/posts/176290201912691221b2c7a12', 0, 0, '2025-11-12 13:08:23', 1, 3),
(93, 'The new heat-shield tiles on SpaceX’s Starship proved extremely durable', 1, 'MediaFolders/posts/176290202012691221b2c7a23', 0, 0, '2025-11-12 13:08:35', 1, 4),
(94, 'SpaceX achieved orbit and re-entry with the Starship 2025 prototype', 1, 'MediaFolders/posts/176290202112691221b2c7a34', 0, 0, '2025-11-12 13:08:47', 1, 7),
(95, 'Starship 2025 flight test data will help refine future Mars vehicle design', 1, 'MediaFolders/posts/176290202212691221b2c7a45', 0, 0, '2025-11-12 13:08:58', 1, 12),
(96, 'Elon Musk calls the latest Starship launch “our biggest leap toward Mars yet”', 1, 'MediaFolders/posts/176290202312691221b2c7a56', 0, 0, '2025-11-12 13:09:09', 1, 15),
(97, 'SpaceX engineers said the Starship 2025 booster performed flawlessly', 1, 'MediaFolders/posts/176290202412691221b2c7a67', 0, 0, '2025-11-12 13:09:21', 1, 3),
(98, 'The Starship 2025 mission puts SpaceX ahead in the race for reusable heavy rockets', 1, 'MediaFolders/posts/176290202512691221b2c7a78', 1, 1, '2025-11-12 13:09:32', 1, 1),
(99, 'Starship 2025 flight test data will help refine future Mars vehicle design', 2, 'MediaFolders/posts/176346638812691c5c94b8b56', 0, 0, '2025-11-18 13:46:28', 0, 12),
(100, 'Starship 2025 flight test data will help refine future Mars vehicle design', 1, 'MediaFolders/posts/176346641012691c5caa2fe5b', 0, 0, '2025-11-18 13:46:50', 0, 12),
(101, 'Starship 2025 flight test data will help refine future Mars vehicle design', 1, 'MediaFolders/posts/176346645112691c5cd35e297', 0, 0, '2025-11-18 13:47:31', 0, 12),
(102, 'trial', 1, 'MediaFolders/posts/176346818312691c6397cef5d', 0, 0, '2025-11-18 14:16:23', 0, 12);

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

--
-- Dumping data for table `saved_posts`
--

INSERT INTO `saved_posts` (`id`, `UID`, `PostID`, `SavedOn`) VALUES
(7, 12, 9, '2025-10-28 03:13:10'),
(10, 12, 98, '2025-11-18 15:50:12'),
(12, 12, 96, '2025-11-22 01:00:24'),
(13, 12, 97, '2025-11-23 17:09:53'),
(14, 12, 94, '2025-11-23 17:09:58'),
(15, 12, 92, '2025-11-23 17:10:01');

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

--
-- Dumping data for table `tokens`
--

INSERT INTO `tokens` (`id`, `UID`, `Token`, `Token_2`, `IP`, `UserAgent`, `UpdatedOn`) VALUES
(57, 1, 'cf15db982ed118a51b9765d068e14165a38ba789d982ce4f0b3ae0e1c2d96aba', 'f82a554cb8900c707b99dca0b4ed651d45683093dee35b8ab03076d974c34315', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 1763858048),
(60, 12, '71ccf1aea65a0340cc44df3dd195bcc017781a6d14edfbc791c0db4e015af33e', '09c96dd80e32dfec579bcac58d5de2429787415355de8f79c1b5ee6c201efed3', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 1763944082);

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

--
-- Dumping data for table `topic_cache`
--

INSERT INTO `topic_cache` (`id`, `Query`, `Results`, `LastCalculated`) VALUES
(36, 'Spacex', '{\"users\":[],\"topics\":[{\"type\":\"full_search\",\"query\":\"spacex\",\"url\":\"index.php?target=search&query=spacex\"},{\"type\":\"suggestion\",\"query\":\"Haven One\",\"url\":\"index.php?target=search&query=Haven+One\"}]}', '2025-11-15 02:15:14'),
(38, 'Space', '{\"users\":[],\"topics\":[{\"type\":\"full_search\",\"query\":\"space\",\"url\":\"index.php?target=search&query=space\"},{\"type\":\"suggestion\",\"query\":\"Haven One\",\"url\":\"index.php?target=search&query=Haven+One\"}]}', '2025-11-15 01:44:28'),
(41, 'Spacx', '{\"users\":[],\"topics\":[{\"type\":\"full_search\",\"query\":\"Spacx\",\"url\":\"index.php?target=search&query=Spacx\"}]}', '2025-11-13 15:24:24'),
(51, 'Spa', '{\"users\":[],\"topics\":[{\"type\":\"full_search\",\"query\":\"Spa\",\"url\":\"index.php?target=search&query=Spa\"}]}', '2025-11-13 15:53:31'),
(52, 'Spac', '{\"users\":[],\"topics\":[{\"type\":\"full_search\",\"query\":\"Spac\",\"url\":\"index.php?target=search&query=Spac\"}]}', '2025-11-13 15:53:32'),
(91, 'co', '{\"users\":[],\"topics\":[{\"type\":\"full_search\",\"query\":\"co\",\"url\":\"index.php?target=search&query=co\"},{\"type\":\"suggestion\",\"query\":\"Vision Pro\",\"url\":\"index.php?target=search&query=Vision+Pro\"}]}', '2025-11-24 15:40:54'),
(92, 'comm', '{\"users\":[],\"topics\":[{\"type\":\"full_search\",\"query\":\"comm\",\"url\":\"index.php?target=search&query=comm\"}]}', '2025-11-24 15:40:51'),
(93, 'com', '{\"users\":[],\"topics\":[{\"type\":\"full_search\",\"query\":\"com\",\"url\":\"index.php?target=search&query=com\"}]}', '2025-11-24 15:40:55'),
(96, 'commune', '{\"users\":[],\"topics\":[{\"type\":\"full_search\",\"query\":\"commune\",\"url\":\"index.php?target=search&query=commune\"}]}', '2025-11-24 15:41:16'),
(98, 'e;', '{\"users\":[],\"topics\":[{\"type\":\"full_search\",\"query\":\"e;\",\"url\":\"index.php?target=search&query=e%3B\"}]}', '2025-11-24 15:41:25'),
(100, 'el', '{\"users\":[{\"id\":7,\"Fname\":\"Yael  \",\"Lname\":\"Lengoff\",\"Username\":\"yael cardenas_len goffd7e21dc4\",\"ProfilePic\":\"Imgs\\/Icons\\/unknown.png\",\"uid\":\"SCkrfL8siv15VYKqzv1zdQ%3D%3D\"}],\"topics\":[{\"type\":\"full_search\",\"query\":\"el\",\"url\":\"index.php?target=search&query=el\"}]}', '2025-11-24 16:04:22'),
(101, 'elo', '{\"users\":[],\"topics\":[{\"type\":\"full_search\",\"query\":\"elo\",\"url\":\"index.php?target=search&query=elo\"}]}', '2025-11-24 16:04:23'),
(103, 'elom', '{\"users\":[],\"topics\":[{\"type\":\"full_search\",\"query\":\"elom\",\"url\":\"index.php?target=search&query=elom\"}]}', '2025-11-24 15:41:34'),
(105, 'elon', '{\"users\":[],\"topics\":[{\"type\":\"full_search\",\"query\":\"elon\",\"url\":\"index.php?target=search&query=elon\"}]}', '2025-11-24 16:05:38'),
(106, 'Elon Musk', '{\"users\":[],\"topics\":[{\"type\":\"full_search\",\"query\":\"elon musk\",\"url\":\"index.php?target=search&query=elon+musk\"}]}', '2025-11-24 16:05:44'),
(111, 'mod', '{\"users\":[],\"topics\":[{\"type\":\"full_search\",\"query\":\"mod\",\"url\":\"index.php?target=search&query=mod\"}]}', '2025-11-24 16:04:04');

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
  `Privilege` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `Fname`, `Lname`, `Username`, `Email`, `Password`, `BirthDay`, `Gender`, `Bio`, `CountryID`, `ProfilePic`, `CoverPhoto`, `Followers`, `Following`, `Privilege`) VALUES
(1, 'Amir ', 'King', 'Amirking', 'testing_one@gmail.com', '$2y$10$Qf87ObM0FFp7YIXjrSmTS.9j0.sZCvS6pz1pWgvBqxu0PmlD3BGVO', NULL, 0, NULL, 66, '', '', 0, 0, 0),
(2, 'Sarah ', 'Ahmed', 'Sarah82', 'testing_two@gmail.com', '', NULL, 1, NULL, 66, '', '', 0, 0, 0),
(3, 'Ahmed ', 'Aly', 'Ahmed_Aly73', 'testing_three@gmail.com', '', NULL, 0, NULL, 66, '', '', 0, 0, 0),
(4, 'Teacher', '', 'Ter34', 'testing_four@gmail.com', '', NULL, 0, NULL, 66, '', '', 0, 0, 1),
(7, 'Yael  ', 'Lengoff', 'yael cardenas_len goffd7e21dc4', 'hyfyd@mailinator.com', '$2y$10$/AFgiGFRSrzEquqJzZ10Gu5MBJwzPz0NbC14sFn54hAaRUhJ47oJm', '2007-04-16', 0, NULL, 66, '', '', 0, 0, 0),
(12, 'amir', 'hamdy', 'amir_hamdy40f259c4', 'amirhamdy45@gmail.com', '$2y$10$XS1ME7NGBnaJG2t/blAmoeLNe8rkuV90DCad5aB9GsMdtUvbrTjvK', '2000-10-02', 0, 'a new bio', 61, '176322413912/12_6918aa4b1c547.jfif', '176242986412/12_690c8ba8150c6.jpg', 0, 0, 0),
(15, 'user', 'one', 'user_oned78bff33', 'user1@gmail.com', '$2y$10$SkbEy7QBNJkS7..WUjBG0ucweJBL2vpGFIbe97ogLDKXaVu31G0m.', '2000-07-05', 1, NULL, 11, NULL, NULL, 0, 0, 0);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `comments_likes`
--
ALTER TABLE `comments_likes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=202;

--
-- AUTO_INCREMENT for table `comments_replies`
--
ALTER TABLE `comments_replies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `comments_replies_likes`
--
ALTER TABLE `comments_replies_likes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=253;

--
-- AUTO_INCREMENT for table `followers`
--
ALTER TABLE `followers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `likes`
--
ALTER TABLE `likes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=239;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT for table `saved_posts`
--
ALTER TABLE `saved_posts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `tokens`
--
ALTER TABLE `tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `topic_cache`
--
ALTER TABLE `topic_cache`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=119;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

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
