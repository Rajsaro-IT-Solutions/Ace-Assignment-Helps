<?php
$pageTitle = "Ace Assignment Helps - #1 University Assignment Assistance (UK, USA, Ireland, Australia, Canada & India)";
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

include __DIR__ . '/includes/header.php';
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
            <label for="word_count">Word Count (Pages)</label>
            <select id="word_count" class="form-control">
              <option value="500">500 Words (2 Pages)</option>
              <option value="1000">1,000 Words (4 Pages)</option>
              <option value="2000" selected>2,000 Words (8 Pages)</option>
              <option value="3000">3,000 Words (12 Pages)</option>
              <option value="5000">5,000 Words (20 Pages)</option>
            </select>
          </div>

          <div class="form-group">
            <label for="urgency">Deadline Urgency</label>
            <select id="urgency" class="form-control">
              <option value="24">Urgent (24 Hours)</option>
              <option value="48">Fast (2 Days)</option>
              <option value="120" selected>Standard (5 Days)</option>
              <option value="240">Relaxed (10 Days)</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label for="coupon_input">Discount Promo Code</label>
          <div style="display:flex; gap:10px;">
            <input type="text" id="coupon_input" class="form-control" placeholder="Try promo ACE20" value="ACE20">
            <button type="button" id="apply_coupon_btn" class="btn btn-outline btn-sm">Apply</button>
          </div>
          <small id="coupon_msg" style="color:var(--success); font-weight:600; display:block; margin-top:4px;">
            <i class="fa-solid fa-circle-check"></i> Coupon ACE20 Applied! 20% Discount Activated.
          </small>
        </div>

        <div class="price-display-box">
          <div class="est-label">Estimated Total Price</div>
          <div class="est-amount" id="final_calc_price">$144.00</div>
          <small style="color:var(--text-muted); display:block; margin-top:4px;">Includes Free Turnitin Plagiarism Report & Unlimited Revisions</small>
        </div>

        <a href="/submit-assignment.php" class="btn btn-primary btn-lg" style="width:100%;">
          Proceed to Order &rarr;
        </a>
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

<script src="/assets/js/main.js"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
