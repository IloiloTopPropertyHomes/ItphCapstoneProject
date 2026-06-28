<?php
session_start();
require_once __DIR__ . '/../backends/config.php';
require_once __DIR__ . '/../backends/send_email.php';
require_once __DIR__ . '/../vendor/autoload.php';

header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://www.gstatic.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self';");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: no-referrer-when-downgrade");

$conn = get_db_connection();

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
$stmt->fetch();
$stmt->close();
$_SESSION['username'] = $username;
$_SESSION['email'] = $admin_email;

// Handle Ban/Unban
if (isset($_POST['ban_id'])) {
    $id = (int)$_POST['ban_id'];
    $new_status = $_POST['current_status'] === 'banned' ? 'active' : 'banned';

    $stmt = $conn->prepare("SELECT id FROM customer_bans WHERE customer_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        $stmt = $conn->prepare("UPDATE customer_bans SET status=? WHERE customer_id=?");
        $stmt->bind_param("si", $new_status, $id);
    } else {
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO customer_bans (customer_id, status) VALUES (?, ?)");
        $stmt->bind_param("is", $id, $new_status);
    }
    $stmt->execute();
    $stmt->close();

    header("Location: customer_ban.php");
    exit;
}

// Fetch all customers with ban status
$customer_query = $conn->query("
    SELECT c.*, IFNULL(cb.status, 'active') AS ban_status
    FROM customers c
    LEFT JOIN customer_bans cb ON c.id = cb.customer_id
    ORDER BY c.created_at DESC
");

// Counts for stats cards
$total_customers = $conn->query("SELECT COUNT(*) AS total FROM customers")->fetch_assoc()['total'] ?? 0;
$total_banned = $conn->query("SELECT COUNT(*) AS total FROM customer_bans WHERE status='banned'")->fetch_assoc()['total'] ?? 0;
$total_active = $total_customers - $total_banned;

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
    <title>Customer Management — ITPH Admin</title>
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
            min-width: 700px;
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

        .badge-active {
            background: rgba(34, 197, 94, 0.15);
            color: #4ade80;
        }

        .badge-banned {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
        }

        .badge-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }

        /* ===== BUTTONS ===== */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            font-family: inherit;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #16a34a;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .btn-sm { padding: 8px 14px; font-size: 12px; }

        /* ===== CONFIRM MODAL ===== */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 200;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }

        .modal-overlay.active { display: flex; }

        .modal {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            width: 100%;
            max-width: 420px;
            padding: 24px;
            animation: modalIn 0.2s ease;
        }

        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .modal-text {
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 20px;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .btn-ghost {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border);
        }

        .btn-ghost:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
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
                        <div class="page-title">Customer Management</div>
                        <div class="breadcrumb">Home / Management / Customers</div>
                    </div>
                </div>

                <div class="topbar-right">
                    <button class="notification-btn">
                        <i class="fa-solid fa-bell"></i>
                        <span class="notification-badge"></span>
                    </button>
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

                <!-- PAGE HERO -->
                <div class="page-hero">
                    <div class="hero-eyebrow">User Management</div>
                    <div class="hero-title">Customer Ban Management</div>
                    <div class="hero-desc">Manage customer access by banning or unbanning accounts. Banned customers cannot log in or make reservations.</div>
                </div>

                <!-- STATS -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon blue">
                                <i class="fa-solid fa-users"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?= number_format($total_customers) ?></div>
                        <div class="stat-label">Total Customers</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon green">
                                <i class="fa-solid fa-user-check"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?= number_format($total_active) ?></div>
                        <div class="stat-label">Active Customers</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon red">
                                <i class="fa-solid fa-user-slash"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?= number_format($total_banned) ?></div>
                        <div class="stat-label">Banned Customers</div>
                    </div>
                </div>

                <!-- SEARCH -->
                <div class="search-bar">
                    <input 
                        type="text" 
                        class="search-input" 
                        id="customerSearch" 
                        placeholder="Search by name, email, or phone..."
                        onkeyup="filterCustomers()"
                    >
                </div>

                <!-- CUSTOMERS TABLE -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">All Customers</div>
                        <div class="card-meta"><?= number_format($customer_query->num_rows) ?> records found</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="customersTable">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Contact</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($customer_query && $customer_query->num_rows > 0): ?>
                                        <?php while ($cust = $customer_query->fetch_assoc()): ?>
                                        <tr data-search="<?= strtolower(htmlspecialchars($cust['fullname'] . ' ' . $cust['email'] . ' ' . $cust['phone'])) ?>">
                                            <td>
                                                <div class="td-name"><?= htmlspecialchars($cust['fullname']) ?></div>
                                            </td>
                                            <td>
                                                <div class="td-email"><?= htmlspecialchars($cust['email']) ?></div>
                                            </td>
                                            <td class="td-phone"><?= htmlspecialchars($cust['phone']) ?></td>
                                            <td>
                                                <?php if ($cust['ban_status'] === 'active'): ?>
                                                    <span class="badge badge-active">
                                                        <span class="badge-dot" style="background: #4ade80;"></span>
                                                        Active
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-banned">
                                                        <span class="badge-dot" style="background: #f87171;"></span>
                                                        Banned
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button 
                                                    type="button" 
                                                    class="btn btn-sm <?= $cust['ban_status'] === 'banned' ? 'btn-success' : 'btn-danger' ?>"
                                                    onclick="confirmBan(<?= $cust['id'] ?>, '<?= $cust['ban_status'] ?>', '<?= htmlspecialchars(addslashes($cust['fullname'])) ?>')"
                                                >
                                                    <i class="fa-solid <?= $cust['ban_status'] === 'banned' ? 'fa-user-check' : 'fa-user-slash' ?>"></i>
                                                    <?= $cust['ban_status'] === 'banned' ? 'Unban' : 'Ban' ?>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5">
                                                <div class="empty-state">
                                                    <i class="fa-solid fa-users-slash"></i>
                                                    <p>No customers found in the system</p>
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

    <!-- CONFIRM MODAL -->
    <div class="modal-overlay" id="confirmModal">
        <div class="modal">
            <div class="modal-title" id="modalTitle">Confirm Action</div>
            <div class="modal-text" id="modalText">Are you sure you want to proceed?</div>
            <div class="modal-actions">
                <button class="btn btn-ghost" onclick="closeModal()">Cancel</button>
                <form method="POST" id="banForm" style="display: inline;">
                    <input type="hidden" name="ban_id" id="banId">
                    <input type="hidden" name="current_status" id="currentStatus">
                    <button type="submit" class="btn btn-danger" id="confirmBtn">Confirm</button>
                </form>
            </div>
        </div>
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
        function filterCustomers() {
            const query = document.getElementById('customerSearch').value.toLowerCase();
            const rows = document.querySelectorAll('#customersTable tbody tr[data-search]');
            
            rows.forEach(row => {
                const searchData = row.getAttribute('data-search');
                row.style.display = searchData.includes(query) ? '' : 'none';
            });
        }

        // ===== BAN CONFIRM MODAL =====
        function confirmBan(id, status, name) {
            const modal = document.getElementById('confirmModal');
            const title = document.getElementById('modalTitle');
            const text = document.getElementById('modalText');
            const banId = document.getElementById('banId');
            const currentStatus = document.getElementById('currentStatus');
            const confirmBtn = document.getElementById('confirmBtn');

            if (status === 'banned') {
                title.textContent = 'Unban Customer';
                text.textContent = `Are you sure you want to unban ${name}? They will regain full access to the platform.`;
                confirmBtn.className = 'btn btn-success';
                confirmBtn.innerHTML = '<i class="fa-solid fa-user-check"></i> Unban';
            } else {
                title.textContent = 'Ban Customer';
                text.textContent = `Are you sure you want to ban ${name}? They will lose access to the platform immediately.`;
                confirmBtn.className = 'btn btn-danger';
                confirmBtn.innerHTML = '<i class="fa-solid fa-user-slash"></i> Ban';
            }

            banId.value = id;
            currentStatus.value = status;
            modal.classList.add('active');
        }

        function closeModal() {
            document.getElementById('confirmModal').classList.remove('active');
        }

        // Close modal on overlay click
        document.getElementById('confirmModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>