<?php
// Remove duplicate announcement_type column

try {
    $pdo = new PDO('mysql:host=localhost;dbname=healthpatient', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Removing duplicate announcement_type column...\n";
    $pdo->exec("ALTER TABLE sitio1_announcements DROP COLUMN announcement_type");
    echo "✓ Removed announcement_type column\n";
    echo "✓ Only announcement_category column remains\n";
    
} catch (PDOException $e) {
    if ($e->getCode() == '42000' && strpos($e->getMessage(), "check that it exists") !== false) {
        echo "Column announcement_type doesn't exist (already removed)\n";
    } else {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
}
?>
