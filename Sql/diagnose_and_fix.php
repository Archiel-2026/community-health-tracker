<?php
// Fix AUTO_INCREMENT issue - safer approach

try {
    $pdo = new PDO('mysql:host=localhost;dbname=healthpatient', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Analyzing sitio1_announcements table...\n\n";
    
    // Check current structure
    $stmt = $pdo->query("SHOW CREATE TABLE sitio1_announcements");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $createTable = $result['Create Table'];
    
    echo "Current table structure:\n";
    echo str_repeat("-", 60) . "\n";
    echo $createTable . "\n";
    echo str_repeat("-", 60) . "\n\n";
    
    // Check current data
    $stmt = $pdo->query("SELECT id FROM sitio1_announcements ORDER BY id");
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($ids)) {
        echo "Table is empty\n";
        $maxId = 0;
    } else {
        echo "Current IDs in table: " . implode(', ', $ids) . "\n";
        $maxId = max($ids);
    }
    
    $nextId = $maxId + 1;
    echo "Max ID: $maxId\n";
    echo "Next ID should be: $nextId\n\n";
    
    // Check for AUTO_INCREMENT
    if (preg_match('/AUTO_INCREMENT=(\d+)/', $createTable, $matches)) {
        $currentAutoInc = $matches[1];
        echo "Current AUTO_INCREMENT value: $currentAutoInc\n";
        
        if ($currentAutoInc != $nextId) {
            echo "⚠ AUTO_INCREMENT mismatch! Fixing...\n";
            $pdo->exec("ALTER TABLE sitio1_announcements AUTO_INCREMENT = $nextId");
            echo "✓ AUTO_INCREMENT updated to $nextId\n";
        } else {
            echo "✓ AUTO_INCREMENT is already correct\n";
        }
    } else {
        echo "⚠ AUTO_INCREMENT not found in table definition!\n";
        echo "Adding AUTO_INCREMENT...\n";
        $pdo->exec("ALTER TABLE sitio1_announcements MODIFY id INT(11) NOT NULL AUTO_INCREMENT");
        $pdo->exec("ALTER TABLE sitio1_announcements AUTO_INCREMENT = $nextId");
        echo "✓ AUTO_INCREMENT added and set to $nextId\n";
    }
    
    // Verify PRIMARY KEY exists
    if (strpos($createTable, 'PRIMARY KEY') !== false) {
        echo "✓ PRIMARY KEY exists\n";
    } else {
        echo "⚠ PRIMARY KEY not found! Adding...\n";
        $pdo->exec("ALTER TABLE sitio1_announcements ADD PRIMARY KEY (id)");
        echo "✓ PRIMARY KEY added\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✓✓✓ Table is now ready! ✓✓✓\n";
    echo "Next announcement will use ID: $nextId\n";
    echo str_repeat("=", 60) . "\n";
    
} catch (PDOException $e) {
    echo "\n✗ Database Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
}
?>
