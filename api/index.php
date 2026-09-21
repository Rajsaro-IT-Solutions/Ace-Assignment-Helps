<?php
// Vercel Serverless Entrypoint & Front Router
$baseDir = dirname(__DIR__);
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$parsedPath = parse_url($requestUri, PHP_URL_PATH);
$route = ltrim($parsedPath, '/');

// When running under built-in CLI server (php -S)
if (php_sapi_name() === 'cli-server') {
    if (!empty($route) && is_file($baseDir . '/' . $route)) {
        return false; // Let PHP built-in server handle existing static files directly
    }
}

// Route non-homepage requests when routed through api/index.php on Vercel
if (!empty($route) && $route !== 'index.php' && $route !== 'api/index.php') {
    $target = $baseDir . '/' . $route;

    // Check directory index (e.g. /admin -> /admin/index.php)
    if (is_dir($target)) {
        $target = rtrim($target, '/') . '/index.php';
    } elseif (!file_exists($target) && file_exists($target . '.php')) {
        $target .= '.php';
    }

    if (file_exists($target) && !is_dir($target)) {
        $ext = strtolower(pathinfo($target, PATHINFO_EXTENSION));
        // Serve static assets if not intercepted by Vercel routes
        if ($ext !== 'php') {
            $mimeTypes = [
                'css'   => 'text/css',
                'js'    => 'application/javascript',
                'json'  => 'application/json',
                'png'   => 'image/png',
                'jpg'   => 'image/jpeg',
                'jpeg'  => 'image/jpeg',
                'gif'   => 'image/gif',
                'svg'   => 'image/svg+xml',
                'ico'   => 'image/x-icon',
                'woff'  => 'font/woff',
                'woff2' => 'font/woff2',
                'ttf'   => 'font/ttf',
                'pdf'   => 'application/pdf',
                'webp'  => 'image/webp'
            ];
            if (isset($mimeTypes[$ext])) {
                header('Content-Type: ' . $mimeTypes[$ext]);
            }
            readfile($target);
            exit;
        }

        // Execute targeted PHP script in its own directory context
        chdir(dirname($target));
        require $target;
        exit;
    }
}

// Set working directory to project root for homepage rendering
chdir($baseDir);

$pageTitle = "Ace Assignment Helps - #1 University Assignment Assistance (UK, USA, Ireland, Australia, Canada & India)";
require_once $baseDir . '/includes/auth.php';
require_once $baseDir . '/includes/helpers.php';
$currentUser = Auth::currentUser();
$homepageCourses = array_filter(DataStore::getCollection('courses'), function($c) { return ($c['status'] ?? 'Active') === 'Active'; });
$homepageBlogs = DataStore::getCollection('blogs');

include $baseDir . '/includes/header.php';
?>

