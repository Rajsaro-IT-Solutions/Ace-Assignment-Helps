<footer class="footer-light">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="logo" style="margin-bottom:1.2rem; color:#fff;">
          <img src="/assets/image/logo.png" alt="Ace Assignment Helps" class="logo-img"
            style="background:#ffffff; padding:2px; border-radius:8px;">
          <span style="color:#ffffff;">Ace Assignment Helps</span>
        </div>
        <p style="color:#94a3b8; font-size:0.92rem; line-height:1.7; margin-bottom:1.5rem; max-width:380px;">
          Ace Assignment Helps is the world's leading academic assistance platform, empowering university students
          across the US, UK, Australia, and Canada to achieve top grades with 100% original Turnitin-scanned
          assignments.
        </p>
        <?php
        $footerWhatsapp = class_exists('DataStore') ? DataStore::getSetting('whatsapp_phone', '+91 8233432123') : '+91 8233432123';
        $footerWhatsappClean = preg_replace('/[^0-9]/', '', $footerWhatsapp);
        if (empty($footerWhatsappClean)) {
            $footerWhatsappClean = '918233432123';
            $footerWhatsapp = '+91 8233432123';
        }
        ?>
        <div style="display:flex; gap:12px; font-size:1.2rem; color:#94a3b8;">
          <a href="#"><i class="fa-brands fa-facebook"></i></a>
          <a href="#"><i class="fa-brands fa-twitter"></i></a>
          <a href="#"><i class="fa-brands fa-linkedin"></i></a>
          <a href="https://wa.me/<?php echo $footerWhatsappClean; ?>?text=Hi%20Ace%20Assignment%20Helps%2C%20I%20need%20assistance%20with%20my%20assignment." target="_blank" rel="noopener noreferrer" title="WhatsApp Support (<?php echo htmlspecialchars($footerWhatsapp); ?>)"><i class="fa-brands fa-whatsapp" style="color:#25D366;"></i></a>
        </div>
      </div>

      <div class="footer-col">
        <h5>Our Services</h5>
        <ul class="footer-links">
          <li><a href="/services.php">Essay Writing Help</a></li>
          <li><a href="/services.php">Dissertation & Thesis</a></li>
          <li><a href="/services.php">Programming & Code</a></li>
          <li><a href="/services.php">Case Study Analysis</a></li>
          <li><a href="/services.php">Nursing & Clinical Care</a></li>
          <li><a href="/services.php">Law Paper Writing</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h5>Quick Links</h5>
        <ul class="footer-links">
          <li><a href="/subjects.php">Courses & Subjects</a></li>
          <li><a href="/blog.php">Study Guides & Blog</a></li>
          <li><a href="/pricing.php">Transparent Pricing</a></li>
          <li><a href="/how-it-works.php">4-Step Workflow</a></li>
          <li><a href="/reviews.php">Verified Reviews</a></li>
          <li><a href="/faq.php">Student FAQs</a></li>
          <li><a href="/contact.php">24/7 Support Desk</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h5>Legal & Trust</h5>
        <ul class="footer-links">
          <li><a href="/privacy.php">Privacy & Anonymity</a></li>
          <li><a href="/terms.php">Terms of Service</a></li>
          <li><a href="/refund.php">Money-Back Refund</a></li>
          <li><a href="/submit-assignment.php">Submit Order</a></li>
          <li><a href="/checkout.php"><i class="fa-solid fa-lock" style="font-size:0.75rem; color:#10b981;"></i> Secure Checkout</a></li>
          <li><a href="/login.php">Student Portal Login</a></li>
        </ul>
      </div>
    </div>

    <div
      style="border-top:1px solid #334155; padding-top:2rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; font-size:0.85rem; color:#94a3b8;">
      <div>&copy; <?php echo date('Y'); ?> <strong>Ace Assignment Helps</strong>. All rights reserved globally.</div>
      <div style="display:flex; gap:15px;">
        <span><i class="fa-solid fa-shield-halved" style="color:#10b981;"></i> 256-Bit SSL Encrypted</span>
        <span><i class="fa-solid fa-lock" style="color:#6366f1;"></i> Masked Data Protection</span>
      </div>
    </div>
  </div>
