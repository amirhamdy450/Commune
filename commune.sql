-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 26, 2025 at 11:27 PM
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
(42, 'test', 0, 0, 1, 12, '2025-10-21 02:54:59');

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
(11, 1, 12, 'wydc , it looks cringe anyways', NULL, 0, '2025-10-14 21:34:10');

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
(23, 12, 4, '2025-10-25 14:49:56');

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
(189, 42, 12),
(191, 1, 12),
(201, 46, 12),
(210, 47, 12),
(213, 9, 12),
(227, 7, 12);

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
(42, 'my  first public post !', 1, 'MediaFolders/posts/17562418211268ae1f9ddb016', 1, 0, '2025-08-26 22:57:01', 1, 12),
(43, 'image tst', 2, 'MediaFolders/posts/17562462391268ae30df98551', 0, 0, '2025-08-27 00:10:39', 0, 12),
(44, 'TEST', 2, 'MediaFolders/posts/17562476671268ae3673c939d', 0, 1, '2025-08-27 00:34:27', 1, 12),
(45, 'test 3', 2, 'MediaFolders/posts/17562509431268ae433f01dfe', 0, 0, '2025-08-27 01:29:03', 1, 12),
(46, 'aasd', 3, 'MediaFolders/posts/17562527331268ae4a3ddd7ed', 1, 1, '2025-08-27 01:58:53', 1, 12),
(47, 'asad', 1, 'MediaFolders/posts/17604758531268eebacd148d5', 1, 0, '2025-10-14 23:04:13', 1, 12),
(48, 'I\'m testing writing a bigger post than the usual to see how the UI would look like with it after i made some CSS changes in order to make it look more adaptative and overall more responsive so i\'m just writing this long unnecessary post', 1, 'MediaFolders/posts/17605686091268f02521e7e19', 0, 1, '2025-10-16 00:50:09', 1, 12);

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

--
-- Dumping data for table `tokens`
--

