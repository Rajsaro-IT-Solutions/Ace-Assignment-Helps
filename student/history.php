<?php
$pageTitle = "Assignment History & Archives";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Student');
$user = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

// Retrieve all historical assignments: Past Completed or Deleted/Cancelled
$historyAssignments = DataStore::filter('assignments', function($a) use ($user) {
    return isset($a['student_id']) && $a['student_id'] === $user['id'] 
        && in_array($a['status'] ?? '', ['Completed', 'Delivered', 'Deleted', 'Cancelled', 'Refunded']);
});

// Sort descending by updated_at or created_at
usort($historyAssignments, function($a, $b) {
    $tA = strtotime($a['updated_at'] ?? ($a['created_at'] ?? '0'));
    $tB = strtotime($b['updated_at'] ?? ($b['created_at'] ?? '0'));
    return $tB - $tA;
});

$totalHistory = count($historyAssignments);
$completedCount = count(array_filter($historyAssignments, function($a) {
    return in_array($a['status'] ?? '', ['Completed', 'Delivered']);
}));
$deletedCount = count(array_filter($historyAssignments, function($a) {
    return ($a['status'] ?? '') === 'Deleted';
}));
$cancelledCount = count(array_filter($historyAssignments, function($a) {
    return in_array($a['status'] ?? '', ['Cancelled', 'Refunded']);
}));
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h2 style="font-size:1.5rem; color:var(--text-main); margin-bottom:0.25rem;">
      <i class="fa-solid fa-clock-rotate-left" style="color:var(--primary);"></i> Assignment History & Archives
    </h2>
    <p style="color:var(--text-muted); font-size:0.9rem; margin:0;">
      Review all your past completed submissions, deleted assignments, and cancelled orders in one place.
    </p>
  </div>
  <div style="display:flex; gap:10px;">
    <a href="/student/assignments.php" class="btn btn-outline btn-sm">
      <i class="fa-solid fa-book-open"></i> Active Assignments
    </a>
    <a href="/student/new-assignment.php" class="btn btn-primary btn-sm">
      <i class="fa-solid fa-plus"></i> Submit New Assignment
    </a>
  </div>
</div>

<!-- History Metrics Grid -->
<div class="metrics-grid" style="margin-bottom:1.8rem;">
  <div class="metric-card">
    <div class="metric-icon icon-blue"><i class="fa-solid fa-folder-archive"></i></div>
    <div class="metric-info">
      <div class="m-val"><?php echo $totalHistory; ?></div>
      <div class="m-lbl">Total Archived</div>
    </div>
  </div>

  <div class="metric-card">
    <div class="metric-icon icon-green"><i class="fa-solid fa-circle-check"></i></div>
    <div class="metric-info">
      <div class="m-val"><?php echo $completedCount; ?></div>
      <div class="m-lbl">Past Completed</div>
    </div>
  </div>

  <div class="metric-card">
    <div class="metric-icon icon-red"><i class="fa-solid fa-trash-can"></i></div>
    <div class="metric-info">
      <div class="m-val"><?php echo $deletedCount; ?></div>
      <div class="m-lbl">Deleted Orders</div>
    </div>
  </div>

  <div class="metric-card">
    <div class="metric-icon icon-amber"><i class="fa-solid fa-ban"></i></div>
    <div class="metric-info">
      <div class="m-val"><?php echo $cancelledCount; ?></div>
      <div class="m-lbl">Cancelled / Refunded</div>
    </div>
  </div>
</div>

<!-- Filter Tabs & Search Bar -->
<div class="filter-bar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; margin-bottom:1.2rem;">
  <div style="display:flex; gap:8px; flex-wrap:wrap;" id="historyFilterTabs">
    <button type="button" class="btn btn-sm btn-primary filter-tab active" data-filter="all">
      All History (<?php echo $totalHistory; ?>)
    </button>
    <button type="button" class="btn btn-sm btn-outline filter-tab" data-filter="completed">
      <i class="fa-solid fa-circle-check" style="color:var(--success);"></i> Past Completed (<?php echo $completedCount; ?>)
    </button>
    <button type="button" class="btn btn-sm btn-outline filter-tab" data-filter="deleted">
      <i class="fa-solid fa-trash-can" style="color:#ef4444;"></i> Deleted (<?php echo $deletedCount; ?>)
    </button>
    <?php if ($cancelledCount > 0): ?>
      <button type="button" class="btn btn-sm btn-outline filter-tab" data-filter="cancelled">
        <i class="fa-solid fa-ban" style="color:#f59e0b;"></i> Cancelled (<?php echo $cancelledCount; ?>)
      </button>
    <?php endif; ?>
  </div>

  <div style="max-width:320px; width:100%;">
    <input type="text" id="historySearchInput" class="form-control" placeholder="Search by ID, title, or subject...">
  </div>
</div>

