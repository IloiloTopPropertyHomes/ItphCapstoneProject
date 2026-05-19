<?php
require_once '../backends/admin_auth.php';
$db = get_db();
$users_count    = $db->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$products_count = $db->query("SELECT COUNT(*) as c FROM products")->fetch_assoc()['c'];
$total_stock    = $db->query("SELECT SUM(stock) as s FROM products")->fetch_assoc()['s'] ?? 0;
$recent_users   = $db->query("SELECT id, fullname, email, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — ThreadVibe Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="dashboard">
<?php require_once '../backends/admin_sidebar.php'; ?>
<div class="main-content">
    <div class="topbar">
        <div class="topbar-left">
            <button class="hamburger" id="hamburgerBtn" onclick="toggleSidebar()"><span></span><span></span><span></span></button>
            <div class="topbar-title">Dashboard</div>
        </div>
        <div class="topbar-right">
            <span class="topbar-user-name"><?= sanitize($_SESSION['admin_username']) ?></span>
            <div class="avatar"><?= strtoupper(substr($_SESSION['admin_username'],0,1)) ?></div>
        </div>
    </div>
    <div class="page-content">
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-label">Total Customers</div><div class="stat-number"><?= $users_count ?></div><div class="stat-icon"><i class="fa fa-users"></i></div></div>
            <div class="stat-card"><div class="stat-label">Products</div><div class="stat-number"><?= $products_count ?></div><div class="stat-icon"><i class="fa fa-shirt"></i></div></div>
            <div class="stat-card"><div class="stat-label">Total Stock</div><div class="stat-number"><?= number_format($total_stock) ?></div><div class="stat-icon"><i class="fa fa-boxes-stacked"></i></div></div>
            <div class="stat-card"><div class="stat-label">System</div><div class="stat-number" style="font-size:22px;color:var(--success)">Secure</div><div class="stat-icon"><i class="fa fa-shield-halved"></i></div></div>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="card-title">Recent Customers</div>
                <a href="users.php" class="btn btn-outline btn-sm"><i class="fa fa-arrow-right"></i> View All</a>
            </div>
            <div class="table-responsive">
                <table>
                    <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Joined</th></tr></thead>
                    <tbody>
                    <?php if (empty($recent_users)): ?>
                    <tr><td colspan="4"><div class="empty-state"><span class="empty-icon"><i class="fa fa-users"></i></span><p>No customers yet.</p></div></td></tr>
                    <?php else: foreach($recent_users as $u): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td style="color:var(--text);font-weight:500"><?= sanitize(decrypt_data($u['fullname'])) ?></td>
                        <td><?= sanitize(decrypt_data($u['email'])) ?></td>
                        <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
<script src="../assets/js/sidebar.js"></script>
</body>
</html>
