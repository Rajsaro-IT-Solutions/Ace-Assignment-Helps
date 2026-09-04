<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/auth.php';
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
        <a href="/<?php echo strtolower($userRole); ?>/notifications.php" style="position:relative; color:var(--text-muted); font-size:1.15rem; margin-right:6px; display:inline-flex; align-items:center;" title="Notifications">
          <i class="fa-solid fa-bell"></i>
          <span style="position:absolute; top:-4px; right:-6px; background:#ef4444; color:#fff; font-size:0.65rem; font-weight:800; border-radius:10px; padding:1px 5px; min-width:14px; text-align:center;">2</span>
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

<!-- Embedded Floating Live Chat Drawer (In-Portal Communication) -->
<button class="floating-chat-btn" id="portalLiveChatTrigger" title="Open Live Portal Chat">
  <i class="fa-brands fa-whatsapp"></i>
</button>

<div class="portal-chat-drawer" id="portalLiveChatDrawer">
  <div class="chat-drawer-header">
    <div style="display:flex; align-items:center; gap:10px;">
      <div style="width:36px; height:36px; background:rgba(255,255,255,0.2); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.1rem;">
        <i class="fa-brands fa-whatsapp"></i>
      </div>
      <div>
        <div style="font-weight:700; font-size:0.95rem;">Ace Live Portal Support</div>
        <small style="opacity:0.9; font-size:0.75rem;"><i class="fa-solid fa-circle" style="color:#a7f3d0; font-size:0.5rem; vertical-align:middle;"></i> Online &bull; Direct Portal Chat</small>
      </div>
    </div>
    <div style="display:flex; align-items:center; gap:8px;">
      <!-- Optional external link button ONLY if requested -->
      <a href="https://wa.me/15559876543?text=Hi%20AceAssignment!%20I%20am%20chatting%20from%20the%20portal." target="_blank" class="btn btn-sm" style="background:rgba(255,255,255,0.2); color:#fff; border:none; padding:4px 8px; font-size:0.75rem;" title="Open in external WhatsApp App/Web">
        <i class="fa-solid fa-arrow-up-right-from-square"></i> App
      </a>
      <button id="closePortalChatDrawer" style="background:none; border:none; color:#fff; font-size:1.2rem; cursor:pointer;">&times;</button>
    </div>
  </div>

  <div class="chat-drawer-body" id="portalChatBody">
    <div class="chat-bubble support">
      👋 Hello <strong><?php echo htmlspecialchars($currentUser['name']); ?></strong>! Welcome to Ace Assignment Helps live support. How can we assist your assignment today?
    </div>
  </div>

  <div class="chat-drawer-footer">
    <input type="text" id="portalChatInput" class="form-control" placeholder="Type your message here..." style="font-size:0.88rem;">
    <button id="btnSendPortalChat" class="btn btn-success btn-sm"><i class="fa-solid fa-paper-plane"></i></button>
  </div>
</div>
