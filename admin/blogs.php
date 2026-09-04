<?php
$pageTitle = "Blog Posts & Articles Manager";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Admin');
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$blogs = DataStore::getCollection('blogs');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? 'Academic Writing');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $author = trim($_POST['author'] ?? 'Admin Team');

    if (!empty($title)) {
        DataStore::insert('blogs', [
            'id' => count($blogs) + 1,
            'title' => $title,
            'excerpt' => $excerpt,
            'category' => $category,
            'author' => $author,
            'published_at' => date('Y-m-d'),
            'image' => 'blog-sample.jpg'
        ]);
        header("Location: /admin/blogs.php");
        exit;
    }
}
?>

<div style="margin-bottom:1.5rem; display:flex; justify-content:space-between; align-items:center;">
  <h3 style="color:#fff;"><i class="fa-solid fa-newspaper" style="color:var(--primary);"></i> Blog Content Manager</h3>
  <button class="btn btn-primary btn-sm" onclick="openModal('addBlogModal')"><i class="fa-solid fa-plus"></i> New Article</button>
</div>

<div class="table-card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Article Title</th>
          <th>Category</th>
          <th>Author</th>
          <th>Published Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($blogs as $b): ?>
          <tr>
            <td><?php echo $b['id']; ?></td>
            <td><strong style="color:#fff;"><?php echo htmlspecialchars($b['title']); ?></strong></td>
            <td><span class="badge badge-info"><?php echo htmlspecialchars($b['category']); ?></span></td>
            <td><?php echo htmlspecialchars($b['author']); ?></td>
            <td><?php echo htmlspecialchars($b['published_at']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal -->
<div id="addBlogModal" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.85); backdrop-filter:blur(10px); z-index:2000; align-items:center; justify-content:center;">
  <div class="calc-card" style="max-width:500px; width:100%; padding:2rem;">
    <h3 style="margin-bottom:1rem; color:#fff;"><i class="fa-solid fa-newspaper"></i> Publish New Blog Article</h3>
    <form method="POST">
      <div class="form-group">
        <label>Article Title *</label>
        <input type="text" name="title" class="form-control" required placeholder="10 Tips for Academic Writing">
      </div>
      <div class="form-group">
        <label>Category</label>
        <select name="category" class="form-control">
          <option value="Academic Writing">Academic Writing</option>
          <option value="Computer Science">Computer Science</option>
          <option value="Study Tips">Study Tips</option>
          <option value="Research & Citations">Research & Citations</option>
        </select>
      </div>
      <div class="form-group">
        <label>Excerpt Summary *</label>
        <textarea name="excerpt" class="form-control" rows="3" required placeholder="Brief summary..."></textarea>
      </div>
      <div style="display:flex; gap:10px; margin-top:1.5rem;">
        <button type="submit" class="btn btn-primary" style="flex:1;">Publish Article</button>
        <button type="button" class="btn btn-outline" onclick="closeModal('addBlogModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script src="/assets/js/portal.js"></script>
</div>
</div>
</body>
</html>
