<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://www.gstatic.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self';");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: no-referrer-when-downgrade");

session_start();

require_once __DIR__ . '/../backends/config.php';
$conn = get_db_connection();

// Reset login attempts (unblock)
if(isset($_POST['reset_attempts'])){
    $agent_id = (int)$_POST['agent_id'];
    $stmt = $conn->prepare("UPDATE agents SET login_attempts = 0 WHERE id = ?");
    $stmt->bind_param("i", $agent_id);
    if($stmt->execute()){
        $_SESSION['success'] = "Agent unblocked successfully.";
    } else {
        $_SESSION['error'] = "Failed to unblock agent.";
    }
    $stmt->close();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

if(isset($_POST['add_agent'])){
    $username = trim($_POST['username']);
    $gmail = trim($_POST['gmail']);
    $phone = trim($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $status = 'Active';

    $check = $conn->prepare("SELECT id FROM agents WHERE gmail = ?");
    $check->bind_param("s", $gmail);
    $check->execute();
    $check->store_result();

    if($check->num_rows > 0){
        $_SESSION['error'] = "Email already exists.";
    } else {
        $stmt = $conn->prepare("INSERT INTO agents (username, gmail, phone, password, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $gmail, $phone, $password, $status);

        if($stmt->execute()){
            $_SESSION['success'] = "Agent added successfully.";
        } else {
            $_SESSION['error'] = "Failed to add agent.";
        }

        $stmt->close();
    }

    $check->close();

    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Auth check
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

// Admin info
$stmt = $conn->prepare("SELECT username, gmail FROM admin_users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$stmt->bind_result($username, $admin_email);
if ($stmt->fetch()) {
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $admin_email;
}
$stmt->close();

// Handle agent actions
if(isset($_POST['toggle_status'])){
    $agent_id = (int)$_POST['agent_id'];
    $current_status = $_POST['current_status'] === 'Active' ? 'Inactive' : 'Active';

    $stmt = $conn->prepare("UPDATE agents SET status=? WHERE id=?");
    $stmt->bind_param("si", $current_status, $agent_id);
    $stmt->execute();
    $stmt->close();

    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

if(isset($_POST['delete_agent'])){
    $agent_id = (int)$_POST['agent_id'];
    $stmt = $conn->prepare("DELETE FROM agents WHERE id=?");
    $stmt->bind_param("i", $agent_id);
    $stmt->execute();
    $stmt->close();

    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

// Fetch all agents
$agents_query = $conn->query("SELECT * FROM agents ORDER BY id DESC");

// Helper functions
function isActive($filename) {
    return basename($_SERVER['PHP_SELF']) === $filename ? 'active' : '';
}

function isDropdownActive($filenames) {
    foreach ($filenames as $filename) {
        if (basename($_SERVER['PHP_SELF']) === $filename) {
            return 'open';
        }
    }
    return '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Agents — ITPH Admin</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

:root {
    --bg: #0f172a;
    --bg-card: #1e293b;
    --bg-hover: #334155;
    --border: #334155;
    --text-primary: #f1f5f9;
    --text-secondary: #94a3b8;
    --text-muted: #64748b;
    --accent: #3b82f6;
    --accent-light: rgba(59, 130, 246, 0.15);
    --success: #22c55e;
    --danger: #ef4444;
    --warning: #f59e0b;
    --radius: 12px;
    --radius-sm: 8px;
    --shadow: 0 4px 6px -1px rgba(0,0,0,.3);
    --shadow-lg: 0 10px 15px -3px rgba(0,0,0,.4);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', sans-serif;
    background: var(--bg);
    color: var(--text-primary);
    line-height: 1.6;
}

/* LAYOUT */

.dashboard {
    display: flex;
    min-height: 100vh;
}

/* SIDEBAR */

.sidebar {
    width: 260px;
    background: var(--bg-card);
    border-right: 1px solid var(--border);
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    overflow-y: auto;
    z-index: 100;
    transition: transform .3s ease;
}

.sidebar-header {
    padding: 24px 20px;
    border-bottom: 1px solid var(--border);
}

.logo {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: inherit;
}

.logo-icon {
    width: 40px;
    height: 40px;
    border-radius: var(--radius-sm);
    background: linear-gradient(135deg, var(--accent), #60a5fa);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
}

.logo-text {
    font-size: 18px;
    font-weight: 700;
}

.logo-text span {
    color: var(--accent);
}

.logo-sub {
    font-size: 11px;
    color: var(--text-muted);
}

.sidebar-nav {
    padding: 16px 12px;
}

.nav-section {
    padding: 16px;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: var(--text-muted);
    font-weight: 600;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    margin-bottom: 4px;
    border-radius: var(--radius-sm);
    text-decoration: none;
    color: var(--text-secondary);
    transition: .2s;
    font-size: 14px;
    font-weight: 500;
    background: none;
    border: none;
    width: 100%;
    cursor: pointer;
    font-family: inherit;
}

.nav-link:hover {
    background: var(--accent-light);
    color: var(--text-primary);
}

.nav-link.active {
    background: var(--accent-light);
    color: var(--accent);
}

.nav-link.logout {
    color: var(--danger);
}

.nav-link.logout:hover {
    background: rgba(239, 68, 68, .1);
}

.nav-link i {
    width: 20px;
    text-align: center;
}

.nav-link i.dropdown-arrow {
    width: auto;
    margin-left: auto;
    font-size: 12px;
    transition: transform .3s ease;
}

/* DROPDOWN */

.nav-dropdown {
    margin-bottom: 4px;
}

.nav-dropdown.open > .nav-link i.dropdown-arrow {
    transform: rotate(180deg);
}

.dropdown-menu {
    display: none;
    padding: 4px 0 4px 48px;
}

.nav-dropdown.open .dropdown-menu {
    display: block;
}

.dropdown-item {
    display: block;
    padding: 10px 16px;
    border-radius: var(--radius-sm);
    text-decoration: none;
    color: var(--text-secondary);
    font-size: 13px;
    font-weight: 500;
    transition: .2s;
    margin-bottom: 4px;
}

.dropdown-item:hover {
    background: var(--accent-light);
    color: var(--text-primary);
}

.dropdown-item.active {
    background: var(--accent-light);
    color: var(--accent);
}

/* SIDEBAR OVERLAY */

.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,.5);
    z-index: 99;
}

.sidebar-overlay.active {
    display: block;
}

/* MAIN */

.main-content {
    flex: 1;
    margin-left: 260px;
    min-height: 100vh;
}

/* TOPBAR */

.topbar {
    height: 64px;
    background: var(--bg-card);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    position: sticky;
    top: 0;
    z-index: 50;
}

.topbar-left {
    display: flex;
    align-items: center;
    gap: 16px;
}

.menu-toggle {
    display: none;
    background: none;
    border: none;
    color: var(--text-secondary);
    font-size: 20px;
    cursor: pointer;
    padding: 8px;
}

.page-title {
    font-size: 20px;
    font-weight: 600;
}

.breadcrumb {
    font-size: 13px;
    color: var(--text-muted);
}

.topbar-right {
    display: flex;
    align-items: center;
    gap: 16px;
}

.user-menu {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-info {
    text-align: right;
}

.user-name {
    font-size: 14px;
    font-weight: 600;
}

.user-role {
    font-size: 12px;
    color: var(--text-muted);
}

.user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--accent), #60a5fa);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: #fff;
}

/* CONTENT */

.content {
    padding: 24px;
    max-width: 1400px;
}

/* ALERTS */

.alert {
    padding: 12px 16px;
    border-radius: var(--radius-sm);
    margin-bottom: 16px;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background: rgba(34, 197, 94, 0.15);
    color: var(--success);
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.alert-danger {
    background: rgba(239, 68, 68, 0.15);
    color: var(--danger);
    border: 1px solid rgba(239, 68, 68, 0.3);
}

/* HERO */

.page-hero {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 28px 24px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}

.page-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--accent), #60a5fa);
}

.hero-eyebrow {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: var(--accent);
    margin-bottom: 8px;
    font-weight: 600;
}

.hero-title {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 6px;
}

.hero-desc {
    font-size: 14px;
    color: var(--text-secondary);
}

/* CARD */

.card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    margin-bottom: 24px;
}

.card-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.card-title {
    font-size: 18px;
    font-weight: 600;
}

.card-body {
    padding: 24px;
}

/* FORM */

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    align-items: end;
}

.form-group {
    margin-bottom: 0;
}

.form-group.full {
    grid-column: 1 / -1;
}

label {
    display: block;
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-secondary);
}

input[type="text"],
input[type="email"],
input[type="password"],
input[type="number"],
select,
textarea {
    width: 100%;
    padding: 12px 14px;
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    color: var(--text-primary);
    font-size: 14px;
    outline: none;
    transition: .2s;
    font-family: inherit;
}

input:focus,
select:focus,
textarea:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px var(--accent-light);
}

/* BUTTONS */

.btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    border: none;
    border-radius: var(--radius-sm);
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    transition: .2s;
    text-decoration: none;
    font-family: inherit;
}

