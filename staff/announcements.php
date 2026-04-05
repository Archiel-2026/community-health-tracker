<?php
// staff/announcements.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/activity_logger.php';
// --- Auto-logout for staff after 1 hour of inactivity ---
if (isStaff()) {
    $now = time();
    if (!isset($_SESSION['last_action'])) {
        $_SESSION['last_action'] = $now;
    } else {
        $inactive = $now - $_SESSION['last_action'];
        if ($inactive >= 3600) { // 1 hour = 3600 seconds
            session_unset();
            session_destroy();
            header('Location: /community-health-tracker/index-admin-staff.php');
            exit();
        } else {
            $_SESSION['last_action'] = $now;
        }
    }
}
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../vendor/autoload.php'; // For PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

function ensureAnnouncementViewsTable()
{
    global $pdo;

    static $initialized = false;
    if ($initialized) {
        return;
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS announcement_views (
        id INT AUTO_INCREMENT PRIMARY KEY,
        announcement_id INT NOT NULL,
        viewer_token VARCHAR(128) NOT NULL,
        viewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_announcement_view (announcement_id, viewer_token),
        KEY idx_announcement_id (announcement_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $initialized = true;
}

// Send email to specific users for announcement
function sendAnnouncementEmail($email, $fullName, $title, $message, $type = 'basic', $imageUrl = null)
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'bo.luzhealthcentercebucity@gmail.com';
        $mail->Password = 'ilru ijfn smhz bvdc';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('bo.luzhealthcentercebucity@gmail.com', 'Barangay Luz Health Center');
        $mail->addAddress($email, $fullName);
        $mail->isHTML(true);

        // Determine subject based on type and audience
        $subject = 'Announcement';
        if ($type === 'lab_result') {
            $subject = 'Lab Result';
        } elseif ($type === 'basic') {
            $subject = 'General Announcement';
        } elseif ($type === 'public') {
            $subject = 'For All Residents';
        }
        $mail->Subject = $subject;

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $logoPath = __DIR__ . '/../asssets/images/Luz.jpg';
        if (is_file($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'barangay-luz-logo', 'Luz.jpg');
        }

        $imageHtml = '';
        if ($imageUrl) {
            if (strpos($imageUrl, 'http') !== 0) {
                $normalizedImagePath = '/' . ltrim($imageUrl, '/');
                $imageUrl = $scheme . '://' . $host . $normalizedImagePath;
            }
            $imageHtml = '
                <tr>
                    <td style="padding:0 32px 28px 32px;">
                        <img src="' . htmlspecialchars($imageUrl) . '" alt="Announcement Image" style="display:block;width:100%;max-width:576px;height:auto;border-radius:18px;border:1px solid #dbe7f4;">
                    </td>
                </tr>';
        }

        $subtitle = 'Official Announcement';
        if ($type === 'basic') {
            $subtitle = 'Specific Resident Announcement';
        } elseif ($type === 'lab_result') {
            $subtitle = 'Specific Resident Lab Result';
        } elseif ($type === 'public') {
            $subtitle = 'For All Resident Users';
        }
        $escapedTitle = htmlspecialchars($title);
        $escapedFullName = htmlspecialchars($fullName);
        $formattedMessage = nl2br(htmlspecialchars($message));
        $year = date('Y');

        $mail->Body = '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Barangay Luz Health Center Announcement</title>
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
            <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
            <style>
                body, table, td, div, p, a, span, h1 {
                    font-family: \'Poppins\', Arial, Helvetica, sans-serif !important;
                }
            </style>
        </head>
        <body style="margin:0;padding:0;background-color:#eef4f8;font-family:\'Poppins\',Arial,Helvetica,sans-serif;color:#17324d;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color:#eef4f8;font-family:\'Poppins\',Arial,Helvetica,sans-serif;">
                <tr>
                    <td align="center" style="padding:32px 16px;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width:640px;background-color:#ffffff;border-radius:6px;overflow:hidden;border:1px solid #d8e5ef;font-family:\'Poppins\',Arial,Helvetica,sans-serif;">
                            <tr>
                                <td style="padding:0;background:linear-gradient(135deg,#0d5c91 0%,#1f7fb8 55%,#6ec1d4 100%);">
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                        <tr>
                                            <td style="padding:32px 32px 24px 32px;text-align:center;">
                                                <img src="cid:barangay-luz-logo" alt="Barangay Luz Health Center Logo" width="88" height="88" style="display:block;margin:0 auto 16px auto;width:88px;height:88px;border-radius:50%;border:4px solid rgba(255,255,255,0.28);object-fit:cover;background-color:#ffffff;">
                                                <div style="font-family:\'Poppins\',Arial,Helvetica,sans-serif;font-size:13px;line-height:18px;letter-spacing:1.6px;text-transform:uppercase;color:#d8f1fb;font-weight:bold;">Barangay Luz, Cebu City</div>
                                                <div style="font-family:\'Poppins\',Arial,Helvetica,sans-serif;font-size:30px;line-height:36px;color:#ffffff;font-weight:bold;margin-top:10px;">Barangay Luz Health Center</div>
                                                <div style="font-family:\'Poppins\',Arial,Helvetica,sans-serif;font-size:15px;line-height:22px;color:#e5f6ff;margin-top:10px;">' . htmlspecialchars($subtitle) . '</div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:32px 32px 8px 32px;">
                                    <div style="font-family:\'Poppins\',Arial,Helvetica,sans-serif;display:inline-block;background-color:#eaf6fb;color:#0d5c91;border-radius:6px;padding:8px 14px;font-size:12px;line-height:12px;font-weight:bold;letter-spacing:0.8px;text-transform:uppercase;">Official Notice</div>
                                    <h1 style="font-family:\'Poppins\',Arial,Helvetica,sans-serif;margin:18px 0 12px 0;font-size:24px;line-height:34px;color:#12395a;font-weight:semi-bold;">' . $escapedTitle . '</h1>
                                    <p style="font-family:\'Poppins\',Arial,Helvetica,sans-serif;margin:0 0 18px 0;font-size:16px;line-height:26px;color:#38556f;">Good Day! ' . $escapedFullName . ',</p>
                                    <div style="font-family:\'Poppins\',Arial,Helvetica,sans-serif;background-color:#f7fbfd;border:1px solid #d9e8f1;border-radius:6px;padding:22px 20px;font-size:16px;line-height:28px;color:#234764;">
                                        ' . $formattedMessage . '
                                    </div>
                                </td>
                            </tr>
                            ' . $imageHtml . '
                            <tr>
                                <td style="padding:0 32px 32px 32px;">
                                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color:#0f3552;border-radius:6px;">
                                        <tr>
                                            <td style="padding:20px 22px;">
                                                <div style="font-family:\'Poppins\',Arial,Helvetica,sans-serif;font-size:16px;line-height:24px;color:#ffffff;font-weight:bold;">Need assistance?</div>
                                                <div style="font-family:\'Poppins\',Arial,Helvetica,sans-serif;font-size:14px;line-height:22px;color:#d3e9f7;margin-top:6px;">For questions about this announcement, please contact or visit Barangay Luz Health Center during office hours.</div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:22px 32px 28px 32px;border-top:1px solid #e1ecf3;background-color:#fbfdfe;">
                                    <div style="font-family:\'Poppins\',Arial,Helvetica,sans-serif;font-size:12px;line-height:20px;color:#647b91;text-align:center;">
                                        This is an automated message from the Barangay Luz Health Monitoring and Tracking System. Please do not reply directly to this email.
                                    </div>
                                    <div style="font-family:\'Poppins\',Arial,Helvetica,sans-serif;font-size:12px;line-height:20px;color:#647b91;text-align:center;margin-top:8px;">
                                        &copy; ' . $year . ' Barangay Luz Health Center. All rights reserved.
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>';
        $mail->AltBody = "Barangay Luz Health Center\n" .
            $subtitle . "\n\n" .
            $title . "\n\n" .
            "Dear " . $fullName . ",\n\n" .
            $message . "\n\n" .
            "For questions, please contact or visit Barangay Luz Health Center.\n\n" .
            "This is an automated message. Please do not reply directly to this email.";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Add notification functions before they're called
function createTargetedAnnouncementNotification($announcementId, $title, $targetUsers)
{
    global $pdo;

    try {
        $message = "New announcement: " . $title;
        $link = "/community-health-tracker/announcements.php";

        foreach ($targetUsers as $userId) {
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, link, created_at) 
                                  VALUES (?, 'announcement', ?, ?, NOW())");
            $stmt->execute([$userId, $message, $link]);
        }

        return true;
    } catch (PDOException $e) {
        error_log("Error creating targeted notifications: " . $e->getMessage());
        return false;
    }
}

function createAnnouncementNotification($announcementId, $title)
{
    global $pdo;

    try {
        $message = "New announcement: " . $title;
        $link = "/community-health-tracker/announcements.php";

        // Get all approved users
        $stmt = $pdo->prepare("SELECT id FROM sitio1_users WHERE approved = TRUE");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($users as $user) {
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, link, created_at) 
                                  VALUES (?, 'announcement', ?, ?, NOW())");
            $stmt->execute([$user['id'], $message, $link]);
        }

        return true;
    } catch (PDOException $e) {
        error_log("Error creating public notifications: " . $e->getMessage());
        return false;
    }
}

// Rest of your existing code...
redirectIfNotLoggedIn();
if (!isStaff()) {
    header('Location: /community-health-tracker/');
    exit();
}

global $pdo;
ensureAnnouncementViewsTable();

$staffId = $_SESSION['user']['id'];
$error = '';
$success = '';
// Show success message from session if redirected after POST
if (isset($_SESSION['announcement_success'])) {
    $success = $_SESSION['announcement_success'];
    unset($_SESSION['announcement_success']);
}

// Handle form submission for new announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['post_announcement']) || isset($_POST['post_lab_result']))) {
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    $priority = isset($_POST['priority']) ? $_POST['priority'] : 'normal';
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $audience_type = isset($_POST['audience_type']) ? $_POST['audience_type'] : 'public';
    $target_users = isset($_POST['target_users']) ? (is_array($_POST['target_users']) ? array_filter($_POST['target_users']) : []) : [];
    
    // Determine announcement type
    $announcement_type = isset($_POST['post_lab_result']) ? 'lab_result' : 'basic';

    if ($audience_type === 'specific' && empty($target_users)) {
        $error = 'Please select at least one user for specific announcement.';
    } elseif (!empty($title) && !empty($message)) {
        // Prevent duplicate announcement (active only)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sitio1_announcements WHERE title = ? AND message = ? AND status = 'active'");
        $stmt->execute([$title, $message]);
        $duplicateCount = $stmt->fetchColumn();
        if ($duplicateCount > 0) {
            $error = 'Duplicate announcement: An active announcement with the same title and message already exists.';
        } else {
            // Handle image upload
            $image_path = null;
            if (isset($_FILES['announcement_image']) && $_FILES['announcement_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../uploads/announcements/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_extension = pathinfo($_FILES['announcement_image']['name'], PATHINFO_EXTENSION);
                $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

                if (in_array(strtolower($file_extension), $allowed_ext)) {
                    $file_name = uniqid() . '.' . $file_extension;
                    $file_path = $upload_dir . $file_name;

                    if (move_uploaded_file($_FILES['announcement_image']['tmp_name'], $file_path)) {
                        $image_path = '/community-health-tracker/uploads/announcements/' . $file_name;
                    }
                }
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO sitio1_announcements 
                                      (staff_id, title, message, priority, expiry_date, status, audience_type, image_path, post_date, announcement_type) 
                                      VALUES (?, ?, ?, ?, ?, 'active', ?, ?, NOW(), ?)");
                $stmt->execute([$staffId, $title, $message, $priority, $expiry_date, $audience_type, $image_path, $announcement_type]);

                $announcementId = $pdo->lastInsertId();

                // Handle target users if specific audience
                if ($audience_type === 'specific' && !empty($target_users)) {
                    foreach ($target_users as $userId) {
                        $stmt = $pdo->prepare("INSERT INTO announcement_targets (announcement_id, user_id) VALUES (?, ?)");
                        $stmt->execute([$announcementId, $userId]);
                        // Get user email and name
                        $stmtUser = $pdo->prepare("SELECT email, full_name FROM sitio1_users WHERE id = ? AND approved = TRUE");
                        $stmtUser->execute([$userId]);
                        $userInfo = $stmtUser->fetch(PDO::FETCH_ASSOC);
                        if ($userInfo && !empty($userInfo['email'])) {
                            sendAnnouncementEmail($userInfo['email'], $userInfo['full_name'], $title, $message, $announcement_type, $image_path);
                        }
                    }
                    createTargetedAnnouncementNotification($announcementId, $title, $target_users);
                    $success = ($announcement_type === 'lab_result') ? 'Lab Result sent to ' . count($target_users) . ' user(s) successfully!' : 'Message sent to ' . count($target_users) . ' user(s) successfully!';
                    
                    // Log staff announcement action
                    $targetUserNames = implode(', ', array_map(function($uid) use ($pdo) {
                        $stmt = $pdo->prepare("SELECT full_name FROM sitio1_users WHERE id = ?");
                        $stmt->execute([$uid]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                        return $user['full_name'] ?? 'Unknown';
                    }, $target_users));
                    
                    logActivity(
                        $pdo,
                        $staffId,
                        'send_announcement',
                        'staff',
                        $announcementId,
                        [
                            'title' => $title,
                            'audience_type' => 'specific',
                            'target_count' => count($target_users),
                            'announcement_type' => $announcement_type,
                            'target_users' => $targetUserNames
                        ]
                    );
                } elseif ($audience_type === 'public') {
                    createAnnouncementNotification($announcementId, $title);
                    // Send email to all approved users
                    $stmtAll = $pdo->prepare("SELECT email, full_name FROM sitio1_users WHERE approved = TRUE AND email IS NOT NULL AND email != ''");
                    $stmtAll->execute();
                    $allUserInfos = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
                    $sentCount = 0;
                    foreach ($allUserInfos as $userInfo) {
                        if (!empty($userInfo['email'])) {
                            if (sendAnnouncementEmail($userInfo['email'], $userInfo['full_name'], $title, $message, $announcement_type, $image_path)) {
                                $sentCount++;
                            }
                        }
                    }
                    $success = 'Message broadcasted to all users successfully! Email sent to ' . $sentCount . ' user(s).';
                    
                    // Log staff announcement action
                    logActivity(
                        $pdo,
                        $staffId,
                        'send_announcement',
                        'staff',
                        $announcementId,
                        [
                            'title' => $title,
                            'audience_type' => 'public',
                            'target_count' => $sentCount,
                            'announcement_type' => $announcement_type
                        ]
                    );
                } else {
                    // For landing_page announcements, no notifications needed
                    $success = 'Landing page announcement published successfully!';
                    
                    // Log staff announcement action
                    logActivity(
                        $pdo,
                        $staffId,
                        'send_announcement',
                        'staff',
                        $announcementId,
                        [
                            'title' => $title,
                            'audience_type' => $audience_type,
                            'announcement_type' => $announcement_type
                        ]
                    );
                }

                // Prevent duplicate POST on refresh: redirect to same page with success message
                $_SESSION['announcement_success'] = $success;
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit();
            } catch (PDOException $e) {
                $error = 'Error sending message: ' . $e->getMessage();
            }
        }
    } else {
        $error = 'Please fill in all required fields.';
    }
}

// Handle edit operation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_announcement'])) {
    $id = $_POST['id'];
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    $priority = $_POST['priority'];
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;

    try {
        $stmt = $pdo->prepare("UPDATE sitio1_announcements SET title = ?, message = ?, priority = ?, expiry_date = ? WHERE id = ? AND staff_id = ?");
        $stmt->execute([$title, $message, $priority, $expiry_date, $id, $staffId]);
        
        // Log edit action
        logActivity(
            $pdo,
            $staffId,
            'edit_announcement',
            'staff',
            $id,
            [
                'title' => $title,
                'priority' => $priority
            ]
        );
        
        $success = 'Announcement updated successfully!';
    } catch (PDOException $e) {
        $error = 'Error updating announcement: ' . $e->getMessage();
    }
}

// Handle archive operation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_announcement'])) {
    $id = $_POST['id'];
    try {
        // Get announcement title for logging
        $stmtTitle = $pdo->prepare("SELECT title FROM sitio1_announcements WHERE id = ?");
        $stmtTitle->execute([$id]);
        $announcement = $stmtTitle->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("UPDATE sitio1_announcements SET status = 'archived' WHERE id = ? AND staff_id = ?");
        $stmt->execute([$id, $staffId]);
        
        // Log archive action
        logActivity(
            $pdo,
            $staffId,
            'delete_announcement',
            'staff',
            $id,
            [
                'title' => $announcement['title'] ?? 'Unknown',
                'action' => 'archived'
            ]
        );
        
        $success = 'Announcement archived successfully!';
    } catch (PDOException $e) {
        $error = 'Error archiving announcement: ' . $e->getMessage();
    }
}

