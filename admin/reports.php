<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/header.php';

redirectIfNotLoggedIn();
if (!isAdmin()) {
    header('Location: /community-health-tracker/');
    exit();
}

global $pdo;

// Initialize variables with simple defaults
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$sitioFilter = $_GET['sitio'] ?? 'all';

// Get sitios for filter
$sitios = [];
$stmt = $pdo->query("SELECT DISTINCT sitio FROM sitio1_patients WHERE sitio IS NOT NULL AND sitio != '' ORDER BY sitio");
$sitios = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Reports - Community Health Tracker</title>
    <!-- Tailwind CSS - Offline Local Build -->
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/tailwind.css">
    <!-- Local Font Awesome for offline support -->
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/font-awesome.min.css">
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/admin-styles.css">
    <style>
        * { font-family: 'Poppins', sans-serif !important; }
        .export-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }
        .export-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }
        .export-card.excel { border-top: 4px solid #10b981; }
        .export-card.pdf { border-top: 4px solid #ef4444; }
        .summary-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 10px;
            padding: 16px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
    </style>
</head>
<body class="bg-gray-50">

<div class="container mx-auto px-4 py-8 max-w-6xl">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-title">
            <div class="page-header-icon bg-indigo-100 text-indigo-600">
                <i class="fas fa-file-export"></i>
            </div>
            <div>
                <h1>Export Reports</h1>
                <p>Generate and download reports in Excel or PDF format</p>
            </div>
        </div>
        <a href="/community-health-tracker/admin/dashboard.php" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <!-- Report Selection -->
    <div class="main-container p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Configure Report</h2>
        <form method="GET" action="" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Date Range -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Date Range</label>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">From</label>
                            <input type="date" name="start_date" value="<?= $startDate ?>"
                                   class="form-input">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">To</label>
                            <input type="date" name="end_date" value="<?= $endDate ?>"
                                   class="form-input">
                        </div>
                    </div>
                </div>

                <!-- Sitio Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Filter by Sitio</label>
                    <select name="sitio" class="form-input w-full">
                        <option value="all">All Sitios</option>
                        <?php foreach ($sitios as $sitio): ?>
                            <option value="<?= htmlspecialchars($sitio) ?>" <?= $sitioFilter === $sitio ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sitio) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Generate Button -->
            <div class="pt-2">
                <button type="submit" class="btn btn-primary-solid">
                    <i class="fas fa-filter mr-2"></i> Apply Filters
                </button>
            </div>
        </form>
    </div>

    <!-- Export Options -->
    <div class="main-container p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-2">Export Options</h2>
        <p class="text-gray-600 mb-6">Click on any format below to download the report</p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Excel Export -->
            <div class="export-card excel">
                <div class="flex items-center mb-4">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                        <i class="fas fa-file-excel text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Excel Spreadsheet</h3>
                        <p class="text-sm text-gray-500">Download data in .xlsx format</p>
                    </div>
                </div>
                <p class="text-gray-600 text-sm mb-4">Best for data analysis, charts, and sharing with colleagues</p>
                <a href="/community-health-tracker/api/export_patients_excel.php?start_date=<?= $startDate ?>&end_date=<?= $endDate ?>&sitio=<?= urlencode($sitioFilter) ?>"
                   class="btn btn-success w-full text-center">
                    <i class="fas fa-download mr-2"></i> Download Patient Excel
                </a>
            </div>

            <!-- PDF Export -->
            <div class="export-card pdf">
                <div class="flex items-center mb-4">
                    <div class="p-3 rounded-full bg-red-100 text-red-600 mr-4">
                        <i class="fas fa-file-pdf text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">PDF Document</h3>
                        <p class="text-sm text-gray-500">Generate professional PDF report</p>
                    </div>
                </div>
                <p class="text-gray-600 text-sm mb-4">Perfect for printing, archiving, and official records</p>
                <a href="/community-health-tracker/api/export_patients_pdf.php?start_date=<?= $startDate ?>&end_date=<?= $endDate ?>&sitio=<?= urlencode($sitioFilter) ?>"
                   class="btn btn-danger w-full text-center">
                    <i class="fas fa-download mr-2"></i> Download Patient PDF
                </a>
            </div>
        </div>

        <!-- Quick Stats Summary -->
        <div class="mt-8 pt-6" style="border-top: 1px solid #e5e7eb;">
            <h3 class="text-sm font-medium text-gray-700 mb-4">Report Summary</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="summary-card">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Date Range</p>
                    <p class="text-lg font-semibold text-gray-800 mt-1">
                        <i class="fas fa-calendar text-green-500 mr-2"></i>
                        <?= date('M j', strtotime($startDate)) ?> - <?= date('M j, Y', strtotime($endDate)) ?>
                    </p>
                </div>
                <div class="summary-card">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Sitio Filter</p>
                    <p class="text-lg font-semibold text-gray-800 mt-1">
                        <i class="fas fa-filter text-purple-500 mr-2"></i>
                        <?= $sitioFilter === 'all' ? 'All Sitios' : htmlspecialchars($sitioFilter) ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>