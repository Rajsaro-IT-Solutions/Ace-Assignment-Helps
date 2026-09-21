<?php
$pageTitle = "Global Payments & Financial Logs";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Admin');
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$payments = DataStore::getCollection('payments');
?>

<div class="table-card">
  <div class="table-header">
    <h3 style="font-size:1.1rem; color:#fff;"><i class="fa-solid fa-money-bill-wave" style="color:var(--success);"></i> All Payment Transactions</h3>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Transaction ID</th>
          <th>Assignment ID</th>
          <th>Student</th>
          <th>Amount</th>
          <th>Method</th>
          <th>Status</th>
          <th>Date</th>
          <th>Invoice</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($payments as $pay): 
          $student = DataStore::findOne('students', 'student_id', $pay['student_id']);
        ?>
          <tr>
            <td><strong style="color:var(--secondary);"><?php echo htmlspecialchars($pay['transaction_id']); ?></strong></td>
            <td><?php echo htmlspecialchars($pay['assignment_id']); ?></td>
            <td><strong><?php echo htmlspecialchars($student['name'] ?? 'Student'); ?></strong></td>
            <td><strong style="color:var(--success);"><?php echo format_currency_amount($pay['amount'], $pay['currency'] ?? 'USD'); ?> (<?php echo htmlspecialchars($pay['currency'] ?? 'USD'); ?>)</strong></td>
            <td><?php echo format_payment_method_badge($pay['payment_method']); ?></td>
            <td><span class="badge badge-success"><?php echo htmlspecialchars($pay['status']); ?></span></td>
            <td><?php echo htmlspecialchars($pay['payment_date']); ?></td>
            <td>
              <a href="/student/invoice.php?id=<?php echo urlencode($pay['assignment_id']); ?>" target="_blank" class="btn btn-outline btn-sm">
                View Invoice
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="/assets/js/portal.js"></script>
</div>
</div>
</body>
</html>