<!-- HERO SECTION -->
<section class="hero">
  <div class="container hero-grid">
    <div>
      <div class="badge badge-primary" style="margin-bottom: 1rem;">
        <i class="fa-solid fa-earth-americas"></i> Trusted by Students Across UK, USA, Ireland, Australia, Canada & India
      </div>
      
      <h1 class="hero-title">
        Ace Your University Grades with <span>Ace Assignment Helps</span>
      </h1>
      
      <p class="hero-subtitle">
        Get 100% confidential, plagiarism-free academic assistance written by 400+ accredited PhD experts. Guaranteed on-time SLA delivery for Essays, Dissertations, Coding, Case Studies, and Nursing Papers tailored to university standards globally.
      </p>

      <!-- Stat Counters Grid -->
      <div class="grid-4" style="margin-bottom: 2rem;">
        <div style="background:#ffffff; border:1px solid var(--border-color); padding:0.9rem; border-radius:var(--radius-sm); box-shadow:var(--shadow-sm); text-align:center;">
          <div style="font-family:var(--font-head); font-size:1.6rem; font-weight:800; color:var(--primary);">15,400+</div>
          <small style="color:var(--text-muted); font-weight:600;">Assignments Delivered</small>
        </div>
        <div style="background:#ffffff; border:1px solid var(--border-color); padding:0.9rem; border-radius:var(--radius-sm); box-shadow:var(--shadow-sm); text-align:center;">
          <div style="font-family:var(--font-head); font-size:1.6rem; font-weight:800; color:var(--success);">99.4%</div>
          <small style="color:var(--text-muted); font-weight:600;">Pass Rate</small>
        </div>
        <div style="background:#ffffff; border:1px solid var(--border-color); padding:0.9rem; border-radius:var(--radius-sm); box-shadow:var(--shadow-sm); text-align:center;">
          <div style="font-family:var(--font-head); font-size:1.6rem; font-weight:800; color:var(--secondary);">4.95 / 5</div>
          <small style="color:var(--text-muted); font-weight:600;">Student Rating</small>
        </div>
        <div style="background:#ffffff; border:1px solid var(--border-color); padding:0.9rem; border-radius:var(--radius-sm); box-shadow:var(--shadow-sm); text-align:center;">
          <div style="font-family:var(--font-head); font-size:1.6rem; font-weight:800; color:var(--accent);">400+</div>
          <small style="color:var(--text-muted); font-weight:600;">PhD Experts</small>
        </div>
      </div>

      <div style="display:flex; gap:1rem; flex-wrap:wrap;">
        <a href="/submit-assignment.php" class="btn btn-primary btn-lg"><i class="fa-solid fa-paper-plane"></i> Submit Assignment Order</a>
        <a href="/pricing.php" class="btn btn-outline btn-lg"><i class="fa-solid fa-calculator"></i> View Price Matrix</a>
      </div>
    </div>

    <!-- Live Price Calculator Widget with Multi-Currency & Geo-Selection -->
    <div>
      <div class="calc-card">
        <h3><i class="fa-solid fa-calculator" style="color:var(--primary);"></i> Instant Global Price Estimator</h3>
        
        <div class="form-group">
          <label for="country_curr_select"><i class="fa-solid fa-globe"></i> Select Your Country & Currency</label>
          <select id="country_curr_select" class="form-control">
            <option value="GBP">🇬🇧 United Kingdom (GBP £)</option>
            <option value="USD" selected>🇺🇸 United States (USD $)</option>
            <option value="EUR">🇮🇪 Ireland / EU (EUR €)</option>
            <option value="AUD">🇦🇺 Australia (AUD A$)</option>
            <option value="CAD">🇨🇦 Canada (CAD C$)</option>
            <option value="INR">🇮🇳 India (INR ₹)</option>
          </select>
        </div>

        <div class="form-group">
          <label for="acad_level">Academic Level</label>
          <select id="acad_level" class="form-control">
            <option value="undergraduate">Undergraduate / Bachelor's</option>
            <option value="postgraduate" selected>Master's / Postgraduate</option>
            <option value="doctorate">Doctorate / PhD</option>
          </select>
        </div>

        <div class="grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
          <div class="form-group">
            <label for="word_count"><i class="fa-solid fa-file-lines"></i> Word Count (250 Words = 1 Page)</label>
            <select id="word_count" class="form-control">
              <?php echo render_word_count_options(2000); ?>
            </select>
          </div>

          <div class="form-group">
            <label for="urgency">Deadline Urgency</label>
            <select id="urgency" class="form-control">
              <option value="24">Urgent (24 Hours / 1 Day)</option>
              <option value="48">Fast (2 Days)</option>
              <option value="72">3 Days</option>
              <option value="96">4 Days</option>
              <option value="120" selected>Standard (5 Days - Base Rate)</option>
              <option value="240">Relaxed (10 Days)</option>
            </select>
          </div>
        </div>

