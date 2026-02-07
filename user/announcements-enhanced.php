<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/header.php';

redirectIfNotLoggedIn();
if (!isUser()) {
    header('Location: /community-health-tracker/');
    exit();
}

// Check if user has profile image, if not redirect to upload profile page
redirectIfUserMissingProfile();

global $pdo;
$userId = $_SESSION['user']['id'];
$error = '';
$success = '';

// Handle announcement response
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respond_to_announcement'])) {
    $announcementId = $_POST['announcement_id'];
    $status = $_POST['respond_to_announcement'];
    
    if (!in_array($status, ['accepted', 'dismissed'])) {
        $error = 'Invalid response status';
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT a.id 
                FROM sitio1_announcements a
                LEFT JOIN announcement_targets at ON a.id = at.announcement_id
                WHERE a.id = ? 
                AND a.status = 'active'
                AND (a.audience_type = 'public' OR at.user_id = ?)
            ");
            $stmt->execute([$announcementId, $userId]);
            
            if (!$stmt->fetch()) {
                $error = 'This announcement is no longer available';
            } else {
                $stmt = $pdo->prepare("SELECT id FROM user_announcements WHERE user_id = ? AND announcement_id = ?");
                $stmt->execute([$userId, $announcementId]);
                
                if ($stmt->fetch()) {
                    $stmt = $pdo->prepare("UPDATE user_announcements SET status = ?, response_date = NOW() WHERE user_id = ? AND announcement_id = ?");
                    $stmt->execute([$status, $userId, $announcementId]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO user_announcements (user_id, announcement_id, status, response_date) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$userId, $announcementId, $status]);
                }
                
                $success = 'Response recorded successfully!';
            }
        } catch (PDOException $e) {
            $error = 'Error recording response: ' . $e->getMessage();
        }
    }
}

// Get announcements targeted to this user
$announcements = [];

