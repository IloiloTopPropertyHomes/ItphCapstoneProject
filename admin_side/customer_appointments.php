<?php
session_start();
require_once __DIR__ . '/../backends/config.php';
$conn = get_db_connection();

// ================= AUTH CHECK =================
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

// ================= ADMIN INFO =================
$stmt = $conn->prepare("SELECT username, gmail FROM admin_users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$stmt->bind_result($username, $admin_email);
$stmt->fetch();
$stmt->close();

$_SESSION['username'] = $username;
$_SESSION['email'] = $admin_email;

// ================= RESERVATIONS =================
$reservations = $conn->query("
    SELECT *
    FROM reservations
    ORDER BY created_at DESC
");

// ================= STATS =================
$total_res = $conn->query("SELECT COUNT(*) AS total FROM reservations")->fetch_assoc()['total'] ?? 0;
$pending = $conn->query("SELECT COUNT(*) AS total FROM reservations WHERE status='pending'")->fetch_assoc()['total'] ?? 0;
$approved = $conn->query("SELECT COUNT(*) AS total FROM reservations WHERE status='approved'")->fetch_assoc()['total'] ?? 0;
$rejected = $conn->query("SELECT COUNT(*) AS total FROM reservations WHERE status='rejected'")->fetch_assoc()['total'] ?? 0;

// Helper functions for sidebar
function isActive($filename) {
    return basename($_SERVER['PHP_SELF']) === $filename ? 'active' : '';
}
function isDropdownActive($filenames) {
    foreach ($filenames as $filename) {
        if (basename($_SERVER['PHP_SELF']) === $filename) return 'open';
    }
    return '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation History — ITPH Admin</title>
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
            --warning: #f59e0b;
            --danger: #ef4444;
            --radius: 12px;
            --radius-sm: 8px;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -2px rgba(0, 0, 0, 0.2);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.4);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text-primary);
            line-height: 1.6;
        }

        /* ===== LAYOUT ===== */
        .dashboard { display: flex; min-height: 100vh; }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: 260px;
            background: var(--bg-card);
            border-right: 1px solid var(--border);
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 100;
            overflow-y: auto;
            overflow-x: hidden;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 24px 20px;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            background: var(--bg-card);
            z-index: 10;
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
            background: linear-gradient(135deg, var(--accent), #60a5fa);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
            flex-shrink: 0;
        }

        .logo-text {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.2;
        }

        .logo-text span { color: var(--accent); }

        .logo-sub {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .sidebar-nav { padding: 16px 12px; }

        .nav-section {
            padding: 16px 16px 8px;
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            margin-bottom: 4px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            border: none;
            background: none;
            width: 100%;
            cursor: pointer;
            font-family: inherit;
            text-align: left;
        }

        .nav-link:hover {
            background: var(--accent-light);
            color: var(--text-primary);
        }

        .nav-link.active {
            background: var(--accent-light);
            color: var(--accent);
        }

        .nav-link.logout { color: var(--danger); }

        .nav-link.logout:hover {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .nav-link i {
            width: 20px;
            text-align: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .nav-link i.dropdown-arrow {
            margin-left: auto;
            font-size: 12px;
            transition: transform 0.2s;
            width: auto;
        }

        .nav-dropdown { margin-bottom: 4px; }

        .nav-dropdown.open > .nav-link .dropdown-arrow { transform: rotate(180deg); }

        .nav-dropdown.open > .dropdown-menu {
            max-height: 200px;
            opacity: 1;
        }

        .dropdown-menu {
            max-height: 0;
            opacity: 0;
            overflow: hidden;
            transition: all 0.3s ease;
            padding-left: 48px;
        }

        .dropdown-item {
            display: block;
            padding: 10px 16px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            border-radius: 6px;
            transition: all 0.2s;
            margin-bottom: 2px;
        }

        .dropdown-item:hover {
            color: var(--text-primary);
            background: rgba(59, 130, 246, 0.05);
        }

        .dropdown-item.active {
            color: var(--accent);
            background: rgba(59, 130, 246, 0.1);
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 99;
            backdrop-filter: blur(4px);
        }

        .sidebar-overlay.active { display: block; }

        .sidebar::-webkit-scrollbar { width: 6px; }
        .sidebar::-webkit-scrollbar-track { background: transparent; }
        .sidebar::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            flex: 1;
            margin-left: 260px;
            min-height: 100vh;
        }

        /* ===== TOPBAR ===== */
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

        .topbar-left { display: flex; align-items: center; gap: 16px; }

        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 20px;
            cursor: pointer;
            padding: 8px;
            border-radius: var(--radius-sm);
        }

        .menu-toggle:hover { background: var(--bg-hover); }

        .page-title { font-size: 20px; font-weight: 600; }

        .breadcrumb {
            font-size: 13px;
            color: var(--text-muted);
        }

        .topbar-right { display: flex; align-items: center; gap: 16px; }

        .notification-btn {
            position: relative;
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 18px;
            cursor: pointer;
            padding: 8px;
            border-radius: var(--radius-sm);
            transition: all 0.2s;
        }

        .notification-btn:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
        }

        .notification-badge {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 8px;
            height: 8px;
            background: var(--danger);
            border-radius: 50%;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 0.2s;
        }

        .user-menu:hover { background: var(--bg-hover); }

        .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--accent), #60a5fa);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            color: white;
        }

        .user-info { text-align: right; }

        .user-name { font-size: 14px; font-weight: 600; }

        .user-role { font-size: 12px; color: var(--text-muted); }

        /* ===== CONTENT ===== */
        .content { padding: 24px; max-width: 1400px; }

        /* ===== PAGE HERO ===== */
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
            max-width: 500px;
        }

        /* ===== STATS GRID ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s;
        }

        .stat-card:hover {
            border-color: var(--accent);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .stat-icon.blue { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
        .stat-icon.amber { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
        .stat-icon.green { background: rgba(34, 197, 94, 0.15); color: #4ade80; }
        .stat-icon.red { background: rgba(239, 68, 68, 0.15); color: #f87171; }

        .stat-value { font-size: 32px; font-weight: 700; margin-bottom: 4px; }

        .stat-label {
            font-size: 14px;
            color: var(--text-secondary);
        }

        /* ===== SEARCH BAR ===== */
        .search-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 240px;
            padding: 12px 16px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: all 0.2s;
        }

        .search-input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-light);
        }

        .search-input::placeholder { color: var(--text-muted); }

        /* ===== CARD ===== */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            margin-bottom: 24px;
            overflow: hidden;
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .card-title { font-size: 18px; font-weight: 600; }

        .card-meta {
            font-size: 13px;
            color: var(--text-muted);
        }

        .card-body { padding: 0; }

        /* ===== TABLE ===== */
        .table-responsive { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            min-width: 900px;
        }

        thead th {
            padding: 14px 16px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
            background: var(--bg);
            white-space: nowrap;
        }

        tbody td {
            padding: 16px;
            border-bottom: 1px solid var(--border);
            color: var(--text-secondary);
            vertical-align: middle;
        }

        tbody tr:hover td {
            background: rgba(255, 255, 255, 0.02);
            color: var(--text-primary);
        }

        tbody tr:last-child td { border-bottom: none; }

        .td-name {
            font-weight: 600;
            color: var(--text-primary);
        }

        .td-email {
            font-size: 13px;
            color: var(--text-muted);
        }

        .td-phone {
            font-family: 'SF Mono', monospace;
            font-size: 13px;
            color: var(--text-secondary);
        }

        .td-property {
            color: var(--text-primary);
            font-weight: 500;
        }

        .td-date {
            font-family: 'SF Mono', monospace;
            font-size: 12px;
            color: var(--text-muted);
            white-space: nowrap;
        }

        .td-payment {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ===== BADGES ===== */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 100px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-pending {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
        }

        .badge-approved {
            background: rgba(34, 197, 94, 0.15);
            color: #4ade80;
        }

        .badge-rejected {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
        }

        .badge-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 60px 24px;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-state p { font-size: 14px; }

        /* ===== SCROLLBAR ===== */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .menu-toggle { display: block; }
            .content { padding: 16px; }
            .stats-grid { grid-template-columns: 1fr; }
            .search-bar { flex-direction: column; }
            .search-input { width: 100%; }
            .card-header { flex-direction: column; align-items: flex-start; }
            .user-info { display: none; }
        }

        @media (min-width: 769px) {
            .sidebar-overlay { display: none !important; }
        }
    </style>