<?php
// Check active default coupon from DataStore (prefer ACE20 if active, else first active)
$ace20 = DataStore::findOne('coupons', 'code', 'ACE20');
if ($ace20 && ($ace20['status'] ?? '') === 'Active' && (empty($ace20['expires_at']) || strtotime($ace20['expires_at'] . ' 23:59:59') >= time())) {
    $defaultPromoCode = 'ACE20';
} else {
    $activeCoupons = DataStore::filter('coupons', function($c) {
        return ($c['status'] ?? '') === 'Active' && (empty($c['expires_at']) || strtotime($c['expires_at'] . ' 23:59:59') >= time());
    });
    $defaultPromoCode = !empty($activeCoupons) ? $activeCoupons[0]['code'] : '';
}
$defaultHeroCalc = calculate_assignment_price(2000, 120, 'postgraduate', 'General', $defaultPromoCode, 'USD');
?>
        <div class="form-group">
          <label for="coupon_input">Discount Promo Code</label>
          <div style="display:flex; gap:10px;">
            <input type="text" id="coupon_input" class="form-control" placeholder="Enter promo code" value="<?php echo htmlspecialchars($defaultPromoCode); ?>" style="text-transform:uppercase;">
            <button type="button" id="apply_coupon_btn" class="btn btn-outline btn-sm">Apply</button>
          </div>
          <small id="coupon_msg" style="<?php echo ($defaultHeroCalc['discount_percent'] > 0) ? 'color:var(--success); display:block;' : 'display:none;'; ?> font-weight:600; margin-top:4px;">
            <?php if ($defaultHeroCalc['discount_percent'] > 0): 
              $discFmt = ($defaultHeroCalc['currency'] === 'INR') ? number_format($defaultHeroCalc['discount_amount'], 0) : number_format($defaultHeroCalc['discount_amount'], 2);
            ?>
              <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($defaultHeroCalc['coupon_message']); ?> (-<?php echo $defaultHeroCalc['currency_symbol'] . $discFmt; ?>)
            <?php endif; ?>
          </small>
        </div>

        <!-- File Upload Accepting Any Format -->
        <div class="form-group">
          <label for="hero_files"><i class="fa-solid fa-cloud-arrow-up" style="color:var(--primary);"></i> Upload Assignment File(s) (Any Format)</label>
          <div style="border: 2px dashed var(--portal-border); padding: 0.8rem 1rem; border-radius: var(--radius-sm); text-align: center; background: #f8fafc;">
            <input type="file" id="hero_files" name="assignment_files[]" multiple class="form-control" style="font-size:0.85rem;">
            <small style="color:var(--text-muted); font-size:0.75rem; display:block; margin-top:4px;">
              Accepts <strong>ANY</strong> format: PDF, DOCX, ZIP, RAR, TXT, PY, IPYNB, XLS, PPTX, Images, etc.
            </small>
            <div id="hero_files_summary" style="margin-top:6px; font-size:0.8rem; color:var(--primary); font-weight:600;"></div>
          </div>
        </div>

        <div class="price-display-box">
          <div class="est-label">Estimated Total Price</div>
          <div class="est-amount" id="final_calc_price"><?php echo '$' . number_format($defaultHeroCalc['final_price'], 2); ?></div>
          <small style="color:var(--text-muted); display:block; margin-top:4px;">Includes Free Turnitin Plagiarism Report & Unlimited Revisions</small>
        </div>

        <button type="button" id="btnHeroProceed" class="btn btn-primary btn-lg" style="width:100%; cursor:pointer;">
          <i class="fa-solid fa-paper-plane"></i> Proceed to Order &rarr;
        </button>
        <div style="text-align:center; margin-top:8px;">
          <a href="/submit-assignment.php" id="heroFullOrderLink" style="color:var(--text-muted); font-size:0.8rem; text-decoration:none;">Or open full detailed order form &rarr;</a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- INTERNATIONAL GLOBAL GEO-TARGETING SECTION -->
