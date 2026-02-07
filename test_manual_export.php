<?php
/**
 * Manual Export Test Page
 * Tests if the manual export POST requests are being received and processed correctly
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

redirectIfNotLoggedIn();
if (!isStaff()) {
    header('Location: /community-health-tracker/');
    exit();
}

$test_results = [];

// Test 1: Check if export_manual POST data is being received
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_export'])) {
    $test_results['post_received'] = [
        'status' => 'PASS',
        'message' => 'POST request received successfully',
        'data' => [
            'export_type' => $_POST['export_type'] ?? 'none',
            'patients_count' => count($_POST['selected_patients'] ?? []),
            'patient_ids' => $_POST['selected_patients'] ?? []
        ]
    ];
    
    // Test 2: Check database connection
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM sitio1_patients WHERE deleted_at IS NULL");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $test_results['db_connection'] = [
            'status' => 'PASS',
            'message' => 'Database connected successfully',
            'total_patients' => $result['total']
        ];
    } catch (Exception $e) {
        $test_results['db_connection'] = [
            'status' => 'FAIL',
            'message' => 'Database connection error: ' . $e->getMessage()
        ];
    }
    
    // Test 3: Simulate query that export would use
    if (!empty($_POST['selected_patients'])) {
        try {
            $selectedPatients = $_POST['selected_patients'];
            $placeholders = implode(',', array_fill(0, count($selectedPatients), '?'));
            $query = "SELECT 
                p.*,
                e.*,
                CASE 
                    WHEN p.user_id IS NOT NULL THEN 'Registered Patient'
                    ELSE 'Regular Patient'
                END as patient_type
            FROM sitio1_patients p
            LEFT JOIN existing_info_patients e ON p.id = e.patient_id
            WHERE p.id IN ($placeholders) AND p.deleted_at IS NULL";
            
            if (!isStaff() || !function_exists('staff_can_view_all') || !staff_can_view_all()) {
                $query .= " AND p.added_by = ?";
                $params = array_merge($selectedPatients, [$_SESSION['user']['id']]);
            } else {
                $params = $selectedPatients;
            }
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $test_results['query_execution'] = [
                'status' => 'PASS',
                'message' => 'Query executed successfully',
                'records_found' => count($patients),
                'sample_patient' => $patients[0] ?? null
            ];
        } catch (Exception $e) {
            $test_results['query_execution'] = [
                'status' => 'FAIL',
                'message' => 'Query error: ' . $e->getMessage()
            ];
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Manual Export Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .test-form { margin: 20px 0; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, button { padding: 10px; }
        button { background: #4A90E2; color: white; border: none; cursor: pointer; border-radius: 4px; }
        button:hover { background: #357ABD; }
        .results { margin-top: 30px; }
        .result-item { margin: 15px 0; padding: 15px; border-left: 4px solid; border-radius: 4px; }
        .result-item.PASS { border-left-color: #4CAF50; background-color: #f1f8e9; }
        .result-item.FAIL { border-left-color: #f44336; background-color: #ffebee; }
        .result-item h3 { margin: 0 0 10px 0; }
        .result-item pre { background-color: #f5f5f5; padding: 10px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #4A90E2; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manual Export Test Interface</h1>
        <p>Use this page to test if the manual export functionality is working correctly.</p>
        
        <div class="test-form">
            <h2>Step 1: Select Patients to Export</h2>
            <form method="POST" id="exportTestForm">
                <input type="hidden" name="test_export" value="1">
                
                <div class="form-group">
                    <label>Export Type:</label>
                    <select name="export_type" required>
                        <option value="manual_excel">Manual Export as Excel</option>
                        <option value="manual_pdf">Manual Export as PDF</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Select Patients (Check at least one):</label>
                    <table>
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Age</th>
                                <th>Blood Type</th>
                            </tr>
                        </thead>
                        <tbody id="patientList">
                            <?php
                                try {
                                    if (function_exists('staff_can_view_all') && staff_can_view_all()) {
                                        $stmt = $pdo->prepare("SELECT id, full_name, age, blood_type FROM sitio1_patients WHERE deleted_at IS NULL LIMIT 10");
                                        $stmt->execute();
                                    } else {
                                        $stmt = $pdo->prepare("SELECT id, full_name, age, blood_type FROM sitio1_patients WHERE deleted_at IS NULL AND added_by = ? LIMIT 10");
                                        $stmt->execute([$_SESSION['user']['id']]);
                                    }
                                    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    foreach ($patients as $patient) {
                                        echo '<tr>';
                                        echo '<td><input type="checkbox" name="selected_patients[]" value="' . htmlspecialchars($patient['id']) . '"></td>';
                                        echo '<td>' . htmlspecialchars($patient['id']) . '</td>';
                                        echo '<td>' . htmlspecialchars($patient['full_name']) . '</td>';
                                        echo '<td>' . htmlspecialchars($patient['age'] ?? '-') . '</td>';
                                        echo '<td>' . htmlspecialchars($patient['blood_type'] ?? '-') . '</td>';
                                        echo '</tr>';
                                    }
                                } catch (Exception $e) {
                                    echo '<tr><td colspan="5" style="color: red;">Error loading patients: ' . $e->getMessage() . '</td></tr>';
                                }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <button type="submit">Test Manual Export</button>
            </form>
            
            <script>
                document.getElementById('selectAll').addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('input[name="selected_patients[]"]');
                    checkboxes.forEach(cb => cb.checked = this.checked);
                });
            </script>
        </div>
        
        <?php if (!empty($test_results)): ?>
        <div class="results">
            <h2>Test Results</h2>
            <?php foreach ($test_results as $testName => $result): ?>
            <div class="result-item <?= $result['status'] ?>">
                <h3><?= ucfirst(str_replace('_', ' ', $testName)) ?> - <?= $result['status'] ?></h3>
                <p><?= $result['message'] ?></p>
                <?php if (!empty($result['data'])): ?>
                    <pre><?= json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?></pre>
                <?php endif; ?>
                <?php if (!empty($result['sample_patient'])): ?>
                    <strong>Sample Record:</strong>
                    <pre><?= json_encode($result['sample_patient'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?></pre>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
