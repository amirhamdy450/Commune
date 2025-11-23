<?php
// This file is included by RouteController.php when ?target=search is set.
// The $SearchQuery variable is already set by RouteController.php.

if (!isset($PATH)) $PATH = '';
if (!isset($SearchQuery)) $SearchQuery = ''; // Should be set, but as a fallback

include_once $PATH.'Includes/UserAuth.php';
include_once $PATH.'Includes/Encryption.php';
$DocumentExtensions = '.pdf, .doc, .docx, .txt ,.xls,.xlsx,.ppt,.pptx';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="Styles/Global.css">
    <link rel="stylesheet" href="Styles/Feed.css">
    <link rel="stylesheet" href="Styles/profile.css"> 
    <link rel="stylesheet" href="Styles/Search.css">
    <title>Search for "<?php echo htmlspecialchars($SearchQuery); ?>"</title>
</head>

<body data-search-query="<?php echo htmlspecialchars($SearchQuery); ?>">

    <?php include 'Includes/NavBar.php'; ?>

    <div class="FlexContainer">
        <div class="SearchContainer">
            
            <h2 class="SearchPageTitle">Search results for "<strong><?php echo htmlspecialchars($SearchQuery); ?></strong>"</h2>

            <div class="SearchSection" id="SearchUsersSection">
                <h3 class="SearchSectionTitle">People</h3>
                <div class="SearchUsersContainer" id="SearchUsersContainer">
                    </div>
                <div class="Loader hidden" id="UsersLoader"></div>
                <button class="BrandBtn SeeMoreBtn hidden" id="SeeMoreUsersBtn">See More Users</button>
            </div>

            <div class="SearchSection" id="SearchPostsSection">
                <h3 class="SearchSectionTitle">Posts</h3>
                <div class="FeedContainer" id="SearchPostsContainer">
                    </div>
                <div class="Loader" id="PostsLoader"></div> <button class="BrandBtn SeeMoreBtn hidden" id="SeeMorePostsBtn">See More Posts</button>
            </div>

        </div>
    </div>

    <?php include 'Includes/Modals/CreatePost.php'; ?>
    <?php include 'Includes/Modals/CommentSection.php'; ?>
    <?php include 'Includes/Modals/Confirmation.php'; ?>

    <script src="Scripts/modal.js"></script>
    <script type="module" src="Scripts/Feed.js"></script>
    <script type="module" src="Scripts/Search.js"></script>
</body>
</html>