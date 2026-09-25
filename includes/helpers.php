<?php
/**
 * AceAssignment System Helpers
 */

require_once __DIR__ . '/db.php';

function get_sla_status($deadline_str)
{
    if (empty($deadline_str)) {
        return [
            'level' => 'green',
            'label' => 'No Deadline',
            'hours_left' => 999,
            'badge_class' => 'sla-green'
        ];
    }

    $now = time();
    $deadline = strtotime($deadline_str);
    $diff_seconds = $deadline - $now;
    $hours_left = round($diff_seconds / 3600, 1);

    if ($diff_seconds <= 0) {
        return [
            'level' => 'overdue',
            'label' => 'OVERDUE (' . abs(round($hours_left)) . 'h ago)',
            'hours_left' => $hours_left,
            'badge_class' => 'sla-red-pulse'
        ];
    } elseif ($hours_left <= 12) {
        return [
            'level' => 'red',
            'label' => 'URGENT (' . $hours_left . 'h left)',
            'hours_left' => $hours_left,
            'badge_class' => 'sla-red'
        ];
    } elseif ($hours_left <= 48) {
        return [
            'level' => 'amber',
            'label' => 'AMBER (' . round($hours_left) . 'h left)',
            'hours_left' => $hours_left,
            'badge_class' => 'sla-amber'
        ];
    } else {
        return [
            'level' => 'green',
            'label' => 'ON TRACK (' . round($hours_left / 24, 1) . ' days)',
            'hours_left' => $hours_left,
            'badge_class' => 'sla-green'
        ];
    }
}

function render_word_count_options($selectedWords = 1000)
{
    $selectedWords = (int) $selectedWords ?: 1000;
    $html = '';
    for ($w = 250; $w <= 20000; $w += 250) {
        $pages = (int) ($w / 250);
        $pageText = ($pages === 1) ? '1 Page' : number_format($pages) . ' Pages';
        $selected = ($w === $selectedWords) ? ' selected' : '';
        $formattedWords = number_format($w);
        $html .= "  <option value=\"{$w}\"{$selected}>{$formattedWords} Words ({$pageText})</option>\n";
    }
    return $html;
}

function get_currency_symbol($currency = 'USD')
{
    $c = strtoupper(trim((string)$currency ?: 'USD'));
    switch ($c) {
        case 'INR': return '₹';
        case 'GBP': return '£';
        case 'EUR': return '€';
        case 'AUD': return 'A$';
        case 'CAD': return 'C$';
        case 'USD':
        default:
            return '$';
    }
}

function format_currency_amount($amount, $currency = 'USD')
{
    $c = strtoupper(trim((string)$currency ?: 'USD'));
    $symbol = get_currency_symbol($c);
    $decimals = ($c === 'INR') ? 0 : 2;
    return $symbol . number_format((float)$amount, $decimals);
}

