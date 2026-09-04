<?php
$pageTitle = "Executive Admin Dashboard";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Admin');
$user = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$assignments = DataStore::getCollection('assignments');
$students = DataStore::getCollection('students');
$experts = DataStore::getCollection('experts');
$payments = DataStore::getCollection('payments');

$totalRevenue = array_reduce($payments, function($sum, $p) { return $sum + (float)$p['amount']; }, 0);
$pendingPayments = count(array_filter($assignments, function($a) { return $a['status'] === 'Waiting for Payment'; }));
?>

<!-- Admin Executive Metrics Grid -->
<div class="metrics-grid">
  <div class="metric-card">
    <div class="metric-icon icon-green"><i class="fa-solid fa-sack-dollar"></i></div>
    <div class="metric-info">
      <div class="m-val">$<?php echo number_format($totalRevenue, 2); ?></div>
      <div class="m-lbl">Total Gross Revenue</div>
    </div>
  </div>

  <div class="metric-card">
    <div class="metric-icon icon-blue"><i class="fa-solid fa-folder-tree"></i></div>
    <div class="metric-info">
      <div class="m-val"><?php echo count($assignments); ?></div>
      <div class="m-lbl">Total Assignments</div>
    </div>
  </div>

  <div class="metric-card">
    <div class="metric-icon icon-cyan"><i class="fa-solid fa-user-graduate"></i></div>
    <div class="metric-info">
      <div class="m-val"><?php echo count($students); ?></div>
      <div class="m-lbl">Registered Students</div>
    </div>
  </div>

  <div class="metric-card">
    <div class="metric-icon icon-purple"><i class="fa-solid fa-user-ninja"></i></div>
    <div class="metric-info">
      <div class="m-val"><?php echo count($experts); ?></div>
      <div class="m-lbl">Active PhD Experts</div>
    </div>
  </div>
</div>

<!-- SVG Visual Chart Widget -->
<div class="chart-box">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem;">
    <h3 style="font-size:1.1rem; color:#fff;"><i class="fa-solid fa-chart-column" style="color:var(--secondary);"></i> Revenue & Order Growth Analytics</h3>
    <span class="badge badge-success">Live Realtime Metrics</span>
  </div>

  <svg viewBox="0 0 800 220" style="width:100%; height:auto; overflow:visible;">
    <!-- Grid Lines -->
    <line x1="40" y1="20" x2="780" y2="20" stroke="rgba(255,255,255,0.05)" />
    <line x1="40" y1="70" x2="780" y2="70" stroke="rgba(255,255,255,0.05)" />
    <line x1="40" y1="120" x2="780" y2="120" stroke="rgba(255,255,255,0.05)" />
    <line x1="40" y1="170" x2="780" y2="170" stroke="rgba(255,255,255,0.05)" />

    <!-- Revenue Gradient Area -->
    <defs>
      <linearGradient id="chartGrad" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0%" stop-color="#6366f1" stop-opacity="0.4"/>
        <stop offset="100%" stop-color="#06b6d4" stop-opacity="0.0"/>
      </linearGradient>
    </defs>

    <path d="M 50 160 Q 150 140, 250 110 T 450 70 T 650 40 T 750 30 L 750 180 L 50 180 Z" fill="url(#chartGrad)" />
    <path d="M 50 160 Q 150 140, 250 110 T 450 70 T 650 40 T 750 30" fill="none" stroke="#6366f1" stroke-width="4" />

    <!-- Data Points -->
    <circle cx="50" cy="160" r="5" fill="#06b6d4" />
    <circle cx="250" cy="110" r="5" fill="#06b6d4" />
    <circle cx="450" cy="70" r="5" fill="#06b6d4" />
    <circle cx="650" cy="40" r="5" fill="#06b6d4" />
    <circle cx="750" cy="30" r="6" fill="#10b981" />

    <!-- Labels -->
    <text x="50" y="200" fill="#94a3b8" font-size="12" text-anchor="middle">May</text>
    <text x="250" y="200" fill="#94a3b8" font-size="12" text-anchor="middle">Jun</text>
    <text x="450" y="200" fill="#94a3b8" font-size="12" text-anchor="middle">Jul</text>
    <text x="650" y="200" fill="#94a3b8" font-size="12" text-anchor="middle">Aug</text>
    <text x="750" y="200" fill="#34d399" font-size="12" text-anchor="middle" font-weight="bold">Sep (Current)</text>
  </svg>
</div>

<!-- All Assignments Administration Table -->
<div class="table-card">
  <div class="table-header">
    <h3 style="font-size:1.1rem; color:#fff;"><i class="fa-solid fa-folder-tree" style="color:var(--primary);"></i> Master Assignments Management</h3>
    <a href="/admin/assignments.php" class="btn btn-outline btn-sm">Full Assignments Table &rarr;</a>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Student</th>
          <th>Subject</th>
          <th>Allocated Expert</th>
          <th>SLA Deadline</th>
          <th>Status</th>
          <th>Price</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (array_slice($assignments, 0, 5) as $asm): 
          $student = DataStore::findOne('students', 'student_id', $asm['student_id']);
          $expert = DataStore::findOne('experts', 'expert_id', $asm['expert_id']);
          $sla = get_sla_status($asm['deadline']);
        ?>
          <tr>
            <td><strong style="color:var(--secondary);"><?php echo htmlspecialchars($asm['assignment_id']); ?></strong></td>
            <td><strong><?php echo htmlspecialchars($student['name'] ?? 'Student'); ?></strong><br><small style="color:var(--text-muted);"><?php echo htmlspecialchars($student['email'] ?? ''); ?></small></td>
            <td><?php echo htmlspecialchars($asm['subject']); ?></td>
            <td><?php echo htmlspecialchars($expert['name'] ?? 'Unassigned'); ?></td>
            <td><span class="badge <?php echo $sla['badge_class']; ?>"><?php echo $sla['label']; ?></span></td>
            <td><span class="badge <?php echo get_status_badge_class($asm['status']); ?>"><?php echo htmlspecialchars($asm['status']); ?></span></td>
            <td><strong>$<?php echo number_format($asm['final_price'], 2); ?></strong></td>
            <td>
              <a href="/admin/assignment-detail.php?id=<?php echo urlencode($asm['assignment_id']); ?>" class="btn btn-outline btn-sm">
                Control &rarr;
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
