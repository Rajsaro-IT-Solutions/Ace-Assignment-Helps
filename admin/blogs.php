<?php
$pageTitle = "Blog Posts & Articles Manager";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Admin');
$adminUser = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$msg = '';
$msgType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Create Article
    if ($action === 'create_blog') {
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? 'Academic Writing');
        $excerpt = trim($_POST['excerpt'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $author = trim($_POST['author'] ?? 'Admin Team');

        $img = 'blog-sample.jpg';
        if (isset($_FILES['blog_image']) && $_FILES['blog_image']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['blog_image']['name'], PATHINFO_EXTENSION));
            $safeName = time() . '_' . rand(100, 999) . '_blog.' . ($ext ?: 'jpg');
            $uploadDir = __DIR__ . '/../assets/uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            if (move_uploaded_file($_FILES['blog_image']['tmp_name'], $uploadDir . $safeName)) {
                $img = 'assets/uploads/' . $safeName;
            }
        }

        if (!empty($title)) {
            try {
                DataStore::insert('blogs', [
                    'title' => $title,
                    'excerpt' => $excerpt,
                    'content' => $content,
                    'category' => $category,
                    'author' => $author ?: 'Ace Assignment Team',
                    'published_at' => date('Y-m-d'),
                    'image' => $img
                ]);
                add_audit_log('Admin', $adminUser['id'], 'Create Blog', "Published article '$title'");
                $msg = "New article '$title' published successfully!";
                $msgType = 'success';
            } catch (Throwable $e) {
                $msg = "Failed to publish article: " . $e->getMessage();
                $msgType = 'danger';
            }
        }
    }

    // 2. Update Article
    if ($action === 'update_blog') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? 'Academic Writing');
        $excerpt = trim($_POST['excerpt'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $author = trim($_POST['author'] ?? 'Admin Team');

        if ($id && $title) {
            $updates = [
                'title' => $title,
                'excerpt' => $excerpt,
                'content' => $content,
                'category' => $category,
                'author' => $author
            ];

            if (isset($_FILES['blog_image']) && $_FILES['blog_image']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['blog_image']['name'], PATHINFO_EXTENSION));
                $safeName = time() . '_' . rand(100, 999) . '_blog.' . ($ext ?: 'jpg');
                $uploadDir = __DIR__ . '/../assets/uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                if (move_uploaded_file($_FILES['blog_image']['tmp_name'], $uploadDir . $safeName)) {
                    $updates['image'] = 'assets/uploads/' . $safeName;
                }
            }

            DataStore::update('blogs', 'id', $id, $updates);
            add_audit_log('Admin', $adminUser['id'], 'Update Blog', "Updated article #$id '$title'");
            $msg = "Article #$id updated successfully!";
            $msgType = 'success';
        }
    }

    // 3. Delete Article
    if ($action === 'delete_blog') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            try {
                DataStore::delete('blogs', 'id', $id);
                add_audit_log('Admin', $adminUser['id'], 'Delete Blog', "Deleted article #$id");
                $msg = "Article #$id deleted successfully.";
                $msgType = 'success';
            } catch (Throwable $e) {
                $msg = "Failed to delete article: " . $e->getMessage();
                $msgType = 'danger';
            }
        }
    }
}

$blogs = DataStore::getCollection('blogs');
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h2 style="font-size:1.5rem; color:var(--text-main); margin-bottom:0.25rem;">
      <i class="fa-solid fa-newspaper" style="color:var(--primary);"></i> Blog Content & Academic Articles Manager
    </h2>
    <p style="color:var(--text-muted); font-size:0.9rem; margin:0;">Publish educational guides, university study tips, and academic articles.</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('addBlogModal')">
    <i class="fa-solid fa-plus"></i> New Article
  </button>
</div>

<?php if ($msg): ?>
  <div class="badge badge-<?php echo $msgType; ?>" style="width:100%; padding:0.9rem; margin-bottom:1.5rem; text-align:center; font-size:0.95rem; display:block;">
    <i class="fa-solid fa-circle-info"></i> <?php echo htmlspecialchars($msg); ?>
  </div>
<?php endif; ?>

