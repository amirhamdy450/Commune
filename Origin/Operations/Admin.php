<?php
$PATH = "../../";
require_once $PATH . "Includes/Config.php";
require_once $PATH . "Includes/DB.php";
require_once $PATH . "Includes/UserAuth.php";
require_once $PATH . "Includes/Encryption.php";
require_once $PATH . "Origin/Validation.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require $PATH . 'vendor/phpmailer/phpmailer/src/Exception.php';
require $PATH . 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require $PATH . 'vendor/phpmailer/phpmailer/src/SMTP.php';

function SendSanctionEmail(string $ToEmail, string $ToName, int $Type, string $Reason, ?string $EndDate, ?string $RefPostsJson = null, ?string $RefCommentsJson = null, ?\PDO $pdo = null): void {
    $TypeLabel = match($Type) {
        0 => 'Account Warning',
        2 => 'Permanent Ban',
        default => 'Temporary Ban',
    };

    if ($Type === 0) {
        $Subject = 'You have received a warning on Commune';
        $Intro   = 'Your account has received an official warning from the Commune moderation team.';
        $Details = '<p style="margin:0 0 12px">Warnings are recorded on your account. Continued violations may result in a temporary or permanent ban.</p>';
        $EndLine = '';
    } elseif ($Type === 1) {
        $Subject = 'Your Commune account has been temporarily suspended';
        $Intro   = 'Your account has been temporarily suspended by the Commune moderation team.';
        $Formatted = $EndDate ? date('F j, Y \a\t g:i A', strtotime($EndDate)) : 'a set date';
        $Details = '<p style="margin:0 0 12px">You will not be able to access Commune until your suspension is lifted.</p>';
        $EndLine = '<p style="margin:0 0 12px">Your access will be restored on <strong>' . htmlspecialchars($Formatted) . '</strong>.</p>';
    } else {
        $Subject = 'Your Commune account has been permanently banned';
        $Intro   = 'Your account has been permanently banned from Commune.';
        $Details = '<p style="margin:0 0 12px">This means you will no longer be able to log in or access Commune. This action was taken due to a serious violation of our community guidelines.</p>';
        $EndLine = '<p style="margin:0 0 12px">If you believe this decision was made in error, please contact our support team.</p>';
    }

    $Body = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#f0f2f5;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 20px">
        <tr><td align="center">
            <table width="520" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,0.08)">
                <tr><td style="background:#111827;padding:24px 32px">
                    <span style="font-size:20px;font-weight:800;color:#fff">Commune</span>
                </td></tr>
                <tr><td style="padding:32px">
                    <h2 style="margin:0 0 8px;font-size:20px;color:#111827">' . htmlspecialchars($TypeLabel) . '</h2>
                    <p style="margin:0 0 20px;font-size:14px;color:#6b7280">' . htmlspecialchars($Intro) . '</p>
                    <div style="background:#fff1f2;border:1px solid #fecdd3;border-left:4px solid #ef4444;border-radius:0 10px 10px 0;padding:18px 20px;margin-bottom:20px">
                        <table cellpadding="0" cellspacing="0" style="margin:0 0 8px 0"><tr>
                            <td style="vertical-align:middle;padding-right:7px">
                                <div style="width:16px;height:16px;background:#ef4444;border-radius:50%;text-align:center;line-height:16px;font-size:10px;font-weight:900;color:#fff;font-family:Georgia,serif">!</div>
                            </td>
                            <td style="vertical-align:middle;font-size:11px;font-weight:700;color:#ef4444;text-transform:uppercase;letter-spacing:.8px;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif">Moderation Reason</td>
                        </tr></table>
                        <p style="margin:0;font-size:14px;color:#111827;font-weight:500;line-height:1.7;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif">' . htmlspecialchars($Reason) . '</p>
                    </div>
                    ' . $Details . $EndLine .
                    (($RefPostsJson || $RefCommentsJson) ? (function() use ($pdo, $RefPostsJson, $RefCommentsJson) {
                        $html = '<div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:16px 18px;margin-bottom:20px">';
                        $html .= '<p style="margin:0 0 14px;font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px">Referenced content</p>';
                        if ($RefPostsJson) {
                            $postIDs = json_decode($RefPostsJson, true);
                            $placeholders = implode(',', array_fill(0, count($postIDs), '?'));
                            $rows = $pdo->prepare("SELECT Content FROM posts WHERE id IN ($placeholders)");
                            $rows->execute($postIDs);
                            $html .= '<p style="margin:0 0 8px;font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.4px">Posts</p>';
                            foreach ($rows->fetchAll(PDO::FETCH_COLUMN) as $idx => $content) {
                                $html .= '<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:10px 14px;margin-bottom:8px;font-size:13px;color:#374151;line-height:1.6">' . htmlspecialchars(mb_strimwidth($content, 0, 300, '…')) . '</div>';
                            }
                        }
                        if ($RefCommentsJson) {
                            $commentIDs = json_decode($RefCommentsJson, true);
                            $placeholders = implode(',', array_fill(0, count($commentIDs), '?'));
                            $rows = $pdo->prepare("SELECT c.comment, p.Content AS PostContent FROM comments c INNER JOIN posts p ON c.PostID = p.id WHERE c.id IN ($placeholders)");
                            $rows->execute($commentIDs);
                            $html .= '<p style="margin:8px 0 8px;font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;letter-spacing:.4px">Comments</p>';
                            foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $row) {
                                $html .= '<div style="background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:10px 14px;margin-bottom:8px">';
                                $html .= '<p style="margin:0 0 6px;font-size:11px;color:#9ca3af">On post: ' . htmlspecialchars(mb_strimwidth($row['PostContent'], 0, 100, '…')) . '</p>';
                                $html .= '<p style="margin:0;font-size:13px;color:#374151;line-height:1.6">' . htmlspecialchars(mb_strimwidth($row['comment'], 0, 300, '…')) . '</p>';
                                $html .= '</div>';
                            }
                        }
                        $html .= '</div>';
                        return $html;
                    })() : '') . '
                    <p style="margin:0;font-size:13px;color:#9ca3af">— The Commune Team</p>
                </td></tr>
            </table>
        </td></tr>
    </table>
    </body></html>';

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($ToEmail, $ToName);
        $mail->isHTML(true);
        $mail->Subject = $Subject;
        $mail->Body    = $Body;
        $mail->send();
    } catch (Exception $e) {
        error_log('Sanction email failed: ' . $mail->ErrorInfo);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { die(); }
ValidateCsrf();

if ((int)$User['Privilege'] < PRIV_ADMIN) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    die();
}

