<?php
$page_title = 'Student Performance Report';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_role('teacher');

$classes = $pdo->query("SELECT id, name, section FROM classes ORDER BY numeric_level ASC, name ASC")->fetchAll();
$selected_class = intval($_GET['class_id'] ?? ($classes[0]['id'] ?? 0));

$top_performers = [];
if ($selected_class > 0) {
    $stmt = $pdo->prepare("SELECT s.id, s.roll_no, u.full_name, 
                                  AVG(m.marks_obtained) as avg_score, 
                                  COUNT(m.id) as subjects_count 
                           FROM students s 
                           JOIN users u ON s.user_id = u.id 
                           JOIN marks m ON s.id = m.student_id 
                           WHERE s.class_id = ? 
                           GROUP BY s.id 
                           ORDER BY avg_score DESC");
    $stmt->execute([$selected_class]);
    $top_performers = $stmt->fetchAll();
}
?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    <main class="p-4">
        <?php display_flash(); ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="fa-solid fa-square-poll-vertical text-primary me-2"></i> Student Performance Analytics</h4>
                <p class="text-muted small mb-0">Evaluate student grade trends and academic standings.</p>
            </div>
        </div>

        <div class="card border-0 shadow-sm p-3 mb-4">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Filter Class</label>
                    <select name="class_id" class="form-select" onchange="this.form.submit()">
                        <?php foreach ($classes as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $selected_class == $c['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['name'] . ' (' . $c['section'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start">Rank</th>
                            <th class="text-start">Student Name</th>
                            <th>Roll No</th>
                            <th>Average Mark</th>
                            <th>Equivalent Grade</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_performers)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No assessment records found for this class.</td></tr>
                        <?php else: ?>
                            <?php $rank = 1; foreach ($top_performers as $tp): ?>
                                <tr>
                                    <td class="text-start">
                                        <?php if ($rank === 1): ?>
                                            <span class="badge bg-warning text-dark"><i class="fa-solid fa-trophy"></i> 1st</span>
                                        <?php elseif ($rank === 2): ?>
                                            <span class="badge bg-secondary text-white">2nd</span>
                                        <?php elseif ($rank === 3): ?>
                                            <span class="badge bg-light text-dark border">3rd</span>
                                        <?php else: ?>
                                            <span class="text-muted"><?php echo $rank; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-start fw-bold text-dark"><?php echo htmlspecialchars($tp['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($tp['roll_no']); ?></td>
                                    <td class="fw-bold text-primary"><?php echo number_format($tp['avg_score'], 1); ?>%</td>
                                    <td><span class="badge bg-success fs-6"><?php echo calculate_grade($tp['avg_score']); ?></span></td>
                                    <td>
                                        <?php if ($tp['avg_score'] >= 75): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success">Distinction</span>
                                        <?php elseif ($tp['avg_score'] >= 40): ?>
                                            <span class="badge bg-primary bg-opacity-10 text-primary">Cleared</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger">Needs Help</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php $rank++; endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
