<?php
// staff/announcements_companyx.php
require_once __DIR__ . '/../includes/auth.php';
redirectIfNotLoggedIn();
if (!isStaff()) {
    header('Location: /community-health-tracker/');
    exit();
}

global $pdo;
$staffId = $_SESSION['user']['id'];
$error = '';
$success = '';

// Handle Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    $priority = $_POST['priority'] ?? 'normal';
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    $audience_type = $_POST['audience_type'] ?? 'public';
    $announcement_category = $_POST['announcement_category'] ?? 'basic';
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/announcements/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array(strtolower($file_extension), $allowed_ext)) {
            $file_name = uniqid() . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
                $image_path = '/community-health-tracker/uploads/announcements/' . $file_name;
            }
        }
    }
    if ($title && $message) {
        try {
            $stmt = $pdo->prepare("INSERT INTO sitio1_announcements (staff_id, title, message, priority, expiry_date, status, audience_type, announcement_category, image_path, post_date) VALUES (?, ?, ?, ?, ?, 'active', ?, ?, ?, NOW())");
            $stmt->execute([$staffId, $title, $message, $priority, $expiry_date, $audience_type, $announcement_category, $image_path]);
            $success = 'Announcement posted!';
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    } else {
        $error = 'Title and message required.';
    }
}

// Handle Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = $_POST['id'];
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    $priority = $_POST['priority'];
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
    try {
        $stmt = $pdo->prepare("UPDATE sitio1_announcements SET title=?, message=?, priority=?, expiry_date=? WHERE id=? AND staff_id=?");
        $stmt->execute([$title, $message, $priority, $expiry_date, $id, $staffId]);
        $success = 'Announcement updated!';
    } catch (PDOException $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = $_POST['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM sitio1_announcements WHERE id=? AND staff_id=?");
        $stmt->execute([$id, $staffId]);
        $success = 'Announcement deleted!';
    } catch (PDOException $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Fetch Announcements
$announcements = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM sitio1_announcements WHERE staff_id=? ORDER BY post_date DESC");
    $stmt->execute([$staffId]);
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Error: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CompanyX Announcements</title>
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/tailwind.css">
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/font-awesome.min.css">
    <style>
    body{font-family:sans-serif;background:#f4f6f8;}
    .card{background:#fff;border-radius:8px;box-shadow:0 2px 8px #0001;padding:2rem;margin-bottom:2rem;}
    .btn{padding:0.5rem 1.2rem;border-radius:5px;border:none;cursor:pointer;}
    .btn-primary{background:#2563eb;color:#fff;}
    .btn-danger{background:#dc2626;color:#fff;}
    .btn-secondary{background:#64748b;color:#fff;}
    .modal {
        display: none;
        position: fixed;
        top: 0; left: 0; width: 100vw; height: 100vh;
        background: none !important;
        align-items: center; justify-content: center;
        z-index: 999999 !important;
        pointer-events: all;
    }
    .modal.active {
        display: flex !important;
    }
    .modal-content {
        background: #fff !important;
        opacity: 1 !important;
        padding: 2rem;
        border-radius: 8px;
        min-width: 320px;
        max-width: 95vw;
        box-shadow: 0 8px 32px rgba(0,0,0,0.25);
        z-index: 1000000 !important;
        color: #222;
        position: relative;
        margin: auto;
        display: block;
    }
    .input{width:100%;padding:0.5rem;border:1px solid #d1d5db;border-radius:4px;margin-bottom:1rem;}
    .announcement-list{margin-top:2rem;}
    .announcement-item{padding:1rem 0;border-bottom:1px solid #e5e7eb;}
    .announcement-item:last-child{border-bottom:none;}
    .actions{display:flex;gap:0.5rem;}
    </style>
</head>
<body>
<div class="container mx-auto max-w-2xl py-8">
    <div class="card">
        <h2 class="text-xl font-bold mb-4">Post New Announcement (CompanyX)</h2>
        <?php if ($error): ?><div class="mb-2 text-red-600"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="mb-2 text-green-600"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create">
            <input class="input" type="text" name="title" placeholder="Title" maxlength="100" required>
            <textarea class="input" name="message" placeholder="Message" maxlength="500" required></textarea>
            <select class="input" name="priority">
                <option value="normal">Normal</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
            </select>
            <input class="input" type="date" name="expiry_date">
            <select class="input" name="audience_type">
                <option value="public">All Users</option>
                <option value="landing_page">Landing Page</option>
                <option value="specific">Specific Users</option>
            </select>
            <select class="input" name="announcement_category">
                <option value="basic">Basic</option>
                <option value="lab_result">Lab Result</option>
            </select>
            <input class="input" type="file" name="image" accept="image/*">
            <button class="btn btn-primary" type="submit">Post</button>
        </form>
    </div>
    <div class="card announcement-list">
        <h2 class="text-lg font-bold mb-4">Your Announcements</h2>
        <?php if (empty($announcements)): ?>
            <div class="text-gray-500">No announcements yet.</div>
        <?php else: ?>
            <?php foreach ($announcements as $a): ?>
                <div class="announcement-item" id="announcement-<?= $a['id'] ?>">
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="font-semibold text-blue-900"><?= htmlspecialchars($a['title']) ?></div>
                            <div class="text-gray-600 text-sm"><?= date('M d, Y', strtotime($a['post_date'])) ?></div>
                        </div>
                        <div class="actions">
                            <button class="btn btn-secondary" onclick='openViewModal(<?= json_encode($a) ?>)'><i class="fas fa-eye"></i></button>
                            <button class="btn btn-primary" onclick='openEditModal(<?= json_encode($a) ?>)'><i class="fas fa-edit"></i></button>
                            <button class="btn btn-danger" onclick='openDeleteModal(<?= $a['id'] ?>)'><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<!-- View Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content" style="max-width:500px;">
        <h3 class="text-lg font-bold mb-2">Announcement Details</h3>
        <div id="viewContent"></div>
        <button class="btn btn-secondary mt-4" onclick="closeViewModal()">Close</button>
    </div>
</div>
<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content" style="max-width:500px;">
        <h3 class="text-lg font-bold mb-2">Edit Announcement</h3>
        <form id="editForm" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <input class="input" type="text" name="title" id="edit-title" required maxlength="100">
            <textarea class="input" name="message" id="edit-message" required maxlength="500"></textarea>
            <select class="input" name="priority" id="edit-priority">
                <option value="normal">Normal</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
            </select>
            <input class="input" type="date" name="expiry_date" id="edit-expiry">
            <button class="btn btn-primary mt-2" type="submit">Save</button>
            <button class="btn btn-secondary mt-2" type="button" onclick="closeEditModal()">Cancel</button>
        </form>
    </div>
</div>
<!-- Delete Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content" style="max-width:400px;">
        <h3 class="text-lg font-bold mb-2 text-red-700">Delete Announcement</h3>
        <div id="deleteContent" class="mb-4">Are you sure you want to delete this announcement?</div>
        <form id="deleteForm" method="POST">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="delete-id">
            <button class="btn btn-danger" type="submit">Delete</button>
            <button class="btn btn-secondary ml-2" type="button" onclick="closeDeleteModal()">Cancel</button>
        </form>
    </div>
</div>
<script>
// View Modal
function openViewModal(a) {
    console.log('openViewModal called', a);
    let html = `<div><b>Title:</b> ${a.title}<br><b>Message:</b> ${a.message}<br><b>Priority:</b> ${a.priority}<br><b>Category:</b> ${a.announcement_category}<br><b>Audience:</b> ${a.audience_type}<br><b>Expiry:</b> ${a.expiry_date||'-'}<br><b>Date:</b> ${a.post_date}`;
    if(a.image_path) html += `<br><img src='${a.image_path}' style='max-width:100%;margin-top:8px;'>`;
    html += `</div>`;
    document.getElementById('viewContent').innerHTML = html;
    document.getElementById('viewModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeViewModal() {
    console.log('closeViewModal called');
    document.getElementById('viewModal').classList.remove('active');
    document.body.style.overflow = 'auto';
}
// Edit Modal
function openEditModal(a) {
    console.log('openEditModal called', a);
    document.getElementById('edit-id').value = a.id;
    document.getElementById('edit-title').value = a.title;
    document.getElementById('edit-message').value = a.message;
    document.getElementById('edit-priority').value = a.priority;
    document.getElementById('edit-expiry').value = a.expiry_date||'';
    document.getElementById('editModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeEditModal() {
    console.log('closeEditModal called');
    document.getElementById('editModal').classList.remove('active');
    document.body.style.overflow = 'auto';
}
// Delete Modal
function openDeleteModal(id) {
    console.log('openDeleteModal called', id);
    document.getElementById('delete-id').value = id;
    document.getElementById('deleteModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeDeleteModal() {
    console.log('closeDeleteModal called');
    document.getElementById('deleteModal').classList.remove('active');
    document.body.style.overflow = 'auto';
}
// AJAX Edit
document.getElementById('editForm').onsubmit = function(e) {
    e.preventDefault();
    const form = e.target;
    const data = new FormData(form);
    fetch('', {method:'POST', body:data})
        .then(r=>r.text())
        .then(()=>{closeEditModal();window.location.reload();});
    return false;
};
// AJAX Delete
document.getElementById('deleteForm').onsubmit = function(e) {
    e.preventDefault();
    const form = e.target;
    const data = new FormData(form);
    fetch('', {method:'POST', body:data})
        .then(r=>r.text())
        .then(()=>{closeDeleteModal();window.location.reload();});
    return false;
};
// Modal close on background click
document.querySelectorAll('.modal').forEach(m=>{
    m.addEventListener('click',function(e){if(e.target===this){this.classList.remove('active');document.body.style.overflow='auto';}});
});
// Modal close on Escape
document.addEventListener('keydown',function(e){if(e.key==='Escape'){closeViewModal();closeEditModal();closeDeleteModal();}});
</script>
</body>
</html>
