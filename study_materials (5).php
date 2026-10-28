<?php
$page_title = 'Study Materials';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_role('teacher');

$stmt = $pdo->prepare("SELECT id FROM teachers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$teacher_id = $stmt->fetchColumn() ?: 0;

$classes = $pdo->query("SELECT id, name, section FROM classes ORDER BY numeric_level ASC, name ASC")->fetchAll();
$subjects = $pdo->query("SELECT id, name, code FROM subjects ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_material'])) {
    validate_csrf();
    $class_id = intval($_POST['class_id'] ?? 0);
    $subject_id = intval($_POST['subject_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $file_link = trim($_POST['file_link'] ?? '');

    if ($class_id > 0 && $subject_id > 0 && !empty($title) && !empty($file_link)) {
        $stmt = $pdo->prepare("INSERT INTO study_materials (class_id, subject_id, teacher_id, title, description, file_link) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$class_id, $subject_id, $teacher_id, $title, $description, $file_link]);
        log_activity($pdo, $_SESSION['user_id'], 'Upload Material', "Shared: $title");
        set_flash('success', 'Study material shared successfully!');
        header('Location: ' . BASE_URL . '/teacher/study_materials.php');
        exit;
    }
}

if (isset($_GET['delete'])) {
    validate_csrf();
    $id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM study_materials WHERE id = ? AND teacher_id = ?")->execute([$id, $teacher_id]);
    set_flash('success', 'Material removed.');
    header('Location: ' . BASE_URL . '/teacher/study_materials.php');
    exit;
}

$materials = $pdo->prepare("SELECT sm.*, c.name as class_name, c.section, sub.name as subject_name 
                            FROM study_materials sm 
                            JOIN classes c ON sm.class_id = c.id 
                            JOIN subjects sub ON sm.subject_id = sub.id 
                            WHERE sm.teacher_id = ? 
                            ORDER BY sm.id DESC");
$materials->execute([$teacher_id]);
$mat_list = $materials->fetchAll();
?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    <main class="p-4">
        <?php display_flash(); ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="fa-solid fa-folder-open text-primary me-2"></i> Study Materials & Lecture Notes</h4>
                <p class="text-muted small mb-0">Upload syllabi, PDF reference materials, and resource links for students.</p>
            </div>
            <button class="btn btn-primary btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#newMatModal">
                <i class="fa-solid fa-cloud-arrow-up me-1"></i> Upload Material
            </button>
        </div>

        <div class="row g-4">
            <?php if (empty($mat_list)): ?>
                <div class="col-12 text-center py-5 text-muted">
                    <i class="fa-solid fa-box-open fa-3x mb-3 text-secondary opacity-50 d-block"></i>
                    No study materials uploaded yet. Click Upload Material to share resources with your students.
                </div>
            <?php else: ?>
                <?php foreach ($mat_list as $m): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                <span class="badge bg-primary bg-opacity-10 text-primary"><?php echo htmlspecialchars($m['class_name'] . ' ' . $m['section']); ?></span>
                                <small class="text-muted"><?php echo format_date($m['created_at']); ?></small>
                            </div>
                            <div class="card-body">
                                <span class="badge bg-light text-dark border mb-2"><?php echo htmlspecialchars($m['subject_name']); ?></span>
                                <h6 class="fw-bold text-dark"><?php echo htmlspecialchars($m['title']); ?></h6>
                                <p class="small text-muted mb-3"><?php echo htmlspecialchars($m['description']); ?></p>
                                <div class="d-flex justify-content-between align-items-center border-top pt-2">
                                    <a href="<?php echo htmlspecialchars($m['file_link']); ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa-solid fa-download me-1"></i> Open / Download</a>
                                    <form method="GET" action="" onsubmit="return confirm('Delete this material?');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="delete" value="<?php echo $m['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-link text-danger p-0"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Upload Material Modal -->
        <div class="modal fade" id="newMatModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="">
                        <?php echo csrf_field(); ?>
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">Upload Study Material</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Class *</label>
                                    <select name="class_id" class="form-select" required>
                                        <?php foreach ($classes as $c): ?>
                                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name'] . ' (' . $c['section'] . ')'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Subject *</label>
                                    <select name="subject_id" class="form-select" required>
                                        <?php foreach ($subjects as $sub): ?>
                                            <option value="<?php echo $sub['id']; ?>"><?php echo htmlspecialchars($sub['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Material Title *</label>
                                <input type="text" name="title" class="form-control" required placeholder="e.g. Chapter 4 Calculus Notes & Handout">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Description / Topic</label>
                                <textarea name="description" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">File Download URL or Cloud Link *</label>
                                <input type="url" name="file_link" class="form-control" required placeholder="https://example.com/drive/notes.pdf">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="upload_material" class="btn btn-primary btn-sm fw-semibold">Share Material</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
