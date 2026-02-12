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
$patient_type = isset($_GET['type']) ? $_GET['type'] : 'all';

// Pagination settings
$records_per_page = 5;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $records_per_page;

// Build base query conditions
$where_conditions = ["p.deleted_at IS NULL"];
$params = [];
$count_params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.full_name LIKE ? OR p.disease LIKE ? OR p.sitio LIKE ? OR u.username LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $count_params = array_merge($count_params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if ($patient_type === 'registered') {
    $where_conditions[] = "p.user_id IS NOT NULL";
} elseif ($patient_type === 'regular') {
    $where_conditions[] = "p.user_id IS NULL";
}

if ($filter === 'recent') {
    $where_conditions[] = "p.last_checkup >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
} elseif ($filter === 'no_checkup') {
    $where_conditions[] = "(p.last_checkup IS NULL OR p.last_checkup = '')";
}

// Build WHERE clause
$where_clause = implode(" AND ", $where_conditions);

// Count query
$count_query = "SELECT COUNT(*) as total 
                FROM sitio1_patients p 
                LEFT JOIN sitio1_users u ON p.user_id = u.id 
                WHERE $where_clause";

// Main query
$query = "SELECT p.*, u.username as user_username, u.email as user_email, u.approved as user_approved,
                 CASE 
                     WHEN p.user_id IS NOT NULL THEN 'registered_user'
                     ELSE 'regular_patient'
                 END as patient_type
          FROM sitio1_patients p 
          LEFT JOIN sitio1_users u ON p.user_id = u.id 
          WHERE $where_clause
          ORDER BY p.created_at DESC 
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

// Get patients for current page
try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Main Query Error: " . $e->getMessage());
    $_SESSION['error_message'] = "Unable to fetch patients. Please try again later.";
    $patients = [];
}

// Get stats for dashboard
$stats = [
    'total_patients' => 0,
    'registered_users' => 0,
    'regular_patients' => 0,
    'recent_checkups' => 0,
    'no_checkup' => 0
];

