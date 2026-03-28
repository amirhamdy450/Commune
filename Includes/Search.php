<?php
if (!isset($PATH)) $PATH = '';
if (!isset($SearchQuery)) $SearchQuery = ''; 

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
            
            <div class="SearchHeader">
                <h2 class="SearchPageTitle">Results for "<strong><?php echo htmlspecialchars($SearchQuery); ?></strong>"</h2>
                
                <div class="TabsNav SearchNav">
                    <a href="#" class="NavItem Active" tab-content="SearchAllTab">All</a>
                    <a href="#" class="NavItem" tab-content="SearchUsersTab">People</a>
                    <a href="#" class="NavItem" tab-content="SearchPostsTab">Posts</a>
                </div>
            </div>

            <div class="SearchContent">
                
                <div class="TabContent Active" id="SearchAllTab">
                    
                    <div class="SearchSectionPreview hidden" id="AllPeopleSection">
                        <h3 class="PreviewTitle">People</h3>
                        <div class="SearchUsersContainer" id="AllPeopleList"></div>
                        <div class="ViewAllBtn" id="SeeAllPeopleBtn">See all people</div>
                    </div>

                    <div class="SearchSectionPreview hidden" id="AllPostsSection">
                        <h3 class="PreviewTitle">Posts</h3>
                        <div class="FeedContainer" id="AllPostsList"></div>
                        <div class="ViewAllBtn" id="SeeAllPostsBtn">See all posts</div>
                    </div>

                    <div class="SearchEmptyState hidden" id="AllEmptyState">
                        <img src="Imgs/Icons/search.svg" alt="No Results">
                        <h3>No results found</h3>
                        <p>We couldn't find anything matching "<?php echo htmlspecialchars($SearchQuery); ?>".</p>
                    </div>
                </div>

                <div class="TabContent hidden" id="SearchUsersTab">
                    <div class="SearchUsersContainer" id="SearchUsersList"></div>
                    <div class="FeedLoader hidden" id="UsersLoader"><div class="Loader"></div></div>
                    <p class="NoMorePosts hidden" id="NoMoreUsers">No more users found.</p>
                </div>

                <div class="TabContent hidden" id="SearchPostsTab">
                    <div class="FeedContainer" id="SearchPostsList"></div>
                    <div class="FeedLoader hidden" id="PostsLoader"><div class="Loader"></div></div>
                    <p class="NoMorePosts hidden" id="NoMorePosts">No more posts found.</p>
                </div>

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