function calculate_assignment_price($word_count, $deadline_hours, $academic_level = 'Undergraduate', $subject = 'General', $coupon_code = '', $currency = 'USD')
{
    $word_count = max(250, (int) $word_count);
    $pages = ceil($word_count / 250);
    $deadline_hours = max(1, (float) $deadline_hours);
    $currency = strtoupper(trim($currency ?: 'USD'));

    // Exact tiered rate schedule:
    // 3+ Days (72h+, 4 days, 5+ days): USD $0.011 / INR ₹1.00 per word
    // 2 Days (48 Hours): USD $0.016 / INR ₹1.50 per word
    // 1 Day (24 Hours urgent or less): USD $0.021 / INR ₹2.00 per word
    $currencyConfigs = [
        'USD' => ['symbol' => '$', 'rate_3plus' => 0.0110, 'rate_2days' => 0.0160, 'rate_1day' => 0.0210, 'decimals' => 2],
        'INR' => ['symbol' => '₹', 'rate_3plus' => 1.0000, 'rate_2days' => 1.5000, 'rate_1day' => 2.0000, 'decimals' => 0],
        'GBP' => ['symbol' => '£', 'rate_3plus' => 0.0087, 'rate_2days' => 0.0126, 'rate_1day' => 0.0166, 'decimals' => 2],
        'EUR' => ['symbol' => '€', 'rate_3plus' => 0.0100, 'rate_2days' => 0.0145, 'rate_1day' => 0.0191, 'decimals' => 2],
        'AUD' => ['symbol' => 'A$', 'rate_3plus' => 0.0170, 'rate_2days' => 0.0247, 'rate_1day' => 0.0325, 'decimals' => 2],
        'CAD' => ['symbol' => 'C$', 'rate_3plus' => 0.0150, 'rate_2days' => 0.0218, 'rate_1day' => 0.0286, 'decimals' => 2],
    ];

    $config = $currencyConfigs[$currency] ?? $currencyConfigs['USD'];

    if ($deadline_hours <= 24.0) {
        $effective_rate_per_word = $config['rate_1day'];
    } elseif ($deadline_hours <= 48.0) {
        $effective_rate_per_word = $config['rate_2days'];
    } else {
        $effective_rate_per_word = $config['rate_3plus'];
    }

    $base_rate = $config['rate_3plus'];
    $urgency_rate = max(0.0, $effective_rate_per_word - $base_rate);
    $subtotal = round($word_count * $effective_rate_per_word, 2);

    $discount_amount = 0.0;
    $discount_percent = 0;
    $coupon_valid = false;
    $coupon_msg = '';
    $clean_code = strtoupper(trim($coupon_code));

    if (!empty($clean_code)) {
        $coupon = DataStore::findOne('coupons', 'code', $clean_code);
        if (!$coupon) {
            $coupon_valid = false;
            $coupon_msg = "Coupon code '{$clean_code}' is not valid.";
        } elseif (($coupon['status'] ?? 'Active') !== 'Active') {
            $coupon_valid = false;
            $coupon_msg = "Coupon '{$clean_code}' is currently inactive or disabled.";
        } elseif (!empty($coupon['expires_at']) && strtotime($coupon['expires_at'] . ' 23:59:59') < time()) {
            $coupon_valid = false;
            $coupon_msg = "Coupon '{$clean_code}' expired on " . htmlspecialchars($coupon['expires_at']) . ".";
        } elseif (!empty($coupon['max_uses']) && (int) ($coupon['current_uses'] ?? 0) >= (int) $coupon['max_uses']) {
            $coupon_valid = false;
            $coupon_msg = "Coupon '{$clean_code}' has reached its maximum usage limit.";
        } else {
            $coupon_valid = true;
            $discount_percent = (float) $coupon['discount_percent'];
            $discount_amount = round(($subtotal * $discount_percent) / 100, 2);
            $coupon_msg = "Coupon {$coupon['code']} Applied! {$discount_percent}% Discount Activated.";
        }
    }

    $min_price = ($currency === 'INR') ? 100.0 : 1.0;
    $final_price = round(max($min_price, $subtotal - $discount_amount), 2);

    return [
        'word_count' => $word_count,
        'pages' => $pages,
        'deadline_hours' => $deadline_hours,
        'currency' => $currency,
        'currency_symbol' => $config['symbol'],
        'decimals' => $config['decimals'] ?? 2,
        'base_rate_per_word' => $base_rate,
        'urgency_rate_per_word' => $urgency_rate,
        'effective_rate_per_word' => $effective_rate_per_word,
        'subtotal' => $subtotal,
        'coupon_code' => $clean_code,
        'coupon_valid' => $coupon_valid,
        'coupon_message' => $coupon_msg,
        'discount_percent' => $discount_percent,
        'discount_amount' => $discount_amount,
        'final_price' => $final_price
    ];
}

