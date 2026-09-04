<?php
require_once __DIR__ . '/includes/auth.php';
Auth::logout();
header("Location: /login.php?msg=You have been logged out successfully.");
exit;
