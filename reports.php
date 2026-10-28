<?php
$page_title = 'System Reports';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_role('admin');

$class_breakdown = $pdo->query("SELECT c.name, c.section, 
                                      COUNT(s.id) as total_students,
                                      SUM(CASE WHEN s.gender = 'Male' THEN 1 ELSE 0 END) as male_count,
                                      SUM(CASE WHEN s.gender = 'Female' THEN 1 ELSE 0 END) as female_count 
                               FROM classes c 
                               LEFT JOIN students s ON c.id = s.class_id AND s.status = 'active' 
                               GROUP BY c.id ORDER BY c.numeric_level ASC")->fetchAll();

$fee_summary = $pdo->query("SELECT 
                             COUNT(*) as total_invoices,
                             COALESCE(SUM(amount), 0) as total_billed,
                             COALESCE(SUM(paid_amount), 0) as total_paid,
                             COALESCE(SUM(amount - paid_amount), 0) as total_due 
                            FROM fees")->fetch();
?>
<div class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/navbar.php'; ?>
    <main class="p-4">
        <?php display_flash(); ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="fa-solid fa-file-invoice text-primary me-2"></i> Institutional Analytical Reports</h4>
                <p class="text-muted small mb-0">Summarized metrics across student enrollment demographics, finance, and attendance.</p>
            </div>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-print me-1"></i> Print Report</button>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-3 bg-white">
                    <span class="text-muted small">Total Fee Invoiced</span>
                    <h3 class="fw-bold text-dark mt-1">₹<?php echo number_format($fee_summary['total_billed'], 2); ?></h3>
                    <small class="text-muted"><?php echo $fee_summary['total_invoices']; ?> Total Invoices</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-3 bg-white">
                    <span class="text-muted small">Total Fee Collected</span>
                    <h3 class="fw-bold text-success mt-1">₹<?php echo number_format($fee_summary['total_paid'], 2); ?></h3>
                    <small class="text-success">Successful collections</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-3 bg-white">
                    <span class="text-muted small">Outstanding Dues</span>
                    <h3 class="fw-bold text-danger mt-1">₹<?php echo number_format($fee_summary['total_due'], 2); ?></h3>
                    <small class="text-danger">Uncollected student fees</small>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="fa-solid fa-users text-primary me-2"></i> Class Enrollment & Gender Demographics</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start">Class & Section</th>
                            <th>Total Students</th>
                            <th>Boys (Male)</th>
                            <th>Girls (Female)</th>
                            <th>Gender Ratio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($class_breakdown as $row): ?>
                            <tr>
                                <td class="text-start fw-bold text-dark"><?php echo htmlspecialchars($row['name'] . ' (' . $row['section'] . ')'); ?></td>
                                <td><span class="badge bg-primary fs-6"><?php echo $row['total_students']; ?></span></td>
                                <td class="text-primary fw-semibold"><?php echo $row['male_count']; ?></td>
                                <td class="text-success fw-semibold"><?php echo $row['female_count']; ?></td>
                                <td class="small text-muted">
                                    <?php echo $row['female_count'] > 0 ? round($row['male_count'] / $row['female_count'], 2) . ' : 1' : '-'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
