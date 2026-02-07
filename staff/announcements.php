<?php
// staff/announcements.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../vendor/autoload.php'; // For PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
// Send email to specific users for announcement
function sendAnnouncementEmail($email, $fullName, $title, $message, $type = 'basic', $imageUrl = null) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'cabanagarchiel@gmail.com'; // Replace with your email
        $mail->Password   = 'qmdh ofnf bhfj wxsa'; // Replace with your email password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->setFrom('cabanagarchiel@gmail.com', 'Barangay Luz Health Center');
        $mail->addAddress($email, $fullName);
        $mail->isHTML(true);
        $mail->Subject = ($type === 'lab_result') ? 'Lab Result Notification' : 'New Announcement Notification';

        // Use the correct host for absolute URLs
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $logoUrl = 'https://' . $host . '/community-health-tracker/asssets/images/Luz.jpg';
        $imageHtml = '';
        if ($imageUrl) {
            // If the imageUrl is relative, make it absolute
            if (strpos($imageUrl, 'http') !== 0) {
                $imageUrl = 'https://' . $host . $imageUrl;
            }
            $imageHtml = '<div style="text-align:center;margin:24px 0;"><img src="' . htmlspecialchars($imageUrl) . '" alt="Announcement Image" style="max-width:100%;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.10);"></div>';
        }

        $mail->Body = '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Announcement</title>
            <style>
                body { font-family: Arial, Helvetica, sans-serif; background: #f4f6f8; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); overflow: hidden; }
                .header { background: #3498db; color: #fff; padding: 32px 24px 20px 24px; text-align: center; }
                .header h1 { margin: 0; font-size: 2rem; font-weight: 700; letter-spacing: 1px; }
                .logo { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-bottom: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.10); background: #fff; border: 3px solid #fff; }
                .content { padding: 32px 24px; color: #2c3e50; }
                .content h2 { color: #3498db; margin-top: 0; }
                .footer { background: #f8f9fa; color: #7b8a8b; font-size: 13px; text-align: center; padding: 18px 24px; border-top: 1px solid #e2e8f0; }
                .btn { display: inline-block; background: #3498db; color: #fff; padding: 10px 24px; border-radius: 6px; text-decoration: none; font-weight: 600; margin-top: 18px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <img src="' . $logoUrl . '" alt="Barangay Luz Logo" class="logo"><br>
                    <h1>Barangay Luz Health Center</h1>
                </div>
                <div class="content">
                    <h2>' . htmlspecialchars($title) . '</h2>
                    <p>Dear ' . htmlspecialchars($fullName) . ',</p>
                    ' . $imageHtml . '
                    <p>' . nl2br(htmlspecialchars($message)) . '</p>
                    <p style="margin-top:2em;">If you have questions, please contact us or visit the health center.</p>
                </div>
                <div class="footer">
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
function createTargetedAnnouncementNotification($announcementId, $title, $targetUsers) {
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

function createAnnouncementNotification($announcementId, $title) {
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

// Handle form submission for new announcement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_announcement'])) {
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    $priority = isset($_POST['priority']) ? $_POST['priority'] : 'normal';
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $audience_type = isset($_POST['audience_type']) ? $_POST['audience_type'] : 'public';
    $target_users = isset($_POST['target_users']) ? (is_array($_POST['target_users']) ? array_filter($_POST['target_users']) : []) : [];
    $announcement_type = isset($_POST['announcement_type']) ? $_POST['announcement_type'] : 'basic';
    
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
                $success = 'Message broadcasted to all users successfully!';
            } else {
                // For landing_page announcements, no notifications needed
                $success = 'Landing page announcement published successfully!';
            }
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
                          COUNT(CASE WHEN ua.status = 'accepted' THEN 1 END) as accepted_count,
                          COUNT(CASE WHEN ua.status = 'dismissed' THEN 1 END) as dismissed_count,
                          COUNT(CASE WHEN ua.status IS NULL THEN 1 END) as pending_count
                          FROM sitio1_announcements a
                          LEFT JOIN sitio1_users u ON u.approved = TRUE AND a.audience_type IN ('public', 'specific')
                          LEFT JOIN user_announcements ua ON ua.announcement_id = a.id AND ua.user_id = u.id
                          WHERE a.staff_id = ? AND a.status = 'active'
                          GROUP BY a.id
                          ORDER BY a.post_date DESC");
    $stmt->execute([$staffId]);
    $activeAnnouncements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT * FROM sitio1_announcements 
                          WHERE staff_id = ? AND status = 'archived' 
                          ORDER BY post_date DESC");
    $stmt->execute([$staffId]);
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
        --shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .alert-success { background-color: #f0fdf4; border: 2px solid #bbf7d0; color: #065f46; }
    .alert-error { background-color: #fef2f2; border: 2px solid #fecaca; color: #b91c1c; }
    .custom-notification {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1rem;
        font-weight: 500;
        min-width: 320px;
        max-width: 480px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        border-radius: 0.75rem;
        margin-bottom: 1rem;
        opacity: 0;
        transform: translateY(-20px);
        transition: opacity 0.4s cubic-bezier(.4,0,.2,1), transform 0.4s cubic-bezier(.4,0,.2,1);
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
    }

    .card-header {
        padding: 1.25rem;
        border-bottom: 1px solid var(--border);
        background: var(--light);
        font-weight: 600;
    }

    .card-body {
        padding: 1.25rem;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: var(--secondary);
    }

    .form-control {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--border);
        border-radius: 6px;
        font-size: 0.875rem;
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
        resize: vertical;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.75rem 1.5rem;
        font-weight: 500;
        border-radius: 6px;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        gap: 0.5rem;
        font-family: 'Poppins', sans-serif;
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
        border-radius: 30px;
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
        background: white;
        color: var(--secondary);
        border: 2px solid #7e7e7eff;
        border-radius: 30px;
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

    .tab-nav {
        display: flex;
        border-bottom: 1px solid var(--border);
        background: white;
    }

    .tab-btn {
        padding: 1rem 1.5rem;
        background: none;
        border: none;
        border-bottom: 2px solid transparent;
        font-weight: 500;
        color: var(--gray);
        cursor: pointer;
        transition: all 0.2s;
    }

    .tab-btn:hover {
        color: var(--primary);
    }

    .tab-btn.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
        background: #f0f9ff;
    }

    .announcement-item {
        background: white;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 0.75rem;
        transition: all 0.2s;
    }

    .announcement-item:hover {
        box-shadow: var(--shadow);
    }

    .announcement-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 0.75rem;
    }

    .announcement-title {
        font-weight: 600;
        color: var(--secondary);
        margin-bottom: 0.25rem;
    }

    .announcement-meta {
        font-size: 0.75rem;
        color: var(--gray);
    }

    .announcement-content {
        color: var(--secondary);
        font-size: 0.875rem;
        line-height: 1.5;
        margin-bottom: 1rem;
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
        background: #fee2e2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }

    .badge-medium {
        background: #fef3c7;
        color: #d97706;
        border: 1px solid #fde68a;
    }

    .badge-normal {
        background: #f0f9ff;
        color: var(--primary);
        border: 1px solid #bfdbfe;
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
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 0.75rem;
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
        align-items: center;
        justify-content: center;
        padding: 1rem;
        border: 1px solid var(--border);
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
    }

    .radio-input:checked + .radio-label {
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
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 1rem;
        text-align: center;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--primary);
        margin-bottom: 0.25rem;
    }

    .stat-label {
        font-size: 0.875rem;
        color: var(--gray);
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
    
    <div class="container mx-auto px-4 py-6 mt-16">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-secondary mb-2">Announcement Management</h1>
            <p class="text-gray-600">Create and manage community health announcements</p>
        </div>

        <!-- Stats -->
        <div class="stats-grid mb-6">
            <div class="stat-card">
                <div class="stat-value"><?= count($activeAnnouncements) ?></div>
                <div class="stat-label">Active Announcements</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= count($archivedAnnouncements) ?></div>
                <div class="stat-label">Archived</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?= count($allUsers) ?></div>
                <div class="stat-label">Registered Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">
                    <?php
                    $totalResponses = 0;
                    foreach ($activeAnnouncements as $announcement) {
                        $totalResponses += $announcement['accepted_count'] + $announcement['dismissed_count'];
                    }
                    echo $totalResponses;
                    ?>
                </div>
                <div class="stat-label">Total Responses</div>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
            <script>document.addEventListener('DOMContentLoaded', function() { showNotification('error', <?= json_encode($error) ?>); });</script>
        <?php endif; ?>
        <?php if ($success): ?>
            <script>document.addEventListener('DOMContentLoaded', function() { showNotification('success', <?= json_encode($success) ?>); });</script>
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
            <!-- Left Column - Form -->
            <div class="lg:col-span-2">
                <div class="card mb-6">
                    <div class="card-header">
                        <h2 class="text-lg font-semibold text-secondary">Create New Announcement</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <!-- Title -->
                            <!-- Loading Animation Overlay -->
                            <div id="announcement-loading" style="display:none;position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,0.7);z-index:1000;align-items:center;justify-content:center;" class="flex">
                                <div class="flex flex-col items-center justify-center w-full h-full">
                                    <svg class="animate-spin h-12 w-12 text-blue-500 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                    </svg>
                                    <span class="text-lg font-semibold text-blue-600">Sending announcement and emails...</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Title *</label>
                                <input type="text" name="title" required class="form-control" 
                                       placeholder="Enter announcement title" maxlength="100">
                            </div>

                            <!-- Message -->
                            <div class="form-group">
                                <label class="form-label">Message *</label>
                                <textarea name="message" required class="form-control" 
                                          placeholder="Type your announcement message here..." 
                                          maxlength="500" rows="4"></textarea>
                            </div>

                            <!-- Settings -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div class="form-group">
                                    <label class="form-label">Priority</label>
                                    <select name="priority" class="form-control">
                                        <option value="normal">Normal</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Expiry Date</label>
                                    <input type="date" name="expiry_date" class="form-control" min="<?= date('Y-m-d') ?>">
                                </div>
                            </div>

                            <!-- Audience -->
                            <div class="form-group">
                                <label class="form-label mb-2">Audience</label>
                                <div class="radio-group">
                                    <div class="radio-option">
                                        <input type="radio" id="audience-landing" name="audience_type" value="landing_page" class="radio-input" checked>
                                        <label for="audience-landing" class="radio-label">
                                            <i class="fas fa-globe mb-2 text-primary"></i>
                                            <span class="font-medium">Landing Page</span>
                                            <span class="text-xs text-gray-500">All visitors</span>
                                        </label>
                                    </div>
                                    
                                    <div class="radio-option">
                                        <input type="radio" id="audience-public" name="audience_type" value="public" class="radio-input">
                                        <label for="audience-public" class="radio-label">
                                            <i class="fas fa-users mb-2 text-primary"></i>
                                            <span class="font-medium">All Users</span>
                                            <span class="text-xs text-gray-500">Registered only</span>
                                        </label>
                                    </div>
                                    
                                    <div class="radio-option">
                                        <input type="radio" id="audience-specific" name="audience_type" value="specific" class="radio-input">
                                        <label for="audience-specific" class="radio-label">
                                            <i class="fas fa-user-friends mb-2 text-primary"></i>
                                            <span class="font-medium">Specific Users</span>
                                            <span class="text-xs text-gray-500">Select recipients</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div id="user-selection" class="mt-3 hidden">
                                    <div class="mb-2">
                                        <input type="text" id="user-search" placeholder="Search users..." class="form-control">
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
                                                        <span class="text-gray-400 ml-1">@<?= htmlspecialchars($user['username']) ?></span>
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
                                <div class="file-upload" onclick="document.getElementById('announcement_image').click()">
                                    <input type="file" id="announcement_image" name="announcement_image" 
                                           accept="image/*" class="hidden" onchange="updateImageName(this)">
                                    <i class="fas fa-cloud-upload-alt text-3xl mb-2 text-gray-400"></i>
                                    <p class="font-medium" id="image-name">Click to upload image</p>
                                    <p class="text-sm text-gray-500">JPG, PNG, GIF • Max 5MB</p>
                                </div>
                            </div>

                            <!-- Announcement Type Buttons (for Specific Users) -->
                            <div id="announcement-type-buttons" class="flex gap-3 pt-4 border-t border-gray-200 hidden">
                                <button type="submit" name="post_announcement" value="basic" class="btn btn-success" onclick="setAnnouncementType('basic'); return showAnnouncementLoading();">
                                    <i class="fas fa-paper-plane"></i> Basic Announcement
                                </button>
                                <button type="submit" name="post_announcement" value="lab_result" class="btn btn-info" onclick="setAnnouncementType('lab_result'); return showAnnouncementLoading();">
                                    <i class="fas fa-vial"></i> Lab Results
                                </button>
                            </div>
                            <!-- Submit for other audience types -->
                            <div id="announcement-submit" class="flex justify-end gap-3 pt-4 border-t border-gray-200">
                                <button type="button" onclick="clearForm()" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </button>
                                <button type="submit" name="post_announcement" class="btn btn-success" onclick="return showAnnouncementLoading();">
                                    <i class="fas fa-paper-plane"></i> Publish
                                </button>
                            </div>
                                    
                            <input type="hidden" id="announcement_type" name="announcement_type" value="basic">
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Column - Announcements List -->
            <div class="lg:col-span-1">
                <div class="card">
                    <div class="card-header">
                        <h2 class="text-lg font-semibold text-secondary">Announcements</h2>
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
                    <div id="active-tab-content" class="p-4">
                        <?php
                        $displayLimit = 3;
                        $hasMore = count($activeAnnouncements) > $displayLimit;
                        ?>
                        <?php if (empty($activeAnnouncements)): ?>
                            <div class="empty-state">
                                <i class="fas fa-bullhorn empty-icon"></i>
                                <p>No active announcements</p>
                            </div>
                        <?php else: ?>
                            <?php for ($i = 0; $i < min($displayLimit, count($activeAnnouncements)); $i++):
                                $announcement = $activeAnnouncements[$i];
                            ?>
                                <div class="announcement-item">
                                    <div class="announcement-header">
                                        <div>
                                            <h3 class="announcement-title"><?= htmlspecialchars($announcement['title']) ?></h3>
                                            <div class="announcement-meta">
                                                <?= date('M d, Y', strtotime($announcement['post_date'])) ?>
                                            </div>
                                        </div>
                                        <div class="flex gap-1">
                                            <button onclick="openViewModal(<?= htmlspecialchars(json_encode($announcement, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>)"
                                                    class="btn btn-primary btn-sm"
                                                    title="View">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button onclick="openEditModal(<?= htmlspecialchars(json_encode($announcement, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>)"
                                                    class="btn btn-warning btn-sm"
                                                    title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="openArchiveModal(<?= $announcement['id'] ?>)"
                                                    class="btn btn-danger btn-sm"
                                                    title="Archive">
                                                <i class="fas fa-archive"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="announcement-content">
                                        <?= htmlspecialchars(substr($announcement['message'], 0, 100)) ?>
                                        <?php if (strlen($announcement['message']) > 100): ?>...<?php endif; ?>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="badge badge-<?= $announcement['priority'] ?>">
                                            <?= ucfirst($announcement['priority']) ?>
                                        </span>
                                        <div class="stats">
                                            <div class="stat-item">
                                                <div class="stat-icon stat-accepted">
                                                    <i class="fas fa-check"></i>
                                                </div>
                                                <span class="text-sm"><?= $announcement['accepted_count'] ?></span>
                                            </div>
                                            <div class="stat-item">
                                                <div class="stat-icon stat-pending">
                                                    <i class="fas fa-clock"></i>
                                                </div>
                                                <span class="text-sm"><?= $announcement['pending_count'] ?></span>
                                            </div>
                                            <div class="stat-item">
                                                <div class="stat-icon stat-dismissed">
                                                    <i class="fas fa-times"></i>
                                                </div>
                                                <span class="text-sm"><?= $announcement['dismissed_count'] ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endfor; ?>
                            <?php if ($hasMore): ?>
                                <button class="btn btn-primary w-full mt-2" onclick="openAllAnnouncementsModal()">View All</button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Archived Announcements -->
                    <div id="archived-tab-content" class="hidden p-4">
                        <?php if (empty($archivedAnnouncements)): ?>
                            <div class="empty-state">
                                <i class="fas fa-archive empty-icon"></i>
                                <p>No archived announcements</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($archivedAnnouncements as $announcement): ?>
                                <div class="announcement-item">
                                    <div class="announcement-header">
                                        <div>
                                            <h3 class="announcement-title"><?= htmlspecialchars($announcement['title']) ?></h3>
                                            <div class="announcement-meta">
                                                Archived on <?= date('M d, Y', strtotime($announcement['post_date'])) ?>
                                            </div>
                                        </div>
                                        <div class="flex gap-1">
                                            <form method="POST" action="" class="inline">
                                                <input type="hidden" name="id" value="<?= $announcement['id'] ?>">
                                                <button type="submit" name="repost_announcement"
                                                        class="btn btn-warning btn-sm"
                                                        title="Repost"
                                                        onclick="return confirm('Repost this announcement?')">
                                                    <i class="fas fa-redo"></i>
                                                </button>
                                            </form>
                                            <button onclick="openDeleteModal(<?= $announcement['id'] ?>)"
                                                    class="btn btn-danger btn-sm"
                                                    title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Announcement Details</h3>
                <button onclick="closeViewModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="modalContent"></div>
            </div>
            <div class="modal-footer">
                <button onclick="closeViewModal()" class="btn btn-secondary">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Announcement</h3>
                <button type="button" onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700">
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
                            <input type="text" name="title" id="edit-title" required class="form-control" maxlength="200">
                            <span class="text-xs text-gray-500" id="edit-title-counter">0/200 characters</span>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Message *</label>
                            <textarea name="message" id="edit-message" required class="form-control" rows="5" maxlength="1000"></textarea>
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
                    <button type="button" onclick="closeEditModal()" class="btn btn-secondary">
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
                <button type="button" onclick="closeDeleteModal()" class="text-gray-500 hover:text-gray-700">
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
                    <button type="button" onclick="closeDeleteModal()" class="btn btn-secondary">
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
                <button type="button" onclick="closeArchiveModal()" class="text-gray-500 hover:text-gray-700">
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
                    <button type="button" onclick="closeArchiveModal()" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-archive"></i> Archive
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- All Announcements Modal -->
    <div id="allAnnouncementsModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h3 class="modal-title">All Announcements</h3>
                <button onclick="closeAllAnnouncementsModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="allAnnouncementsBody">
                <!-- Announcements will be rendered here -->
            </div>
            <div class="modal-footer">
                <button onclick="closeAllAnnouncementsModal()" class="btn btn-secondary">Close</button>
            </div>
        </div>
    </div>
    <script>
        // All Announcements Modal with Pagination
        function openAllAnnouncementsModal(page = 1, perPage = 5) {
            const announcements = <?= json_encode($activeAnnouncements) ?>;
            const total = announcements.length;
            const totalPages = Math.ceil(total / perPage);
            let html = '';

            const start = (page - 1) * perPage;
            const end = Math.min(start + perPage, total);

            for (let i = start; i < end; i++) {
                const a = announcements[i];
                html += `
                    <div class=\"announcement-item mb-3\">
                        <div class=\"announcement-header\">
                            <div>
                                <h3 class=\"announcement-title\">${a.title}</h3>
                                <div class=\"announcement-meta\">${new Date(a.post_date).toLocaleDateString()}</div>
                            </div>
                            <div class=\"flex gap-1\">
                                <button onclick='openViewModal(${JSON.stringify(a)})' class=\"btn btn-primary btn-sm\" title=\"View\"><i class=\"fas fa-eye\"></i></button>
                                <button onclick='openEditModal(${JSON.stringify(a)})' class=\"btn btn-warning btn-sm\" title=\"Edit\"><i class=\"fas fa-edit\"></i></button>
                                <button onclick='openArchiveModal(${a.id})' class=\"btn btn-danger btn-sm\" title=\"Archive\"><i class=\"fas fa-archive\"></i></button>
                            </div>
                        </div>
                        <div class=\"announcement-content\">${a.message.length > 100 ? a.message.substring(0, 100) + '...' : a.message}</div>
                    </div>
                `;
            }

            // Pagination controls
            if (totalPages > 1) {
                html += `<div class=\"flex justify-center gap-2 mt-4\">`;
                for (let p = 1; p <= totalPages; p++) {
                    html += `<button class=\"btn btn-sm ${p === page ? 'btn-primary' : ''}\" onclick=\"openAllAnnouncementsModal(${p},${perPage})\">${p}</button>`;
                }
                html += `</div>`;
            }

            document.getElementById('allAnnouncementsBody').innerHTML = html;
            document.getElementById('allAnnouncementsModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeAllAnnouncementsModal() {
            document.getElementById('allAnnouncementsModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }
        // Tab functionality
        document.querySelectorAll('.tab-btn').forEach(button => {
            button.addEventListener('click', function() {
                const tabId = this.dataset.tab;
                
                // Update active tab button
                document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Show active tab content
                document.getElementById('active-tab-content').classList.toggle('hidden', tabId !== 'active');
                document.getElementById('archived-tab-content').classList.toggle('hidden', tabId !== 'archived');
            });
        });
        
        // Audience selection
        document.querySelectorAll('input[name="audience_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const userSelection = document.getElementById('user-selection');
                const typeButtons = document.getElementById('announcement-type-buttons');
                const submitDiv = document.getElementById('announcement-submit');
                if (this.value === 'specific') {
                    userSelection.classList.remove('hidden');
                    typeButtons.classList.remove('hidden');
                    submitDiv.classList.add('hidden');
                    updateSelectedUserCount();
                } else {
                    userSelection.classList.add('hidden');
                    typeButtons.classList.add('hidden');
                    submitDiv.classList.remove('hidden');
                }
            });
        });

        // Set announcement type
        function setAnnouncementType(type) {
            document.getElementById('announcement_type').value = type;
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
            document.getElementById('selected-count').textContent = `${count} user${count !== 1 ? 's' : ''} selected`;
        }
        
        // User search
        document.getElementById('user-search')?.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const userItems = document.querySelectorAll('.checkbox-item');
            
            userItems.forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(searchTerm) ? 'flex' : 'none';
            });
            
            updateSelectedUserCount();
        });
        
        // Clear form
        function clearForm() {
            document.querySelector('form').reset();
            document.getElementById('user-selection').classList.add('hidden');
            document.getElementById('image-name').textContent = 'Click to upload image';
            document.getElementById('selected-count').textContent = '0 users selected';
            document.getElementById('audience-landing').checked = true;
        }
        
        // View Modal Functions
        function openViewModal(announcement) {
            const modal = document.getElementById('viewModal');
            const modalContent = document.getElementById('modalContent');
            
            const postDate = new Date(announcement.post_date).toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric'
            });
            
            let content = `
                <div class="space-y-4">
                    <div class="flex justify-between items-start">
                        <h4 class="font-semibold text-lg">${announcement.title || 'No Title'}</h4>
                        <span class="badge badge-${announcement.priority || 'normal'}">
                            ${announcement.priority ? announcement.priority.charAt(0).toUpperCase() + announcement.priority.slice(1) : 'Normal'} Priority
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Posted on:</p>
                            <p class="font-medium">${postDate}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Audience:</p>
                            <p class="font-medium">
                                ${announcement.audience_type === 'public' ? 'All Users' : 
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
                        <p class="text-gray-700 whitespace-pre-line">${announcement.message || 'No message'}</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div class="p-3 bg-green-50 border border-green-200 rounded">
                        <p class="text-green-700 font-bold">${announcement.accepted_count || 0}</p>
                        <p class="text-sm text-green-600">Accepted</p>
                    </div>
                    <div class="p-3 bg-yellow-50 border border-yellow-200 rounded">
                        <p class="text-yellow-700 font-bold">${announcement.pending_count || 0}</p>
                        <p class="text-sm text-yellow-600">Pending</p>
                    </div>
                    <div class="p-3 bg-red-50 border border-red-200 rounded">
                        <p class="text-red-700 font-bold">${announcement.dismissed_count || 0}</p>
                        <p class="text-sm text-red-600">Dismissed</p>
                    </div>
                </div>
            `;
            
            modalContent.innerHTML = content;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Edit Modal Functions
        function openEditModal(announcement) {
            const modal = document.getElementById('editModal');
            document.getElementById('edit-id').value = announcement.id || '';
            document.getElementById('edit-title').value = announcement.title || '';
            document.getElementById('edit-message').value = announcement.message || '';
            document.getElementById('edit-priority').value = announcement.priority || 'normal';
            document.getElementById('edit-expiry').value = announcement.expiry_date || '';
            
            // Update character counters
            updateCharCounter('edit-title', 'edit-title-counter', 200);
            updateCharCounter('edit-message', 'edit-message-counter', 1000);
            
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeEditModal() {
            const modal = document.getElementById('editModal');
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
            // Clear any error states
            modal.querySelectorAll('.form-control').forEach(input => {
                input.classList.remove('border-red-500');
            });
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
        
        // Archive Modal Functions
        function openArchiveModal(announcementId) {
            const modal = document.getElementById('archiveModal');
            document.getElementById('archive-id').value = announcementId;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeArchiveModal() {
            document.getElementById('archiveModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }
        
        // Delete Modal Functions
        function openDeleteModal(announcementId) {
            const modal = document.getElementById('deleteModal');
            document.getElementById('delete-id').value = announcementId;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    if (this.id === 'viewModal') closeViewModal();
                    if (this.id === 'editModal') closeEditModal();
                    if (this.id === 'deleteModal') closeDeleteModal();
                    if (this.id === 'archiveModal') closeArchiveModal();
                }
            });
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeViewModal();
                closeEditModal();
                closeDeleteModal();
                closeArchiveModal();
            }
        });

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Add click listeners to checkboxes
            document.querySelectorAll('.checkbox-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    if (!e.target.matches('input[type="checkbox"]')) {
                        const checkbox = this.querySelector('input[type="checkbox"]');
                        if (checkbox) {
                            checkbox.checked = !checkbox.checked;
                            updateSelectedUserCount();
                        }
                    }
                });
            });

            // Add character counter event listeners
            const editTitle = document.getElementById('edit-title');
            const editMessage = document.getElementById('edit-message');

            if (editTitle) {
                editTitle.addEventListener('input', function() {
                    updateCharCounter('edit-title', 'edit-title-counter', 200);
                    this.classList.remove('border-red-500');
                });
            }

            if (editMessage) {
                editMessage.addEventListener('input', function() {
                    updateCharCounter('edit-message', 'edit-message-counter', 1000);
                    this.classList.remove('border-red-500');
                });
            }

            updateSelectedUserCount();

            // Auto-hide success/error messages after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transition = 'opacity 0.5s';
                    setTimeout(() => alert.remove(), 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>