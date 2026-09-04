<?php
$pageTitle = "Analytics & Financial Reports";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Admin');
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$assignments = DataStore::getCollection('assignments');

// Country breakdown calculation
$countryBreakdown = [];
foreach ($assignments as $a) {
    $c = $a['country'] ?? 'Unknown';
    if (!isset($countryBreakdown[$c])) $countryBreakdown[$c] = ['count' => 0, 'revenue' => 0];
    $countryBreakdown[$c]['count']++;
    $countryBreakdown[$c]['revenue'] += (float)$a['final_price'];
}

// Subject breakdown calculation
$subjectBreakdown = [];
foreach ($assignments as $a) {
    $s = $a['subject'] ?? 'General';
    if (!isset($subjectBreakdown[$s])) $subjectBreakdown[$s] = ['count' => 0, 'revenue' => 0];
    $subjectBreakdown[$s]['count']++;
    $subjectBreakdown[$s]['revenue'] += (float)$a['final_price'];
}
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
  <h3 style="color:#fff;"><i class="fa-solid fa-chart-column" style="color:var(--primary);"></i> Comprehensive Platform Analytics</h3>
  <a href="/admin/backup.php?export=csv" class="btn btn-outline btn-sm"><i class="fa-solid fa-file-csv"></i> Export CSV Report</a>
</div>

<div class="grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
  <!-- Country Wise Revenue -->
  <div class="table-card" style="padding:1.5rem;">
    <h3 style="font-size:1.1rem; color:#fff; margin-bottom:1rem;"><i class="fa-solid fa-globe" style="color:var(--secondary);"></i> Country-Wise Revenue Breakdown</h3>
    <table class="data-table">
      <thead>
        <tr>
          <th>Country</th>
          <th>Orders Count</th>
          <th>Total Revenue</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($countryBreakdown as $country => $data): ?>
          <tr>
            <td><strong><?php echo htmlspecialchars($country); ?></strong></td>
            <td><?php echo $data['count']; ?> orders</td>
            <td><strong style="color:var(--success);">$<?php echo number_format($data['revenue'], 2); ?></strong></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Subject Wise Revenue -->
  <div class="table-card" style="padding:1.5rem;">
    <h3 style="font-size:1.1rem; color:#fff; margin-bottom:1rem;"><i class="fa-solid fa-book-bookmark" style="color:var(--accent);"></i> Subject-Wise Revenue Breakdown</h3>
    <table class="data-table">
      <thead>
        <tr>
          <th>Subject</th>
          <th>Orders Count</th>
          <th>Total Revenue</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($subjectBreakdown as $subject => $data): ?>
          <tr>
            <td><strong><?php echo htmlspecialchars($subject); ?></strong></td>
            <td><?php echo $data['count']; ?> orders</td>
            <td><strong style="color:var(--success);">$<?php echo number_format($data['revenue'], 2); ?></strong></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

</div>
</div>
</body>
</html>