<section style="padding: 3rem 0; background: #ffffff; border-y: 1px solid var(--border-color);">
  <div class="container">
    <div style="text-align: center; max-width: 750px; margin: 0 auto 2.5rem auto;">
      <h2 style="font-size: 2.1rem; margin-bottom: 0.8rem; color:#0f172a;">Tailored Academic Help for Global Universities</h2>
      <p style="color: var(--text-muted); font-size: 1rem;">
        Our academic writers understand specific university marking rubrics, referencing standards (Harvard, OSCOLA, APA 7th, IEEE, Chicago), and grading criteria across all major study destinations.
      </p>
    </div>

    <div class="grid-3">
      <div style="background:#f8fafc; border:1px solid var(--border-color); padding:1.5rem; border-radius:var(--radius-md);">
        <h3 style="font-size:1.2rem; color:var(--primary); margin-bottom:0.6rem;"><span style="font-size:1.4rem;">🇬🇧</span> United Kingdom (UK)</h3>
        <p style="color:var(--text-muted); font-size:0.88rem; line-height:1.6;">
          Expert assignment help for UK universities including Oxford, Cambridge, Imperial, KCL, Manchester, and Edinburgh. Tailored for OSCOLA legal citations, Harvard referencing, and First-Class UK grading criteria.
        </p>
      </div>

      <div style="background:#f8fafc; border:1px solid var(--border-color); padding:1.5rem; border-radius:var(--radius-md);">
        <h3 style="font-size:1.2rem; color:var(--secondary); margin-bottom:0.6rem;"><span style="font-size:1.4rem;">🇺🇸</span> United States (USA)</h3>
        <p style="color:var(--text-muted); font-size:0.88rem; line-height:1.6;">
          Custom essay and research paper writing for Ivy League and US state universities (Stanford, Harvard, MIT, NYU, Berkeley). APA 7th, MLA, and Chicago citation standards guaranteed.
        </p>
      </div>

      <div style="background:#f8fafc; border:1px solid var(--border-color); padding:1.5rem; border-radius:var(--radius-md);">
        <h3 style="font-size:1.2rem; color:var(--success); margin-bottom:0.6rem;"><span style="font-size:1.4rem;">🇮🇪</span> Ireland</h3>
        <p style="color:var(--text-muted); font-size:0.88rem; line-height:1.6;">
          Dedicated assignment assistance for Irish university students at Trinity College Dublin (TCD), UCD, University of Galway, and UCC. Specialized in Nursing care plans, Business MBA, and Computer Science.
        </p>
      </div>

      <div style="background:#f8fafc; border:1px solid var(--border-color); padding:1.5rem; border-radius:var(--radius-md);">
        <h3 style="font-size:1.2rem; color:var(--accent); margin-bottom:0.6rem;"><span style="font-size:1.4rem;">🇦🇺</span> Australia</h3>
        <p style="color:var(--text-muted); font-size:0.88rem; line-height:1.6;">
          High-grade assignment and thesis help for Group of Eight (Go8) Australian universities including Sydney, Melbourne, UNSW, and Monash. Compliant with Australian academic integrity guidelines.
        </p>
      </div>

      <div style="background:#f8fafc; border:1px solid var(--border-color); padding:1.5rem; border-radius:var(--radius-md);">
        <h3 style="font-size:1.2rem; color:#7c3aed; margin-bottom:0.6rem;"><span style="font-size:1.4rem;">🇨🇦</span> Canada</h3>
        <p style="color:var(--text-muted); font-size:0.88rem; line-height:1.6;">
          Academic writing support for Canadian colleges and universities (Toronto, UBC, McGill, Waterloo). Covers Engineering, IT coding, Healthcare, and Business Administration coursework.
        </p>
      </div>

      <div style="background:#f8fafc; border:1px solid var(--border-color); padding:1.5rem; border-radius:var(--radius-md);">
        <h3 style="font-size:1.2rem; color:#dc2626; margin-bottom:0.6rem;"><span style="font-size:1.4rem;">🇮🇳</span> India</h3>
        <p style="color:var(--text-muted); font-size:0.88rem; line-height:1.6;">
          Comprehensive assignment, dissertation, and programming guidance for Indian students in IITs, IIMs, NLUs, and premier universities, as well as students preparing to study abroad.
        </p>
      </div>
    </div>
  </div>
</section>