function handle_uploaded_files($fileInputName, $assignmentId, $uploadedBy = 'Student', $isInternal = false, $fileStage = 'draft')
{
    $uploadedRecords = [];

    // Find all matching keys in $_FILES (exact match, array bracketed, or indexed keys)
    $matchingKeys = [];
    if (isset($_FILES[$fileInputName])) {
        $matchingKeys[] = $fileInputName;
    }
    foreach (array_keys($_FILES) as $k) {
        if ($k !== $fileInputName && strpos($k, $fileInputName) === 0) {
            $matchingKeys[] = $k;
        }
    }

    if (empty($matchingKeys)) {
        return $uploadedRecords;
    }

    $targetDir = __DIR__ . '/../assets/uploads/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    // Normalize files into array of files (supports single, multiple, and indexed)
    $fileList = [];
    foreach ($matchingKeys as $key) {
        $files = $_FILES[$key];
        if (is_array($files['name'])) {
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK && !empty($files['name'][$i])) {
                    $fileList[] = [
                        'name' => $files['name'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i]
                    ];
                }
            }
        } else {
            if ($files['error'] === UPLOAD_ERR_OK && !empty($files['name'])) {
                $fileList[] = [
                    'name' => $files['name'],
                    'tmp_name' => $files['tmp_name'],
                    'error' => $files['error'],
                    'size' => $files['size']
                ];
            }
        }
    }

    foreach ($fileList as $f) {
        $origName = basename($f['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $safePrefix = preg_replace('/[^a-zA-Z0-9_\-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
        $stageTag = ($fileStage === 'complete') ? '_SOLUTION_' : (($fileStage === 'draft') ? '_DRAFT_' : '_');
        $uniqueName = time() . $stageTag . rand(100, 999) . '_' . $safePrefix . ($ext ? '.' . $ext : '');
        $targetPath = $targetDir . $uniqueName;

        $moved = is_uploaded_file($f['tmp_name']) ? @move_uploaded_file($f['tmp_name'], $targetPath) : (@copy($f['tmp_name'], $targetPath) || @move_uploaded_file($f['tmp_name'], $targetPath));

        if ($moved) {
            $fileId = DataStore::generateNextId('files', 'file_id', 'FILE-', 4, 1001);
            $rec = [
                'file_id' => $fileId,
                'assignment_id' => $assignmentId,
                'file_name' => $origName,
                'path' => 'assets/uploads/' . $uniqueName,
                'file_type' => $ext ?: 'file',
                'file_stage' => $fileStage,
                'uploaded_by' => $uploadedBy,
                'upload_date' => date('Y-m-d H:i:s'),
                'is_internal' => (bool) $isInternal
            ];
            DataStore::insert('files', $rec);
            $uploadedRecords[] = $rec;
        }
    }

    return $uploadedRecords;
}

function is_tech_file($file)
{
    $ext = '';
    if (is_array($file)) {
        $ext = strtolower($file['file_type'] ?? pathinfo($file['file_name'] ?? '', PATHINFO_EXTENSION));
    } elseif (is_string($file)) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    }
    $techExts = [
        'zip', 'rar', '7z', 'tar', 'gz', 'tgz', 'bz2', 'iso',
        'sql', 'py', 'java', 'cpp', 'c', 'cs', 'php', 'js', 'jsx', 'ts', 'tsx',
        'html', 'css', 'scss', 'ipynb', 'sh', 'bat', 'json', 'xml', 'yaml', 'yml',
        'r', 'm', 'dockerfile'
    ];
    return in_array($ext, $techExts);
}

function get_tech_file_icon($ext)
{
    $ext = strtolower($ext);
    if (in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz', 'tgz', 'bz2', 'iso'])) {
        return 'fa-solid fa-file-zipper';
    }
    if (in_array($ext, ['py', 'java', 'cpp', 'c', 'cs', 'php', 'js', 'ts', 'html', 'css', 'ipynb', 'sh', 'sql'])) {
        return 'fa-solid fa-file-code';
    }
    return 'fa-solid fa-file-lines';
}

function generate_assignment_id()
{
    $year = date('Y');
    return DataStore::generateNextId('assignments', 'assignment_id', 'ACE-' . $year . '-', 6, 101);
}

function mask_student_name($name)
{
    if (empty($name))
        return "Student [Masked]";
    $parts = explode(' ', trim($name));
    $first = $parts[0];
    $last_initial = isset($parts[1]) ? mb_substr($parts[1], 0, 1) . '.' : '';
    return $first . ' ' . $last_initial . ' [Privacy Protected]';
}

function add_audit_log($role, $user_id, $action, $details)
{
    try {
        $log_id = DataStore::generateNextId('audit_logs', 'log_id', 'LOG-', 4, 1001);
        DataStore::insert('audit_logs', [
            'log_id' => $log_id,
            'user_role' => $role,
            'user_id' => $user_id,
            'action' => $action,
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Throwable $ignore) {}
}

function is_assignment_paid($assignment_id)
{
    if (empty($assignment_id)) return false;
    $asm = DataStore::findOne('assignments', 'assignment_id', $assignment_id);
    if ($asm) {
        $final = (float)($asm['final_price'] ?? 0);
        if ($final <= 0) {
            return true;
        }
        $rem = (float)($asm['remaining_balance'] ?? 0);
        $paid = (float)($asm['paid_amount'] ?? 0);
        $status = strtolower($asm['payment_status'] ?? '');

        // An assignment is ONLY fully paid if the remaining balance is zero
        // AND (paid >= final price OR status is explicitly Paid/Completed)
        if ($rem <= 0.01 && ($paid >= ($final - 0.01) || in_array($status, ['paid', 'completed', 'success', 'captured']))) {
            return true;
        }
        return false;
    }
    return false;
}

function get_assignment_paid_percentage($asm)
{
    if (!$asm) return 0.0;
    $final = (float)($asm['final_price'] ?? 0);
    if ($final <= 0) return 100.0;
    $paid = (float)($asm['paid_amount'] ?? 0);
    $rem = (float)($asm['remaining_balance'] ?? ($final - $paid));
    if ($rem <= 0.01 && $paid >= ($final - 0.01)) {
        return 100.0;
    }
    $pct = ($paid / $final) * 100;
    return max(0.0, min(100.0, round($pct, 1)));
}

function get_assignment_draft_limit($asm)
{
    if (!$asm) return 0;
    if (is_assignment_paid($asm['assignment_id'] ?? '')) {
        return 999; // Unlimited / All
    }
    $pct = get_assignment_paid_percentage($asm);
    if ($pct >= 99.9) {
        return 999;
    }
    if ($pct >= 50.0) {
        return 3; // 1 initial draft + 2 more drafts = 3 drafts
    }
    if ($pct > 0.0) {
        return 1; // Partial payment (<50%, e.g. 20%) -> 1 draft
    }
    return 0; // Unpaid
}

function is_draft_file($file)
{
    if (empty($file)) return false;
    $stage = strtolower($file['file_stage'] ?? '');
    if ($stage === 'draft') return true;
    if ($stage === 'complete' || $stage === 'refund_proof' || $stage === 'brief') return false;

    $fileName = strtolower($file['file_name'] ?? '');
    $path = strtolower($file['path'] ?? '');
    $uploadedBy = strtolower($file['uploaded_by'] ?? '');

    if (strpos($fileName, 'draft') !== false || strpos($path, '_draft_') !== false || strpos($uploadedBy, 'draft') !== false) {
        return true;
    }
    return false;
}

function is_complete_solution_file($file)
{
    if (empty($file)) return false;
    $stage = strtolower($file['file_stage'] ?? '');
    if ($stage === 'complete') return true;
    if ($stage === 'draft' || $stage === 'refund_proof' || $stage === 'brief') return false;

    return is_solution_file($file) && !is_draft_file($file);
}

function is_solution_file($file)
{
    if (empty($file)) return false;
    $uploader = strtolower($file['uploaded_by'] ?? '');
    $fileName = strtolower($file['file_name'] ?? '');
    $path = strtolower($file['path'] ?? '');
    $stage = strtolower($file['file_stage'] ?? '');

    if ($stage === 'complete' || $stage === 'draft') {
        return true;
    }

    // Files uploaded by experts are solutions, drafts, or Turnitin originality reports
    if (strpos($uploader, 'expert') !== false) {
        return true;
    }
    // Files matching solution or turnitin naming patterns
    if (strpos($path, '_solution_') !== false || strpos($path, '_draft_') !== false || strpos($fileName, 'solution') !== false || strpos($fileName, 'turnitin') !== false || strpos($fileName, 'draft') !== false) {
        return true;
    }
    // Admin deliverables
    if (strpos($uploader, 'admin') !== false && (strpos($fileName, 'solution') !== false || strpos($path, 'solution') !== false || strpos($fileName, 'draft') !== false)) {
        return true;
    }
    return false;
}


function get_status_badge_class($status)
{
    switch ($status) {
        case 'New':
        case 'Pending Review':
            return 'badge-warning';
        case 'Waiting for Payment':
            return 'badge-info';
        case 'Confirmed':
        case 'Allocated':
            return 'badge-primary';
        case 'In Progress':
            return 'badge-indigo';
        case 'Quality Check':
            return 'badge-purple';
        case 'Pending Admin Approval':
        case 'QA Approved':
            return 'badge-cyan';
        case 'Completed':
        case 'Delivered':
            return 'badge-success';
        case 'Revision Requested':
            return 'badge-amber';
        case 'Refund Requested':
        case 'Cancelled':
        case 'Refunded':
        case 'Deleted':
            return 'badge-danger';
        case 'Partially Paid':
            return 'badge-info';
        default:
            return 'badge-secondary';
    }
}

function add_notification($user_role, $user_id, $title, $message, $type = 'info', $link = '')
{
    $notif_id = DataStore::generateNextId('notifications', 'notification_id', 'NTF-', 0, 1);
    return DataStore::insert('notifications', [
        'id' => $notif_id,
        'user_id' => $user_id ?: '',
        'user_role' => $user_role ?: 'All',
        'title' => $title,
        'message' => $message,
        'is_read' => false,
        'created_at' => date('Y-m-d H:i:s')
    ]);
}

function is_payment_notification($n)
{
    $combined = strtolower(($n['title'] ?? '') . ' ' . ($n['message'] ?? ''));
    $keywords = [
        'payment', 'paid', 'deposit', 'stripe', 'razorpay', 'invoice', 'refund',
        'settled', 'balance', 'part payment', 'confirmed & paid', 'billing', 'remittance',
        'transaction', 'txn', 'pay now', 'wallet', 'checkout'
    ];
    foreach ($keywords as $kw) {
        if (strpos($combined, $kw) !== false) {
            return true;
        }
    }
    if (preg_match('/[\$₹€£]\s*[0-9]+/', $combined)) {
        return true;
    }
    return false;
}

function extract_assignment_id_from_text($text)
{
    if (preg_match('/(ACE-[A-Za-z0-9\-]+|AAH-[A-Za-z0-9\-]+|TEST-[A-Za-z0-9\-]+)/i', $text, $matches)) {
        return strtoupper($matches[1]);
    }
    if (preg_match('/[?&]id=([A-Za-z0-9\-]+)/i', $text, $matches)) {
        return strtoupper($matches[1]);
    }
    return null;
}

function get_user_notifications($user_id, $user_role)
{
    $all = DataStore::getCollection('notifications');
    
    // Cache assignments for fast relational lookup
    static $assignmentsMap = null;
    if ($assignmentsMap === null) {
        $allAsms = DataStore::getCollection('assignments');
        $assignmentsMap = [];
        foreach ($allAsms as $a) {
            if (!empty($a['assignment_id'])) {
                $assignmentsMap[strtoupper($a['assignment_id'])] = $a;
            }
        }
    }

    $filtered = array_filter($all, function ($n) use ($user_id, $user_role, &$assignmentsMap) {
        $targetRole = $n['user_role'] ?? '';
        $targetUser = $n['user_id'] ?? '';
        $title = $n['title'] ?? '';
        $message = $n['message'] ?? '';
        $link = $n['link'] ?? '';

        // RULE 1: Payment notifications are strictly for Admin only
        // Non-admins (Allocator, Expert, Student, etc.) must NEVER see payment notifications
        if ($user_role !== 'Admin') {
            if (is_payment_notification($n)) {
                return false;
            }
        }

        // Admin monitors all system notifications
        if ($user_role === 'Admin') {
            return true;
        }

        // RULE 2: If explicitly targeted to another specific user_id, exclude
        if (!empty($targetUser) && $targetUser !== $user_id) {
            return false;
        }

        // RULE 3: Role and assignment relevance check
        $combinedText = $title . ' ' . $message . ' ' . $link;
        $asmId = extract_assignment_id_from_text($combinedText);

        if ($asmId && isset($assignmentsMap[$asmId])) {
            $asm = $assignmentsMap[$asmId];
            if ($user_role === 'Allocator') {
                // Allocator only sees notifications for assignments explicitly assigned to them
                return !empty($asm['allocator_id']) && $asm['allocator_id'] === $user_id;
            }
            if ($user_role === 'Expert') {
                // Expert only sees notifications for assignments explicitly assigned to them
                return !empty($asm['expert_id']) && $asm['expert_id'] === $user_id;
            }
            if ($user_role === 'Student') {
                // Student only sees notifications for their own assignments
                return !empty($asm['student_id']) && $asm['student_id'] === $user_id;
            }
        } elseif ($asmId && !isset($assignmentsMap[$asmId])) {
            // Assignment referenced but not found: hide from staff to prevent leakage
            return false;
        }

        // If no assignment ID is present:
        if (!empty($targetUser)) {
            return ($targetUser === $user_id);
        }

        // Broadcast notifications must match role
        if ($targetRole === $user_role) {
            return true;
        }

        return false;
    });

    // Sort descending by created_at or id
    usort($filtered, function ($a, $b) {
        return strtotime($b['created_at'] ?? '0') - strtotime($a['created_at'] ?? '0');
    });

    return array_values($filtered);
}

function get_unread_notifications_count($user_id, $user_role)
{
    $notifs = get_user_notifications($user_id, $user_role);
    $count = 0;
    foreach ($notifs as $n) {
        if (empty($n['is_read'])) {
            $count++;
        }
    }
    return $count;
}

function mark_notification_read($notif_id)
{
    return DataStore::update('notifications', 'id', $notif_id, ['is_read' => true]);
}

function mark_all_notifications_read($user_id, $user_role)
{
    $notifs = get_user_notifications($user_id, $user_role);
    foreach ($notifs as $n) {
        if (empty($n['is_read']) && isset($n['id'])) {
            DataStore::update('notifications', 'id', $n['id'], ['is_read' => true]);
        }
    }
    return true;
}

function delete_notification($notif_id)
{
    return DataStore::delete('notifications', 'notification_id', $notif_id);
}

/**
 * Payment Gateway Configuration & Helpers
 */
function get_gateway_config()
{
    return [
        'mode' => DataStore::getSetting('payment_gateway_mode', 'test'), // 'test' or 'live'
        'stripe' => [
            'enabled' => DataStore::getSetting('stripe_enabled', '1') === '1',
            'publishable_key' => DataStore::getSetting('stripe_publishable_key', 'pk_test_51MockStripeKey123456789AceAssign'),
            'secret_key' => DataStore::getSetting('stripe_secret_key', 'sk_test_51MockStripeSecretKey123456789AceAssign'),
        ],
        'razorpay' => [
            'enabled' => DataStore::getSetting('razorpay_enabled', '1') === '1',
            'key_id' => DataStore::getSetting('razorpay_key_id', 'rzp_test_AceAssignment2026'),
            'key_secret' => DataStore::getSetting('razorpay_key_secret', 'mock_rzp_secret_key_2026'),
            'upi_id' => DataStore::getSetting('razorpay_upi_id', 'aceassignment@okhdfcbank'),
        ],
        'paypal' => [
            'enabled' => DataStore::getSetting('paypal_enabled', '1') === '1',
            'client_id' => DataStore::getSetting('paypal_client_id', 'sb_mock_client_id_aceassignment'),
            'mode' => DataStore::getSetting('paypal_mode', 'sandbox'),
        ]
    ];
}

function format_payment_method_badge($method)
{
    $m = strtolower((string)$method);
    if (strpos($m, 'stripe') !== false || strpos($m, 'card') !== false) {
        return '<span class="badge" style="background:#e0e7ff; color:#3730a3;"><i class="fa-brands fa-stripe"></i> Card (Stripe)</span>';
    } elseif (strpos($m, 'razorpay') !== false || strpos($m, 'upi') !== false || strpos($m, 'netbanking') !== false) {
        return '<span class="badge" style="background:#dcfce7; color:#166534;"><i class="fa-solid fa-bolt"></i> Razorpay / UPI</span>';
    } elseif (strpos($m, 'paypal') !== false) {
        return '<span class="badge" style="background:#dbeafe; color:#1e40af;"><i class="fa-brands fa-paypal"></i> PayPal</span>';
    } elseif (strpos($m, 'bank') !== false || strpos($m, 'wire') !== false) {
        return '<span class="badge" style="background:#fef3c7; color:#92400e;"><i class="fa-solid fa-building-columns"></i> Bank Wire</span>';
    }
    return '<span class="badge badge-secondary"><i class="fa-solid fa-credit-card"></i> ' . htmlspecialchars($method) . '</span>';
}

function create_razorpay_order_api($assignment_id, $plan = '100%', $isRemaining = false)
{
    $asm = DataStore::findOne('assignments', 'assignment_id', $assignment_id);
    if (!$asm) {
        return ['success' => false, 'message' => 'Assignment not found'];
    }

    $gw = get_gateway_config();
    $keyId = $gw['razorpay']['key_id'];
    $keySecret = $gw['razorpay']['key_secret'];

    if (empty($keyId) || empty($keySecret)) {
        return ['success' => false, 'message' => 'Razorpay API credentials not configured'];
    }

    $currency = strtoupper($asm['currency'] ?? 'INR');
    $totalPrice = (float)($asm['final_price'] ?? $asm['price']);
    $existingPaid = (float)($asm['paid_amount'] ?? 0);
    $existingRemaining = (float)($asm['remaining_balance'] ?? 0);

    if ($isRemaining || $plan === 'remaining') {
        $amount = ($existingRemaining > 0) ? $existingRemaining : max(1, round($totalPrice - $existingPaid, 2));
    } else {
        switch ($plan) {
            case '20%': $pct = 0.20; break;
            case '30%': $pct = 0.30; break;
            case '50%': $pct = 0.50; break;
            case '100%':
            default: $pct = 1.00; break;
        }
        $amount = round($totalPrice * $pct, 2);
    }

    $amountSubunits = (int)round($amount * 100);
    $receipt = substr('rec_' . $assignment_id . '_' . time(), 0, 40);

    $payload = [
        'amount' => $amountSubunits,
        'currency' => $currency,
        'receipt' => $receipt,
        'notes' => [
            'assignment_id' => $assignment_id,
            'payment_plan' => $plan,
            'title' => substr($asm['title'] ?? 'Assignment', 0, 50)
        ]
    ];

    $ch = curl_init('https://api.razorpay.com/v1/orders');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $keyId . ':' . $keySecret);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        return ['success' => false, 'message' => 'Curl connection error: ' . $err];
    }

    $data = json_decode($res, true);
    if ($httpCode === 200 && isset($data['id'])) {
        return [
            'success' => true,
            'order_id' => $data['id'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'key_id' => $keyId,
            'assignment_id' => $assignment_id
        ];
    }

    $errorMsg = $data['error']['description'] ?? 'Failed to create Razorpay order';
    return ['success' => false, 'message' => $errorMsg, 'raw' => $data];
}

function verify_razorpay_signature($order_id, $payment_id, $signature)
{
    $gw = get_gateway_config();
    $keySecret = $gw['razorpay']['key_secret'];
    if (empty($keySecret)) return false;
    $expected = hash_hmac('sha256', $order_id . '|' . $payment_id, $keySecret);
    return hash_equals($expected, $signature);
}

function calculate_payment_plan($totalPrice, $reqPlan = '100%')
{
    $totalPrice = (float)$totalPrice;
    switch ($reqPlan) {
        case '20%':
            $pct = 0.20;
            $planLabel = '20% Deposit';
            break;
        case '30%':
            $pct = 0.30;
            $planLabel = '30% Advance';
            break;
        case '50%':
            $pct = 0.50;
            $planLabel = '50% Milestone';
            break;
        case '100%':
        default:
            $pct = 1.00;
            $planLabel = '100% Full Payment';
            break;
    }

    $paymentAmount = round($totalPrice * $pct, 2);
    $remaining = max(0, round($totalPrice - $paymentAmount, 2));
    $status = ($remaining <= 0.01) ? 'Paid' : 'Partially Paid';

    return [
        'plan' => $reqPlan,
        'label' => $planLabel,
        'percentage' => $pct,
        'pay_amount' => $paymentAmount,
        'remaining_balance' => $remaining,
        'payment_status' => $status
    ];
}