<!-- Historical Assignments Table -->
<div class="table-card">
  <div class="table-responsive">
    <table class="data-table" id="historyTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Title & Subject</th>
          <th>Type</th>
          <th>Date Archived</th>
          <th>Status</th>
          <th>Investment</th>
          <th style="text-align:center;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($historyAssignments)): ?>
          <tr id="emptyHistoryRow">
            <td colspan="7" style="text-align:center; padding:3.5rem 1rem;">
              <div style="font-size:2.5rem; color:var(--text-dim); margin-bottom:0.8rem;">
                <i class="fa-solid fa-clock-rotate-left"></i>
              </div>
              <h4 style="color:var(--text-main); margin-bottom:0.4rem;">No History Records Found</h4>
              <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1rem;">
                Your completed, delivered, and deleted assignments will appear here for record-keeping.
              </p>
              <a href="/student/assignments.php" class="btn btn-primary btn-sm">View Active Assignments</a>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($historyAssignments as $asm): 
            $status = $asm['status'] ?? 'Unknown';
            $badgeClass = get_status_badge_class($status);
            $isDeleted = ($status === 'Deleted');
            $isCompleted = in_array($status, ['Completed', 'Delivered']);
            $isCancelled = in_array($status, ['Cancelled', 'Refunded']);
            
            $category = $isDeleted ? 'deleted' : ($isCompleted ? 'completed' : 'cancelled');
            $archiveDate = date('M d, Y', strtotime($asm['updated_at'] ?? ($asm['created_at'] ?? 'now')));
          ?>
            <tr class="history-row" data-category="<?php echo $category; ?>" data-search="<?php echo strtolower($asm['assignment_id'] . ' ' . $asm['title'] . ' ' . $asm['subject']); ?>">
              <td>
                <strong style="color:var(--secondary); font-family:monospace; font-size:0.95rem;">
                  <?php echo htmlspecialchars($asm['assignment_id']); ?>
                </strong>
              </td>
              <td>
                <div style="font-weight:700; color:var(--text-main);"><?php echo htmlspecialchars($asm['title']); ?></div>
                <small style="color:var(--text-muted);">
                  <?php echo htmlspecialchars($asm['subject']); ?> &bull; <?php echo (int)$asm['word_count']; ?> words
                </small>
              </td>
              <td><?php echo htmlspecialchars($asm['assignment_type'] ?? 'Essay'); ?></td>
              <td>
                <span style="color:var(--text-muted); font-size:0.88rem;">
                  <i class="fa-regular fa-calendar" style="margin-right:4px;"></i><?php echo $archiveDate; ?>
                </span>
              </td>
              <td>
                <?php if ($isDeleted): ?>
                  <span class="badge badge-danger" style="background:#fee2e2; color:#991b1b; border:1px solid #fecaca;">
                    <i class="fa-solid fa-trash-can"></i> Deleted
                  </span>
                <?php else: ?>
                  <span class="badge <?php echo $badgeClass; ?>">
                    <?php echo htmlspecialchars($status); ?>
                  </span>
                <?php endif; ?>
              </td>
              <td>
                <strong><?php echo format_currency_amount($asm['final_price'] ?? 0, $asm['currency'] ?? 'USD'); ?></strong>
              </td>
              <td style="text-align:center;">
                <div style="display:inline-flex; gap:6px; align-items:center; justify-content:center;">
                  <a href="/student/assignment-detail.php?id=<?php echo urlencode($asm['assignment_id']); ?>" class="btn btn-outline btn-sm" title="View Assignment Details">
                    Details &rarr;
                  </a>
                  <?php if ($isCompleted): ?>
                    <a href="/student/invoice.php?id=<?php echo urlencode($asm['assignment_id']); ?>" target="_blank" class="btn btn-outline btn-sm" title="Download Official Invoice">
                      <i class="fa-solid fa-file-pdf"></i>
                    </a>
                  <?php elseif ($isDeleted || $isCancelled): ?>
                    <a href="/student/new-assignment.php" class="btn btn-outline btn-sm" title="Submit Similar Order" style="color:var(--primary); border-color:var(--primary);">
                      <i class="fa-solid fa-rotate"></i> Reorder
                    </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <tr id="noMatchRow" style="display:none;">
            <td colspan="7" style="text-align:center; padding:2rem; color:var(--text-muted);">
              No historical assignments match your search or filter.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('historySearchInput');
  const tabs = document.querySelectorAll('.filter-tab');
  const rows = document.querySelectorAll('.history-row');
  const noMatchRow = document.getElementById('noMatchRow');
  let currentCategory = 'all';

  function filterTable() {
    const q = (searchInput ? searchInput.value : '').toLowerCase().trim();
    let visibleCount = 0;

    rows.forEach(r => {
      const category = r.getAttribute('data-category');
      const searchData = r.getAttribute('data-search') || '';
      const matchCat = (currentCategory === 'all' || category === currentCategory);
      const matchSearch = (!q || searchData.indexOf(q) !== -1);

      if (matchCat && matchSearch) {
        r.style.display = '';
        visibleCount++;
      } else {
        r.style.display = 'none';
      }
    });

    if (noMatchRow) {
      noMatchRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
    }
  }

  tabs.forEach(t => {
    t.addEventListener('click', () => {
      tabs.forEach(tab => {
        tab.classList.remove('btn-primary', 'active');
        tab.classList.add('btn-outline');
      });
      t.classList.add('btn-primary', 'active');
      t.classList.remove('btn-outline');
      currentCategory = t.getAttribute('data-filter');
      filterTable();
    });
  });

  if (searchInput) {
    searchInput.addEventListener('input', filterTable);
  }
});
</script>

</div>
</div>
</body>
</html>
