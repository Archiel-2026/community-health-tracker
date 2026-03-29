<?php
// staff/announcements-enhanced.php
require_once __DIR__ . '/../includes/auth.php';

// Add notification functions before they're called
function createTargetedAnnouncementNotification($announcementId, $title, $targetUsers) {
    global $pdo;
    
    try {
        $message = "New announcement: " . $title;
        $link = "/community-health-tracker/user/announcements.php";
        
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
        $link = "/community-health-tracker/user/announcements.php";
        
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
                                  (staff_id, title, message, priority, expiry_date, status, audience_type, image_path, post_date) 
                                  VALUES (?, ?, ?, ?, ?, 'active', ?, ?, NOW())");
            $stmt->execute([$staffId, $title, $message, $priority, $expiry_date, $audience_type, $image_path]);
            
            $announcementId = $pdo->lastInsertId();
            
            // Handle target users if specific audience
            if ($audience_type === 'specific' && !empty($target_users)) {
                foreach ($target_users as $userId) {
                    $stmt = $pdo->prepare("INSERT INTO announcement_targets (announcement_id, user_id) VALUES (?, ?)");
                    $stmt->execute([$announcementId, $userId]);
                }
                
                createTargetedAnnouncementNotification($announcementId, $title, $target_users);
                $success = 'Message sent to ' . count($target_users) . ' user(s) successfully!';
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

// Handle delete operation (soft delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_announcement'])) {
    $id = $_POST['id'];
    try {
        $stmt = $pdo->prepare("UPDATE sitio1_announcements SET status = 'deleted' WHERE id = ? AND staff_id = ?");
        $stmt->execute([$id, $staffId]);
        $success = 'Announcement deleted successfully!';
    } catch (PDOException $e) {
        $error = 'Error deleting announcement: ' . $e->getMessage();
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

// Get all announcements by this staff (excluding deleted)
$activeAnnouncements = [];
$archivedAnnouncements = [];

try {
    $stmt = $pdo->prepare("
        SELECT a.*, 
               COUNT(CASE WHEN ua.status = 'accepted' THEN 1 END) as accepted_count,
               COUNT(CASE WHEN ua.status = 'dismissed' THEN 1 END) as dismissed_count,
               COUNT(CASE WHEN ua.status IS NULL THEN 1 END) as pending_count,
               COUNT(DISTINCT am.id) as message_count
        FROM sitio1_announcements a
        LEFT JOIN sitio1_users u ON u.approved = TRUE AND a.audience_type IN ('public', 'specific')
        LEFT JOIN user_announcements ua ON ua.announcement_id = a.id AND ua.user_id = u.id
        LEFT JOIN announcement_messages am ON am.announcement_id = a.id
        WHERE a.staff_id = ? AND a.status IN ('active', 'archived')
        GROUP BY a.id
        ORDER BY a.post_date DESC
    ");
    $stmt->execute([$staffId]);
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($announcements as $ann) {
        if ($ann['status'] === 'active') {
            $activeAnnouncements[] = $ann;
        } else {
            $archivedAnnouncements[] = $ann;
        }
    }
    
    // Get detailed responses
    foreach ($activeAnnouncements as &$announcement) {
        $stmt = $pdo->prepare("
            SELECT u.id, u.full_name, ua.response_date 
            FROM user_announcements ua
            JOIN sitio1_users u ON ua.user_id = u.id
            WHERE ua.announcement_id = ? AND ua.status = 'accepted'
            ORDER BY ua.response_date DESC
        ");
        $stmt->execute([$announcement['id']]);
        $announcement['accepted_users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("
            SELECT u.id, u.full_name, ua.response_date 
            FROM user_announcements ua
            JOIN sitio1_users u ON ua.user_id = u.id
            WHERE ua.announcement_id = ? AND ua.status = 'dismissed'
            ORDER BY ua.response_date DESC
        ");
        $stmt->execute([$announcement['id']]);
        $announcement['dismissed_users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($announcement['audience_type'] === 'specific') {
            $stmt = $pdo->prepare("
                SELECT u.id, u.full_name 
                FROM announcement_targets at
                JOIN sitio1_users u ON at.user_id = u.id
                WHERE at.announcement_id = ?
            ");
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
    <title>Enhanced Announcements - Community Health Tracker</title>
    <!-- Tailwind CSS - Offline Local Build -->
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/tailwind.css">
    <!-- Local Font Awesome for offline support -->
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/font-awesome.min.css">
    <link rel="stylesheet" href="/asssets/css/normalize.css">
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

    body {
        font-family: 'Poppins', sans-serif;
        background-color: #ecf0f1;
        color: #2c3e50;
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
    }

    .card {
        background: white;
        border-radius: 8px;
        border: 1px solid var(--border);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.6);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        animation: fadeIn 0.3s ease;
    }

    .modal.active {
        display: flex;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .modal-content {
        background: white;
        border-radius: 12px;
        width: 90%;
        max-width: 700px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        animation: slideUp 0.3s ease;
    }

    @keyframes slideUp {
        from {
            transform: translateY(50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .modal-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        border-radius: 12px 12px 0 0;
    }

    .modal-title {
        font-size: 1.3rem;
        font-weight: 600;
    }

    .modal-body {
        padding: 1.5rem;
    }

    .modal-footer {
        padding: 1.5rem;
        border-top: 1px solid var(--border);
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        font-weight: 500;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-family: 'Poppins', sans-serif;
    }

    .btn-primary {
        background: white;
        color: var(--primary);
        border: 2px solid var(--primary);
    }

    .btn-primary:hover {
        background: var(--primary);
        color: white;
    }

    .btn-success {
        background: var(--success);
        color: white;
        border: 2px solid var(--success);
    }

    .btn-success:hover {
        background: #358cc7ff;
    }

    .btn-danger {
        background: white;
        color: var(--danger);
        border: 2px solid var(--danger);
    }

    .btn-danger:hover {
        background: var(--danger);
        color: white;
    }

    .btn-secondary {
        background: #f0f0f0;
        color: #666;
        border: 2px solid #ddd;
    }

    .btn-secondary:hover {
        background: #e0e0e0;
    }

    .form-group {
        margin-bottom: 1.25rem;
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
        font-family: 'Poppins', sans-serif;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    }

    textarea.form-control {
        min-height: 100px;
        resize: vertical;
    }

    .announcement-item {
        background: white;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1rem;
        transition: all 0.2s;
    }

    .announcement-item:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        border-color: var(--primary);
    }

    .badge {
        display: inline-block;
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .badge-high {
        background: #fee2e2;
        color: #dc2626;
    }

    .badge-medium {
        background: #fef3c7;
        color: #d97706;
    }

    .badge-normal {
        background: #f0f9ff;
        color: var(--primary);
    }

    .message-container {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
        max-height: 400px;
        overflow-y: auto;
    }

    .message-item {
        background: white;
        border-left: 3px solid var(--primary);
        padding: 0.75rem;
        margin-bottom: 0.75rem;
        border-radius: 4px;
    }

    .message-sender {
        font-weight: 600;
        color: var(--secondary);
        font-size: 0.85rem;
    }

    .message-time {
        font-size: 0.75rem;
        color: var(--gray);
        margin-top: 0.25rem;
    }

    .message-text {
        margin-top: 0.5rem;
        color: #555;
        line-height: 1.4;
    }

    .delete-confirm-modal {
        max-width: 400px;
    }

    .confirm-text {
        color: var(--danger);
        font-weight: 500;
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
        padding: 1.5rem;
        text-align: center;
    }

    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--primary);
    }

    .stat-label {
        font-size: 0.85rem;
        color: var(--gray);
        margin-top: 0.5rem;
    }
    </style>
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8 mt-20 max-w-6xl">
        <h1 class="text-3xl font-bold text-secondary mb-2">Announcement Management</h1>
        <p class="text-gray-600 mb-8">Create, manage, and communicate with community members</p>

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 rounded">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                    <span class="text-red-700"><?= htmlspecialchars($error) ?></span>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 rounded">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-3"></i>
                    <span class="text-green-700"><?= htmlspecialchars($success) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-grid">
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
                <div class="stat-label">Total Users</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Create Announcement Form -->
            <div class="lg:col-span-2">
                <div class="card">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-blue-100">
                        <h2 class="text-lg font-semibold text-secondary">Create New Announcement</h2>
                    </div>
                    <form method="POST" enctype="multipart/form-data" class="p-6">
                        <div class="form-group">
                            <label class="form-label">Title *</label>
                            <input type="text" name="title" required class="form-control" placeholder="Enter announcement title">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Message *</label>
                            <textarea name="message" required class="form-control" placeholder="Type your message..."></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
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
                                <input type="date" name="expiry_date" class="form-control">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Audience</label>
                            <div class="grid grid-cols-3 gap-3">
                                <label class="flex items-center p-3 border rounded cursor-pointer hover:bg-blue-50">
                                    <input type="radio" name="audience_type" value="landing_page" checked class="mr-2"> Landing Page
                                </label>
                                <label class="flex items-center p-3 border rounded cursor-pointer hover:bg-blue-50">
                                    <input type="radio" name="audience_type" value="public" class="mr-2"> All Users
                                </label>
                                <label class="flex items-center p-3 border rounded cursor-pointer hover:bg-blue-50">
                                    <input type="radio" name="audience_type" value="specific" class="mr-2"> Specific Users
                                </label>
                            </div>
                        </div>

                        <div id="user-selection" class="hidden mb-4">
                            <input type="text" id="user-search" placeholder="Search users..." class="form-control mb-3">
                            <div class="border rounded p-3 max-h-200 overflow-y-auto">
                                <?php foreach ($allUsers as $user): ?>
                                    <label class="flex items-center p-2 hover:bg-gray-50 cursor-pointer">
                                        <input type="checkbox" name="target_users[]" value="<?= $user['id'] ?>" class="user-checkbox mr-2">
                                        <span><?= htmlspecialchars($user['full_name']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="flex gap-3 pt-4 border-t">
                            <button type="submit" name="post_announcement" class="btn btn-success flex-1">
                                <i class="fas fa-paper-plane"></i> Publish Announcement
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Clear
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Announcements List -->
            <div class="card">
                <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-blue-100">
                    <h2 class="text-lg font-semibold text-secondary">My Announcements</h2>
                </div>
                <div class="p-6 space-y-4">
                    <?php if (empty($activeAnnouncements)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-bullhorn text-4xl mb-3 opacity-30"></i>
                            <p>No announcements yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($activeAnnouncements as $ann): ?>
                            <div class="announcement-item">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="font-semibold text-secondary"><?= htmlspecialchars($ann['title']) ?></h3>
                                    <span class="badge badge-<?= $ann['priority'] ?>"><?= ucfirst($ann['priority']) ?></span>
                                </div>
                                <p class="text-gray-600 text-sm mb-3"><?= htmlspecialchars(substr($ann['message'], 0, 80)) ?>...</p>
                                <div class="flex gap-2">
                                    <button onclick="openViewModal(<?= htmlspecialchars(json_encode($ann)) ?>)" class="btn btn-primary btn-sm flex-1">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <button onclick="openEditModal(<?= htmlspecialchars(json_encode($ann)) ?>)" class="btn btn-primary btn-sm flex-1">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button onclick="openDeleteModal(<?= $ann['id'] ?>)" class="btn btn-danger btn-sm flex-1">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
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
                <button onclick="closeViewModal()" class="text-white hover:opacity-80">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="viewContent"></div>
            </div>
            <div class="modal-footer">
                <button onclick="closeViewModal()" class="btn btn-secondary">Close</button>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Announcement</h3>
                <button onclick="closeEditModal()" class="text-white hover:opacity-80">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" id="editForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="editId">
                    <input type="hidden" name="edit_announcement" value="1">
                    
                    <div class="form-group">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" id="editTitle" required class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Message</label>
                        <textarea name="message" id="editMessage" required class="form-control"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Priority</label>
                            <select name="priority" id="editPriority" class="form-control">
                                <option value="normal">Normal</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Expiry Date</label>
                            <input type="date" name="expiry_date" id="editExpiry" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeEditModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content delete-confirm-modal">
            <div class="modal-header">
                <h3 class="modal-title">Delete Announcement</h3>
                <button onclick="closeDeleteModal()" class="text-white hover:opacity-80">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" id="deleteForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="deleteId">
                    <input type="hidden" name="delete_announcement" value="1">
                    
                    <div class="flex items-center gap-4 mb-4">
                        <i class="fas fa-exclamation-triangle text-4xl text-yellow-500"></i>
                        <div>
                            <p class="font-semibold">Are you sure?</p>
                            <p class="text-gray-600 text-sm">This announcement will be permanently deleted.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeDeleteModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Permanently</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal functions
        function openViewModal(announcement) {
            const content = document.getElementById('viewContent');
            const date = new Date(announcement.post_date).toLocaleDateString();
            
            content.innerHTML = `
                <div class="space-y-4">
                    <div>
                        <h3 class="text-xl font-semibold text-secondary mb-2">${announcement.title}</h3>
                        <div class="flex gap-2 mb-4">
                            <span class="badge badge-${announcement.priority}">${announcement.priority}</span>
                            <span class="text-sm text-gray-500">${date}</span>
                        </div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded">
                        <p class="text-gray-700 whitespace-pre-wrap">${announcement.message}</p>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="bg-green-50 p-3 rounded text-center">
                            <p class="text-2xl font-bold text-green-600">${announcement.accepted_count || 0}</p>
                            <p class="text-sm text-green-600">Accepted</p>
                        </div>
                        <div class="bg-yellow-50 p-3 rounded text-center">
                            <p class="text-2xl font-bold text-yellow-600">${announcement.pending_count || 0}</p>
                            <p class="text-sm text-yellow-600">Pending</p>
                        </div>
                        <div class="bg-red-50 p-3 rounded text-center">
                            <p class="text-2xl font-bold text-red-600">${announcement.dismissed_count || 0}</p>
                            <p class="text-sm text-red-600">Dismissed</p>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('viewModal').classList.add('active');
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.remove('active');
        }

        function openEditModal(announcement) {
            document.getElementById('editId').value = announcement.id;
            document.getElementById('editTitle').value = announcement.title;
            document.getElementById('editMessage').value = announcement.message;
            document.getElementById('editPriority').value = announcement.priority;
            document.getElementById('editExpiry').value = announcement.expiry_date || '';
            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        function openDeleteModal(id) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }

        // Audience selection
        document.querySelectorAll('input[name="audience_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const userSelection = document.getElementById('user-selection');
                userSelection.classList.toggle('hidden', this.value !== 'specific');
            });
        });

        // User search
        document.getElementById('user-search')?.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('#user-selection label').forEach(label => {
                const text = label.textContent.toLowerCase();
                label.style.display = text.includes(searchTerm) ? 'flex' : 'none';
            });
        });

        // Close modal when clicking outside
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });

        // Close modal with Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.active').forEach(m => m.classList.remove('active'));
            }
        });
    </script>
</body>
</html>
