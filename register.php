<?php
$pageTitle = "Student Registration";
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $country = trim($_POST['country'] ?? 'United States');
    $university = trim($_POST['university'] ?? '');
    $course = trim($_POST['course'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } else {
        $existing = DataStore::findOne('students', 'email', $email);
        if ($existing) {
            $error = "An account with this email already exists. Please login instead.";
        } else {
            $idCount = count(DataStore::getCollection('students')) + 1001;
            $newStudent = [
                'student_id' => 'STU-' . $idCount,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'country' => $country,
                'university' => $university,
                'course' => $course,
                'status' => 'Active',
                'created_at' => date('Y-m-d H:i:s')
            ];

            DataStore::insert('students', $newStudent);
            Auth::login($email, $password);
            add_audit_log('Student', 'STU-' . $idCount, 'Student Registration', 'Self-registered student account');

            header('Location: /student/index.php');
            exit;
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 3rem 1.5rem; max-width: 580px;">
  <div class="calc-card" style="padding: 2.5rem; background: #ffffff;">
    <div style="text-align: center; margin-bottom: 1.8rem;">
      <div class="logo-icon" style="width: 56px; height: 56px; font-size: 1.6rem; margin: 0 auto 1rem auto;">
        <i class="fa-solid fa-user-plus"></i>
      </div>
      <h1 style="font-size: 1.8rem; margin-bottom: 0.5rem; color: #0f172a;">Student Registration</h1>
      <p style="color: var(--text-muted); font-size: 0.95rem;">Join 15,400+ university students on <strong>Ace Assignment Helps</strong></p>
    </div>

    <?php if ($error): ?>
      <div class="badge badge-danger" style="width:100%; padding:0.8rem; margin-bottom:1.5rem; text-align:center; font-size:0.9rem;">
        <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" name="name" class="form-control" required placeholder="e.g. Sarah Jenkins">
      </div>

      <div class="grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
        <div class="form-group">
          <label>University Email *</label>
          <input type="email" name="email" class="form-control" required placeholder="name@stanford.edu">
        </div>

        <div class="form-group">
          <label>Phone / WhatsApp</label>
          <input type="tel" name="phone" class="form-control" placeholder="+1 (555) 000-0000">
        </div>
      </div>

      <div class="grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
        <div class="form-group">
          <label>Country *</label>
          <select name="country" class="form-control" required>
            <option value="United States">United States</option>
            <option value="United Kingdom">United Kingdom</option>
            <option value="Australia">Australia</option>
            <option value="Canada">Canada</option>
            <option value="Germany">Germany</option>
            <option value="Other">Other</option>
          </select>
        </div>

        <div class="form-group">
          <label>University Name</label>
          <input type="text" name="university" class="form-control" placeholder="e.g. Stanford University">
        </div>
      </div>

      <div class="form-group">
        <label>Course / Major</label>
        <input type="text" name="course" class="form-control" placeholder="e.g. Computer Science / Business">
      </div>

      <div class="form-group">
        <label>Account Password *</label>
        <input type="password" name="password" class="form-control" required minlength="6" placeholder="Minimum 6 characters">
      </div>

      <button type="submit" class="btn btn-primary btn-lg" style="width:100%; margin-top:1rem;">
        <i class="fa-solid fa-user-check"></i> Register Student Account
      </button>
    </form>

    <div style="text-align: center; margin-top: 1.5rem; border-top: 1px solid var(--border-color); padding-top: 1.2rem;">
      <p style="color: var(--text-muted); font-size: 0.9rem;">
        Already registered? <a href="/login.php" style="font-weight:700;">Login Here</a>
      </p>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