<!-- WHY CHOOSE ACE ASSIGNMENT HELPS SECTION -->
<section style="padding: 4rem 0;">
  <div class="container">
    <div style="text-align: center; max-width: 700px; margin: 0 auto 3rem auto;">
      <h2 style="font-size: 2.2rem; margin-bottom: 0.8rem; color:#0f172a;">Why Ace Assignment Helps is the Top Choice for Students</h2>
      <p style="color: var(--text-muted); font-size: 1.05rem;">
        We combine PhD-level domain experts, rigorous quality checks, and real-time SLA tracking to guarantee top academic scores.
      </p>
    </div>

    <div class="grid-3">
      <div class="feature-card">
        <div class="feature-icon"><i class="fa-solid fa-file-shield"></i></div>
        <h3 style="font-size: 1.2rem; margin-bottom: 0.8rem; color:#0f172a;">100% Plagiarism-Free Guarantee</h3>
        <p style="color: var(--text-muted); font-size: 0.95rem;">
          Every paper is written from scratch according to your university guidelines. Includes a complimentary Turnitin plagiarism and AI-detector report.
        </p>
      </div>

      <div class="feature-card">
        <div class="feature-icon"><i class="fa-solid fa-graduation-cap"></i></div>
        <h3 style="font-size: 1.2rem; margin-bottom: 0.8rem; color:#0f172a;">400+ Specialized PhD Experts</h3>
        <p style="color: var(--text-muted); font-size: 0.95rem;">
          Our academic team consists of professors, PhD scholars, and industry specialists across Computer Science, Law, Business, Medicine, and Engineering.
        </p>
      </div>

      <div class="feature-card">
        <div class="feature-icon"><i class="fa-solid fa-stopwatch"></i></div>
        <h3 style="font-size: 1.2rem; margin-bottom: 0.8rem; color:#0f172a;">Urgent Deadline SLA Delivery</h3>
        <p style="color: var(--text-muted); font-size: 0.95rem;">
          Tight deadline? We deliver fully researched, high-quality papers in as fast as 3 to 6 hours without compromising on academic quality.
        </p>
      </div>

      <div class="feature-card">
        <div class="feature-icon"><i class="fa-solid fa-lock"></i></div>
        <h3 style="font-size: 1.2rem; margin-bottom: 0.8rem; color:#0f172a;">Strict Identity Masking & Privacy</h3>
        <p style="color: var(--text-muted); font-size: 0.95rem;">
          Your personal identity, phone number, and university details are strictly protected with end-to-end RBAC data masking technology.
        </p>
      </div>

      <div class="feature-card">
        <div class="feature-icon"><i class="fa-solid fa-hand-holding-dollar"></i></div>
        <h3 style="font-size: 1.2rem; margin-bottom: 0.8rem; color:#0f172a;">Student-Friendly Transparent Pricing</h3>
        <p style="color: var(--text-muted); font-size: 0.95rem;">
          Affordable rates starting at just $15/page with no hidden costs. Pay securely via Credit Card, Stripe, PayPal, or NetBanking.
        </p>
      </div>

      <div class="feature-card">
        <div class="feature-icon"><i class="fa-solid fa-headset"></i></div>
        <h3 style="font-size: 1.2rem; margin-bottom: 0.8rem; color:#0f172a;">24/7 Live WhatsApp Support</h3>
        <p style="color: var(--text-muted); font-size: 0.95rem;">
          Get instant progress updates, upload additional requirements, or speak with our support staff anytime via WhatsApp or live portal chat.
        </p>
      </div>
    </div>
  </div>
</section>

