<?php
$pageTitle = "Allocator Performance Reports";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole(['Allocator', 'Admin']);
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';
?>

<div class="metrics-grid">
  <div class="metric-card">
    <div class="metric-icon icon-green"><i class="fa-solid fa-bolt"></i></div>
    <div class="metric-info">
      <div class="m-val">98.4%</div>
      <div class="m-lbl">SLA On-Time Rate</div>
    </div>
  </div>

  <div class="metric-card">
    <div class="metric-icon icon-blue"><i class="fa-solid fa-stopwatch"></i></div>
    <div class="metric-info">
      <div class="m-val">18 mins</div>
      <div class="m-lbl">Avg Allocation Speed</div>
    </div>
  </div>

  <div class="metric-card">
    <div class="metric-icon icon-cyan"><i class="fa-solid fa-user-check"></i></div>
    <div class="metric-info">
      <div class="m-val">4.9 / 5</div>
      <div class="m-lbl">Quality Audit Rating</div>
    </div>
  </div>
</div>

<div class="table-card" style="padding:1.5rem;">
  <h3 style="font-size:1.1rem; color:var(--text-main); margin-bottom:1rem;"><i class="fa-solid fa-chart-line" style="color:var(--primary);"></i> Allocator SLA Performance Report</h3>
  <p style="color:var(--text-muted); font-size:0.9rem;">Your performance is tracked in real-time based on allocation speed, expert match accuracy, and deadline completion rate.</p>
</div>

</div>
</div>
</body>
</html>
