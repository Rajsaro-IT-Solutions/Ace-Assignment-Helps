<?php
$pageTitle = "Payments & Invoices";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Student');
$user = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$payments = DataStore::filter('payments', function($p) use ($user) {
    return isset($p['student_id']) && $p['student_id'] === $user['id'];
});
?>

<div class="table-card">
  <div class="table-header">
    <h3 style="font-size:1.1rem; color:var(--text-main);"><i class="fa-solid fa-credit-card" style="color:var(--primary);"></i> Payment Transaction History</h3>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Transaction ID</th>
          <th>Assignment ID</th>
          <th>Amount</th>
          <th>Payment Method</th>
          <th>Status</th>
          <th>Date</th>
          <th>Invoice PDF</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($payments)): ?>
          <tr><td colspan="7" style="text-align:center; color:var(--text-muted);">No payment records found.</td></tr>
        <?php else: ?>
          <?php foreach ($payments as $pay): ?>
            <tr>
              <td><strong style="color:var(--secondary);"><?php echo htmlspecialchars($pay['transaction_id']); ?></strong></td>
              <td><?php echo htmlspecialchars($pay['assignment_id']); ?></td>
              <td><strong style="color:var(--success);">$<?php echo number_format($pay['amount'], 2); ?> <?php echo $pay['currency']; ?></strong></td>
              <td><?php echo htmlspecialchars($pay['payment_method']); ?></td>
              <td><span class="badge badge-success"><?php echo htmlspecialchars($pay['status']); ?></span></td>
              <td><?php echo htmlspecialchars($pay['payment_date']); ?></td>
              <td>
                <a href="/student/invoice.php?id=<?php echo urlencode($pay['assignment_id']); ?>" target="_blank" class="btn btn-outline btn-sm">
                  <i class="fa-solid fa-file-pdf"></i> Download PDF
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

</div>
</div>
</body>
</html>
