<?php
$host = 'localhost';
$dbname = 'healthpatient';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create tables if they don't exist
    $tables = [
        "CREATE TABLE IF NOT EXISTS admin (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        "CREATE TABLE IF NOT EXISTS sitio1_staff (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            position VARCHAR(100),
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES admin(id)
        )",
        
        "CREATE TABLE IF NOT EXISTS sitio1_users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                full_name VARCHAR(100) NOT NULL,
                gender ENUM('male','female','other') DEFAULT NULL,
                age INT DEFAULT NULL,
                address TEXT DEFAULT NULL,
                sitio VARCHAR(100) DEFAULT NULL,
                contact VARCHAR(20) DEFAULT NULL,
                civil_status VARCHAR(50) DEFAULT NULL,
                occupation VARCHAR(100) DEFAULT NULL,
                approved BOOLEAN DEFAULT FALSE,
                approved_by INT DEFAULT NULL,
                unique_number VARCHAR(50) DEFAULT NULL,
                status ENUM('pending','approved','declined') DEFAULT 'pending',
                role VARCHAR(20) DEFAULT 'patient',
                specialization VARCHAR(255) DEFAULT NULL,
                license_number VARCHAR(100) DEFAULT NULL,
                verification_method ENUM('manual_verification','id_upload') DEFAULT 'manual_verification',
                id_image_path VARCHAR(255) DEFAULT NULL,
                verification_notes TEXT DEFAULT NULL,
                verification_consent TINYINT(1) DEFAULT 0,
                id_verified TINYINT(1) DEFAULT 0,
                failed_login_attempts INT DEFAULT 0,
                last_failed_login DATETIME DEFAULT NULL,
                account_locked_until DATETIME DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                verified_at TIMESTAMP NULL DEFAULT NULL,
                FOREIGN KEY (approved_by) REFERENCES sitio1_staff(id)
            )",
        
        "CREATE TABLE IF NOT EXISTS sitio1_patients (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            full_name VARCHAR(100) NOT NULL,
            age INT,
            address TEXT,
            disease VARCHAR(255),
            contact VARCHAR(20),
            last_checkup DATE,
            medical_history TEXT,
            added_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES sitio1_users(id),
            FOREIGN KEY (added_by) REFERENCES sitio1_staff(id)
        )",
        
        "CREATE TABLE IF NOT EXISTS sitio1_consultations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            question TEXT NOT NULL,
            response TEXT,
            responded_by INT,
            is_custom BOOLEAN DEFAULT FALSE,
            status ENUM('pending', 'responded') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            responded_at TIMESTAMP NULL,
            FOREIGN KEY (user_id) REFERENCES sitio1_users(id),
            FOREIGN KEY (responded_by) REFERENCES sitio1_staff(id)
        )",
        
        "CREATE TABLE IF NOT EXISTS sitio1_appointments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            staff_id INT NOT NULL,
            date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            max_slots INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (staff_id) REFERENCES sitio1_staff(id)
        )",
        
        "CREATE TABLE IF NOT EXISTS user_appointments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            appointment_id INT NOT NULL,
            status ENUM('pending', 'approved', 'completed', 'rejected') DEFAULT 'pending',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES sitio1_users(id),
            FOREIGN KEY (appointment_id) REFERENCES sitio1_appointments(id)
        )",
        
        "CREATE TABLE IF NOT EXISTS sitio1_announcements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            staff_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            priority ENUM('normal', 'medium', 'high') DEFAULT 'normal',            announcement_type ENUM('simple', 'lab_result') DEFAULT 'simple',            expiry_date DATE NULL,
            status ENUM('active', 'archived', 'deleted') DEFAULT 'active',
            audience_type ENUM('landing_page', 'public', 'specific') DEFAULT 'public',
            image_path VARCHAR(500) NULL,
            post_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (staff_id) REFERENCES sitio1_staff(id),
            INDEX idx_status (status),
            INDEX idx_post_date (post_date)
        )",
        // patient_visits table for storing visit records
        "CREATE TABLE IF NOT EXISTS patient_visits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            patient_id INT NOT NULL,
            staff_id INT NOT NULL,
            visit_date DATETIME NOT NULL,
            visit_type VARCHAR(100) NOT NULL,
            symptoms TEXT NULL,
            vital_signs TEXT NULL,
            diagnosis TEXT NULL,
            treatment TEXT NULL,
            prescription TEXT NULL,
            referral_info TEXT NULL,
            notes TEXT NULL,
            next_visit_date DATE NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (patient_id) REFERENCES sitio1_patients(id),
            FOREIGN KEY (staff_id) REFERENCES sitio1_staff(id)
        )",
        
        "CREATE TABLE IF NOT EXISTS user_announcements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            announcement_id INT NOT NULL,
            status ENUM('accepted', 'dismissed') NOT NULL,
            response_date TIMESTAMP NULL DEFAULT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES sitio1_users(id),
            FOREIGN KEY (announcement_id) REFERENCES sitio1_announcements(id),
            UNIQUE KEY unique_user_announcement (user_id, announcement_id)
        )",
        
        "CREATE TABLE IF NOT EXISTS announcement_targets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            announcement_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (announcement_id) REFERENCES sitio1_announcements(id),
            FOREIGN KEY (user_id) REFERENCES sitio1_users(id),
            UNIQUE KEY unique_target (announcement_id, user_id)
        )",
        
        "CREATE TABLE IF NOT EXISTS announcement_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            announcement_id INT NOT NULL,
            sender_id INT NOT NULL,
            message TEXT NOT NULL,
            is_edited BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (announcement_id) REFERENCES sitio1_announcements(id),
            FOREIGN KEY (sender_id) REFERENCES sitio1_users(id),
            INDEX idx_announcement (announcement_id),
            INDEX idx_created_at (created_at)
        )"
    ];
    
    foreach ($tables as $table) {
        $pdo->exec($table);
    }
    
    // Add missing columns to existing tables if they don't exist
    try {
        // Check and add civil_status column if it doesn't exist
        $stmt = $pdo->query("SHOW COLUMNS FROM sitio1_users LIKE 'civil_status'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE sitio1_users ADD COLUMN civil_status VARCHAR(50) DEFAULT NULL AFTER contact");
        }
        
        // Check and add occupation column if it doesn't exist
        $stmt = $pdo->query("SHOW COLUMNS FROM sitio1_users LIKE 'occupation'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE sitio1_users ADD COLUMN occupation VARCHAR(100) DEFAULT NULL AFTER civil_status");
        }
        
        // Check and add announcement_type column if it doesn't exist
        $stmt = $pdo->query("SHOW COLUMNS FROM sitio1_announcements LIKE 'announcement_type'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE sitio1_announcements ADD COLUMN announcement_type ENUM('simple', 'lab_result') DEFAULT 'simple' AFTER priority");
        }
        
        // Check and add family_medical_history column if it doesn't exist
        $stmt = $pdo->query("SHOW COLUMNS FROM existing_info_patients LIKE 'family_medical_history'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE existing_info_patients ADD COLUMN family_medical_history TEXT DEFAULT NULL");
        }
        
        // Check and add other missing columns if they don't exist
        $stmt = $pdo->query("SHOW COLUMNS FROM existing_info_patients LIKE 'chronic_conditions'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE existing_info_patients ADD COLUMN chronic_conditions TEXT DEFAULT NULL");
        }
        
        $stmt = $pdo->query("SHOW COLUMNS FROM existing_info_patients LIKE 'immunization_record'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE existing_info_patients ADD COLUMN immunization_record TEXT DEFAULT NULL");
        }
        
        $stmt = $pdo->query("SHOW COLUMNS FROM existing_info_patients LIKE 'medical_history'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE existing_info_patients ADD COLUMN medical_history TEXT DEFAULT NULL");
        }
    } catch (PDOException $e) {
        // Columns might already exist, continue
    }
    
    // Insert default admin if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO admin (username, password, full_name) VALUES ('admin', '$hashedPassword', 'System Administrator')");
    }
    
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
// Helper function to get PDO instance
function getPDO() {
    global $pdo;
    return $pdo;
}
?>