<?php
// staff/announcements.php
require_once __DIR__ . '/../includes/auth.php';
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
        $mail->Username = 'cabanagarchiel@gmail.com';
        $mail->Password = 'qmdh ofnf bhfj wxsa';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('cabanagarchiel@gmail.com', 'Barangay Luz Health Center');
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

        // Use the correct host for absolute URLs
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $logoUrl = 'https://' . $host . '/community-health-tracker/asssets/images/Luz.jpg';
        $imageHtml = '';
        if ($imageUrl) {
            if (strpos($imageUrl, 'http') !== 0) {
                $imageUrl = 'https://' . $host . $imageUrl;
            }
            $imageHtml = '<div style="text-align:center;margin:24px 0;"><img src="' . htmlspecialchars($imageUrl) . '" alt="Announcement Image" style="max-width:100%;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.10);"></div>';
        }

        // Unique, branded email design for Brgy Luz Health Center
        $subtitle = 'Official Announcement';
        if ($type === 'basic') {
            $subtitle = 'Specific Resident Announcement';
        } elseif ($type === 'lab_result') {
            $subtitle = 'Specific Resident Lab Result';
        }
        $mail->Body = '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Barangay Luz Health Center Announcement</title>
            <link href="https://fonts.googleapis.com/css?family=Poppins:400,600,700,800&display=swap" rel="stylesheet">
            <style>
                body {
                    background: #fff;
                    margin: 0;
                    padding: 0;
                    font-family: Poppins, Arial, Helvetica, sans-serif;
                }
                .main-container {
                    max-width: 480px;
                    margin: 40px auto;
                    background: #fff;
                    border-radius: 16px;
                    box-shadow: 0 6px 32px rgba(52,152,219,0.18);
                    overflow: hidden;
                }
                .email-header {
                    background: #3498db;
                    color: #fff;
                    text-align: center;
                    padding: 32px 18px 16px 18px;
                    box-shadow: 0 2px 12px rgba(52,152,219,0.12);
                }
                .email-header .logo {
                    width: 72px;
                    height: 72px;
                    border-radius: 50%;
                    object-fit: cover;
                    margin-bottom: 10px;
                    box-shadow: 0 2px 8px rgba(52,152,219,0.18);
                }
                .email-header h1 {
                    margin: 0;
                    font-size: 1.6rem;
                    font-weight: 700;
                    letter-spacing: 1px;
                    font-family: Poppins, Arial, Helvetica, sans-serif;
                }
                .email-header .subtitle {
                    font-size: 1rem;
                    font-weight: 500;
                    margin-top: 4px;
                    color: #e0eaff;
                }
                .email-content {
                    padding: 24px 18px 18px 18px;
                    color: #222;
                    font-size: 1rem;
                }
                .email-content h2 {
                    color: #3498db;
                    margin-top: 0;
                    font-size: 1.15rem;
                    font-weight: 600;
                }
                .email-content .greeting {
                    font-weight: 500;
                    margin-bottom: 10px;
                }
                .email-content .main-message {
                    background: #f4f8ff;
                    padding: 14px 16px;
                    border-radius: 8px;
                    margin: 14px 0 14px 0;
                    font-size: 1rem;
                    box-shadow: 0 2px 8px rgba(52,152,219,0.10);
                }
                .email-content .image-section {
                    margin: 14px 0;
                    text-align: center;
                }
                .email-content .image-section img {
                    max-width: 90%;
                    border-radius: 10px;
                    box-shadow: 0 2px 8px rgba(52,152,219,0.18);
                }
                .email-footer {
                    background: #fff;
                    color: #3498db;
                    font-size: 12px;
                    text-align: center;
                    padding: 16px 18px;
                    box-shadow: 0 -2px 8px rgba(52,152,219,0.10);
                }
                @media (max-width: 600px) {
                    .main-container { max-width: 98vw; }
                    .email-content { padding: 12px 4vw 12px 4vw; }
                }
            </style>
        </head>
        <body>
            <div class="main-container">
                <div class="email-header">
                    <img src="../asssets/images/Luz.jpg" alt="Barangay Luz Logo" class="logo">
                    <h1>Barangay Luz Health Center</h1>
                    <div class="subtitle">' . $subtitle . '</div>
                </div>
                <div class="email-content">
                    <h2>' . htmlspecialchars($title) . '</h2>
                    <div class="greeting">Dear ' . htmlspecialchars($fullName) . ',</div>
                    <div class="main-message">' . nl2br(htmlspecialchars($message)) . '</div>
                    ' . ($imageHtml ? '<div class="image-section">' . $imageHtml . '</div>' : '') . '
                    <div style="margin-top:1.5em;color:#3498db;font-size:0.95em;">If you have questions, please contact us or visit the health center.</div>
                </div>
                <div class="email-footer">
                    This is an automated message. Please do not reply.<br>
                    &copy; ' . date('Y') . ' Barangay Luz Health Monitoring and Tracking System
                </div>
            </div>
        </body>
        </html>';
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
            } else {
                // For landing_page announcements, no notifications needed
                $success = 'Landing page announcement published successfully!';
            }

            // Prevent duplicate POST on refresh: redirect to same page with success message
            $_SESSION['announcement_success'] = $success;
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit();
        } catch (PDOException $e) {
            $error = 'Error sending message: ' . $e->getMessage();
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
        $success = 'Announcement updated successfully!';
    } catch (PDOException $e) {
        $error = 'Error updating announcement: ' . $e->getMessage();
    }
}

