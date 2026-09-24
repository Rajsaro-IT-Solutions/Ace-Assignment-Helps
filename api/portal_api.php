<?php
/**
 * Ace Assignment Helps - Dedicated Mobile & Portal REST API
 * Synchronizes seamlessly with AWS RDS MySQL and mirrors existing portal auth/business logic.
 */

// Enable CORS for mobile app requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-User-Id, X-User-Role, ngrok-skip-browser-warning');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

// Parse JSON request body if present
$rawInput = file_get_contents('php://input');
$jsonInput = json_decode($rawInput, true) ?? [];
$params = array_merge($_GET, $_POST, $jsonInput);

$action = $params['action'] ?? '';

function sendResponse($success, $data = [], $message = '', $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}

try {
    switch ($action) {

        // ---------------------------------------------------------
        // 1. Health & Server Status Check
        // ---------------------------------------------------------
        case 'health':
            $pdo = DataStore::getPdo();
            sendResponse(true, [
                'status' => 'online',
                'app_name' => 'Ace Assignment Helps Portal API',
                'database' => $pdo ? 'connected (AWS RDS MySQL)' : 'offline',
                'server_time' => date('Y-m-d H:i:s'),
                'supported_roles' => ['Student', 'Allocator', 'Expert', 'Admin']
            ], 'API is online and healthy.');
            break;

        // ---------------------------------------------------------
        // 2. Unified Portal Login (Same logic as Auth::login)
        // ---------------------------------------------------------
        case 'login':
            $email = trim($params['email'] ?? '');
            $password = trim($params['password'] ?? '');
            $requestedRole = trim($params['role'] ?? 'all');

            if (empty($email) || empty($password)) {
                sendResponse(false, null, 'Email and password are required.', 400);
            }

            $loginResult = Auth::login($email, $password, $requestedRole);

            if ($loginResult === true) {
                $user = Auth::currentUser();

                if (function_exists('add_audit_log')) {
                    add_audit_log($user['role'], $user['id'], 'Mobile Login', 'Logged in via Android Portal App');
                }

                $token = base64_encode($user['id'] . ':' . $user['role'] . ':' . time());

                sendResponse(true, [
                    'user' => [
                        'id' => $user['id'],
                        'name' => $user['name'] ?? '',
                        'email' => $user['email'] ?? '',
                        'role' => $user['role'] ?? 'Student',
                        'phone' => $user['phone'] ?? '',
                        'country' => $user['country'] ?? ''
                    ],
                    'token' => $token
                ], 'Login successful! Welcome to the portal.');
            } elseif ($loginResult === 'blocked') {
                sendResponse(false, null, 'Access Denied: This account has been blocked by an administrator. Please contact support.', 403);
            } else {
                sendResponse(false, null, 'Invalid email address or password. Please try again.', 401);
            }
            break;

        // ---------------------------------------------------------
        // 3. Role-Based Dashboard Statistics
        // ---------------------------------------------------------
        case 'dashboard':
            $role = ucfirst(strtolower($params['role'] ?? 'Student'));
            $userId = trim($params['user_id'] ?? '');

            $pdo = DataStore::getPdo();
            if (!$pdo) {
                sendResponse(false, null, 'Database connection error.', 500);
            }

            $stats = [];
            $recentAssignments = [];

            if ($role === 'Student') {
                $stmt = $pdo->prepare("SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status IN ('Pending', 'Assigned', 'In Progress') THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status IN ('Under QA', 'Under Review', 'Revision Requested') THEN 1 ELSE 0 END) as under_review,
                    SUM(CASE WHEN status IN ('Completed', 'Delivered') THEN 1 ELSE 0 END) as completed
                    FROM assignments WHERE student_id = ?");
                $stmt->execute([$userId]);
                $counts = $stmt->fetch() ?: ['total' => 0, 'in_progress' => 0, 'under_review' => 0, 'completed' => 0];

                $stats = [
                    'total_assignments' => (int)($counts['total'] ?? 0),
                    'in_progress' => (int)($counts['in_progress'] ?? 0),
                    'under_review' => (int)($counts['under_review'] ?? 0),
                    'completed' => (int)($counts['completed'] ?? 0),
                ];

                $stmtRecent = $pdo->prepare("SELECT * FROM assignments WHERE student_id = ? ORDER BY created_at DESC LIMIT 5");
                $stmtRecent->execute([$userId]);
                $recentAssignments = $stmtRecent->fetchAll();

            } elseif ($role === 'Expert') {
                $stmt = $pdo->prepare("SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status IN ('Under QA', 'Under Review') THEN 1 ELSE 0 END) as under_qa,
                    SUM(CASE WHEN status IN ('Completed', 'Delivered') THEN 1 ELSE 0 END) as completed
                    FROM assignments WHERE expert_id = ?");
                $stmt->execute([$userId]);
                $counts = $stmt->fetch() ?: ['total' => 0, 'in_progress' => 0, 'under_qa' => 0, 'completed' => 0];

                $stats = [
                    'total_allocated' => (int)($counts['total'] ?? 0),
                    'in_progress' => (int)($counts['in_progress'] ?? 0),
                    'under_qa' => (int)($counts['under_qa'] ?? 0),
                    'completed' => (int)($counts['completed'] ?? 0),
                ];

                $stmtRecent = $pdo->prepare("SELECT * FROM assignments WHERE expert_id = ? ORDER BY created_at DESC LIMIT 5");
                $stmtRecent->execute([$userId]);
                $recentAssignments = $stmtRecent->fetchAll();

            } elseif ($role === 'Allocator') {
                $stmt = $pdo->prepare("SELECT
                    SUM(CASE WHEN expert_id IS NULL OR expert_id = '' OR status = 'Pending' THEN 1 ELSE 0 END) as unallocated,
                    SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status IN ('Under QA', 'Under Review') THEN 1 ELSE 0 END) as under_qa,
                    SUM(CASE WHEN status IN ('Completed', 'Delivered') THEN 1 ELSE 0 END) as completed
                    FROM assignments");
                $stmt->execute();
                $counts = $stmt->fetch() ?: ['unallocated' => 0, 'in_progress' => 0, 'under_qa' => 0, 'completed' => 0];

                $expertCountStmt = $pdo->query("SELECT COUNT(*) FROM experts WHERE status != 'Inactive'");
                $expertCount = (int)$expertCountStmt->fetchColumn();

                $stats = [
                    'unallocated' => (int)($counts['unallocated'] ?? 0),
                    'in_progress' => (int)($counts['in_progress'] ?? 0),
                    'under_qa' => (int)($counts['under_qa'] ?? 0),
                    'active_experts' => $expertCount,
                ];

                $stmtRecent = $pdo->prepare("SELECT * FROM assignments WHERE (expert_id IS NULL OR expert_id = '' OR status = 'Pending') ORDER BY created_at DESC LIMIT 5");
                $stmtRecent->execute();
                $recentAssignments = $stmtRecent->fetchAll();

            } elseif ($role === 'Admin') {
                $assignStats = $pdo->query("SELECT
                    COUNT(*) as total_orders,
                    SUM(paid_amount) as total_revenue,
                    SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as active_orders,
                    SUM(CASE WHEN status IN ('Completed', 'Delivered') THEN 1 ELSE 0 END) as completed_orders
                    FROM assignments")->fetch();

                $studentCount = (int)$pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
                $expertCount = (int)$pdo->query("SELECT COUNT(*) FROM experts")->fetchColumn();
                $allocatorCount = (int)$pdo->query("SELECT COUNT(*) FROM allocators")->fetchColumn();

                $stats = [
                    'total_assignments' => (int)($assignStats['total_orders'] ?? 0),
                    'total_revenue' => (float)($assignStats['total_revenue'] ?? 0),
                    'active_orders' => (int)($assignStats['active_orders'] ?? 0),
                    'completed_orders' => (int)($assignStats['completed_orders'] ?? 0),
                    'total_students' => $studentCount,
                    'total_experts' => $expertCount,
                    'total_allocators' => $allocatorCount
                ];

                $recentAssignments = $pdo->query("SELECT * FROM assignments ORDER BY created_at DESC LIMIT 5")->fetchAll();
            }

            sendResponse(true, [
                'role' => $role,
                'stats' => $stats,
                'recent_assignments' => $recentAssignments
            ]);
            break;

        // ---------------------------------------------------------
        // 4. Assignments List Filtered by Role & Status
        // ---------------------------------------------------------
        case 'assignments':
            $role = ucfirst(strtolower($params['role'] ?? 'Student'));
            $userId = trim($params['user_id'] ?? '');
            $status = trim($params['status'] ?? '');
            $search = trim($params['search'] ?? '');

            $pdo = DataStore::getPdo();
            if (!$pdo) {
                sendResponse(false, null, 'Database connection error.', 500);
            }

            $sql = "SELECT * FROM assignments WHERE 1=1";
            $sqlParams = [];

            if ($role === 'Student') {
                $sql .= " AND student_id = ?";
                $sqlParams[] = $userId;
            } elseif ($role === 'Expert') {
                $sql .= " AND expert_id = ?";
                $sqlParams[] = $userId;
            }

            if (!empty($status) && $status !== 'All') {
                $sql .= " AND status = ?";
                $sqlParams[] = $status;
            }

            if (!empty($search)) {
                $sql .= " AND (title LIKE ? OR subject LIKE ? OR assignment_id LIKE ?)";
                $like = "%$search%";
                $sqlParams[] = $like;
                $sqlParams[] = $like;
                $sqlParams[] = $like;
            }

            $sql .= " ORDER BY created_at DESC LIMIT 100";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($sqlParams);
            $assignments = $stmt->fetchAll();

            foreach ($assignments as &$item) {
                if (function_exists('get_sla_status') && !empty($item['deadline'])) {
                    $item['sla'] = get_sla_status($item['deadline']);
                }
            }

            sendResponse(true, $assignments);
            break;

        // ---------------------------------------------------------
        // 5. Assignment Detail with Files & Allocation Info
        // ---------------------------------------------------------
        case 'assignment_detail':
            $assignmentId = trim($params['assignment_id'] ?? '');
            if (empty($assignmentId)) {
                sendResponse(false, null, 'assignment_id is required.', 400);
            }

            $pdo = DataStore::getPdo();
            $stmt = $pdo->prepare("SELECT * FROM assignments WHERE assignment_id = ? LIMIT 1");
            $stmt->execute([$assignmentId]);
            $assignment = $stmt->fetch();

            if (!$assignment) {
                sendResponse(false, null, 'Assignment not found.', 404);
            }

            $fileStmt = $pdo->prepare("SELECT * FROM files WHERE assignment_id = ?");
            $fileStmt->execute([$assignmentId]);
            $files = $fileStmt->fetchAll();

            $allocStmt = $pdo->prepare("SELECT a.*, e.name as expert_name, e.email as expert_email
                FROM allocation a
                LEFT JOIN experts e ON a.expert_id = e.expert_id
                WHERE a.assignment_id = ?
                ORDER BY a.allocated_date DESC LIMIT 1");
            $allocStmt->execute([$assignmentId]);
            $allocation = $allocStmt->fetch();

            $sla = !empty($assignment['deadline']) ? get_sla_status($assignment['deadline']) : null;

            sendResponse(true, [
                'assignment' => $assignment,
                'files' => $files,
                'allocation' => $allocation,
                'sla' => $sla
            ]);
            break;

        // ---------------------------------------------------------
        // 6. Update Assignment Status
        // ---------------------------------------------------------
        case 'update_status':
            $assignmentId = trim($params['assignment_id'] ?? '');
            $newStatus = trim($params['status'] ?? '');
            $userId = trim($params['user_id'] ?? '');
            $userRole = trim($params['role'] ?? '');

            if (empty($assignmentId) || empty($newStatus)) {
                sendResponse(false, null, 'assignment_id and status are required.', 400);
            }

            $pdo = DataStore::getPdo();
            $stmt = $pdo->prepare("UPDATE assignments SET status = ?, updated_at = NOW() WHERE assignment_id = ?");
            $stmt->execute([$newStatus, $assignmentId]);

            if (function_exists('add_audit_log')) {
                add_audit_log($userRole, $userId, 'Status Update', "Updated assignment $assignmentId status to $newStatus");
            }

            sendResponse(true, ['assignment_id' => $assignmentId, 'status' => $newStatus], 'Assignment status updated successfully.');
            break;

        // ---------------------------------------------------------
        // 7. User Notifications
        // ---------------------------------------------------------
        case 'notifications':
            $userId = trim($params['user_id'] ?? '');
            $userRole = trim($params['role'] ?? '');

            $pdo = DataStore::getPdo();
            $stmt = $pdo->prepare("SELECT * FROM notifications
                WHERE (user_id = ? OR user_role = ? OR user_role = 'All')
                ORDER BY created_at DESC LIMIT 30");
            $stmt->execute([$userId, $userRole]);
            $notifications = $stmt->fetchAll();

            sendResponse(true, $notifications);
            break;

        // ---------------------------------------------------------
        // 8. Submit New Assignment (Student Portal)
        // ---------------------------------------------------------
        case 'submit_assignment':
            $studentId = trim($params['student_id'] ?? '');
            $title = trim($params['title'] ?? '');
            $subject = trim($params['subject'] ?? 'General Studies');
            $assignmentType = trim($params['assignment_type'] ?? 'Essay');
            $deadline = trim($params['deadline'] ?? date('Y-m-d H:i:s', strtotime('+5 days')));
            $wordCount = (int)($params['word_count'] ?? 1000);
            $pages = max(1, (int)ceil($wordCount / 250));
            $referenceStyle = trim($params['reference_style'] ?? 'APA 7th');
            $instructions = trim($params['instructions'] ?? $params['description'] ?? '');
            $currency = strtoupper(trim($params['currency'] ?? 'USD'));
            $price = (float)($params['price'] ?? ($wordCount * 0.05));
            $finalPrice = (float)($params['final_price'] ?? $price);

            if (empty($studentId) || empty($title)) {
                sendResponse(false, null, 'student_id and title are required.', 400);
            }

            $pdo = DataStore::getPdo();
            $assignmentId = 'AAH-' . rand(10000, 99999);

            $stmt = $pdo->prepare("INSERT INTO assignments
                (assignment_id, student_id, title, subject, assignment_type, deadline, timezone, word_count, pages, reference_style, instructions, currency, price, final_price, status, payment_status, paid_amount, remaining_balance, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, 'UTC', ?, ?, ?, ?, ?, ?, ?, 'Pending', 'Pending', 0, ?, NOW(), NOW())");

            $stmt->execute([
                $assignmentId, $studentId, $title, $subject, $assignmentType, $deadline,
                $wordCount, $pages, $referenceStyle, $instructions, $currency, $price, $finalPrice, $finalPrice
            ]);

            $paymentId = 'PAY-' . rand(10000, 99999);
            $payStmt = $pdo->prepare("INSERT INTO payments (payment_id, assignment_id, student_id, amount, currency, status, payment_method, payment_date) VALUES (?, ?, ?, ?, ?, 'Pending', 'Credit/Debit Card', NOW())");
            $payStmt->execute([$paymentId, $assignmentId, $studentId, $finalPrice, $currency]);

            $notifStmt = $pdo->prepare("INSERT INTO notifications (notification_id, user_role, title, message, is_read, created_at) VALUES (?, 'Allocator', 'New Order Received', ?, 0, NOW())");
            $notifStmt->execute(['NTF-' . rand(10000, 99999), "New assignment '$title' ($assignmentId) submitted by student."]);

            sendResponse(true, [
                'assignment_id' => $assignmentId,
                'title' => $title,
                'status' => 'Pending',
                'final_price' => $finalPrice,
                'currency' => $currency
            ], 'Assignment submitted successfully! Our allocators will match an expert shortly.');
            break;

        // ---------------------------------------------------------
        // 9. Allocate Expert (Allocator Portal)
        // ---------------------------------------------------------
        case 'allocate_expert':
            $assignmentId = trim($params['assignment_id'] ?? '');
            $expertId = trim($params['expert_id'] ?? '');
            $allocatorId = trim($params['allocator_id'] ?? 'ALC-001');
            $deadline = trim($params['deadline'] ?? date('Y-m-d H:i:s', strtotime('+3 days')));

            if (empty($assignmentId) || empty($expertId)) {
                sendResponse(false, null, 'assignment_id and expert_id are required.', 400);
            }

            $pdo = DataStore::getPdo();
            $stmt = $pdo->prepare("UPDATE assignments SET expert_id = ?, allocator_id = ?, status = 'In Progress', updated_at = NOW() WHERE assignment_id = ?");
            $stmt->execute([$expertId, $allocatorId, $assignmentId]);

            $allocId = 'ALC-' . rand(1000, 9999);
            $allocStmt = $pdo->prepare("INSERT INTO allocation (allocation_id, assignment_id, expert_id, allocator_id, allocated_date, deadline, status) VALUES (?, ?, ?, ?, NOW(), ?, 'Allocated')");
            $allocStmt->execute([$allocId, $assignmentId, $expertId, $allocatorId, $deadline]);

            $notif = $pdo->prepare("INSERT INTO notifications (notification_id, user_id, user_role, title, message, is_read, created_at) VALUES (?, ?, 'Expert', 'New Task Assigned', ?, 0, NOW())");
            $notif->execute(['NTF-' . rand(10000, 99999), $expertId, "You have been allocated assignment $assignmentId. Deadline: $deadline"]);

            sendResponse(true, ['assignment_id' => $assignmentId, 'expert_id' => $expertId, 'status' => 'In Progress'], 'Expert successfully allocated.');
            break;

        // ---------------------------------------------------------
        // 10. Allocator Review & QA (Approve / Request Revision)
        // ---------------------------------------------------------
        case 'allocator_review':
            $assignmentId = trim($params['assignment_id'] ?? '');
            $decision = strtolower(trim($params['decision'] ?? 'approve'));
            $notes = trim($params['notes'] ?? '');

            if (empty($assignmentId)) {
                sendResponse(false, null, 'assignment_id is required.', 400);
            }

            $pdo = DataStore::getPdo();
            if ($decision === 'approve') {
                $stmt = $pdo->prepare("UPDATE assignments SET status = 'Completed', payment_status = 'Paid', updated_at = NOW() WHERE assignment_id = ?");
                $stmt->execute([$assignmentId]);
                $msg = 'Assignment approved and marked as Completed.';
            } else {
                $stmt = $pdo->prepare("UPDATE assignments SET status = 'Revision Requested', revision_notes = ?, updated_at = NOW() WHERE assignment_id = ?");
                $stmt->execute([$notes, $assignmentId]);
                $msg = 'Revision request logged and sent back to expert.';
            }

            sendResponse(true, ['assignment_id' => $assignmentId, 'decision' => $decision], $msg);
            break;

        // ---------------------------------------------------------
        // 11. Expert Actions (Start Work / Submit Solution)
        // ---------------------------------------------------------
        case 'expert_action':
            $assignmentId = trim($params['assignment_id'] ?? '');
            $actionType = trim($params['action_type'] ?? 'start');
            $expertId = trim($params['expert_id'] ?? '');
            $notes = trim($params['notes'] ?? '');

            if (empty($assignmentId)) {
                sendResponse(false, null, 'assignment_id is required.', 400);
            }

            $pdo = DataStore::getPdo();
            if ($actionType === 'start') {
                $stmt = $pdo->prepare("UPDATE assignments SET status = 'In Progress', updated_at = NOW() WHERE assignment_id = ?");
                $stmt->execute([$assignmentId]);
                sendResponse(true, ['assignment_id' => $assignmentId, 'status' => 'In Progress'], 'Started working on task.');
            } elseif ($actionType === 'submit_qa') {
                $stmt = $pdo->prepare("UPDATE assignments SET status = 'Under QA', updated_at = NOW() WHERE assignment_id = ?");
                $stmt->execute([$assignmentId]);

                $pdo->prepare("INSERT INTO notifications (notification_id, user_role, title, message, is_read, created_at) VALUES (?, 'Allocator', 'Solution Submitted for QA', ?, 0, NOW())")->execute(['NTF-' . rand(10000, 99999), "Expert $expertId submitted assignment $assignmentId for QA review."]);

                sendResponse(true, ['assignment_id' => $assignmentId, 'status' => 'Under QA'], 'Solution submitted for Quality Assurance.');
            }
            break;

        // ---------------------------------------------------------
        // 12. Expert Roster Directory
        // ---------------------------------------------------------
        case 'experts_list':
            $pdo = DataStore::getPdo();
            $stmt = $pdo->query("SELECT e.id, e.expert_id, e.name, e.email, e.phone, e.subjects, e.rating, e.completed_count, e.status,
                (SELECT COUNT(*) FROM assignments a WHERE a.expert_id = e.expert_id AND a.status IN ('In Progress', 'Assigned', 'Under QA')) as active_tasks
                FROM experts e ORDER BY e.name ASC");
            $experts = $stmt->fetchAll();
            sendResponse(true, $experts);
            break;

        // ---------------------------------------------------------
        // 13. Registered Students Directory
        // ---------------------------------------------------------
        case 'students_list':
            $pdo = DataStore::getPdo();
            $stmt = $pdo->query("SELECT s.id, s.student_id, s.name, s.email, s.phone, s.country, s.university, s.status, s.created_at,
                (SELECT COUNT(*) FROM assignments a WHERE a.student_id = s.student_id) as total_orders,
                (SELECT COALESCE(SUM(a.paid_amount), 0) FROM assignments a WHERE a.student_id = s.student_id) as total_spent
                FROM students s ORDER BY s.created_at DESC");
            $students = $stmt->fetchAll();
            sendResponse(true, $students);
            break;

        // ---------------------------------------------------------
        // 14. Allocators Directory
        // ---------------------------------------------------------
        case 'allocators_list':
            $pdo = DataStore::getPdo();
            $stmt = $pdo->query("SELECT id, allocator_id, name, email, phone, status, performance_score FROM allocators ORDER BY name ASC");
            $allocators = $stmt->fetchAll();
            sendResponse(true, $allocators);
            break;

        // ---------------------------------------------------------
        // 15. Invoices & Payments List
        // ---------------------------------------------------------
        case 'payments_list':
            $studentId = trim($params['student_id'] ?? '');
            $pdo = DataStore::getPdo();

            if (!empty($studentId)) {
                $stmt = $pdo->prepare("SELECT p.*, a.title as assignment_title, a.subject as assignment_subject, a.status as assignment_status
                    FROM payments p
                    LEFT JOIN assignments a ON p.assignment_id = a.assignment_id
                    WHERE p.student_id = ?
                    ORDER BY p.payment_date DESC LIMIT 100");
                $stmt->execute([$studentId]);
            } else {
                $stmt = $pdo->query("SELECT p.*, a.title as assignment_title, a.subject as assignment_subject, a.status as assignment_status, s.name as student_name
                    FROM payments p
                    LEFT JOIN assignments a ON p.assignment_id = a.assignment_id
                    LEFT JOIN students s ON p.student_id = s.student_id
                    ORDER BY p.payment_date DESC LIMIT 100");
            }
            $payments = $stmt->fetchAll();
            sendResponse(true, $payments);
            break;

        // ---------------------------------------------------------
        // 16. Coupons List & Create
        // ---------------------------------------------------------
        case 'coupons':
            $pdo = DataStore::getPdo();
            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($params['create'])) {
                $code = strtoupper(trim($params['code'] ?? ''));
                $discount = (int)($params['discount_percent'] ?? 10);
                $maxUses = (int)($params['max_uses'] ?? 100);
                $expires = trim($params['expires_at'] ?? date('Y-m-d H:i:s', strtotime('+30 days')));

                if (empty($code)) {
                    sendResponse(false, null, 'Coupon code is required.', 400);
                }

                $cId = 'CPN-' . rand(100, 999);
                $stmt = $pdo->prepare("INSERT INTO coupons (coupon_id, code, discount_percent, max_uses, current_uses, expires_at, status) VALUES (?, ?, ?, ?, 0, ?, 'Active')");
                $stmt->execute([$cId, $code, $discount, $maxUses, $expires]);
                sendResponse(true, ['code' => $code, 'discount_percent' => $discount], 'Coupon created successfully.');
            } else {
                $coupons = $pdo->query("SELECT * FROM coupons ORDER BY id DESC")->fetchAll();
                sendResponse(true, $coupons);
            }
            break;

        // ---------------------------------------------------------
        // 17. Courses & Subjects List
        // ---------------------------------------------------------
        case 'courses':
            $pdo = DataStore::getPdo();
            $courses = $pdo->query("SELECT * FROM courses WHERE status = 'Active' ORDER BY title ASC")->fetchAll();
            sendResponse(true, $courses);
            break;

        // ---------------------------------------------------------
        // 18. Support Tickets & Messaging
        // ---------------------------------------------------------
        case 'support_tickets':
            $userId = trim($params['user_id'] ?? '');
            $userRole = trim($params['role'] ?? 'Student');
            $pdo = DataStore::getPdo();

            if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($params['create'])) {
                $subject = trim($params['subject'] ?? 'Portal Inquiry');
                $message = trim($params['message'] ?? '');
                $priority = trim($params['priority'] ?? 'Medium');
                $assignmentId = trim($params['assignment_id'] ?? '');

                if (empty($message)) {
                    sendResponse(false, null, 'Message is required.', 400);
                }

                $ticketId = 'TCK-' . rand(1000, 9999);
                $stmt = $pdo->prepare("INSERT INTO support_tickets (ticket_id, student_id, assignment_id, subject, message, priority, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'Open', NOW())");
                $stmt->execute([$ticketId, $userId, $assignmentId, $subject, $message, $priority]);

                sendResponse(true, ['ticket_id' => $ticketId], 'Support ticket created successfully.');
            } else {
                if ($userRole === 'Student') {
                    $stmt = $pdo->prepare("SELECT * FROM support_tickets WHERE student_id = ? ORDER BY created_at DESC LIMIT 50");
                    $stmt->execute([$userId]);
                } else {
                    $stmt = $pdo->query("SELECT * FROM support_tickets ORDER BY created_at DESC LIMIT 50");
                }
                $tickets = $stmt->fetchAll();
                sendResponse(true, $tickets);
            }
            break;

        // ---------------------------------------------------------
        // 19. User Profile Update
        // ---------------------------------------------------------
        case 'update_profile':
            $userId = trim($params['user_id'] ?? '');
            $role = ucfirst(strtolower(trim($params['role'] ?? 'Student')));
            $name = trim($params['name'] ?? '');
            $phone = trim($params['phone'] ?? '');
            $country = trim($params['country'] ?? '');
            $university = trim($params['university'] ?? '');

            if (empty($userId) || empty($name)) {
                sendResponse(false, null, 'user_id and name are required.', 400);
            }

            $pdo = DataStore::getPdo();
            if ($role === 'Student') {
                $stmt = $pdo->prepare("UPDATE students SET name = ?, phone = ?, country = ?, university = ? WHERE student_id = ?");
                $stmt->execute([$name, $phone, $country, $university, $userId]);
            } elseif ($role === 'Expert') {
                $stmt = $pdo->prepare("UPDATE experts SET name = ?, phone = ? WHERE expert_id = ?");
                $stmt->execute([$name, $phone, $userId]);
            } elseif ($role === 'Allocator') {
                $stmt = $pdo->prepare("UPDATE allocators SET name = ?, phone = ? WHERE allocator_id = ?");
                $stmt->execute([$name, $phone, $userId]);
            } elseif ($role === 'Admin') {
                $stmt = $pdo->prepare("UPDATE admins SET name = ?, phone = ? WHERE admin_id = ?");
                $stmt->execute([$name, $phone, $userId]);
            }

            sendResponse(true, ['name' => $name, 'phone' => $phone], 'Profile updated successfully.');
            break;

        // ---------------------------------------------------------
        // 20. Change Password
        // ---------------------------------------------------------
        case 'change_password':
            $userId = trim($params['user_id'] ?? '');
            $role = ucfirst(strtolower(trim($params['role'] ?? 'Student')));
            $currentPassword = trim($params['current_password'] ?? '');
            $newPassword = trim($params['new_password'] ?? '');

            if (empty($userId) || empty($currentPassword) || empty($newPassword)) {
                sendResponse(false, null, 'All password fields are required.', 400);
            }

            $pdo = DataStore::getPdo();
            $table = ($role === 'Student') ? 'students' : (($role === 'Expert') ? 'experts' : (($role === 'Allocator') ? 'allocators' : 'admins'));
            $idCol = ($role === 'Student') ? 'student_id' : (($role === 'Expert') ? 'expert_id' : (($role === 'Allocator') ? 'allocator_id' : 'admin_id'));

            $stmt = $pdo->prepare("SELECT password FROM $table WHERE $idCol = ?");
            $stmt->execute([$userId]);
            $hash = $stmt->fetchColumn();

            $verified = false;
            if ($hash) {
                if (password_verify($currentPassword, $hash) || $hash === $currentPassword || md5($currentPassword) === $hash) {
                    $verified = true;
                }
            }

            if (!$verified) {
                sendResponse(false, null, 'Current password is incorrect.', 400);
            }

            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE $table SET password = ? WHERE $idCol = ?");
            $upd->execute([$newHash, $userId]);

            sendResponse(true, null, 'Password changed successfully.');
            break;

        // ---------------------------------------------------------
        // 21. Admin Toggle User Status (Block/Unblock)
        // ---------------------------------------------------------
        case 'toggle_user_status':
            $targetRole = ucfirst(strtolower(trim($params['target_role'] ?? 'Student')));
            $targetId = trim($params['target_id'] ?? '');
            $newStatus = trim($params['status'] ?? 'Active');

            if (empty($targetId)) {
                sendResponse(false, null, 'target_id is required.', 400);
            }

            $pdo = DataStore::getPdo();
            if ($targetRole === 'Student') {
                $stmt = $pdo->prepare("UPDATE students SET status = ? WHERE student_id = ?");
                $stmt->execute([$newStatus, $targetId]);
            } elseif ($targetRole === 'Expert') {
                $stmt = $pdo->prepare("UPDATE experts SET status = ? WHERE expert_id = ?");
                $stmt->execute([$newStatus, $targetId]);
            }

            sendResponse(true, ['target_id' => $targetId, 'status' => $newStatus], "User status updated to $newStatus.");
            break;


        // ---------------------------------------------------------
        // 22. Student Self-Registration (Mobile & Portal)
        // ---------------------------------------------------------

        // ---------------------------------------------------------
        // 22. Student Self-Registration (Mobile & Portal)
        // ---------------------------------------------------------
        case 'register':
            $name = trim($params['name'] ?? '');
            $email = trim($params['email'] ?? '');
            $password = trim($params['password'] ?? '');
            $phone = trim($params['phone'] ?? '');
            $country = trim($params['country'] ?? '');
            $university = trim($params['university'] ?? '');

            if (empty($name) || empty($email) || empty($password)) {
                sendResponse(false, null, 'Name, email, and password are required.', 400);
            }

            $pdo = DataStore::getPdo();
            $check = $pdo->prepare("SELECT student_id FROM students WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                sendResponse(false, null, 'An account with this email address already exists. Please sign in.', 400);
            }

            $last = $pdo->query("SELECT student_id FROM students ORDER BY id DESC LIMIT 1")->fetchColumn();
            $num = 1001;
            if ($last && preg_match('/STU-(\d+)/', $last, $matches)) {
                $num = intval($matches[1]) + 1;
            }
            $studentId = "STU-" . $num;

            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO students (student_id, name, email, phone, password, country, university, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'Active', NOW())");
            $stmt->execute([$studentId, $name, $email, $phone, $hashed, $country, $university]);

            add_audit_log('Student', $studentId, 'Student Self-Registration', "Registered student $studentId ($name, $email) from Mobile App");

            $token = base64_encode($studentId . ":Student:" . (time() + 86400 * 30));
            $user = [
                'id' => $studentId,
                'name' => $name,
                'email' => $email,
                'role' => 'Student',
                'phone' => $phone,
                'country' => $country,
                'university' => $university
            ];

            sendResponse(true, ['user' => $user, 'token' => $token], 'Registration successful! Welcome to Ace Assignment Helps.');
            break;

        // ---------------------------------------------------------
        // 23. Multipart Document Upload (Briefs, Drafts, Solutions)
        // ---------------------------------------------------------
        case 'upload_file':
            $assignmentId = trim($params['assignment_id'] ?? '');
            $fileStage = trim($params['file_stage'] ?? 'brief');
            $uploadedBy = trim($params['uploaded_by'] ?? 'User');
            $isInternal = !empty($params['is_internal']) ? 1 : 0;

            if (empty($assignmentId)) {
                sendResponse(false, null, 'assignment_id is required.', 400);
            }

            $inputKey = isset($_FILES['files']) ? 'files' : (isset($_FILES['file']) ? 'file' : 'assignment_file');
            $uploaded = handle_uploaded_files($inputKey, $assignmentId, $uploadedBy, $isInternal, $fileStage);

            if (!empty($uploaded)) {
                add_audit_log($uploadedBy, $assignmentId, 'Upload File', "Uploaded " . count($uploaded) . " file(s) [Stage: $fileStage] to $assignmentId");
                sendResponse(true, ['files' => $uploaded, 'count' => count($uploaded)], count($uploaded) . ' file(s) uploaded successfully!');
            } else {
                sendResponse(false, null, 'No files were received or upload failed.', 400);
            }
            break;

        // ---------------------------------------------------------
        // 24. Delete Assignment File
        // ---------------------------------------------------------
        case 'delete_file':
            $fileId = trim($params['file_id'] ?? '');
            if (empty($fileId)) {
                sendResponse(false, null, 'file_id is required.', 400);
            }
            $pdo = DataStore::getPdo();
            $fStmt = $pdo->prepare("SELECT path FROM files WHERE file_id = ?");
            $fStmt->execute([$fileId]);
            $path = $fStmt->fetchColumn();
            if ($path && file_exists(__DIR__ . '/' . $path)) {
                @unlink(__DIR__ . '/' . $path);
            }
            $del = $pdo->prepare("DELETE FROM files WHERE file_id = ?");
            $del->execute([$fileId]);
            sendResponse(true, null, 'File deleted successfully.');
            break;

        // ---------------------------------------------------------
        // 25. Submit Free Revision Request
        // ---------------------------------------------------------
        case 'request_revision':
            $assignmentId = trim($params['assignment_id'] ?? '');
            $studentId = trim($params['student_id'] ?? '');
            $instructions = trim($params['instructions'] ?? '');

            if (empty($assignmentId) || empty($instructions)) {
                sendResponse(false, null, 'assignment_id and instructions are required.', 400);
            }

            $pdo = DataStore::getPdo();
            $pdo->prepare("UPDATE assignments SET status = 'Revision', updated_at = NOW() WHERE assignment_id = ?")->execute([$assignmentId]);

            $noteId = "NOTE-" . rand(100, 999);
            $pdo->prepare("INSERT INTO notes (note_id, assignment_id, user_id, user_role, user_name, message, visibility, created_at) VALUES (?, ?, ?, 'Student', 'Student', ?, 'Public', NOW())")->execute([$noteId, $assignmentId, $studentId, "Revision Request: " . $instructions]);

            add_audit_log('Student', $studentId, 'Request Revision', "Requested revision for $assignmentId: $instructions");
            add_notification('Admin', '', "Revision Requested: $assignmentId", "Student requested a revision on order $assignmentId.", 'warning', "/admin/assignment-detail.php?id=$assignmentId");
            add_notification('Allocator', '', "Revision Requested: $assignmentId", "Student requested a revision on order $assignmentId.", 'warning', "/allocator/assignment-detail.php?id=$assignmentId");

            sendResponse(true, ['assignment_id' => $assignmentId, 'status' => 'Revision'], 'Revision request submitted successfully. Our team will review your instructions immediately.');
            break;

        // ---------------------------------------------------------
        // 26. Submit Refund Request
        // ---------------------------------------------------------
        case 'request_refund':
            $assignmentId = trim($params['assignment_id'] ?? '');
            $studentId = trim($params['student_id'] ?? '');
            $reason = trim($params['reason'] ?? '');
            $details = trim($params['details'] ?? '');

            if (empty($assignmentId) || empty($reason)) {
                sendResponse(false, null, 'assignment_id and reason are required.', 400);
            }

            $pdo = DataStore::getPdo();
            $ticketId = "TICK-" . rand(1000, 9999);
            $msg = "Refund Request for $assignmentId. Reason: $reason. Details: $details";
            $pdo->prepare("INSERT INTO support_tickets (ticket_id, user_id, user_role, subject, message, priority, status, created_at) VALUES (?, ?, 'Student', ?, ?, 'High', 'Open', NOW())")->execute([$ticketId, $studentId, "Refund Request: $assignmentId", $msg]);

            add_audit_log('Student', $studentId, 'Request Refund', "Submitted refund request for $assignmentId: $reason");
            add_notification('Admin', '', "Refund Request: $assignmentId", "Student $studentId submitted a refund request for $assignmentId: $reason", 'danger', "/admin/assignment-detail.php?id=$assignmentId");

            sendResponse(true, ['ticket_id' => $ticketId], 'Refund request submitted. A senior account manager will review your ticket within 24 hours.');
            break;

        // ---------------------------------------------------------
        // 27. Live WhatsApp & Support Chat (Get Messages)
        // ---------------------------------------------------------
        case 'get_chat':
            $studentId = trim($params['student_id'] ?? '');
            $assignmentId = trim($params['assignment_id'] ?? '');
            $pdo = DataStore::getPdo();

            if (!empty($studentId)) {
                $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE student_id = ? ORDER BY id ASC");
                $stmt->execute([$studentId]);
            } elseif (!empty($assignmentId)) {
                $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE assignment_id = ? ORDER BY id ASC");
                $stmt->execute([$assignmentId]);
            } else {
                $stmt = $pdo->query("SELECT * FROM chat_messages ORDER BY id DESC LIMIT 50");
            }
            $msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            sendResponse(true, $msgs, 'Chat messages retrieved.');
            break;

        // ---------------------------------------------------------
        // 28. Live WhatsApp & Support Chat (Send Message)
        // ---------------------------------------------------------
        case 'send_chat':
            $senderId = trim($params['sender_id'] ?? '');
            $senderRole = trim($params['sender_role'] ?? 'Student');
            $senderName = trim($params['sender_name'] ?? 'User');
            $studentId = trim($params['student_id'] ?? $senderId);
            $assignmentId = trim($params['assignment_id'] ?? '');
            $message = trim($params['message'] ?? '');

            if (empty($message)) {
                sendResponse(false, null, 'message cannot be empty.', 400);
            }

            $pdo = DataStore::getPdo();
            $msgId = "MSG-" . rand(10000, 99999);
            $stmt = $pdo->prepare("INSERT INTO chat_messages (msg_id, assignment_id, student_id, sender_id, sender_role, sender_name, message, is_read, timestamp) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())");
            $stmt->execute([$msgId, $assignmentId ?: null, $studentId, $senderId, $senderRole, $senderName, $message]);

            $inserted = [
                'msg_id' => $msgId,
                'assignment_id' => $assignmentId,
                'student_id' => $studentId,
                'sender_id' => $senderId,
                'sender_role' => $senderRole,
                'sender_name' => $senderName,
                'message' => $message,
                'timestamp' => date('Y-m-d H:i:s')
            ];

            sendResponse(true, $inserted, 'Message sent successfully.');
            break;

        // ---------------------------------------------------------
        // 29. Allocator QA Approval (Forward to Admin)
        // ---------------------------------------------------------
        case 'allocator_approve_qa':
            $assignmentId = trim($params['assignment_id'] ?? '');
            $allocatorId = trim($params['allocator_id'] ?? '');
            $allocatorName = trim($params['allocator_name'] ?? 'Allocator');

            if (empty($assignmentId)) {
                sendResponse(false, null, 'assignment_id is required.', 400);
            }

            $pdo = DataStore::getPdo();
            $pdo->prepare("UPDATE assignments SET status = 'Pending Admin Approval', updated_at = NOW() WHERE assignment_id = ?")->execute([$assignmentId]);

            add_audit_log('Allocator', $allocatorId, 'QA Approved', "Allocator $allocatorName approved solution QA for order $assignmentId and sent for Final Admin Approval");
            add_notification('Admin', '', "QA Approved - $assignmentId", "Allocator $allocatorName QA-approved order $assignmentId. Final Admin approval is required.", 'info', "/admin/assignment-detail.php?id=$assignmentId");

            sendResponse(true, ['assignment_id' => $assignmentId, 'status' => 'Pending Admin Approval'], 'QA Approved! Order forwarded for final Admin approval and student release.');
            break;

        // ---------------------------------------------------------
        // 30. Allocator Request Revision from Expert
        // ---------------------------------------------------------
        case 'allocator_request_revision':
            $assignmentId = trim($params['assignment_id'] ?? '');
            $allocatorId = trim($params['allocator_id'] ?? '');
            $allocatorName = trim($params['allocator_name'] ?? 'Allocator');
            $instructions = trim($params['instructions'] ?? '');

            if (empty($assignmentId) || empty($instructions)) {
                sendResponse(false, null, 'assignment_id and instructions are required.', 400);
            }

            $pdo = DataStore::getPdo();
            $pdo->prepare("UPDATE assignments SET status = 'Revision Required', updated_at = NOW() WHERE assignment_id = ?")->execute([$assignmentId]);

            $noteId = "NOTE-" . rand(100, 999);
            $pdo->prepare("INSERT INTO notes (note_id, assignment_id, user_id, user_role, user_name, message, visibility, created_at) VALUES (?, ?, ?, 'Allocator', ?, ?, 'Internal', NOW())")->execute([$noteId, $assignmentId, $allocatorId, $allocatorName, "QA Revision Request: $instructions"]);

            add_audit_log('Allocator', $allocatorId, 'QA Revision Requested', "Allocator requested QA revision for $assignmentId: $instructions");

            sendResponse(true, ['assignment_id' => $assignmentId, 'status' => 'Revision Required'], 'Revision request sent back to expert.');
            break;

        // ---------------------------------------------------------
        // 31. Admin Final Solution Release
        // ---------------------------------------------------------
        case 'admin_release_solution':
            $assignmentId = trim($params['assignment_id'] ?? '');
            $adminId = trim($params['admin_id'] ?? '');

            if (empty($assignmentId)) {
                sendResponse(false, null, 'assignment_id is required.', 400);
            }

            $pdo = DataStore::getPdo();
            $pdo->prepare("UPDATE assignments SET status = 'Completed', updated_at = NOW() WHERE assignment_id = ?")->execute([$assignmentId]);

            $pdo->prepare("UPDATE files SET file_stage = 'complete' WHERE assignment_id = ? AND file_stage IN ('draft', 'solution')")->execute([$assignmentId]);

            add_audit_log('Admin', $adminId, 'Final Admin Approval', "Admin released final verified solution for order $assignmentId to student");
            add_notification('Student', '', "Solution Delivered - $assignmentId", "Your completed solution for order $assignmentId has been verified and released! You can now download it.", 'success', "/student/assignment-detail.php?id=$assignmentId");

            sendResponse(true, ['assignment_id' => $assignmentId, 'status' => 'Completed'], 'Solution approved and released to student successfully!');
            break;

        // ---------------------------------------------------------
        // 32. Admin Accounts Management
        // ---------------------------------------------------------
        case 'admins_list':
            $pdo = DataStore::getPdo();
            $admins = $pdo->query("SELECT id, admin_id, name, email, phone, status FROM admins ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
            sendResponse(true, $admins, 'Admin accounts retrieved.');
            break;

        case 'admin_create':
            $name = trim($params['name'] ?? '');
            $email = trim($params['email'] ?? '');
            $password = trim($params['password'] ?? '');
            $phone = trim($params['phone'] ?? '');

            if (empty($name) || empty($email) || empty($password)) {
                sendResponse(false, null, 'Name, email, and password are required.', 400);
            }

            $pdo = DataStore::getPdo();
            $chk = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
            $chk->execute([$email]);
            if ($chk->fetch()) {
                sendResponse(false, null, 'Admin with this email already exists.', 400);
            }

            $adminId = "ADM-" . sprintf("%03d", rand(10, 999));
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admins (admin_id, name, email, phone, password, status) VALUES (?, ?, ?, ?, ?, 'Active')");
            $stmt->execute([$adminId, $name, $email, $phone, $hashed]);

            sendResponse(true, ['admin_id' => $adminId, 'name' => $name, 'email' => $email], 'Admin user created successfully.');
            break;

        case 'admin_delete':
            $adminId = trim($params['admin_id'] ?? '');
            if (empty($adminId)) {
                sendResponse(false, null, 'admin_id is required.', 400);
            }
            $pdo = DataStore::getPdo();
            $pdo->prepare("DELETE FROM admins WHERE admin_id = ?")->execute([$adminId]);
            sendResponse(true, null, 'Admin deleted successfully.');
            break;

        // ---------------------------------------------------------
        // 33. Allocators Staff Management
        // ---------------------------------------------------------
        case 'allocator_create':
            $name = trim($params['name'] ?? '');
            $email = trim($params['email'] ?? '');
            $password = trim($params['password'] ?? '');
            $phone = trim($params['phone'] ?? '');

            if (empty($name) || empty($email) || empty($password)) {
                sendResponse(false, null, 'Name, email, and password are required.', 400);
            }

            $pdo = DataStore::getPdo();
            $chk = $pdo->prepare("SELECT id FROM allocators WHERE email = ?");
            $chk->execute([$email]);
            if ($chk->fetch()) {
                sendResponse(false, null, 'Allocator with this email already exists.', 400);
            }

            $allocId = "ALL-" . sprintf("%03d", rand(500, 999));
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO allocators (allocator_id, name, email, phone, password, status) VALUES (?, ?, ?, ?, ?, 'Active')");
            $stmt->execute([$allocId, $name, $email, $phone, $hashed]);

            sendResponse(true, ['allocator_id' => $allocId, 'name' => $name, 'email' => $email], 'Allocator created successfully.');
            break;

        case 'allocator_delete':
            $allocId = trim($params['allocator_id'] ?? '');
            if (empty($allocId)) {
                sendResponse(false, null, 'allocator_id is required.', 400);
            }
            $pdo = DataStore::getPdo();
            $pdo->prepare("DELETE FROM allocators WHERE allocator_id = ?")->execute([$allocId]);
            sendResponse(true, null, 'Allocator deleted successfully.');
            break;

        // ---------------------------------------------------------
        // 34. Expert Directory & Payout Management
        // ---------------------------------------------------------
        case 'expert_create':
            $name = trim($params['name'] ?? '');
            $email = trim($params['email'] ?? '');
            $password = trim($params['password'] ?? '');
            $phone = trim($params['phone'] ?? '');
            $subjects = trim($params['subjects'] ?? '');

            if (empty($name) || empty($email) || empty($password)) {
                sendResponse(false, null, 'Name, email, and password are required.', 400);
            }

            $pdo = DataStore::getPdo();
            $chk = $pdo->prepare("SELECT id FROM experts WHERE email = ?");
            $chk->execute([$email]);
            if ($chk->fetch()) {
                sendResponse(false, null, 'Expert with this email already exists.', 400);
            }

            $expId = "EXP-" . sprintf("%03d", rand(300, 999));
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO experts (expert_id, name, email, phone, password, subjects, status, rating, completed_count) VALUES (?, ?, ?, ?, ?, ?, 'Available', 5.00, 0)");
            $stmt->execute([$expId, $name, $email, $phone, $hashed, $subjects]);

            sendResponse(true, ['expert_id' => $expId, 'name' => $name, 'email' => $email], 'Expert created successfully.');
            break;

        case 'expert_delete':
            $expId = trim($params['expert_id'] ?? '');
            if (empty($expId)) {
                sendResponse(false, null, 'expert_id is required.', 400);
            }
            $pdo = DataStore::getPdo();
            $pdo->prepare("DELETE FROM experts WHERE expert_id = ?")->execute([$expId]);
            sendResponse(true, null, 'Expert deleted successfully.');
            break;

        case 'expert_update_payout':
            $expId = trim($params['expert_id'] ?? '');
            $payoutInfo = trim($params['payout_info'] ?? '');
            if (empty($expId)) {
                sendResponse(false, null, 'expert_id is required.', 400);
            }
            $pdo = DataStore::getPdo();
            $pdo->prepare("UPDATE experts SET bio = CONCAT(COALESCE(bio, ''), '\n[Payout Details: ', ?, ']') WHERE expert_id = ?")->execute([$payoutInfo, $expId]);
            sendResponse(true, null, 'Payout details saved successfully.');
            break;

        // ---------------------------------------------------------
        // 35. Audit Logs
        // ---------------------------------------------------------
        case 'audit_logs':
            $pdo = DataStore::getPdo();
            $logs = $pdo->query("SELECT * FROM audit_logs ORDER BY id DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
            sendResponse(true, $logs, 'Audit logs retrieved.');
            break;

        // ---------------------------------------------------------
        // 36. Blogs Management
        // ---------------------------------------------------------
        case 'blogs_list':
            $pdo = DataStore::getPdo();
            $blogs = $pdo->query("SELECT * FROM blogs ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
            sendResponse(true, $blogs, 'Blogs retrieved.');
            break;

        case 'blog_create':
            $title = trim($params['title'] ?? '');
            $excerpt = trim($params['excerpt'] ?? '');
            $category = trim($params['category'] ?? 'Academic Writing');
            $author = trim($params['author'] ?? 'Editorial Team');
            $image = trim($params['image'] ?? 'assets/images/blog1.jpg');

            if (empty($title)) {
                sendResponse(false, null, 'Title is required.', 400);
            }

            $pdo = DataStore::getPdo();
            $stmt = $pdo->prepare("INSERT INTO blogs (title, excerpt, category, author, published_at, image) VALUES (?, ?, ?, ?, CURDATE(), ?)");
            $stmt->execute([$title, $excerpt, $category, $author, $image]);
            sendResponse(true, ['id' => $pdo->lastInsertId(), 'title' => $title], 'Blog article created successfully.');
            break;

        case 'blog_delete':
            $id = intval($params['id'] ?? 0);
            if (!$id) {
                sendResponse(false, null, 'id is required.', 400);
            }
            $pdo = DataStore::getPdo();
            $pdo->prepare("DELETE FROM blogs WHERE id = ?")->execute([$id]);
            sendResponse(true, null, 'Blog article deleted successfully.');
            break;

        // ---------------------------------------------------------
        // 37. Site Settings Management
        // ---------------------------------------------------------
        case 'site_settings':
            $pdo = DataStore::getPdo();
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($params['save_settings'])) {
                $allowed = ['site_name', 'default_currency', 'whatsapp_phone', 'support_email', 'base_rate_per_word'];
                foreach ($allowed as $k) {
                    if (isset($params[$k])) {
                        DataStore::setSetting($k, $params[$k]);
                    }
                }
                sendResponse(true, null, 'Settings updated successfully.');
            } else {
                $settings = [
                    'site_name' => DataStore::getSetting('site_name', 'Ace Assignment Helps'),
                    'default_currency' => DataStore::getSetting('default_currency', 'USD'),
                    'whatsapp_phone' => DataStore::getSetting('whatsapp_phone', '+91 8233432123'),
                    'support_email' => DataStore::getSetting('support_email', 'support@aceassignmenthelps.com'),
                    'base_rate_per_word' => DataStore::getSetting('base_rate_per_word', '0.05'),
                ];
                sendResponse(true, $settings, 'Settings retrieved.');
            }
            break;

        // ---------------------------------------------------------
        // 38. Partial / Balance Payments
        // ---------------------------------------------------------
        case 'process_partial_payment':
            $assignmentId = trim($params['assignment_id'] ?? '');
            $studentId = trim($params['student_id'] ?? '');
            $amount = floatval($params['amount'] ?? 0);
            $method = trim($params['payment_method'] ?? 'Stripe / Online');
            $currency = trim($params['currency'] ?? 'USD');

            if (empty($assignmentId) || $amount <= 0) {
                sendResponse(false, null, 'assignment_id and valid amount are required.', 400);
            }

            $pdo = DataStore::getPdo();
            $paymentId = "PAY-" . rand(10000, 99999);
            $stmt = $pdo->prepare("INSERT INTO payments (payment_id, assignment_id, student_id, amount, currency, status, payment_method, transaction_id, created_at) VALUES (?, ?, ?, ?, ?, 'Paid', ?, CONCAT('tx_', UUID_SHORT()), NOW())");
            $stmt->execute([$paymentId, $assignmentId, $studentId, $amount, $currency, $method]);

            $pdo->prepare("UPDATE assignments SET paid_amount = COALESCE(paid_amount, 0) + ?, status = CASE WHEN (COALESCE(paid_amount, 0) + ?) >= COALESCE(price, 0) AND status IN ('Waiting for Payment', 'New') THEN 'Confirmed' ELSE status END, updated_at = NOW() WHERE assignment_id = ?")->execute([$amount, $amount, $assignmentId]);

            add_audit_log('Student', $studentId, 'Payment Received', "Paid $currency $amount for $assignmentId via $method (TX: $paymentId)");

            sendResponse(true, ['payment_id' => $paymentId, 'amount' => $amount], 'Payment processed successfully!');
            break;

        // ---------------------------------------------------------
        // 39. Official Tax Invoice Detail
        // ---------------------------------------------------------
        case 'invoice_detail':
            $assignmentId = trim($params['assignment_id'] ?? '');
            if (empty($assignmentId)) {
                sendResponse(false, null, 'assignment_id is required.', 400);
            }

            $pdo = DataStore::getPdo();
            $asmStmt = $pdo->prepare("SELECT * FROM assignments WHERE assignment_id = ?");
            $asmStmt->execute([$assignmentId]);
            $asm = $asmStmt->fetch(PDO::FETCH_ASSOC);

            if (!$asm) {
                sendResponse(false, null, 'Assignment not found.', 404);
            }

            $stuStmt = $pdo->prepare("SELECT student_id, name, email, phone, country, university FROM students WHERE student_id = ?");
            $stuStmt->execute([$asm['student_id']]);
            $student = $stuStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $payStmt = $pdo->prepare("SELECT * FROM payments WHERE assignment_id = ? ORDER BY id ASC");
            $payStmt->execute([$assignmentId]);
            $payments = $payStmt->fetchAll(PDO::FETCH_ASSOC);

            sendResponse(true, [
                'assignment' => $asm,
                'student' => $student,
                'payments' => $payments,
                'invoice_url' => "http://localhost:8001/student/invoice.php?id=" . urlencode($assignmentId)
            ], 'Invoice details retrieved.');
            break;


        default:
            sendResponse(false, null, "Unknown or missing action ''. Available actions: health, login, dashboard, assignments, assignment_detail, update_status, notifications, submit_assignment, allocate_expert, allocator_review, expert_action, experts_list, students_list, allocators_list, payments_list, coupons, courses, support_tickets, update_profile, change_password, toggle_user_status.", 400);
            break;
    }
} catch (Throwable $e) {
    error_log("portal_api.php error: " . $e->getMessage());
    sendResponse(false, null, 'Internal Server Error: ' . $e->getMessage(), 500);
}
