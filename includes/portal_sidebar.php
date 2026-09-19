<?php
$role = $currentUser['role'] ?? 'Student';
$currUri = $_SERVER['REQUEST_URI'] ?? '';
?>
<aside class="portal-sidebar">
  <div class="sidebar-brand">
    <a href="/">
      <img src="/assets/image/logo.png" alt="Ace Assignment Helps" class="logo-img">
      <span style="font-size:1.05rem;">Ace Assignment</span>
    </a>
  </div>

  <div class="sidebar-nav">
    <?php if ($role === 'Student'): ?>
      <div class="nav-section-lbl">Student Portal</div>
      <a href="/student/index.php" class="sidebar-link <?php echo strpos($currUri, '/student/index.php') !== false ? 'active' : ''; ?>">
        <i class="fa-solid fa-house"></i> Dashboard
      </a>
      <a href="/student/new-assignment.php" class="sidebar-link <?php echo strpos($currUri, 'new-assignment') !== false ? 'active' : ''; ?>">
        <i class="fa-solid fa-file-circle-plus"></i> Submit Assignment
      </a>
      <a href="/student/assignments.php" class="sidebar-link <?php echo strpos($currUri, '/student/assignments.php') !== false ? 'active' : ''; ?>">
        <i class="fa-solid fa-book-open"></i> My Assignments
      </a>
      <a href="/student/payments.php" class="sidebar-link <?php echo strpos($currUri, 'payments') !== false ? 'active' : ''; ?>">
        <i class="fa-solid fa-credit-card"></i> Invoices & Payments
      </a>
      <a href="/student/whatsapp.php" class="sidebar-link <?php echo strpos($currUri, 'whatsapp') !== false ? 'active' : ''; ?>">
        <i class="fa-brands fa-whatsapp"></i> WhatsApp Alerts
      </a>
      <a href="/student/messages.php" class="sidebar-link <?php echo strpos($currUri, 'messages') !== false ? 'active' : ''; ?>">
        <i class="fa-solid fa-comments"></i> Support Tickets
      </a>
      <a href="/student/profile.php" class="sidebar-link <?php echo strpos($currUri, 'profile') !== false ? 'active' : ''; ?>">
        <i class="fa-solid fa-user-gear"></i> Profile Settings
      </a>

    <?php elseif ($role === 'Allocator'): ?>
      <div class="nav-section-lbl">Allocation Control</div>
      <a href="/allocator/index.php" class="sidebar-link <?php echo strpos($currUri, '/allocator/index.php') !== false ? 'active' : ''; ?>">
        <i class="fa-solid fa-gauge"></i> Command Center
      </a>
      <a href="/allocator/pending.php" class="sidebar-link <?php echo strpos($currUri, 'pending') !== false ? 'active' : ''; ?>">
        <i class="fa-solid fa-hourglass-start"></i> Pending Queue
      </a>
      <a href="/allocator/allocated.php" class="sidebar-link <?php echo strpos($currUri, 'allocated') !== false ? 'active' : ''; ?>">
        <i class="fa-solid fa-bars-staggered"></i> Allocated & Active
      </a>
      <a href="/allocator/completed.php" class="sidebar-link <?php echo strpos($currUri, 'completed') !== false ? 'active' : ''; ?>">
        <i class="fa-solid fa-circle-check"></i> Completed Log
      </a>

      <div class="nav-section-lbl">Resources & Comms</div>
      <a href="/allocator/experts.php" class="sidebar-link <?php echo strpos($currUri, 'experts') !== false ? 'active' : ''; ?>">
        <i class="fa-solid fa-user-graduate"></i> Expert Roster
      </a>
      <a href="/allocator/email-center.php" class="sidebar-link <?php echo strpos($currUri, 'email-center') !== false ? 'active' : ''; ?>">
        <i class="fa-solid fa-paper-plane"></i> Email Center
      </a>

    <?php elseif ($role === 'Admin'): ?>
      <div class="nav-section-lbl">Executive Admin</div>
      <a href="/admin/index.php" class="sidebar-link <?php echo strpos($currUri, '/admin/index.php') !== false ? 'active' : ''; ?>">
        <i class="fa-solid fa-chart-pie"></i> Executive Dashboard
      </a>
      <a href="/admin/assignments.php" class="sidebar-link <?php echo strpos($currUri, 'assignments') !== false ? 'active' : ''; ?>">
        <i class="fa-solid fa-folder-tree"></i> Master Assignments
      </a>
      <a href="/admin/courses.php" class="sidebar-link <?php echo strpos($currUri, 'courses') !== false ? 'active' : ''; ?>">
        <i class="fa-solid fa-graduation-cap"></i> Courses & Subjects
      </a>
      <a href="/admin/students.php" class="sidebar-link <?php echo strpos($currUri, 'students') !== false ? 'active' : ''; ?>">
        <i class="fa-solid fa-users"></i> Registered Students
      </a>
      <a href="/admin/experts.php" class="sidebar-link <?php echo strpos($currUri, 'experts') !== false ? 'active' : ''; ?>">
        <i class="fa-solid fa-user-ninja"></i> Expert Roster
      </a>
      <a href="/admin/allocators.php" class="sidebar-link <?php echo strpos($currUri, 'allocators') !== false ? 'active' : ''; ?>">
        <i class="fa-solid fa-user-shield"></i> Allocator Staff (Add)
      </a>
      <a href="/admin/admins.php" class="sidebar-link <?php echo strpos($currUri, 'admins') !== false ? 'active' : ''; ?>">
        <i class="fa-solid fa-user-gear"></i> Admin Users (Add)
      </a>

      <div class="nav-section-lbl">Finance & Marketing</div>
      <a href="/admin/payments.php" class="sidebar-link <?php echo strpos($currUri, 'payments') !== false ? 'active' : ''; ?>">
        <i class="fa-solid fa-money-bill-wave"></i> Financial Logs
      </a>
      <a href="/admin/coupons.php" class="sidebar-link <?php echo strpos($currUri, 'coupons') !== false ? 'active' : ''; ?>">
        <i class="fa-solid fa-ticket"></i> Coupons System
      </a>
      <a href="/admin/blogs.php" class="sidebar-link <?php echo strpos($currUri, 'blogs') !== false ? 'active' : ''; ?>">
        <i class="fa-solid fa-newspaper"></i> Blogs Manager
      </a>
      <a href="/admin/whatsapp.php" class="sidebar-link <?php echo strpos($currUri, 'whatsapp') !== false ? 'active' : ''; ?>">
        <i class="fa-brands fa-whatsapp"></i> WhatsApp Broadcast
      </a>

      <div class="nav-section-lbl">System</div>
      <a href="/admin/reports.php" class="sidebar-link <?php echo strpos($currUri, 'reports') !== false ? 'active' : ''; ?>">
        <i class="fa-solid fa-chart-line"></i> Analytics & CSV
      </a>
      <a href="/admin/logs.php" class="sidebar-link <?php echo strpos($currUri, 'logs') !== false ? 'active' : ''; ?>">
        <i class="fa-solid fa-list-check"></i> Audit Trail
      </a>
      <a href="/admin/settings.php" class="sidebar-link <?php echo strpos($currUri, 'settings') !== false ? 'active' : ''; ?>">
        <i class="fa-solid fa-sliders"></i> Site Settings
      </a>
      <a href="/admin/backup.php" class="sidebar-link <?php echo strpos($currUri, 'backup') !== false ? 'active' : ''; ?>">
        <i class="fa-solid fa-database"></i> Backup & Export
      </a>
    <?php endif; ?>
  </div>
</aside>
