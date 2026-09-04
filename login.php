<?php
$pageTitle = "Student & Staff Portal Login";
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        if (Auth::login($email, $password)) {
            $user = Auth::currentUser();
            add_audit_log($user['role'], $user['id'], 'User Login', 'Logged into portal');
            if ($user['role'] === 'Admin') {
                header('Location: /admin/index.php');
            } elseif ($user['role'] === 'Allocator') {
                header('Location: /allocator/index.php');
            } else {
                header('Location: /student/index.php');
            }
            exit;
        } else {
            $error = "Invalid email address or password. Please try again.";
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 4rem 1.5rem; max-width: 480px;">
  <div class="calc-card" style="padding: 2.5rem; background: #ffffff;">
    <div style="text-align: center; margin-bottom: 2rem;">
      <div class="logo-icon" style="width: 56px; height: 56px; font-size: 1.6rem; margin: 0 auto 1rem auto;">
        <i class="fa-solid fa-graduation-cap"></i>
      </div>
      <h1 style="font-size: 1.8rem; margin-bottom: 0.5rem; color: #0f172a;">Portal Account Login</h1>
      <p style="color: var(--text-muted); font-size: 0.95rem;">Access your <strong>Ace Assignment Helps</strong> dashboard</p>
    </div>

    <?php if ($error): ?>
      <div class="badge badge-danger" style="width:100%; padding:0.8rem; margin-bottom:1.5rem; text-align:center; font-size:0.9rem;">
        <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Email Address</label>
        <div style="position:relative;">
          <i class="fa-solid fa-envelope" style="position:absolute; left:12px; top:13px; color:var(--text-dim);"></i>
          <input type="email" name="email" class="form-control" style="padding-left:38px;" required placeholder="student@university.edu">
        </div>
      </div>

      <div class="form-group">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.4rem;">
          <label style="margin-bottom:0;">Password</label>
          <a href="#" style="font-size:0.82rem;">Forgot Password?</a>
        </div>
        <div style="position:relative;">
          <i class="fa-solid fa-lock" style="position:absolute; left:12px; top:13px; color:var(--text-dim);"></i>
          <input type="password" name="password" class="form-control" style="padding-left:38px;" required placeholder="••••••••">
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-lg" style="width:100%; margin-top:1rem;">
        <i class="fa-solid fa-right-to-bracket"></i> Sign In to Portal
      </button>
    </form>

    <div style="text-align: center; margin-top: 2rem; border-top: 1px solid var(--border-color); padding-top: 1.5rem;">
      <p style="color: var(--text-muted); font-size: 0.9rem;">
        New Student? <a href="/register.php" style="font-weight:700;">Create a Free Account</a>
      </p>
      <small style="color: var(--text-dim); display: block; margin-top: 8px;">
        <i class="fa-solid fa-lock"></i> Allocator & Staff accounts are managed by Platform Admins.
      </small>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
