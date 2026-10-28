<?php
$page_title = 'Notice Board';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_notice'])) {
    validate_csrf();
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $target = $_POST['target_audience'] ?? 'all';
    $priority = $_POST['priority'] ?? 'normal';

    if (!empty($title) && !empty($content)) {
        $stmt = $pdo->prepare("INSERT INTO notices (title, content, target_audience, priority, posted_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $content, $target, $priority, $_SESSION['user_id']]);
        log_activity($pdo, $_SESSION['user_id'], 'Post Notice', "Published notice: $title");
        set_flash('success', 'Notice published successfully!');
        header('Location: ' . BASE_URL . '/admin/notices.php');
        exit;
    }
}

if (isset($_GET['delete'])) {
    validate_csrf();
    $id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM notices WHERE id = ?")->execute([$id]);
    log_activity($pdo, $_SESSION['user_id'], 'Delete Notice', "Deleted notice ID: $id");
    set_flash('success', 'Notice removed.');
    header('Location: ' . BASE_URL . '/admin/notices.php');
    exit;
}

$notices = $pdo->query("SELECT n.*, u.full_name as author_name FROM notices n JOIN users u ON n.posted_by = u.id ORDER BY n.id DESC")->fetchAll();
?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    <main class="p-4">
        <?php display_flash(); ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="fa-solid fa-bullhorn text-primary me-2"></i> Notice Board & Circulars</h4>
                <p class="text-muted small mb-0">Broadcast institutional notices, alerts, and circulars to faculty and students.</p>
            </div>
            <button class="btn btn-primary btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#newNoticeModal">
                <i class="fa-solid fa-plus me-1"></i> Post New Notice
            </button>
        </div>

        <div class="row g-4">
            <?php foreach ($notices as $n): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                            <span class="badge <?php echo $n['priority'] === 'urgent' ? 'bg-danger' : 'bg-primary'; ?> text-uppercase" style="font-size:0.7rem;">
                                <?php echo $n['priority']; ?>
                            </span>
                            <small class="text-muted"><?php echo format_date($n['created_at']); ?></small>
                        </div>
                        <div class="card-body">
                            <h6 class="fw-bold text-dark"><?php echo htmlspecialchars($n['title']); ?></h6>
                            <p class="small text-muted mb-3"><?php echo nl2br(htmlspecialchars($n['content'])); ?></p>
                            <div class="d-flex justify-content-between align-items-center border-top pt-2">
                                <span class="badge bg-light text-dark border">Audience: <?php echo ucfirst($n['target_audience']); ?></span>
                                <form method="GET" action="" onsubmit="return confirm('Remove this notice?');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="delete" value="<?php echo $n['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-link text-danger p-0"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Post Notice Modal -->
        <div class="modal fade" id="newNoticeModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="">
                        <?php echo csrf_field(); ?>
                        <div class="modal-header">
                            <h5 class="modal-title fw-bold">Publish Notice / Circular</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Notice Title *</label>
                                <input type="text" name="title" class="form-control" required placeholder="e.g. Schedule of Annual Sports Meet">
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Target Audience</label>
                                    <select name="target_audience" class="form-select">
                                        <option value="all">Everyone (All)</option>
                                        <option value="teachers">Faculty Only</option>
                                        <option value="students">Students Only</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small fw-semibold">Priority</label>
                                    <select name="priority" class="form-select">
                                        <option value="normal">Normal</option>
                                        <option value="urgent">Urgent</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Notice Content *</label>
                                <textarea name="content" class="form-control" rows="4" required placeholder="Type the detailed circular message here..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="post_notice" class="btn btn-primary btn-sm fw-semibold">Broadcast Notice</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
