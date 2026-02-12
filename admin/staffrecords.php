<?php
require_once __DIR__ . '/../includes/auth.php';
// --- Auto-logout for resident users after 10 minutes of inactivity ---
if (isUser()) {
    $now = time();
    if (!isset($_SESSION['last_action'])) {
        $_SESSION['last_action'] = $now;
    } else {
        $inactive = $now - $_SESSION['last_action'];
        if ($inactive >= 600) { // 10 minutes = 600 seconds
            // Destroy session and redirect to resident landing page
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

redirectIfNotLoggedIn();
if (!isAdmin()) {
    header('Location: /community-health-tracker/');
    exit();
}

global $pdo;

// Get filter parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination settings
$records_per_page = 5;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $records_per_page;

// Build base query conditions
$where_conditions = ["1=1"];
$params = [];
$count_params = [];

if (!empty($search)) {
    $where_conditions[] = "(username LIKE ? OR full_name LIKE ? OR position LIKE ? OR specialization LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $count_params = array_merge($count_params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if ($filter === 'active') {
    $where_conditions[] = "status = 'active' AND is_active = 1";
} elseif ($filter === 'inactive') {
    $where_conditions[] = "(status = 'inactive' OR is_active = 0)";
}

// Build WHERE clause
$where_clause = implode(" AND ", $where_conditions);

// Count query
$count_query = "SELECT COUNT(*) as total FROM sitio1_staff WHERE $where_clause";

// Main query with creator information
$query = "SELECT s.*, u.username as created_by_username 
          FROM sitio1_staff s 
          LEFT JOIN sitio1_users u ON s.created_by = u.id 
          WHERE $where_clause 
          ORDER BY s.created_at DESC 
          LIMIT $records_per_page OFFSET $offset";

// Get total count for pagination
try {
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($count_params);
    $total_records = $stmt->fetchColumn();
    $total_pages = ceil($total_records / $records_per_page);
} catch (PDOException $e) {
    error_log("Count Query Error: " . $e->getMessage());
    $total_records = 0;
    $total_pages = 1;
}

// Get staff for current page
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Main Query Error: " . $e->getMessage());
    $_SESSION['error_message'] = "Unable to fetch staff. Please try again later.";
    $staff = [];
}

// Get stats for dashboard
$stats = [
    'total_staff' => 0,
    'active_staff' => 0,
    'inactive_staff' => 0
];

try {
    // Total staff
    $stmt = $pdo->query("SELECT COUNT(*) FROM sitio1_staff");
    $stats['total_staff'] = $stmt->fetchColumn();
    
    // Active staff
    $stmt = $pdo->query("SELECT COUNT(*) FROM sitio1_staff WHERE status = 'active' AND is_active = 1");
    $stats['active_staff'] = $stmt->fetchColumn();
    
    // Inactive staff
    $stmt = $pdo->query("SELECT COUNT(*) FROM sitio1_staff WHERE status = 'inactive' OR is_active = 0");
    $stats['inactive_staff'] = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Stats Query Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - Community Health Tracker</title>
    <!-- Tailwind CSS - Offline Local Build -->
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/tailwind.css">
    <!-- Local Font Awesome for offline support -->
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/font-awesome.min.css">
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/admin-styles.css">
    <style>
        * { font-family: 'Poppins', sans-serif !important; }
        .stat-card { position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; }
        .stat-card-blue::before { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
        .stat-card-green::before { background: linear-gradient(90deg, #10b981, #34d399); }
        .stat-card-gray::before { background: linear-gradient(90deg, #6b7280, #9ca3af); }
    </style>
</head>
<body class="bg-gray-50">
    
    <div class="container mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-header-title">
                <div class="page-header-icon bg-blue-100 text-blue-600">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div>
                    <h1>Staff Management</h1>
                    <p>View and manage all staff members</p>
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
        
        <!-- Stats Cards -->
        <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
            <div class="stat-card stat-card-blue">
                <div class="stat-card-icon bg-blue-100">
                    <i class="fas fa-user-shield text-blue-600 text-xl"></i>
                </div>
                <div class="stat-card-value text-blue-600"><?= $stats['total_staff'] ?></div>
                <div class="stat-card-label">Total Staff</div>
            </div>
            
            <div class="stat-card stat-card-green">
                <div class="stat-card-icon bg-green-100">
                    <i class="fas fa-user-check text-green-600 text-xl"></i>
                </div>
                <div class="stat-card-value text-green-600"><?= $stats['active_staff'] ?></div>
                <div class="stat-card-label">Active Staff</div>
            </div>
            
            <div class="stat-card stat-card-gray">
                <div class="stat-card-icon bg-gray-100">
                    <i class="fas fa-user-times text-gray-600 text-xl"></i>
                </div>
                <div class="stat-card-value text-gray-600"><?= $stats['inactive_staff'] ?></div>
                <div class="stat-card-label">Inactive Staff</div>
            </div>
        </div>
        
        <!-- Search and Filter Section -->
        <div class="main-container p-6 mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <h2 class="text-lg font-semibold text-gray-800">Filter Staff</h2>
                
                <div class="flex flex-col sm:flex-row gap-4 flex-wrap">
                    <!-- Search Form -->
                    <form method="GET" class="flex">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Search staff..." 
                               class="form-input rounded-r-none" style="border-radius: 8px 0 0 8px;">
                        <input type="hidden" name="filter" value="<?= $filter ?>">
                        <button type="submit" class="btn btn-primary-solid" style="border-radius: 0 8px 8px 0;">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                    
                    <!-- Status Filter -->
                    <div class="tabs-container">
                        <a href="?filter=all<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                           class="tab-btn <?= $filter === 'all' ? 'active' : '' ?>">All</a>
                        <a href="?filter=active<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                           class="tab-btn <?= $filter === 'active' ? 'active' : '' ?>">Active</a>
                        <a href="?filter=inactive<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                           class="tab-btn <?= $filter === 'inactive' ? 'active' : '' ?>">Inactive</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Staff List -->
        <div class="main-container p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-lg font-semibold text-gray-800">
                    <?php 
                    if ($filter === 'active') {
                        echo "Active Staff Members";
                    } elseif ($filter === 'inactive') {
                        echo "Inactive Staff Members";
                    } else {
                        echo "All Staff Members";
                    }
                    ?>
                </h2>
                <div class="badge badge-blue">
                    Showing <?= count($staff) ?> of <?= $total_records ?> staff member(s)
                </div>
            </div>
            
            <?php if (empty($staff)): ?>
                <div class="empty-state">
                    <i class="fas fa-user-shield"></i>
                    <h3>No staff members found</h3>
                    <p>There are no staff members matching your current filter.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Staff Information</th>
                                <th>Position & Specialization</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staff as $staff_member): ?>
                                <tr>
                                    <td>
                                        <div class="font-medium text-gray-800"><?= htmlspecialchars($staff_member['full_name']) ?></div>
                                        <div class="text-sm text-gray-500">@<?= htmlspecialchars($staff_member['username']) ?></div>
                                        <div class="text-xs text-gray-400 mt-1">
                                            Created by: <?= !empty($staff_member['created_by_username']) ? htmlspecialchars($staff_member['created_by_username']) : 'System' ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="font-medium text-gray-800">
                                            <?= !empty($staff_member['position']) ? htmlspecialchars($staff_member['position']) : 'No position' ?>
                                        </div>
                                        <?php if (!empty($staff_member['specialization'])): ?>
                                            <div class="text-sm text-blue-600 mt-1">
                                                <i class="fas fa-stethoscope mr-1"></i>
                                                <?= htmlspecialchars($staff_member['specialization']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($staff_member['license_number'])): ?>
                                            <div class="text-xs text-green-600 mt-1">
                                                <i class="fas fa-id-card mr-1"></i>
                                                License: <?= htmlspecialchars($staff_member['license_number']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($staff_member['status'] === 'active' && $staff_member['is_active'] == 1): ?>
                                            <span class="badge badge-green">
                                                <i class="fas fa-check-circle mr-1"></i> Active
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-gray">
                                                <i class="fas fa-times-circle mr-1"></i> Inactive
                                            </span>
                                        <?php endif; ?>
                                        
                                        <div class="text-xs text-gray-500 mt-2">
                                            <?php if ($staff_member['is_active'] == 1): ?>
                                                <span class="text-green-600">✓ Account Active</span>
                                            <?php else: ?>
                                                <span class="text-red-600">✗ Account Disabled</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-sm text-gray-700">
                                            <?= date('M j, Y', strtotime($staff_member['created_at'])) ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            <?= date('g:i A', strtotime($staff_member['created_at'])) ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Circular Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="flex flex-col sm:flex-row justify-between items-center mt-6 pt-6 border-t border-gray-200 space-y-4 sm:space-y-0">
                    <div class="text-sm text-gray-600">
                        Page <?= $current_page ?> of <?= $total_pages ?>
                    </div>
                    <div class="flex items-center space-x-2">
                        <!-- Previous Button -->
                        <?php if ($current_page > 1): ?>
                            <a href="?page=<?= $current_page - 1 ?>&filter=<?= $filter ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                               class="w-10 h-10 flex items-center justify-center bg-gray-200 text-gray-700 rounded-full hover:bg-gray-300 transition-colors">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php else: ?>
                            <span class="w-10 h-10 flex items-center justify-center bg-gray-100 text-gray-400 rounded-full cursor-not-allowed">
                                <i class="fas fa-chevron-left"></i>
                            </span>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <div class="flex space-x-1">
                            <?php 
                            // Show page numbers
                            $start_page = max(1, $current_page - 2);
                            $end_page = min($total_pages, $current_page + 2);
                            
                            // Show first page if not in range
                            if ($start_page > 1) {
                                echo '<a href="?page=1&filter=' . $filter . (!empty($search) ? '&search=' . urlencode($search) : '') . '" 
                                      class="w-10 h-10 flex items-center justify-center bg-gray-200 text-gray-700 rounded-full hover:bg-gray-300 transition-colors">1</a>';
                                if ($start_page > 2) {
                                    echo '<span class="w-10 h-10 flex items-center justify-center text-gray-500">...</span>';
                                }
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++): 
                            ?>
                                <a href="?page=<?= $i ?>&filter=<?= $filter ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                                   class="w-10 h-10 flex items-center justify-center rounded-full transition-colors <?= $i == $current_page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; 
                            
                            // Show last page if not in range
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<span class="w-10 h-10 flex items-center justify-center text-gray-500">...</span>';
                                }
                                echo '<a href="?page=' . $total_pages . '&filter=' . $filter . (!empty($search) ? '&search=' . urlencode($search) : '') . '" 
                                      class="w-10 h-10 flex items-center justify-center bg-gray-200 text-gray-700 rounded-full hover:bg-gray-300 transition-colors">' . $total_pages . '</a>';
                            }
                            ?>
                        </div>

                        <!-- Next Button -->
                        <?php if ($current_page < $total_pages): ?>
                            <a href="?page=<?= $current_page + 1 ?>&filter=<?= $filter ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                               class="w-10 h-10 flex items-center justify-center bg-gray-200 text-gray-700 rounded-full hover:bg-gray-300 transition-colors">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="w-10 h-10 flex items-center justify-center bg-gray-100 text-gray-400 rounded-full cursor-not-allowed">
                                <i class="fas fa-chevron-right"></i>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>

<?php
// require_once __DIR__ . '/../includes/footer.php';
?>