.btn-primary {
    background: var(--accent);
    color: #fff;
}

.btn-primary:hover {
    background: #2563eb;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, .3);
}

.btn-success {
    background: var(--success);
    color: #fff;
}

.btn-success:hover {
    background: #16a34a;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(34, 197, 94, .3);
}

.btn-danger {
    background: var(--danger);
    color: #fff;
}

.btn-danger:hover {
    background: #dc2626;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, .3);
}

.btn-warning {
    background: var(--warning);
    color: #fff;
}

.btn-warning:hover {
    background: #d97706;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, .3);
}

.btn-block {
    width: 100%;
    justify-content: center;
}

/* TABLE */

.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
    min-width: 900px;
}

thead th {
    text-align: left;
    padding: 14px 16px;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .5px;
    color: var(--text-muted);
    font-weight: 600;
    border-bottom: 1px solid var(--border);
    white-space: nowrap;
}

tbody td {
    padding: 14px 16px;
    border-bottom: 1px solid var(--border);
    color: var(--text-secondary);
    vertical-align: middle;
}

tbody tr:hover {
    background: rgba(51, 65, 85, 0.3);
}

tbody tr:hover td {
    color: var(--text-primary);
}

tbody tr:last-child td {
    border-bottom: none;
}

/* STATUS BADGE */

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.status-active {
    background: rgba(34, 197, 94, 0.15);
    color: var(--success);
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.status-inactive {
    background: rgba(148, 163, 184, 0.15);
    color: var(--text-secondary);
    border: 1px solid rgba(148, 163, 184, 0.3);
}

.status-blocked {
    background: rgba(239, 68, 68, 0.15);
    color: var(--danger);
    border: 1px solid rgba(239, 68, 68, 0.3);
}

/* ACTIONS CELL */

.actions-cell {
    display: flex;
    gap: 8px;
    flex-wrap: nowrap;
}

/* EMPTY STATE */

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--text-muted);
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 16px;
    display: block;
    opacity: 0.5;
}

