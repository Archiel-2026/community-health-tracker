<?php
// Run database migration for announcement_category field

try {
    $pdo = new PDO('mysql:host=localhost;dbname=healthpatient', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Reading SQL file...\n";
    $sql = file_get_contents(__DIR__ . '/add_announcement_category.sql');
    
    echo "Executing migration...\n";
    $pdo->exec($sql);
    
    echo "✓ Database updated successfully!\n";
    echo "✓ announcement_category field added to sitio1_announcements table\n";
    
} catch (PDOException $e) {
    echo "✗ Database Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
