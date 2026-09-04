<?php
$pageTitle = "Academic Blog & Study Guides";
include __DIR__ . '/includes/header.php';
$blogs = DataStore::getCollection('blogs');
?>

<div class="container" style="padding-top:3rem;">
  <div class="section-header">
    <div class="badge badge-primary" style="margin-bottom:0.8rem;">Insights & Tips</div>
    <h2>Academic Writing & Research Blog</h2>
    <p>Guides written by our top PhD experts to elevate your writing and university research skills.</p>
  </div>

  <div class="grid-3" style="margin-top:3rem;">
    <?php foreach ($blogs as $blog): ?>
      <div class="feature-card">
        <div class="badge badge-info" style="margin-bottom:0.8rem;"><?php echo htmlspecialchars($blog['category']); ?></div>
        <h4 style="font-size:1.15rem; margin-bottom:0.6rem;"><?php echo htmlspecialchars($blog['title']); ?></h4>
        <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1.2rem;"><?php echo htmlspecialchars($blog['excerpt']); ?></p>
        <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.8rem; color:var(--text-dim); border-top:1px solid var(--border-color); padding-top:0.8rem;">
          <span>By <?php echo htmlspecialchars($blog['author']); ?></span>
          <span><?php echo htmlspecialchars($blog['published_at']); ?></span>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
