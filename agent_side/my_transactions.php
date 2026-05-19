<?php
// ─── YOUR ORIGINAL BACKEND CODE ───
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

// Fetch agent info
$stmt = $conn->prepare("SELECT username, gmail FROM agents WHERE id = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$stmt->bind_result($username, $agent_email);
if ($stmt->fetch()) {
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $agent_email;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Transactions — RealEstate</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ═══════════════════════════════════════════════════════════
   MY TRANSACTIONS — DARK LUXURY THEME
   Gold accent · Clean hierarchy · Smooth interactions
   ═══════════════════════════════════════════════════════════ */

:root {
    /* ── Colors ── */
    --bg:              #0a0a0a;
    --bg-elevated:     #111111;
    --bg-card:         #161616;
    --bg-hover:        #1c1c1c;
    --bg-input:        #1e1e1e;
    
    --gold:            #c9a84c;
    --gold-light:      #e0c878;
    --gold-dim:        rgba(201,168,76,0.12);
    --gold-glow:       rgba(201,168,76,0.06);
    
    --text:            #f0ece3;
    --text-secondary:  #a39e96;
    --text-muted:      #6b6560;
    --text-dim:        #4a4540;
    
    --border:          #252525;
    --border-hover:    #333333;
    
    --success:         #5aab7a;
    --success-bg:      rgba(90,171,122,0.1);
    --warning:         #c97a2a;
    --warning-bg:      rgba(201,122,42,0.1);
    --error:           #e05252;
    --error-bg:        rgba(224,82,82,0.1);
    --info:            #4a90d9;
    --info-bg:         rgba(74,144,217,0.1);
    
    /* ── Layout ── */
    --sidebar-w:       260px;
    --topbar-h:        64px;
    --radius:          14px;
    --radius-sm:       10px;
    --radius-xs:       6px;
    
    /* ── Shadows ── */
    --shadow-sm:       0 2px 8px rgba(0,0,0,0.3);
    --shadow:          0 8px 32px rgba(0,0,0,0.4);
    --shadow-lg:       0 24px 64px rgba(0,0,0,0.5);
    
    /* ── Transitions ── */
    --transition:      all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

*, *::before, *::after { 
    box-sizing: border-box; 
    margin: 0; 
    padding: 0; 
}

html { scroll-behavior: smooth; }

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--bg);
    color: var(--text);
    font-size: 14px;
    line-height: 1.6;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

/* ───────────────────────────────────────────
   SCROLLBAR
   ─────────────────────────────────────────── */
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: var(--bg); }
::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
::-webkit-scrollbar-thumb:hover { background: var(--text-dim); }

/* ───────────────────────────────────────────
   SIDEBAR
   ─────────────────────────────────────────── */
.sidebar-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.7);
    backdrop-filter: blur(12px);
    z-index: 98;
    opacity: 0;
    transition: opacity 0.3s;
}
.sidebar-overlay.active { 
    display: block; 
    opacity: 1; 
}

.sidebar {
    width: var(--sidebar-w);
    background: var(--bg-elevated);
    border-right: 1px solid var(--border);
    position: fixed;
    top: 0; left: 0;
    height: 100vh;
    overflow-y: auto;
    z-index: 99;
    display: flex;
    flex-direction: column;
    transform: translateX(-100%);
    transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.sidebar.open { transform: translateX(0); }

.sidebar-brand {
    padding: 28px 24px 24px;
    border-bottom: 1px solid var(--border);
}
.brand-wordmark {
    font-family: 'Playfair Display', serif;
    font-size: 22px;
    font-weight: 700;
    color: var(--text);
    letter-spacing: -0.5px;
}
.brand-wordmark span { color: var(--gold); }
.brand-sub {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 3px;
    color: var(--text-dim);
    margin-top: 6px;
}

.sidebar-nav { padding: 20px 0; flex: 1; }
.nav-section {
    padding: 16px 24px 8px;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 2.5px;
    color: var(--text-dim);
    font-weight: 600;
}
.nav-item { margin: 2px 12px; }
.nav-item a {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 16px;
    color: var(--text-muted);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    border-radius: var(--radius-xs);
    transition: var(--transition);
    position: relative;
}
.nav-item a::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 0;
    background: var(--gold);
    border-radius: 0 3px 3px 0;
    transition: height 0.3s;
}
.nav-item a:hover {
    color: var(--text);
    background: var(--bg-hover);
}
.nav-item a:hover::before { height: 20px; }
.nav-item.active a {
    color: var(--gold);
    background: var(--gold-dim);
}
.nav-item.active a::before { height: 28px; }
.nav-icon { 
    width: 20px; 
    text-align: center; 
    font-size: 16px;
    opacity: 0.8;
}

