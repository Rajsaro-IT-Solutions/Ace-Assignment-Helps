<?php
$pageTitle = "Academic Blog & University Study Guides";
include __DIR__ . '/includes/header.php';
$blogs = DataStore::getCollection('blogs');

// Extract unique categories
$categories = [];
foreach ($blogs as $b) {
    if (!empty($b['category']) && !in_array($b['category'], $categories)) {
        $categories[] = $b['category'];
    }
}
?>

<div class="container" style="padding-top:3rem; padding-bottom:5rem;">
  <div class="section-header">
    <div class="badge badge-primary" style="margin-bottom:0.8rem;">
      <i class="fa-solid fa-newspaper"></i> Insights, Tips & Academic Guides
    </div>
    <h2>Academic Writing & Research Study Hub</h2>
    <p>Written by our 400+ accredited PhD professors to help you master university assignments, referencing styles, and thesis research.</p>
  </div>

  <!-- Search & Category Filters -->
  <div style="max-width:800px; margin:2rem auto; display:flex; flex-direction:column; gap:1rem; align-items:center;">
    <input type="text" id="blogSearchInput" class="form-control" placeholder="Search guides by title, category, or keyword..." style="padding:0.9rem 1.2rem; font-size:1rem; border-radius:var(--radius-md); box-shadow:var(--shadow-sm); width:100%;">
    
    <?php if (!empty($categories)): ?>
      <div style="display:flex; flex-wrap:wrap; gap:8px; justify-content:center;">
        <button class="btn btn-primary btn-sm blog-tab active" data-cat="all" onclick="filterBlogCategory('all', this)">All Topics</button>
        <?php foreach ($categories as $cat): ?>
          <button class="btn btn-outline btn-sm blog-tab" data-cat="<?php echo htmlspecialchars($cat); ?>" onclick="filterBlogCategory('<?php echo htmlspecialchars(addslashes($cat)); ?>', this)">
            <?php echo htmlspecialchars($cat); ?>
          </button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Blog Cards Grid -->
  <div class="grid-3" id="blogsGrid" style="margin-top:2.5rem;">
    <?php if (empty($blogs)): ?>
      <div style="grid-column: 1 / -1; text-align:center; padding:3rem; background:#fff; border-radius:var(--radius-md); border:1px solid var(--border-color);">
        <i class="fa-solid fa-newspaper" style="font-size:2.5rem; color:var(--text-muted); margin-bottom:1rem;"></i>
        <h3>Articles Coming Soon</h3>
        <p style="color:var(--text-muted);">Our academic editorial team is preparing comprehensive study guides for you.</p>
      </div>
    <?php endif; ?>

    <?php foreach ($blogs as $blog): ?>
      <div class="feature-card blog-item-card" data-category="<?php echo htmlspecialchars($blog['category'] ?? ''); ?>" style="display:flex; flex-direction:column; justify-content:space-between; height:100%;">
        <div>
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.8rem;">
            <span class="badge badge-info"><?php echo htmlspecialchars($blog['category']); ?></span>
            <small style="color:var(--text-muted); font-size:0.75rem;"><i class="fa-solid fa-clock"></i> 5 min read</small>
          </div>

          <h3 style="font-size:1.2rem; margin-bottom:0.8rem; line-height:1.4;">
            <a href="/blog-detail.php?id=<?php echo $blog['id']; ?>" style="color:var(--text-main); text-decoration:none; transition:color 0.2s;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-main)'">
              <?php echo htmlspecialchars($blog['title']); ?>
            </a>
          </h3>

          <p style="color:var(--text-muted); font-size:0.92rem; line-height:1.6; margin-bottom:1.5rem;">
            <?php echo htmlspecialchars($blog['excerpt']); ?>
          </p>
        </div>

        <div>
          <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.82rem; color:var(--text-muted); border-top:1px solid var(--border-color); padding-top:0.8rem; margin-bottom:1rem;">
            <span><i class="fa-solid fa-user-pen"></i> <?php echo htmlspecialchars($blog['author']); ?></span>
            <span><i class="fa-solid fa-calendar"></i> <?php echo htmlspecialchars($blog['published_at']); ?></span>
          </div>

          <a href="/blog-detail.php?id=<?php echo $blog['id']; ?>" class="btn btn-primary btn-sm" style="width:100%; text-align:center; display:block;">
            Read Full Guide &rarr;
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
let activeBlogCategory = 'all';

function filterBlogCategory(cat, tabElement) {
  activeBlogCategory = cat;

  document.querySelectorAll('.blog-tab').forEach(t => {
    t.classList.remove('btn-primary', 'active');
    t.classList.add('btn-outline');
  });
  if (tabElement) {
    tabElement.classList.remove('btn-outline');
    tabElement.classList.add('btn-primary', 'active');
  }

  applyBlogSearchAndFilter();
}

function applyBlogSearchAndFilter() {
  const query = (document.getElementById('blogSearchInput').value || '').toLowerCase().trim();
  const cards = document.querySelectorAll('.blog-item-card');

  cards.forEach(c => {
    const text = c.innerText.toLowerCase();
    const cardCat = (c.dataset.category || '').toLowerCase();

    const matchesCategory = (activeBlogCategory === 'all') || (cardCat === activeBlogCategory.toLowerCase());
    const matchesQuery = !query || text.includes(query);

    if (matchesCategory && matchesQuery) {
      c.style.display = 'flex';
    } else {
      c.style.display = 'none';
    }
  });
}

document.getElementById('blogSearchInput').addEventListener('input', applyBlogSearchAndFilter);
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