.empty-state p {
    font-size: 16px;
}

/* RESPONSIVE */

@media(max-width: 1024px) {
    .actions-cell {
        flex-direction: column;
    }
}

@media(max-width: 768px) {

    .sidebar {
        transform: translateX(-100%);
    }

    .sidebar.open {
        transform: translateX(0);
    }

    .main-content {
        margin-left: 0;
    }

    .menu-toggle {
        display: block;
    }

    .content {
        padding: 16px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }

    .user-info {
        display: none;
    }

    table {
        min-width: 800px;
    }
}

</style>
</head>

<body>

<div class="dashboard">

    <!-- SIDEBAR OVERLAY -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">

        <div class="sidebar-header">
            <a href="../admin_side/index.php" class="logo">
                <div class="logo-icon">
                    <i class="fa-solid fa-building"></i>
                </div>
                <div>
                    <div class="logo-text">ITPH <span>Admin</span></div>
                    <div class="logo-sub">Real Estate Management</div>
                </div>
            </a>
        </div>

        <nav class="sidebar-nav">

            <div class="nav-section">Main</div>

            <a href="../admin_side/index.php" class="nav-link <?= isActive('index.php') ?>">
                <i class="fa-solid fa-chart-line"></i>
                <span>Dashboard</span>
            </a>

            <a href="../admin_side/ad_account.php" class="nav-link <?= isActive('ad_account.php') ?>">
                <i class="fa-solid fa-user-cog"></i>
                <span>My Account</span>
            </a>

            <div class="nav-section">Management</div>

            <div class="nav-dropdown <?= isDropdownActive(['customer_ban.php', 'customer_appointments.php']) ?>">
                <button class="nav-link" onclick="toggleDropdown(this)">
                    <i class="fa-solid fa-users"></i>
                    <span>Manage Customers</span>
                    <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                </button>
                <div class="dropdown-menu">
                    <a href="../admin_side/customer_ban.php" class="dropdown-item <?= isActive('customer_ban.php') ?>">
                        Ban / Unban
                    </a>
                    <a href="../admin_side/customer_appointments.php" class="dropdown-item <?= isActive('customer_appointments.php') ?>">
                        Appointments History
                    </a>
                </div>
            </div>

            <div class="nav-dropdown <?= isDropdownActive(['add-property.php', 'update_properties.php']) ?>">
                <button class="nav-link" onclick="toggleDropdown(this)">
                    <i class="fa-solid fa-house"></i>
                    <span>Properties</span>
                    <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                </button>
                <div class="dropdown-menu">
                    <a href="../backends/add-property.php" class="dropdown-item <?= isActive('add-property.php') ?>">
                        Add Property
                    </a>
                    <a href="../admin_side/update_properties.php" class="dropdown-item <?= isActive('update_properties.php') ?>">
                        Update Property
                    </a>
                </div>
            </div>

            <a href="../admin_side/admin_blog_management.php" class="nav-link <?= isActive('admin_blog_management.php') ?>">
                <i class="fa-solid fa-newspaper"></i>
                <span>Blog & News</span>
            </a>

            <a href="../admin_side/transaction.php" class="nav-link <?= isActive('transaction.php') ?>">
                <i class="fa-solid fa-money-bill-transfer"></i>
                <span>Transactions</span>
            </a>

            <a href="../admin_side/admin_message.php" class="nav-link <?= isActive('admin_message.php') ?>">
                <i class="fa-solid fa-envelope"></i>
                <span>Messages</span>
            </a>

            <a href="../admin_side/manage_agent.php" class="nav-link <?= isActive('manage_agent.php') ?>">
                <i class="fa-solid fa-user-tie"></i>
                <span>Manage Agents</span>
            </a>

            <div class="nav-section">System</div>

            <a href="../admin_side/audit_log.php" class="nav-link <?= isActive('audit_log.php') ?>">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Audit Log</span>
            </a>

            <a href="logout.php" class="nav-link logout">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Logout</span>
            </a>

        </nav>

    </aside>

    <!-- MAIN -->
    <main class="main-content">

        <!-- TOPBAR -->
        <header class="topbar">

            <div class="topbar-left">
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <div class="page-title">Manage Agents</div>
                    <div class="breadcrumb">Home / Management / Manage Agents</div>
                </div>
            </div>

            <div class="topbar-right">
                <div class="user-menu">
                    <div class="user-info">
                        <div class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                        <div class="user-role">Administrator</div>
                    </div>
                    <div class="user-avatar">
                        <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                    </div>
                </div>
            </div>

        </header>

        <!-- CONTENT -->
        <div class="content">

            <!-- HERO -->
            <div class="page-hero">
                <div class="hero-eyebrow">Agents</div>
                <div class="hero-title">Manage All Agents</div>
                <div class="hero-desc">Add, edit, activate, or remove agents from the system.</div>
            </div>

            <!-- ADD AGENT CARD -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Add New Agent</div>
                </div>

                <div class="card-body">

                    <?php if(isset($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <i class="fa-solid fa-check-circle"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <i class="fa-solid fa-circle-exclamation"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" name="username" placeholder="Enter username..." required>
                            </div>

                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="gmail" placeholder="Enter email..." required>
                            </div>

                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" name="phone" placeholder="Enter phone..." required>
                            </div>

                            <div class="form-group">
                                <label>Password</label>
                                <input type="password" name="password" placeholder="Enter password..." required>
                            </div>

                            <div class="form-group">
                                <button type="submit" name="add_agent" class="btn btn-primary btn-block">
                                    <i class="fa-solid fa-user-plus"></i> Add Agent
                                </button>
                            </div>
                        </div>
                    </form>

                </div>
            </div>

            <!-- AGENT LIST CARD -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Agent List</div>
                </div>

                <div class="card-body">

                    <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Last Login</th>
                                <th>Attempts</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php if($agents_query->num_rows > 0): ?>
                            <?php while($agent = $agents_query->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($agent['username']) ?></td>
                                    <td><?= htmlspecialchars($agent['gmail']) ?></td>
                                    <td><?= htmlspecialchars($agent['phone']) ?></td>
                                    <td><?= htmlspecialchars($agent['last_login'] ? date('M d, Y h:i A', strtotime($agent['last_login'])) : '-') ?></td>
                                    <td><?= $agent['login_attempts'] ?></td>
                                    <td>
                                        <?php if($agent['login_attempts'] >= 5): ?>
                                            <span class="status-badge status-blocked">
                                                <i class="fa-solid fa-lock"></i> Blocked
                                            </span>
                                        <?php elseif($agent['status'] === 'Active'): ?>
                                            <span class="status-badge status-active">
                                                <i class="fa-solid fa-circle-check"></i> Active
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge status-inactive">
                                                <i class="fa-solid fa-circle-xmark"></i> Inactive
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="actions-cell">
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="agent_id" value="<?= $agent['id'] ?>">
                                                <input type="hidden" name="current_status" value="<?= $agent['status'] ?>">
                                                <button type="submit" name="toggle_status" class="btn btn-warning">
                                                    <i class="fa-solid fa-power-off"></i> <?= $agent['status'] === 'Active' ? 'Deactivate' : 'Activate' ?>
                                                </button>
                                            </form>

                                            <?php if($agent['login_attempts'] >= 5): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="agent_id" value="<?= $agent['id'] ?>">
                                                <button type="submit" name="reset_attempts" class="btn btn-success">
                                                    <i class="fa-solid fa-unlock"></i> Unblock
                                                </button>
                                            </form>
                                            <?php endif; ?>

                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this agent?');">
                                                <input type="hidden" name="agent_id" value="<?= $agent['id'] ?>">
                                                <button type="submit" name="delete_agent" class="btn btn-danger">
                                                    <i class="fa-solid fa-trash"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <span class="empty-icon"><i class="fa-solid fa-users-slash"></i></span>
                                        <p>No agents found.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                    </div>

                </div>
            </div>

        </div>
    </main>
</div>

<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('active');
}

function toggleDropdown(btn) {
    const dropdown = btn.parentElement;
    dropdown.classList.toggle('open');
}
</script>

</body>
</html>