// Handle repost operation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['repost_announcement'])) {
    $id = $_POST['id'];
    try {
        // Get announcement title for logging
        $stmtTitle = $pdo->prepare("SELECT title FROM sitio1_announcements WHERE id = ?");
        $stmtTitle->execute([$id]);
        $announcement = $stmtTitle->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("UPDATE sitio1_announcements SET status = 'active', post_date = NOW() WHERE id = ? AND staff_id = ?");
        $stmt->execute([$id, $staffId]);
        
        // Log repost action (treat as send_announcement)
        logActivity(
            $pdo,
            $staffId,
            'send_announcement',
            'staff',
            $id,
            [
                'title' => $announcement['title'] ?? 'Unknown',
                'action' => 'reposted'
            ]
        );
        
        $success = 'Announcement reposted successfully!';
    } catch (PDOException $e) {
        $error = 'Error reposting announcement: ' . $e->getMessage();
    }
}

// Handle delete operation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_announcement'])) {
    $id = $_POST['id'];
    try {
        $stmtTitle = $pdo->prepare("SELECT title FROM sitio1_announcements WHERE id = ? AND staff_id = ? AND status = 'archived'");
        $stmtTitle->execute([$id, $staffId]);
        $announcement = $stmtTitle->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("UPDATE sitio1_announcements SET status = 'deleted' WHERE id = ? AND staff_id = ? AND status = 'archived'");
        $stmt->execute([$id, $staffId]);

        if ($stmt->rowCount() > 0) {
            logActivity(
                $pdo,
                $staffId,
                'delete_announcement',
                'staff',
                $id,
                [
                    'title' => $announcement['title'] ?? 'Unknown',
                    'action' => 'deleted'
                ]
            );
        }

        $success = 'Announcement deleted permanently!';
    } catch (PDOException $e) {
        $error = 'Error deleting announcement: ' . $e->getMessage();
    }
}

// Get all announcements by this staff
$activeAnnouncements = [];
$archivedAnnouncements = [];