INSERT INTO `tokens` (`id`, `UID`, `Token`, `Token_2`, `IP`, `UserAgent`, `UpdatedOn`) VALUES
(33, 12, '8e875f917d52a8fae23acacb5b128a7510504bc717c16ba036aa7dcc24f0965f', 'fa5a54c10f2c0476d60441fc6bb2152a2ea5f3321ec26df224cfff536fd52b95', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', 1756680481),
(34, 12, '0b23fecf7195b8a087a91fc4c56fd3b5f11b80d1f582afe6442327c71f125b01', '96a6a89cde8545759f987bfb4676d13d222611b141dac44144d9ed43feeed398', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 1757373064),
(35, 12, 'ef4eeea04f0b33745a27e6b61530d2b8f8d559f387667c09fc829a40748b9872', '50f22fdb8b5719a39e8718b39c51fb594f56dd7c7a321554d4b15c550c2cf55c', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 1757511084),
(36, 12, '108007f0d14cb8696cdbf4d408b41a6774d588916aa6028ce9f3f67203ebeafc', 'dadd16b993685791d230205991c3461c59f03a2adb232af5d9b507c9d11c16c1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 1758406376),
(37, 12, '1e8f5a8f3ba27c0ea98fd8c62411a322dfa34b6914d6c54081f6854ab3410d6b', 'df076ea464b36e10c51602aa04d7d2b9538c1bc8b0fc769c461d9008e7335f11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 1759013757),
(38, 12, 'aaba9c7fa7a916b3938683dfe1b720ce59b929811d4a5f2c9caf3398c1fbf397', '731f4fb9f3bd1b6cde67e3d8d7672efa5613a9939bf6e674186b45a38d2b41ff', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 1759103861),
(39, 12, 'eb82ff6f6653a9f0e83366899de97bafd2e5f142d7efe862f4cc06b10896a559', 'bf0ff67859d8d474a42c7055cedfc08a97bea653ed9805daf4fb6c3dfdbe3b80', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', 1759104194),
(40, 12, 'e72a5dfbbbd0c28c0ee94b908b9a2e292cc1fc4e377e907eaa2ed2407bc80601', '75231a90b384ab3a3c667f014e0be1666c55c5db0dea92af416ae27487135e2a', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1759788710),
(41, 12, '9a4a5c4e217c38c8bd43045311ffd78fafe4d2e42edcd82a64bc8f8fde456cab', '6f1b19572adb1f36411097015ac8147520db0d636f7379bfe44ee009acf9d3b4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1759792474),
(42, 12, '564417b453df567f70f9895e82e6f8339844b16c85debe507cba82bf991e57f0', '6f224437f9f40d0d074ec4997b54dfdf2ce4112f0c6a7c1d87c111c223b9314d', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1760053891),
(43, 12, 'c17808210cba0e8dc7dea7fab519e5840670dbf611b4d2277313b4d4dc1ddbcc', 'a3546930e6571d551b2d9cd4fdc5b7ad9ead509059ecc95dce345a1882b17737', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1760131222),
(44, 12, 'ce4f852061eef8103de8b08e5b9830ca2b7cc5ded9c007475055c7f0c0f6063e', 'a64ebfd284ec342cd51a83c0fb6f89a3640b562e91587ba3b85cb96ac63526ca', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1760134895),
(45, 12, '2fc19387d28910fc2b9cda3e162e77f2e56dd042357fc9fec121ff548a93b579', 'ae7eda702c5a6ae8557f5d0c72a2981d26ec21fed7570279e32464d227343b19', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', 1760136459),
(46, 12, 'd0e87cae22dc2d6ae0c6420f2ed5e2525e2e518a3ceb11ec590735cf80d53a09', '7f73ad0852afd85d7b8854019b9112b62a2a64348ec4e59e1e1c6cb39fb7f280', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', 1760140213),
(47, 12, 'd8a02c777b4114f659781aa46a7724af94f0dee2e950ad439d50c529280759b6', '11d25ad91540f5786a7f74a84208f5d5aed753167669dbc5c87edde9709f8f4c', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1760393941),
(48, 12, '28850216f0320512c9dcb37bb7f9176112457ddbbaf95b0c1ecc672026149cd5', '0d2b6bf70276339dd7a1bd4c68f4b77e20472c3685783964d686ac7e6930e3d9', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', 1760217604),
(49, 12, '189500ccb364b49bef81332d11cfce097426d5ca5e2890bd30cf95ebe82730b7', '0b32d882718896a725535327a873ecbf46b19632bb2a647a1c8ec7105af8252f', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1760470660),
(50, 12, '155acf3f313d4e4fb7c6f3c24dd18f24f059d148fc9ca7b8d21d3d706b8f55f6', 'e011957b1870a69882b7fc21868b61a5e1f39c09a57865ca2cad7ce5cdcf6eaa', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1760478791),
(51, 12, '57132ce6fc74802b97bd39179a7b6c080a0550165eb447bb56aa85e34afbb2b1', '96d9ec549de07c43f1ba1eb4de4f125746c6d2fa7313d27876258303bc279386', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1760483929),
(52, 12, '67896fbdf4e2c7125bd7ea2a7c803e60a23836d71396aba87f4b74a463e9cb6f', 'eece5de3f13f1f53a4e96c8a0e89ab5832129a57c536586a95c8ec6de2bd9ddb', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1761350571),
(53, 12, 'd4c86c363f8caa7092bd68b6086e17752ca3ba9d588cf1524d3570256457ac26', '212751d1eccd8e91bc53bedc7ee8327f27adc21cedc1cecbff10e6409f706c7f', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', 1761512109);

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
(1, 'Amir ', 'King', 'Amirking', '', '', NULL, 0, NULL, 66, '', '', 0, 0, 0),
(2, 'Sarah ', 'Ahmed', 'Sarah82', '', '', NULL, 1, NULL, 66, '', '', 0, 0, 0),
(3, 'Ahmed ', 'Aly', 'Ahmed_Aly73', '', '', NULL, 0, NULL, 66, '', '', 0, 0, 0),
(4, 'Teacher', '', 'Ter34', '', '', NULL, 0, NULL, 66, '', '', 0, 0, 1),
(7, 'Yael  ', 'Lengoff', 'yael cardenas_len goffd7e21dc4', 'hyfyd@mailinator.com', '$2y$10$/AFgiGFRSrzEquqJzZ10Gu5MBJwzPz0NbC14sFn54hAaRUhJ47oJm', '2007-04-16', 0, NULL, 66, '', '', 0, 0, 0),
(12, 'amir ', 'hamdy', 'amir_hamdy40f259c4', 'amirhamdy450@gmail.com', '$2y$10$BKjkavTff16oXjfjLEeY6.xZlj461bC4MtsIHSPyKLP65/0Sijo06', '2000-10-01', 0, NULL, 66, '', '', 0, 0, 0),
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
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commune_posts_UID` (`UID`);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `comments_likes`
--
ALTER TABLE `comments_likes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=202;

--
-- AUTO_INCREMENT for table `comments_replies`
--
ALTER TABLE `comments_replies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `comments_replies_likes`
--
ALTER TABLE `comments_replies_likes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=253;

--
-- AUTO_INCREMENT for table `followers`
--
ALTER TABLE `followers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `likes`
--
ALTER TABLE `likes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=228;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `saved_posts`
--
ALTER TABLE `saved_posts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tokens`
--
ALTER TABLE `tokens`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

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