.sidebar-footer {
    padding: 16px 20px;
    border-top: 1px solid var(--border);
    margin: 0 12px 12px;
    border-radius: var(--radius-xs);
    background: var(--bg-hover);
}
.sidebar-user {
    display: flex;
    align-items: center;
    gap: 12px;
}
.sidebar-avatar {
    width: 38px;
    height: 38px;
    background: var(--gold-dim);
    border: 1.5px solid rgba(201,168,76,0.25);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Playfair Display', serif;
    font-size: 15px;
    font-weight: 700;
    color: var(--gold);
    flex-shrink: 0;
}
.sidebar-user-info { min-width: 0; }
.sidebar-user-name {
    font-size: 13px;
    font-weight: 600;
    color: var(--text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.sidebar-user-role {
    font-size: 11px;
    color: var(--text-muted);
    margin-top: 2px;
}

/* ───────────────────────────────────────────
   DASHBOARD LAYOUT
   ─────────────────────────────────────────── */
.dashboard { display: flex; min-height: 100vh; }
.main-content {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    margin-left: 0;
    transition: margin-left 0.4s;
}

/* ───────────────────────────────────────────
   TOPBAR
   ─────────────────────────────────────────── */
.topbar {
    height: var(--topbar-h);
    background: rgba(17,17,17,0.85);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--border);
    padding: 0 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 50;
}

.topbar-left {
    display: flex;
    align-items: center;
    gap: 18px;
}
.hamburger {
    background: none;
    border: 1.5px solid var(--border);
    padding: 9px;
    border-radius: var(--radius-xs);
    cursor: pointer;
    display: flex;
    flex-direction: column;
    gap: 4.5px;
    transition: var(--transition);
}
.hamburger:hover { border-color: var(--gold); }
.hamburger span {
    display: block;
    width: 18px;
    height: 1.5px;
    background: var(--text-muted);
    border-radius: 2px;
    transition: all 0.3s;
}
.hamburger.active span:nth-child(1) { transform: translateY(6px) rotate(45deg); background: var(--gold); }
.hamburger.active span:nth-child(2) { opacity: 0; transform: scaleX(0); }
.hamburger.active span:nth-child(3) { transform: translateY(-6px) rotate(-45deg); background: var(--gold); }

.topbar-title {
    font-family: 'Playfair Display', serif;
    font-size: 20px;
    font-weight: 600;
    color: var(--text);
}

.topbar-right {
    display: flex;
    align-items: center;
    gap: 16px;
}
.topbar-time {
    font-size: 13px;
    color: var(--text-muted);
    font-variant-numeric: tabular-nums;
    display: none;
}
.notification-btn {
    position: relative;
    width: 40px;
    height: 40px;
    border-radius: var(--radius-xs);
    border: 1.5px solid var(--border);
    background: none;
    color: var(--text-muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    transition: var(--transition);
}
.notification-btn:hover {
    border-color: var(--gold);
    color: var(--gold);
    background: var(--gold-dim);
}
.notification-dot {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 8px;
    height: 8px;
    background: var(--error);
    border-radius: 50%;
    border: 2px solid var(--bg-elevated);
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* ───────────────────────────────────────────
   PAGE CONTENT
   ─────────────────────────────────────────── */
.page-content { 
    padding: 28px 24px; 
    flex: 1; 
}

/* ───────────────────────────────────────────
   WELCOME HERO
   ─────────────────────────────────────────── */
.welcome-hero {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 32px;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
}
.welcome-hero::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, transparent, var(--gold), transparent);
    opacity: 0.7;
}
.welcome-hero::after {
    content: '';
    position: absolute;
    top: -40%;
    right: -5%;
    width: 350px;
    height: 350px;
    background: radial-gradient(circle, var(--gold-glow) 0%, transparent 70%);
    pointer-events: none;
}
.welcome-hero h2 {
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    font-weight: 600;
    margin-bottom: 8px;
    position: relative;
    z-index: 1;
}
.welcome-hero p {
    color: var(--text-muted);
    font-size: 15px;
    position: relative;
    z-index: 1;
    max-width: 480px;
}
.welcome-meta {
    display: flex;
    gap: 24px;
    margin-top: 20px;
    font-size: 12px;
    color: var(--text-dim);
    position: relative;
    z-index: 1;
}
.welcome-meta span {
    display: flex;
    align-items: center;
    gap: 8px;
}
.welcome-meta i { 
    color: var(--gold); 
    font-size: 12px; 
}

/* ───────────────────────────────────────────
   STATS GRID
   ─────────────────────────────────────────── */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-bottom: 28px;
}
.stat-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 24px;
    position: relative;
    overflow: hidden;
    transition: var(--transition);
}
.stat-card:hover {
    border-color: var(--border-hover);
    transform: translateY(-3px);
    box-shadow: var(--shadow);
}
.stat-card::after {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 2px;
    background: linear-gradient(90deg, transparent, var(--gold), transparent);
    opacity: 0;
    transition: opacity 0.3s;
}
.stat-card:hover::after { opacity: 0.5; }
.stat-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: var(--text-muted);
    margin-bottom: 12px;
    font-weight: 500;
}
.stat-number {
    font-family: 'Playfair Display', serif;
    font-size: 34px;
    font-weight: 600;
    color: var(--text);
    line-height: 1;
}
.stat-icon {
    position: absolute;
    right: 20px;
    top: 20px;
    width: 44px;
    height: 44px;
    background: var(--gold-dim);
    border-radius: var(--radius-xs);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--gold);
    font-size: 18px;
    transition: var(--transition);
}
.stat-card:hover .stat-icon {
    transform: scale(1.1) rotate(-5deg);
    background: rgba(201,168,76,0.2);
}

