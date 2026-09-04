<?php
header('Content-Type: application/json');
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'price_calc':
        $word_count = $_REQUEST['word_count'] ?? 250;
        $deadline_hours = $_REQUEST['deadline_hours'] ?? 72;
        $academic_level = $_REQUEST['academic_level'] ?? 'Undergraduate';
        $subject = $_REQUEST['subject'] ?? 'General';
        $coupon_code = $_REQUEST['coupon_code'] ?? '';

        $calc = calculate_assignment_price($word_count, $deadline_hours, $academic_level, $subject, $coupon_code);
        echo json_encode(['success' => true, 'data' => $calc]);
        exit;

    case 'submit_assignment':
        $user = Auth::currentUser();
        $student_id = $user ? $user['id'] : 'STU-' . rand(1004, 9999);
        
        $title = trim($_POST['title'] ?? 'Untitled Assignment');
        $subject = trim($_POST['subject'] ?? 'Computer Science');
        $assignment_type = trim($_POST['assignment_type'] ?? 'Essay');
        $deadline_hours = (int)($_POST['deadline_hours'] ?? 72);
        $deadline_date = date('Y-m-d H:i:s', strtotime("+{$deadline_hours} hours"));
        $timezone = trim($_POST['timezone'] ?? 'EST (UTC-5)');
        $word_count = (int)($_POST['word_count'] ?? 1000);
        $pages = ceil($word_count / 250);
        $reference_style = trim($_POST['reference_style'] ?? 'APA 7th');
        $priority = trim($_POST['priority'] ?? 'Normal');
        $language = trim($_POST['language'] ?? 'English (US)');
        $instructions = trim($_POST['instructions'] ?? '');
        $coupon_code = trim($_POST['discount_code'] ?? '');
        $country = trim($_POST['country'] ?? ($user['country'] ?? 'United States'));
        $university = trim($_POST['university'] ?? 'Stanford University');

        $calc = calculate_assignment_price($word_count, $deadline_hours, 'Undergraduate', $subject, $coupon_code);
        $assignment_id = generate_assignment_id();

        $assignment_data = [
            'assignment_id' => $assignment_id,
            'student_id' => $student_id,
            'title' => $title,
            'subject' => $subject,
            'assignment_type' => $assignment_type,
            'deadline' => $deadline_date,
            'timezone' => $timezone,
            'word_count' => $word_count,
            'pages' => $pages,
            'reference_style' => $reference_style,
            'priority' => $priority,
            'language' => $language,
            'instructions' => $instructions,
            'price' => $calc['subtotal'],
            'discount_code' => $coupon_code,
            'final_price' => $calc['final_price'],
            'status' => 'Pending Review',
            'allocator_id' => '',
            'expert_id' => '',
            'country' => $country,
            'university' => $university,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        DataStore::insert('assignments', $assignment_data);

        // Handle uploaded file if present
        if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] === UPLOAD_ERR_OK) {
            $fileName = basename($_FILES['assignment_file']['name']);
            $targetDir = __DIR__ . '/assets/uploads/';
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $targetPath = $targetDir . time() . '_' . $fileName;
            if (move_uploaded_file($_FILES['assignment_file']['tmp_name'], $targetPath)) {
                DataStore::insert('files', [
                    'file_id' => 'FILE-' . rand(200, 999),
                    'assignment_id' => $assignment_id,
                    'file_name' => $fileName,
                    'path' => 'assets/uploads/' . basename($targetPath),
                    'file_type' => pathinfo($fileName, PATHINFO_EXTENSION),
                    'uploaded_by' => 'Student',
                    'upload_date' => date('Y-m-d H:i:s'),
                    'is_internal' => false
                ]);
            }
        }

        add_audit_log('Student', $student_id, 'Submit Assignment', "Created $assignment_id: $title");

        echo json_encode([
            'success' => true,
            'assignment_id' => $assignment_id,
            'message' => "Assignment $assignment_id submitted successfully!"
        ]);
        exit;

    case 'allocate_expert':
        Auth::checkRole(['Allocator', 'Admin']);
        $user = Auth::currentUser();
        $assignment_id = $_POST['assignment_id'] ?? '';
        $expert_id = $_POST['expert_id'] ?? '';
        $allocator_id = $user['id'];
        $notes = trim($_POST['notes'] ?? '');

        if ($assignment_id && $expert_id) {
            DataStore::update('assignments', 'assignment_id', $assignment_id, [
                'expert_id' => $expert_id,
                'allocator_id' => $allocator_id,
                'status' => 'Allocated',
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            DataStore::insert('allocation', [
                'allocation_id' => 'ALC-' . rand(1000, 9999),
                'assignment_id' => $assignment_id,
                'expert_id' => $expert_id,
                'allocator_id' => $allocator_id,
                'allocated_date' => date('Y-m-d H:i:s'),
                'deadline' => date('Y-m-d H:i:s', strtotime('+48 hours')),
                'status' => 'Active'
            ]);

            if (!empty($notes)) {
                DataStore::insert('notes', [
                    'note_id' => 'NOTE-' . rand(100, 999),
                    'assignment_id' => $assignment_id,
                    'user_id' => $allocator_id,
                    'user_role' => $user['role'],
                    'user_name' => $user['name'],
                    'message' => $notes,
                    'visibility' => 'Internal',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }

            add_audit_log($user['role'], $user['id'], 'Allocate Expert', "Allocated $assignment_id to $expert_id");
            echo json_encode(['success' => true, 'message' => "Assignment $assignment_id allocated to expert!"]);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Missing assignment_id or expert_id']);
        exit;

    case 'update_status':
        Auth::checkRole(['Allocator', 'Admin', 'Student']);
        $user = Auth::currentUser();
        $assignment_id = $_POST['assignment_id'] ?? '';
        $new_status = $_POST['status'] ?? '';

        if ($assignment_id && $new_status) {
            DataStore::update('assignments', 'assignment_id', $assignment_id, [
                'status' => $new_status,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            add_audit_log($user['role'], $user['id'], 'Update Status', "Changed $assignment_id status to $new_status");
            echo json_encode(['success' => true, 'message' => "Status updated to $new_status"]);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit;

    case 'pay_now':
        Auth::checkRole(['Student', 'Admin']);
        $user = Auth::currentUser();
        $assignment_id = $_POST['assignment_id'] ?? '';
        $method = $_POST['payment_method'] ?? 'Stripe Credit Card';

        $asm = DataStore::findOne('assignments', 'assignment_id', $assignment_id);
        if ($asm) {
            $tx_id = 'ch_' . substr(md5(uniqid()), 0, 16);
            DataStore::insert('payments', [
                'payment_id' => 'PAY-' . rand(8900, 9999),
                'assignment_id' => $assignment_id,
                'student_id' => $asm['student_id'],
                'amount' => (float)$asm['final_price'],
                'currency' => 'USD',
                'status' => 'Paid',
                'payment_method' => $method,
                'transaction_id' => $tx_id,
                'payment_date' => date('Y-m-d H:i:s')
            ]);

            DataStore::update('assignments', 'assignment_id', $assignment_id, [
                'status' => 'Confirmed',
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            add_audit_log($user['role'], $user['id'], 'Payment Received', "Paid \${$asm['final_price']} for $assignment_id");
            echo json_encode(['success' => true, 'message' => 'Payment successful! Status changed to Confirmed.']);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Assignment not found']);
        exit;

    case 'send_chat_message':
        $user = Auth::currentUser();
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }
        $msg_text = trim($_POST['message'] ?? '');
        $student_id = trim($_POST['student_id'] ?? '');
        if (!$student_id && $user['role'] === 'Student') {
            $student_id = $user['id'];
        }
        $assignment_id = trim($_POST['assignment_id'] ?? '');

        if (!empty($msg_text)) {
            $msg_id = 'MSG-' . rand(1000, 9999);
            $new_msg = [
                'msg_id' => $msg_id,
                'student_id' => $student_id ?: $user['id'],
                'sender_id' => $user['id'],
                'sender_role' => $user['role'],
                'sender_name' => $user['name'],
                'message' => $msg_text,
                'assignment_id' => $assignment_id,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            DataStore::insert('chat_messages', $new_msg);

            // Fetch full thread for return
            $thread = DataStore::filter('chat_messages', function($m) use ($student_id, $user) {
                $targetStudent = $student_id ?: $user['id'];
                return isset($m['student_id']) && $m['student_id'] === $targetStudent;
            });
            
            echo json_encode(['success' => true, 'data' => $new_msg, 'messages' => array_values($thread)]);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Empty message']);
        exit;

    case 'get_chat_messages':
        $user = Auth::currentUser();
        if (!$user) {
            echo json_encode(['success' => false, 'messages' => []]);
            exit;
        }
        $student_id = trim($_REQUEST['student_id'] ?? '');
        if (!$student_id && $user['role'] === 'Student') {
            $student_id = $user['id'];
        }
        
        $messages = DataStore::filter('chat_messages', function($m) use ($student_id, $user) {
            $targetStudent = $student_id ?: $user['id'];
            return isset($m['student_id']) && $m['student_id'] === $targetStudent;
        });

        echo json_encode(['success' => true, 'messages' => array_values($messages)]);
        exit;

    case 'request_revision':
        Auth::checkRole('Student');
        $user = Auth::currentUser();
        $assignment_id = $_POST['assignment_id'] ?? '';
        $revision_instructions = trim($_POST['instructions'] ?? '');

        if ($assignment_id && !empty($revision_instructions)) {
            DataStore::update('assignments', 'assignment_id', $assignment_id, [
                'status' => 'Revision Requested',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            DataStore::insert('notes', [
                'note_id' => 'NOTE-' . rand(100, 999),
                'assignment_id' => $assignment_id,
                'user_id' => $user['id'],
                'user_role' => 'Student',
                'user_name' => $user['name'],
                'message' => "Revision Request: " . $revision_instructions,
                'visibility' => 'Internal',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            add_audit_log('Student', $user['id'], 'Request Revision', "Requested revision for $assignment_id");
            echo json_encode(['success' => true, 'message' => 'Revision request submitted! Support and allocator team notified.']);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Missing revision instructions']);
        exit;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown API action']);
        exit;
}