</footer>

<?php
// Floating WhatsApp Icon - Strictly for the public website, never on portals
$currPath = $_SERVER['REQUEST_URI'] ?? '';
$isPortal = (strpos($currPath, '/student') !== false || strpos($currPath, '/admin') !== false || strpos($currPath, '/allocator') !== false);
if (!$isPortal):
  ?>
  <!-- WhatsApp Floating Widget (Left Side) -->
  <div class="whatsapp-floating-wrap">
    <a href="https://wa.me/<?php echo $footerWhatsappClean; ?>?text=Hi%20Ace%20Assignment%20Helps%2C%20I%20need%20assistance%20with%20my%20assignment."
      target="_blank" rel="noopener noreferrer" class="whatsapp-float-btn"
      title="Chat with us on WhatsApp (<?php echo htmlspecialchars($footerWhatsapp); ?>)" aria-label="Chat with us on WhatsApp (<?php echo htmlspecialchars($footerWhatsapp); ?>)">
      <div class="whatsapp-pulse-ring"></div>
      <i class="fa-brands fa-whatsapp"></i>
      <span class="whatsapp-badge-online"></span>
      <span class="whatsapp-tooltip-text">Chat with PhD Experts</span>
    </a>
  </div>

  <style>
    .whatsapp-floating-wrap {
      position: fixed;
      bottom: 24px;
      left: 24px;
      z-index: 99999;
    }

    .whatsapp-float-btn {
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 60px;
      height: 60px;
      background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
      color: #ffffff !important;
      border-radius: 50%;
      box-shadow: 0 8px 24px rgba(37, 211, 102, 0.4), 0 2px 6px rgba(0, 0, 0, 0.15);
      text-decoration: none;
      font-size: 32px;
      transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .whatsapp-float-btn:hover {
      transform: scale(1.1) translateY(-2px);
      box-shadow: 0 12px 30px rgba(37, 211, 102, 0.55), 0 4px 10px rgba(0, 0, 0, 0.2);
    }

    .whatsapp-pulse-ring {
      position: absolute;
      top: -4px;
      left: -4px;
      right: -4px;
      bottom: -4px;
      border: 2px solid #25D366;
      border-radius: 50%;
      animation: wa-pulse 2s infinite cubic-bezier(0.45, 0, 0.2, 1);
      pointer-events: none;
    }

    @keyframes wa-pulse {
      0% {
        transform: scale(0.95);
        opacity: 0.9;
      }

      70% {
        transform: scale(1.35);
        opacity: 0;
      }

      100% {
        transform: scale(1.35);
        opacity: 0;
      }
    }

    .whatsapp-badge-online {
      position: absolute;
      top: 2px;
      right: 2px;
      width: 14px;
      height: 14px;
      background: #10b981;
      border: 2.5px solid #ffffff;
      border-radius: 50%;
    }

    .whatsapp-tooltip-text {
      position: absolute;
      left: 72px;
      background: #0f172a;
      color: #ffffff;
      font-family: var(--font-sans, system-ui, -apple-system, sans-serif);
      font-size: 0.82rem;
      font-weight: 600;
      white-space: nowrap;
      padding: 6px 14px;
      border-radius: 20px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25);
      opacity: 0;
      visibility: hidden;
      transform: translateX(-10px);
      transition: all 0.3s ease;
      pointer-events: none;
    }

    .whatsapp-tooltip-text::before {
      content: '';
      position: absolute;
      top: 50%;
      left: -5px;
      transform: translateY(-50%);
      border-width: 5px 5px 5px 0;
      border-style: solid;
      border-color: transparent #0f172a transparent transparent;
    }

    .whatsapp-float-btn:hover .whatsapp-tooltip-text {
      opacity: 1;
      visibility: visible;
      transform: translateX(0);
    }

    @media (max-width: 640px) {
      .whatsapp-floating-wrap {
        bottom: 18px;
        left: 18px;
      }

      .whatsapp-float-btn {
        width: 54px;
        height: 54px;
        font-size: 28px;
      }

      .whatsapp-tooltip-text {
        display: none;
      }
    }
  </style>
<?php endif; ?>

<script src="/assets/js/main.js"></script>
</body>

</html>