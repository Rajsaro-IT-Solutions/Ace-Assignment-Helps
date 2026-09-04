<?php
/**
 * AceAssignment System Helpers
 */

require_once __DIR__ . '/db.php';

function get_sla_status($deadline_str) {
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

function calculate_assignment_price($word_count, $deadline_hours, $academic_level = 'Undergraduate', $subject = 'General', $coupon_code = '') {
    $word_count = max(250, (int)$word_count);
    $pages = ceil($word_count / 250);

    // Base rate per page ($15/page baseline)
    $base_per_page = 15.0;

    // Academic level multiplier
    $level_multipliers = [
        'High School' => 0.85,
        'Undergraduate' => 1.0,
        'Master\'s' => 1.35,
        'PhD' => 1.75
    ];
    $level_mult = $level_multipliers[$academic_level] ?? 1.0;

    // Deadline urgency multiplier
    $urgency_mult = 1.0;
    if ($deadline_hours <= 12) {
        $urgency_mult = 2.2;
    } elseif ($deadline_hours <= 24) {
        $urgency_mult = 1.8;
    } elseif ($deadline_hours <= 48) {
        $urgency_mult = 1.4;
    } elseif ($deadline_hours <= 72) {
        $urgency_mult = 1.2;
    }

    // Subject complexity boost
    $complex_subjects = ['Computer Science', 'Programming', 'Engineering', 'Medical Sciences', 'Finance', 'Law & Legal Studies'];
    $subject_mult = in_array($subject, $complex_subjects) ? 1.15 : 1.0;

    $subtotal = round($pages * $base_per_page * $level_mult * $urgency_mult * $subject_mult, 2);

    $discount_amount = 0.0;
    $discount_percent = 0;

    if (!empty($coupon_code)) {
        $coupon = DataStore::findOne('coupons', 'code', strtoupper(trim($coupon_code)));
        if ($coupon && $coupon['status'] === 'Active') {
            $discount_percent = (float)$coupon['discount_percent'];
            $discount_amount = round(($subtotal * $discount_percent) / 100, 2);
        }
    }

    $final_price = round(max(10.0, $subtotal - $discount_amount), 2);

    return [
        'word_count' => $word_count,
        'pages' => $pages,
        'subtotal' => $subtotal,
        'discount_percent' => $discount_percent,
        'discount_amount' => $discount_amount,
        'final_price' => $final_price
    ];
}

function generate_assignment_id() {
    $year = date('Y');
    $assignments = DataStore::getCollection('assignments');
    $next_num = count($assignments) + 101;
    return sprintf("ACE-%s-%06d", $year, $next_num);
}

function mask_student_name($name) {
    if (empty($name)) return "Student [Masked]";
    $parts = explode(' ', trim($name));
    $first = $parts[0];
    $last_initial = isset($parts[1]) ? mb_substr($parts[1], 0, 1) . '.' : '';
    return $first . ' ' . $last_initial . ' [Privacy Protected]';
}

function add_audit_log($role, $user_id, $action, $details) {
    $assignments = DataStore::getCollection('audit_logs');
    $log_id = 'LOG-' . (count($assignments) + 1001);
    DataStore::insert('audit_logs', [
        'log_id' => $log_id,
        'user_role' => $role,
        'user_id' => $user_id,
        'action' => $action,
        'details' => $details,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

function get_status_badge_class($status) {
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
        case 'Completed':
        case 'Delivered':
            return 'badge-success';
        case 'Revision Requested':
            return 'badge-amber';
        case 'Cancelled':
        case 'Refunded':
            return 'badge-danger';
        default:
            return 'badge-secondary';
    }
}