try {
    $stmt = $pdo->prepare("
        SELECT a.*, ua.status as user_status, ua.response_date,
               s.full_name as staff_name,
               COUNT(DISTINCT am.id) as message_count
        FROM sitio1_announcements a
        LEFT JOIN user_announcements ua ON a.id = ua.announcement_id AND ua.user_id = ?
        LEFT JOIN sitio1_staff s ON a.staff_id = s.id
        LEFT JOIN announcement_messages am ON am.announcement_id = a.id
        WHERE a.status = 'active'
        AND (a.audience_type = 'public' OR a.id IN (
            SELECT announcement_id FROM announcement_targets WHERE user_id = ?
        ))
        GROUP BY a.id
        ORDER BY a.priority DESC, a.post_date DESC
    ");
    $stmt->execute([$userId, $userId]);
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error fetching announcements: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Community Health Tracker</title>
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
        --success: #27ae60;
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
        transition: all 0.2s;
    }

    .card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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
    }

    .modal.active {
        display: flex;
    }

    .modal-content {
        background: white;
        border-radius: 12px;
        width: 90%;
        max-width: 800px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    }

    .modal-header {
        padding: 1.5rem;
        border-bottom: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: linear-gradient(135deg, var(--primary), #2980b9);
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
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.875rem 1.75rem;
        border-radius: 8px;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        min-height: 44px;
        letter-spacing: 0.3px;
        border: none;
        font-family: 'Poppins', sans-serif;
    }

    .btn-primary {
        background: var(--primary);
        color: white;
    }

    .btn-primary:hover {
        background: #2980b9;
        transform: translateY(-2px);
    }

    .btn-secondary {
        background: #f0f0f0;
        color: #666;
    }

    .btn-secondary:hover {
        background: #e0e0e0;
    }

    .btn-success {
        background: var(--success);
        color: white;
    }

    .btn-success:hover {
        background: #229954;
    }

    .form-group {
        margin-bottom: 1.25rem;
    }

    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
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

    .announcement-card {
        background: white;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .announcement-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 1rem;
    }

    .announcement-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: #2c3e50;
    }

    .announcement-meta {
        display: flex;
        gap: 1rem;
        font-size: 0.85rem;
        color: var(--gray);
        margin-top: 0.5rem;
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
        margin-top: 1.5rem;
        max-height: 500px;
        overflow-y: auto;
    }

    .message-item {
        background: white;
        border-left: 4px solid var(--primary);
        padding: 1rem;
        margin-bottom: 0.75rem;
        border-radius: 4px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .message-content {
        flex: 1;
    }

    .message-sender {
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.9rem;
    }

    .message-text {
        margin-top: 0.5rem;
        color: #555;
        line-height: 1.5;
        word-wrap: break-word;
    }

    .message-time {
        font-size: 0.75rem;
        color: var(--gray);
        margin-top: 0.25rem;
    }

    .message-edited {
        font-size: 0.7rem;
        color: var(--warning);
        font-style: italic;
    }

    .message-actions {
        display: flex;
        gap: 0.5rem;
        margin-top: 0.5rem;
    }

    .message-btn {
        padding: 0.35rem 0.75rem;
        font-size: 0.75rem;
        background: #f0f0f0;
        color: #666;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .message-btn:hover {
        background: #ddd;
    }

    .message-input-area {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
        margin-top: 1rem;
        border: 1px solid var(--border);
    }

    .stats-header {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .stat-item {
        background: white;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 1rem;
        text-align: center;
    }

    .stat-number {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary);
    }

    .stat-label {
        font-size: 0.85rem;
        color: var(--gray);
        margin-top: 0.25rem;
    }

    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
    }

    .empty-icon {
        font-size: 3rem;
        color: var(--gray);
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    @media (max-width: 768px) {
        .announcement-header {
            flex-direction: column;
        }

        .stats-header {
            grid-template-columns: repeat(2, 1fr);
        }

        .modal-content {
            width: 95%;
        }
    }
    </style>
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container mx-auto px-4 py-8 mt-20 max-w-4xl">
        <h1 class="text-3xl font-bold text-secondary mb-2">Announcements</h1>
        <p class="text-gray-600 mb-8">Stay updated with community health center announcements</p>

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="mb-4 p-4 bg-red-50 border-l-4 border-red-500 rounded flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                <span class="text-red-700"><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="mb-4 p-4 bg-green-50 border-l-4 border-green-500 rounded flex items-center">
                <i class="fas fa-check-circle text-green-500 mr-3"></i>
                <span class="text-green-700"><?= htmlspecialchars($success) ?></span>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-header">
            <div class="stat-item">
                <div class="stat-number"><?= count($announcements) ?></div>
                <div class="stat-label">Total</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">
                    <?php 
                    echo count(array_filter($announcements, fn($a) => $a['user_status'] === 'accepted'));
                    ?>
                </div>
                <div class="stat-label">Accepted</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">
                    <?php 
                    echo count(array_filter($announcements, fn($a) => empty($a['user_status'])));
                    ?>
                </div>
                <div class="stat-label">Pending</div>
            </div>
        </div>

        <!-- Announcements List -->
        <?php if (empty($announcements)): ?>
            <div class="announcement-card">
                <div class="empty-state">
                    <i class="fas fa-inbox empty-icon"></i>
                    <h3 class="text-lg font-semibold mb-2">No Announcements</h3>
                    <p class="text-gray-600">You don't have any announcements yet</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($announcements as $ann): ?>
                <div class="announcement-card card-shadow"
                    style="border-left: 6px solid <?= $ann['announcement_type'] === 'lab_result' ? '#10B981' : '#2563eb' ?>; background: linear-gradient(90deg,<?= $ann['announcement_type'] === 'lab_result' ? '#F0FDF4' : '#DBEAFE' ?> 60%,#fff 100%);">
                    <div class="announcement-header">
                        <div class="flex-1">
                            <h2 class="announcement-title" style="color:<?= $ann['announcement_type'] === 'lab_result' ? '#10B981' : '#2563eb' ?>;font-weight:700;">
                                <?= htmlspecialchars($ann['title']) ?>
                            </h2>
                            <div class="announcement-meta">
                                <span><i class="fas fa-user-md mr-1"></i><?= htmlspecialchars($ann['staff_name'] ?? 'Staff') ?></span>
                                <span><i class="fas fa-calendar mr-1"></i><?= date('M d, Y', strtotime($ann['post_date'])) ?></span>
                                <?php if ($ann['message_count'] > 0): ?>
                                    <span><i class="fas fa-comments mr-1"></i><?= $ann['message_count'] ?> messages</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="announcement-badges flex flex-col gap-2">
                            <span class="badge <?= $ann['announcement_type'] === 'lab_result' ? 'badge-lab-result' : 'badge-simple' ?>">
                                <i class="fas fa-<?= $ann['announcement_type'] === 'lab_result' ? 'flask' : 'bullhorn' ?>"></i>
                                <?= $ann['announcement_type'] === 'lab_result' ? 'Lab Result' : 'Announcement' ?>
                            </span>
                            <span class="badge badge-<?= $ann['priority'] ?>"><i class="fas fa-flag"></i> <?= ucfirst($ann['priority']) ?></span>
                        </div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded mb-4 announcement-content">
                        <p class="text-gray-700 whitespace-pre-wrap"><?= htmlspecialchars($ann['message']) ?></p>
                    </div>
                    <?php if (!empty($ann['image_path'])): ?>
                        <div class="image-preview mb-2">
                            <img src="<?= htmlspecialchars($ann['image_path']) ?>" alt="Announcement Image" onclick="openImageModal('<?= htmlspecialchars($ann['image_path']) ?>')">
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($ann['expiry_date'])): ?>
                        <div class="mb-2 text-<?= $ann['announcement_type'] === 'lab_result' ? 'green' : 'blue' ?>-700 text-sm flex items-center gap-2">
                            <i class="fas fa-clock"></i> Expires: <?= date('M d, Y', strtotime($ann['expiry_date'])) ?>
                        </div>
                    <?php endif; ?>
                    <!-- Response Section -->
                    <div class="mb-4 p-4 rounded"
                        style="background:<?= $ann['announcement_type'] === 'lab_result' ? '#F0FDF4' : '#DBEAFE' ?>;border:1px solid <?= $ann['announcement_type'] === 'lab_result' ? '#10B981' : '#2563eb' ?>;">
                        <p class="text-sm text-gray-600 mb-3">Your Response:</p>
                        <form method="POST" class="flex gap-2">
                            <input type="hidden" name="announcement_id" value="<?= $ann['id'] ?>">
                            <button type="submit" name="respond_to_announcement" value="accepted" 
                                    class="btn btn-success flex-1 <?= $ann['user_status'] === 'accepted' ? 'ring-2 ring-green-400' : '' ?>">
                                <i class="fas fa-check"></i> Accept
                            </button>
                            <button type="submit" name="respond_to_announcement" value="dismissed"
                                    class="btn flex-1 <?= $ann['user_status'] === 'dismissed' ? 'ring-2 ring-red-400' : '' ?>" style="background: #f0f0f0; color: #666;">
                                <i class="fas fa-times"></i> Dismiss
                            </button>
                            <button type="button" onclick="openMessagesModal(<?= $ann['id'] ?>, '<?= htmlspecialchars(addslashes($ann['title'])) ?>')" 
                                    class="btn btn-primary">
                                <i class="fas fa-comments"></i> Messages
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Messages Modal -->
    <div id="messagesModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="messagesTitle">Messages</h3>
                <button onclick="closeMessagesModal()" class="text-white hover:opacity-80">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="message-container" id="messagesContainer">
                    <div class="text-center text-gray-500 py-4">
                        <i class="fas fa-spinner fa-spin"></i> Loading messages...
                    </div>
                </div>

                <div class="message-input-area">
                    <form id="messageForm">
                        <div class="form-group">
                            <label class="form-label">Send a Message</label>
                            <textarea id="messageInput" name="message" class="form-control" placeholder="Type your message..." maxlength="5000" required></textarea>
                            <div class="text-right text-sm text-gray-500 mt-2">
                                <span id="charCount">0</span>/5000
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success w-full">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentAnnouncementId = null;

        // Open messages modal
        function openMessagesModal(announcementId, title) {
            currentAnnouncementId = announcementId;
            document.getElementById('messagesTitle').textContent = 'Messages - ' + title;
            document.getElementById('messagesModal').classList.add('active');
            loadMessages();
        }

        // Close messages modal
        function closeMessagesModal() {
            document.getElementById('messagesModal').classList.remove('active');
            currentAnnouncementId = null;
        }

        // Load messages
        async function loadMessages() {
            if (!currentAnnouncementId) return;

            try {
                const response = await fetch(`/community-health-tracker/api/announcement_messages.php?announcement_id=${currentAnnouncementId}`);
                const data = await response.json();

                if (data.success) {
                    const container = document.getElementById('messagesContainer');
                    
                    if (data.messages.length === 0) {
                        container.innerHTML = '<div class="text-center text-gray-500 py-8">No messages yet. Be the first to comment!</div>';
                    } else {
                        container.innerHTML = data.messages.map(msg => `
                            <div class="message-item">
                                <div class="message-content">
                                    <div class="message-sender">
                                        <i class="fas fa-user-circle mr-1"></i>${msg.full_name}
                                        ${msg.is_edited ? '<span class="message-edited">(edited)</span>' : ''}
                                    </div>
                                    <div class="message-text">${msg.message}</div>
                                    <div class="message-time">${new Date(msg.created_at).toLocaleString()}</div>
                                    ${msg.is_own_message ? `
                                        <div class="message-actions">
                                            <button type="button" onclick="editMessage(${msg.id}, '${msg.message}')" class="message-btn">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button type="button" onclick="deleteMessage(${msg.id})" class="message-btn" style="color: #e74c3c;">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        `).join('');
                    }

                    // Auto-scroll to bottom
                    container.scrollTop = container.scrollHeight;
                }
            } catch (error) {
                console.error('Error loading messages:', error);
                document.getElementById('messagesContainer').innerHTML = '<div class="text-red-500">Error loading messages</div>';
            }
        }

        // Send message
        document.getElementById('messageForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();

            const message = document.getElementById('messageInput').value.trim();
            if (!message || !currentAnnouncementId) return;

            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('announcement_id', currentAnnouncementId);
            formData.append('message', message);

            try {
                const response = await fetch('/community-health-tracker/api/announcement_messages.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('messageInput').value = '';
                    document.getElementById('charCount').textContent = '0';
                    loadMessages();
                } else {
                    alert('Error sending message: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error sending message');
            }
        });

        // Edit message
        async function editMessage(messageId, currentText) {
            const newText = prompt('Edit message:', currentText);
            if (newText === null || newText.trim() === '') return;

            const formData = new FormData();
            formData.append('action', 'edit_message');
            formData.append('message_id', messageId);
            formData.append('message', newText);

            try {
                const response = await fetch('/community-health-tracker/api/announcement_messages.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    loadMessages();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error editing message');
            }
        }

        // Delete message
        async function deleteMessage(messageId) {
            if (!confirm('Delete this message?')) return;

            const formData = new FormData();
            formData.append('action', 'delete_message');
            formData.append('message_id', messageId);

            try {
                const response = await fetch('/community-health-tracker/api/announcement_messages.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    loadMessages();
                } else {
                    alert('Error: ' + data.error);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error deleting message');
            }
        }

        // Character counter
        document.getElementById('messageInput')?.addEventListener('input', function() {
            document.getElementById('charCount').textContent = this.value.length;
        });

        // Close modal when clicking outside
        document.getElementById('messagesModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeMessagesModal();
            }
        });

        // Auto-refresh messages every 3 seconds
        setInterval(() => {
            if (currentAnnouncementId) {
                loadMessages();
            }
        }, 3000);
    </script>
</body>
</html>