try {
    $stmt = $pdo->prepare("SELECT a.*, 
                          s.full_name AS staff_full_name, s.position AS staff_position,
                          COALESCE(MAX(av.seen_count), 0) as seen_count,
                          COUNT(CASE WHEN u.id IS NOT NULL AND ua.status = 'accepted' THEN 1 END) as accepted_count,
                          COUNT(CASE WHEN u.id IS NOT NULL AND ua.status = 'dismissed' THEN 1 END) as dismissed_count,
                          COUNT(CASE WHEN u.id IS NOT NULL AND ua.status IS NULL THEN 1 END) as pending_count
                          FROM sitio1_announcements a
                          JOIN sitio1_staff s ON a.staff_id = s.id
                          LEFT JOIN (
                              SELECT announcement_id, COUNT(*) AS seen_count
                              FROM announcement_views
                              GROUP BY announcement_id
                          ) av ON av.announcement_id = a.id
                          LEFT JOIN sitio1_users u ON u.approved = TRUE AND a.audience_type IN ('public', 'specific')
                          LEFT JOIN user_announcements ua ON ua.announcement_id = a.id AND ua.user_id = u.id
                          WHERE a.status = 'active'
                          GROUP BY a.id
                          ORDER BY a.post_date DESC");
    $stmt->execute();
    $activeAnnouncements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT a.*, s.full_name AS staff_full_name, s.position AS staff_position
                          FROM sitio1_announcements a
                          JOIN sitio1_staff s ON a.staff_id = s.id
                          WHERE a.status = 'archived'
                          ORDER BY a.post_date DESC");
    $stmt->execute();
    $archivedAnnouncements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get detailed responses
    foreach ($activeAnnouncements as &$announcement) {
        $stmt = $pdo->prepare("SELECT u.id, u.full_name, ua.response_date 
                              FROM user_announcements ua
                              JOIN sitio1_users u ON ua.user_id = u.id
                              WHERE ua.announcement_id = ? AND ua.status = 'accepted'
                              ORDER BY ua.response_date DESC");
        $stmt->execute([$announcement['id']]);
        $announcement['accepted_users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT u.id, u.full_name, ua.response_date 
                              FROM user_announcements ua
                              JOIN sitio1_users u ON ua.user_id = u.id
                              WHERE ua.announcement_id = ? AND ua.status = 'dismissed'
                              ORDER BY ua.response_date DESC");
        $stmt->execute([$announcement['id']]);
        $announcement['dismissed_users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT u.id, u.full_name 
                              FROM sitio1_users u
                              WHERE u.approved = TRUE AND u.id NOT IN (
                                  SELECT user_id FROM user_announcements 
                                  WHERE announcement_id = ?
                              )");
        $stmt->execute([$announcement['id']]);
        $announcement['pending_users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($announcement['audience_type'] === 'specific') {
            $stmt = $pdo->prepare("SELECT u.id, u.full_name 
                                  FROM announcement_targets at
                                  JOIN sitio1_users u ON at.user_id = u.id
                                  WHERE at.announcement_id = ?");
            $stmt->execute([$announcement['id']]);
            $announcement['target_users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    unset($announcement);
} catch (PDOException $e) {
    $error = 'Error fetching messages: ' . $e->getMessage();
}

// Get all users for targeting
$allUsers = [];
try {
    $stmt = $pdo->prepare("SELECT id, full_name, username FROM sitio1_users WHERE approved = TRUE AND status = 'approved' ORDER BY full_name");
    $stmt->execute();
    $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error fetching users: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcement Management - Barangay Luz Health Center</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/asssets/css/normalize.css">
    <link rel="stylesheet" href="../asssets/css/admin-announcement.css">

</head>

<body class="bg-gray-50 min-h-screen">

    <!-- Modern Loader Animation Overlay -->
    <div id="announcement-loading" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.95); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(5px);">
        <div class="cht-loader-bg" style="background: white; padding: 40px; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.2); text-align: center; max-width: 500px;">
            <div class="cht-loader-unique" style="margin-bottom: 30px;">
                <div class="cht-loader-bounce" style="width: 20px; height: 20px; background-color: #3498db; border-radius: 50%; display: inline-block; animation: bounce 1.4s infinite ease-in-out both; margin: 0 5px;"></div>
                <div class="cht-loader-bounce" style="width: 20px; height: 20px; background-color: #3498db; border-radius: 50%; display: inline-block; animation: bounce 1.4s infinite ease-in-out both; animation-delay: -0.32s; margin: 0 5px;"></div>
                <div class="cht-loader-bounce" style="width: 20px; height: 20px; background-color: #3498db; border-radius: 50%; display: inline-block; animation: bounce 1.4s infinite ease-in-out both; animation-delay: -0.16s; margin: 0 5px;"></div>
            </div>
            <div class="cht-loader-text" id="announcement-loading-message" style="font-size: 20px; font-weight: 600; color: #2c3e50; margin-bottom: 15px;">Processing your announcement...</div>
            <span class="mt-3 text-base text-slate-500 text-center" style="font-family: Poppins, Arial, Helvetica, sans-serif; font-weight: 400;">
                Please wait while we process your announcement.<br>
                Do not close or refresh this page.
            </span>
            <div style="margin-top: 20px; width: 100%; background: #ecf0f1; height: 4px; border-radius: 4px; overflow: hidden;">
                <div style="width: 100%; height: 100%; background: linear-gradient(90deg, #3498db, #2980b9); animation: progress 2s infinite;"></div>
            </div>
        </div>
    </div>

    <script>
    // Expose all active announcements to JS for modal
    window.allActiveAnnouncementsData = <?php echo json_encode($activeAnnouncements, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    
    // Show announcement loading with dynamic message
    function showAnnouncementLoading(buttonType) {
        const audienceType = document.querySelector('input[name="audience_type"]:checked')?.value;
        const title = document.querySelector('input[name="title"]').value.trim();
        const message = document.querySelector('textarea[name="message"]').value.trim();
        
        if (!title || !message) {
            showNotification('error', 'Please fill in all required fields');
            return false;
        }
        
        if (audienceType === 'specific') {
            const checkedUsers = document.querySelectorAll('.user-checkbox:checked');
            if (checkedUsers.length === 0) {
                showNotification('error', 'Please select at least one user for specific announcement');
                return false;
            }
        }
        
        if (audienceType === 'landing_page') {
            return true;
        }
        
        const loadingDiv = document.getElementById('announcement-loading');
        const loadingMsg = document.getElementById('announcement-loading-message');
        
        if (loadingDiv && loadingMsg) {
            let msg = '';
            if (audienceType === 'specific') {
                if (buttonType === 'lab_result') {
                    msg = 'Sending Lab Results to specific residents and sending email notifications...';
                } else {
                    msg = 'Sending General Announcement to specific residents and sending email notifications...';
                }
            } else if (audienceType === 'public') {
                msg = 'Broadcasting General Announcement to all residents and sending email notifications...';
            }
            loadingMsg.textContent = msg;
            loadingDiv.style.display = 'flex';
        }
        return true;
    }
    </script>

    <style>
        @keyframes bounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }
        @keyframes progress {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
    </style>

    <div class="container w-full max-w-none px-14 py-10">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-medium mb-2">Announcement Management</h1>
            <p class="text-lg text-gray-500">Create and manage community health announcements</p>
        </div>

        <!-- Stats -->
        <div class="stats-grid mb-6">
            <!-- ACTIVE ANNOUNCEMENTS -->
            <div class="stat-card">
                <div class="flex flex-col md:flex-row sm:flex-row items-center justify-between mb-4">
                    <div class="stat-value">
                        <?= count($activeAnnouncements) ?>
                    </div>
                    <div>
                        <svg width="50" height="50" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M28.5749 22.0029C29.0802 21.304 30.0566 21.1463 30.7559 21.6509C31.4554 22.1562 31.6132 23.1344 31.1079 23.8339L25.275 31.9089L25.2689 31.917C25.076 32.1799 24.8258 32.3976 24.5385 32.5518C24.2512 32.7059 23.9323 32.7927 23.6067 32.8081C23.2812 32.8234 22.9569 32.7655 22.6566 32.6393C22.356 32.5128 22.086 32.3213 21.8692 32.0777L21.8611 32.0676L18.9884 28.7879C18.4199 28.1387 18.4857 27.151 19.1348 26.5825C19.7841 26.0141 20.7718 26.0798 21.3402 26.729L23.4317 29.1196L28.5749 22.0029Z" fill="#3C96E1"/>
<path d="M40.1029 20.8496C40.1026 19.9235 39.6914 19.0576 39.0063 18.4957H39.0042L26.785 8.45744L26.7789 8.45337C26.2783 8.03901 25.6486 7.8125 24.9987 7.8125C24.3488 7.8125 23.7191 8.03901 23.2185 8.45337L23.2124 8.45744L10.9911 18.4957C10.3071 19.0578 9.89482 19.9233 9.89453 20.8496V39.1886C9.89483 40.885 11.2229 42.1873 12.7754 42.1875H37.222C38.7745 42.1873 40.1026 40.885 40.1029 39.1886V20.8496ZM43.2279 39.1886C43.2276 42.5332 40.5771 45.3123 37.222 45.3125H12.7754C9.4203 45.3123 6.76983 42.5332 6.76953 39.1886V20.8496C6.76982 19.0058 7.58779 17.2474 9.00749 16.0807L21.2247 6.04858C22.2859 5.16981 23.6209 4.6875 24.9987 4.6875C26.3765 4.6875 27.7115 5.16781 28.7727 6.04655L28.7707 6.04858L40.9879 16.0807L41.2463 16.3066C42.5083 17.468 43.2276 19.1209 43.2279 20.8496V39.1886Z" fill="#3C96E1"/>
</svg>


                    </div>
                </div>
                <div class="text-xl font-medium text-gray-500">Active Announcements</div>
            </div>
            <!-- ARCHIVED ANNOUNCEMENTS -->
            <div class="stat-card">
                <div class="flex flex-col md:flex-row sm:flex-row items-center justify-between mb-4">
                    <div class="stat-value">
                        <?= count($archivedAnnouncements) ?>
                    </div>
                    <div>
                        <svg width="55" height="55" viewBox="0 0 55 55" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M48.125 10.3125H6.875C5.96332 10.3125 5.08898 10.6747 4.44432 11.3193C3.79966 11.964 3.4375 12.8383 3.4375 13.75V18.9063C3.4375 19.8179 3.79966 20.6923 4.44432 21.3369C5.08898 21.9816 5.96332 22.3438 6.875 22.3438V41.25C6.875 42.1617 7.23716 43.036 7.88182 43.6807C8.52648 44.3253 9.40082 44.6875 10.3125 44.6875H44.6875C45.5992 44.6875 46.4735 44.3253 47.1182 43.6807C47.7628 43.036 48.125 42.1617 48.125 41.25V22.3438C49.0367 22.3438 49.911 21.9816 50.5557 21.3369C51.2003 20.6923 51.5625 19.8179 51.5625 18.9063V13.75C51.5625 12.8383 51.2003 11.964 50.5557 11.3193C49.911 10.6747 49.0367 10.3125 48.125 10.3125ZM44.6875 41.25H10.3125V22.3438H44.6875V41.25ZM48.125 18.9063H6.875V13.75H48.125V18.9063ZM20.625 29.2188C20.625 28.7629 20.8061 28.3257 21.1284 28.0034C21.4507 27.6811 21.8879 27.5 22.3438 27.5H32.6563C33.1121 27.5 33.5493 27.6811 33.8716 28.0034C34.1939 28.3257 34.375 28.7629 34.375 29.2188C34.375 29.6746 34.1939 30.1118 33.8716 30.4341C33.5493 30.7564 33.1121 30.9375 32.6563 30.9375H22.3438C21.8879 30.9375 21.4507 30.7564 21.1284 30.4341C20.8061 30.1118 20.625 29.6746 20.625 29.2188Z" fill="#D97706"/>
</svg>


                    </div>
                </div>
                <div class="text-xl font-medium text-gray-500">Archived Announcements</div>
            </div>
            <!-- REGISTERED USERS -->
            <div class="stat-card">
                <div class="flex flex-col md:flex-row sm:flex-row items-center justify-between mb-4">
                    <div class="stat-value">
                        <?= count($allUsers) ?>
                    </div>
                    <div>
                        <svg width="50" height="50" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M29.689 15.625C30.9251 15.625 32.1335 15.2584 33.1613 14.5717C34.1891 13.8849 34.9902 12.9088 35.4633 11.7668C35.9363 10.6247 36.0601 9.36807 35.8189 8.15569C35.5778 6.94331 34.9825 5.82966 34.1084 4.95559C33.2343 4.08151 32.1207 3.48625 30.9083 3.24509C29.6959 3.00394 28.4393 3.12771 27.2972 3.60076C26.1552 4.0738 25.1791 4.87488 24.4923 5.90269C23.8056 6.9305 23.439 8.13887 23.439 9.375C23.439 11.0326 24.0975 12.6223 25.2696 13.7944C26.4417 14.9665 28.0314 15.625 29.689 15.625ZM29.689 6.25C30.3071 6.25 30.9113 6.43328 31.4252 6.77666C31.9391 7.12004 32.3396 7.6081 32.5761 8.17912C32.8127 8.75014 32.8745 9.37847 32.754 9.98466C32.6334 10.5909 32.3358 11.1477 31.8987 11.5847C31.4617 12.0217 30.9049 12.3194 30.2987 12.44C29.6925 12.5605 29.0641 12.4987 28.4931 12.2621C27.9221 12.0256 27.434 11.6251 27.0907 11.1112C26.7473 10.5973 26.564 9.99307 26.564 9.375C26.564 8.5462 26.8932 7.75134 27.4793 7.16529C28.0653 6.57924 28.8602 6.25 29.689 6.25ZM42.189 28.125C42.189 28.5394 42.0244 28.9368 41.7314 29.2299C41.4383 29.5229 41.0409 29.6875 40.6265 29.6875C33.73 29.6875 30.2847 26.209 27.5171 23.4141C26.982 22.873 26.4703 22.3594 25.9546 21.8828L23.3316 27.9141L30.5972 33.1035C30.7996 33.2481 30.9645 33.4389 31.0783 33.66C31.1921 33.8812 31.2515 34.1263 31.2515 34.375V45.3125C31.2515 45.7269 31.0869 46.1243 30.7939 46.4174C30.5008 46.7104 30.1034 46.875 29.689 46.875C29.2746 46.875 28.8772 46.7104 28.5842 46.4174C28.2911 46.1243 28.1265 45.7269 28.1265 45.3125V35.1797L22.0581 30.8438L15.4976 45.9355C15.3762 46.2148 15.1758 46.4525 14.9211 46.6194C14.6664 46.7863 14.3685 46.8751 14.064 46.875C13.8494 46.8755 13.6372 46.8309 13.441 46.7441C13.0611 46.579 12.7624 46.2698 12.6104 45.8846C12.4584 45.4993 12.4656 45.0694 12.6304 44.6895L23.1929 20.3984C21.3746 20.0762 19.107 20.6328 16.4156 22.0742C14.2691 23.2582 12.2657 24.6849 10.4449 26.3262C10.1409 26.5984 9.74257 26.741 9.33492 26.7234C8.92727 26.7058 8.54268 26.5294 8.26336 26.232C7.98403 25.9345 7.83214 25.5396 7.84014 25.1317C7.84815 24.7237 8.01542 24.3351 8.30619 24.0488C8.79447 23.5898 20.355 12.8711 27.5874 19.1504C28.3355 19.7988 29.0484 20.5176 29.7359 21.2148C32.4605 23.9648 35.0328 26.5625 40.6265 26.5625C41.0409 26.5625 41.4383 26.7271 41.7314 27.0201C42.0244 27.3132 42.189 27.7106 42.189 28.125Z" fill="#9333EA"/>
</svg>

                    </div>
                </div>
                <div class="text-xl font-medium text-gray-500">Registered Users</div>
            </div>
            <!-- TOTAL RESPONSES -->
            <div class="stat-card">
                <div class="flex flex-col md:flex-row sm:flex-row items-center justify-between mb-4">
                    <div class="stat-value">
                        <?php
                        $totalResponses = 0;
                        foreach ($activeAnnouncements as $announcement) {
                            $totalResponses += $announcement['accepted_count'] + $announcement['dismissed_count'];
                        }
                        echo $totalResponses;
                        ?>
                    </div>
                    <div>
                        <svg width="50" height="50" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M43.75 39.0625H42.1875V7.8125C42.1875 7.3981 42.0229 7.00067 41.7299 6.70765C41.4368 6.41462 41.0394 6.25 40.625 6.25H29.6875C29.2731 6.25 28.8757 6.41462 28.5826 6.70765C28.2896 7.00067 28.125 7.3981 28.125 7.8125V15.625H18.75C18.3356 15.625 17.9382 15.7896 17.6451 16.0826C17.3521 16.3757 17.1875 16.7731 17.1875 17.1875V25H9.375C8.9606 25 8.56317 25.1646 8.27015 25.4576C7.97712 25.7507 7.8125 26.1481 7.8125 26.5625V39.0625H6.25C5.8356 39.0625 5.43817 39.2271 5.14515 39.5201C4.85212 39.8132 4.6875 40.2106 4.6875 40.625C4.6875 41.0394 4.85212 41.4368 5.14515 41.7299C5.43817 42.0229 5.8356 42.1875 6.25 42.1875H43.75C44.1644 42.1875 44.5618 42.0229 44.8549 41.7299C45.1479 41.4368 45.3125 41.0394 45.3125 40.625C45.3125 40.2106 45.1479 39.8132 44.8549 39.5201C44.5618 39.2271 44.1644 39.0625 43.75 39.0625ZM31.25 9.375H39.0625V39.0625H31.25V9.375ZM20.3125 18.75H28.125V39.0625H20.3125V18.75ZM10.9375 28.125H17.1875V39.0625H10.9375V28.125Z" fill="#22C55E"/>
</svg>

                    </div>
                </div>
                <div class="text-xl font-medium text-gray-500">Total Responses</div>
            </div>
        </div>

        <!-- CREATE ANNOUNCEMENT TITLE HEADER -->
        <div>
            <div class="border-b-2 border-gray-100 pb-4 mb-6">
                <h2 class="text-2xl font-bold mb-2">Create New Announcement</h2>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
            <script>document.addEventListener('DOMContentLoaded', function () { showNotification('error', <?= json_encode($error) ?>); });</script>
        <?php endif; ?>
        <?php if ($success): ?>
            <script>document.addEventListener('DOMContentLoaded', function () { showNotification('success', <?= json_encode($success) ?>); });</script>
        <?php endif; ?>
        <script>
            // Notification prompt with smooth show/hide
            function showNotification(type, message, duration = 5000) {
                const existingNotifications = document.querySelectorAll('.custom-notification');
                existingNotifications.forEach(notification => notification.remove());

                const notification = document.createElement('div');
                notification.className = `custom-notification fixed top-6 right-6 z-50 px-6 py-4 rounded-xl shadow-lg border-2 ${type === 'error' ? 'alert-error' :
                    type === 'success' ? 'alert-success' :
                        type === 'warning' ? 'bg-yellow-100 text-yellow-800 border-yellow-200' :
                            'bg-blue-100 text-blue-800 border-blue-200'
                    }`;

                const icon = type === 'error' ? 'fa-exclamation-circle' :
                    type === 'success' ? 'fa-check-circle' :
                        type === 'warning' ? 'fa-exclamation-triangle' :
                            'fa-info-circle';

                notification.innerHTML = `
                <div class="flex items-center gap-2">
                    <i class="fas ${icon} text-xl"></i>
                    <span class="font-semibold">${message}</span>
                </div>
            `;

                document.body.appendChild(notification);
                // Force reflow to enable transition
                void notification.offsetWidth;
                notification.classList.add('show');

                const hideNotification = () => {
                    notification.classList.remove('show');
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 400);
                };

                const timeoutId = setTimeout(hideNotification, duration);

                // Allow manual dismissal by clicking
                notification.style.cursor = 'pointer';
                notification.addEventListener('click', () => {
                    clearTimeout(timeoutId);
                    hideNotification();
                });
            }
        </script>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- FORM START (wraps LEFT + CENTER columns only) -->
            <form method="POST" action="" enctype="multipart/form-data"
                class="lg:col-span-2 grid grid-cols-1 lg:grid-cols-2 gap-6"
                onsubmit="return showAnnouncementLoading(event.submitter && event.submitter.name === 'post_lab_result' ? 'lab_result' : 'basic')">

                <!-- Left Column - Form -->
                <div class="h-full">
                    <div class="card" style="height:auto;min-height:0;">
                        <div>

                            <!-- Title -->
                            <div class="form-group">
                                <label class="form-label">Title of the Announcement <span
                                        style="color: #FF5555;">*</span></label>
                                <input type="text" name="title" required class="form-control mb-4"
                                    placeholder="Enter Announcement Title" maxlength="100" id="announcement-title">
                            </div>

                            <!-- Message -->
                            <div class="form-group">
                                <label class="form-label">Announcement Message <span
                                        style="color: #FF5555;">*</span></label>
                                <textarea name="message" required class="form-control mb-4"
                                    placeholder="Type your announcement message here..." maxlength="500"
                                    rows="13" id="announcement-message"></textarea>
                            </div>

                            <!-- Settings -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="form-group">
                                    <label class="form-label">Priority <span style="color: #FF5555;">*</span></label>
                                        <div class="relative">
                                            <select name="priority" class="form-control appearance-none w-full text-lg flex items-center justify-center border border-blue-300 rounded-lg py-3 pl-12 pr-12 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                                <option value="">Select Priority</option>
                                                <option value="normal">Normal</option>
                                                <option value="medium">Medium</option>
                                                <option value="high">High</option>
                                            </select>
                                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-5">
                                                <svg class="h-6 w-6 text-[#3C96E1]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6" />
                                                </svg>
                                            </div>
                                        </div>
                                </div>

                                <div class="form-group">
    <label class="form-label">
        Expiry Date <span style="color: #FF5555;">*</span>
    </label>

    <div class="relative">
        <input 
            type="date" 
            name="expiry_date" 
            class="form-control pr-10"
            min="<?= date('Y-m-d') ?>" 
            id="announcement-expiry"
        >

        <!-- Custom Icon -->
        <button 
            type="button"
            onclick="document.getElementById('announcement-expiry').showPicker()"
            class="absolute right-6 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700"
        >
            <!-- Calendar Icon -->
            <svg width="25" height="25" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M24.375 3.75H21.5625V2.8125C21.5625 2.56386 21.4637 2.3254 21.2879 2.14959C21.1121 1.97377 20.8736 1.875 20.625 1.875C20.3764 1.875 20.1379 1.97377 19.9621 2.14959C19.7863 2.3254 19.6875 2.56386 19.6875 2.8125V3.75H10.3125V2.8125C10.3125 2.56386 10.2137 2.3254 10.0379 2.14959C9.8621 1.97377 9.62364 1.875 9.375 1.875C9.12636 1.875 8.8879 1.97377 8.71209 2.14959C8.53627 2.3254 8.4375 2.56386 8.4375 2.8125V3.75H5.625C5.12772 3.75 4.65081 3.94754 4.29917 4.29917C3.94754 4.65081 3.75 5.12772 3.75 5.625V24.375C3.75 24.8723 3.94754 25.3492 4.29917 25.7008C4.65081 26.0525 5.12772 26.25 5.625 26.25H24.375C24.8723 26.25 25.3492 26.0525 25.7008 25.7008C26.0525 25.3492 26.25 24.8723 26.25 24.375V5.625C26.25 5.12772 26.0525 4.65081 25.7008 4.29917C25.3492 3.94754 24.8723 3.75 24.375 3.75ZM8.4375 5.625V6.5625C8.4375 6.81114 8.53627 7.0496 8.71209 7.22541C8.8879 7.40123 9.12636 7.5 9.375 7.5C9.62364 7.5 9.8621 7.40123 10.0379 7.22541C10.2137 7.0496 10.3125 6.81114 10.3125 6.5625V5.625H19.6875V6.5625C19.6875 6.81114 19.7863 7.0496 19.9621 7.22541C20.1379 7.40123 20.3764 7.5 20.625 7.5C20.8736 7.5 21.1121 7.40123 21.2879 7.22541C21.4637 7.0496 21.5625 6.81114 21.5625 6.5625V5.625H24.375V9.375H5.625V5.625H8.4375ZM24.375 24.375H5.625V11.25H24.375V24.375ZM16.4062 15.4688C16.4062 15.7469 16.3238 16.0188 16.1693 16.25C16.0147 16.4813 15.7951 16.6615 15.5381 16.768C15.2812 16.8744 14.9984 16.9022 14.7257 16.848C14.4529 16.7937 14.2023 16.6598 14.0056 16.4631C13.809 16.2665 13.675 16.0159 13.6208 15.7431C13.5665 15.4703 13.5944 15.1876 13.7008 14.9306C13.8072 14.6736 13.9875 14.454 14.2187 14.2995C14.45 14.145 14.7219 14.0625 15 14.0625C15.373 14.0625 15.7306 14.2107 15.9944 14.4744C16.2581 14.7381 16.4062 15.0958 16.4062 15.4688ZM21.5625 15.4688C21.5625 15.7469 21.48 16.0188 21.3255 16.25C21.171 16.4813 20.9514 16.6615 20.6944 16.768C20.4374 16.8744 20.1547 16.9022 19.8819 16.848C19.6091 16.7937 19.3585 16.6598 19.1619 16.4631C18.9652 16.2665 18.8313 16.0159 18.777 15.7431C18.7228 15.4703 18.7506 15.1876 18.857 14.9306C18.9635 14.6736 19.1437 14.454 19.375 14.2995C19.6062 14.145 19.8781 14.0625 20.1562 14.0625C20.5292 14.0625 20.8869 14.2107 21.1506 14.4744C21.4143 14.7381 21.5625 15.0958 21.5625 15.4688ZM11.25 20.1562C11.25 20.4344 11.1675 20.7063 11.013 20.9375C10.8585 21.1688 10.6389 21.349 10.3819 21.4555C10.1249 21.5619 9.84219 21.5897 9.5694 21.5355C9.29662 21.4812 9.04605 21.3473 8.84938 21.1506C8.65271 20.954 8.51878 20.7034 8.46452 20.4306C8.41026 20.1578 8.43811 19.8751 8.54454 19.6181C8.65098 19.3611 8.83122 19.1415 9.06248 18.987C9.29374 18.8325 9.56562 18.75 9.84375 18.75C10.2167 18.75 10.5744 18.8982 10.8381 19.1619C11.1018 19.4256 11.25 19.7833 11.25 20.1562ZM16.4062 20.1562C16.4062 20.4344 16.3238 20.7063 16.1693 20.9375C16.0147 21.1688 15.7951 21.349 15.5381 21.4555C15.2812 21.5619 14.9984 21.5897 14.7257 21.5355C14.4529 21.4812 14.2023 21.3473 14.0056 21.1506C13.809 20.954 13.675 20.7034 13.6208 20.4306C13.5665 20.1578 13.5944 19.8751 13.7008 19.6181C13.8072 19.3611 13.9875 19.1415 14.2187 18.987C14.45 18.8325 14.7219 18.75 15 18.75C15.373 18.75 15.7306 18.8982 15.9944 19.1619C16.2581 19.4256 16.4062 19.7833 16.4062 20.1562ZM21.5625 20.1562C21.5625 20.4344 21.48 20.7063 21.3255 20.9375C21.171 21.1688 20.9514 21.349 20.6944 21.4555C20.4374 21.5619 20.1547 21.5897 19.8819 21.5355C19.6091 21.4812 19.3585 21.3473 19.1619 21.1506C18.9652 20.954 18.8313 20.7034 18.777 20.4306C18.7228 20.1578 18.7506 19.8751 18.857 19.6181C18.9635 19.3611 19.1437 19.1415 19.375 18.987C19.6062 18.8325 19.8781 18.75 20.1562 18.75C20.5292 18.75 20.8869 18.8982 21.1506 19.1619C21.4143 19.4256 21.5625 19.7833 21.5625 20.1562Z" fill="#3C96E1"/>
</svg>

        </button>
    </div>
</div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Center Column -->
                <div class="h-full">
                    <div class="card" style="height:auto;min-height:0;">
                        <div class="flex flex-col">

                            <!-- Audience -->
                            <div class="form-group">
                                <label class="form-label mb-2">Choose Audience <span
                                        style="color: #FF5555;">*</span></label>
                                <div class="radio-group">
                                    <!-- LANDING PAGE -->
                                    <div class="radio-option">
                                        <input type="radio" id="audience-landing" name="audience_type"
                                            value="landing_page" class="radio-input" checked>
                                        <label for="audience-landing" class="radio-label">
                                            <div class="flex flex-col md:flex-row items-center justify-between">
                                                <div class="flex flex-col">
                                                    <h2 class="font-normal text-xl mb-2 text-[#1D85DD]">Landing
                                                        Page</h2>
                                                    <span class="text-lg font-normal text-gray-500">Send directly to
                                                        website</span>
                                                </div>
                                                <div>
                                                    <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M21.875 20.0011C21.875 20.372 21.765 20.7345 21.559 21.0428C21.353 21.3512 21.0601 21.5915 20.7175 21.7334C20.3749 21.8753 19.9979 21.9125 19.6342 21.8401C19.2705 21.7678 18.9364 21.5892 18.6742 21.327C18.412 21.0647 18.2334 20.7307 18.161 20.3669C18.0887 20.0032 18.1258 19.6262 18.2677 19.2836C18.4096 18.941 18.65 18.6482 18.9583 18.4421C19.2666 18.2361 19.6292 18.1261 20 18.1261C20.4973 18.1261 20.9742 18.3237 21.3258 18.6753C21.6775 19.027 21.875 19.5039 21.875 20.0011ZM13.125 18.1261C12.7542 18.1261 12.3916 18.2361 12.0833 18.4421C11.775 18.6482 11.5346 18.941 11.3927 19.2836C11.2508 19.6262 11.2137 20.0032 11.286 20.3669C11.3584 20.7307 11.537 21.0647 11.7992 21.327C12.0614 21.5892 12.3955 21.7678 12.7592 21.8401C13.1229 21.9125 13.4999 21.8753 13.8425 21.7334C14.1851 21.5915 14.478 21.3512 14.684 21.0428C14.89 20.7345 15 20.372 15 20.0011C15 19.5039 14.8025 19.027 14.4508 18.6753C14.0992 18.3237 13.6223 18.1261 13.125 18.1261ZM26.875 18.1261C26.5042 18.1261 26.1416 18.2361 25.8333 18.4421C25.525 18.6482 25.2846 18.941 25.1427 19.2836C25.0008 19.6262 24.9637 20.0032 25.036 20.3669C25.1084 20.7307 25.287 21.0647 25.5492 21.327C25.8114 21.5892 26.1455 21.7678 26.5092 21.8401C26.8729 21.9125 27.2499 21.8753 27.5925 21.7334C27.9351 21.5915 28.228 21.3512 28.434 21.0428C28.64 20.7345 28.75 20.372 28.75 20.0011C28.75 19.5039 28.5525 19.027 28.2008 18.6753C27.8492 18.3237 27.3723 18.1261 26.875 18.1261ZM36.25 20.0011C36.2506 22.8067 35.5249 25.5646 34.1434 28.0064C32.7619 30.4482 30.7718 32.4908 28.3667 33.9353C25.9616 35.3797 23.2236 36.177 20.419 36.2493C17.6144 36.3216 14.8388 35.6666 12.3625 34.348L7.04219 36.1215C6.6017 36.2684 6.129 36.2897 5.67707 36.183C5.22515 36.0764 4.81185 35.846 4.48352 35.5176C4.15518 35.1893 3.92477 34.776 3.81812 34.3241C3.71147 33.8721 3.73279 33.3994 3.87969 32.959L5.65312 27.6386C4.49398 25.4593 3.84656 23.0446 3.76 20.5777C3.67343 18.1108 4.15001 15.6566 5.15355 13.4014C6.15708 11.1461 7.66121 9.14919 9.55175 7.56207C11.4423 5.97496 13.6696 4.83939 16.0645 4.24158C18.4594 3.64376 20.9591 3.59941 23.3737 4.11188C25.7883 4.62435 28.0545 5.68018 30.0001 7.19923C31.9458 8.71827 33.5198 10.6606 34.6027 12.8788C35.6856 15.097 36.249 17.5327 36.25 20.0011ZM33.75 20.0011C33.7494 17.892 33.2636 15.8112 32.3301 13.9198C31.3967 12.0284 30.0406 10.3771 28.3669 9.0937C26.6931 7.81026 24.7466 6.92907 22.6778 6.51831C20.609 6.10756 18.4734 6.17824 16.4363 6.72489C14.3992 7.27155 12.5151 8.27952 10.9299 9.67082C9.34471 11.0621 8.10081 12.7995 7.29447 14.7484C6.48813 16.6974 6.14097 18.8057 6.27983 20.9103C6.4187 23.015 7.03988 25.0594 8.09531 26.8855C8.18389 27.0388 8.2389 27.2092 8.25667 27.3854C8.27445 27.5615 8.2546 27.7395 8.19844 27.9074L6.25 33.7511L12.0937 31.8027C12.221 31.7593 12.3546 31.7371 12.4891 31.7371C12.7086 31.7375 12.9242 31.7957 13.1141 31.9058C15.2044 33.1152 17.5763 33.7528 19.9913 33.7543C22.4063 33.7558 24.7791 33.1213 26.8709 31.9145C28.9627 30.7078 30.6999 28.9713 31.9075 26.88C33.1151 24.7886 33.7506 22.4161 33.75 20.0011Z" fill="#1D85DD"/>
</svg>



                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    <!-- ALL RESIDENTS -->
                                    <div class="radio-option">
                                        <input type="radio" id="audience-public" name="audience_type" value="public"
                                            class="radio-input">
                                        <label for="audience-public" class="radio-label">
                                            <div class="flex flex-col md:flex-row items-center justify-between">
                                                <div class="flex flex-col">
                                                    <h2 class="font-normal text-xl mb-2 text-[#1D85DD]">All Residents
                                                    </h2>
                                                    <span class="text-lg font-normal text-gray-500">Send directly to all
                                                        users</span>
                                                </div>
                                                <div>
                                                    <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M21.875 20.0011C21.875 20.372 21.765 20.7345 21.559 21.0428C21.353 21.3512 21.0601 21.5915 20.7175 21.7334C20.3749 21.8753 19.9979 21.9125 19.6342 21.8401C19.2705 21.7678 18.9364 21.5892 18.6742 21.327C18.412 21.0647 18.2334 20.7307 18.161 20.3669C18.0887 20.0032 18.1258 19.6262 18.2677 19.2836C18.4096 18.941 18.65 18.6482 18.9583 18.4421C19.2666 18.2361 19.6292 18.1261 20 18.1261C20.4973 18.1261 20.9742 18.3237 21.3258 18.6753C21.6775 19.027 21.875 19.5039 21.875 20.0011ZM13.125 18.1261C12.7542 18.1261 12.3916 18.2361 12.0833 18.4421C11.775 18.6482 11.5346 18.941 11.3927 19.2836C11.2508 19.6262 11.2137 20.0032 11.286 20.3669C11.3584 20.7307 11.537 21.0647 11.7992 21.327C12.0614 21.5892 12.3955 21.7678 12.7592 21.8401C13.1229 21.9125 13.4999 21.8753 13.8425 21.7334C14.1851 21.5915 14.478 21.3512 14.684 21.0428C14.89 20.7345 15 20.372 15 20.0011C15 19.5039 14.8025 19.027 14.4508 18.6753C14.0992 18.3237 13.6223 18.1261 13.125 18.1261ZM26.875 18.1261C26.5042 18.1261 26.1416 18.2361 25.8333 18.4421C25.525 18.6482 25.2846 18.941 25.1427 19.2836C25.0008 19.6262 24.9637 20.0032 25.036 20.3669C25.1084 20.7307 25.287 21.0647 25.5492 21.327C25.8114 21.5892 26.1455 21.7678 26.5092 21.8401C26.8729 21.9125 27.2499 21.8753 27.5925 21.7334C27.9351 21.5915 28.228 21.3512 28.434 21.0428C28.64 20.7345 28.75 20.372 28.75 20.0011C28.75 19.5039 28.5525 19.027 28.2008 18.6753C27.8492 18.3237 27.3723 18.1261 26.875 18.1261ZM36.25 20.0011C36.2506 22.8067 35.5249 25.5646 34.1434 28.0064C32.7619 30.4482 30.7718 32.4908 28.3667 33.9353C25.9616 35.3797 23.2236 36.177 20.419 36.2493C17.6144 36.3216 14.8388 35.6666 12.3625 34.348L7.04219 36.1215C6.6017 36.2684 6.129 36.2897 5.67707 36.183C5.22515 36.0764 4.81185 35.846 4.48352 35.5176C4.15518 35.1893 3.92477 34.776 3.81812 34.3241C3.71147 33.8721 3.73279 33.3994 3.87969 32.959L5.65312 27.6386C4.49398 25.4593 3.84656 23.0446 3.76 20.5777C3.67343 18.1108 4.15001 15.6566 5.15355 13.4014C6.15708 11.1461 7.66121 9.14919 9.55175 7.56207C11.4423 5.97496 13.6696 4.83939 16.0645 4.24158C18.4594 3.64376 20.9591 3.59941 23.3737 4.11188C25.7883 4.62435 28.0545 5.68018 30.0001 7.19923C31.9458 8.71827 33.5198 10.6606 34.6027 12.8788C35.6856 15.097 36.249 17.5327 36.25 20.0011ZM33.75 20.0011C33.7494 17.892 33.2636 15.8112 32.3301 13.9198C31.3967 12.0284 30.0406 10.3771 28.3669 9.0937C26.6931 7.81026 24.7466 6.92907 22.6778 6.51831C20.609 6.10756 18.4734 6.17824 16.4363 6.72489C14.3992 7.27155 12.5151 8.27952 10.9299 9.67082C9.34471 11.0621 8.10081 12.7995 7.29447 14.7484C6.48813 16.6974 6.14097 18.8057 6.27983 20.9103C6.4187 23.015 7.03988 25.0594 8.09531 26.8855C8.18389 27.0388 8.2389 27.2092 8.25667 27.3854C8.27445 27.5615 8.2546 27.7395 8.19844 27.9074L6.25 33.7511L12.0937 31.8027C12.221 31.7593 12.3546 31.7371 12.4891 31.7371C12.7086 31.7375 12.9242 31.7957 13.1141 31.9058C15.2044 33.1152 17.5763 33.7528 19.9913 33.7543C22.4063 33.7558 24.7791 33.1213 26.8709 31.9145C28.9627 30.7078 30.6999 28.9713 31.9075 26.88C33.1151 24.7886 33.7506 22.4161 33.75 20.0011Z" fill="#1D85DD"/>
</svg>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    <!-- SPECIFIC RESIDENT -->
                                    <div class="radio-option">
                                        <input type="radio" id="audience-specific" name="audience_type" value="specific"
                                            class="radio-input">
                                        <label for="audience-specific" class="radio-label">
                                            <div class="flex flex-col md:flex-row items-center justify-between">
                                                <div class="flex flex-col">
                                                    <h2 class="font-normal text-xl mb-2 text-[#1D85DD]">Specific
                                                        Resident
                                                    </h2>
                                                    <span class="text-lg font-normal text-gray-500">Send directly to
                                                        specific user</span>
                                                </div>
                                                <div>
                                                    <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M21.875 20.0011C21.875 20.372 21.765 20.7345 21.559 21.0428C21.353 21.3512 21.0601 21.5915 20.7175 21.7334C20.3749 21.8753 19.9979 21.9125 19.6342 21.8401C19.2705 21.7678 18.9364 21.5892 18.6742 21.327C18.412 21.0647 18.2334 20.7307 18.161 20.3669C18.0887 20.0032 18.1258 19.6262 18.2677 19.2836C18.4096 18.941 18.65 18.6482 18.9583 18.4421C19.2666 18.2361 19.6292 18.1261 20 18.1261C20.4973 18.1261 20.9742 18.3237 21.3258 18.6753C21.6775 19.027 21.875 19.5039 21.875 20.0011ZM13.125 18.1261C12.7542 18.1261 12.3916 18.2361 12.0833 18.4421C11.775 18.6482 11.5346 18.941 11.3927 19.2836C11.2508 19.6262 11.2137 20.0032 11.286 20.3669C11.3584 20.7307 11.537 21.0647 11.7992 21.327C12.0614 21.5892 12.3955 21.7678 12.7592 21.8401C13.1229 21.9125 13.4999 21.8753 13.8425 21.7334C14.1851 21.5915 14.478 21.3512 14.684 21.0428C14.89 20.7345 15 20.372 15 20.0011C15 19.5039 14.8025 19.027 14.4508 18.6753C14.0992 18.3237 13.6223 18.1261 13.125 18.1261ZM26.875 18.1261C26.5042 18.1261 26.1416 18.2361 25.8333 18.4421C25.525 18.6482 25.2846 18.941 25.1427 19.2836C25.0008 19.6262 24.9637 20.0032 25.036 20.3669C25.1084 20.7307 25.287 21.0647 25.5492 21.327C25.8114 21.5892 26.1455 21.7678 26.5092 21.8401C26.8729 21.9125 27.2499 21.8753 27.5925 21.7334C27.9351 21.5915 28.228 21.3512 28.434 21.0428C28.64 20.7345 28.75 20.372 28.75 20.0011C28.75 19.5039 28.5525 19.027 28.2008 18.6753C27.8492 18.3237 27.3723 18.1261 26.875 18.1261ZM36.25 20.0011C36.2506 22.8067 35.5249 25.5646 34.1434 28.0064C32.7619 30.4482 30.7718 32.4908 28.3667 33.9353C25.9616 35.3797 23.2236 36.177 20.419 36.2493C17.6144 36.3216 14.8388 35.6666 12.3625 34.348L7.04219 36.1215C6.6017 36.2684 6.129 36.2897 5.67707 36.183C5.22515 36.0764 4.81185 35.846 4.48352 35.5176C4.15518 35.1893 3.92477 34.776 3.81812 34.3241C3.71147 33.8721 3.73279 33.3994 3.87969 32.959L5.65312 27.6386C4.49398 25.4593 3.84656 23.0446 3.76 20.5777C3.67343 18.1108 4.15001 15.6566 5.15355 13.4014C6.15708 11.1461 7.66121 9.14919 9.55175 7.56207C11.4423 5.97496 13.6696 4.83939 16.0645 4.24158C18.4594 3.64376 20.9591 3.59941 23.3737 4.11188C25.7883 4.62435 28.0545 5.68018 30.0001 7.19923C31.9458 8.71827 33.5198 10.6606 34.6027 12.8788C35.6856 15.097 36.249 17.5327 36.25 20.0011ZM33.75 20.0011C33.7494 17.892 33.2636 15.8112 32.3301 13.9198C31.3967 12.0284 30.0406 10.3771 28.3669 9.0937C26.6931 7.81026 24.7466 6.92907 22.6778 6.51831C20.609 6.10756 18.4734 6.17824 16.4363 6.72489C14.3992 7.27155 12.5151 8.27952 10.9299 9.67082C9.34471 11.0621 8.10081 12.7995 7.29447 14.7484C6.48813 16.6974 6.14097 18.8057 6.27983 20.9103C6.4187 23.015 7.03988 25.0594 8.09531 26.8855C8.18389 27.0388 8.2389 27.2092 8.25667 27.3854C8.27445 27.5615 8.2546 27.7395 8.19844 27.9074L6.25 33.7511L12.0937 31.8027C12.221 31.7593 12.3546 31.7371 12.4891 31.7371C12.7086 31.7375 12.9242 31.7957 13.1141 31.9058C15.2044 33.1152 17.5763 33.7528 19.9913 33.7543C22.4063 33.7558 24.7791 33.1213 26.8709 31.9145C28.9627 30.7078 30.6999 28.9713 31.9075 26.88C33.1151 24.7886 33.7506 22.4161 33.75 20.0011Z" fill="#1D85DD"/>
</svg>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div id="user-selection" class="mt-3 hidden">
                                    <div class="mb-2">
                                        <input type="text" id="user-search" placeholder="Search users..."
                                            class="form-control">
                                    </div>

                                    <div class="checkbox-group">
                                        <?php if (empty($allUsers)): ?>
                                            <p class="text-center py-4 text-gray-500">No users available</p>
                                        <?php else: ?>
                                            <?php foreach ($allUsers as $user): ?>
                                                <label class="checkbox-item">
                                                    <input type="checkbox" name="target_users[]" value="<?= $user['id'] ?>"
                                                        class="user-checkbox mr-2">
                                                    <span>
                                                        <?= htmlspecialchars($user['full_name']) ?>
                                                    </span>
                                                </label>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mt-2 text-sm text-gray-500" id="selected-count">0 users selected</div>
                                </div>
                            </div>

                            <!-- Image Upload -->
                            <div class="form-group">
                                <label class="form-label mb-2">Image (Optional)</label>
                                <div class="file-upload" onclick="document.getElementById('announcement_image').click();">
                                    <input type="file" id="announcement_image" name="announcement_image" 
                                           accept="image/*" class="hidden" onchange="updateImageName(this)">
                                           <div class="flex flex-col items-center justify-center text-center">
    <svg width="55" height="55" viewBox="0 0 24 24" fill="none"
        xmlns="http://www.w3.org/2000/svg" class="mb-2">
        <path d="M22.5 12.7506V18.7506C22.5 19.1484 22.342 19.5299 22.0607 19.8112C21.7794 20.0926 21.3978 20.2506 21 20.2506H3C2.60218 20.2506 2.22064 20.0926 1.93934 19.8112C1.65804 19.5299 1.5 19.1484 1.5 18.7506V12.7506C1.5 12.3528 1.65804 11.9712 1.93934 11.6899C2.22064 11.4086 2.60218 11.2506 3 11.2506H7.5C7.69891 11.2506 7.88968 11.3296 8.03033 11.4703C8.17098 11.6109 8.25 11.8017 8.25 12.0006C8.25 12.1995 8.17098 12.3903 8.03033 12.5309C7.88968 12.6716 7.69891 12.7506 7.5 12.7506H3V18.7506H21V12.7506H16.5C16.3011 12.7506 16.1103 12.6716 15.9697 12.5309C15.829 12.3903 15.75 12.1995 15.75 12.0006C15.75 11.8017 15.829 11.6109 15.9697 11.4703C16.1103 11.3296 16.3011 11.2506 16.5 11.2506H21C21.3978 11.2506 21.7794 11.4086 22.0607 11.6899C22.342 11.9712 22.5 12.3528 22.5 12.7506ZM8.03063 7.28122L11.25 4.0609V12.0006C11.25 12.1995 11.329 12.3903 11.4697 12.5309C11.6103 12.6716 11.8011 12.7506 12 12.7506C12.1989 12.7506 12.3897 12.6716 12.5303 12.5309C12.671 12.3903 12.75 12.1995 12.75 12.0006V4.0609L15.9694 7.28122C16.1101 7.42195 16.301 7.50101 16.5 7.50101C16.699 7.50101 16.8899 7.42195 17.0306 7.28122C17.1714 7.14048 17.2504 6.94961 17.2504 6.75059C17.2504 6.55157 17.1714 6.3607 17.0306 6.21996L12.5306 1.71997C12.461 1.65023 12.3783 1.59491 12.2872 1.55717C12.1962 1.51943 12.0986 1.5 12 1.5C11.9014 1.5 11.8038 1.51943 11.7128 1.55717C11.6217 1.59491 11.539 1.65023 11.4694 1.71997L6.96937 6.21996C6.82864 6.3607 6.74958 6.55157 6.74958 6.75059C6.74958 6.94961 6.82864 7.14048 6.96938 7.28121C7.11011 7.42195 7.30098 7.50101 7.5 7.50101C7.69902 7.50101 7.88989 7.42195 8.03063 7.28122ZM18.75 15.7506C18.75 15.5281 18.684 15.3106 18.5604 15.1256C18.4368 14.9406 18.2611 14.7964 18.0555 14.7112C17.85 14.6261 17.6238 14.6038 17.4055 14.6472C17.1873 14.6906 16.9868 14.7978 16.8295 14.9551C16.6722 15.1124 16.565 15.3129 16.5216 15.5311C16.4782 15.7493 16.5005 15.9755 16.5856 16.1811C16.6708 16.3867 16.815 16.5624 17 16.686C17.185 16.8096 17.4025 16.8756 17.625 16.8756C17.9234 16.8756 18.2095 16.7571 18.4205 16.5461C18.6315 16.3351 18.75 16.049 18.75 15.7506Z"
            fill="#0EA4EE" />
    </svg>
    <p class="font-medium" id="image-name">Click to upload image</p>
    <p class="text-sm text-gray-500">JPG, PNG, GIF • Max 5MB</p>
</div>
                                </div>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="flex justify-between gap-3 mt-auto">
                                <div class="flex gap-2">
                                    <button type="submit" name="post_announcement" class="btn btn-success">
                                        Post Announcement
                                    </button>

                                    <script>
                                    document.addEventListener('DOMContentLoaded', function () {
                                        const titleInput = document.getElementById('announcement-title');
                                        const messageInput = document.getElementById('announcement-message');
                                        const priorityInput = document.querySelector('select[name="priority"]');
                                        const expiryInput = document.getElementById('announcement-expiry');
                                        const postBtn = document.querySelector('button[name="post_announcement"]');
                                        const audienceInputs = document.querySelectorAll('input[name="audience_type"]');
                                        const specificUserCheckboxes = document.querySelectorAll('.user-checkbox');

                                        function isFutureDate(dateStr) {
                                            if (!dateStr) return false;
                                            const inputDate = new Date(dateStr);
                                            const today = new Date();
                                            today.setHours(0,0,0,0);
                                            return inputDate > today;
                                        }

                                        function validateAnnouncementFields() {
                                            const title = titleInput.value.trim();
                                            const message = messageInput.value.trim();
                                            const priority = priorityInput.value;
                                            const expiry = expiryInput.value;
                                            const audienceType = document.querySelector('input[name="audience_type"]:checked')?.value;
                                            const selectedSpecificUsers = document.querySelectorAll('.user-checkbox:checked').length;
                                            const allFilled = title && message && priority && expiry;
                                            const expiryValid = isFutureDate(expiry);
                                            const specificAudienceValid = audienceType !== 'specific' || selectedSpecificUsers > 0;
                                            const enable = allFilled && expiryValid && specificAudienceValid;
                                            postBtn.disabled = !enable;
                                            postBtn.classList.toggle('opacity-50', !enable);
                                            postBtn.classList.toggle('cursor-not-allowed', !enable);
                                        }

                                        [titleInput, messageInput, priorityInput, expiryInput].forEach(el => {
                                            el.addEventListener('input', validateAnnouncementFields);
                                            el.addEventListener('change', validateAnnouncementFields);
                                        });

                                        audienceInputs.forEach(el => {
                                            el.addEventListener('change', validateAnnouncementFields);
                                        });

                                        specificUserCheckboxes.forEach(el => {
                                            el.addEventListener('change', validateAnnouncementFields);
                                        });

                                        window.validateAnnouncementFields = validateAnnouncementFields;

                                        // Initial state
                                        validateAnnouncementFields();
                                    });
                                    </script>
                                    <!-- Lab Result Button: Only show if Specific Resident is selected -->
                                    <button type="submit" name="post_lab_result" id="labResultBtn" class="btn btn-lab" style="display:none;">
                                        Lab Result
                                    </button>
                                </div>
                                <button type="button" onclick="clearForm()" class="btn btn-secondary">
                                    Clear
                                </button>
                            </div>

                        </div>
                    </div>
                </div>

            </form>

            <!-- Right Column - Announcements List -->
            <div class="lg:col-span-1 h-full">
                <div class="card flex flex-col" style="height:auto;min-height:0;">

                    <!-- Header -->
                    <div class="card-header">
                        <h2 class="text-lg font-semibold mb-6 text-secondary">
                            Announcements Posted
                        </h2>
                    </div>

                    <!-- Tabs -->
                    <div class="tab-nav">
                        <button class="tab-btn active" data-tab="active">
                            Active Announcements (<?= count($activeAnnouncements) ?>)
                        </button>
                        <button class="tab-btn" data-tab="archived">
                            Archived Announcements (<?= count($archivedAnnouncements) ?>)
                        </button>
                    </div>

                    <!-- Active Announcements -->
                    <div id="active-tab-content" style="display: flex; flex-direction: column; gap: 0.5rem; flex: 1; overflow: auto;">
                        <?php if (empty($activeAnnouncements)): ?>
                            <div class="empty-state col-span-full">
                                <div class="flex flex-col items-center gap-4">
                                <svg width="70" height="55" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M21.4256 8.1225L4.92 3.06C4.6966 2.99484 4.4611 2.98255 4.23213 3.02411C4.00316 3.06567 3.787 3.15993 3.70075 3.29944C3.41449 3.43895 3.26325 3.61988 3.15899 3.82792C3.05472 4.03597 3.00029 4.26542 3 4.49813V17.9981C3 18.396 3.15804 18.7775 3.43934 19.0588C3.72064 19.3401 4.10218 19.4981 4.5 19.4981C4.64344 19.4982 4.78614 19.4777 4.92375 19.4372L12.75 17.0353V17.9981C12.75 18.396 12.908 18.7775 13.1893 19.0588C13.4706 19.3401 13.8522 19.4981 14.25 19.4981H17.25C17.6478 19.4981 18.0294 19.3401 18.3107 19.0588C18.592 18.7775 18.75 18.396 18.75 17.9981V15.195L21.4256 14.3747C21.7353 14.2816 22.0069 14.0916 22.2003 13.8325C22.3937 13.5734 22.4988 13.259 22.5 12.9356V9.56063C22.4986 9.23745 22.3934 8.92326 22.2 8.66435C22.0066 8.40544 21.7351 8.2155 21.4256 8.1225ZM12.75 15.4669L4.5 17.9981V4.49813L12.75 7.02938V15.4669ZM17.25 17.9981H14.25V16.575L17.25 15.6544V17.9981ZM21 12.9356H20.9897L14.25 15.0056V7.49063L20.9897 9.55313H21V12.9281V12.9356Z" fill="#AAAAAA"/>
</svg>
                                <p>No active announcements</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php $activeCount = 0; ?>
                            <?php
                            $activeCount = 0;
                            foreach ($activeAnnouncements as $announcement) {
                                if ($activeCount < 3) {
                                    $activeCount++;
                            ?>
                                    <div class="announcement-item limited-active" style="display: flex; flex-direction: column; gap: 0.5rem; box-shadow: 0 2px 8px rgba(52,152,219,0.07); border-radius: 12px; border: 1px solid #e2e8f0; padding: 1.5rem; background: #fff;">
                                        <div class="flex flex-row justify-between items-start mb-2">
                                            <div class="flex flex-col gap-1" style="min-width:0;">
                                                <h3 class="announcement-title" style="font-size: 1.15rem; color: #2563eb; font-weight: 500; margin-bottom: 0.2rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 260px;" title="<?= htmlspecialchars($announcement['title']) ?>">
                                                    <?= htmlspecialchars($announcement['title']) ?>

                                                    
                                                </h3>
                                                <div class="text-xs text-gray-400 mb-1">Posted by : <span class="badge badge-normal" style="background: #e0eaff; color: #2563eb; font-size: 0.85rem; padding: 0.2rem 0.7rem; border-radius: 999px;">Encoder</span></div>
                                                <div class="text-base text-gray-700">Leandro Labos</div>
                                            </div>
                                            <div class="flex flex-col items-end gap-2">
                                                <span class="badge badge-<?= $announcement['priority'] ?>" style="background: <?= $announcement['priority'] === 'high' ? '#fee2e2' : '#e0eaff' ?>; color: <?= $announcement['priority'] === 'high' ? '#dc2626' : '#2563eb' ?>; font-size: 0.95rem; font-weight: 500; border-radius: 999px; padding: 0.2rem 1.2rem; min-width: 70px; text-align: center;">
                                                    <?= ucfirst($announcement['priority']) ?>
                                                </span>
                                                <?php
                                                if ($announcement['audience_type'] === 'landing_page') {
                                                    echo '<span class="badge" style="background: #e0f2fe; color: #0369a1; font-size: 0.85rem; font-weight: 500; border-radius: 999px; padding: 0.2rem 0.9rem; margin-top: 0.2rem;"><i class="fas fa-eye mr-1"></i>Seen by: ' . (int)$announcement['seen_count'] . '</span>';
                                                } elseif ($announcement['audience_type'] === 'public') {
                                                    echo '<div style="display:flex;flex-direction:row;gap:0.5rem;margin-top:0.2rem;">';
                                                    echo '<span class="badge stat-accepted" style="display:inline-flex;align-items:center;gap:0.3rem;padding:0.2rem 0.7rem;border-radius:4px;"><i class="fas fa-check-circle stat-icon" style="background:none;color:#059669;"></i> ' . $announcement['accepted_count'] . '</span>';
                                                    echo '<span class="badge stat-pending" style="display:inline-flex;align-items:center;gap:0.3rem;padding:0.2rem 0.7rem;border-radius:4px;"><i class="fas fa-hourglass-half stat-icon" style="background:none;color:#d97706;"></i> ' . $announcement['pending_count'] . '</span>';
                                                    echo '<span class="badge stat-dismissed" style="display:inline-flex;align-items:center;gap:0.3rem;padding:0.2rem 0.7rem;border-radius:4px;"><i class="fas fa-times-circle stat-icon" style="background:none;color:#dc2626;"></i> ' . $announcement['dismissed_count'] . '</span>';
                                                    echo '</div>';
                                                } else {
                                                    $statusBadge = '';
                                                    if ($announcement['accepted_count'] > 0) {
                                                        $statusBadge = '<span class="badge" style="background: #d1fae5; color: #059669; font-size: 0.85rem; font-weight: 500; border-radius: 999px; padding: 0.2rem 0.9rem; margin-top: 0.2rem;">Accepted</span>';
                                                    } elseif ($announcement['dismissed_count'] > 0) {
                                                        $statusBadge = '<span class="badge" style="background: #fee2e2; color: #dc2626; font-size: 0.85rem; font-weight: 500; border-radius: 999px; padding: 0.2rem 0.9rem; margin-top: 0.2rem;">Dismissed</span>';
                                                    } else {
                                                        $statusBadge = '<span class="badge" style="background: #fef3c7; color: #d97706; font-size: 0.85rem; font-weight: 500; border-radius: 999px; padding: 0.2rem 0.9rem; margin-top: 0.2rem;">Pending</span>';
                                                    }
                                                    echo $statusBadge;
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        <div class="flex flex-row justify-end items-center mt-2 gap-2">
                                            <button
                                                onclick="openViewModal(<?= htmlspecialchars(json_encode($announcement, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>)"
                                                class="btn btn-primary btn-sm" title="View" style="background: #e0eaff; color: #2563eb; border: none;">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button
                                                onclick="openEditModal(<?= htmlspecialchars(json_encode($announcement, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>)"
                                                class="btn btn-warning btn-sm" title="Edit" style="background: #fef3c7; color: #d97706; border: none;">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" action="" class="inline">
                                                <input type="hidden" name="id" value="<?= $announcement['id'] ?>">
                                                <button type="submit" name="archive_announcement" class="btn btn-danger btn-sm"
                                                    title="Archive" style="background: #fee2e2; color: #dc2626; border: none;" onclick="return confirm('Archive this announcement?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php }
                            }
                            ?>
                            <?php if (count($activeAnnouncements) > 3): ?>
                                <button id="viewAllActiveBtn" class="btn btn-primary btn-block mt-2" style="background: #2563eb; color: #fff;">View All Active Announcements</button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Archived Announcements -->
                    <div id="archived-tab-content" style="display: flex; flex-direction: column; gap: 0.5rem; flex: 1; overflow: auto;">
                        <?php if (empty($archivedAnnouncements)): ?>
                            <div class="empty-state col-span-full">
                                <i class="fas fa-archive empty-icon"></i>
                                <p>No archived announcements</p>
                            </div>
                        <?php else: ?>
                            <?php $archivedCount = 0; ?>
                            <?php foreach ($archivedAnnouncements as $announcement): ?>
                                <?php if ($archivedCount < 3): ?>
                                    <?php $archivedCount++; ?>
                                    <div class="announcement-item limited-archived" style="display: flex; flex-direction: column; gap: 0.5rem; box-shadow: 0 2px 8px rgba(52,152,219,0.07); border-radius: 12px; border: 1px solid #e2e8f0; padding: 1.5rem; background: #fff;">
                                        <div class="flex flex-row justify-between items-start mb-2">
                                            <div class="flex flex-col gap-1" style="min-width:0;">
                                                <h3 class="announcement-title" style="font-size: 1.15rem; color: #2563eb; font-weight: 500; margin-bottom: 0.2rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 260px;" title="<?= htmlspecialchars($announcement['title']) ?>">
                                                    <?= htmlspecialchars($announcement['title']) ?>
                                                </h3>
                                                <div class="text-xs text-gray-400 mb-1">Posted by : <span class="badge badge-normal" style="background: #e0eaff; color: #2563eb; font-size: 0.85rem; padding: 0.2rem 0.7rem; border-radius: 999px;">Encoder</span></div>
                                                <div class="text-base text-gray-700">Leandro Labos</div>
                                                <div class="text-xs text-gray-400 mt-2">Archived on : <span style="color: #222; font-weight: 500;"><?= date('M d, Y', strtotime($announcement['post_date'])) ?></span></div>
                                            </div>
                                            <span class="badge badge-<?= $announcement['priority'] ?>" style="background: <?= $announcement['priority'] === 'high' ? '#fee2e2' : '#e0eaff' ?>; color: <?= $announcement['priority'] === 'high' ? '#dc2626' : '#2563eb' ?>; font-size: 0.95rem; font-weight: 500; border-radius: 999px; padding: 0.2rem 1.2rem; min-width: 70px; text-align: center;">
                                                <?= ucfirst($announcement['priority']) ?>
                                            </span>
                                        </div>
                                        <div class="flex flex-row justify-end items-center mt-2 gap-2">
                                            <button
                                                onclick="openViewModal(<?= htmlspecialchars(json_encode($announcement, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>)"
                                                class="btn btn-primary btn-sm" title="View" style="background: #e0eaff; color: #2563eb; border: none;">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <form method="POST" action="" class="inline">
                                                <input type="hidden" name="id" value="<?= $announcement['id'] ?>">
                                                <button type="submit" name="repost_announcement" class="btn btn-warning btn-sm"
                                                    title="Repost" style="background: #fef3c7; color: #d97706; border: none;" onclick="return confirm('Repost this announcement?')">
                                                    <i class="fas fa-redo"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="" class="inline">
                                                <input type="hidden" name="id" value="<?= $announcement['id'] ?>">
                                                <button type="submit" name="delete_announcement" class="btn btn-danger btn-sm"
                                                    title="Delete" style="background: #fee2e2; color: #dc2626; border: none;" onclick="return confirm('Permanently delete this announcement?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if (count($archivedAnnouncements) > 3): ?>
                                <button id="viewAllArchivedBtn" class="btn btn-warning btn-block mt-2" style="background: #d97706; color: #fff;">View All Archived Announcements</button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- View Modal -->
        <div id="viewModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Announcement Details</h3>
                    <button type="button" class="text-gray-500 hover:text-gray-700 close-modal">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M19.281 18.2198C19.3507 18.2895 19.406 18.3722 19.4437 18.4632C19.4814 18.5543 19.5008 18.6519 19.5008 18.7504C19.5008 18.849 19.4814 18.9465 19.4437 19.0376C19.406 19.1286 19.3507 19.2114 19.281 19.281C19.2114 19.3507 19.1286 19.406 19.0376 19.4437C18.9465 19.4814 18.849 19.5008 18.7504 19.5008C18.6519 19.5008 18.5543 19.4814 18.4632 19.4437C18.3722 19.406 18.2895 19.3507 18.2198 19.281L12.0004 13.0607L5.78104 19.281C5.64031 19.4218 5.44944 19.5008 5.25042 19.5008C5.05139 19.5008 4.86052 19.4218 4.71979 19.281C4.57906 19.1403 4.5 18.9494 4.5 18.7504C4.5 18.5514 4.57906 18.3605 4.71979 18.2198L10.9401 12.0004L4.71979 5.78104C4.57906 5.64031 4.5 5.44944 4.5 5.25042C4.5 5.05139 4.57906 4.86052 4.71979 4.71979C4.86052 4.57906 5.05139 4.5 5.25042 4.5C5.44944 4.5 5.64031 4.57906 5.78104 4.71979L12.0004 10.9401L18.2198 4.71979C18.3605 4.57906 18.5514 4.5 18.7504 4.5C18.9494 4.5 19.1403 4.57906 19.281 4.71979C19.4218 4.86052 19.5008 5.05139 19.5008 5.25042C19.5008 5.44944 19.4218 5.64031 19.281 5.78104L13.0607 12.0004L19.281 18.2198Z" fill="black"/>
</svg>

                    </button>
                </div>
                <div class="modal-body">
                    <div id="modalContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close-modal">
                        Close
                    </button>
                </div>
            </div>
        </div>

        <!-- All Active Announcements Modal -->
        <div id="allActiveModal" class="modal">
            <div class="modal-content" style="max-width: 800px; width: 95vw;">
                <div class="modal-header">
                    <h3 class="modal-title">All Active Announcements</h3>
                    <button type="button" class="text-gray-500 hover:text-gray-700 close-modal">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M19.281 18.2198C19.3507 18.2895 19.406 18.3722 19.4437 18.4632C19.4814 18.5543 19.5008 18.6519 19.5008 18.7504C19.5008 18.849 19.4814 18.9465 19.4437 19.0376C19.406 19.1286 19.3507 19.2114 19.281 19.281C19.2114 19.3507 19.1286 19.406 19.0376 19.4437C18.9465 19.4814 18.849 19.5008 18.7504 19.5008C18.6519 19.5008 18.5543 19.4814 18.4632 19.4437C18.3722 19.406 18.2895 19.3507 18.2198 19.281L12.0004 13.0607L5.78104 19.281C5.64031 19.4218 5.44944 19.5008 5.25042 19.5008C5.05139 19.5008 4.86052 19.4218 4.71979 19.281C4.57906 19.1403 4.5 18.9494 4.5 18.7504C4.5 18.5514 4.57906 18.3605 4.71979 18.2198L10.9401 12.0004L4.71979 5.78104C4.57906 5.64031 4.5 5.44944 4.5 5.25042C4.5 5.05139 4.57906 4.86052 4.71979 4.71979C4.86052 4.57906 5.05139 4.5 5.25042 4.5C5.44944 4.5 5.64031 4.57906 5.78104 4.71979L12.0004 10.9401L18.2198 4.71979C18.3605 4.57906 18.5514 4.5 18.7504 4.5C18.9494 4.5 19.1403 4.57906 19.281 4.71979C19.4218 4.86052 19.5008 5.05139 19.5008 5.25042C19.5008 5.44944 19.4218 5.64031 19.281 5.78104L13.0607 12.0004L19.281 18.2198Z" fill="black"/>
</svg>

                    </button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <div id="allActiveAnnouncementsContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close-modal">Close</button>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">Edit Announcement</h3>
                    <button type="button" class="text-gray-500 hover:text-gray-700 close-modal">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M19.281 18.2198C19.3507 18.2895 19.406 18.3722 19.4437 18.4632C19.4814 18.5543 19.5008 18.6519 19.5008 18.7504C19.5008 18.849 19.4814 18.9465 19.4437 19.0376C19.406 19.1286 19.3507 19.2114 19.281 19.281C19.2114 19.3507 19.1286 19.406 19.0376 19.4437C18.9465 19.4814 18.849 19.5008 18.7504 19.5008C18.6519 19.5008 18.5543 19.4814 18.4632 19.4437C18.3722 19.406 18.2895 19.3507 18.2198 19.281L12.0004 13.0607L5.78104 19.281C5.64031 19.4218 5.44944 19.5008 5.25042 19.5008C5.05139 19.5008 4.86052 19.4218 4.71979 19.281C4.57906 19.1403 4.5 18.9494 4.5 18.7504C4.5 18.5514 4.57906 18.3605 4.71979 18.2198L10.9401 12.0004L4.71979 5.78104C4.57906 5.64031 4.5 5.44944 4.5 5.25042C4.5 5.05139 4.57906 4.86052 4.71979 4.71979C4.86052 4.57906 5.05139 4.5 5.25042 4.5C5.44944 4.5 5.64031 4.57906 5.78104 4.71979L12.0004 10.9401L18.2198 4.71979C18.3605 4.57906 18.5514 4.5 18.7504 4.5C18.9494 4.5 19.1403 4.57906 19.281 4.71979C19.4218 4.86052 19.5008 5.05139 19.5008 5.25042C19.5008 5.44944 19.4218 5.64031 19.281 5.78104L13.0607 12.0004L19.281 18.2198Z" fill="black"/>
</svg>

                    </button>
                </div>
                <form method="POST" action="" id="edit-form" onsubmit="return validateEditForm()">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit-id">
                        <input type="hidden" name="edit_announcement" value="1">

                        <div class="space-y-4">
                            <div class="form-group">
                                <label class="form-label">Title *</label>
                                <input type="text" name="title" id="edit-title" required class="form-control"
                                    maxlength="200">
                                <span class="text-xs text-gray-500" id="edit-title-counter">0/200 characters</span>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Message *</label>
                                <textarea name="message" id="edit-message" required class="form-control" rows="5"
                                    maxlength="1000"></textarea>
                                <span class="text-xs text-gray-500" id="edit-message-counter">0/1000 characters</span>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Priority</label>
                                <div class="relative">
                                    <select name="priority" id="edit-priority" class="form-control appearance-none w-full text-lg flex items-center justify-center border border-blue-300 rounded-lg py-3 pl-12 pr-12 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                        <option value="normal">Normal</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-5">
                                        <svg class="h-6 w-6 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Expiry Date</label>
                                <input type="date" name="expiry_date" id="edit-expiry" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary close-modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div id="deleteModal" class="modal">
            <div class="modal-content" style="max-width: 450px;">
                <div class="modal-header bg-red-50">
                    <h3 class="modal-title text-red-700">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Confirm Deletion
                    </h3>
                    <button type="button" class="text-gray-500 hover:text-gray-700 close-modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="" id="delete-form">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="delete-id">
                        <input type="hidden" name="delete_announcement" value="1">

                        <div class="text-center py-4">
                            <div class="text-red-500 text-5xl mb-4">
                                <i class="fas fa-trash-alt"></i>
                            </div>
                            <p class="text-lg font-semibold mb-2">Are you sure?</p>
                            <p class="text-gray-600">This will permanently delete this announcement.</p>
                            <p class="text-gray-600">This action cannot be undone.</p>
                        </div>
                    </div>
                    <div class="modal-footer bg-gray-50">
                        <button type="button" class="btn btn-secondary close-modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete Permanently
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Archive Confirmation Modal -->
        <div id="archiveModal" class="modal">
            <div class="modal-content" style="max-width: 450px;">
                <div class="modal-header bg-yellow-50">
                    <h3 class="modal-title text-yellow-700">
                        <i class="fas fa-archive mr-2"></i>Archive Announcement
                    </h3>
                    <button type="button" class="text-gray-500 hover:text-gray-700 close-modal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="" id="archive-form">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="archive-id">
                        <input type="hidden" name="archive_announcement" value="1">

                        <div class="text-center py-4">
                            <div class="text-yellow-500 text-5xl mb-4">
                                <i class="fas fa-archive"></i>
                            </div>
                            <p class="text-lg font-semibold mb-2">Archive this announcement?</p>
                            <p class="text-gray-600">You can repost it later from the archived section.</p>
                        </div>
                    </div>
                    <div class="modal-footer bg-gray-50">
                        <button type="button" class="btn btn-secondary close-modal">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-archive"></i> Archive
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            const MODAL_TRANSITION_MS = 280;

            // Helper function to escape HTML
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            function closeModalElement(modal) {
                if (!modal || !modal.classList.contains('active')) {
                    return;
                }

                modal.classList.add('closing');
                modal.classList.remove('active');

                setTimeout(() => {
                    modal.classList.remove('closing');
                    if (!document.querySelector('.modal.active')) {
                        document.body.style.overflow = 'auto';
                    }
                }, MODAL_TRANSITION_MS);
            }

            // Close all modals function
            function closeAllModals() {
                const modals = document.querySelectorAll('.modal.active');
                modals.forEach(modal => {
                    closeModalElement(modal);
                });
            }

            // Show modal function
            function showModal(modalId) {
                const modal = document.getElementById(modalId);
                if (!modal) {
                    return;
                }

                document.querySelectorAll('.modal.active').forEach(openModal => {
                    if (openModal !== modal) {
                        closeModalElement(openModal);
                    }
                });

                modal.classList.remove('closing');
                requestAnimationFrame(() => {
                    modal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                });
            }

            // Update image name
            function updateImageName(input) {
                if (input.files && input.files[0]) {
                    document.getElementById('image-name').innerHTML =
                        `<i class="fas fa-check-circle text-green-500 mr-2"></i> ${input.files[0].name}`;
                }
            }

            // Update selected user count
            function updateSelectedUserCount() {
                const checkboxes = document.querySelectorAll('.user-checkbox:checked');
                const count = checkboxes.length;
                const countElement = document.getElementById('selected-count');
                if (countElement) {
                    countElement.textContent = `${count} user${count !== 1 ? 's' : ''} selected`;
                }
                if (typeof window.validateAnnouncementFields === 'function') {
                    window.validateAnnouncementFields();
                }
            }

            // Clear form
            function clearForm() {
                document.querySelector('form').reset();
                const userSelection = document.getElementById('user-selection');
                if (userSelection) userSelection.classList.add('hidden');
                document.getElementById('image-name').textContent = 'Click to upload image';
                document.getElementById('selected-count').textContent = '0 users selected';
                document.getElementById('audience-landing').checked = true;
                
                // Hide lab result button
                const labResultBtn = document.getElementById('labResultBtn');
                if (labResultBtn) labResultBtn.style.display = 'none';
                if (typeof window.validateAnnouncementFields === 'function') {
                    window.validateAnnouncementFields();
                }
            }

            // Character counter function
            function updateCharCounter(inputId, counterId, maxLength) {
                const input = document.getElementById(inputId);
                const counter = document.getElementById(counterId);

                if (input && counter) {
                    const currentLength = input.value.length;
                    counter.textContent = `${currentLength}/${maxLength} characters`;

                    if (currentLength > maxLength * 0.9) {
                        counter.classList.add('text-red-500');
                        counter.classList.remove('text-gray-500');
                    } else {
                        counter.classList.remove('text-red-500');
                        counter.classList.add('text-gray-500');
                    }
                }
            }

            // View Modal Functions
            function openViewModal(announcement) {
                if (typeof announcement === 'string') {
                    try {
                        announcement = JSON.parse(announcement);
                    } catch (e) {
                        console.error('Error parsing announcement data:', e);
                        return;
                    }
                }

                const modalContent = document.getElementById('modalContent');

                const postDate = new Date(announcement.post_date).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });

                let content = `
                <div class="space-y-4">
                    <div class="flex justify-between items-start">
                        <div>
                            <h4 class="font-semibold text-lg">${escapeHtml(announcement.title || 'No Title')}</h4>
                            <div class="text-xs text-gray-500 mt-1">
                                Posted by: <b>${escapeHtml(announcement.staff_full_name || '')}</b> (${escapeHtml(announcement.staff_position || '')})
                            </div>
                            <div class="mt-1">
                                <span class="badge ${announcement.announcement_type === 'lab_result' ? 'badge-lab' : 'badge-normal'}" style="display:inline-block;padding:0.2rem 0.9rem;border-radius:4px;font-size:0.95rem;font-weight:500;${announcement.announcement_type === 'lab_result' ? 'background:#e0eaff;color:#2563eb;' : (announcement.audience_type === 'public' ? 'background:#e0eaff;color:#2563eb;' : 'background:#f3f4f6;color:#374151;')}">
                                    ${announcement.announcement_type === 'lab_result' ? 'Lab Result' : (announcement.audience_type === 'public' ? 'For All Residents' : 'Basic Announcement')}
                                </span>
                            </div>
                        </div>
                        <span class="badge badge-${announcement.priority || 'normal'}" style="display:inline-block;padding:0.2rem 0.9rem;border-radius:4px;font-size:0.95rem;font-weight:500;${announcement.priority === 'high' ? 'background:#fee2e2;color:#dc2626;' : announcement.priority === 'medium' ? 'background:#fef3c7;color:#d97706;' : 'background:#e0eaff;color:#2563eb;'}">
                            ${announcement.priority ? announcement.priority.charAt(0).toUpperCase() + announcement.priority.slice(1) : 'Normal'} Priority
                        </span>
                    </div>
                    
                    ${announcement.audience_type === 'specific' && announcement.target_users && announcement.target_users.length > 0 ? `
                        <div>
                            <p class="text-gray-500 mb-1">Specific Users:</p>
                            <ul class="pl-4 list-disc text-sm">
                                ${announcement.target_users.map(u => `<li>${escapeHtml(u.full_name)}</li>`).join('')}
                            </ul>
                        </div>
                    ` : ''}
                    
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Posted on:</p>
                            <p class="font-medium">${postDate}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Audience:</p>
                            <p class="font-medium">
                                ${announcement.audience_type === 'public' ? 'All Residents' :
                        announcement.audience_type === 'landing_page' ? 'Landing Page' :
                            'Specific Users'}
                            </p>
                        </div>
                    </div>
            `;

                if (announcement.expiry_date) {
                    const expiryDate = new Date(announcement.expiry_date).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                    content += `
                    <div>
                        <p class="text-gray-500">Expires:</p>
                        <p class="font-medium">${expiryDate}</p>
                    </div>
                `;
                }

                content += `
                <div>
                    <p class="text-gray-500 mb-2">Message:</p>
                    <div class="bg-gray-50 p-4 rounded border">
                        <p class="text-gray-700 whitespace-pre-line">${escapeHtml(announcement.message || 'No message')}</p>
                    </div>
                </div>
                
                ${announcement.audience_type === 'landing_page' ? `
                <div class="grid grid-cols-1 gap-4 text-center mt-2">
                    <div class="p-3 bg-sky-50 border border-sky-200 rounded" style="border-radius:4px;">
                        <p class="text-sky-700 font-bold flex items-center justify-center gap-1"><i class='fas fa-eye mr-1'></i> ${announcement.seen_count || 0}</p>
                        <p class="text-sm text-sky-600">Seen by</p>
                    </div>
                </div>
                ` : `
                <div class="grid grid-cols-3 gap-4 text-center mt-2">
                    <div class="p-3 bg-green-50 border border-green-200 rounded" style="border-radius:4px;">
                        <p class="text-green-700 font-bold flex items-center justify-center gap-1"><i class='fas fa-check-circle mr-1'></i> ${announcement.accepted_count || 0}</p>
                        <p class="text-sm text-green-600">Accepted</p>
                    </div>
                    <div class="p-3 bg-yellow-50 border border-yellow-200 rounded" style="border-radius:4px;">
                        <p class="text-yellow-700 font-bold flex items-center justify-center gap-1"><i class='fas fa-hourglass-half mr-1'></i> ${announcement.pending_count || 0}</p>
                        <p class="text-sm text-yellow-600">Pending</p>
                    </div>
                    <div class="p-3 bg-red-50 border border-red-200 rounded" style="border-radius:4px;">
                        <p class="text-red-700 font-bold flex items-center justify-center gap-1"><i class='fas fa-times-circle mr-1'></i> ${announcement.dismissed_count || 0}</p>
                        <p class="text-sm text-red-600">Dismissed</p>
                    </div>
                </div>
                `}
            `;

                modalContent.innerHTML = content;
                showModal('viewModal');
            }

            // Edit Modal Functions
            function openEditModal(announcement) {
                if (typeof announcement === 'string') {
                    try {
                        announcement = JSON.parse(announcement);
                    } catch (e) {
                        console.error('Error parsing announcement data:', e);
                        return;
                    }
                }

                document.getElementById('edit-id').value = announcement.id || '';
                document.getElementById('edit-title').value = announcement.title || '';
                document.getElementById('edit-message').value = announcement.message || '';
                document.getElementById('edit-priority').value = announcement.priority || 'normal';
                document.getElementById('edit-expiry').value = announcement.expiry_date || '';

                updateCharCounter('edit-title', 'edit-title-counter', 200);
                updateCharCounter('edit-message', 'edit-message-counter', 1000);

                showModal('editModal');
            }

            function openArchiveModalById(announcementId) {
                const archiveIdInput = document.getElementById('archive-id');
                if (!archiveIdInput) {
                    return;
                }

                archiveIdInput.value = announcementId || '';
                showModal('archiveModal');
            }

            function validateEditForm() {
                const title = document.getElementById('edit-title').value.trim();
                const message = document.getElementById('edit-message').value.trim();

                if (title.length === 0) {
                    alert('Please enter a title');
                    document.getElementById('edit-title').classList.add('border-red-500');
                    document.getElementById('edit-title').focus();
                    return false;
                }

                if (message.length === 0) {
                    alert('Please enter a message');
                    document.getElementById('edit-message').classList.add('border-red-500');
                    document.getElementById('edit-message').focus();
                    return false;
                }

                return true;
            }

            // Initialize all event listeners
            document.addEventListener('DOMContentLoaded', function () {
                // Tab functionality
                document.querySelectorAll('.tab-btn').forEach(button => {
                    button.addEventListener('click', function (e) {
                        e.stopPropagation();
                        const tabId = this.dataset.tab;
                        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
                        this.classList.add('active');
                        // Show only the selected tab's content
                        if (tabId === 'active') {
                            document.getElementById('active-tab-content').style.display = 'flex';
                            document.getElementById('archived-tab-content').style.display = 'none';
                        } else {
                            document.getElementById('active-tab-content').style.display = 'none';
                            document.getElementById('archived-tab-content').style.display = 'flex';
                        }
                    });
                });
                // Set initial state: show active, hide archived
                document.getElementById('active-tab-content').style.display = 'flex';
                document.getElementById('archived-tab-content').style.display = 'none';

                // View All Active Announcements button
                const viewAllActiveBtn = document.getElementById('viewAllActiveBtn');
                if (viewAllActiveBtn) {
                    viewAllActiveBtn.addEventListener('click', function () {
                        // Get all active announcements from PHP variable
                        const announcements = window.allActiveAnnouncementsData || [];
                        const container = document.getElementById('allActiveAnnouncementsContent');
                        if (!container) return;
                        if (!announcements.length) {
                            container.innerHTML = '<div class="text-center text-gray-500">No active announcements found.</div>';
                        } else {
                            container.innerHTML = announcements.map(announcement => {
                                const postDate = new Date(announcement.post_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                                let statusHtml = '';

                                if (announcement.audience_type === 'landing_page') {
                                    statusHtml = `
                                        <span class="badge" style="background: #e0f2fe; color: #0369a1; font-size: 0.85rem; font-weight: 500; border-radius: 999px; padding: 0.2rem 0.9rem;">
                                            <i class="fas fa-eye mr-1"></i> Seen by: ${announcement.seen_count || 0}
                                        </span>
                                    `;
                                } else if (announcement.audience_type === 'public') {
                                    statusHtml = `
                                        <div style="display:flex;flex-direction:row;gap:0.5rem;flex-wrap:wrap;">
                                            <span class="badge stat-accepted" style="display:inline-flex;align-items:center;gap:0.3rem;padding:0.2rem 0.7rem;border-radius:4px;">
                                                <i class="fas fa-check-circle stat-icon" style="background:none;color:#059669;"></i> ${announcement.accepted_count || 0}
                                            </span>
                                            <span class="badge stat-pending" style="display:inline-flex;align-items:center;gap:0.3rem;padding:0.2rem 0.7rem;border-radius:4px;">
                                                <i class="fas fa-hourglass-half stat-icon" style="background:none;color:#d97706;"></i> ${announcement.pending_count || 0}
                                            </span>
                                            <span class="badge stat-dismissed" style="display:inline-flex;align-items:center;gap:0.3rem;padding:0.2rem 0.7rem;border-radius:4px;">
                                                <i class="fas fa-times-circle stat-icon" style="background:none;color:#dc2626;"></i> ${announcement.dismissed_count || 0}
                                            </span>
                                        </div>
                                    `;
                                } else if ((announcement.accepted_count || 0) > 0) {
                                    statusHtml = '<span class="badge" style="background: #d1fae5; color: #059669; font-size: 0.85rem; font-weight: 500; border-radius: 999px; padding: 0.2rem 0.9rem;">Accepted</span>';
                                } else if ((announcement.dismissed_count || 0) > 0) {
                                    statusHtml = '<span class="badge" style="background: #fee2e2; color: #dc2626; font-size: 0.85rem; font-weight: 500; border-radius: 999px; padding: 0.2rem 0.9rem;">Dismissed</span>';
                                } else {
                                    statusHtml = '<span class="badge" style="background: #fef3c7; color: #d97706; font-size: 0.85rem; font-weight: 500; border-radius: 999px; padding: 0.2rem 0.9rem;">Pending</span>';
                                }

                                return `
                                    <div class="announcement-item mb-4 p-4 rounded shadow border bg-white">
                                        <div class="flex justify-between items-start mb-2 gap-3">
                                            <div style="min-width:0;">
                                                <h4 class="font-semibold text-lg mb-1">${escapeHtml(announcement.title || 'No Title')}</h4>
                                                <div class="text-xs text-gray-500">Posted by: <b>${escapeHtml(announcement.staff_full_name || '')}</b> (${escapeHtml(announcement.staff_position || '')})</div>
                                            </div>
                                            <span class="badge badge-${announcement.priority || 'normal'}" style="display:inline-block;padding:0.2rem 0.9rem;border-radius:4px;font-size:0.95rem;font-weight:500;${announcement.priority === 'high' ? 'background:#fee2e2;color:#dc2626;' : announcement.priority === 'medium' ? 'background:#fef3c7;color:#d97706;' : 'background:#e0eaff;color:#2563eb;'}">
                                                ${announcement.priority ? announcement.priority.charAt(0).toUpperCase() + announcement.priority.slice(1) : 'Normal'} Priority
                                            </span>
                                        </div>
                                        <div class="mb-2">
                                            <span class="badge ${announcement.announcement_type === 'lab_result' ? 'badge-lab' : 'badge-normal'}" style="display:inline-block;padding:0.2rem 0.9rem;border-radius:4px;font-size:0.95rem;font-weight:500;${announcement.announcement_type === 'lab_result' ? 'background:#e0eaff;color:#2563eb;' : (announcement.audience_type === 'public' ? 'background:#e0eaff;color:#2563eb;' : 'background:#f3f4f6;color:#374151;')}">
                                                ${announcement.announcement_type === 'lab_result' ? 'Lab Result' : (announcement.audience_type === 'public' ? 'For All Residents' : 'Basic Announcement')}
                                            </span>
                                            <span class="ml-2 text-xs text-gray-400">${postDate}</span>
                                        </div>
                                        <div class="mb-3">
                                            ${statusHtml}
                                        </div>
                                        <div class="mb-2">
                                            <p class="text-gray-500 mb-1">Message:</p>
                                            <div class="bg-gray-50 p-3 rounded border text-gray-700 whitespace-pre-line">${escapeHtml(announcement.message || 'No message')}</div>
                                        </div>
                                        <div class="flex justify-end items-center mt-3 gap-2">
                                            <button type="button"
                                                onclick='openViewModal(${JSON.stringify('__ANNOUNCEMENT__')})'
                                                class="btn btn-primary btn-sm" title="View" style="background: #e0eaff; color: #2563eb; border: none;">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button"
                                                onclick='openEditModal(${JSON.stringify('__ANNOUNCEMENT__')})'
                                                class="btn btn-success btn-sm" title="Edit" style="background: #dcfce7; color: #16a34a; border: none;">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <button type="button"
                                                onclick="openArchiveModalById(${announcement.id})"
                                                class="btn btn-danger btn-sm" title="Archive" style="background: #fee2e2; color: #dc2626; border: none;">
                                                <i class="fas fa-archive"></i>
                                            </button>
                                        </div>
                                    </div>
                                `.replaceAll('"__ANNOUNCEMENT__"', JSON.stringify(announcement).replace(/"/g, '&quot;'));
                            }).join('');
                        }
                        showModal('allActiveModal');
                    });
                }

                // Audience selection
                document.querySelectorAll('input[name="audience_type"]').forEach(radio => {
                    radio.addEventListener('change', function () {
                        const userSelection = document.getElementById('user-selection');
                        const labResultBtn = document.getElementById('labResultBtn');
                        const radioGroup = document.querySelector('.radio-group');
                        if (this.value === 'specific') {
                            userSelection.classList.remove('hidden');
                            labResultBtn.style.display = 'block';
                            if (radioGroup) {
                                radioGroup.style.height = 'auto';
                                radioGroup.style.maxHeight = 'none';
                            }
                        } else {
                            userSelection.classList.add('hidden');
                            labResultBtn.style.display = 'none';
                            if (radioGroup) {
                                radioGroup.style.height = '';
                                radioGroup.style.maxHeight = '';
                            }
                        }
                        updateSelectedUserCount();
                    });
                });

                // Add click listeners to checkboxes
                document.querySelectorAll('.checkbox-item').forEach(item => {
                    item.addEventListener('click', function (e) {
                        if (!e.target.matches('input[type="checkbox"]')) {
                            const checkbox = this.querySelector('input[type="checkbox"]');
                            if (checkbox) {
                                checkbox.checked = !checkbox.checked;
                                updateSelectedUserCount();
                            }
                        }
                    });
                });
                
                document.querySelectorAll('.user-checkbox').forEach(cb => {
                    cb.addEventListener('change', function () {
                        updateSelectedUserCount();
                    });
                });

                // User search functionality
                const userSearch = document.getElementById('user-search');
                if (userSearch) {
                    userSearch.addEventListener('input', function () {
                        const searchTerm = this.value.toLowerCase();
                        const userItems = document.querySelectorAll('.checkbox-item');
                        userItems.forEach(item => {
                            const text = item.textContent.toLowerCase();
                            item.style.display = text.includes(searchTerm) ? 'flex' : 'none';
                        });
                        updateSelectedUserCount();
                    });
                }

                // Add character counter event listeners
                const editTitle = document.getElementById('edit-title');
                const editMessage = document.getElementById('edit-message');
                if (editTitle) {
                    editTitle.addEventListener('input', function () {
                        updateCharCounter('edit-title', 'edit-title-counter', 200);
                        this.classList.remove('border-red-500');
                    });
                }
                if (editMessage) {
                    editMessage.addEventListener('input', function () {
                        updateCharCounter('edit-message', 'edit-message-counter', 1000);
                        this.classList.remove('border-red-500');
                    });
                }

                // Initialize user count
                updateSelectedUserCount();

                // Close modal when clicking outside
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.addEventListener('click', function (e) {
                        if (e.target === this) {
                            closeModalElement(this);
                        }
                    });
                });
                
                // Close modal with Escape key
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') {
                        closeAllModals();
                    }
                });
                
                // Add click listeners to close buttons
                document.querySelectorAll('.close-modal').forEach(button => {
                    button.addEventListener('click', function (e) {
                        e.stopPropagation();
                        closeModalElement(this.closest('.modal'));
                    });
                });
            });
        </script>
</body>

</html>
