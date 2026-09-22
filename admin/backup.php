<?php
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Admin');
require_once __DIR__ . '/../includes/helpers.php';

if (isset($_GET['download']) && $_GET['download'] === 'sql') {
    $sqlFile = __DIR__ . '/../database.sql';
    if (file_exists($sqlFile)) {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="database_backup_' . date('Y-m-d_H-i-s') . '.sql"');
        readfile($sqlFile);
        exit;
    }
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $assignments = DataStore::getCollection('assignments');
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="assignments_report_' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Assignment ID', 'Student ID', 'Title', 'Subject', 'Word Count', 'Price', 'Status', 'Deadline', 'Created At']);
    foreach ($assignments as $a) {
        fputcsv($out, [$a['assignment_id'], $a['student_id'], $a['title'], $a['subject'], $a['word_count'], $a['final_price'], $a['status'], $a['deadline'], $a['created_at']]);
    }
    fclose($out);
    exit;
}

$pageTitle = "Data Backup & Exporter";
include __DIR__ . '/../includes/portal_header.php';
?>

<div style="max-width:650px; margin:0 auto;">
  <div class="calc-card" style="padding:2.5rem; text-align:center;">
    <div style="width:64px; height:64px; background:rgba(99, 102, 241, 0.15); color:var(--primary); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:2rem; margin:0 auto 1.2rem auto;">
      <i class="fa-solid fa-database"></i>
    </div>
    <h2 style="font-size:1.8rem; color:#fff; margin-bottom:0.5rem;">Database Backup & Data Exporter</h2>
    <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:2rem;">Export raw MySQL database snapshots (.sql) or download structured CSV business reports.</p>

    <div style="display:flex; flex-direction:column; gap:1.2rem;">
      <a href="/admin/backup.php?download=sql" class="btn btn-primary btn-lg" style="padding:1rem;">
        <i class="fa-solid fa-download"></i> Download Full MySQL Database Backup (.sql)
      </a>

      <a href="/admin/backup.php?export=csv" class="btn btn-outline btn-lg" style="padding:1rem;">
        <i class="fa-solid fa-file-csv"></i> Export Assignments Master CSV Report (.csv)
      </a>
    </div>
  </div>
</div>

<script src="/assets/js/portal.js"></script>
</div>
</div>
</body>
</html>
