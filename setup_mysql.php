<?php
/**
 * Setup MySQL Database Schema & Seed Data Migration Script
 * Connects to AWS RDS MySQL, creates aceassignmenthelp_db schema, and populates initial records.
 */

$host = 'database-1.c1o0ygcs2cex.ap-south-1.rds.amazonaws.com';
$port = 3306;
$user = 'admin';
$pass = 'Marwal#1627';
$dbname = 'aceassignmenthelp_db';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("USE `$dbname`;");

    echo "Connected to AWS RDS MySQL. Creating tables...\n";

    // 1. Students Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id VARCHAR(50) UNIQUE NOT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        phone VARCHAR(100),
        password VARCHAR(255) NOT NULL,
        country VARCHAR(100),
        university VARCHAR(255),
        course VARCHAR(255),
        status VARCHAR(50) DEFAULT 'Active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // 2. Allocators Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS allocators (
        id INT AUTO_INCREMENT PRIMARY KEY,
        allocator_id VARCHAR(50) UNIQUE NOT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        phone VARCHAR(100),
        password VARCHAR(255) NOT NULL,
        status VARCHAR(50) DEFAULT 'Active',
        performance_score DECIMAL(5,2) DEFAULT 100.00
    ) ENGINE=InnoDB;");

    // 3. Admins Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id VARCHAR(50) UNIQUE NOT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        phone VARCHAR(100),
        password VARCHAR(255) NOT NULL,
        status VARCHAR(50) DEFAULT 'Active'
    ) ENGINE=InnoDB;");

    // 4. Experts Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS experts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        expert_id VARCHAR(50) UNIQUE NOT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        phone VARCHAR(100),
        subjects TEXT,
        rating DECIMAL(3,2) DEFAULT 5.00,
        completed_count INT DEFAULT 0,
        status VARCHAR(50) DEFAULT 'Available'
    ) ENGINE=InnoDB;");

    // 5. Assignments Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        assignment_id VARCHAR(50) UNIQUE NOT NULL,
        student_id VARCHAR(50) NOT NULL,
        title VARCHAR(255) NOT NULL,
        subject VARCHAR(100) NOT NULL,
        assignment_type VARCHAR(100),
        deadline DATETIME,
        timezone VARCHAR(50),
        word_count INT DEFAULT 250,
        pages INT DEFAULT 1,
        reference_style VARCHAR(50),
        priority VARCHAR(50) DEFAULT 'Normal',
        language VARCHAR(50) DEFAULT 'English (US)',
        instructions TEXT,
        price DECIMAL(10,2) DEFAULT 0.00,
        discount_code VARCHAR(50),
        final_price DECIMAL(10,2) DEFAULT 0.00,
        status VARCHAR(50) DEFAULT 'New',
        allocator_id VARCHAR(50),
        expert_id VARCHAR(50),
        country VARCHAR(100),
        university VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // 6. Files Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS files (
        id INT AUTO_INCREMENT PRIMARY KEY,
        file_id VARCHAR(50) UNIQUE NOT NULL,
        assignment_id VARCHAR(50) NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        path VARCHAR(255) NOT NULL,
        file_type VARCHAR(50),
        uploaded_by VARCHAR(100),
        upload_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        is_internal TINYINT(1) DEFAULT 0
    ) ENGINE=InnoDB;");

    // 7. Payments Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        payment_id VARCHAR(50) UNIQUE NOT NULL,
        assignment_id VARCHAR(50) NOT NULL,
        student_id VARCHAR(50) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        currency VARCHAR(10) DEFAULT 'USD',
        status VARCHAR(50) DEFAULT 'Paid',
        payment_method VARCHAR(100),
        transaction_id VARCHAR(100),
        payment_date DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // 8. Allocation Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS allocation (
        id INT AUTO_INCREMENT PRIMARY KEY,
        allocation_id VARCHAR(50) UNIQUE NOT NULL,
        assignment_id VARCHAR(50) NOT NULL,
        expert_id VARCHAR(50) NOT NULL,
        allocator_id VARCHAR(50) NOT NULL,
        allocated_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        deadline DATETIME,
        status VARCHAR(50) DEFAULT 'Active'
    ) ENGINE=InnoDB;");

    // 9. Notes Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        note_id VARCHAR(50) UNIQUE NOT NULL,
        assignment_id VARCHAR(50) NOT NULL,
        user_id VARCHAR(50) NOT NULL,
        user_role VARCHAR(50) NOT NULL,
        user_name VARCHAR(255),
        message TEXT,
        visibility VARCHAR(50) DEFAULT 'Internal',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // 10. Notifications Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        notification_id VARCHAR(50) UNIQUE NOT NULL,
        user_id VARCHAR(50),
        user_role VARCHAR(50),
        title VARCHAR(255) NOT NULL,
        message TEXT,
        is_read TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // 11. Coupons Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS coupons (
        id INT AUTO_INCREMENT PRIMARY KEY,
        coupon_id VARCHAR(50) UNIQUE NOT NULL,
        code VARCHAR(50) UNIQUE NOT NULL,
        discount_percent DECIMAL(5,2) NOT NULL,
        max_uses INT DEFAULT 100,
        current_uses INT DEFAULT 0,
        expires_at DATE,
        status VARCHAR(50) DEFAULT 'Active'
    ) ENGINE=InnoDB;");

    // 12. Blogs Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS blogs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        excerpt TEXT,
        category VARCHAR(100),
        author VARCHAR(255),
        published_at DATE,
        image VARCHAR(255)
    ) ENGINE=InnoDB;");

    // 13. Support Tickets Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS support_tickets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id VARCHAR(50) UNIQUE NOT NULL,
        student_id VARCHAR(50) NOT NULL,
        assignment_id VARCHAR(50),
        subject VARCHAR(255) NOT NULL,
        message TEXT,
        priority VARCHAR(50) DEFAULT 'Medium',
        status VARCHAR(50) DEFAULT 'Open',
        replies JSON,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");

    // 14. Audit Logs Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        log_id VARCHAR(50) UNIQUE NOT NULL,
        user_role VARCHAR(50) NOT NULL,
        user_id VARCHAR(50) NOT NULL,
        action VARCHAR(255) NOT NULL,
        details TEXT,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;");


    echo "Tables verified successfully.\n";

    // Read and execute database.sql if present
    $sqlFile = __DIR__ . '/database.sql';
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        $pdo->exec($sql);
        echo "Successfully imported/verified database.sql into MySQL.\n";
    }

    echo "\n=== MySQL Setup Complete! ===\n";

} catch (PDOException $e) {
    echo "Database setup error: " . $e->getMessage() . "\n";
    exit(1);
}
