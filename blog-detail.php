<?php
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db.php';

$id = (int)($_GET['id'] ?? 0);
$blog = null;

if ($id > 0) {
    $blog = DataStore::findOne('blogs', 'id', $id);
}

if (!$blog) {
    // Fallback to first blog if available
    $blogs = DataStore::getCollection('blogs');
    $blog = !empty($blogs) ? $blogs[0] : null;
}

if (!$blog) {
    header("Location: /blog.php");
    exit;
}

$pageTitle = htmlspecialchars($blog['title']) . " - Academic Study Guide";
include __DIR__ . '/includes/header.php';

// Other recent blogs for related section
$allBlogs = DataStore::getCollection('blogs');
$relatedBlogs = array_slice(array_filter($allBlogs, function($b) use ($blog) {
    return $b['id'] != $blog['id'];
}), 0, 3);
?>

<div class="container" style="padding-top:3rem; padding-bottom:5rem; max-width:960px;">
  <!-- Breadcrumb -->
  <div style="font-size:0.88rem; color:var(--text-muted); margin-bottom:1.5rem;">
    <a href="/" style="color:var(--text-muted); text-decoration:none;">Home</a> &rsaquo; 
    <a href="/blog.php" style="color:var(--text-muted); text-decoration:none;">Blog</a> &rsaquo; 
    <span style="color:var(--primary); font-weight:600;"><?php echo htmlspecialchars($blog['category']); ?></span>
  </div>

  <!-- Article Header -->
  <div style="margin-bottom:2.5rem;">
    <div style="display:flex; gap:10px; align-items:center; margin-bottom:1rem; flex-wrap:wrap;">
      <span class="badge badge-primary"><?php echo htmlspecialchars($blog['category']); ?></span>
      <span style="font-size:0.85rem; color:var(--text-muted);"><i class="fa-solid fa-calendar-days"></i> <?php echo htmlspecialchars($blog['published_at'] ?? date('Y-m-d')); ?></span>
      <span style="font-size:0.85rem; color:var(--text-muted);"><i class="fa-solid fa-user-pen"></i> By <?php echo htmlspecialchars($blog['author'] ?? 'Ace Editorial Team'); ?></span>
      <span style="font-size:0.85rem; color:var(--text-muted);"><i class="fa-solid fa-clock"></i> 5 Min Read</span>
    </div>

    <h1 style="font-size:2.4rem; line-height:1.25; color:var(--text-main); margin-bottom:1.2rem;">
      <?php echo htmlspecialchars($blog['title']); ?>
    </h1>

    <div style="background:rgba(99,102,241,0.06); border-left:4px solid var(--primary); padding:1.2rem 1.5rem; border-radius:0 var(--radius-sm) var(--radius-sm) 0; font-size:1.1rem; color:var(--text-main); font-style:italic; line-height:1.6;">
      "<?php echo htmlspecialchars($blog['excerpt'] ?? ''); ?>"
    </div>
  </div>

  <!-- Featured Image If Not Default -->
  <?php if (!empty($blog['image']) && $blog['image'] !== 'blog-sample.jpg' && file_exists(__DIR__ . '/' . ltrim($blog['image'], '/'))): ?>
    <div style="margin-bottom:2.5rem; border-radius:var(--radius-md); overflow:hidden; box-shadow:var(--shadow-md);">
      <img src="/<?php echo ltrim(htmlspecialchars($blog['image']), '/'); ?>" alt="<?php echo htmlspecialchars($blog['title']); ?>" style="width:100%; height:auto; display:block; max-height:450px; object-fit:cover;">
    </div>
  <?php endif; ?>

  <!-- Article Body Content -->
  <div style="background:#ffffff; padding:2.5rem; border-radius:var(--radius-md); border:1px solid var(--border-color); box-shadow:var(--shadow-sm); line-height:1.85; font-size:1.05rem; color:#334155;">
    <?php 
    $content = $blog['content'] ?? '';
    if (empty($content)) {
        $content = "Academic research and university assignments demand structured critical thinking, rigorous literature synthesis, and strict adherence to formal writing conventions.\n\n### 1. Structuring Your Arguments\nEvery successful assignment starts with a clear thesis statement. Break your work into logical paragraphs where each introduces a singular claim supported by scholarly empirical evidence.\n\n### 2. Citation & Integrity Standards\nEnsure all quotations and paraphrased concepts are cited using university guidelines (APA 7th, Harvard, OSCOLA, IEEE, or Chicago). Always verify similarity metrics with Turnitin before submission.\n\n### 3. Professional Editing & Review\nReview sentence syntax, eliminate passive voice redundancies, and verify that the assignment rubric deliverables are comprehensively addressed.\n\nNeed professional assistance with your " . htmlspecialchars($blog['category']) . " coursework? Our 400+ PhD experts are ready to assist 24/7!";
    }

    // Render paragraphs with simple markdown conversion (headers and paragraphs)
    $lines = explode("\n", $content);
    $inList = false;
    foreach ($lines as $line) {
        $trim = trim($line);
        if (empty($trim)) {
            if ($inList) { echo "</ul>"; $inList = false; }
            continue;
        }

        if (str_starts_with($trim, '### ')) {
            if ($inList) { echo "</ul>"; $inList = false; }
            echo '<h3 style="font-size:1.4rem; color:var(--text-main); margin:1.8rem 0 0.8rem 0; font-family:var(--font-head);">' . htmlspecialchars(substr($trim, 4)) . '</h3>';
        } elseif (str_starts_with($trim, '## ')) {
            if ($inList) { echo "</ul>"; $inList = false; }
            echo '<h2 style="font-size:1.7rem; color:var(--text-main); margin:2rem 0 1rem 0; font-family:var(--font-head);">' . htmlspecialchars(substr($trim, 3)) . '</h2>';
        } elseif (str_starts_with($trim, '* ') || str_starts_with($trim, '- ')) {
            if (!$inList) { echo '<ul style="padding-left:1.5rem; margin:1rem 0;">'; $inList = true; }
            echo '<li style="margin-bottom:0.4rem;">' . htmlspecialchars(substr($trim, 2)) . '</li>';
        } else {
            if ($inList) { echo "</ul>"; $inList = false; }
            echo '<p style="margin-bottom:1.2rem;">' . htmlspecialchars($trim) . '</p>';
        }
    }
    if ($inList) { echo "</ul>"; }
    ?>

    <!-- In-Article CTA Banner -->
    <div style="margin-top:3rem; background:linear-gradient(135deg, #4f46e5, #06b6d4); color:#fff; border-radius:var(--radius-md); padding:2rem; text-align:center;">
      <h3 style="color:#ffffff; font-size:1.5rem; margin-bottom:0.6rem;">Need University Assignment Assistance in <?php echo htmlspecialchars($blog['category']); ?>?</h3>
      <p style="color:rgba(255,255,255,0.9); font-size:0.95rem; margin-bottom:1.5rem; max-width:600px; margin-left:auto; margin-right:auto;">
        Get 1-on-1 writing support from accredited PhD specialists. 100% confidential, 0% Turnitin plagiarism report included, and guaranteed on-time delivery!
      </p>
      <div style="display:flex; justify-content:center; gap:12px; flex-wrap:wrap;">
        <a href="/submit-assignment.php?subject=<?php echo urlencode($blog['category']); ?>&coupon=ACE20" class="btn btn-secondary" style="background:#fff; color:var(--primary); font-weight:800;">
          <i class="fa-solid fa-paper-plane"></i> Order Assignment (20% Off)
        </a>
        <a href="/pricing.php" class="btn btn-outline" style="color:#fff; border-color:rgba(255,255,255,0.5);">
          <i class="fa-solid fa-calculator"></i> Calculate Pricing
        </a>
      </div>
    </div>
  </div>

  <!-- Related Study Guides -->
  <?php if (!empty($relatedBlogs)): ?>
    <div style="margin-top:4rem;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
        <h3 style="font-size:1.5rem; color:var(--text-main); margin:0;">More Academic Study Guides</h3>
        <a href="/blog.php" class="btn btn-outline btn-sm">View All Guides &rarr;</a>
      </div>

      <div class="grid-3">
        <?php foreach ($relatedBlogs as $rb): ?>
          <div class="feature-card" style="display:flex; flex-direction:column; justify-content:space-between;">
            <div>
              <div class="badge badge-info" style="margin-bottom:0.6rem;"><?php echo htmlspecialchars($rb['category']); ?></div>
              <h4 style="font-size:1.1rem; margin-bottom:0.6rem;">
                <a href="/blog-detail.php?id=<?php echo $rb['id']; ?>" style="color:var(--text-main); text-decoration:none;">
                  <?php echo htmlspecialchars($rb['title']); ?>
                </a>
              </h4>
              <p style="color:var(--text-muted); font-size:0.88rem; margin-bottom:1rem;">
                <?php echo htmlspecialchars(substr($rb['excerpt'] ?? '', 0, 90)); ?>...
              </p>
            </div>
            <div style="border-top:1px solid var(--border-color); padding-top:0.8rem;">
              <a href="/blog-detail.php?id=<?php echo $rb['id']; ?>" style="color:var(--primary); font-weight:600; font-size:0.88rem; text-decoration:none;">
                Read Guide &rarr;
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
