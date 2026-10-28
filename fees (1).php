<?php
$page_title = 'Fee Status & Receipts';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_role('student');

$stmt = $pdo->prepare("SELECT id FROM students WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student_id = $stmt->fetchColumn() ?: 0;

$fees = [];
if ($student_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM fees WHERE student_id = ? ORDER BY id DESC");
    $stmt->execute([$student_id]);
    $fees = $stmt->fetchAll();
}
?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    <main class="p-4">
        <?php display_flash(); ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="fa-solid fa-wallet text-primary me-2"></i> Fees Status & Transaction History</h4>
                <p class="text-muted small mb-0">Review invoice statements, balances, and payment receipts.</p>
            </div>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-print me-1"></i> Print Statement</button>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Invoice ID</th>
                            <th>Fee Description</th>
                            <th>Total Amount</th>
                            <th>Amount Paid</th>
                            <th>Balance Due</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Transaction Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($fees)): ?>
                            <tr><td colspan="8" class="text-center py-4 text-muted">No fee invoices issued for your account.</td></tr>
                        <?php else: ?>
                            <?php foreach ($fees as $f): ?>
                                <?php $bal = $f['amount'] - $f['paid_amount']; ?>
                                <tr>
                                    <td><span class="badge bg-light text-dark border">#INV-<?php echo str_pad($f['id'], 4, '0', STR_PAD_LEFT); ?></span></td>
                                    <td class="fw-bold text-dark"><?php echo htmlspecialchars($f['title']); ?></td>
                                    <td>₹<?php echo number_format($f['amount'], 2); ?></td>
                                    <td class="text-success fw-semibold">₹<?php echo number_format($f['paid_amount'], 2); ?></td>
                                    <td class="text-danger fw-semibold">₹<?php echo number_format($bal, 2); ?></td>
                                    <td><small class="text-muted"><?php echo format_date($f['due_date']); ?></small></td>
                                    <td>
                                        <?php if ($f['status'] === 'paid'): ?>
                                            <span class="badge bg-success">Paid</span>
                                        <?php elseif ($f['status'] === 'partial'): ?>
                                            <span class="badge bg-warning text-dark">Partial</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Unpaid</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($f['paid_amount'] > 0): ?>
                                            <div class="small fw-semibold"><?php echo htmlspecialchars($f['payment_method'] ?? 'Online'); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($f['transaction_id'] ?? ''); ?> (<?php echo format_date($f['payment_date']); ?>)</div>
                                        <?php else: ?>
                                            <span class="text-muted small">Pending Payment</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
