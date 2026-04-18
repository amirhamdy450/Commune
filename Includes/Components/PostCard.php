<?php

if (!function_exists('BuildPostCardViewModel')) {
    function BuildPostCardViewModel(array $Post, int $ViewerUID): array
    {
        $PostTimestamp = strtotime($Post['Date']);
        $Params = ['Timestamp' => $PostTimestamp];

        $EncryptedPostID = Encrypt($Post['PID'], 'Positioned', $Params);
        $EncryptedUserID = Encrypt($Post['UID'], 'Positioned', $Params);

        $ProfilePic = (!empty($Post['ProfilePic']))
            ? 'MediaFolders/profile_pictures/' . htmlspecialchars($Post['ProfilePic'])
            : 'Imgs/Icons/unknown.png';

        return [
            'Post' => $Post,
            'PostTimestamp' => $PostTimestamp,
            'EncryptedPostID' => $EncryptedPostID,
            'EncryptedUserID' => $EncryptedUserID,
            'ProfilePic' => $ProfilePic,
            'IsSelfPost' => (int)($ViewerUID == $Post['UID']),
            'IsSavedPost' => !empty($Post['saved']) ? 1 : 0,
        ];
    }
}

if (!function_exists('RenderPostCard')) {
    function RenderPostCard(array $ViewModel, array $Options = []): void
    {
        $Post = $ViewModel['Post'];
        $ProfileHrefMode = $Options['ProfileHrefMode'] ?? 'direct';
        $OpenDocumentsInNewTab = $Options['OpenDocumentsInNewTab'] ?? true;

        $ProfileHref = $ProfileHrefMode === 'redirected'
            ? 'index.php?redirected_from=profile&target=profile&uid=' . urlencode($ViewModel['EncryptedUserID'])
            : 'index.php?target=profile&uid=' . urlencode($ViewModel['EncryptedUserID']);

        $DocumentTargetAttrs = $OpenDocumentsInNewTab ? ' target="_blank" rel="noopener"' : '';

        echo '<div class="FeedPost' . (!empty($Post['PageName']) ? ' PageFeedPost' : '') . '" PID="' . $ViewModel['EncryptedPostID'] . '" UID="' . $ViewModel['EncryptedUserID'] . '" Self="' . $ViewModel['IsSelfPost'] . '" Saved="' . $ViewModel['IsSavedPost'] . '">
            <div class="FeedPostHeader">
                <div class="FeedPostAuthorContainer">';

        if (!empty($Post['PageName'])) {
            $PageLogoSrc = !empty($Post['PageLogo'])
                ? 'MediaFolders/page_logos/' . htmlspecialchars($Post['PageLogo'])
                : null;

            echo '<a class="FeedPageBadge" href="index.php?target=page&handle=' . urlencode($Post['PageHandle']) . '">';

            if ($PageLogoSrc) {
                echo '<img class="FeedPageLogo" src="' . $PageLogoSrc . '" alt="">';
            } else {
                echo '<div class="FeedPageLogoPlaceholder">' . mb_strtoupper(mb_substr($Post['PageName'], 0, 1)) . '</div>';
            }

            echo '<div class="FeedPostAuthorInfo">
                    <div class="FeedPostNameRow">
                        <p class="FeedPostAuthorName">' . htmlspecialchars($Post['PageName']) . '</p>
                        ' . (!empty($Post['PageIsVerified']) ? '<span class="BlueTick" title="Verified"></span>' : '') . '
                        <svg class="FeedPageIcon" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="Page"><path d="M3 2v12M3 2h8.5l-2 3.5 2 3.5H3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span class="FeedPostTime" data-date="' . $ViewModel['PostTimestamp'] . '"></span>
                    </div>
                    <span class="FeedPostUsername">@' . htmlspecialchars($Post['PageHandle']) . '</span>
                </div>
            </a>';
        } else {
            echo '<a class="FeedPostAuthor" href="' . $ProfileHref . '">
                <img src="' . $ViewModel['ProfilePic'] . '" alt="Profile Picture">
                <div class="FeedPostAuthorInfo">
                    <div class="FeedPostNameRow">
                        <p class="FeedPostAuthorName">' . htmlspecialchars($Post['Fname'] . ' ' . $Post['Lname']) . '</p>
                        ' . (!empty($Post['IsBlueTick']) ? '<span class="BlueTick" title="Verified"></span>' : '') . '
                        <span class="FeedPostTime" data-date="' . $ViewModel['PostTimestamp'] . '"></span>
                    </div>
                    <span class="FeedPostUsername">@' . htmlspecialchars($Post['Username']) . '</span>
                </div>
            </a>';

            if (!$ViewModel['IsSelfPost'] && array_key_exists('following', $Post)) {
                echo '<button class="BrandBtn FollowBtn ' . (!empty($Post['following']) ? 'Followed' : '') . '" uid="' . $ViewModel['EncryptedUserID'] . '"> ' . (!empty($Post['following']) ? 'Following' : 'Follow') . '</button>';
            }
        }

        echo '</div>

                <div class="ActionBtn"><img src="Imgs/Icons/3-dots.svg"></div>

            </div>

            <div class="FeedPostContent">
                <p>' . preg_replace('/@([\w]+)/', '<a class="MentionLink" href="index.php?target=profile&username=$1">@$1</a>', htmlspecialchars($Post['Content'])) . '</p>';

        $MediaFolder = $Post['MediaFolder'];
        if (is_dir($MediaFolder)) {
            $Media = scandir($MediaFolder);
            $MediaType = (int)$Post['Type'];

            if ($MediaType === 2) {
                foreach ($Media as $Image) {
                    if (in_array(strtolower($Image), ['.', '..'])) continue;
                    $ImagePath = $MediaFolder . '/' . $Image;
                    echo '<img src="' . $ImagePath . '" alt="">';
                }
            } elseif ($MediaType === 3) {
                foreach ($Media as $Document) {
                    if (in_array(strtolower($Document), ['.', '..'])) continue;
                    $DocumentPath = $MediaFolder . '/' . $Document;
                    $DocExt = strtoupper(pathinfo($Document, PATHINFO_EXTENSION));
                    $DocName = htmlspecialchars(pathinfo($Document, PATHINFO_FILENAME));

                    echo '<a class="FeedPostLink" href="' . APP_URL . '/' . $DocumentPath . '"' . $DocumentTargetAttrs . '>
                        <div class="UploadedFile">
                            <div class="UploadedFileIcon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg>
                            </div>
                            <div class="UploadedFileBody">
                                <div class="UploadedFileName">' . $DocName . '</div>
                                <div class="UploadedFileExt">' . $DocExt . ' Document</div>
                            </div>
                            <svg class="UploadedFileArrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                        </div>
                    </a>';
                }
            }
        }

        $LikeIcon = !empty($Post['liked']) ? 'Imgs/Icons/liked.svg' : 'Imgs/Icons/like.svg';

        echo '</div>
            <div class="FeedPostInteractionCounters">
                <p><span class="PostLikesCNT">' . (int)$Post['LikeCounter'] . '</span> likes</p>
                <p>' . (int)$Post['CommentCounter'] . ' Comments</p>
            </div>

            <div class="FeedPostInteractions">
                <div class="Interaction FeedPostLike">
                    <img src="' . $LikeIcon . '">
                    Like
                </div>

                <div class="Interaction FeedPostComment">
                    <img src="Imgs/Icons/comment.svg">
                    Comment
                </div>

                <div class="Interaction FeedPostShare">
                    <img src="Imgs/Icons/share.svg">
                    Share
                </div>
            </div>
        </div>';
    }
}
