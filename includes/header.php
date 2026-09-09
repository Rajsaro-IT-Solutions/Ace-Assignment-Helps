<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isLoggedIn = isset($_SESSION['user']);
$userRole = $_SESSION['user']['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
  <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . " | Ace Assignment Helps" : "Ace Assignment Helps - #1 Academic Assignment Writing Service (UK, USA, Ireland, Australia, Canada & India)"; ?></title>
  
  <!-- Global SEO Meta Tags -->
  <meta name="description" content="Ace Assignment Helps provides top-rated university assignment writing, essay help, dissertation assistance & coding support for students in the United Kingdom (UK), United States (USA), Ireland, Australia, Canada, and India. 100% Turnitin plagiarism-free guarantee.">
  <meta name="keywords" content="Ace Assignment Helps, assignment help UK, essay writer USA, dissertation help Ireland, assignment writing service Australia, coding help India, university essay help Canada, academic paper writing">
  <meta name="robots" content="index, follow">
  
  <!-- Multi-Country Geo-Targeting SEO Tags -->
  <meta name="geo.region" content="GB, US, IE, AU, CA, IN">
  <meta name="geo.placename" content="London, New York, Dublin, Sydney, Toronto, Delhi">
  <meta http-equiv="content-language" content="en-gb, en-us, en-ie, en-au, en-ca, en-in">
  <link rel="canonical" href="http://127.0.0.1:8085/">

  <!-- Hreflang Tags for International Country SEO -->
  <link rel="alternate" hreflang="en-gb" href="http://127.0.0.1:8085/?country=uk" />
  <link rel="alternate" hreflang="en-us" href="http://127.0.0.1:8085/?country=us" />
  <link rel="alternate" hreflang="en-ie" href="http://127.0.0.1:8085/?country=ireland" />
  <link rel="alternate" hreflang="en-au" href="http://127.0.0.1:8085/?country=australia" />
  <link rel="alternate" hreflang="en-ca" href="http://127.0.0.1:8085/?country=canada" />
  <link rel="alternate" hreflang="en-in" href="http://127.0.0.1:8085/?country=india" />

  <!-- Open Graph / Social Media -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="http://127.0.0.1:8085/">
  <meta property="og:title" content="Ace Assignment Helps - Trusted Global Academic Writing Service">
  <meta property="og:description" content="24/7 PhD expert assignment writing help across UK, USA, Ireland, Australia, Canada, and India with 0% Turnitin plagiarism guarantee.">
  <meta property="og:image" content="http://127.0.0.1:8085/assets/images/og-banner.jpg">

  <!-- Schema.org JSON-LD Structured Data for International Service -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "EducationalOrganization",
    "name": "Ace Assignment Helps",
    "url": "http://127.0.0.1:8085/",
    "logo": "http://127.0.0.1:8085/assets/images/logo.png",
    "description": "Global academic assignment writing and dissertation support service.",
    "areaServed": ["United Kingdom", "United States", "Ireland", "Australia", "Canada", "India"],
    "aggregateRating": {
      "@type": "AggregateRating",
      "ratingValue": "4.95",
      "reviewCount": "15400"
    }
  }
  </script>

  <!-- Google Fonts & FontAwesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<header class="header-glass">
  <div class="container navbar">
    <a href="/" class="logo">
      <div class="logo-icon"><i class="fa-solid fa-graduation-cap"></i></div>
      <span>Ace Assignment Helps</span>
    </a>

    <!-- Clean Responsive Nav Menu Drawer -->
    <ul class="nav-menu" id="primaryNavMenu">
      <li><a href="/" class="nav-link">Home</a></li>
      <li><a href="/services.php" class="nav-link">Services</a></li>
      <li><a href="/pricing.php" class="nav-link">Pricing</a></li>
      <li><a href="/subjects.php" class="nav-link">Subjects</a></li>
      <li><a href="/how-it-works.php" class="nav-link">How It Works</a></li>
      <li><a href="/reviews.php" class="nav-link">Reviews</a></li>
      <li><a href="/faq.php" class="nav-link">FAQ</a></li>
      <li><a href="/contact.php" class="nav-link">Contact</a></li>

      <!-- Mobile Action Buttons inside Menu Drawer -->
      <li class="mobile-nav-actions">
        <?php if ($isLoggedIn): ?>
          <?php 
            $targetPortal = ($userRole === 'Admin') ? '/admin/index.php' : (($userRole === 'Allocator') ? '/allocator/index.php' : '/student/index.php');
          ?>
          <a href="<?php echo $targetPortal; ?>" class="btn btn-primary" style="width:100%;"><i class="fa-solid fa-gauge"></i> My Dashboard</a>
          <a href="/logout.php" class="btn btn-outline" style="width:100%;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        <?php else: ?>
          <a href="/login.php" class="btn btn-outline" style="width:100%;"><i class="fa-solid fa-right-to-bracket"></i> Student Login</a>
          <a href="/register.php" class="btn btn-primary" style="width:100%;"><i class="fa-solid fa-user-plus"></i> Register Student Account</a>
        <?php endif; ?>
      </li>
    </ul>

    <div class="nav-actions">
      <?php if ($isLoggedIn): ?>
        <?php 
          $targetPortal = ($userRole === 'Admin') ? '/admin/index.php' : (($userRole === 'Allocator') ? '/allocator/index.php' : '/student/index.php');
        ?>
        <a href="<?php echo $targetPortal; ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-gauge"></i> My Dashboard</a>
        <a href="/logout.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
      <?php else: ?>
        <a href="/login.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-right-to-bracket"></i> Student Login</a>
        <a href="/register.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-user-plus"></i> Register Student</a>
      <?php endif; ?>

      <!-- Mobile Hamburger Toggle Icon -->
      <button class="mobile-toggle" id="mobileMenuBtn" aria-label="Toggle Navigation Menu">
        <i class="fa-solid fa-bars"></i>
      </button>
    </div>
  </div>
</header>