try {
    // Total patients
    $stmt = $pdo->query("SELECT COUNT(*) FROM sitio1_patients WHERE deleted_at IS NULL");
    $stats['total_patients'] = $stmt->fetchColumn();
    
    // Registered users (patients with user accounts)
    $stmt = $pdo->query("SELECT COUNT(*) FROM sitio1_patients WHERE user_id IS NOT NULL AND deleted_at IS NULL");
    $stats['registered_users'] = $stmt->fetchColumn();
    
    // Regular patients (without user accounts)
    $stmt = $pdo->query("SELECT COUNT(*) FROM sitio1_patients WHERE user_id IS NULL AND deleted_at IS NULL");
    $stats['regular_patients'] = $stmt->fetchColumn();
    
    // Recent checkups
    $stmt = $pdo->query("SELECT COUNT(*) FROM sitio1_patients WHERE last_checkup >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND deleted_at IS NULL");
    $stats['recent_checkups'] = $stmt->fetchColumn();
    
    // No checkup
    $stmt = $pdo->query("SELECT COUNT(*) FROM sitio1_patients WHERE (last_checkup IS NULL OR last_checkup = '') AND deleted_at IS NULL");
    $stats['no_checkup'] = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Stats Query Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Management - Community Health Tracker</title>
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
        .stat-card-purple::before { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }
        .stat-card-cyan::before { background: linear-gradient(90deg, #06b6d4, #22d3ee); }
        .stat-card-amber::before { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
    </style>
</head>
<body class="bg-gray-50">
    
    <div class="container mx-auto px-4 py-8">
        <!-- Page Header -->
        <div class="page-header">
            <div class="page-header-title">
                <div class="page-header-icon bg-purple-100 text-purple-600">
                    <i class="fas fa-hospital-user"></i>
                </div>
                <div>
                    <h1>Patient Management</h1>
                    <p>View and manage all patient records</p>
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
        <div class="stats-grid" style="grid-template-columns: repeat(5, 1fr);">
            <div class="stat-card stat-card-blue">
                <div class="stat-card-icon bg-blue-100">
                    <i class="fas fa-procedures text-blue-600 text-xl"></i>
                </div>
                <div class="stat-card-value text-blue-600"><?= $stats['total_patients'] ?></div>
                <div class="stat-card-label">Total Patients</div>
            </div>
            
            <div class="stat-card stat-card-green">
                <div class="stat-card-icon bg-green-100">
                    <i class="fas fa-user-check text-green-600 text-xl"></i>
                </div>
                <div class="stat-card-value text-green-600"><?= $stats['registered_users'] ?></div>
                <div class="stat-card-label">Registered Users</div>
            </div>
            
            <div class="stat-card stat-card-purple">
                <div class="stat-card-icon bg-purple-100">
                    <i class="fas fa-user-injured text-purple-600 text-xl"></i>
                </div>
                <div class="stat-card-value text-purple-600"><?= $stats['regular_patients'] ?></div>
                <div class="stat-card-label">Regular Patients</div>
            </div>
            
            <div class="stat-card stat-card-cyan">
                <div class="stat-card-icon bg-cyan-100">
                    <i class="fas fa-calendar-check text-cyan-600 text-xl"></i>
                </div>
                <div class="stat-card-value text-cyan-600"><?= $stats['recent_checkups'] ?></div>
                <div class="stat-card-label">Recent Checkups</div>
            </div>
            
            <div class="stat-card stat-card-amber">
                <div class="stat-card-icon bg-amber-100">
                    <i class="fas fa-clock text-amber-600 text-xl"></i>
                </div>
                <div class="stat-card-value text-amber-600"><?= $stats['no_checkup'] ?></div>
                <div class="stat-card-label">Due for Checkup</div>
            </div>
        </div>
        
        <!-- Search and Filter Section -->
        <div class="main-container p-6 mb-6">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <h2 class="text-lg font-semibold text-gray-800">Filter Patients</h2>
                
                <div class="flex flex-col sm:flex-row gap-4 flex-wrap">
                    <!-- Search Form -->
                    <form method="GET" class="flex">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                               placeholder="Search patients..." 
                               class="form-input rounded-r-none" style="border-radius: 8px 0 0 8px;">
                        <input type="hidden" name="type" value="<?= $patient_type ?>">
                        <input type="hidden" name="filter" value="<?= $filter ?>">
                        <button type="submit" class="btn btn-primary-solid" style="border-radius: 0 8px 8px 0;">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                    
                    <!-- Tabs -->
                    <div class="tabs-container">
                        <a href="?type=all&filter=<?= $filter ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                           class="tab-btn <?= $patient_type === 'all' ? 'active' : '' ?>">All</a>
                        <a href="?type=registered&filter=<?= $filter ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                           class="tab-btn <?= $patient_type === 'registered' ? 'active' : '' ?>">Registered</a>
                        <a href="?type=regular&filter=<?= $filter ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                           class="tab-btn <?= $patient_type === 'regular' ? 'active' : '' ?>">Regular</a>
                    </div>
                    
                    <!-- Checkup Filter -->
                    <div class="tabs-container">
                        <a href="?type=<?= $patient_type ?>&filter=all<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                           class="tab-btn <?= $filter === 'all' ? 'active' : '' ?>">All</a>
                        <a href="?type=<?= $patient_type ?>&filter=recent<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                           class="tab-btn <?= $filter === 'recent' ? 'active' : '' ?>">Recent</a>
                        <a href="?type=<?= $patient_type ?>&filter=no_checkup<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                           class="tab-btn <?= $filter === 'no_checkup' ? 'active' : '' ?>">Due</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Patients List -->
        <div class="main-container p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-lg font-semibold text-gray-800">
                    <?php 
                    if ($patient_type === 'registered') {
                        echo "Registered User Patients";
                    } elseif ($patient_type === 'regular') {
                        echo "Regular Patients";
                    } else {
                        echo "All Patients";
                    }
                    
                    if ($filter === 'recent') {
                        echo " with Recent Checkups";
                    } elseif ($filter === 'no_checkup') {
                        echo " Due for Checkup";
                    }
                    ?>
                </h2>
                <div class="badge badge-blue">
                    Showing <?= count($patients) ?> of <?= $total_records ?> patient(s)
                </div>
            </div>
            
            <?php if (empty($patients)): ?>
                <div class="empty-state">
                    <i class="fas fa-procedures"></i>
                    <h3>No patients found</h3>
                    <p>There are no patients matching your current filter.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Patient Info</th>
                                <th>Type</th>
                                <th>Sitio</th>
                                <th>Condition</th>
                                <th>Last Checkup</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($patients as $patient): ?>
                                <tr>
                                    <td>
                                        <div class="font-medium text-gray-800"><?= htmlspecialchars($patient['full_name']) ?></div>
                                        <div class="text-sm text-gray-500">
                                            <?= $patient['age'] ?? 'N/A' ?> • <?= ucfirst($patient['gender'] ?? 'Not specified') ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?= !empty($patient['contact']) ? htmlspecialchars($patient['contact']) : 'No contact' ?>
                                        </div>
                                        <?php if ($patient['patient_type'] === 'registered_user' && !empty($patient['user_username'])): ?>
                                            <div class="text-xs text-green-600 mt-1">
                                                <i class="fas fa-user-check mr-1"></i>
                                                User: <?= htmlspecialchars($patient['user_username']) ?>
                                                <?php if ($patient['user_approved']): ?>
                                                    <span class="text-green-500">(Approved)</span>
                                                <?php else: ?>
                                                    <span class="text-yellow-500">(Pending)</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($patient['patient_type'] === 'registered_user'): ?>
                                            <span class="badge badge-green">
                                                <i class="fas fa-user-check mr-1"></i> Registered
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-purple">
                                                <i class="fas fa-user-injured mr-1"></i> Regular
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= !empty($patient['sitio']) ? htmlspecialchars($patient['sitio']) : 'Not specified' ?></td>
                                    <td>
                                        <?php if (!empty($patient['disease'])): ?>
                                            <span class="badge badge-red"><?= htmlspecialchars($patient['disease']) ?></span>
                                        <?php else: ?>
                                            <span class="text-gray-500">No condition</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($patient['last_checkup']): ?>
                                            <div class="text-green-600 font-medium">
                                                <?= date('M j, Y', strtotime($patient['last_checkup'])) ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-yellow-600 font-medium">No checkup</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($patient['last_checkup'] && strtotime($patient['last_checkup']) >= strtotime('-30 days')): ?>
                                            <span class="badge badge-green">
                                                <i class="fas fa-check-circle mr-1"></i> Recent
                                            </span>
                                        <?php elseif (empty($patient['last_checkup'])): ?>
                                            <span class="badge badge-yellow">
                                                <i class="fas fa-clock mr-1"></i> Due
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-gray">
                                                <i class="fas fa-calendar mr-1"></i> Scheduled
                                            </span>
                                        <?php endif; ?>
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
                            <a href="?page=<?= $current_page - 1 ?>&type=<?= $patient_type ?>&filter=<?= $filter ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
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
                                echo '<a href="?page=1&type=' . $patient_type . '&filter=' . $filter . (!empty($search) ? '&search=' . urlencode($search) : '') . '" 
                                      class="w-10 h-10 flex items-center justify-center bg-gray-200 text-gray-700 rounded-full hover:bg-gray-300 transition-colors">1</a>';
                                if ($start_page > 2) {
                                    echo '<span class="w-10 h-10 flex items-center justify-center text-gray-500">...</span>';
                                }
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++): 
                            ?>
                                <a href="?page=<?= $i ?>&type=<?= $patient_type ?>&filter=<?= $filter ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
                                   class="w-10 h-10 flex items-center justify-center rounded-full transition-colors <?= $i == $current_page ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                                    <?= $i ?>
                                </a>
                            <?php endfor; 
                            
                            // Show last page if not in range
                            if ($end_page < $total_pages) {
                                if ($end_page < $total_pages - 1) {
                                    echo '<span class="w-10 h-10 flex items-center justify-center text-gray-500">...</span>';
                                }
                                echo '<a href="?page=' . $total_pages . '&type=' . $patient_type . '&filter=' . $filter . (!empty($search) ? '&search=' . urlencode($search) : '') . '" 
                                      class="w-10 h-10 flex items-center justify-center bg-gray-200 text-gray-700 rounded-full hover:bg-gray-300 transition-colors">' . $total_pages . '</a>';
                            }
                            ?>
                        </div>

                        <!-- Next Button -->
                        <?php if ($current_page < $total_pages): ?>
                            <a href="?page=<?= $current_page + 1 ?>&type=<?= $patient_type ?>&filter=<?= $filter ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" 
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