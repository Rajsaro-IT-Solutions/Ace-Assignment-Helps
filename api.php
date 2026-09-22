<?php
header('Content-Type: application/json');
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'price_calc':
        $word_count = (int)($_REQUEST['word_count'] ?? 250);
        $deadline_hours = (float)($_REQUEST['deadline_hours'] ?? 120);
        $academic_level = $_REQUEST['academic_level'] ?? 'Undergraduate';
        $subject = $_REQUEST['subject'] ?? 'General';
        $coupon_code = $_REQUEST['coupon_code'] ?? '';
        $currency = strtoupper(trim($_REQUEST['currency'] ?? 'USD'));

        $calc = calculate_assignment_price($word_count, $deadline_hours, $academic_level, $subject, $coupon_code, $currency);
        echo json_encode(['success' => true, 'data' => $calc]);
        exit;

    case 'validate_coupon':
        $code = strtoupper(trim($_REQUEST['code'] ?? ($_REQUEST['coupon_code'] ?? '')));
        if (empty($code)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a coupon code.']);
            exit;
        }
        $coupon = DataStore::findOne('coupons', 'code', $code);
        if (!$coupon) {
            echo json_encode(['success' => false, 'message' => "Coupon code '{$code}' does not exist."]);
            exit;
        }
        if (($coupon['status'] ?? 'Active') !== 'Active') {
            echo json_encode(['success' => false, 'message' => "Coupon '{$code}' is currently inactive or disabled."]);
            exit;
        }
        if (!empty($coupon['expires_at']) && strtotime($coupon['expires_at'] . ' 23:59:59') < time()) {
            echo json_encode(['success' => false, 'message' => "Coupon '{$code}' expired on " . htmlspecialchars($coupon['expires_at']) . "."]);
            exit;
        }
        if (!empty($coupon['max_uses']) && (int)($coupon['current_uses'] ?? 0) >= (int)$coupon['max_uses']) {
            echo json_encode(['success' => false, 'message' => "Coupon '{$code}' has reached its maximum usage limit."]);
            exit;
        }
        echo json_encode([
            'success' => true,
            'coupon' => [
                'code' => $coupon['code'],
                'discount_percent' => (float)$coupon['discount_percent'],
                'expires_at' => $coupon['expires_at'] ?? '',
                'message' => "Coupon {$coupon['code']} Applied! {$coupon['discount_percent']}% Discount Activated."
            ]
        ]);
        exit;

    case 'apply_checkout_coupon':
        $asmId = trim($_POST['assignment_id'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        if (!$asmId) {
            echo json_encode(['success' => false, 'message' => 'Assignment ID is required.']);
            exit;
        }
        $asm = DataStore::findOne('assignments', 'assignment_id', $asmId);
        if (!$asm) {
            echo json_encode(['success' => false, 'message' => 'Assignment not found.']);
            exit;
        }

        $currency = $asm['currency'] ?? 'USD';
        $deadline_hours = 120.0;
        if (!empty($asm['deadline'])) {
            $diffHours = (strtotime($asm['deadline']) - time()) / 3600.0;
            $deadline_hours = max(1.0, $diffHours);
        }

        $calc = calculate_assignment_price(
            $asm['word_count'] ?? 1000,
            $deadline_hours,
            $asm['academic_level'] ?? 'Undergraduate',
            $asm['subject'] ?? 'General',
            $code,
            $currency
        );

        if (!empty($code) && !$calc['coupon_valid']) {
            echo json_encode([
                'success' => false,
                'message' => $calc['coupon_message'] ?: "Invalid or expired coupon code '{$code}'."
            ]);
            exit;
        }

        // Update assignment in database
        DataStore::update('assignments', 'assignment_id', $asmId, [
            'price' => $calc['subtotal'],
            'discount_code' => $calc['coupon_code'],
            'final_price' => $calc['final_price'],
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        echo json_encode([
            'success' => true,
            'message' => !empty($calc['coupon_code']) ? $calc['coupon_message'] : 'Coupon removed.',
            'coupon_code' => $calc['coupon_code'],
            'discount_percent' => $calc['discount_percent'],
            'subtotal' => $calc['subtotal'],
            'discount_amount' => $calc['discount_amount'],
            'final_price' => $calc['final_price'],
            'currency' => $currency,
            'formatted_subtotal' => format_currency_amount($calc['subtotal'], $currency),
            'formatted_discount' => format_currency_amount($calc['discount_amount'], $currency),
            'formatted_final_price' => format_currency_amount($calc['final_price'], $currency)
        ]);
        exit;

    case 'submit_assignment':
        $user = Auth::currentUser();
        $student_id = $user ? $user['id'] : 'STU-' . rand(1004, 9999);
        
        $title = trim($_POST['title'] ?? 'Untitled Assignment');
        $subject = trim($_POST['subject'] ?? 'Computer Science');
        $assignment_type = trim($_POST['assignment_type'] ?? 'Essay');
        $deadline_hours = (float)($_POST['deadline_hours'] ?? 120);
        $deadline_date = date('Y-m-d H:i:s', strtotime("+{$deadline_hours} hours"));
        $timezone = trim($_POST['timezone'] ?? 'EST (UTC-5)');
        $word_count = (int)($_POST['word_count'] ?? 1000);
        $pages = ceil($word_count / 250);
        $reference_style = trim($_POST['reference_style'] ?? 'APA 7th');
        $priority = trim($_POST['priority'] ?? 'Normal');
        $language = trim($_POST['language'] ?? 'English (US)');
        $instructions = trim($_POST['instructions'] ?? '');
        $coupon_code = trim($_POST['discount_code'] ?? '');
        $currency = strtoupper(trim($_POST['currency'] ?? 'USD'));
        $country = trim($_POST['country'] ?? ($user['country'] ?? 'United States'));
        $university = trim($_POST['university'] ?? 'Stanford University');

        $calc = calculate_assignment_price($word_count, $deadline_hours, 'Undergraduate', $subject, $coupon_code, $currency);
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
            'currency' => $currency,
            'price' => $calc['subtotal'],
            'discount_code' => $calc['coupon_valid'] ? $calc['coupon_code'] : '',
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

        // If a valid coupon was applied, increment current_uses
        if ($calc['coupon_valid'] && !empty($calc['coupon_code'])) {
            $cpn = DataStore::findOne('coupons', 'code', $calc['coupon_code']);
            if ($cpn) {
                $currUses = (int)($cpn['current_uses'] ?? 0) + 1;
                DataStore::update('coupons', 'coupon_id', $cpn['coupon_id'], ['current_uses' => $currUses]);
            }
        }

        // Handle uploaded files of ANY format (single or multiple)
        if (!empty($_FILES)) {
            handle_uploaded_files('assignment_files', $assignment_id, 'Student');
            handle_uploaded_files('assignment_file', $assignment_id, 'Student');
            handle_uploaded_files('files', $assignment_id, 'Student');
            handle_uploaded_files('file', $assignment_id, 'Student');
        }

        add_audit_log('Student', $student_id, 'Submit Assignment', "Created $assignment_id: $title ($currency {$calc['final_price']})");
        add_notification('Allocator', '', "New Assignment Received", "Assignment $assignment_id ($title) has been submitted and awaits allocation.", 'info', "/allocator/assignment-detail.php?id=$assignment_id");

        echo json_encode([
            'success' => true,
            'assignment_id' => $assignment_id,
            'currency' => $currency,
            'final_price' => $calc['final_price'],
            'message' => "Assignment $assignment_id submitted successfully!"
        ]);
        exit;

    case 'upload_assignment_file':
        Auth::checkLoggedIn();
        $user = Auth::currentUser();
        $assignment_id = trim($_POST['assignment_id'] ?? '');
        $is_internal = !empty($_POST['is_internal']);

        if ($assignment_id) {
            $inputKey = isset($_FILES['files']) ? 'files' : (isset($_FILES['file']) ? 'file' : (isset($_FILES['assignment_files']) ? 'assignment_files' : 'assignment_file'));
            $uploaded = handle_uploaded_files($inputKey, $assignment_id, $user['name'] . ' (' . $user['role'] . ')', $is_internal);
            if (!empty($uploaded)) {
                add_audit_log($user['role'], $user['id'], 'Upload File', "Uploaded " . count($uploaded) . " file(s) to $assignment_id");
                echo json_encode(['success' => true, 'message' => count($uploaded) . ' file(s) uploaded successfully!', 'files' => $uploaded]);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'No files were uploaded or an error occurred.']);
                exit;
            }
        }
        echo json_encode(['success' => false, 'message' => 'Assignment ID required.']);
        exit;

    case 'delete_assignment_file':
        Auth::checkRole('Admin');
        $user = Auth::currentUser();
        $file_id = trim($_POST['file_id'] ?? '');
        if ($file_id) {
            $f = DataStore::findOne('files', 'file_id', $file_id);
            if ($f && !empty($f['path']) && file_exists(__DIR__ . '/' . $f['path'])) {
                @unlink(__DIR__ . '/' . $f['path']);
            }
            DataStore::delete('files', 'file_id', $file_id);
            add_audit_log('Admin', $user['id'], 'Delete File', "Deleted file $file_id");
            echo json_encode(['success' => true, 'message' => 'File deleted successfully.']);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'File ID required.']);
        exit;

    case 'permanent_delete_assignment':
        Auth::checkRole('Admin');
        $user = Auth::currentUser();
        $assignment_id = trim($_POST['assignment_id'] ?? '');
        if ($assignment_id) {
            $purged = DataStore::purgeAssignment($assignment_id);
            if ($purged) {
                add_audit_log('Admin', $user['id'], 'Permanent Delete Assignment', "Permanently wiped assignment $assignment_id from everywhere via API");
                echo json_encode(['success' => true, 'message' => "Assignment $assignment_id permanently deleted from everywhere."]);
                exit;
            }
            echo json_encode(['success' => false, 'message' => "Failed to permanently delete assignment $assignment_id."]);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Assignment ID required.']);
        exit;

    case 'delete_assignment':
        Auth::checkRole('Admin');
        $user = Auth::currentUser();
        $assignment_id = trim($_POST['assignment_id'] ?? '');
        if ($assignment_id) {
            DataStore::update('assignments', 'assignment_id', $assignment_id, [
                'status' => 'Deleted',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            DataStore::update('payments', 'assignment_id', $assignment_id, [
                'status' => 'Cancelled'
            ]);
            add_audit_log('Admin', $user['id'], 'Delete Assignment', "Moved assignment $assignment_id to History via API");
            echo json_encode(['success' => true, 'message' => "Assignment $assignment_id moved to History."]);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Assignment ID required.']);
        exit;

    case 'restore_assignment':
        Auth::checkRole('Admin');
        $user = Auth::currentUser();
        $assignment_id = trim($_POST['assignment_id'] ?? '');
        if ($assignment_id) {
            DataStore::update('assignments', 'assignment_id', $assignment_id, [
                'status' => 'Pending Review',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            add_audit_log('Admin', $user['id'], 'Restore Assignment', "Restored assignment $assignment_id from History via API");
            echo json_encode(['success' => true, 'message' => "Assignment $assignment_id restored to active status."]);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Assignment ID required.']);
        exit;

    case 'allocate_expert':
        Auth::checkRole(['Allocator', 'Admin']);
        $user = Auth::currentUser();
        $assignment_id = $_POST['assignment_id'] ?? '';
        $expert_id = $_POST['expert_id'] ?? '';
        $allocator_id = $user['id'];
        $notes = trim($_POST['notes'] ?? '');

        if ($assignment_id && $expert_id) {
            $chosenStatus = !empty($_POST['status']) ? trim($_POST['status']) : 'Allocated';
            DataStore::update('assignments', 'assignment_id', $assignment_id, [
                'expert_id' => $expert_id,
                'allocator_id' => $allocator_id,
                'status' => $chosenStatus,
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

    case 'allocator_approve_qa':
        Auth::checkRole(['Allocator', 'Admin']);
        $user = Auth::currentUser();
        $assignment_id = trim($_POST['assignment_id'] ?? '');
        if (!$assignment_id) {
            echo json_encode(['success' => false, 'message' => 'Assignment ID required']);
            exit;
        }
        $asm = DataStore::findOne('assignments', 'assignment_id', $assignment_id);
        if (!$asm) {
            echo json_encode(['success' => false, 'message' => 'Assignment not found']);
            exit;
        }
        DataStore::update('assignments', 'assignment_id', $assignment_id, [
            'status' => 'Pending Admin Approval',
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        add_audit_log('Allocator', $user['id'], 'QA Approved', "Allocator {$user['name']} approved solution QA for order $assignment_id and sent for Final Admin Approval");
        add_notification('Admin', '', "QA Approved - $assignment_id", "Allocator {$user['name']} has QA-approved the solution for order $assignment_id. Final Admin approval is required to release to student.", 'info', "/admin/assignment-detail.php?id=$assignment_id");
        if (!empty($asm['expert_id'])) {
            add_notification('Expert', $asm['expert_id'], "QA Passed - $assignment_id", "Your solution for order $assignment_id passed QA review and is awaiting final administrative sign-off.", 'success', "/expert/assignment-detail.php?id=$assignment_id");
        }
        echo json_encode(['success' => true, 'message' => 'QA Approved! Order forwarded for final Admin approval and student release.']);
        exit;

    case 'allocator_request_revision':
        Auth::checkRole(['Allocator', 'Admin']);
        $user = Auth::currentUser();
        $assignment_id = trim($_POST['assignment_id'] ?? '');
        $instructions = trim($_POST['instructions'] ?? '');
        if (!$assignment_id) {
            echo json_encode(['success' => false, 'message' => 'Assignment ID required']);
            exit;
        }
        $asm = DataStore::findOne('assignments', 'assignment_id', $assignment_id);
        if (!$asm) {
            echo json_encode(['success' => false, 'message' => 'Assignment not found']);
            exit;
        }
        DataStore::update('assignments', 'assignment_id', $assignment_id, [
            'status' => 'Revision Requested',
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        if (!empty($instructions)) {
            DataStore::insert('notes', [
                'note_id' => 'NOTE-' . rand(100, 999),
                'assignment_id' => $assignment_id,
                'user_id' => $user['id'],
                'user_role' => $user['role'],
                'user_name' => $user['name'],
                'message' => 'QA Revision Requested: ' . $instructions,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
        add_audit_log('Allocator', $user['id'], 'Request Revision', "Allocator {$user['name']} requested revisions on order $assignment_id");
        if (!empty($asm['expert_id'])) {
            add_notification('Expert', $asm['expert_id'], "Revision Requested - $assignment_id", "Allocator {$user['name']} requested revisions on order $assignment_id: " . substr($instructions, 0, 80), 'warning', "/expert/assignment-detail.php?id=$assignment_id");
        }
        echo json_encode(['success' => true, 'message' => 'Revision request submitted to expert.']);
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

    case 'create_razorpay_order':
        $assignment_id = trim($_POST['assignment_id'] ?? $_GET['assignment_id'] ?? '');
        if (!$assignment_id) {
            echo json_encode(['success' => false, 'message' => 'Missing assignment ID']);
            exit;
        }
        $res = create_razorpay_order_api($assignment_id);
        echo json_encode($res);
        exit;

    case 'verify_razorpay_payment':
        $assignment_id = trim($_POST['assignment_id'] ?? '');
        $order_id = trim($_POST['razorpay_order_id'] ?? '');
        $payment_id = trim($_POST['razorpay_payment_id'] ?? '');
        $signature = trim($_POST['razorpay_signature'] ?? '');

        if (!$assignment_id || !$payment_id) {
            echo json_encode(['success' => false, 'message' => 'Missing payment verification data']);
            exit;
        }

        $asm = DataStore::findOne('assignments', 'assignment_id', $assignment_id);
        if (!$asm) {
            echo json_encode(['success' => false, 'message' => 'Assignment not found']);
            exit;
        }

        // Verify signature if provided
        $isValid = true;
        if (!empty($signature) && !empty($order_id)) {
            $isValid = verify_razorpay_signature($order_id, $payment_id, $signature);
        }

        if (!$isValid) {
            echo json_encode(['success' => false, 'message' => 'Razorpay payment signature verification failed']);
            exit;
        }

        $user = Auth::currentUser();
        if (!$user) {
            $student = DataStore::findOne('students', 'student_id', $asm['student_id']);
            if ($student) {
                if (session_status() === PHP_SESSION_NONE) session_start();
                $_SESSION['user'] = [
                    'id' => $student['student_id'],
                    'name' => $student['name'],
                    'email' => $student['email'],
                    'role' => 'Student',
                    'country' => $student['country'] ?? 'India'
                ];
                $user = $_SESSION['user'];
            }
        }

        $asmCurrency = $asm['currency'] ?? 'INR';
        $paymentAmount = (float)($asm['final_price'] ?? $asm['price']);
        $paymentId = 'PAY-' . rand(8900, 9999);

        // Check duplicate
        $existingPayment = DataStore::findOne('payments', 'assignment_id', $assignment_id);
        if ($existingPayment && $existingPayment['status'] === 'Paid') {
            echo json_encode([
                'success' => true,
                'already_paid' => true,
                'transaction_id' => $existingPayment['transaction_id'],
                'invoice_url' => "/student/invoice.php?id=" . urlencode($assignment_id),
                'message' => 'Assignment already paid in full.'
            ]);
            exit;
        }

        DataStore::insert('payments', [
            'payment_id' => $paymentId,
            'assignment_id' => $assignment_id,
            'student_id' => $asm['student_id'],
            'amount' => $paymentAmount,
            'currency' => $asmCurrency,
            'status' => 'Paid',
            'payment_method' => 'Razorpay Gateway',
            'transaction_id' => $payment_id,
            'payment_date' => date('Y-m-d H:i:s')
        ]);

        $newStatus = in_array($asm['status'], ['New', 'Pending Review', 'Waiting for Payment']) ? 'Confirmed' : $asm['status'];
        DataStore::update('assignments', 'assignment_id', $assignment_id, [
            'status' => $newStatus,
            'payment_status' => 'Paid',
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $formattedPaid = format_currency_amount($paymentAmount, $asmCurrency);
        add_audit_log('Student', $user['id'] ?? $asm['student_id'], 'Razorpay Payment Confirmed', "Paid {$formattedPaid} ({$asmCurrency}) via Razorpay (TX: {$payment_id})");

        add_notification('Allocator', '', "Razorpay Payment Settled: $assignment_id", "Order $assignment_id settled via Razorpay ({$formattedPaid}). Ready for expert allocation.", 'success', "/allocator/assignment-detail.php?id=$assignment_id");
        add_notification('Student', $asm['student_id'], "Payment Confirmed - Order $assignment_id", "Your Razorpay payment of {$formattedPaid} is confirmed! Expert allocation started.", 'success', "/student/assignment-detail.php?id=$assignment_id");

        echo json_encode([
            'success' => true,
            'transaction_id' => $payment_id,
            'order_id' => $order_id,
            'assignment_id' => $assignment_id,
            'amount' => $paymentAmount,
            'currency' => $asmCurrency,
            'formatted_amount' => $formattedPaid,
            'invoice_url' => "/student/invoice.php?id=" . urlencode($assignment_id),
            'message' => "Razorpay payment verified successfully! Order $assignment_id is Confirmed."
        ]);
        exit;

    case 'pay_now':
    case 'process_payment':
        $user = Auth::currentUser();
        $assignment_id = trim($_POST['assignment_id'] ?? '');
        $method = trim($_POST['payment_method'] ?? 'Stripe Credit Card');
        $cardLast4 = trim($_POST['card_last4'] ?? '');
        $cardBrand = trim($_POST['card_brand'] ?? '');
        $upiVpa = trim($_POST['upi_vpa'] ?? '');

        if (!$assignment_id) {
            echo json_encode(['success' => false, 'message' => 'Missing assignment ID']);
            exit;
        }

        $asm = DataStore::findOne('assignments', 'assignment_id', $assignment_id);
        if (!$asm) {
            echo json_encode(['success' => false, 'message' => 'Assignment not found']);
            exit;
        }

        // If user not in session, attempt to auto-login the student matching the assignment
        if (!$user) {
            $student = DataStore::findOne('students', 'student_id', $asm['student_id']);
            if ($student) {
                if (session_status() === PHP_SESSION_NONE) session_start();
                $_SESSION['user'] = [
                    'id' => $student['student_id'],
                    'name' => $student['name'],
                    'email' => $student['email'],
                    'role' => 'Student',
                    'country' => $student['country'] ?? 'United States'
                ];
                $user = $_SESSION['user'];
            } else {
                $user = [
                    'id' => $asm['student_id'],
                    'name' => 'Student',
                    'email' => 'student@aceassign.com',
                    'role' => 'Student'
                ];
            }
        }

        // Generate provider-specific transaction ID
        $randHex = substr(md5(uniqid(mt_rand(), true)), 0, 14);
        if (stripos($method, 'stripe') !== false || stripos($method, 'card') !== false) {
            $tx_id = 'pi_stripe_' . $randHex;
        } elseif (stripos($method, 'razorpay') !== false || stripos($method, 'upi') !== false) {
            $tx_id = 'pay_rzp_' . $randHex;
        } elseif (stripos($method, 'paypal') !== false) {
            $tx_id = 'PAYID-' . strtoupper($randHex);
        } else {
            $tx_id = 'TRX-' . strtoupper($randHex);
        }

        $asmCurrency = $asm['currency'] ?? 'USD';
        $paymentAmount = (float)$asm['final_price'];
        $paymentId = 'PAY-' . rand(8900, 9999);

        // Check if a payment for this assignment already exists to prevent duplicate insertion
        $existingPayment = DataStore::findOne('payments', 'assignment_id', $assignment_id);
        if ($existingPayment && $existingPayment['status'] === 'Paid') {
            echo json_encode([
                'success' => true,
                'already_paid' => true,
                'transaction_id' => $existingPayment['transaction_id'],
                'payment_id' => $existingPayment['payment_id'],
                'assignment_id' => $assignment_id,
                'invoice_url' => "/student/invoice.php?id=" . urlencode($assignment_id),
                'message' => 'This assignment is already paid in full.'
            ]);
            exit;
        }

        DataStore::insert('payments', [
            'payment_id' => $paymentId,
            'assignment_id' => $assignment_id,
            'student_id' => $asm['student_id'],
            'amount' => $paymentAmount,
            'currency' => $asmCurrency,
            'status' => 'Paid',
            'payment_method' => $method,
            'transaction_id' => $tx_id,
            'payment_date' => date('Y-m-d H:i:s')
        ]);

        $newStatus = in_array($asm['status'], ['New', 'Pending Review', 'Waiting for Payment']) ? 'Confirmed' : $asm['status'];
        DataStore::update('assignments', 'assignment_id', $assignment_id, [
            'status' => $newStatus,
            'payment_status' => 'Paid',
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $formattedPaid = format_currency_amount($paymentAmount, $asmCurrency);
        add_audit_log($user['role'] ?? 'Student', $user['id'] ?? $asm['student_id'], 'Payment Received', "Paid {$formattedPaid} ({$asmCurrency}) via {$method} for {$assignment_id} (TX: {$tx_id})");

        // Trigger in-portal notifications
        add_notification(
            'Allocator',
            '',
            "Order Confirmed & Paid: $assignment_id",
            "Payment of {$formattedPaid} ({$asmCurrency}) received via {$method}. Assignment is ready for expert allocation.",
            'success',
            "/allocator/assignment-detail.php?id=$assignment_id"
        );

        add_notification(
            'Student',
            $asm['student_id'],
            "Payment Confirmed - Order $assignment_id",
            "Thank you! Your payment of {$formattedPaid} has been verified. Your assignment is now Confirmed and allocated to a subject specialist.",
            'success',
            "/student/assignment-detail.php?id=$assignment_id"
        );

        echo json_encode([
            'success' => true,
            'transaction_id' => $tx_id,
            'payment_id' => $paymentId,
            'assignment_id' => $assignment_id,
            'amount' => $paymentAmount,
            'currency' => $asmCurrency,
            'formatted_amount' => $formattedPaid,
            'invoice_url' => "/student/invoice.php?id=" . urlencode($assignment_id),
            'message' => "Payment authorized successfully! Your assignment $assignment_id is now Confirmed."
        ]);
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
            $uploaded = [];
            if (isset($_FILES['revision_files'])) {
                $uploaded = handle_uploaded_files('revision_files', $assignment_id, $user['name'] . ' (Revision Request)', false);
            }
            $fileNote = !empty($uploaded) ? " [" . count($uploaded) . " file(s) attached]" : "";

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
                'message' => "Revision Request: " . $revision_instructions . $fileNote,
                'visibility' => 'Internal',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            add_audit_log('Student', $user['id'], 'Request Revision', "Requested revision for $assignment_id$fileNote");
            add_notification('Allocator', null, "Revision Requested: $assignment_id", "Student {$user['name']} requested revisions$fileNote", 'warning');
            echo json_encode(['success' => true, 'message' => 'Revision request submitted! Support and allocator team notified.']);
            exit;
        }
        echo json_encode(['success' => false, 'message' => 'Missing revision instructions']);
        exit;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown API action']);
        exit;
}
