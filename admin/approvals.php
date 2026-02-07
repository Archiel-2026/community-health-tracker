<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/header.php';

redirectIfNotLoggedIn();
if (!isAdmin()) {
    header('Location: /community-health-tracker/');
    exit();
}

global $pdo;

// Get filter status
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'pending';

// Fetch users based on filter
try {
    if ($filter === 'pending') {
        $stmt = $pdo->prepare("SELECT * FROM sitio1_users WHERE status = 'pending' ORDER BY created_at DESC");
    } elseif ($filter === 'approved') {
        $stmt = $pdo->prepare("SELECT * FROM sitio1_users WHERE status = 'approved' ORDER BY created_at DESC");
    } elseif ($filter === 'declined') {
        $stmt = $pdo->prepare("SELECT * FROM sitio1_users WHERE status = 'declined' ORDER BY created_at DESC");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM sitio1_users ORDER BY created_at DESC");
    }
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error_message'] = "Unable to fetch users. Please try again later.";
    $users = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Approvals - Community Health Tracker</title>
    <!-- Tailwind CSS - Offline Local Build -->
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/tailwind.css">
    <!-- Local Font Awesome for offline support -->
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/font-awesome.min.css">
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/admin-styles.css">
    <style>
        * { font-family: 'Poppins', sans-serif !important; }
        .stat-card { position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; }
        .stat-card-yellow::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
        .stat-card-green::before { background: linear-gradient(90deg, #10b981, #34d399); }
        .stat-card-red::before { background: linear-gradient(90deg, #ef4444, #f87171); }
        .stat-card-blue::before { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
        .user-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            margin-bottom: 16px;
        }
        .user-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <div class="container mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-header-title">
                <div class="page-header-icon bg-blue-100 text-blue-600">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div>
                    <h1>User Approvals</h1>
                    <p>Review and approve user registrations</p>
                </div>
            </div>
            <a href="/community-health-tracker/admin/dashboard.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= $_SESSION['error_message'] ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= $_SESSION['success_message'] ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="stats-grid" style="grid-template-columns: repeat(4, 1fr);">
            <div class="stat-card stat-card-yellow">
                <div class="stat-card-icon bg-yellow-100">
                    <i class="fas fa-clock text-yellow-600 text-xl"></i>
                </div>
                <div class="stat-card-value text-yellow-600">
                    <?php 
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM sitio1_users WHERE status = 'pending'");
                        echo $stmt->fetchColumn();
                    } catch (PDOException $e) {
                        echo "0";
                    }
                    ?>
                </div>
                <div class="stat-card-label">Pending Approvals</div>
            </div>
            
            <div class="stat-card stat-card-green">
                <div class="stat-card-icon bg-green-100">
                    <i class="fas fa-user-check text-green-600 text-xl"></i>
                </div>
                <div class="stat-card-value text-green-600">
                    <?php 
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM sitio1_users WHERE status = 'approved'");
                        echo $stmt->fetchColumn();
                    } catch (PDOException $e) {
                        echo "0";
                    }
                    ?>
                </div>
                <div class="stat-card-label">Approved Users</div>
            </div>
            
            <div class="stat-card stat-card-red">
                <div class="stat-card-icon bg-red-100">
                    <i class="fas fa-user-times text-red-600 text-xl"></i>
                </div>
                <div class="stat-card-value text-red-600">
                    <?php 
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM sitio1_users WHERE status = 'declined'");
                        echo $stmt->fetchColumn();
                    } catch (PDOException $e) {
                        echo "0";
                    }
                    ?>
                </div>
                <div class="stat-card-label">Declined Users</div>
            </div>
            
            <div class="stat-card stat-card-blue">
                <div class="stat-card-icon bg-blue-100">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
                <div class="stat-card-value text-blue-600">
                    <?php 
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM sitio1_users");
                        echo $stmt->fetchColumn();
                    } catch (PDOException $e) {
                        echo "0";
                    }
                    ?>
                </div>
                <div class="stat-card-label">Total Users</div>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="main-container p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Filter Users</h2>
            <div class="tabs-container">
                <a href="?filter=pending" class="tab-btn <?= $filter === 'pending' ? 'active' : '' ?>">
                    <i class="fas fa-clock mr-2"></i> Pending
                </a>
                <a href="?filter=approved" class="tab-btn <?= $filter === 'approved' ? 'active' : '' ?>">
                    <i class="fas fa-user-check mr-2"></i> Approved
                </a>
                <a href="?filter=declined" class="tab-btn <?= $filter === 'declined' ? 'active' : '' ?>">
                    <i class="fas fa-user-times mr-2"></i> Declined
                </a>
                <a href="?filter=all" class="tab-btn <?= $filter === 'all' ? 'active' : '' ?>">
                    <i class="fas fa-users mr-2"></i> All
                </a>
            </div>
        </div>
        
        <!-- Users List -->
        <div class="main-container p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-lg font-semibold text-gray-800">
                    <?php if ($filter === 'pending'): ?>
                        <i class="fas fa-clock text-yellow-500 mr-2"></i> Pending Approvals
                    <?php elseif ($filter === 'approved'): ?>
                        <i class="fas fa-user-check text-green-500 mr-2"></i> Approved Users
                    <?php elseif ($filter === 'declined'): ?>
                        <i class="fas fa-user-times text-red-500 mr-2"></i> Declined Users
                    <?php else: ?>
                        <i class="fas fa-users text-blue-500 mr-2"></i> All Users
                    <?php endif; ?>
                </h2>
                <div class="badge badge-blue"><?= count($users) ?> user(s) found</div>
            </div>
            
            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>No users found</h3>
                    <p>There are no users matching your current filter.</p>
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($users as $user): ?>
                        <div class="user-card">
                            <div class="flex flex-col md:flex-row md:items-center justify-between">
                                <div class="flex items-start mb-4 md:mb-0">
                                    <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mr-4">
                                        <?php if (!empty($user['profile_image'])): ?>
                                            <img src="<?= htmlspecialchars($user['profile_image']) ?>" alt="Profile" class="w-12 h-12 rounded-full object-cover">
                                        <?php else: ?>
                                            <i class="fas fa-user text-gray-400"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-lg text-gray-800"><?= htmlspecialchars($user['full_name']) ?></h3>
                                        <p class="text-gray-500"><?= htmlspecialchars($user['email']) ?></p>
                                        <div class="flex flex-wrap gap-2 mt-2">
                                            <?php if ($user['status'] === 'pending'): ?>
                                                <span class="badge badge-yellow">Pending</span>
                                            <?php elseif ($user['status'] === 'approved'): ?>
                                                <span class="badge badge-green">Approved</span>
                                            <?php else: ?>
                                                <span class="badge badge-red">Declined</span>
                                            <?php endif; ?>
                                            <?php if ($user['id_verified']): ?>
                                                <span class="badge badge-cyan">
                                                    <i class="fas fa-id-card mr-1"></i> ID Verified
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex flex-col md:items-end">
                                    <p class="text-gray-500 text-sm mb-2">Registered: <?= date('M j, Y', strtotime($user['created_at'])) ?></p>
                                    
                                    <?php if ($user['status'] === 'approved' && !empty($user['verified_at'])): ?>
                                        <p class="text-green-600 text-sm font-medium">
                                            <i class="fas fa-check-circle mr-1"></i> Approved on <?= date('M j, Y', strtotime($user['verified_at'])) ?>
                                        </p>
                                    <?php elseif ($user['status'] === 'declined'): ?>
                                        <p class="text-red-600 text-sm font-medium">
                                            <i class="fas fa-times-circle mr-1"></i> Declined
                                        </p>
                                    <?php else: ?>
                                        <p class="text-yellow-600 text-sm font-medium">
                                            <i class="fas fa-clock mr-1"></i> Awaiting Approval
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- User Details -->
                            <div class="mt-4 pt-4" style="border-top: 1px solid #e5e7eb;">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <div>
                                        <p class="text-xs text-gray-400 uppercase tracking-wide">Contact</p>
                                        <p class="font-medium text-gray-700"><?= !empty($user['contact']) ? htmlspecialchars($user['contact']) : 'Not provided' ?></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-400 uppercase tracking-wide">Address</p>
                                        <p class="font-medium text-gray-700"><?= !empty($user['address']) ? htmlspecialchars($user['address']) : 'Not provided' ?></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-400 uppercase tracking-wide">Sitio</p>
                                        <p class="font-medium text-gray-700"><?= !empty($user['sitio']) ? htmlspecialchars($user['sitio']) : 'Not provided' ?></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-400 uppercase tracking-wide">Verification Method</p>
                                        <p class="font-medium text-gray-700"><?= str_replace('_', ' ', ucfirst($user['verification_method'])) ?></p>
                                    </div>
                                </div>
                                
                                <?php if (!empty($user['id_image_path'])): ?>
                                    <div class="mt-4">
                                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-2">ID Document</p>
                                        <a href="<?= htmlspecialchars($user['id_image_path']) ?>" target="_blank" class="inline-flex items-center text-blue-600 hover:underline text-sm">
                                            <i class="fas fa-external-link-alt mr-1"></i> View ID Document
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($user['verification_notes'])): ?>
                                    <div class="mt-4">
                                        <p class="text-xs text-gray-400 uppercase tracking-wide mb-2">Verification Notes</p>
                                        <p class="text-gray-700 bg-gray-50 p-3 rounded-lg text-sm"><?= htmlspecialchars($user['verification_notes']) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>

<?php
// require_once __DIR__ . '/../includes/footer.php';
?>