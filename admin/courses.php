<?php
$pageTitle = "Courses & Academic Subjects Manager";
require_once __DIR__ . '/../includes/auth.php';
Auth::checkRole('Admin');
$adminUser = Auth::currentUser();
require_once __DIR__ . '/../includes/helpers.php';
include __DIR__ . '/../includes/portal_header.php';

$msg = '';
$msgType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Create Course
    if ($action === 'create_course') {
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? 'Academic Discipline');
        $icon = trim($_POST['icon'] ?? 'fa-book-open');
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'Active';
        $topicsRaw = trim($_POST['topics'] ?? '');
        
        // Clean and parse topics
        $topics = array_values(array_filter(array_map('trim', preg_split('/[,\n\r]+/', $topicsRaw))));

        if (!empty($title)) {
            try {
                $newId = DataStore::generateNextId('courses', 'course_id', 'CRS-', 3, 101);
                DataStore::insert('courses', [
                    'course_id' => $newId,
                    'title' => $title,
                    'category' => $category,
                    'icon' => $icon ?: 'fa-book-open',
                    'description' => $description,
                    'topics' => $topics,
                    'status' => $status,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                add_audit_log('Admin', $adminUser['id'], 'Create Course', "Created course '$title' ($newId)");
                $msg = "New course '$title' ($newId) created successfully!";
                $msgType = 'success';
            } catch (Throwable $e) {
                $msg = "Failed to create course: " . $e->getMessage();
                $msgType = 'danger';
            }
        } else {
            $msg = "Course title is required.";
            $msgType = 'danger';
        }
    }

    // 2. Update Course
    if ($action === 'update_course') {
        $course_id = trim($_POST['course_id'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? 'Academic Discipline');
        $icon = trim($_POST['icon'] ?? 'fa-book-open');
        $description = trim($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'Active';
        $topicsRaw = trim($_POST['topics'] ?? '');
        
        $topics = array_values(array_filter(array_map('trim', preg_split('/[,\n\r]+/', $topicsRaw))));

        if ($course_id && $title) {
            DataStore::update('courses', 'course_id', $course_id, [
                'title' => $title,
                'category' => $category,
                'icon' => $icon ?: 'fa-book-open',
                'description' => $description,
                'topics' => $topics,
                'status' => $status
            ]);
            add_audit_log('Admin', $adminUser['id'], 'Update Course', "Updated course $course_id '$title'");
            $msg = "Course $course_id updated successfully!";
            $msgType = 'success';
        } else {
            $msg = "Required fields missing for course update.";
            $msgType = 'danger';
        }
    }

    // 3. Toggle Status (Active / Inactive)
    if ($action === 'toggle_status') {
        $course_id = trim($_POST['course_id'] ?? '');
        $newStatus = trim($_POST['status'] ?? 'Active');
        if ($course_id) {
            DataStore::update('courses', 'course_id', $course_id, ['status' => $newStatus]);
            add_audit_log('Admin', $adminUser['id'], 'Toggle Course Status', "Set course $course_id to $newStatus");
            $msg = "Course $course_id status updated to $newStatus.";
            $msgType = 'success';
        }
    }

    // 4. Delete Course
    if ($action === 'delete_course') {
        $course_id = trim($_POST['course_id'] ?? '');
        if ($course_id) {
            try {
                DataStore::delete('courses', 'course_id', $course_id);
                add_audit_log('Admin', $adminUser['id'], 'Delete Course', "Deleted course $course_id");
                $msg = "Course $course_id deleted successfully.";
                $msgType = 'success';
            } catch (Throwable $e) {
                $msg = "Failed to delete course: " . $e->getMessage();
                $msgType = 'danger';
            }
        }
    }
}

$courses = DataStore::getCollection('courses');
?>

<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
  <div>
    <h2 style="font-size:1.5rem; color:var(--text-main); margin-bottom:0.25rem;">
      <i class="fa-solid fa-graduation-cap" style="color:var(--primary);"></i> Courses & Academic Subjects Manager
    </h2>
    <p style="color:var(--text-muted); font-size:0.9rem; margin:0;">
      Add, edit, and manage dynamic courses and subjects displayed across the main site and order submission portals.
    </p>
  </div>
  <button class="btn btn-primary" onclick="openModal('addCourseModal')">
    <i class="fa-solid fa-plus"></i> Add New Course
  </button>
</div>

<?php if ($msg): ?>
  <div class="badge badge-<?php echo $msgType; ?>" style="width:100%; padding:0.9rem; margin-bottom:1.5rem; text-align:center; font-size:0.95rem; display:block;">
    <i class="fa-solid fa-circle-info"></i> <?php echo htmlspecialchars($msg); ?>
  </div>
<?php endif; ?>

<!-- Quick Search and Filter Bar -->
<div class="filter-bar">
  <input type="text" id="tableSearchInput" class="form-control" placeholder="Search courses by title, category, or subtopics...">
  <select id="tableStatusFilter" class="form-control">
    <option value="">All Statuses</option>
    <option value="Active">Active</option>
    <option value="Inactive">Inactive</option>
  </select>
</div>

<div class="table-card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Course ID</th>
          <th>Course & Category</th>
          <th>Icon</th>
          <th>Description & Subtopics</th>
          <th>Status</th>
          <th style="text-align:center; min-width:160px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($courses)): ?>
          <tr>
            <td colspan="6" style="text-align:center; padding:2rem; color:var(--text-muted);">
              No courses found. Click "Add New Course" to create one.
            </td>
          </tr>
        <?php endif; ?>
        <?php foreach ($courses as $c): 
          $topicsList = is_array($c['topics']) ? $c['topics'] : (array_filter(explode(',', (string)$c['topics'])));
          $isActive = ($c['status'] ?? 'Active') === 'Active';
        ?>
          <tr data-status="<?php echo htmlspecialchars($c['status'] ?? 'Active'); ?>">
            <td><strong style="color:var(--secondary); font-family:monospace;"><?php echo htmlspecialchars($c['course_id']); ?></strong></td>
            <td>
              <strong style="color:var(--text-main); font-size:1rem; display:block;"><?php echo htmlspecialchars($c['title']); ?></strong>
              <span class="badge badge-info" style="font-size:0.75rem;"><?php echo htmlspecialchars($c['category']); ?></span>
            </td>
            <td>
              <div style="width:36px; height:36px; border-radius:8px; background:rgba(99,102,241,0.1); display:flex; align-items:center; justify-content:center; color:var(--primary); font-size:1.1rem;">
                <i class="fa-solid <?php echo htmlspecialchars($c['icon'] ?: 'fa-book-open'); ?>"></i>
              </div>
            </td>
            <td>
              <p style="color:var(--text-muted); font-size:0.85rem; margin:0 0 6px 0; max-width:380px;">
                <?php echo htmlspecialchars($c['description'] ?? ''); ?>
              </p>
              <div style="display:flex; flex-wrap:wrap; gap:4px; max-width:400px;">
                <?php foreach (array_slice($topicsList, 0, 4) as $top): ?>
                  <span style="background:var(--portal-bg, #f1f5f9); border:1px solid var(--portal-border, #e2e8f0); color:var(--text-main); font-size:0.72rem; padding:1px 6px; border-radius:4px;">
                    <?php echo htmlspecialchars(trim($top)); ?>
                  </span>
                <?php endforeach; ?>
                <?php if (count($topicsList) > 4): ?>
                  <span style="font-size:0.72rem; color:var(--text-muted); padding:1px 4px;">+<?php echo count($topicsList) - 4; ?> more</span>
                <?php endif; ?>
              </div>
            </td>
            <td>
              <span class="badge badge-<?php echo $isActive ? 'success' : 'warning'; ?>">
                <?php echo htmlspecialchars($c['status'] ?? 'Active'); ?>
              </span>
            </td>
            <td style="text-align:center;">
              <div style="display:inline-flex; gap:6px; align-items:center; justify-content:center;">
                <!-- Edit Button -->
                <button type="button" class="btn btn-outline btn-sm" onclick='editCourse(<?php echo json_encode($c); ?>)' title="Edit Course">
                  <i class="fa-solid fa-pen-to-square"></i> Edit
                </button>

                <!-- Toggle Status Button -->
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="action" value="toggle_status">
                  <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($c['course_id']); ?>">
                  <input type="hidden" name="status" value="<?php echo $isActive ? 'Inactive' : 'Active'; ?>">
                  <button type="submit" class="btn btn-<?php echo $isActive ? 'outline' : 'success'; ?> btn-sm" title="<?php echo $isActive ? 'Deactivate' : 'Activate'; ?>">
                    <i class="fa-solid fa-power-off"></i>
                  </button>
                </form>

                <!-- Delete Button -->
                <form method="POST" style="display:inline;" onsubmit="return confirm('Permanently DELETE course <?php echo addslashes($c['title']); ?> (<?php echo $c['course_id']; ?>)?');">
                  <input type="hidden" name="action" value="delete_course">
                  <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($c['course_id']); ?>">
                  <button type="submit" class="btn btn-danger btn-sm" title="Delete Course">
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

<!-- Add Course Modal -->
<div id="addCourseModal" class="modal-overlay">
  <div class="modal-box" style="padding:2rem; max-width:600px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem;">
      <h3 style="margin:0; color:var(--text-main);"><i class="fa-solid fa-graduation-cap" style="color:var(--primary);"></i> Add New Course / Subject</h3>
      <button type="button" onclick="closeModal('addCourseModal')" style="background:none; border:none; font-size:1.3rem; color:var(--text-muted); cursor:pointer;">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create_course">
      <div class="form-group">
        <label>Course Title *</label>
        <input type="text" name="title" class="form-control" required placeholder="e.g. Computer Science & Artificial Intelligence">
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div class="form-group">
          <label>Category *</label>
          <select name="category" class="form-control">
            <option value="Engineering & Technology">Engineering & Technology</option>
            <option value="Business & Management">Business & Management</option>
            <option value="Health & Medical Sciences">Health & Medical Sciences</option>
            <option value="Law & Jurisprudence">Law & Jurisprudence</option>
            <option value="Mathematics & Analytics">Mathematics & Analytics</option>
            <option value="Social Sciences & Humanities">Social Sciences & Humanities</option>
            <option value="General Studies">General Studies</option>
          </select>
        </div>
        <div class="form-group">
          <label>FontAwesome Icon Class</label>
          <input type="text" name="icon" class="form-control" placeholder="fa-laptop-code" value="fa-book-open">
          <small style="font-size:0.75rem; color:var(--text-muted);">e.g. fa-laptop-code, fa-chart-pie, fa-user-nurse, fa-scale-balanced, fa-gear</small>
        </div>
      </div>

      <div class="form-group">
        <label>Course Overview & Description</label>
        <textarea name="description" class="form-control" rows="2" placeholder="Short description of this discipline for students..."></textarea>
      </div>

      <div class="form-group">
        <label>Key Topics & Modules (Comma-separated or one per line)</label>
        <textarea name="topics" class="form-control" rows="3" placeholder="Machine Learning & AI, Data Structures, Full-Stack Web Development, Cyber Security"></textarea>
        <small style="font-size:0.75rem; color:var(--text-muted);">Topics appear as bullet points on the subjects page.</small>
      </div>

      <div class="form-group">
        <label>Status</label>
        <select name="status" class="form-control">
          <option value="Active">Active (Visible on Main Site)</option>
          <option value="Inactive">Inactive (Hidden)</option>
        </select>
      </div>

      <div style="display:flex; gap:10px; margin-top:1.5rem;">
        <button type="submit" class="btn btn-primary" style="flex:1;">Create Course</button>
        <button type="button" class="btn btn-outline" onclick="closeModal('addCourseModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Course Modal -->
<div id="editCourseModal" class="modal-overlay">
  <div class="modal-box" style="padding:2rem; max-width:600px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.2rem;">
      <h3 style="margin:0; color:var(--text-main);"><i class="fa-solid fa-pen-to-square" style="color:var(--primary);"></i> Edit Course / Subject</h3>
      <button type="button" onclick="closeModal('editCourseModal')" style="background:none; border:none; font-size:1.3rem; color:var(--text-muted); cursor:pointer;">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="update_course">
      <input type="hidden" name="course_id" id="edit_course_id">

      <div class="form-group">
        <label>Course Title *</label>
        <input type="text" name="title" id="edit_course_title" class="form-control" required>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div class="form-group">
          <label>Category *</label>
          <select name="category" id="edit_course_category" class="form-control">
            <option value="Engineering & Technology">Engineering & Technology</option>
            <option value="Business & Management">Business & Management</option>
            <option value="Health & Medical Sciences">Health & Medical Sciences</option>
            <option value="Law & Jurisprudence">Law & Jurisprudence</option>
            <option value="Mathematics & Analytics">Mathematics & Analytics</option>
            <option value="Social Sciences & Humanities">Social Sciences & Humanities</option>
            <option value="General Studies">General Studies</option>
          </select>
        </div>
        <div class="form-group">
          <label>FontAwesome Icon Class</label>
          <input type="text" name="icon" id="edit_course_icon" class="form-control">
        </div>
      </div>

      <div class="form-group">
        <label>Course Overview & Description</label>
        <textarea name="description" id="edit_course_description" class="form-control" rows="2"></textarea>
      </div>

      <div class="form-group">
        <label>Key Topics & Modules (Comma-separated)</label>
        <textarea name="topics" id="edit_course_topics" class="form-control" rows="3"></textarea>
      </div>

      <div class="form-group">
        <label>Status</label>
        <select name="status" id="edit_course_status" class="form-control">
          <option value="Active">Active (Visible on Main Site)</option>
          <option value="Inactive">Inactive (Hidden)</option>
        </select>
      </div>

      <div style="display:flex; gap:10px; margin-top:1.5rem;">
        <button type="submit" class="btn btn-primary" style="flex:1;">Save Changes</button>
        <button type="button" class="btn btn-outline" onclick="closeModal('editCourseModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function editCourse(data) {
  document.getElementById('edit_course_id').value = data.course_id || '';
  document.getElementById('edit_course_title').value = data.title || '';
  document.getElementById('edit_course_category').value = data.category || 'Engineering & Technology';
  document.getElementById('edit_course_icon').value = data.icon || 'fa-book-open';
  document.getElementById('edit_course_description').value = data.description || '';
  document.getElementById('edit_course_status').value = data.status || 'Active';

  let topicsStr = '';
  if (Array.isArray(data.topics)) {
    topicsStr = data.topics.join(', ');
  } else if (typeof data.topics === 'string') {
    topicsStr = data.topics;
  }
  document.getElementById('edit_course_topics').value = topicsStr;

  openModal('editCourseModal');
}
</script>

<script src="/assets/js/portal.js"></script>
</div>
</div>
</body>
</html>
