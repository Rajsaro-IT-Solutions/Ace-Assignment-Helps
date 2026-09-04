<?php
$pageTitle = "Email System Logs & Gateway";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Admin');
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';
?>

<div class="table-card">
  <div class="table-header">
    <h3 style="font-size:1.1rem; color:#fff;"><i class="fa-solid fa-envelope-open-text" style="color:var(--primary);"></i> Automated Email System Delivery Logs</h3>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Event ID</th>
          <th>Recipient</th>
          <th>Subject Email</th>
          <th>Provider</th>
          <th>Status</th>
          <th>Timestamp</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>EML-9901</td>
          <td>sarah.jenkins@stanford.edu</td>
          <td>Assignment Submission Confirmation (ACE-2026-000101)</td>
          <td>SendGrid API</td>
          <td><span class="badge badge-success">Delivered</span></td>
          <td>2026-09-01 12:05:10</td>
        </tr>
        <tr>
          <td>EML-9902</td>
          <td>liam.h@oxford.ac.uk</td>
          <td>Official Payment Receipt (ACE-2026-000102)</td>
          <td>Amazon SES</td>
          <td><span class="badge badge-success">Delivered</span></td>
          <td>2026-09-02 14:36:00</td>
        </tr>
        <tr>
          <td>EML-9903</td>
          <td>robert.vance@experts.com</td>
          <td>New Expert Allocation Alert (ACE-2026-000101)</td>
          <td>SendGrid API</td>
          <td><span class="badge badge-success">Delivered</span></td>
          <td>2026-09-01 14:30:45</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

</div>
</div>
</body>
</html>
