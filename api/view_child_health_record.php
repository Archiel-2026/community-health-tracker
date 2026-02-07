<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$recordId = $_GET['id'] ?? 0;

try {
    $pdo = getPDO();
    
    // Get child health record
    $stmt = $pdo->prepare("SELECT * FROM child_health_records WHERE id = ?");
    $stmt->execute([$recordId]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$record) {
        echo '<div class="p-6 text-center text-red-600">Record not found.</div>';
        exit;
    }
    
    // Get immunizations
    $stmt = $pdo->prepare("SELECT * FROM child_immunizations WHERE child_health_record_id = ?");
    $stmt->execute([$recordId]);
    $immunizations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get results
    $stmt = $pdo->prepare("SELECT * FROM child_health_results WHERE child_health_record_id = ?");
    $stmt->execute([$recordId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    ?>
    <div class="p-6">
        <div class="grid grid-cols-2 gap-6 mb-8">
            <div class="bg-blue-50 p-4 rounded-lg">
                <h4 class="font-bold text-lg mb-3 text-blue-700">Child Information</h4>
                <p><strong>Name:</strong> <?= htmlspecialchars($record['fullname']) ?></p>
                <p><strong>Date of Birth:</strong> <?= date('M d, Y', strtotime($record['dob'])) ?></p>
                <p><strong>Sex:</strong> <?= htmlspecialchars($record['sex']) ?></p>
                <p><strong>Birth Order:</strong> <?= htmlspecialchars($record['birth_order'] ?? 'N/A') ?></p>
            </div>
            
            <div class="bg-green-50 p-4 rounded-lg">
                <h4 class="font-bold text-lg mb-3 text-green-700">Family Information</h4>
                <p><strong>Family No:</strong> <?= htmlspecialchars($record['family_no']) ?></p>
                <p><strong>UFC No:</strong> <?= htmlspecialchars($record['ufc_no']) ?></p>
                <p><strong>Mother:</strong> <?= htmlspecialchars($record['mother'] ?? 'N/A') ?></p>
                <p><strong>Father:</strong> <?= htmlspecialchars($record['father'] ?? 'N/A') ?></p>
            </div>
        </div>
        
        <?php if (!empty($immunizations)): ?>
        <div class="mb-6">
            <h4 class="font-bold text-lg mb-3 text-gray-700">Immunization Record</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full border">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border p-2">Type</th>
                            <th class="border p-2">Within 24hrs</th>
                            <th class="border p-2">1st</th>
                            <th class="border p-2">2nd</th>
                            <th class="border p-2">3rd</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($immunizations as $imm): ?>
                            <tr>
                                <td class="border p-2"><?= htmlspecialchars($imm['type']) ?></td>
                                <td class="border p-2 text-center"><?= $imm['within_24hrs'] ? '✓' : '' ?></td>
                                <td class="border p-2 text-center"><?= $imm['first'] ? '✓' : '' ?></td>
                                <td class="border p-2 text-center"><?= $imm['second'] ? '✓' : '' ?></td>
                                <td class="border p-2 text-center"><?= $imm['third'] ? '✓' : '' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($results)): ?>
        <div class="mb-6">
            <h4 class="font-bold text-lg mb-3 text-gray-700">Health Results</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full border">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border p-2">Date</th>
                            <th class="border p-2">Age</th>
                            <th class="border p-2">Weight</th>
                            <th class="border p-2">Temp</th>
                            <th class="border p-2">Height</th>
                            <th class="border p-2">Findings</th>
                            <th class="border p-2">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $result): ?>
                            <tr>
                                <td class="border p-2"><?= date('M d, Y', strtotime($result['result_date'])) ?></td>
                                <td class="border p-2"><?= htmlspecialchars($result['age']) ?></td>
                                <td class="border p-2"><?= htmlspecialchars($result['weight']) ?></td>
                                <td class="border p-2"><?= htmlspecialchars($result['temperature']) ?></td>
                                <td class="border p-2"><?= htmlspecialchars($result['height']) ?></td>
                                <td class="border p-2"><?= htmlspecialchars($result['findings']) ?></td>
                                <td class="border p-2"><?= htmlspecialchars($result['notes']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php
    
} catch (Exception $e) {
    echo '<div class="p-6 text-center text-red-600">Error loading record: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>