$ReqType = (int)($_POST['ReqType'] ?? 0);

// ── Content removal email ─────────────────────────────────────────────────
function SendContentRemovedEmail(string $ToEmail, string $ToName, string $ContentType, string $ContentSnippet, string $Reason): void {
    $Label   = $ContentType === 'post' ? 'Post Removed' : 'Comment Removed';
    $Intro   = $ContentType === 'post'
        ? 'One of your posts has been removed by the Commune moderation team.'
        : 'One of your comments has been removed by the Commune moderation team.';
    $Snippet = htmlspecialchars(mb_strimwidth($ContentSnippet, 0, 300, '…'));

    $Body = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#f0f2f5;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 20px">
        <tr><td align="center">
            <table width="520" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,0.08)">
                <tr><td style="background:#111827;padding:24px 32px">
                    <span style="font-size:20px;font-weight:800;color:#fff">Commune</span>
                </td></tr>
                <tr><td style="padding:32px">
                    <h2 style="margin:0 0 8px;font-size:20px;color:#111827">' . $Label . '</h2>
                    <p style="margin:0 0 20px;font-size:14px;color:#6b7280">' . $Intro . '</p>
                    <div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;padding:16px 18px;margin-bottom:20px">
                        <p style="margin:0 0 4px;font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px">Removed content</p>
                        <p style="margin:0;font-size:14px;color:#374151;line-height:1.6">' . $Snippet . '</p>
                    </div>
                    <div style="background:#fff1f2;border:1px solid #fecdd3;border-left:4px solid #ef4444;border-radius:0 10px 10px 0;padding:18px 20px;margin-bottom:20px">
                        <table cellpadding="0" cellspacing="0" style="margin:0 0 8px 0"><tr>
                            <td style="vertical-align:middle;padding-right:7px">
                                <div style="width:16px;height:16px;background:#ef4444;border-radius:50%;text-align:center;line-height:16px;font-size:10px;font-weight:900;color:#fff;font-family:Georgia,serif">!</div>
                            </td>
                            <td style="vertical-align:middle;font-size:11px;font-weight:700;color:#ef4444;text-transform:uppercase;letter-spacing:.8px;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif">Moderation Reason</td>
                        </tr></table>
                        <p style="margin:0;font-size:14px;color:#111827;font-weight:500;line-height:1.7;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',sans-serif">' . htmlspecialchars($Reason) . '</p>
                    </div>
                    <p style="margin:0 0 12px;font-size:14px;color:#374151">If you believe this was a mistake, please review our community guidelines or contact support.</p>
                    <p style="margin:0;font-size:13px;color:#9ca3af">— The Commune Team</p>
                </td></tr>
            </table>
        </td></tr>
    </table>
    </body></html>';

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($ToEmail, $ToName);
        $mail->isHTML(true);
        $mail->Subject = 'Your ' . $ContentType . ' was removed on Commune';
        $mail->Body    = $Body;
        $mail->send();
    } catch (Exception $e) {
        // Silent — notification was already sent
    }
}

