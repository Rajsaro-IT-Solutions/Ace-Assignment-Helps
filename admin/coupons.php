<?php
$pageTitle = "Coupons & Promotional Discounts";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Admin');
$adminUser = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$msg = '';
$msgType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Create Coupon
    if ($action === 'create_coupon') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $discount = (float)($_POST['discount_percent'] ?? 10);
        $max = (int)($_POST['max_uses'] ?? 100);
        $expires = $_POST['expires_at'] ?? date('Y-12-31');
        $status = $_POST['status'] ?? 'Active';

        if (!empty($code)) {
            $existing = DataStore::findOne('coupons', 'code', $code);
            if ($existing) {
                $msg = "Coupon code '$code' already exists!";
                $msgType = 'danger';
            } else {
                $newId = 'CPN-' . rand(10, 99);
                DataStore::insert('coupons', [
                    'coupon_id' => $newId,
                    'code' => $code,
                    'discount_percent' => $discount,
                    'max_uses' => $max,
                    'current_uses' => 0,
                    'expires_at' => $expires,
                    'status' => $status
                ]);
                add_audit_log('Admin', $adminUser['id'], 'Create Coupon', "Created coupon $code ($newId)");
                $msg = "New coupon $code created successfully!";
                $msgType = 'success';
            }
        }
    }

    // 2. Update Coupon
    if ($action === 'update_coupon') {
        $coupon_id = trim($_POST['coupon_id'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $discount = (float)($_POST['discount_percent'] ?? 10);
        $max = (int)($_POST['max_uses'] ?? 100);
        $expires = $_POST['expires_at'] ?? date('Y-12-31');
        $status = $_POST['status'] ?? 'Active';

        if ($coupon_id && $code) {
            DataStore::update('coupons', 'coupon_id', $coupon_id, [
                'code' => $code,
                'discount_percent' => $discount,
                'max_uses' => $max,
                'expires_at' => $expires,
                'status' => $status
            ]);
            add_audit_log('Admin', $adminUser['id'], 'Update Coupon', "Updated coupon $code ($coupon_id)");
            $msg = "Coupon $code updated successfully!";
            $msgType = 'success';
        }
    }

    // 3. Toggle Status
    if ($action === 'toggle_status') {
        $coupon_id = trim($_POST['coupon_id'] ?? '');
        $newStatus = trim($_POST['status'] ?? 'Active');
        if ($coupon_id) {
            DataStore::update('coupons', 'coupon_id', $coupon_id, ['status' => $newStatus]);
            add_audit_log('Admin', $adminUser['id'], 'Toggle Coupon Status', "Set coupon $coupon_id to $newStatus");
            $msg = "Coupon status set to $newStatus.";
            $msgType = 'success';
        }
    }

    // 4. Delete Coupon
    if ($action === 'delete_coupon') {
        $coupon_id = trim($_POST['coupon_id'] ?? '');
        if ($coupon_id) {
            DataStore::delete('coupons', 'coupon_id', $coupon_id);
            add_audit_log('Admin', $adminUser['id'], 'Delete Coupon', "Permanently deleted coupon $coupon_id");
            $msg = "Coupon $coupon_id deleted successfully.";
            $msgType = 'danger';
        }
    }
}

$coupons = DataStore::getCollection('coupons');
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h2 style="font-size:1.5rem; color:var(--text-main); margin-bottom:0.25rem;">
      <i class="fa-solid fa-ticket" style="color:var(--primary);"></i> Discount Coupon System & Promotions
    </h2>
    <p style="color:var(--text-muted); font-size:0.9rem; margin:0;">Create promotional discount codes, manage student discounts, and toggle campaigns.</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('addCouponModal')">
    <i class="fa-solid fa-plus"></i> Create New Coupon
  </button>
</div>

<?php if ($msg): ?>
  <div class="badge badge-<?php echo $msgType; ?>" style="width:100%; padding:0.9rem; margin-bottom:1.5rem; text-align:center; font-size:0.95rem; display:block;">
    <i class="fa-solid fa-circle-info"></i> <?php echo htmlspecialchars($msg); ?>
  </div>
<?php endif; ?>

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
          <th style="text-align:center; min-width:200px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($coupons)): ?>
          <tr>
            <td colspan="8" style="text-align:center; padding:2rem; color:var(--text-muted);">No discount coupons found. Click "Create New Coupon" to add one.</td>
          </tr>
        <?php endif; ?>
        <?php foreach ($coupons as $cpn): 
          $isActive = ($cpn['status'] === 'Active');
        ?>
          <tr>
            <td><strong style="color:var(--text-muted); font-family:monospace;"><?php echo htmlspecialchars($cpn['coupon_id']); ?></strong></td>
            <td><strong style="color:var(--primary); font-size:1.05rem; letter-spacing:1px;"><?php echo htmlspecialchars($cpn['code']); ?></strong></td>
            <td><strong style="color:var(--success); font-size:0.95rem;"><?php echo $cpn['discount_percent']; ?>% OFF</strong></td>
            <td><?php echo $cpn['max_uses']; ?> max</td>
            <td><?php echo $cpn['current_uses']; ?> used</td>
            <td><?php echo htmlspecialchars($cpn['expires_at']); ?></td>
            <td>
              <span class="badge <?php echo $isActive ? 'badge-success' : 'badge-danger'; ?>">
                <?php echo htmlspecialchars($cpn['status']); ?>
              </span>
            </td>
            <td style="text-align:center;">
              <div style="display:inline-flex; gap:6px; align-items:center; justify-content:center;">
                <!-- Edit Button -->
                <button type="button" class="btn btn-outline btn-sm" onclick='editCoupon(<?php echo json_encode($cpn); ?>)' title="Edit Coupon">
                  <i class="fa-solid fa-pen-to-square"></i> Edit
                </button>

                <!-- Toggle Status Button -->
                <?php if ($isActive): ?>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="coupon_id" value="<?php echo htmlspecialchars($cpn['coupon_id']); ?>">
                    <input type="hidden" name="status" value="Disabled">
                    <button type="submit" class="btn btn-warning btn-sm" title="Disable Coupon">
                      <i class="fa-solid fa-pause"></i> Disable
                    </button>
                  </form>
                <?php else: ?>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="coupon_id" value="<?php echo htmlspecialchars($cpn['coupon_id']); ?>">
                    <input type="hidden" name="status" value="Active">
                    <button type="submit" class="btn btn-success btn-sm" title="Activate Coupon">
                      <i class="fa-solid fa-play"></i> Enable
                    </button>
                  </form>
                <?php endif; ?>

                <!-- Delete Button -->
                <form method="POST" style="display:inline;" onsubmit="return confirm('Permanently DELETE coupon <?php echo addslashes($cpn['code']); ?>?');">
                  <input type="hidden" name="action" value="delete_coupon">
                  <input type="hidden" name="coupon_id" value="<?php echo htmlspecialchars($cpn['coupon_id']); ?>">
                  <button type="submit" class="btn btn-danger btn-sm" title="Delete Coupon">
                    <i class="fa-solid fa-trash"></i> Delete
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Modal -->
<div id="addCouponModal" class="modal-overlay">
  <div class="modal-box" style="padding:2rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem;">
      <h3 style="margin:0; color:var(--text-main);"><i class="fa-solid fa-ticket" style="color:var(--primary);"></i> Create Discount Coupon</h3>
      <button type="button" onclick="closeModal('addCouponModal')" style="background:none; border:none; font-size:1.3rem; color:var(--text-muted); cursor:pointer;">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create_coupon">
      <div class="form-group">
        <label>Coupon Code *</label>
        <input type="text" name="code" class="form-control" required placeholder="e.g. FALL30" style="text-transform:uppercase;">
      </div>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div class="form-group">
          <label>Discount (%) *</label>
          <input type="number" name="discount_percent" class="form-control" required value="20" min="5" max="90">
        </div>
        <div class="form-group">
          <label>Max Uses Count</label>
          <input type="number" name="max_uses" class="form-control" value="100">
        </div>
      </div>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div class="form-group">
          <label>Expiry Date</label>
          <input type="date" name="expires_at" class="form-control" value="<?php echo date('Y-12-31'); ?>">
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status" class="form-control">
            <option value="Active">Active</option>
            <option value="Disabled">Disabled</option>
          </select>
        </div>
      </div>
      <div style="display:flex; gap:10px; margin-top:1.5rem;">
        <button type="submit" class="btn btn-primary" style="flex:1;">Save Coupon</button>
        <button type="button" class="btn btn-outline" onclick="closeModal('addCouponModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div id="editCouponModal" class="modal-overlay">
  <div class="modal-box" style="padding:2rem;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem;">
      <h3 style="margin:0; color:var(--text-main);"><i class="fa-solid fa-pen-to-square" style="color:var(--primary);"></i> Edit Discount Coupon</h3>
      <button type="button" onclick="closeModal('editCouponModal')" style="background:none; border:none; font-size:1.3rem; color:var(--text-muted); cursor:pointer;">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="update_coupon">
      <input type="hidden" name="coupon_id" id="edit_coupon_id">
      <div class="form-group">
        <label>Coupon Code *</label>
        <input type="text" name="code" id="edit_coupon_code" class="form-control" required style="text-transform:uppercase;">
      </div>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div class="form-group">
          <label>Discount (%) *</label>
          <input type="number" name="discount_percent" id="edit_coupon_discount" class="form-control" required min="5" max="90">
        </div>
        <div class="form-group">
          <label>Max Uses Count</label>
          <input type="number" name="max_uses" id="edit_coupon_max" class="form-control">
        </div>
      </div>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div class="form-group">
          <label>Expiry Date</label>
          <input type="date" name="expires_at" id="edit_coupon_expires" class="form-control">
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status" id="edit_coupon_status" class="form-control">
            <option value="Active">Active</option>
            <option value="Disabled">Disabled</option>
          </select>
        </div>
      </div>
      <div style="display:flex; gap:10px; margin-top:1.5rem;">
        <button type="submit" class="btn btn-primary" style="flex:1;">Save Changes</button>
        <button type="button" class="btn btn-outline" onclick="closeModal('editCouponModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function editCoupon(data) {
  document.getElementById('edit_coupon_id').value = data.coupon_id || '';
  document.getElementById('edit_coupon_code').value = data.code || '';
  document.getElementById('edit_coupon_discount').value = data.discount_percent || '10';
  document.getElementById('edit_coupon_max').value = data.max_uses || '100';
  document.getElementById('edit_coupon_expires').value = data.expires_at || '';
  document.getElementById('edit_coupon_status').value = data.status || 'Active';
  openModal('editCouponModal');
}
</script>

<script src="/assets/js/portal.js"></script>
</div>
</div>
</body>
</html>
