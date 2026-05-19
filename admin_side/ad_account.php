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

$success = "";
$error = "";

// FETCH ADMIN DATA
$stmt = $conn->prepare("SELECT username, gmail FROM admin_users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$stmt->bind_result($username, $email);
$stmt->fetch();
$stmt->close();

// UPDATE LOGIC
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['username']);
    $new_email = trim($_POST['email']);
    $new_password = $_POST['password'];

    if (empty($new_username) || empty($new_email)) {
        $error = "Username and email are required.";
    } else {
        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admin_users SET username=?, gmail=?, password=? WHERE id=?");
            $stmt->bind_param("sssi", $new_username, $new_email, $hashed_password, $_SESSION['id']);
        } else {
            $stmt = $conn->prepare("UPDATE admin_users SET username=?, gmail=? WHERE id=?");
            $stmt->bind_param("ssi", $new_username, $new_email, $_SESSION['id']);
        }

        if ($stmt->execute()) {
            $success = "Account updated successfully!";
            $_SESSION['username'] = $new_username;
            $_SESSION['email'] = $new_email;
            $username = $new_username;
            $email = $new_email;
        } else {
            $error = "Update failed.";
        }
        $stmt->close();
    }
}

$conn->close();

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
    <title>My Account — ITPH Admin</title>
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
        .content { padding: 24px; max-width: 800px; }

        /* ===== PROFILE HEADER ===== */
        .profile-header {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 32px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 24px;
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent), #60a5fa);
        }

        .profile-avatar-large {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--accent), #60a5fa);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 700;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 8px 24px rgba(59, 130, 246, 0.3);
        }

        .profile-info h2 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .profile-info p {
            color: var(--text-secondary);
            font-size: 14px;
        }

        .profile-role {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
            padding: 4px 12px;
            background: var(--accent-light);
            color: var(--accent);
            border-radius: 100px;
            font-size: 12px;
            font-weight: 600;
        }

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
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
        }

        .card-subtitle {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .card-body { padding: 24px; }

        /* ===== ALERTS ===== */
        .alert {
            padding: 14px 18px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            color: var(--success);
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-10px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* ===== FORM ===== */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-size: 14px;
            font-family: inherit;
            transition: all 0.2s;
            outline: none;
        }

        .form-group input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-light);
        }

        .form-group input::placeholder {
            color: var(--text-muted);
        }

        .form-hint {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 6px;
        }

        /* ===== PASSWORD FIELD ===== */
        .password-wrapper { position: relative; }

        .password-wrapper input { padding-right: 44px; }

        .pw-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px;
            font-size: 14px;
            transition: all 0.2s;
        }

        .pw-toggle:hover { color: var(--text-primary); }

        /* ===== BUTTONS ===== */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: var(--radius-sm);
            font-size: 14px;
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
        }

        .btn-ghost:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
        }

        /* ===== DIVIDER ===== */
        .divider {
            display: flex;
            align-items: center;
            gap: 16px;
            margin: 24px 0;
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        /* ===== SECURITY SECTION ===== */
        .security-tips {
            display: grid;
            gap: 12px;
        }

        .security-tip {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
        }

        .security-tip i {
            color: var(--accent);
            font-size: 16px;
            margin-top: 2px;
        }

        .security-tip div {
            font-size: 13px;
        }

        .security-tip strong {
            display: block;
            color: var(--text-primary);
            margin-bottom: 2px;
        }

        .security-tip span {
            color: var(--text-secondary);
        }

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
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
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
                        <div class="page-title">My Account</div>
                        <div class="breadcrumb">Home / Account Settings</div>
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

                <!-- PROFILE HEADER -->
                <div class="profile-header">
                    <div class="profile-avatar-large">
                        <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                    </div>
                    <div class="profile-info">
                        <h2><?= htmlspecialchars($username) ?></h2>
                        <p><?= htmlspecialchars($email) ?></p>
                        <span class="profile-role">
                            <i class="fa-solid fa-shield-halved"></i>
                            Administrator
                        </span>
                    </div>
                </div>

                <!-- ACCOUNT FORM -->
                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">Account Settings</div>
                            <div class="card-subtitle">Update your personal information</div>
                        </div>
                    </div>
                    <div class="card-body">

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fa-solid fa-check-circle"></i>
                                <?= htmlspecialchars($success) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-error">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" id="accountForm">

                            <div class="form-group">
                                <label for="username">Username</label>
                                <input 
                                    type="text" 
                                    id="username"
                                    name="username" 
                                    value="<?= htmlspecialchars($username) ?>" 
                                    required
                                    autocomplete="username"
                                >
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input 
                                    type="email" 
                                    id="email"
                                    name="email" 
                                    value="<?= htmlspecialchars($email) ?>" 
                                    required
                                    autocomplete="email"
                                >
                            </div>

                            <div class="divider">Security</div>

                            <div class="form-group">
                                <label for="password">New Password</label>
                                <div class="password-wrapper">
                                    <input 
                                        type="password" 
                                        id="password"
                                        name="password"
                                        placeholder="••••••••"
                                        autocomplete="new-password"
                                    >
                                    <button type="button" class="pw-toggle" onclick="togglePassword()">
                                        <i class="fa-solid fa-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                                <p class="form-hint">Leave blank to keep your current password unchanged</p>
                            </div>

                            <div style="display: flex; gap: 12px; margin-top: 24px;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-solid fa-floppy-disk"></i>
                                    Save Changes
                                </button>
                                <button type="reset" class="btn btn-ghost">
                                    <i class="fa-solid fa-rotate-left"></i>
                                    Reset
                                </button>
                            </div>

                        </form>

                    </div>
                </div>

                <!-- SECURITY TIPS -->
                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">Security Tips</div>
                            <div class="card-subtitle">Keep your account safe</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="security-tips">
                            <div class="security-tip">
                                <i class="fa-solid fa-lock"></i>
                                <div>
                                    <strong>Strong Password</strong>
                                    <span>Use at least 8 characters with mixed case, numbers, and symbols</span>
                                </div>
                            </div>
                            <div class="security-tip">
                                <i class="fa-solid fa-key"></i>
                                <div>
                                    <strong>Regular Updates</strong>
                                    <span>Change your password every 3-6 months for better security</span>
                                </div>
                            </div>
                            <div class="security-tip">
                                <i class="fa-solid fa-user-shield"></i>
                                <div>
                                    <strong>Unique Credentials</strong>
                                    <span>Don't reuse this password on other websites or services</span>
                                </div>
                            </div>
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

        // ===== PASSWORD TOGGLE =====
        function togglePassword() {
            const pw = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            
            if (pw.type === 'password') {
                pw.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                pw.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // ===== FORM VALIDATION =====
        document.getElementById('accountForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            
            if (username.length < 3) {
                e.preventDefault();
                alert('Username must be at least 3 characters long');
                return;
            }
            
            if (!email.includes('@') || !email.includes('.')) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return;
            }
        });
    </script>
</body>
</html>