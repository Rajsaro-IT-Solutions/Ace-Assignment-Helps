<?php
$pageTitle = "Supported University Courses & Subjects";
include __DIR__ . '/includes/header.php';

$allCourses = DataStore::getCollection('courses');
$activeCourses = array_filter($allCourses, function($c) {
    return ($c['status'] ?? 'Active') === 'Active';
});

// Extract unique categories
$categories = [];
foreach ($activeCourses as $c) {
    if (!empty($c['category']) && !in_array($c['category'], $categories)) {
        $categories[] = $c['category'];
    }
}
?>

<div class="container" style="padding-top:3rem; padding-bottom:5rem;">
  <div class="section-header">
    <div class="badge badge-info" style="margin-bottom:0.8rem;">
      <i class="fa-solid fa-graduation-cap"></i> <?php echo count($activeCourses); ?>+ Academic Faculties & Disciplines
    </div>
    <h2>Supported University Courses & Subjects</h2>
    <p>Our network of 400+ accredited PhD scholars covers every academic faculty, degree module, and specialized syllabus globally.</p>
  </div>

  <!-- Search & Category Filter Tabs -->
  <div style="max-width:850px; margin:2rem auto; display:flex; flex-direction:column; gap:1rem; align-items:center;">
    <div style="width:100%; position:relative;">
      <input type="text" id="courseSearchInput" class="form-control" placeholder="Search by degree name, subject, or module (e.g. Python, AI, Nursing, MBA, Law)..." style="padding:0.9rem 1.2rem; font-size:1rem; border-radius:var(--radius-md); box-shadow:var(--shadow-sm);">
    </div>
    
    <?php if (!empty($categories)): ?>
      <div style="display:flex; flex-wrap:wrap; gap:8px; justify-content:center;">
        <button class="btn btn-primary btn-sm category-tab active" data-cat="all" onclick="filterCourses('all', this)">All Disciplines</button>
        <?php foreach ($categories as $cat): ?>
          <button class="btn btn-outline btn-sm category-tab" data-cat="<?php echo htmlspecialchars($cat); ?>" onclick="filterCourses('<?php echo htmlspecialchars(addslashes($cat)); ?>', this)">
            <?php echo htmlspecialchars($cat); ?>
          </button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Dynamic Courses Grid -->
  <div class="grid-3" id="coursesGrid" style="margin-top:2.5rem;">
    <?php if (empty($activeCourses)): ?>
      <div style="grid-column: 1 / -1; text-align:center; padding:3rem; background:#fff; border-radius:var(--radius-md); border:1px solid var(--border-color);">
        <i class="fa-solid fa-book-open" style="font-size:2.5rem; color:var(--text-muted); margin-bottom:1rem;"></i>
        <h3>Courses Being Updated</h3>
        <p style="color:var(--text-muted);">Our academic catalogue is being updated. You can still submit any custom assignment requirement.</p>
        <a href="/submit-assignment.php" class="btn btn-primary" style="margin-top:1rem;">Submit Custom Order &rarr;</a>
      </div>
    <?php endif; ?>

    <?php foreach ($activeCourses as $course): 
      $topics = is_array($course['topics']) ? $course['topics'] : array_filter(explode(',', (string)$course['topics']));
      $icon = !empty($course['icon']) ? $course['icon'] : 'fa-book-open';
    ?>
      <div class="feature-card course-item-card" data-category="<?php echo htmlspecialchars($course['category'] ?? ''); ?>" style="display:flex; flex-direction:column; justify-content:space-between; height:100%;">
        <div>
          <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1rem;">
            <div style="width:48px; height:48px; border-radius:12px; background:rgba(99,102,241,0.1); display:flex; align-items:center; justify-content:center; color:var(--primary); font-size:1.4rem;">
              <i class="fa-solid <?php echo htmlspecialchars($icon); ?>"></i>
            </div>
            <span class="badge badge-info" style="font-size:0.75rem;"><?php echo htmlspecialchars($course['category']); ?></span>
          </div>

          <h3 style="font-size:1.25rem; margin-bottom:0.6rem; color:var(--text-main);">
            <?php echo htmlspecialchars($course['title']); ?>
          </h3>

          <p style="color:var(--text-muted); font-size:0.92rem; line-height:1.6; margin-bottom:1.2rem;">
            <?php echo htmlspecialchars($course['description']); ?>
          </p>

          <?php if (!empty($topics)): ?>
            <div style="border-top:1px solid var(--border-color); padding-top:1rem; margin-bottom:1.5rem;">
              <div style="font-size:0.8rem; font-weight:700; color:var(--text-main); margin-bottom:0.6rem; text-transform:uppercase; letter-spacing:0.5px;">
                Specialized Modules Covered:
              </div>
              <ul style="list-style:none; padding:0; margin:0; color:var(--text-muted); font-size:0.88rem; line-height:1.8;">
                <?php foreach ($topics as $t): ?>
                  <li style="display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-circle-check" style="color:var(--success); font-size:0.75rem;"></i>
                    <span><?php echo htmlspecialchars(trim($t)); ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>
        </div>

        <div style="border-top:1px solid var(--border-color); padding-top:1rem; margin-top:auto;">
          <a href="/submit-assignment.php?subject=<?php echo urlencode($course['title']); ?>" class="btn btn-outline" style="width:100%; text-align:center; font-weight:700; display:block;">
            Order <?php echo htmlspecialchars($course['title']); ?> &rarr;
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Bottom CTA -->
  <div style="margin-top:4rem; text-align:center; background:var(--card-bg, #f8fafc); border:1px solid var(--border-color); border-radius:var(--radius-lg); padding:3rem 2rem;">
    <h3 style="font-size:1.8rem; margin-bottom:0.8rem; color:var(--text-main);">Don't See Your Specific Syllabus or Module?</h3>
    <p style="color:var(--text-muted); max-width:650px; margin:0 auto 1.5rem auto;">
      We have over 400 PhD professors covering specialized niche subjects, multidisciplinary degrees, and inter-faculty research. Send us your syllabus prompt!
    </p>
    <a href="/submit-assignment.php" class="btn btn-primary btn-lg"><i class="fa-solid fa-paper-plane"></i> Get Free Assignment Quote &rarr;</a>
  </div>
</div>

<script>
let currentSelectedCat = 'all';

function filterCourses(category, btnElement) {
  currentSelectedCat = category;
  
  // Update button active classes
  document.querySelectorAll('.category-tab').forEach(b => {
    b.classList.remove('btn-primary', 'active');
    b.classList.add('btn-outline');
  });
  if (btnElement) {
    btnElement.classList.remove('btn-outline');
    btnElement.classList.add('btn-primary', 'active');
  }

  applyCombinedFilters();
}

function applyCombinedFilters() {
  const search = (document.getElementById('courseSearchInput').value || '').toLowerCase().trim();
  const cards = document.querySelectorAll('.course-item-card');

  cards.forEach(card => {
    const text = card.innerText.toLowerCase();
    const cardCat = (card.dataset.category || '').toLowerCase();
    
    const matchesCat = (currentSelectedCat === 'all') || (cardCat === currentSelectedCat.toLowerCase());
    const matchesSearch = !search || text.includes(search);

    if (matchesCat && matchesSearch) {
      card.style.display = 'flex';
    } else {
      card.style.display = 'none';
    }
  });
}

document.getElementById('courseSearchInput').addEventListener('input', applyCombinedFilters);
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
