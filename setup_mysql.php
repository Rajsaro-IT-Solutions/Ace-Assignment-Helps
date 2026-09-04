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

    echo "Tables created successfully. Seeding initial data from JSON...\n";

    // Read JSON Seed Data
    $jsonFile = __DIR__ . '/data/database.json';
    if (file_exists($jsonFile)) {
        $json = json_decode(file_get_contents($jsonFile), true);

        // Students
        if (!empty($json['students'])) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO students (student_id, name, email, phone, password, country, university, course, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($json['students'] as $s) {
                $stmt->execute([$s['student_id'], $s['name'], $s['email'], $s['phone'], $s['password'], $s['country'], $s['university'], $s['course'], $s['status'], $s['created_at']]);
            }
        }

        // Allocators
        if (!empty($json['allocators'])) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO allocators (allocator_id, name, email, phone, password, status, performance_score) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($json['allocators'] as $a) {
                $stmt->execute([$a['allocator_id'], $a['name'], $a['email'], $a['phone'], $a['password'], $a['status'], $a['performance_score']]);
            }
        }

        // Admins
        if (!empty($json['admins'])) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO admins (admin_id, name, email, phone, password, status) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($json['admins'] as $ad) {
                $stmt->execute([$ad['admin_id'], $ad['name'], $ad['email'], $ad['phone'], $ad['password'], $ad['status']]);
            }
        }

        // Experts
        if (!empty($json['experts'])) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO experts (expert_id, name, email, phone, subjects, rating, completed_count, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($json['experts'] as $e) {
                $subj = is_array($e['subjects']) ? json_encode($e['subjects']) : $e['subjects'];
                $stmt->execute([$e['expert_id'], $e['name'], $e['email'], $e['phone'], $subj, $e['rating'], $e['completed_count'], $e['status']]);
            }
        }

        // Assignments
        if (!empty($json['assignments'])) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO assignments (assignment_id, student_id, title, subject, assignment_type, deadline, timezone, word_count, pages, reference_style, priority, language, instructions, price, discount_code, final_price, status, allocator_id, expert_id, country, university, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($json['assignments'] as $asm) {
                $stmt->execute([
                    $asm['assignment_id'], $asm['student_id'], $asm['title'], $asm['subject'], $asm['assignment_type'],
                    $asm['deadline'], $asm['timezone'], $asm['word_count'], $asm['pages'], $asm['reference_style'],
                    $asm['priority'], $asm['language'], $asm['instructions'], $asm['price'], $asm['discount_code'],
                    $asm['final_price'], $asm['status'], $asm['allocator_id'], $asm['expert_id'], $asm['country'],
                    $asm['university'], $asm['created_at'], $asm['updated_at']
                ]);
            }
        }

        // Files
        if (!empty($json['files'])) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO files (file_id, assignment_id, file_name, path, file_type, uploaded_by, upload_date, is_internal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($json['files'] as $f) {
                $stmt->execute([$f['file_id'], $f['assignment_id'], $f['file_name'], $f['path'], $f['file_type'], $f['uploaded_by'], $f['upload_date'], $f['is_internal'] ? 1 : 0]);
            }
        }

        // Payments
        if (!empty($json['payments'])) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO payments (payment_id, assignment_id, student_id, amount, currency, status, payment_method, transaction_id, payment_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($json['payments'] as $p) {
                $stmt->execute([$p['payment_id'], $p['assignment_id'], $p['student_id'], $p['amount'], $p['currency'], $p['status'], $p['payment_method'], $p['transaction_id'], $p['payment_date']]);
            }
        }

        // Allocation
        if (!empty($json['allocation'])) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO allocation (allocation_id, assignment_id, expert_id, allocator_id, allocated_date, deadline, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($json['allocation'] as $alc) {
                $stmt->execute([$alc['allocation_id'], $alc['assignment_id'], $alc['expert_id'], $alc['allocator_id'], $alc['allocated_date'], $alc['deadline'], $alc['status']]);
            }
        }

        // Notes
        if (!empty($json['notes'])) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO notes (note_id, assignment_id, user_id, user_role, user_name, message, visibility, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($json['notes'] as $n) {
                $stmt->execute([$n['note_id'], $n['assignment_id'], $n['user_id'], $n['user_role'], $n['user_name'], $n['message'], $n['visibility'], $n['created_at']]);
            }
        }

        // Notifications
        if (!empty($json['notifications'])) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO notifications (notification_id, user_id, user_role, title, message, is_read, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($json['notifications'] as $ntf) {
                $stmt->execute([$ntf['id'], $ntf['user_id'], $ntf['user_role'], $ntf['title'], $ntf['message'], $ntf['is_read'] ? 1 : 0, $ntf['created_at']]);
            }
        }

        // Coupons
        if (!empty($json['coupons'])) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO coupons (coupon_id, code, discount_percent, max_uses, current_uses, expires_at, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($json['coupons'] as $cpn) {
                $stmt->execute([$cpn['coupon_id'], $cpn['code'], $cpn['discount_percent'], $cpn['max_uses'], $cpn['current_uses'], $cpn['expires_at'], $cpn['status']]);
            }
        }

        // Blogs
        if (!empty($json['blogs'])) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO blogs (id, title, excerpt, category, author, published_at, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($json['blogs'] as $b) {
                $stmt->execute([$b['id'], $b['title'], $b['excerpt'], $b['category'], $b['author'], $b['published_at'], $b['image']]);
            }
        }

        // Support Tickets
        if (!empty($json['support_tickets'])) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO support_tickets (ticket_id, student_id, assignment_id, subject, message, priority, status, replies, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($json['support_tickets'] as $t) {
                $replies = json_encode($t['replies'] ?? []);
                $stmt->execute([$t['ticket_id'], $t['student_id'], $t['assignment_id'], $t['subject'], $t['message'], $t['priority'], $t['status'], $replies, $t['created_at']]);
            }
        }

        // Audit Logs
        if (!empty($json['audit_logs'])) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO audit_logs (log_id, user_role, user_id, action, details, timestamp) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($json['audit_logs'] as $log) {
                $stmt->execute([$log['log_id'], $log['user_role'], $log['user_id'], $log['action'], $log['details'], $log['timestamp']]);
            }
        }

        echo "Database migration completed successfully!\n";
    }

} catch (PDOException $e) {
    echo "Database setup error: " . $e->getMessage() . "\n";
    exit(1);
}