<!-- DYNAMIC COURSES & ACADEMIC DISCIPLINES SECTION -->
<section style="padding: 4rem 0; background: #f8fafc; border-top: 1px solid var(--border-color);">
  <div class="container">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:2.5rem; flex-wrap:wrap; gap:1rem;">
      <div>
        <div class="badge badge-info" style="margin-bottom:0.6rem;"><i class="fa-solid fa-graduation-cap"></i> Dynamic Disciplines</div>
        <h2 style="font-size:2.2rem; color:#0f172a; margin:0;">Supported Academic Courses & Subjects</h2>
        <p style="color:var(--text-muted); font-size:1rem; margin:0.4rem 0 0 0;">Accredited PhD experts covering every syllabus module and university faculty globally.</p>
      </div>
      <a href="/subjects.php" class="btn btn-outline">Explore All <?php echo count($homepageCourses); ?>+ Courses &rarr;</a>
    </div>

    <div class="grid-3">
      <?php foreach (array_slice($homepageCourses, 0, 6) as $c): 
        $topics = is_array($c['topics']) ? $c['topics'] : array_filter(explode(',', (string)$c['topics']));
        $icon = !empty($c['icon']) ? $c['icon'] : 'fa-book-open';
      ?>
        <div class="feature-card" style="display:flex; flex-direction:column; justify-content:space-between;">
          <div>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
              <div style="width:44px; height:44px; border-radius:10px; background:rgba(99,102,241,0.1); display:flex; align-items:center; justify-content:center; color:var(--primary); font-size:1.3rem;">
                <i class="fa-solid <?php echo htmlspecialchars($icon); ?>"></i>
              </div>
              <span class="badge badge-info" style="font-size:0.75rem;"><?php echo htmlspecialchars($c['category']); ?></span>
            </div>
            <h3 style="font-size:1.18rem; margin-bottom:0.5rem; color:#0f172a;"><?php echo htmlspecialchars($c['title']); ?></h3>
            <p style="color:var(--text-muted); font-size:0.9rem; line-height:1.5; margin-bottom:1rem;"><?php echo htmlspecialchars($c['description']); ?></p>
            <?php if (!empty($topics)): ?>
              <ul style="list-style:none; padding:0; margin:0 0 1rem 0; font-size:0.85rem; color:#475569; line-height:1.7;">
                <?php foreach (array_slice($topics, 0, 3) as $t): ?>
                  <li><i class="fa-solid fa-check" style="color:var(--success); font-size:0.75rem; margin-right:6px;"></i> <?php echo htmlspecialchars(trim($t)); ?></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
          <div style="border-top:1px solid var(--border-color); padding-top:0.8rem; margin-top:auto;">
            <a href="/submit-assignment.php?subject=<?php echo urlencode($c['title']); ?>" class="btn btn-outline btn-sm" style="width:100%; text-align:center;">
              Order <?php echo htmlspecialchars($c['title']); ?> &rarr;
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- DYNAMIC RECENT BLOGS & STUDY GUIDES SECTION -->
<section style="padding: 4rem 0; background: #ffffff;">
  <div class="container">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:2.5rem; flex-wrap:wrap; gap:1rem;">
      <div>
        <div class="badge badge-primary" style="margin-bottom:0.6rem;"><i class="fa-solid fa-newspaper"></i> Academic Guides</div>
        <h2 style="font-size:2.2rem; color:#0f172a; margin:0;">Latest University Guides & Tips</h2>
        <p style="color:var(--text-muted); font-size:1rem; margin:0.4rem 0 0 0;">Expert writing insights, dissertation methods, and university success guides.</p>
      </div>
      <a href="/blog.php" class="btn btn-outline">Visit Study Hub &rarr;</a>
    </div>

    <div class="grid-3">
      <?php foreach (array_slice($homepageBlogs, 0, 3) as $hb): ?>
        <div class="feature-card" style="display:flex; flex-direction:column; justify-content:space-between;">
          <div>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.8rem;">
              <span class="badge badge-info"><?php echo htmlspecialchars($hb['category']); ?></span>
              <small style="color:var(--text-muted); font-size:0.75rem;"><?php echo htmlspecialchars($hb['published_at']); ?></small>
            </div>
            <h3 style="font-size:1.18rem; margin-bottom:0.6rem; line-height:1.4;">
              <a href="/blog-detail.php?id=<?php echo $hb['id']; ?>" style="color:#0f172a; text-decoration:none;">
                <?php echo htmlspecialchars($hb['title']); ?>
              </a>
            </h3>
            <p style="color:var(--text-muted); font-size:0.9rem; line-height:1.6; margin-bottom:1rem;">
              <?php echo htmlspecialchars($hb['excerpt']); ?>
            </p>
          </div>
          <div style="border-top:1px solid var(--border-color); padding-top:0.8rem; margin-top:auto;">
            <a href="/blog-detail.php?id=<?php echo $hb['id']; ?>" style="color:var(--primary); font-weight:700; font-size:0.9rem; text-decoration:none;">
              Read Guide &rarr;
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CALL TO ACTION BANNER -->
<section style="padding: 4rem 0; background: var(--primary-gradient); color:#ffffff; text-align:center;">
  <div class="container" style="max-width:800px;">
    <h2 style="font-size:2.4rem; color:#ffffff; margin-bottom:1rem;">Ready to Score A+ Grades in Your University Assignments?</h2>
    <p style="font-size:1.1rem; color:rgba(255,255,255,0.9); margin-bottom:2rem;">
      Join 15,400+ satisfied students in UK, USA, Ireland, Australia, Canada, and India. Submit your order now and claim your 20% discount!
    </p>
    <div style="display:flex; justify-content:center; gap:1rem; flex-wrap:wrap;">
      <a href="/submit-assignment.php" class="btn btn-secondary btn-lg" style="background:#ffffff; color:var(--primary); font-weight:800;">
        <i class="fa-solid fa-paper-plane"></i> Submit Order Now
      </a>
      <a href="/register.php" class="btn btn-outline btn-lg" style="color:#ffffff; border-color:rgba(255,255,255,0.4); background:transparent;">
        <i class="fa-solid fa-user-plus"></i> Create Free Student Account
      </a>
    </div>
  </div>
</section>