/* ───────────────────────────────────────────
   SECTION HEADERS
   ─────────────────────────────────────────── */
.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 18px;
    flex-wrap: wrap;
    gap: 12px;
}
.section-title {
    font-family: 'Playfair Display', serif;
    font-size: 18px;
    font-weight: 600;
}
.section-action {
    font-size: 13px;
    color: var(--gold);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 500;
    transition: var(--transition);
}
.section-action:hover { 
    color: var(--gold-light); 
    gap: 10px; 
}

/* ───────────────────────────────────────────
   CARDS
   ─────────────────────────────────────────── */
.card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    overflow: hidden;
    margin-bottom: 24px;
    transition: var(--transition);
}
.card:hover {
    border-color: var(--border-hover);
}
.card-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
}
.card-title {
    font-family: 'Playfair Display', serif;
    font-size: 17px;
    font-weight: 600;
}
.card-body { padding: 0; }

/* ───────────────────────────────────────────
   TABLES
   ─────────────────────────────────────────── */
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 13.5px;
    min-width: 600px;
}
thead th {
    padding: 14px 20px;
    text-align: left;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: var(--text-dim);
    border-bottom: 1px solid var(--border);
    background: var(--bg-elevated);
    white-space: nowrap;
    position: sticky;
    top: 0;
}
tbody td {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
    color: var(--text-secondary);
    vertical-align: middle;
    transition: var(--transition);
}
tbody tr {
    transition: background 0.2s;
}
tbody tr:hover {
    background: var(--bg-hover);
}
tbody tr:hover td {
    color: var(--text);
}
tbody tr:last-child td { border-bottom: none; }

/* ───────────────────────────────────────────
   BADGES
   ─────────────────────────────────────────── */
.badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}
.badge-gold {
    background: var(--gold-dim);
    color: var(--gold);
    border: 1px solid rgba(201,168,76,0.25);
}
.badge-success {
    background: var(--success-bg);
    color: var(--success);
    border: 1px solid rgba(90,171,122,0.2);
}
.badge-warning {
    background: var(--warning-bg);
    color: var(--warning);
    border: 1px solid rgba(201,122,42,0.2);
}
.badge-muted {
    background: rgba(255,255,255,0.03);
    color: var(--text-muted);
    border: 1px solid var(--border);
}

/* ───────────────────────────────────────────
   EMPTY STATE
   ─────────────────────────────────────────── */
.empty-state {
    text-align: center;
    padding: 56px 24px;
    color: var(--text-dim);
}
.empty-state i {
    font-size: 52px;
    margin-bottom: 16px;
    opacity: 0.25;
    display: block;
    color: var(--text-muted);
}
.empty-state p {
    font-size: 15px;
    color: var(--text-muted);
    margin-bottom: 6px;
}
.empty-state span {
    font-size: 13px;
    color: var(--text-dim);
}

/* ───────────────────────────────────────────
   RESPONSIVE
   ─────────────────────────────────────────── */
@media (min-width: 640px) {
    .stats-grid { grid-template-columns: repeat(4, 1fr); }
}

@media (min-width: 1024px) {
    .sidebar { transform: translateX(0) !important; }
    .sidebar-overlay { display: none !important; }
    .main-content { margin-left: var(--sidebar-w); }
    .hamburger { display: none; }
    .topbar-time { display: block; }
    .page-content { padding: 32px; }
}

