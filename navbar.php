<?php
$unread_notices_count = 0;
try {
    $role = $_SESSION['user_role'] ?? 'all';
    $target = ($role == 'admin') ? 'all' : (($role == 'teacher') ? 'teachers' : 'students');
    $n_stmt = $pdo->prepare("SELECT COUNT(*) FROM notices WHERE target_audience = 'all' OR target_audience = ?");
    $n_stmt->execute([$target]);
    $unread_notices_count = $n_stmt->fetchColumn();
} catch (Exception $e) {}
?>
<header class="top-navbar d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-light d-lg-none border" id="sidebarToggle" type="button">
            <i class="fa-solid fa-bars"></i>
        </button>
        <h5 class="mb-0 fw-semibold text-secondary"><?php echo htmlspecialchars($page_title ?? 'Dashboard'); ?></h5>
    </div>
    
    <div class="d-flex align-items-center gap-3">
        <!-- Notice Indicator -->
        <div class="dropdown">
            <button class="btn btn-light position-relative rounded-circle p-2 border" type="button" data-bs-toggle="dropdown">
                <i class="fa-regular fa-bell text-secondary"></i>
                <?php if ($unread_notices_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.6rem;">
                        <?php echo $unread_notices_count; ?>
                    </span>
                <?php endif; ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width: 250px;">
                <li><h6 class="dropdown-header">Latest Notice Updates</h6></li>
                <li><span class="dropdown-item-text small text-muted"><?php echo $unread_notices_count; ?> active notice(s) broadcasted</span></li>
            </ul>
        </div>

        <!-- User Dropdown -->
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle gap-2" data-bs-toggle="dropdown">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:34px; height:34px; font-weight:600;">
                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)); ?>
                </div>
                <span class="d-none d-md-inline fw-medium small"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Account'); ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                <li><span class="dropdown-item-text small text-muted">Role: <strong><?php echo ucfirst($_SESSION['user_role'] ?? 'Guest'); ?></strong></span></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/logout.php"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Log Out</a></li>
            </ul>
        </div>
    </div>
</header>