// Handle archive operation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_announcement'])) {
    $id = $_POST['id'];
    try {
        $stmt = $pdo->prepare("UPDATE sitio1_announcements SET status = 'archived' WHERE id = ? AND staff_id = ?");
        $stmt->execute([$id, $staffId]);
        $success = 'Announcement archived successfully!';
    } catch (PDOException $e) {
        $error = 'Error archiving announcement: ' . $e->getMessage();
    }
}

// Handle repost operation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['repost_announcement'])) {
    $id = $_POST['id'];
    try {
        $stmt = $pdo->prepare("UPDATE sitio1_announcements SET status = 'active', post_date = NOW() WHERE id = ? AND staff_id = ?");
        $stmt->execute([$id, $staffId]);
        $success = 'Announcement reposted successfully!';
    } catch (PDOException $e) {
        $error = 'Error reposting announcement: ' . $e->getMessage();
    }
}

// Handle delete operation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_announcement'])) {
    $id = $_POST['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM sitio1_announcements WHERE id = ? AND staff_id = ? AND status = 'archived'");
        $stmt->execute([$id, $staffId]);
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
                          COUNT(CASE WHEN ua.status = 'accepted' THEN 1 END) as accepted_count,
                          COUNT(CASE WHEN ua.status = 'dismissed' THEN 1 END) as dismissed_count,
                          COUNT(CASE WHEN ua.status IS NULL THEN 1 END) as pending_count
                          FROM sitio1_announcements a
                          JOIN sitio1_staff s ON a.staff_id = s.id
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            background-color: #ecf0f1;
            color: var(--secondary);
        }

        :root {
            --primary: #3498db;
            --primary-dark: #2980b9;
            --secondary: #2c3e50;
            --success: #3994d1ff;
            --warning: #f39c12;
            --danger: #e74c3c;
            --light: #f8f9fa;
            --gray: #95a5a6;
            --border: #e2e8f0;
            --shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .alert-success {
            background-color: #f0fdf4;
            border: 2px solid #bbf7d0;
            color: #065f46;
        }

        .alert-error {
            background-color: #fef2f2;
            border: 2px solid #fecaca;
            color: #b91c1c;
        }

        .custom-notification {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
            font-weight: 500;
            min-width: 320px;
            max-width: 480px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-radius: 0.75rem;
            margin-bottom: 1rem;
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.4s cubic-bezier(.4, 0, .2, 1), transform 0.4s cubic-bezier(.4, 0, .2, 1);
            pointer-events: auto;
        }

        .custom-notification.show {
            opacity: 1;
            transform: translateY(0);
        }

        .card {
            background: white;
            border-radius: 8px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            height: 100%;
            padding: 36px 36px;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 1.125rem;
            color: var(--secondary);
        }

        .form-control {
            width: 100%;
            padding: 1rem 1.5rem;
            border: 1px solid #3C96E1;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s;
            font-family: 'Poppins', sans-serif;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        textarea.form-control {
            min-height: 120px;
            resize: none;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            gap: 0.5rem;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
        }

        .btn-primary {
            background: white;
            color: var(--primary);
            border: 2px solid rgba(52, 152, 219, 1);
        }

        .btn-primary:hover {
            background: #f0f9ff;
            border-color: rgba(52, 152, 219, 0.6);
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--success);
            color: white;
            border-radius: 6px;
        }

        .btn-success:hover {
            background: #358cc7ff;
            border-color: #2980b9;
            transform: translateY(-2px);
        }

        .btn-warning {
            background: white;
            color: var(--warning);
            border: 2px solid rgba(243, 156, 18, 1);
        }

        .btn-warning:hover {
            background: #fef3c7;
            border-color: rgba(243, 156, 18, 0.6);
            transform: translateY(-2px);
        }

        .btn-danger {
            background: white;
            color: var(--danger);
            border: 2px solid rgba(231, 76, 60, 1);
        }

        .btn-danger:hover {
            background: #fef2f2;
            border-color: rgba(231, 76, 60, 0.6);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: rgba(29, 133, 221, 0.3);
            color: #1D85DD;
            border-radius: 6px;
        }

        .btn-secondary:hover {
            background: #f8fafc;
            border-color: var(--gray);
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
        }

        .btn-lab {
            background: #10b981;
            color: white;
            border-radius: 6px;
        }

        .btn-lab:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        .tab-nav {
            display: flex;
            margin-bottom: 1rem;
            background: white;
            gap: 1rem;
        }

        .tab-btn {
            padding: 0.75rem 1.5rem;
            background: rgba(29, 133, 221, 0.3);
            border: none;
            font-weight: 500;
            color: #1D85DD;
            cursor: pointer;
            transition: all 0.2s;
            border-radius: 6px;
        }

        .tab-btn:hover {
            color: var(--primary);
        }

        .tab-btn.active {
            color: #FFFFFF;
            border-bottom-color: var(--primary);
            background: #1D85DD;
        }

        .announcement-item {
            background: white;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1rem;
            transition: all 0.2s;
            margin-bottom: 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            box-sizing: border-box;
            min-height: 0;
            max-width: 100%;
            overflow: visible;
        }

        .announcement-item:hover {
            box-shadow: var(--shadow);
        }

        .announcement-title {
            font-weight: 400;
            font-size: 1.125rem;
            color: #1D85DD;
            margin-bottom: 0.25rem;
        }

        .announcement-meta {
            font-size: 0.75rem;
            color: var(--gray);
        }

        .announcement-content {
            color: var(--secondary);
            font-size: 0.95rem;
            line-height: 1.6;
            margin-bottom: 1.2rem;
            word-break: break-word;
            overflow-wrap: break-word;
            padding: 0.5rem 0.2rem;
            background: #f8fafc;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(52,152,219,0.07);
            min-height: 0;
            max-width: 100%;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge-high {
            background: rgba(224, 25, 25, 0.3);
            color: #dc2626;
            font-size: 1rem;
        }

        .badge-medium {
            background: rgba(253, 136, 2, 0.3);
            color: #FD8802;
            font-size: 1rem;
        }

        .badge-normal {
            background: rgba(29, 133, 221, 0.3);
            color: var(--primary);
            font-size: 1rem;
        }

        .badge-lab {
            background: rgba(16, 185, 129, 0.3);
            color: #10b981;
            font-size: 1rem;
        }

        .stats {
            display: flex;
            gap: 1rem;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stat-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
        }

        .stat-accepted {
            background: #d1fae5;
            color: #059669;
        }

        .stat-pending {
            background: #fef3c7;
            color: #d97706;
        }

        .stat-dismissed {
            background: #fee2e2;
            color: #dc2626;
        }

        /* MODAL STYLES - FIXED */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 8px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            z-index: 1001;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
            transform: translateY(0) !important;
        }

        .modal-header {
            padding: 1.25rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--secondary);
        }

        .modal-body {
            padding: 1.25rem;
        }

        .modal-footer {
            padding: 1.25rem;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        .radio-group {
            display: grid;
            gap: 1.5rem;
            margin-bottom: 1rem;
        }

        .radio-option {
            position: relative;
        }

        .radio-input {
            position: absolute;
            opacity: 0;
        }

        .radio-label {
            display: flex;
            flex-direction: column;
            padding: 20px 32px;
            border: 1px solid var(--border);
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .radio-input:checked+.radio-label {
            border-color: var(--primary);
            background: #f0f9ff;
        }

        .checkbox-group {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 0.75rem;
        }

        .checkbox-item {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            cursor: pointer;
        }

        .checkbox-item:hover {
            background: var(--light);
        }

        .file-upload {
            border: 2px dashed var(--border);
            border-radius: 6px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
        }

        .file-upload:hover {
            border-color: var(--primary);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--gray);
        }

        .empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 24px 36px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            line-height: 2rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--gray);
        }

        /* Loader animation styles */
        #announcement-loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(248, 250, 252, 0.95);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .cht-loader-bg {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .cht-loader-unique {
            display: flex;
            gap: 0.7em;
            margin-bottom: 1.5rem;
        }

        .cht-loader-bounce {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: linear-gradient(135deg, #60a5fa 40%, #2563eb 100%);
            animation: cht-bounce 1.1s infinite cubic-bezier(.68, -0.55, .27, 1.55);
        }

        .cht-loader-bounce:nth-child(2) {
            animation-delay: 0.2s;
            background: linear-gradient(135deg, #93c5fd 40%, #3b82f6 100%);
        }

        .cht-loader-bounce:nth-child(3) {
            animation-delay: 0.4s;
            background: linear-gradient(135deg, #a5b4fc 40%, #6366f1 100%);
        }

        @keyframes cht-bounce {

            0%,
            80%,
            100% {
                transform: translateY(0);
            }

            40% {
                transform: translateY(-30px);
            }
        }

        .cht-loader-text {
            color: #22223b;
            font-size: 1.2rem;
            font-weight: 500;
            letter-spacing: 0.01em;
            text-align: center;
        }

        @media (max-width: 768px) {
            .radio-group {
                grid-template-columns: 1fr;
            }

            .announcement-header {
                flex-direction: column;
                gap: 0.75rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .modal-content {
                width: 95%;
                margin: 0.5rem;
            }
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">

    <!-- Modern Loader Animation Overlay -->
    <div id="announcement-loading">
        <div class="cht-loader-bg">
            <div class="cht-loader-unique">
                <div class="cht-loader-bounce"></div>
                <div class="cht-loader-bounce"></div>
                <div class="cht-loader-bounce"></div>
            </div>
            <div class="cht-loader-text" id="announcement-loading-message">Sending announcement and emails...</div>
            <span class="mt-3 text-base text-slate-500 text-center" style="font-family: Poppins, Arial, Helvetica, sans-serif; font-weight: 400;">Please wait while we process your announcement.<br>Do not close or refresh this page.</span>
        </div>

        <script>
        // Expose all active announcements to JS for modal
        window.allActiveAnnouncementsData = <?php echo json_encode($activeAnnouncements, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
        </script>
    </div>

    <div class="container w-full max-w-none px-8 py-10">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold mb-2">Announcement Management</h1>
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
                        <svg class="h-14 w-14" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M0 4C0 1.79086 1.79086 0 4 0H46C48.2091 0 50 1.79086 50 4V46C50 48.2091 48.2091 50 46 50H4C1.79086 50 0 48.2091 0 46V4Z"
                                fill="#2563EB" fill-opacity="0.3" />
                            <path
                                d="M32.1552 20.1339C32.2772 20.2558 32.374 20.4006 32.4401 20.5599C32.5061 20.7192 32.5401 20.89 32.5401 21.0625C32.5401 21.235 32.5061 21.4058 32.4401 21.5651C32.374 21.7244 32.2772 21.8692 32.1552 21.9911L22.9677 31.1786C22.8458 31.3006 22.701 31.3974 22.5417 31.4635C22.3823 31.5295 22.2116 31.5635 22.0391 31.5635C21.8666 31.5635 21.6958 31.5295 21.5365 31.4635C21.3771 31.3974 21.2324 31.3006 21.1105 31.1786L17.173 27.2411C16.9267 26.9948 16.7883 26.6608 16.7883 26.3125C16.7883 25.9642 16.9267 25.6302 17.173 25.3839C17.4193 25.1376 17.7533 24.9993 18.1016 24.9993C18.4499 24.9993 18.7839 25.1376 19.0302 25.3839L22.0391 28.3945L30.298 20.1339C30.4199 20.0119 30.5646 19.9151 30.724 19.849C30.8833 19.783 31.0541 19.749 31.2266 19.749C31.3991 19.749 31.5698 19.783 31.7292 19.849C31.8885 19.9151 32.0333 20.0119 32.1552 20.1339ZM41.7266 25C41.7266 28.3746 40.7259 31.6735 38.851 34.4794C36.9762 37.2853 34.3114 39.4723 31.1936 40.7637C28.0758 42.0551 24.6451 42.393 21.3353 41.7346C18.0255 41.0763 14.9853 39.4512 12.5991 37.065C10.2128 34.6788 8.58778 31.6385 7.92942 28.3287C7.27106 25.0189 7.60896 21.5882 8.90038 18.4705C10.1918 15.3527 12.3787 12.6879 15.1847 10.813C17.9906 8.9382 21.2894 7.9375 24.6641 7.9375C29.1879 7.94228 33.525 9.74146 36.7238 12.9403C39.9226 16.1391 41.7218 20.4762 41.7266 25ZM39.1016 25C39.1016 22.1445 38.2548 19.3532 36.6684 16.979C35.082 14.6047 32.8272 12.7542 30.1891 11.6615C27.551 10.5687 24.6481 10.2828 21.8475 10.8399C19.0469 11.397 16.4743 12.772 14.4552 14.7911C12.4361 16.8103 11.0611 19.3828 10.504 22.1834C9.94691 24.984 10.2328 27.8869 11.3256 30.525C12.4183 33.1631 14.2688 35.4179 16.643 37.0043C19.0173 38.5908 21.8086 39.4375 24.6641 39.4375C28.4918 39.4332 32.1615 37.9107 34.8681 35.2041C37.5747 32.4974 39.0972 28.8277 39.1016 25Z"
                                fill="#3C96E1" />
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
                        <svg class="h-14 w-14" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M0 4C0 1.79086 1.79086 0 4 0H46C48.2091 0 50 1.79086 50 4V46C50 48.2091 48.2091 50 46 50H4C1.79086 50 0 48.2091 0 46V4Z"
                                fill="#D97706" fill-opacity="0.3" />
                            <path
                                d="M35.1641 9.25H14.1641C12.7717 9.25 11.4363 9.80312 10.4518 10.7877C9.46719 11.7723 8.91406 13.1076 8.91406 14.5V35.5C8.91406 36.8924 9.46719 38.2277 10.4518 39.2123C11.4363 40.1969 12.7717 40.75 14.1641 40.75H35.1641C36.5564 40.75 37.8918 40.1969 38.8764 39.2123C39.8609 38.2277 40.4141 36.8924 40.4141 35.5V14.5C40.4141 13.1076 39.8609 11.7723 38.8764 10.7877C37.8918 9.80312 36.5564 9.25 35.1641 9.25ZM37.7891 35.5C37.7891 36.1962 37.5125 36.8639 37.0202 37.3562C36.5279 37.8484 35.8603 38.125 35.1641 38.125H14.1641C13.4679 38.125 12.8002 37.8484 12.3079 37.3562C11.8156 36.8639 11.5391 36.1962 11.5391 35.5V14.5C11.5391 13.8038 11.8156 13.1361 12.3079 12.6438C12.8002 12.1516 13.4679 11.875 14.1641 11.875H35.1641C35.8603 11.875 36.5279 12.1516 37.0202 12.6438C37.5125 13.1361 37.7891 13.8038 37.7891 14.5V35.5ZM23.3516 21.7188C23.3516 22.1081 23.2361 22.4888 23.0198 22.8125C22.8034 23.1363 22.496 23.3886 22.1362 23.5376C21.7765 23.6866 21.3806 23.7256 20.9987 23.6497C20.6168 23.5737 20.266 23.3862 19.9907 23.1109C19.7154 22.8355 19.5279 22.4847 19.4519 22.1028C19.3759 21.7209 19.4149 21.3251 19.5639 20.9653C19.7129 20.6056 19.9653 20.2981 20.289 20.0818C20.6128 19.8655 20.9934 19.75 21.3828 19.75C21.905 19.75 22.4057 19.9574 22.7749 20.3266C23.1441 20.6958 23.3516 21.1966 23.3516 21.7188ZM29.9141 28.2812C29.9141 28.6706 29.7986 29.0513 29.5823 29.375C29.3659 29.6988 29.0585 29.9511 28.6987 30.1001C28.339 30.2491 27.9431 30.2881 27.5612 30.2122C27.1793 30.1362 26.8285 29.9487 26.5532 29.6734C26.2779 29.398 26.0904 29.0472 26.0144 28.6653C25.9384 28.2834 25.9774 27.8876 26.1264 27.5278C26.2754 27.1681 26.5278 26.8606 26.8515 26.6443C27.1753 26.428 27.5559 26.3125 27.9453 26.3125C28.4675 26.3125 28.9682 26.5199 29.3374 26.8891C29.7066 27.2583 29.9141 27.7591 29.9141 28.2812Z"
                                fill="#D97706" />
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
                        <svg class="h-14 w-14" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M0 4C0 1.79086 1.79086 0 4 0H46C48.2091 0 50 1.79086 50 4V46C50 48.2091 48.2091 50 46 50H4C1.79086 50 0 48.2091 0 46V4Z"
                                fill="#9333EA" fill-opacity="0.3" />
                            <path
                                d="M28.6013 17.125C29.6397 17.125 30.6547 16.8171 31.5181 16.2402C32.3814 15.6633 33.0544 14.8434 33.4517 13.8841C33.8491 12.9248 33.953 11.8692 33.7505 10.8508C33.5479 9.83238 33.0479 8.89692 32.3137 8.16269C31.5794 7.42847 30.644 6.92845 29.6256 6.72588C28.6072 6.52331 27.5516 6.62727 26.5923 7.02463C25.6329 7.42199 24.813 8.0949 24.2361 8.95826C23.6593 9.82162 23.3513 10.8367 23.3513 11.875C23.3513 13.2674 23.9045 14.6027 24.889 15.5873C25.8736 16.5719 27.209 17.125 28.6013 17.125ZM28.6013 9.25C29.1205 9.25 29.628 9.40396 30.0597 9.69239C30.4914 9.98083 30.8278 10.3908 31.0265 10.8705C31.2252 11.3501 31.2772 11.8779 31.1759 12.3871C31.0746 12.8963 30.8246 13.364 30.4575 13.7312C30.0904 14.0983 29.6227 14.3483 29.1135 14.4496C28.6043 14.5509 28.0765 14.4989 27.5968 14.3002C27.1171 14.1015 26.7072 13.7651 26.4187 13.3334C26.1303 12.9017 25.9763 12.3942 25.9763 11.875C25.9763 11.1788 26.2529 10.5111 26.7452 10.0188C27.2375 9.52656 27.9052 9.25 28.6013 9.25ZM39.1013 27.625C39.1013 27.9731 38.9631 28.3069 38.7169 28.5531C38.4708 28.7992 38.1369 28.9375 37.7888 28.9375C31.9958 28.9375 29.1017 26.0156 26.777 23.6678C26.3274 23.2134 25.8976 22.7819 25.4645 22.3816L23.2611 27.4478L29.3642 31.807C29.5342 31.9284 29.6728 32.0887 29.7684 32.2744C29.864 32.4602 29.9138 32.6661 29.9138 32.875V42.0625C29.9138 42.4106 29.7756 42.7444 29.5294 42.9906C29.2833 43.2367 28.9494 43.375 28.6013 43.375C28.2532 43.375 27.9194 43.2367 27.6733 42.9906C27.4271 42.7444 27.2888 42.4106 27.2888 42.0625V33.5509L22.1914 29.9088L16.6806 42.5859C16.5786 42.8204 16.4103 43.0201 16.1963 43.1603C15.9824 43.3005 15.7321 43.3751 15.4763 43.375C15.2961 43.3754 15.1178 43.338 14.953 43.2651C14.6339 43.1264 14.383 42.8667 14.2553 42.543C14.1276 42.2194 14.1337 41.8583 14.2721 41.5391L23.1446 21.1347C21.6172 20.864 19.7124 21.3316 17.4517 22.5423C15.6486 23.5369 13.9658 24.7353 12.4363 26.114C12.181 26.3427 11.8463 26.4624 11.5039 26.4476C11.1615 26.4328 10.8384 26.2847 10.6038 26.0348C10.3692 25.785 10.2416 25.4533 10.2483 25.1106C10.255 24.7679 10.3955 24.4415 10.6398 24.201C11.0499 23.8155 20.7608 14.8117 26.836 20.0863C27.4644 20.631 28.0632 21.2348 28.6407 21.8205C30.9294 24.1305 33.0901 26.3125 37.7888 26.3125C38.1369 26.3125 38.4708 26.4508 38.7169 26.6969C38.9631 26.9431 39.1013 27.2769 39.1013 27.625Z"
                                fill="#9333EA" />
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
                        <svg class="h-14 w-14" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M0 4C0 1.79086 1.79086 0 4 0H46C48.2091 0 50 1.79086 50 4V46C50 48.2091 48.2091 50 46 50H4C1.79086 50 0 48.2091 0 46V4Z"
                                fill="#22C55E" fill-opacity="0.3" />
                            <path
                                d="M40.4141 36.8125H39.1016V10.5625C39.1016 10.2144 38.9633 9.88056 38.7171 9.63442C38.471 9.38828 38.1372 9.25 37.7891 9.25H28.6016C28.2535 9.25 27.9196 9.38828 27.6735 9.63442C27.4273 9.88056 27.2891 10.2144 27.2891 10.5625V17.125H19.4141C19.066 17.125 18.7321 17.2633 18.486 17.5094C18.2398 17.7556 18.1016 18.0894 18.1016 18.4375V25H11.5391C11.191 25 10.8571 25.1383 10.611 25.3844C10.3648 25.6306 10.2266 25.9644 10.2266 26.3125V36.8125H8.91406C8.56597 36.8125 8.23213 36.9508 7.98598 37.1969C7.73984 37.4431 7.60156 37.7769 7.60156 38.125C7.60156 38.4731 7.73984 38.8069 7.98598 39.0531C8.23213 39.2992 8.56597 39.4375 8.91406 39.4375H40.4141C40.7622 39.4375 41.096 39.2992 41.3421 39.0531C41.5883 38.8069 41.7266 38.4731 41.7266 38.125C41.7266 37.7769 41.5883 37.4431 41.3421 37.1969C41.096 36.9508 40.7622 36.8125 40.4141 36.8125ZM29.9141 11.875H36.4766V36.8125H29.9141V11.875ZM20.7266 19.75H27.2891V36.8125H20.7266V19.75ZM12.8516 27.625H18.1016V36.8125H12.8516V27.625Z"
                                fill="#22C55E" />
                        </svg>
                    </div>
                </div>
                <div class="text-xl font-medium text-gray-500">Total Responses</div>
            </div>
        </div>

        <!-- CREATE ANNOUNCEMENT TITLE HEADER -->
        <div>
            <div class="border-b-2 border-gray-300 pb-4 mb-6">
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
                onsubmit="return showAnnouncementLoading(this.querySelector('[name=post_lab_result]') ? 'lab_result' : 'basic')">

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
                                    <select name="priority" class="form-control">
                                        <option value="">Select Priority</option>
                                        <option value="normal">Normal</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Expiry Date <span style="color: #FF5555;">*</span></label>
                                    <input type="date" name="expiry_date" class="form-control"
                                        min="<?= date('Y-m-d') ?>" id="announcement-expiry">
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
                                                    <svg class="w-10 h-10" viewBox="0 0 25 25" fill="none"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <path
                                                            d="M19.5317 6.25V16.4063C19.5317 16.6135 19.4494 16.8122 19.3029 16.9587C19.1564 17.1052 18.9576 17.1875 18.7504 17.1875C18.5432 17.1875 18.3445 17.1052 18.198 16.9587C18.0515 16.8122 17.9692 16.6135 17.9692 16.4063V8.13574L6.80317 19.3027C6.65657 19.4493 6.45775 19.5317 6.25043 19.5317C6.04312 19.5317 5.84429 19.4493 5.6977 19.3027C5.55111 19.1561 5.46875 18.9573 5.46875 18.75C5.46875 18.5427 5.55111 18.3439 5.6977 18.1973L16.8647 7.03125H8.59418C8.38698 7.03125 8.18827 6.94894 8.04176 6.80243C7.89524 6.65591 7.81293 6.4572 7.81293 6.25C7.81293 6.0428 7.89524 5.84409 8.04176 5.69757C8.18827 5.55106 8.38698 5.46875 8.59418 5.46875H18.7504C18.9576 5.46875 19.1564 5.55106 19.3029 5.69757C19.4494 5.84409 19.5317 6.0428 19.5317 6.25Z"
                                                            fill="#1D85DD" />
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
                                                    <svg class="w-10 h-10" viewBox="0 0 25 25" fill="none"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <path
                                                            d="M19.5317 6.25V16.4063C19.5317 16.6135 19.4494 16.8122 19.3029 16.9587C19.1564 17.1052 18.9576 17.1875 18.7504 17.1875C18.5432 17.1875 18.3445 17.1052 18.198 16.9587C18.0515 16.8122 17.9692 16.6135 17.9692 16.4063V8.13574L6.80317 19.3027C6.65657 19.4493 6.45775 19.5317 6.25043 19.5317C6.04312 19.5317 5.84429 19.4493 5.6977 19.3027C5.55111 19.1561 5.46875 18.9573 5.46875 18.75C5.46875 18.5427 5.55111 18.3439 5.6977 18.1973L16.8647 7.03125H8.59418C8.38698 7.03125 8.18827 6.94894 8.04176 6.80243C7.89524 6.65591 7.81293 6.4572 7.81293 6.25C7.81293 6.0428 7.89524 5.84409 8.04176 5.69757C8.18827 5.55106 8.38698 5.46875 8.59418 5.46875H18.7504C18.9576 5.46875 19.1564 5.55106 19.3029 5.69757C19.4494 5.84409 19.5317 6.0428 19.5317 6.25Z"
                                                            fill="#1D85DD" />
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
                                                    <svg class="w-10 h-10" viewBox="0 0 25 25" fill="none"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <path
                                                            d="M19.5317 6.25V16.4063C19.5317 16.6135 19.4494 16.8122 19.3029 16.9587C19.1564 17.1052 18.9576 17.1875 18.7504 17.1875C18.5432 17.1875 18.3445 17.1052 18.198 16.9587C18.0515 16.8122 17.9692 16.6135 17.9692 16.4063V8.13574L6.80317 19.3027C6.65657 19.4493 6.45775 19.5317 6.25043 19.5317C6.04312 19.5317 5.84429 19.4493 5.6977 19.3027C5.55111 19.1561 5.46875 18.9573 5.46875 18.75C5.46875 18.5427 5.55111 18.3439 5.6977 18.1973L16.8647 7.03125H8.59418C8.38698 7.03125 8.18827 6.94894 8.04176 6.80243C7.89524 6.65591 7.81293 6.4572 7.81293 6.25C7.81293 6.0428 7.89524 5.84409 8.04176 5.69757C8.18827 5.55106 8.38698 5.46875 8.59418 5.46875H18.7504C18.9576 5.46875 19.1564 5.55106 19.3029 5.69757C19.4494 5.84409 19.5317 6.0428 19.5317 6.25Z"
                                                            fill="#1D85DD" />
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
                                    <i class="fas fa-cloud-upload-alt text-3xl mb-2 text-gray-400"></i>
                                    <p class="font-medium" id="image-name">Click to upload image</p>
                                    <p class="text-sm text-gray-500">JPG, PNG, GIF • Max 5MB</p>
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
                                            const allFilled = title && message && priority && expiry;
                                            const expiryValid = isFutureDate(expiry);
                                            const enable = allFilled && expiryValid;
                                            postBtn.disabled = !enable;
                                            postBtn.classList.toggle('opacity-50', !enable);
                                            postBtn.classList.toggle('cursor-not-allowed', !enable);
                                        }

                                        [titleInput, messageInput, priorityInput, expiryInput].forEach(el => {
                                            el.addEventListener('input', validateAnnouncementFields);
                                            el.addEventListener('change', validateAnnouncementFields);
                                        });

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
                            Active (<?= count($activeAnnouncements) ?>)
                        </button>
                        <button class="tab-btn" data-tab="archived">
                            Archived (<?= count($archivedAnnouncements) ?>)
                        </button>
                    </div>

                    <!-- Active Announcements -->
                    <div id="active-tab-content" style="display: flex; flex-direction: column; gap: 0.5rem; flex: 1; overflow: auto;">
                        <?php if (empty($activeAnnouncements)): ?>
                            <div class="empty-state col-span-full">
                                <i class="fas fa-bullhorn empty-icon"></i>
                                <p>No active announcements</p>
                            </div>
                        <?php else: ?>
                            <?php $activeCount = 0; ?>
                            <?php
                            $activeCount = 0;
                            $seenAnnouncements = [];
                            foreach ($activeAnnouncements as $announcement) {
                                // Use title and content as a unique key
                                $uniqueKey = md5($announcement['title'] . (isset($announcement['content']) ? $announcement['content'] : ''));
                                if (in_array($uniqueKey, $seenAnnouncements)) {
                                    continue; // skip duplicate
                                }
                                $seenAnnouncements[] = $uniqueKey;
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
                                                if ($announcement['audience_type'] === 'public') {
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
                        <i class="fas fa-times"></i>
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
                        <i class="fas fa-times"></i>
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
                        <i class="fas fa-times"></i>
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
                                <select name="priority" id="edit-priority" class="form-control">
                                    <option value="normal">Normal</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
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
            // Helper function to escape HTML
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // Close all modals function
            function closeAllModals() {
                const modals = document.querySelectorAll('.modal');
                modals.forEach(modal => {
                    modal.classList.remove('active');
                });
                document.body.style.overflow = 'auto';
            }

            // Show modal function
            function showModal(modalId) {
                closeAllModals();
                const modal = document.getElementById(modalId);
                if (modal) {
                    setTimeout(() => {
                        modal.classList.add('active');
                        document.body.style.overflow = 'hidden';
                    }, 10);
                }
            }

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
                        msg = buttonType === 'lab_result' 
                            ? 'Sending lab results to specific users and emails...'
                            : 'Sending announcement to specific users and emails...';
                    } else if (audienceType === 'public') {
                        msg = 'Broadcasting announcement to all users and sending emails...';
                    }
                    loadingMsg.textContent = msg;
                    loadingDiv.style.display = 'flex';
                }
                return true;
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
                                return `
                                    <div class="announcement-item mb-4 p-4 rounded shadow border bg-white">
                                        <div class="flex justify-between items-center mb-2">
                                            <div>
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
                                        <div class="mb-2">
                                            <p class="text-gray-500 mb-1">Message:</p>
                                            <div class="bg-gray-50 p-3 rounded border text-gray-700 whitespace-pre-line">${escapeHtml(announcement.message || 'No message')}</div>
                                        </div>
                                    </div>
                                `;
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
                            closeAllModals();
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
                        closeAllModals();
                    });
                });
            });
        </script>
</body>

</html>