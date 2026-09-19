<?php
$pageTitle = "Profile & Account Settings";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Student');
$user = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';
$msg = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $country = trim($_POST['country'] ?? '');
    DataStore::update('students', 'student_id', $user['id'], [
        'name' => $name,
        'phone' => $phone,
        'country' => $country
    ]);
    $_SESSION['user']['name'] = $name;
    $_SESSION['user']['phone'] = $phone;
    $_SESSION['user']['country'] = $country;
    $user = Auth::currentUser();
    $msg = "Profile updated successfully!";
}
?>

<div style="max-width: 600px; margin: 0 auto;">
  <div class="calc-card" style="padding:2rem; background:#ffffff;">
    <h3 style="margin-bottom:1.5rem; color:var(--text-main);"><i class="fa-solid fa-user-gear" style="color:var(--primary);"></i> Student Account Settings</h3>

    <?php if ($msg): ?>
      <div class="badge badge-success" style="width:100%; padding:0.8rem; margin-bottom:1rem; text-align:center;">
        <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($msg); ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
      </div>

      <div class="form-group">
        <label>Email Address (Read Only)</label>
        <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="opacity:0.6;">
      </div>

      <div class="form-group">
        <label>WhatsApp / Phone Number</label>
        <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
      </div>

      <div class="form-group">
        <label>Country</label>
        <input type="text" name="country" class="form-control" value="<?php echo htmlspecialchars($user['country'] ?? 'United States'); ?>" required>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%; margin-top:1rem;"><i class="fa-solid fa-floppy-disk"></i> Save Profile Changes</button>
    </form>
  </div>
</div>

</div>
</div>
</body>
</html>
