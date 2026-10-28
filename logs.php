<?php
$page_title = 'Activity Logs';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_role('admin');

$logs = $pdo->query("SELECT l.*, u.username, u.full_name, u.role 
                     FROM activity_logs l 
                     LEFT JOIN users u ON l.user_id = u.id 
                     ORDER BY l.id DESC LIMIT 100")->fetchAll();
?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    <main class="p-4">
        <?php display_flash(); ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Audit Trail & Activity Logs</h4>
                <p class="text-muted small mb-0">Complete historical log of system actions, updates, logins, and approvals.</p>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Log ID</th>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $l): ?>
                            <tr>
                                <td><span class="badge bg-light text-dark border">#<?php echo $l['id']; ?></span></td>
                                <td><small class="text-muted"><?php echo date('d M Y, H:i:s', strtotime($l['created_at'])); ?></small></td>
                                <td>
                                    <div class="fw-semibold text-dark"><?php echo htmlspecialchars($l['full_name'] ?? 'System Action'); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($l['role'] ?? ''); ?></small>
                                </td>
                                <td><span class="badge bg-primary bg-opacity-10 text-primary"><?php echo htmlspecialchars($l['action']); ?></span></td>
                                <td class="small text-muted"><?php echo htmlspecialchars($l['details']); ?></td>
                                <td><code class="small"><?php echo htmlspecialchars($l['ip_address']); ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