<!-- HERO QUICK ORDER MODAL -->
<div id="heroQuickOrderModal" class="modal-overlay">
  <div class="modal-box" style="padding:2rem; max-width:540px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem;">
      <h3 style="margin:0; color:var(--text-main); font-size:1.3rem;">
        <i class="fa-solid fa-file-circle-check" style="color:var(--primary);"></i> Complete Your Assignment Order
      </h3>
      <button type="button" onclick="closeModal('heroQuickOrderModal')" style="background:none; border:none; font-size:1.4rem; cursor:pointer; color:var(--text-muted);">&times;</button>
    </div>

    <!-- Order Summary Card -->
    <div style="background:rgba(99, 102, 241, 0.08); border:1px solid var(--border-glow); border-radius:var(--radius-sm); padding:1rem; margin-bottom:1.2rem;">
      <div style="display:flex; justify-content:space-between; align-items:center;">
        <div>
          <span style="font-size:0.8rem; color:var(--text-muted);">Estimated Total Investment:</span>
          <div style="font-size:1.7rem; font-weight:800; color:var(--secondary);" id="modalSummaryPrice">$17.60</div>
          <small id="modalSummaryDetails" style="color:var(--text-muted); font-size:0.82rem;">2,000 Words &bull; 5 Days</small>
        </div>
        <div id="modalFileBadge" style="text-align:right;">
          <span class="badge badge-info"><i class="fa-solid fa-paperclip"></i> No files</span>
        </div>
      </div>
    </div>

    <form id="heroQuickSubmitForm">
      <div class="form-group">
        <label>Full Name *</label>
        <input type="text" id="heroQuickName" class="form-control" required placeholder="e.g. Alex Morgan" value="<?php echo htmlspecialchars($currentUser['name'] ?? ''); ?>">
      </div>

      <div class="grid-2" style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
        <div class="form-group">
          <label>University Email *</label>
          <input type="email" id="heroQuickEmail" class="form-control" required placeholder="alex@university.edu" value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>">
        </div>
        <div class="form-group">
          <label>WhatsApp / Phone *</label>
          <input type="tel" id="heroQuickPhone" class="form-control" required placeholder="+1 (555) 000-0000" value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>">
        </div>
      </div>

      <div class="form-group">
        <label>Assignment Title / Topic *</label>
        <input type="text" id="heroQuickTitle" class="form-control" required placeholder="e.g. Business Strategy Case Study Analysis">
      </div>

      <div class="form-group">
        <label>Instructions & Guidelines (Optional)</label>
        <textarea id="heroQuickInstructions" class="form-control" rows="2" placeholder="Paste prompt guidelines, rubrics, or formatting rules..."></textarea>
      </div>

      <div id="heroQuickMsg" style="margin-bottom:1rem;"></div>

      <div style="display:flex; gap:10px;">
        <button type="submit" class="btn btn-primary" style="flex:1;"><i class="fa-solid fa-circle-check"></i> Submit Order Now</button>
        <button type="button" class="btn btn-outline" onclick="closeModal('heroQuickOrderModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script src="/assets/js/main.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const heroFileInput = document.getElementById('hero_files');
  const heroFileSummary = document.getElementById('hero_files_summary');
  const btnHeroProceed = document.getElementById('btnHeroProceed');
  const heroFullOrderLink = document.getElementById('heroFullOrderLink');
  const quickForm = document.getElementById('heroQuickSubmitForm');
  const quickMsg = document.getElementById('heroQuickMsg');

  const countrySel = document.getElementById('country_curr_select');
  const levelSel = document.getElementById('acad_level');
  const wordSel = document.getElementById('word_count');
  const urgencySel = document.getElementById('urgency');
  const couponInp = document.getElementById('coupon_input');
  const finalPrice = document.getElementById('final_calc_price');

  // Track and display selected files
  if (heroFileInput) {
    heroFileInput.addEventListener('change', () => {
      if (heroFileInput.files.length > 0) {
        const fileNames = Array.from(heroFileInput.files).map(f => f.name).join(', ');
        heroFileSummary.innerHTML = `<i class="fa-solid fa-check-circle" style="color:var(--success);"></i> ${heroFileInput.files.length} file(s) selected: <span style="color:var(--text-main); font-weight:normal;">${fileNames}</span>`;
      } else {
        heroFileSummary.innerHTML = '';
      }
    });
  }

  // Update full order link dynamically with chosen parameters
  function updateFullOrderLink() {
    if (heroFullOrderLink) {
      const c = countrySel ? countrySel.value : 'USD';
      const w = wordSel ? wordSel.value : '2000';
      const d = urgencySel ? urgencySel.value : '120';
      const cp = couponInp ? couponInp.value.trim() : '';
      heroFullOrderLink.href = `/submit-assignment.php?currency=${c}&words=${w}&deadline=${d}&coupon=${encodeURIComponent(cp)}`;
    }
  }

  [countrySel, levelSel, wordSel, urgencySel, couponInp].forEach(el => {
    if (el) {
      el.addEventListener('change', updateFullOrderLink);
      el.addEventListener('input', updateFullOrderLink);
    }
  });
  updateFullOrderLink();

  // Open Quick Order Modal
  if (btnHeroProceed) {
    btnHeroProceed.addEventListener('click', () => {
      document.getElementById('modalSummaryPrice').textContent = finalPrice.textContent;
      const words = wordSel ? wordSel.value : '2000';
      const days = (parseFloat(urgencySel ? urgencySel.value : 120) / 24).toFixed(0);
      document.getElementById('modalSummaryDetails').textContent = `${words} Words \u2022 ${days} Days (${levelSel ? levelSel.value : ''})`;

      const badge = document.getElementById('modalFileBadge');
      if (heroFileInput && heroFileInput.files.length > 0) {
        badge.innerHTML = `<span class="badge badge-success"><i class="fa-solid fa-paperclip"></i> ${heroFileInput.files.length} file(s) attached</span>`;
      } else {
        badge.innerHTML = `<span class="badge badge-info"><i class="fa-solid fa-paperclip"></i> No files</span>`;
      }

      openModal('heroQuickOrderModal');
    });
  }

  // Quick Order Modal submission
  if (quickForm) {
    quickForm.addEventListener('submit', (e) => {
      e.preventDefault();
      quickMsg.innerHTML = '<div class="badge badge-info" style="display:block; padding:0.6rem;"><i class="fa-solid fa-spinner fa-spin"></i> Submitting order and uploading files...</div>';

      const fd = new FormData();
      fd.append('name', document.getElementById('heroQuickName').value);
      fd.append('email', document.getElementById('heroQuickEmail').value);
      fd.append('phone', document.getElementById('heroQuickPhone').value);
      fd.append('title', document.getElementById('heroQuickTitle').value);
      fd.append('instructions', document.getElementById('heroQuickInstructions').value || 'Submitted via Homepage Quick Order.');
      fd.append('currency', countrySel ? countrySel.value : 'USD');
      fd.append('country', countrySel ? countrySel.options[countrySel.selectedIndex].text : 'United States');
      fd.append('academic_level', levelSel ? levelSel.value : 'postgraduate');
      fd.append('word_count', wordSel ? wordSel.value : '2000');
      fd.append('deadline_hours', urgencySel ? urgencySel.value : '120');
      fd.append('discount_code', couponInp ? couponInp.value.trim() : '');
      fd.append('subject', 'General');
      fd.append('assignment_type', 'Essay');

      // Append all selected files
      if (heroFileInput && heroFileInput.files.length > 0) {
        for (let i = 0; i < heroFileInput.files.length; i++) {
          fd.append('assignment_files[]', heroFileInput.files[i]);
        }
      }

      fetch('/api.php?action=submit_assignment', {
        method: 'POST',
        body: fd
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          quickMsg.innerHTML = `<div class="badge badge-success" style="display:block; padding:0.8rem;"><i class="fa-solid fa-circle-check"></i> ${data.message} Opening secure payment checkout...</div>`;
          setTimeout(() => {
            window.location.href = `/checkout.php?assignment_id=${encodeURIComponent(data.assignment_id)}`;
          }, 900);
        } else {
          quickMsg.innerHTML = `<div class="badge badge-danger" style="display:block; padding:0.6rem;">${data.message || 'Submission error'}</div>`;
        }
      })
      .catch(err => {
        quickMsg.innerHTML = `<div class="badge badge-danger" style="display:block; padding:0.6rem;">An error occurred during submission.</div>`;
      });
    });
  }
});
</script>

<?php include $baseDir . '/includes/footer.php'; ?>
