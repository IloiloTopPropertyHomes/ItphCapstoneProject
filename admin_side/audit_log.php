<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://www.gstatic.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self';");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: no-referrer-when-downgrade");

session_start();
require_once __DIR__ . '/../backends/config.php';

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
if ($stmt->fetch()) {
    $_SESSION['username'] = $username;
    $_SESSION['email']    = $admin_email;
}
$stmt->close();

// ── Filters ──────────────────────────────────────────────
$tab        = $_GET['tab']        ?? 'auth';
$role_filter= $_GET['role']       ?? '';
$status_filter = $_GET['status']  ?? '';
$search     = trim($_GET['search'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$per_page   = 20;
$offset     = ($page - 1) * $per_page;

// ── Mark stale sessions offline ──────────────────────────
$conn->query("UPDATE auth_logs SET session_status='offline' WHERE session_status='online' AND activity_time < NOW() - INTERVAL 15 MINUTE");

// ── Build queries ─────────────────────────────────────────
if ($tab === 'auth') {
    $where  = "WHERE 1=1";
    $params = [];
    $types  = "";

    if ($role_filter !== '') {
        $where   .= " AND role = ?";
        $params[] = $role_filter;
        $types   .= "s";
    }
    if ($status_filter !== '') {
        $where   .= " AND login_status = ?";
        $params[] = $status_filter;
        $types   .= "s";
    }
    if ($search !== '') {
        $like     = "%$search%";
        $where   .= " AND (fullname LIKE ? OR email LIKE ? OR ip_address LIKE ?)";
        $params[] = $like; $params[] = $like; $params[] = $like;
        $types   .= "sss";
    }

    // Count
    $count_sql = "SELECT COUNT(*) FROM auth_logs $where";
    $cs = $conn->prepare($count_sql);
    if ($types) $cs->bind_param($types, ...$params);
    $cs->execute();
    $cs->bind_result($total_rows);
    $cs->fetch();
    $cs->close();

    $total_pages = max(1, ceil($total_rows / $per_page));

    // Data
    $data_sql = "SELECT id, role, fullname, email, login_status, login_method, session_status, ip_address, activity_time
                 FROM auth_logs $where ORDER BY id DESC LIMIT ? OFFSET ?";
    $ds = $conn->prepare($data_sql);
    $params[] = $per_page; $types .= "i";
    $params[] = $offset;   $types .= "i";
    $ds->bind_param($types, ...$params);
    $ds->execute();
    $rows = $ds->get_result();

    // Summary counts
    $summary = $conn->query("SELECT
        SUM(login_status='success') AS success_total,
        SUM(login_status='failed')  AS failed_total,
        SUM(session_status='online') AS online_total,
        SUM(role='admin')   AS admin_total,
        SUM(role='agent')   AS agent_total,
        SUM(role='customer') AS customer_total
        FROM auth_logs")->fetch_assoc();

} else {
    $where  = "WHERE 1=1";
    $params = [];
    $types  = "";

    if ($role_filter !== '') {
        $where   .= " AND tl.role = ?";
        $params[] = $role_filter;
        $types   .= "s";
    }
    if ($search !== '') {
        $like     = "%$search%";
        $where   .= " AND (tl.action LIKE ? OR tl.details LIKE ? OR COALESCE(au.username, ag.username) LIKE ?)";
        $params[] = $like; $params[] = $like; $params[] = $like;
        $types   .= "sss";
    }

    $count_sql = "SELECT COUNT(*) FROM transaction_logs tl
                  LEFT JOIN admin_users au ON tl.role='admin' AND tl.user_id=au.id
                  LEFT JOIN agents ag     ON tl.role='agent' AND tl.user_id=ag.id
                  $where";
    $cs = $conn->prepare($count_sql);
    if ($types) $cs->bind_param($types, ...$params);
    $cs->execute();
    $cs->bind_result($total_rows);
    $cs->fetch();
    $cs->close();

    $total_pages = max(1, ceil($total_rows / $per_page));

    $data_sql = "SELECT tl.id, tl.role, tl.action, tl.details, tl.created_at,
                        COALESCE(au.username, ag.username) AS actor_name
                 FROM transaction_logs tl
                 LEFT JOIN admin_users au ON tl.role='admin' AND tl.user_id=au.id
                 LEFT JOIN agents ag     ON tl.role='agent' AND tl.user_id=ag.id
                 $where ORDER BY tl.id DESC LIMIT ? OFFSET ?";
    $ds = $conn->prepare($data_sql);
    $params[] = $per_page; $types .= "i";
    $params[] = $offset;   $types .= "i";
    $ds->bind_param($types, ...$params);
    $ds->execute();
    $rows = $ds->get_result();

    $summary = $conn->query("SELECT COUNT(*) AS total, SUM(role='admin') AS admin_total, SUM(role='agent') AS agent_total FROM transaction_logs")->fetch_assoc();
}

// ── Helper: build pagination URL ─────────────────────────
function page_url($p) {
    $q = $_GET;
    $q['page'] = $p;
    return '?' . http_build_query($q);
}

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
    <title>Audit Log — ITPH Admin</title>
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

        /* ===== HERO ===== */
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
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
            margin-bottom: 12px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .stat-icon.green { background: rgba(34, 197, 94, 0.15); color: #4ade80; }
        .stat-icon.red { background: rgba(239, 68, 68, 0.15); color: #f87171; }
        .stat-icon.blue { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
        .stat-icon.amber { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
        .stat-icon.purple { background: rgba(168, 85, 247, 0.15); color: #c084fc; }
        .stat-icon.cyan { background: rgba(6, 182, 212, 0.15); color: #22d3ee; }

        .stat-value { font-size: 28px; font-weight: 700; margin-bottom: 4px; }

        .stat-label {
            font-size: 12px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ===== TABS ===== */
        .tab-bar {
            display: flex;
            gap: 4px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }

        .tab-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
            background: none;
            border-top: none;
            border-left: none;
            border-right: none;
            font-family: inherit;
            text-decoration: none;
        }

        .tab-btn:hover { color: var(--text-primary); }

        .tab-btn.active {
            color: var(--accent);
            border-bottom-color: var(--accent);
        }

        /* ===== FILTERS ===== */
        .filter-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px 24px;
            margin-bottom: 20px;
        }

        .filter-form {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1;
            min-width: 160px;
        }

        .filter-group label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px 14px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: all 0.2s;
        }

        .filter-group input:focus,
        .filter-group select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-light);
        }

        .filter-group input::placeholder { color: var(--text-muted); }

        .filter-group select option { background: var(--bg-card); }

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

        .btn-primary {
            background: var(--accent);
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-ghost {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border);
            text-decoration: none;
        }

        .btn-ghost:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
        }

        /* ===== TABLE CARD ===== */
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

        .table-responsive { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            min-width: 700px;
        }

        thead th {
            padding: 12px 16px;
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
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            color: var(--text-secondary);
            vertical-align: middle;
        }

        tbody tr:hover td {
            background: rgba(255, 255, 255, 0.02);
            color: var(--text-primary);
        }

        tbody tr:last-child td { border-bottom: none; }

        .td-name { font-weight: 600; color: var(--text-primary); }

        .td-email {
            font-size: 12px;
            color: var(--text-muted);
        }

        .td-time {
            white-space: nowrap;
            font-family: 'SF Mono', monospace;
            font-size: 12px;
        }

        .td-ip {
            font-family: 'SF Mono', monospace;
            font-size: 12px;
            color: var(--text-muted);
            background: var(--bg);
            padding: 4px 8px;
            border-radius: 4px;
        }

        /* ===== BADGES ===== */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 10px;
            border-radius: 100px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-success { background: rgba(34, 197, 94, 0.15); color: #4ade80; }
        .badge-danger { background: rgba(239, 68, 68, 0.15); color: #f87171; }
        .badge-online { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
        .badge-offline { background: rgba(100, 116, 139, 0.15); color: #94a3b8; }
        .badge-admin { background: rgba(168, 85, 247, 0.15); color: #c084fc; }
        .badge-agent { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
        .badge-customer { background: rgba(6, 182, 212, 0.15); color: #22d3ee; }

        .badge-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }

        /* ===== PAGINATION ===== */
        .pagination {
            display: flex;
            gap: 6px;
            justify-content: center;
            padding: 20px;
        }

        .page-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 36px;
            height: 36px;
            padding: 0 12px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
        }

        .page-btn:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
            border-color: var(--accent);
        }

        .page-btn.active {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }

        .page-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
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
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .filter-form { flex-direction: column; }
            .filter-group { width: 100%; }
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
                        <div class="page-title">Audit Log</div>
                        <div class="breadcrumb">Home / Security / Audit Log</div>
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
                    <div class="hero-eyebrow">Security & Compliance</div>
                    <div class="hero-title">Audit Log</div>
                    <div class="hero-desc">Full history of logins, session activity, and admin/agent actions across the platform.</div>
                </div>

                <!-- SUMMARY STATS -->
                <?php if ($tab === 'auth'): ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon green"><i class="fa-solid fa-check"></i></div>
                        </div>
                        <div class="stat-value"><?= number_format($summary['success_total'] ?? 0) ?></div>
                        <div class="stat-label">Successful Logins</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon red"><i class="fa-solid fa-xmark"></i></div>
                        </div>
                        <div class="stat-value"><?= number_format($summary['failed_total'] ?? 0) ?></div>
                        <div class="stat-label">Failed Attempts</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon blue"><i class="fa-solid fa-signal"></i></div>
                        </div>
                        <div class="stat-value"><?= number_format($summary['online_total'] ?? 0) ?></div>
                        <div class="stat-label">Currently Online</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon purple"><i class="fa-solid fa-user-shield"></i></div>
                        </div>
                        <div class="stat-value"><?= number_format($summary['admin_total'] ?? 0) ?></div>
                        <div class="stat-label">Admin Sessions</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon amber"><i class="fa-solid fa-user-tie"></i></div>
                        </div>
                        <div class="stat-value"><?= number_format($summary['agent_total'] ?? 0) ?></div>
                        <div class="stat-label">Agent Sessions</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon cyan"><i class="fa-solid fa-users"></i></div>
                        </div>
                        <div class="stat-value"><?= number_format($summary['customer_total'] ?? 0) ?></div>
                        <div class="stat-label">Customer Sessions</div>
                    </div>
                </div>
                <?php else: ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon blue"><i class="fa-solid fa-list-check"></i></div>
                        </div>
                        <div class="stat-value"><?= number_format($summary['total'] ?? 0) ?></div>
                        <div class="stat-label">Total Actions</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon purple"><i class="fa-solid fa-user-shield"></i></div>
                        </div>
                        <div class="stat-value"><?= number_format($summary['admin_total'] ?? 0) ?></div>
                        <div class="stat-label">By Admin</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon amber"><i class="fa-solid fa-user-tie"></i></div>
                        </div>
                        <div class="stat-value"><?= number_format($summary['agent_total'] ?? 0) ?></div>
                        <div class="stat-label">By Agent</div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- TABS -->
                <div class="tab-bar">
                    <a href="?tab=auth" class="tab-btn <?= $tab === 'auth' ? 'active' : '' ?>">
                        <i class="fa-solid fa-user-lock"></i> Login Activity
                    </a>
                    <a href="?tab=transaction" class="tab-btn <?= $tab === 'transaction' ? 'active' : '' ?>">
                        <i class="fa-solid fa-list-check"></i> Admin / Agent Actions
                    </a>
                </div>

                <!-- FILTERS -->
                <div class="filter-card">
                    <form method="GET" class="filter-form">
                        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                        
                        <div class="filter-group">
                            <label>Search</label>
                            <input type="text" name="search" placeholder="Name, email, IP, action..." value="<?= htmlspecialchars($search) ?>">
                        </div>

                        <div class="filter-group">
                            <label>Role</label>
                            <select name="role">
                                <option value="">All Roles</option>
                                <?php if ($tab === 'auth'): ?>
                                <option value="admin"    <?= $role_filter === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="agent"    <?= $role_filter === 'agent' ? 'selected' : '' ?>>Agent</option>
                                <option value="customer" <?= $role_filter === 'customer' ? 'selected' : '' ?>>Customer</option>
                                <?php else: ?>
                                <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="agent" <?= $role_filter === 'agent' ? 'selected' : '' ?>>Agent</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <?php if ($tab === 'auth'): ?>
                        <div class="filter-group">
                            <label>Login Status</label>
                            <select name="status">
                                <option value="">All Statuses</option>
                                <option value="success" <?= $status_filter === 'success' ? 'selected' : '' ?>>Success</option>
                                <option value="failed"  <?= $status_filter === 'failed' ? 'selected' : '' ?>>Failed</option>
                            </select>
                        </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-filter"></i> Apply Filters
                        </button>
                        <a href="?tab=<?= $tab ?>" class="btn btn-ghost">
                            <i class="fa-solid fa-rotate-left"></i> Reset
                        </a>
                    </form>
                </div>

                <!-- TABLE -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <?= $tab === 'auth' ? 'Login Activity' : 'Admin / Agent Actions' ?>
                        </div>
                        <div class="card-meta">
                            Page <?= $page ?> of <?= $total_pages ?> &middot; <?= number_format($total_rows) ?> records
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <?php if ($tab === 'auth'): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Role</th>
                                        <th>User</th>
                                        <th>Status</th>
                                        <th>Method</th>
                                        <th>Session</th>
                                        <th>IP Address</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($rows && $rows->num_rows > 0): ?>
                                        <?php while ($r = $rows->fetch_assoc()): ?>
                                        <tr>
                                            <td class="td-time">#<?= $r['id'] ?></td>
                                            <td>
                                                <?php if ($r['role'] === 'admin'): ?>
                                                    <span class="badge badge-admin"><i class="fa-solid fa-shield-halved"></i> Admin</span>
                                                <?php elseif ($r['role'] === 'agent'): ?>
                                                    <span class="badge badge-agent"><i class="fa-solid fa-user-tie"></i> Agent</span>
                                                <?php else: ?>
                                                    <span class="badge badge-customer"><i class="fa-solid fa-user"></i> Customer</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="td-name"><?= htmlspecialchars($r['fullname']) ?></div>
                                                <div class="td-email"><?= htmlspecialchars($r['email']) ?></div>
                                            </td>
                                            <td>
                                                <?php if ($r['login_status'] === 'success'): ?>
                                                    <span class="badge badge-success"><span class="badge-dot" style="background: #4ade80;"></span> Success</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger"><span class="badge-dot" style="background: #f87171;"></span> Failed</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars(ucfirst($r['login_method'])) ?></td>
                                            <td>
                                                <?php if ($r['session_status'] === 'online'): ?>
                                                    <span class="badge badge-online"><span class="badge-dot" style="background: #60a5fa; animation: pulse 2s infinite;"></span> Online</span>
                                                <?php else: ?>
                                                    <span class="badge badge-offline">Offline</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="td-ip"><?= htmlspecialchars($r['ip_address'] ?? '-') ?></span></td>
                                            <td class="td-time"><?= htmlspecialchars($r['activity_time']) ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8">
                                                <div class="empty-state">
                                                    <i class="fa-solid fa-inbox"></i>
                                                    <p>No login records found matching your filters</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Role</th>
                                        <th>Actor</th>
                                        <th>Action</th>
                                        <th>Details</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($rows && $rows->num_rows > 0): ?>
                                        <?php while ($r = $rows->fetch_assoc()): ?>
                                        <tr>
                                            <td class="td-time">#<?= $r['id'] ?></td>
                                            <td>
                                                <?php if ($r['role'] === 'admin'): ?>
                                                    <span class="badge badge-admin"><i class="fa-solid fa-shield-halved"></i> Admin</span>
                                                <?php else: ?>
                                                    <span class="badge badge-agent"><i class="fa-solid fa-user-tie"></i> Agent</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="td-name"><?= htmlspecialchars($r['actor_name'] ?? '—') ?></td>
                                            <td style="color: var(--text-primary); font-weight: 500;"><?= htmlspecialchars($r['action']) ?></td>
                                            <td style="max-width: 400px; word-break: break-word; font-size: 12px;"><?= htmlspecialchars($r['details'] ?? '—') ?></td>
                                            <td class="td-time"><?= htmlspecialchars($r['created_at']) ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6">
                                                <div class="empty-state">
                                                    <i class="fa-solid fa-inbox"></i>
                                                    <p>No transaction records found matching your filters</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <?php endif; ?>
                        </div>

                        <!-- PAGINATION -->
                        <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a class="page-btn" href="<?= page_url($page - 1) ?>"><i class="fa-solid fa-chevron-left"></i></a>
                            <?php else: ?>
                                <span class="page-btn disabled"><i class="fa-solid fa-chevron-left"></i></span>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $page - 2);
                            $end   = min($total_pages, $page + 2);
                            for ($p = $start; $p <= $end; $p++):
                            ?>
                                <a class="page-btn <?= $p === $page ? 'active' : '' ?>" href="<?= page_url($p) ?>"><?= $p ?></a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a class="page-btn" href="<?= page_url($page + 1) ?>"><i class="fa-solid fa-chevron-right"></i></a>
                            <?php else: ?>
                                <span class="page-btn disabled"><i class="fa-solid fa-chevron-right"></i></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
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
    </script>
</body>
</html>