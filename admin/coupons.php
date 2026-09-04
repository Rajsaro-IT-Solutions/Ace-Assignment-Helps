<?php
$pageTitle = "Coupons & Promotional Discounts";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Admin');
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$coupons = DataStore::getCollection('coupons');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $discount = (float)($_POST['discount_percent'] ?? 10);
    $max = (int)($_POST['max_uses'] ?? 100);
    $expires = $_POST['expires_at'] ?? date('Y-12-31');

    if (!empty($code)) {
        DataStore::insert('coupons', [
            'coupon_id' => 'CPN-' . rand(10, 99),
            'code' => $code,
            'discount_percent' => $discount,
            'max_uses' => $max,
            'current_uses' => 0,
            'expires_at' => $expires,
            'status' => 'Active'
        ]);
        header("Location: /admin/coupons.php");
        exit;
    }
}
?>

<div style="margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center;">
  <h3 style="color:#fff;"><i class="fa-solid fa-ticket" style="color:var(--primary);"></i> Discount Coupon System</h3>
  <button class="btn btn-primary btn-sm" onclick="openModal('addCouponModal')"><i class="fa-solid fa-plus"></i> Create New Coupon</button>
</div>

<div class="table-card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Promo Code</th>
          <th>Discount (%)</th>
          <th>Usage Limit</th>
          <th>Current Uses</th>
          <th>Expires At</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($coupons as $cpn): ?>
          <tr>
            <td><?php echo htmlspecialchars($cpn['coupon_id']); ?></td>
            <td><strong style="color:var(--secondary); font-size:1.1rem;"><?php echo htmlspecialchars($cpn['code']); ?></strong></td>
            <td><strong style="color:var(--success);"><?php echo $cpn['discount_percent']; ?>% OFF</strong></td>
            <td><?php echo $cpn['max_uses']; ?> max</td>
            <td><?php echo $cpn['current_uses']; ?> used</td>
            <td><?php echo htmlspecialchars($cpn['expires_at']); ?></td>
            <td><span class="badge <?php echo ($cpn['status'] === 'Active') ? 'badge-success' : 'badge-danger'; ?>"><?php echo htmlspecialchars($cpn['status']); ?></span></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal -->
<div id="addCouponModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.85); backdrop-filter:blur(10px); z-index:2000; align-items:center; justify-content:center;">
  <div class="calc-card" style="max-width:420px; width:100%; padding:2rem;">
    <h3 style="margin-bottom:1rem; color:#fff;"><i class="fa-solid fa-ticket"></i> Create Discount Coupon</h3>
    <form method="POST">
      <div class="form-group">
        <label>Coupon Code *</label>
        <input type="text" name="code" class="form-control" required placeholder="e.g. FALL30">
      </div>
      <div class="form-group">
        <label>Discount Percentage (%) *</label>
        <input type="number" name="discount_percent" class="form-control" required value="20" min="5" max="90">
      </div>
      <div class="form-group">
        <label>Max Uses Count</label>
        <input type="number" name="max_uses" class="form-control" value="100">
      </div>
      <div class="form-group">
        <label>Expiry Date</label>
        <input type="date" name="expires_at" class="form-control" value="<?php echo date('Y-12-31'); ?>">
      </div>
      <div style="display:flex; gap:10px; margin-top:1.5rem;">
        <button type="submit" class="btn btn-primary" style="flex:1;">Save Coupon</button>
        <button type="button" class="btn btn-outline" onclick="closeModal('addCouponModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script src="/assets/js/portal.js"></script>
</div>
</div>
</body>
</html>