</head>

<body>
    <div class="dashboard">
        
        <!-- SIDEBAR -->
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
        
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

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <!-- TOPBAR -->
            <header class="topbar">
                <div class="topbar-left">
                    <button class="menu-toggle" onclick="toggleSidebar()">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div>
                        <div class="page-title">Reservation History</div>
                        <div class="breadcrumb">Home / Management / Reservations</div>
                    </div>
                </div>

                <div class="topbar-right">
                    <button class="notification-btn">
                        <i class="fa-solid fa-bell"></i>
                        <span class="notification-badge"></span>
                    </button>
                    <div class="user-menu">
                        <div class="user-info">
                            <div class="user-name"><?= htmlspecialchars($username) ?></div>
                            <div class="user-role">Administrator</div>
                        </div>
                        <div class="user-avatar">
                            <?= strtoupper(substr($username, 0, 1)) ?>
                        </div>
                    </div>
                </div>
            </header>

            <!-- CONTENT -->
            <div class="content">

                <!-- PAGE HERO -->
                <div class="page-hero">
                    <div class="hero-eyebrow">Reservations</div>
                    <div class="hero-title">Reservation History</div>
                    <div class="hero-desc">Complete overview of all property reservations including status, payment method, and scheduling details.</div>
                </div>

                <!-- STATS -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon blue">
                                <i class="fa-solid fa-calendar-days"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?= number_format($total_res) ?></div>
                        <div class="stat-label">Total Reservations</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon amber">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?= number_format($pending) ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon green">
                                <i class="fa-solid fa-check-circle"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?= number_format($approved) ?></div>
                        <div class="stat-label">Approved</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon red">
                                <i class="fa-solid fa-circle-xmark"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?= number_format($rejected) ?></div>
                        <div class="stat-label">Rejected</div>
                    </div>
                </div>

                <!-- SEARCH -->
                <div class="search-bar">
                    <input 
                        type="text" 
                        class="search-input" 
                        id="reservationSearch" 
                        placeholder="Search by name, email, property, or phone..."
                        onkeyup="filterReservations()"
                    >
                </div>

                <!-- TABLE -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">All Reservations</div>
                        <div class="card-meta"><?= number_format($reservations->num_rows) ?> records found</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="reservationsTable">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Contact</th>
                                        <th>Property</th>
                                        <th>Schedule</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($reservations && $reservations->num_rows > 0): ?>
                                        <?php while ($row = $reservations->fetch_assoc()): 
                                            $status_class = match(strtolower($row['status'])) {
                                                'approved' => 'badge-approved',
                                                'rejected' => 'badge-rejected',
                                                default => 'badge-pending'
                                            };
                                            $status_color = match(strtolower($row['status'])) {
                                                'approved' => 'green',
                                                'rejected' => 'red',
                                                default => 'amber'
                                            };
                                        ?>
                                        <tr data-search="<?= strtolower(htmlspecialchars($row['fullname'] . ' ' . $row['email'] . ' ' . $row['phone'] . ' ' . $row['property'])) ?>">
                                            <td>
                                                <div class="td-name"><?= htmlspecialchars($row['fullname']) ?></div>
                                            </td>
                                            <td>
                                                <div class="td-email"><?= htmlspecialchars($row['email']) ?></div>
                                                <div class="td-phone"><?= htmlspecialchars($row['phone']) ?></div>
                                            </td>
                                            <td class="td-property">
                                                <i class="fa-solid fa-house" style="color: var(--text-muted); margin-right: 6px;"></i>
                                                <?= htmlspecialchars($row['property']) ?>
                                            </td>
                                            <td class="td-date">
                                                <div><i class="fa-regular fa-calendar" style="color: var(--text-muted); margin-right: 6px;"></i><?= htmlspecialchars($row['date'] ?? 'N/A') ?></div>
                                                <div style="margin-top: 4px;"><i class="fa-regular fa-clock" style="color: var(--text-muted); margin-right: 6px;"></i><?= htmlspecialchars($row['time'] ?? 'N/A') ?></div>
                                            </td>
                                            <td>
                                                <span class="badge <?= $status_class ?>">
                                                    <span class="badge-dot" style="background: var(--<?= $status_color ?>)"></span>
                                                    <?= ucfirst(htmlspecialchars($row['status'])) ?>
                                                </span>
                                            </td>
                                            <td class="td-payment">
                                                <?php if (!empty($row['payment_type'])): ?>
                                                    <span style="color: var(--accent);">
                                                        <i class="fa-solid fa-money-bill-wave" style="margin-right: 4px;"></i>
                                                        <?= htmlspecialchars($row['payment_type']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span style="color: var(--text-muted);">—</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6">
                                                <div class="empty-state">
                                                    <i class="fa-solid fa-calendar-xmark"></i>
                                                    <p>No reservations found in the system</p>
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
        // ===== SIDEBAR & DROPDOWN =====
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        }

        function toggleDropdown(button) {
            const dropdown = button.parentElement;
            const wasOpen = dropdown.classList.contains('open');
            document.querySelectorAll('.nav-dropdown.open').forEach(d => {
                if (d !== dropdown) d.classList.remove('open');
            });
            dropdown.classList.toggle('open', !wasOpen);
        }

        // Auto-open active dropdowns
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.nav-dropdown').forEach(dropdown => {
                if (dropdown.querySelector('.dropdown-item.active')) {
                    dropdown.classList.add('open');
                }
            });
        });

        // ===== SEARCH FILTER =====
        function filterReservations() {
            const query = document.getElementById('reservationSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#reservationsTable tbody tr[data-search]');
            
            rows.forEach(row => {
                const searchData = row.getAttribute('data-search');
                row.style.display = searchData.includes(query) ? '' : 'none';
            });
        }
    </script>
</body>
</html>