@media (max-width: 480px) {
    .page-content { padding: 16px; }
    .welcome-hero { padding: 24px 20px; }
    .welcome-hero h2 { font-size: 22px; }
    .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .stat-card { padding: 18px; }
    .stat-number { font-size: 26px; }
    .stat-icon { width: 36px; height: 36px; font-size: 14px; }
    .card-header { padding: 16px 18px; }
    table { font-size: 12px; min-width: 500px; }
    thead th, tbody td { padding: 10px 14px; }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
</head>

<body>
<div class="dashboard">

<!-- ─── SIDEBAR ─── -->
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-wordmark">Real<span>Estate</span></div>
        <div class="brand-sub">Agent Portal</div>
    </div>
    
    <nav class="sidebar-nav">
        <div class="nav-section">Main</div>
        <div class="nav-item">
            <a href="agent_dashboard.php">
                <span class="nav-icon"><i class="fas fa-chart-line"></i></span>
                Dashboard
            </a>
        </div>
       
        <div class="nav-item active">
            <a href="agent_transactions.php">
                <span class="nav-icon"><i class="fas fa-receipt"></i></span>
                My Transactions
            </a>
        </div>
       
        
        <div class="nav-section">Account</div>
        <div class="nav-item">
            <a href="agent_account.php">
                <span class="nav-icon"><i class="fas fa-user"></i></span>
                Profile
            </a>
        </div>
        <div class="nav-item">
            <a href="logout.php">
                <span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span>
                Logout
            </a>
        </div>
    </nav>
    
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar">
                <?= strtoupper(substr($_SESSION['username'],0,1)) ?>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                <div class="sidebar-user-role">Real Estate Agent</div>
            </div>
        </div>
    </div>
</aside>

<div class="main-content">

    <!-- ─── TOPBAR ─── -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="hamburger" onclick="toggleSidebar()" id="hamburger">
                <span></span><span></span><span></span>
            </button>
            <div class="topbar-title">My Transactions</div>
        </div>
        <div class="topbar-right">
            <div class="topbar-time" id="clock">--:--</div>
            <button class="notification-btn">
                <i class="fas fa-bell"></i>
            </button>
        </div>
    </header>

    <!-- ─── CONTENT ─── -->
    <div class="page-content">

        <!-- Welcome Hero -->
        <div class="welcome-hero">
            <h2>Completed Deals</h2>
            <p>All reservations you have successfully closed are shown below.</p>
            <div class="welcome-meta">
                <span><i class="fas fa-receipt"></i> Transaction History</span>
                <span><i class="fas fa-shield-halved"></i> Secure Session</span>
            </div>
        </div>

        <!-- Stats Cards -->
        <?php
        // Fetch stats for cards
        $statsStmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_done,
                COUNT(DISTINCT MONTH(created_at)) as active_months,
                COUNT(DISTINCT property) as unique_properties,
                AVG(DATEDIFF(CURDATE(), created_at)) as avg_days
            FROM reservations 
            WHERE agent_id = ? AND status = 'Done'
        ");
        $statsStmt->bind_param("i", $_SESSION['id']);
        $statsStmt->execute();
        $stats = $statsStmt->get_result()->fetch_assoc();
        $statsStmt->close();

        // This month's deals
        $monthStmt = $conn->prepare("
            SELECT COUNT(*) as this_month 
            FROM reservations 
            WHERE agent_id = ? AND status = 'Done' 
            AND MONTH(created_at) = MONTH(CURDATE()) 
            AND YEAR(created_at) = YEAR(CURDATE())
        ");
        $monthStmt->bind_param("i", $_SESSION['id']);
        $monthStmt->execute();
        $thisMonth = $monthStmt->get_result()->fetch_assoc()['this_month'] ?? 0;
        $monthStmt->close();
        ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Closed</div>
                <div class="stat-number"><?= (int)($stats['total_done'] ?? 0) ?></div>
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">This Month</div>
                <div class="stat-number"><?= (int)$thisMonth ?></div>
                <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Properties Sold</div>
                <div class="stat-number"><?= (int)($stats['unique_properties'] ?? 0) ?></div>
                <div class="stat-icon"><i class="fas fa-building"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Active Months</div>
                <div class="stat-number"><?= (int)($stats['active_months'] ?? 0) ?></div>
                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">My Done Deals</div>
                <div style="font-size:12px;color:var(--text-muted);">
                    <?= (int)($stats['total_done'] ?? 0) ?> total records
                </div>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Contact</th>
                            <th>Property</th>
                            <th>Schedule</th>
                            <th>Type</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->prepare("
                            SELECT id, fullname, email, phone, property, date, time, meeting_type, status, created_at
                            FROM reservations
                            WHERE agent_id = ? AND status = 'Done'
                            ORDER BY created_at DESC
                        ");
                        $stmt->bind_param("i", $_SESSION['id']);
                        $stmt->execute();
                        $done_reservations = $stmt->get_result();

                        if($done_reservations->num_rows > 0):
                            while($row = $done_reservations->fetch_assoc()):
                        ?>
                        <tr>
                            <td>
                                <div style="font-weight:500;color:var(--text);"><?= htmlspecialchars($row['fullname']) ?></div>
                                <div style="font-size:11px;color:var(--text-muted);margin-top:2px;"><?= htmlspecialchars($row['email']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($row['phone']) ?></td>
                            <td><?= htmlspecialchars($row['property']) ?></td>
                            <td>
                                <div style="font-weight:500;"><?= htmlspecialchars($row['date']) ?></div>
                                <div style="font-size:11px;color:var(--text-muted);"><?= htmlspecialchars($row['time']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($row['meeting_type'] ?? '-') ?></td>
                            <td>
                                <span class="badge badge-success">
                                    <i class="fas fa-check" style="font-size:9px;"></i> Done
                                </span>
                            </td>
                        </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="fas fa-clipboard-check"></i>
                                    <p>No completed transactions yet</p>
                                    <span>Confirmed deals will appear here once finalized</span>
                                </div>
                            </td>
                        </tr>
                        <?php
                        endif;
                        $stmt->close();
                        $conn->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

</div>

<script>
// ─── SIDEBAR ───
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.querySelector('.sidebar-overlay').classList.toggle('active');
    document.getElementById('hamburger').classList.toggle('active');
}

// ─── CLOCK ───
function updateClock() {
    const now = new Date();
    document.getElementById('clock').textContent = now.toLocaleTimeString('en-US', { 
        hour: '2-digit', minute: '2-digit' 
    });
}
updateClock();
setInterval(updateClock, 1000);
</script>

</body>
</html>