// ── [1] Fetch pending verification requests ───────────────────────────────
if ($ReqType === 1) {
    $stmt = $pdo->query("
        SELECT vr.id, vr.UID, vr.Reason, vr.SubmittedAt,
               CONCAT(u.Fname,' ',u.Lname) AS Name,
               u.Username, u.ProfilePic, u.IsBlueTick
        FROM verification_requests vr
        INNER JOIN users u ON vr.UID = u.id
        WHERE vr.Status = 0
        ORDER BY vr.SubmittedAt ASC
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $r['ProfilePic'] = $r['ProfilePic']
            ? 'MediaFolders/profile_pictures/' . $r['ProfilePic']
            : 'Imgs/Icons/unknown.png';
        $ts = strtotime($r['SubmittedAt']);
        $r['EncUID'] = Encrypt($r['UID'], "Positioned", ["Timestamp" => $ts]);
    }
    echo json_encode(['success' => true, 'requests' => $rows]);
    die();
}

// ── [2] User search ───────────────────────────────────────────────────────
if ($ReqType === 2) {
    $query = trim($_POST['query'] ?? '');
    $like = '%' . $query . '%';
    $stmt = $pdo->prepare("
        SELECT id, Fname, Lname, Username, Email, ProfilePic,
               Privilege, IsBlueTick, IsBanned,
               Followers, Following,
               (SELECT COUNT(*) FROM posts WHERE UID = users.id) AS PostCount
        FROM users
        WHERE Fname LIKE ? OR Lname LIKE ? OR Username LIKE ? OR Email LIKE ?
        ORDER BY Username ASC
        LIMIT 20
    ");
    $stmt->execute([$like, $like, $like, $like]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as &$u) {
        $u['ProfilePic'] = $u['ProfilePic']
            ? 'MediaFolders/profile_pictures/' . $u['ProfilePic']
            : 'Imgs/Icons/unknown.png';
        $u['EncUID'] = Encrypt($u['id'], "Positioned", ["Timestamp" => time()]);
        // Fetch active ban detail if banned
        $u['ActiveBan'] = null;
        if ((int)$u['IsBanned'] === 1) {
            $stmtBan = $pdo->prepare("SELECT Type, Reason, EndDate, StartDate, RefPosts, RefComments FROM user_bans WHERE UID = ? AND IsActive = 1 ORDER BY StartDate DESC LIMIT 1");
            $stmtBan->execute([$u['id']]);
            $u['ActiveBan'] = $stmtBan->fetch(PDO::FETCH_ASSOC);
        }
    }
    echo json_encode(['success' => true, 'users' => $users]);
    die();
}

// ── [3] Issue a warning, temp ban, or permanent ban ──────────────────────
// Action=1 = issue sanction, Action=0 = lift active ban
if ($ReqType === 3) {
    $TargetUID   = (int)($_POST['TargetUID'] ?? 0);
    $Action      = (int)($_POST['Action'] ?? 0);
    $Type        = (int)($_POST['Type'] ?? 1);     // 0=Warning, 1=TempBan, 2=PermanentBan
    $Reason      = trim($_POST['Reason'] ?? '');
    $EndDate     = trim($_POST['EndDate'] ?? '');   // ISO date string, only for TempBan
    $RefPostsRaw    = $_POST['RefPosts'] ?? '';      // comma-separated post IDs (plain)
    $RefCommentsRaw = $_POST['RefComments'] ?? '';   // comma-separated comment IDs (plain)

    // Parse and validate reference IDs (positive integers only)
    $ParseIDs = function(string $raw): ?string {
        if (trim($raw) === '') return null;
        $ids = array_values(array_filter(array_map('intval', explode(',', $raw)), fn($v) => $v > 0));
        return empty($ids) ? null : json_encode($ids);
    };
    $RefPostsJson    = $ParseIDs($RefPostsRaw);
    $RefCommentsJson = $ParseIDs($RefCommentsRaw);

    if ($TargetUID <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user.']);
        die();
    }
    if ($TargetUID === (int)$UID) {
        echo json_encode(['success' => false, 'message' => 'You cannot take action against yourself.']);
        die();
    }

    if ($Action === 1) {
        if (strlen($Reason) < 3) {
            echo json_encode(['success' => false, 'message' => 'Provide a reason of at least 3 characters.']);
            die();
        }
        // Validate end date for temp bans
        $EndDateVal = null;
        if ($Type === 1) {
            if (empty($EndDate)) {
                echo json_encode(['success' => false, 'message' => 'Temporary bans require an end date.']);
                die();
            }
            $EndDateVal = date('Y-m-d H:i:s', strtotime($EndDate));
            if ($EndDateVal <= date('Y-m-d H:i:s')) {
                echo json_encode(['success' => false, 'message' => 'End date must be in the future.']);
                die();
            }
        }

        // Deactivate any existing active ban/sanction first
        $pdo->prepare("UPDATE user_bans SET IsActive = 0 WHERE UID = ? AND IsActive = 1")->execute([$TargetUID]);

        // Insert new action record
        $pdo->prepare("INSERT INTO user_bans (UID, Type, Reason, IssuedBy, EndDate, RefPosts, RefComments) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute([$TargetUID, $Type, $Reason, $UID, $EndDateVal, $RefPostsJson, $RefCommentsJson]);

        // Only set IsBanned flag for actual bans (not warnings)
        if ($Type > 0) {
            $pdo->prepare("UPDATE users SET IsBanned = 1 WHERE id = ?")->execute([$TargetUID]);
            // Kick the user by wiping their tokens
            $pdo->prepare("DELETE FROM tokens WHERE UID = ?")->execute([$TargetUID]);
        }

        // Fetch target user's email + name for notification and email
        $TargetUser = $pdo->prepare("SELECT Email, Fname, Lname FROM users WHERE id = ?");
        $TargetUser->execute([$TargetUID]);
        $TargetUser = $TargetUser->fetch(PDO::FETCH_ASSOC);

        if ($TargetUser) {
            // In-app notification — Type 11 = Security/Admin action
            $NotifTypeLabel = match($Type) {
                0 => 'You have received an account warning.',
                2 => 'Your account has been permanently banned.',
                default => 'Your account has been temporarily suspended.',
            };
            $pdo->prepare("INSERT INTO notifications (ToUID, FromUID, Type, MetaInfo) VALUES (?, NULL, 11, ?)")
                ->execute([$TargetUID, $NotifTypeLabel]);

            // Send email
            SendSanctionEmail(
                $TargetUser['Email'],
                $TargetUser['Fname'] . ' ' . $TargetUser['Lname'],
                $Type,
                $Reason,
                $EndDateVal,
                $RefPostsJson,
                $RefCommentsJson,
                $pdo
            );
        }

        // Optionally delete the referenced posts and comments
        $AlsoDelete = (int)($_POST['AlsoDelete'] ?? 0);
        if ($AlsoDelete === 1) {
            if ($RefPostsJson) {
                foreach (json_decode($RefPostsJson, true) as $PID) {
                    $PID = (int)$PID;
                    // Delete dependents before the post
                    $CommentStmt = $pdo->prepare("SELECT id FROM comments WHERE PostID = ?");
                    $CommentStmt->execute([$PID]);
                    $CommentIDs = $CommentStmt->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($CommentIDs as $CID) {
                        $pdo->prepare("DELETE FROM comments_replies WHERE CommentID = ?")->execute([(int)$CID]);
                        $pdo->prepare("DELETE FROM comments_likes WHERE CommentID = ?")->execute([(int)$CID]);
                    }
                    $pdo->prepare("DELETE FROM comments WHERE PostID = ?")->execute([$PID]);
                    $pdo->prepare("DELETE FROM likes WHERE PostID = ?")->execute([$PID]);
                    $pdo->prepare("DELETE FROM posts WHERE id = ? AND UID = ?")->execute([$PID, $TargetUID]);
                }
            }
            if ($RefCommentsJson) {
                foreach (json_decode($RefCommentsJson, true) as $CID) {
                    $CID = (int)$CID;
                    $pdo->prepare("DELETE FROM comments_replies WHERE CommentID = ?")->execute([$CID]);
                    $pdo->prepare("DELETE FROM comments_likes WHERE CommentID = ?")->execute([$CID]);
                    $pdo->prepare("DELETE FROM comments WHERE id = ? AND UID = ?")->execute([$CID, $TargetUID]);
                }
            }
        }

        echo json_encode(['success' => true, 'IsBanned' => $Type > 0 ? 1 : 0, 'Type' => $Type]);

    } else {
        // Lift ban: deactivate all active sanctions and clear flag
        $pdo->prepare("UPDATE user_bans SET IsActive = 0 WHERE UID = ? AND IsActive = 1")->execute([$TargetUID]);
        $pdo->prepare("UPDATE users SET IsBanned = 0 WHERE id = ?")->execute([$TargetUID]);
        echo json_encode(['success' => true, 'IsBanned' => 0]);
    }
    die();
}

// ── [3b] Extend an active ban's end date ─────────────────────────────────
if ($ReqType === 9) {
    $TargetUID  = (int)($_POST['TargetUID'] ?? 0);
    $NewEndDate = trim($_POST['EndDate'] ?? '');

    if ($TargetUID <= 0 || empty($NewEndDate)) {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        die();
    }
    $NewEndDateVal = date('Y-m-d H:i:s', strtotime($NewEndDate));
    if ($NewEndDateVal <= date('Y-m-d H:i:s')) {
        echo json_encode(['success' => false, 'message' => 'New end date must be in the future.']);
        die();
    }

    $pdo->prepare("UPDATE user_bans SET EndDate = ? WHERE UID = ? AND IsActive = 1 AND Type = 1")
        ->execute([$NewEndDateVal, $TargetUID]);

    echo json_encode(['success' => true, 'message' => 'Ban extended.']);
    die();
}

// ── [4] Grant / revoke blue tick ─────────────────────────────────────────
if ($ReqType === 4) {
    $TargetUID = (int)($_POST['TargetUID'] ?? 0);
    $Action    = (int)($_POST['Action'] ?? 0); // 1=grant, 0=revoke

    if ($TargetUID <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user.']);
        die();
    }

    $tick = $Action === 1 ? 1 : 0;
    $pdo->prepare("UPDATE users SET IsBlueTick = ? WHERE id = ?")->execute([$tick, $TargetUID]);

    if ($tick === 1) {
        $pdo->prepare("INSERT INTO notifications (ToUID, FromUID, Type, MetaInfo) VALUES (?, ?, 10, 'Your account has been verified!')")
            ->execute([$TargetUID, $UID]);
    }

    echo json_encode(['success' => true, 'IsBlueTick' => $tick]);
    die();
}

// ── [5] Delete any post (admin override) ─────────────────────────────────
if ($ReqType === 5) {
    $EncPostID = $_POST['PostID'] ?? '';
    $Reason    = trim($_POST['Reason'] ?? '');
    $PostID    = (int)Decrypt($EncPostID, "Positioned");
    if ($PostID <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid post.']);
        die();
    }
    if (strlen($Reason) < 3) {
        echo json_encode(['success' => false, 'message' => 'A reason is required.']);
        die();
    }
    $row = $pdo->prepare("SELECT u.id, u.Email, CONCAT(u.Fname,' ',u.Lname) AS Name, p.Content FROM posts p INNER JOIN users u ON p.UID = u.id WHERE p.id = ?");
    $row->execute([$PostID]);
    $Author = $row->fetch(PDO::FETCH_ASSOC);
    if ($Author) {
        $pdo->prepare("INSERT INTO notifications (ToUID, FromUID, Type, MetaInfo) VALUES (?, NULL, 11, ?)")
            ->execute([$Author['id'], 'Your post was removed by a moderator: ' . $Reason]);
        SendContentRemovedEmail($Author['Email'], $Author['Name'], 'post', $Author['Content'], $Reason);
    }
    $pdo->prepare("DELETE FROM posts WHERE id = ?")->execute([$PostID]);
    echo json_encode(['success' => true]);
    die();
}

// ── [6] Delete any comment (admin override) ───────────────────────────────
if ($ReqType === 6) {
    $EncCommentID = $_POST['CommentID'] ?? '';
    $Reason       = trim($_POST['Reason'] ?? '');
    $CommentID    = (int)Decrypt($EncCommentID, "Positioned");
    if ($CommentID <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid comment.']);
        die();
    }
    if (strlen($Reason) < 3) {
        echo json_encode(['success' => false, 'message' => 'A reason is required.']);
        die();
    }
    $row = $pdo->prepare("SELECT u.id, u.Email, CONCAT(u.Fname,' ',u.Lname) AS Name, c.comment AS Content FROM comments c INNER JOIN users u ON c.UID = u.id WHERE c.id = ?");
    $row->execute([$CommentID]);
    $Author = $row->fetch(PDO::FETCH_ASSOC);
    if ($Author) {
        $pdo->prepare("INSERT INTO notifications (ToUID, FromUID, Type, MetaInfo) VALUES (?, NULL, 11, ?)")
            ->execute([$Author['id'], 'Your comment was removed by a moderator: ' . $Reason]);
        SendContentRemovedEmail($Author['Email'], $Author['Name'], 'comment', $Author['Content'], $Reason);
    }
    $pdo->prepare("DELETE FROM comments WHERE id = ?")->execute([$CommentID]);
    echo json_encode(['success' => true]);
    die();
}

// ── [7] Platform-wide analytics ──────────────────────────────────────────
if ($ReqType === 7) {
    $stats = [];
    $stats['TotalUsers']    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $stats['TotalPosts']    = $pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
    $stats['TotalComments'] = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
    $stats['TotalLikes']    = $pdo->query("SELECT SUM(LikeCounter) FROM posts")->fetchColumn() ?? 0;
    $stats['VerifiedUsers'] = $pdo->query("SELECT COUNT(*) FROM users WHERE IsBlueTick = 1")->fetchColumn();
    $stats['BannedUsers']   = $pdo->query("SELECT COUNT(*) FROM users WHERE IsBanned = 1")->fetchColumn();
    $stats['PendingVerifications'] = $pdo->query("SELECT COUNT(*) FROM verification_requests WHERE Status = 0")->fetchColumn();
    $stats['NewPostsToday'] = $pdo->query("SELECT COUNT(*) FROM posts WHERE DATE(Date) = CURDATE()")->fetchColumn();

    // Posts per day for the last 14 days
    $trend = $pdo->query("
        SELECT DATE(Date) AS Day, COUNT(*) AS Count
        FROM posts
        WHERE Date >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
        GROUP BY DATE(Date)
        ORDER BY Day ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    $stats['PostTrend'] = $trend;

    echo json_encode(['success' => true, 'stats' => $stats]);
    die();
}

// ── [8] Search posts (content moderation) ────────────────────────────────
if ($ReqType === 8) {
    $query = trim($_POST['query'] ?? '');
    $like  = '%' . $query . '%';
    $stmt  = $pdo->prepare("
        SELECT p.id, p.Content, p.Date, p.Type, p.MediaFolder,
               CONCAT(u.Fname,' ',u.Lname) AS AuthorName,
               u.Username, u.ProfilePic
        FROM posts p
        INNER JOIN users u ON p.UID = u.id
        WHERE p.Content LIKE ? OR u.Username LIKE ? OR u.Fname LIKE ? OR u.Lname LIKE ?
        ORDER BY p.Date DESC
        LIMIT 30
    ");
    $stmt->execute([$like, $like, $like, $like]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($items as &$item) {
        $item['ProfilePic'] = $item['ProfilePic']
            ? 'MediaFolders/profile_pictures/' . $item['ProfilePic']
            : 'Imgs/Icons/unknown.png';
        $item['EncID']     = Encrypt($item['id'], "Positioned", ["Timestamp" => time()]);
        $item['Preview']   = htmlspecialchars(mb_strimwidth($item['Content'], 0, 200, '…'));
        $item['Thumbnail'] = null;
        $item['DocName']   = null;
        if ((int)$item['Type'] === 2 || (int)$item['Type'] === 3) {
            $folder = $PATH . $item['MediaFolder'];
            if (is_dir($folder)) {
                foreach (scandir($folder) as $file) {
                    if ($file === '.' || $file === '..') continue;
                    if ((int)$item['Type'] === 2) {
                        $item['Thumbnail'] = $item['MediaFolder'] . '/' . $file;
                    } else {
                        $item['DocName'] = $file;
                    }
                    break;
                }
            }
        }
        unset($item['MediaFolder']);
    }
    echo json_encode(['success' => true, 'items' => $items]);
    die();
}

// ── [10] Search comments (content moderation) ────────────────────────────
if ($ReqType === 10) {
    $query = trim($_POST['query'] ?? '');
    $like  = '%' . $query . '%';
    $stmt  = $pdo->prepare("
        SELECT c.id, c.comment AS Content, c.Date,
               CONCAT(u.Fname,' ',u.Lname) AS AuthorName,
               u.Username, u.ProfilePic,
               p.Content AS PostContent, p.Type AS PostType, p.MediaFolder AS PostMediaFolder
        FROM comments c
        INNER JOIN users u ON c.UID = u.id
        INNER JOIN posts p ON c.PostID = p.id
        WHERE c.comment LIKE ? OR u.Username LIKE ? OR u.Fname LIKE ? OR u.Lname LIKE ?
        ORDER BY c.Date DESC
        LIMIT 30
    ");
    $stmt->execute([$like, $like, $like, $like]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($items as &$item) {
        $item['ProfilePic'] = $item['ProfilePic']
            ? 'MediaFolders/profile_pictures/' . $item['ProfilePic']
            : 'Imgs/Icons/unknown.png';
        $item['EncID']       = Encrypt($item['id'], "Positioned", ["Timestamp" => time()]);
        $item['Preview']     = htmlspecialchars(mb_strimwidth($item['Content'], 0, 200, '…'));
        $item['PostContent'] = htmlspecialchars(mb_strimwidth($item['PostContent'], 0, 150, '…'));
        $item['PostThumbnail'] = null;
        if ((int)$item['PostType'] === 2 && $item['PostMediaFolder']) {
            $folder = $PATH . $item['PostMediaFolder'];
            if (is_dir($folder)) {
                foreach (scandir($folder) as $file) {
                    if ($file === '.' || $file === '..') continue;
                    $item['PostThumbnail'] = $item['PostMediaFolder'] . '/' . $file;
                    break;
                }
            }
        }
        unset($item['PostMediaFolder']);
    }
    echo json_encode(['success' => true, 'items' => $items]);
    die();
}

// ── [11] Fetch a user's posts for reference picker ────────────────────────
if ($ReqType === 11) {
    $TargetUID = (int)($_POST['TargetUID'] ?? 0);
    $query     = trim($_POST['query'] ?? '');
    if ($TargetUID <= 0) { echo json_encode(['success' => false]); die(); }
    $like = '%' . $query . '%';
    $stmt = $pdo->prepare("
        SELECT id, Content, Date, Type, MediaFolder
        FROM posts
        WHERE UID = ? AND (? = '' OR Content LIKE ?)
        ORDER BY Date DESC
        LIMIT 20
    ");
    $stmt->execute([$TargetUID, $query, $like]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($posts as &$p) {
        $p['Preview'] = htmlspecialchars($p['Content']);
        $p['EncID']   = Encrypt($p['id'], "Positioned", ["Timestamp" => time()]);
        // Resolve first media file for preview thumbnail
        $p['Thumbnail'] = null;
        $p['DocName']   = null;
        if ((int)$p['Type'] === 2 || (int)$p['Type'] === 3) {
            $folder = $PATH . $p['MediaFolder'];
            if (is_dir($folder)) {
                foreach (scandir($folder) as $file) {
                    if ($file === '.' || $file === '..') continue;
                    if ((int)$p['Type'] === 2) {
                        $p['Thumbnail'] = $p['MediaFolder'] . '/' . $file;
                    } else {
                        $p['DocName'] = $file;
                    }
                    break;
                }
            }
        }
        unset($p['MediaFolder']);
    }
    echo json_encode(['success' => true, 'posts' => $posts]);
    die();
}

// ── [12] Fetch a user's comments for reference picker ────────────────────
if ($ReqType === 12) {
    $TargetUID = (int)($_POST['TargetUID'] ?? 0);
    $query     = trim($_POST['query'] ?? '');
    if ($TargetUID <= 0) { echo json_encode(['success' => false]); die(); }
    $like = '%' . $query . '%';
    $stmt = $pdo->prepare("
        SELECT c.id, c.comment AS Content, c.Date,
               p.id AS PostID, p.Content AS PostContent, p.Type AS PostType, p.MediaFolder AS PostMediaFolder
        FROM comments c
        INNER JOIN posts p ON c.PostID = p.id
        WHERE c.UID = ? AND (? = '' OR c.comment LIKE ?)
        ORDER BY c.Date DESC
        LIMIT 20
    ");
    $stmt->execute([$TargetUID, $query, $like]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($comments as &$c) {
        $c['Preview']     = htmlspecialchars($c['Content']);
        $c['PostContent'] = htmlspecialchars($c['PostContent']);
        $c['EncID']       = Encrypt($c['id'], "Positioned", ["Timestamp" => time()]);
        // Resolve post thumbnail if image post
        $c['PostThumbnail'] = null;
        if ((int)$c['PostType'] === 2 && $c['PostMediaFolder']) {
            $folder = $PATH . $c['PostMediaFolder'];
            if (is_dir($folder)) {
                foreach (scandir($folder) as $file) {
                    if ($file === '.' || $file === '..') continue;
                    $c['PostThumbnail'] = $c['PostMediaFolder'] . '/' . $file;
                    break;
                }
            }
        }
        unset($c['PostMediaFolder']);
    }
    echo json_encode(['success' => true, 'comments' => $comments]);
    die();
}
