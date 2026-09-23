<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
Auth::checkLoggedIn();
$currentUser = Auth::currentUser();
$userRole = $currentUser['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
  <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . " | Ace Assignment Helps Portal" : "Ace Assignment Helps Portal"; ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/assets/css/portal.css">
  <link rel="icon" type="image/png" href="/assets/image/logo.png">
  <link rel="apple-touch-icon" href="/assets/image/logo.png">
  <script src="/assets/js/portal.js" defer></script>
</head>
<body class="portal-body">

<div class="portal-wrapper">
  <!-- Mobile Sidebar Backdrop Overlay -->
  <div class="sidebar-overlay" id="portalSidebarOverlay"></div>

  <?php include __DIR__ . '/portal_sidebar.php'; ?>

  <div class="portal-main">
    <header class="portal-topbar">
      <div style="display:flex; align-items:center; gap:12px;">
        <button class="sidebar-toggle-btn" id="portalSidebarBtn" aria-label="Toggle Portal Sidebar Menu">
          <i class="fa-solid fa-bars"></i>
        </button>

        <div class="topbar-title">
          <?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard Overview'; ?>
        </div>
      </div>

      <div class="topbar-user">
        <!-- Notification Bell Widget -->
        <?php $unreadNotifCount = function_exists('get_unread_notifications_count') ? get_unread_notifications_count($currentUser['id'], $userRole) : 0; ?>
        <a href="/<?php echo strtolower($userRole); ?>/notifications.php" style="position:relative; color:var(--text-muted); font-size:1.15rem; margin-right:6px; display:inline-flex; align-items:center;" title="Notifications (<?php echo $unreadNotifCount; ?> unread)">
          <i class="fa-solid fa-bell"></i>
          <?php if ($unreadNotifCount > 0): ?>
            <span style="position:absolute; top:-4px; right:-6px; background:#ef4444; color:#fff; font-size:0.65rem; font-weight:800; border-radius:10px; padding:1px 5px; min-width:14px; text-align:center; box-shadow:0 2px 4px rgba(239, 68, 68, 0.4);"><?php echo $unreadNotifCount; ?></span>
          <?php endif; ?>
        </a>

        <a href="/" target="_blank" class="btn btn-outline btn-sm" style="margin-right:4px; display:inline-flex;">
          <i class="fa-solid fa-globe"></i> <span class="user-meta-desktop">Website</span>
        </a>

        <div style="text-align:right;" class="user-meta-desktop">
          <div style="font-weight:700; color:var(--text-main); font-size:0.88rem;"><?php echo htmlspecialchars($currentUser['name']); ?></div>
          <small style="color:var(--text-muted); font-weight:600; font-size:0.7rem; text-transform:uppercase; display:block;"><?php echo htmlspecialchars($userRole); ?></small>
        </div>

        <div class="user-avatar" title="<?php echo htmlspecialchars($currentUser['name']); ?>">
          <?php echo strtoupper(substr($currentUser['name'], 0, 1)); ?>
        </div>

        <a href="/logout.php" title="Logout" style="color:var(--text-muted); font-size:1.1rem; margin-left:4px;">
          <i class="fa-solid fa-right-from-bracket"></i>
        </a>
      </div>
    </header>

    <div class="portal-content">