<!-- Quick Search and Category Filter -->
<div class="filter-bar">
  <input type="text" id="tableSearchInput" class="form-control" placeholder="Search articles by title, author, or excerpt...">
  <select id="tableCategoryFilter" class="form-control" onchange="filterBlogCategory(this.value)">
    <option value="">All Categories</option>
    <option value="Academic Writing">Academic Writing</option>
    <option value="Computer Science">Computer Science</option>
    <option value="Study Tips">Study Tips</option>
    <option value="Research & Citations">Research & Citations</option>
    <option value="Nursing & Healthcare">Nursing & Healthcare</option>
  </select>
</div>

<div class="table-card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Article Details</th>
          <th>Category</th>
          <th>Author</th>
          <th>Published Date</th>
          <th style="text-align:center; min-width:200px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($blogs)): ?>
          <tr>
            <td colspan="6" style="text-align:center; padding:2rem; color:var(--text-muted);">No blog articles found. Click "New Article" to write one.</td>
          </tr>
        <?php endif; ?>
        <?php foreach ($blogs as $b): ?>
          <tr data-category="<?php echo htmlspecialchars($b['category'] ?? ''); ?>">
            <td><strong>#<?php echo $b['id']; ?></strong></td>
            <td>
              <strong style="color:var(--text-main); display:block; font-size:0.98rem;"><?php echo htmlspecialchars($b['title']); ?></strong>
              <small style="color:var(--text-muted);"><?php echo htmlspecialchars(substr($b['excerpt'] ?? '', 0, 95)); ?>...</small>
            </td>
            <td><span class="badge badge-info"><?php echo htmlspecialchars($b['category']); ?></span></td>
            <td><?php echo htmlspecialchars($b['author']); ?></td>
            <td><?php echo htmlspecialchars($b['published_at']); ?></td>
            <td style="text-align:center;">
              <div style="display:inline-flex; gap:6px; align-items:center; justify-content:center;">
                <!-- View Live Article on Main Site -->
                <a href="/blog-detail.php?id=<?php echo $b['id']; ?>" target="_blank" class="btn btn-outline btn-sm" title="View Article on Main Website">
                  <i class="fa-solid fa-arrow-up-right-from-square"></i> Live
                </a>

                <!-- Edit Button -->
                <button type="button" class="btn btn-outline btn-sm" onclick='editBlog(<?php echo json_encode($b); ?>)' title="Edit Article">
                  <i class="fa-solid fa-pen-to-square"></i> Edit
                </button>

                <!-- Delete Button -->
                <form method="POST" style="display:inline;" onsubmit="return confirm('Permanently DELETE article <?php echo addslashes($b['title']); ?>?');">
                  <input type="hidden" name="action" value="delete_blog">
                  <input type="hidden" name="id" value="<?php echo $b['id']; ?>">
                  <button type="submit" class="btn btn-danger btn-sm" title="Delete Article">
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Modal -->
<div id="addBlogModal" class="modal-overlay">
  <div class="modal-box" style="padding:2rem; max-width:680px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem;">
      <h3 style="margin:0; color:var(--text-main);"><i class="fa-solid fa-newspaper" style="color:var(--primary);"></i> Publish New Blog Article</h3>
      <button type="button" onclick="closeModal('addBlogModal')" style="background:none; border:none; font-size:1.3rem; color:var(--text-muted); cursor:pointer;">&times;</button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="create_blog">
      <div class="form-group">
        <label>Article Title *</label>
        <input type="text" name="title" class="form-control" required placeholder="e.g. 10 Essential Tips for University Dissertation Success">
      </div>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div class="form-group">
          <label>Category</label>
          <select name="category" class="form-control">
            <option value="Academic Writing">Academic Writing</option>
            <option value="Computer Science">Computer Science</option>
            <option value="Study Tips">Study Tips</option>
            <option value="Research & Citations">Research & Citations</option>
            <option value="Nursing & Healthcare">Nursing & Healthcare</option>
          </select>
        </div>
        <div class="form-group">
          <label>Author</label>
          <input type="text" name="author" class="form-control" value="Ace Editorial Team">
        </div>
      </div>
      <div class="form-group">
        <label>Excerpt Summary * (Short preview shown on blog cards)</label>
        <textarea name="excerpt" class="form-control" rows="2" required placeholder="Brief 1-2 sentence preview summary..."></textarea>
      </div>
      <div class="form-group">
        <label>Full Article Content (Markdown or HTML supported)</label>
        <textarea name="content" class="form-control" rows="8" placeholder="Write full article body here with paragraphs, headings, and tips..."></textarea>
      </div>
      <div class="form-group">
        <label><i class="fa-solid fa-cloud-arrow-up"></i> Featured Image / Attachment (Any Format)</label>
        <input type="file" name="blog_image" class="form-control">
        <small style="color:var(--text-muted); font-size:0.8rem; display:block; margin-top:4px;">
          Accepts <strong>ANY</strong> format: JPG, PNG, WEBP, PDF, DOCX, etc.
        </small>
      </div>
      <div style="display:flex; gap:10px; margin-top:1.5rem;">
        <button type="submit" class="btn btn-primary" style="flex:1;">Publish Article</button>
        <button type="button" class="btn btn-outline" onclick="closeModal('addBlogModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div id="editBlogModal" class="modal-overlay">
  <div class="modal-box" style="padding:2rem; max-width:680px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem;">
      <h3 style="margin:0; color:var(--text-main);"><i class="fa-solid fa-pen-to-square" style="color:var(--primary);"></i> Edit Blog Article</h3>
      <button type="button" onclick="closeModal('editBlogModal')" style="background:none; border:none; font-size:1.3rem; color:var(--text-muted); cursor:pointer;">&times;</button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="update_blog">
      <input type="hidden" name="id" id="edit_blog_id">
      <div class="form-group">
        <label>Article Title *</label>
        <input type="text" name="title" id="edit_blog_title" class="form-control" required>
      </div>
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div class="form-group">
          <label>Category</label>
          <select name="category" id="edit_blog_category" class="form-control">
            <option value="Academic Writing">Academic Writing</option>
            <option value="Computer Science">Computer Science</option>
            <option value="Study Tips">Study Tips</option>
            <option value="Research & Citations">Research & Citations</option>
            <option value="Nursing & Healthcare">Nursing & Healthcare</option>
          </select>
        </div>
        <div class="form-group">
          <label>Author</label>
          <input type="text" name="author" id="edit_blog_author" class="form-control">
        </div>
      </div>
      <div class="form-group">
        <label>Excerpt Summary *</label>
        <textarea name="excerpt" id="edit_blog_excerpt" class="form-control" rows="2" required></textarea>
      </div>
      <div class="form-group">
        <label>Full Article Content (Markdown or HTML supported)</label>
        <textarea name="content" id="edit_blog_content" class="form-control" rows="8"></textarea>
      </div>
      <div class="form-group">
        <label><i class="fa-solid fa-cloud-arrow-up"></i> Change Featured Image / Attachment (Any Format)</label>
        <input type="file" name="blog_image" class="form-control">
      </div>
      <div style="display:flex; gap:10px; margin-top:1.5rem;">
        <button type="submit" class="btn btn-primary" style="flex:1;">Save Changes</button>
        <button type="button" class="btn btn-outline" onclick="closeModal('editBlogModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function editBlog(data) {
  document.getElementById('edit_blog_id').value = data.id || '';
  document.getElementById('edit_blog_title').value = data.title || '';
  document.getElementById('edit_blog_category').value = data.category || 'Academic Writing';
  document.getElementById('edit_blog_author').value = data.author || '';
  document.getElementById('edit_blog_excerpt').value = data.excerpt || '';
  document.getElementById('edit_blog_content').value = data.content || '';
  openModal('editBlogModal');
}

function filterBlogCategory(cat) {
  const rows = document.querySelectorAll('.data-table tbody tr');
  rows.forEach(r => {
    const rowCat = r.dataset.category || '';
    if (!cat || rowCat.toLowerCase() === cat.toLowerCase()) {
      r.style.display = '';
    } else {
      r.style.display = 'none';
    }
  });
}
</script>

<script src="/assets/js/portal.js"></script>
</div>
</div>
</body>
</html>
