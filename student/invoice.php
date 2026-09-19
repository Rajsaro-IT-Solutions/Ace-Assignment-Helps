<?php
$pageTitle = "Official Tax Invoice";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkLoggedIn();
require_once __DIR__ . '/../includes/helpers.php';

$id = $_GET['id'] ?? '';
$asm = DataStore::findOne('assignments', 'assignment_id', $id);
if (!$asm) {
    echo "Invoice not found.";
    exit;
}

$currentUser = Auth::currentUser();
if ($currentUser && $currentUser['role'] === 'Student' && $asm['student_id'] !== $currentUser['id']) {
    echo "<div style='padding:2rem; text-align:center; font-family:sans-serif;'><h2>Access Denied</h2><p>You do not have permission to view this invoice.</p></div>";
    exit;
}

$student = DataStore::findOne('students', 'student_id', $asm['student_id']);
$payment = DataStore::findOne('payments', 'assignment_id', $id);
$isPaid = ($payment && ($payment['status'] ?? '') === 'Paid') || in_array($asm['status'] ?? '', ['Confirmed', 'Allocated', 'In Progress', 'Quality Check', 'Completed', 'Delivered']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Invoice - <?php echo htmlspecialchars($asm['assignment_id']); ?> | Ace Assignment Helps</title>
  <link rel="icon" type="image/png" href="/assets/image/logo.png">
  <style>
    body { font-family: 'Helvetica Neue', Arial, sans-serif; color: #0f172a; background: #f8fafc; margin: 0; padding: 40px; }
    .invoice-card { max-width: 800px; margin: 0 auto; background: #ffffff; padding: 40px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
    .inv-header { display: flex; justify-content: space-between; border-bottom: 2px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 30px; }
    .logo { font-size: 1.6rem; font-weight: 800; color: #4f46e5; }
    .inv-title { font-size: 1.8rem; font-weight: 700; text-align: right; color: #0f172a; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
    .info-block h4 { margin: 0 0 8px 0; color: #64748b; font-size: 0.85rem; text-transform: uppercase; }
    .info-block p { margin: 0; font-size: 0.95rem; line-height: 1.5; color: #0f172a; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
    th { background: #f1f5f9; padding: 12px; text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #475569; }
    td { padding: 14px 12px; border-bottom: 1px solid #e2e8f0; font-size: 0.95rem; }
    .total-box { text-align: right; font-size: 1.3rem; font-weight: 800; color: #4f46e5; margin-top: 20px; }
    .badge-paid { display: inline-block; padding: 6px 16px; background: #d1fae5; color: #047857; font-weight: 800; border-radius: 20px; text-transform: uppercase; font-size: 0.85rem; }
    @media print {
      body { background: #ffffff; padding: 0; }
      .invoice-card { border: none; box-shadow: none; padding: 0; }
      .no-print { display: none; }
    }
  </style>
</head>
<body>

<div class="no-print" style="max-width:800px; margin:0 auto 20px auto; text-align:right;">
  <button onclick="window.print()" style="padding:10px 20px; background:#4f46e5; color:#ffffff; border:none; border-radius:6px; font-weight:700; cursor:pointer;">
    🖨️ Print / Download PDF Invoice
  </button>
</div>

<div class="invoice-card">
  <div class="inv-header">
    <div style="display:flex; align-items:center; gap:14px;">
      <img src="/assets/image/logo.png" alt="Ace Assignment Helps" style="width:58px; height:58px; object-fit:contain; border-radius:8px;">
      <div>
        <div class="logo">Ace Assignment Helps</div>
        <div style="color:#64748b; font-size:0.85rem; margin-top:2px;">Global Academic Services Platform</div>
        <div style="color:#64748b; font-size:0.85rem;">support@aceassign.com</div>
      </div>
    </div>
    <div>
      <div class="inv-title">TAX INVOICE</div>
      <div style="color:#64748b; font-size:0.9rem; text-align:right; margin-top:4px;">Invoice #: INV-<?php echo htmlspecialchars($asm['assignment_id']); ?></div>
      <div style="color:#64748b; font-size:0.9rem; text-align:right;">Date: <?php echo date('M d, Y', strtotime($asm['created_at'])); ?></div>
    </div>
  </div>

  <div class="grid-2">
    <div class="info-block">
      <h4>Billed To (Student)</h4>
      <p><strong><?php echo htmlspecialchars($student['name'] ?? 'Student'); ?></strong></p>
      <p><?php echo htmlspecialchars($student['email'] ?? ''); ?></p>
      <p><?php echo htmlspecialchars($asm['university'] ?? ''); ?> (<?php echo htmlspecialchars($asm['country'] ?? ''); ?>)</p>
    </div>

    <div class="info-block" style="text-align:right;">
      <h4>Payment Status</h4>
      <div style="margin-bottom:8px;">
        <?php if ($isPaid): ?>
          <span class="badge-paid">PAID IN FULL</span>
        <?php else: ?>
          <span class="badge-paid" style="background:#fef3c7; color:#b45309;">PAYMENT PENDING / PROFORMA</span>
        <?php endif; ?>
      </div>
      <p>Transaction ID: <?php echo htmlspecialchars($payment['transaction_id'] ?? ($isPaid ? 'TRX-SIMULATED-99' : 'Awaiting Settlement')); ?></p>
      <p>Payment Method: <?php echo htmlspecialchars($payment['payment_method'] ?? ($isPaid ? 'Online Credit Card' : 'Pending')); ?></p>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th>Description & Item Details</th>
        <th>Word Count</th>
        <th>Price / Page</th>
        <th style="text-align:right;">Total Amount</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>
          <strong><?php echo htmlspecialchars($asm['title']); ?></strong><br>
          <small style="color:#64748b;">Subject: <?php echo htmlspecialchars($asm['subject']); ?> &bull; Type: <?php echo htmlspecialchars($asm['assignment_type']); ?></small>
        </td>
        <td><?php echo $asm['word_count']; ?> words (<?php echo $asm['pages']; ?> pages)</td>
        <td>$15.00</td>
        <td style="text-align:right;">$<?php echo number_format($asm['price'], 2); ?></td>
      </tr>
      <?php if (!empty($asm['discount_code'])): ?>
        <tr>
          <td colspan="3" style="text-align:right; color:#047857; font-weight:700;">Promotional Discount (<?php echo htmlspecialchars($asm['discount_code']); ?>)</td>
          <td style="text-align:right; color:#047857; font-weight:700;">-$<?php echo number_format($asm['price'] - $asm['final_price'], 2); ?></td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <div class="total-box">
    Grand Total Paid: $<?php echo number_format($asm['final_price'], 2); ?> USD
  </div>

  <div style="margin-top:40px; padding-top:20px; border-top:1px solid #e2e8f0; text-align:center; color:#94a3b8; font-size:0.8rem;">
    Thank you for choosing <strong>Ace Assignment Helps</strong>. 100% confidential & Turnitin verified service.
  </div>
</div>

</body>
</html>
