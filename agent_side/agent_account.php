<?php
// ─── YOUR ORIGINAL BACKEND CODE ───
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://www.gstatic.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self';");
header("X-Content-Type-Options: nosniff"); header("X-Frame-Options: SAMEORIGIN"); header("Referrer-Policy: no-referrer-when-downgrade");
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

// FETCH AGENT DATA
$stmt = $conn->prepare("SELECT username, gmail FROM agents WHERE id = ?");
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

            $stmt = $conn->prepare("UPDATE agents SET username=?, gmail=?, password=? WHERE id=?");
            $stmt->bind_param("sssi", $new_username, $new_email, $hashed_password, $_SESSION['id']);
        } else {
            $stmt = $conn->prepare("UPDATE agents SET username=?, gmail=? WHERE id=?");
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Account — RealEstate</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* ═══════════════════════════════════════════════════════════
   AGENT ACCOUNT — DARK LUXURY THEME
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
    --error:           #e05252;
    --error-bg:        rgba(224,82,82,0.1);
    
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

/* ───────────────────────────────────────────
   PAGE CONTENT
   ─────────────────────────────────────────── */
.page-content { 
    padding: 28px 24px; 
    flex: 1; 
}

/* ───────────────────────────────────────────
   FLASH MESSAGES
   ─────────────────────────────────────────── */
.flash {
    padding: 14px 20px;
    border-radius: var(--radius-sm);
    font-size: 13.5px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
    animation: slideDown 0.35s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid;
    position: relative;
    overflow: hidden;
}
.flash::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 2px;
}
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-12px); }
    to { opacity: 1; transform: translateY(0); }
}
.flash-success {
    background: var(--success-bg);
    border-color: rgba(90,171,122,0.2);
    color: #90d4aa;
}
.flash-success::before { background: var(--success); }
.flash-error {
    background: var(--error-bg);
    border-color: rgba(224,82,82,0.2);
    color: #e8a0a0;
}
.flash-error::before { background: var(--error); }
.flash i { font-size: 16px; }

/* ───────────────────────────────────────────
   PROFILE HEADER
   ─────────────────────────────────────────── */
.profile-header {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 32px;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    gap: 24px;
    flex-wrap: wrap;
}
.profile-header::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, transparent, var(--gold), transparent);
    opacity: 0.7;
}
.profile-header::after {
    content: '';
    position: absolute;
    top: -40%;
    right: -5%;
    width: 350px;
    height: 350px;
    background: radial-gradient(circle, var(--gold-glow) 0%, transparent 70%);
    pointer-events: none;
}
.profile-avatar-large {
    width: 80px;
    height: 80px;
    background: var(--gold-dim);
    border: 2px solid rgba(201,168,76,0.3);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Playfair Display', serif;
    font-size: 32px;
    font-weight: 700;
    color: var(--gold);
    flex-shrink: 0;
    position: relative;
    z-index: 1;
}
.profile-info { position: relative; z-index: 1; }
.profile-name {
    font-family: 'Playfair Display', serif;
    font-size: 26px;
    font-weight: 600;
    margin-bottom: 4px;
}
.profile-email {
    color: var(--text-muted);
    font-size: 14px;
    margin-bottom: 12px;
}
.profile-meta {
    display: flex;
    gap: 20px;
    font-size: 12px;
    color: var(--text-dim);
}
.profile-meta span {
    display: flex;
    align-items: center;
    gap: 6px;
}
.profile-meta i { font-size: 11px; color: var(--gold); }

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
.card-body { padding: 24px; }

/* ───────────────────────────────────────────
   FORMS
   ─────────────────────────────────────────── */
.form-group {
    margin-bottom: 20px;
}
.form-group label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    color: var(--text-muted);
    margin-bottom: 8px;
}
.form-group input {
    width: 100%;
    padding: 14px 16px;
    background: var(--bg-input);
    border: 1.5px solid var(--border);
    border-radius: var(--radius-xs);
    color: var(--text);
    font-size: 15px;
    font-family: inherit;
    outline: none;
    transition: var(--transition);
}
.form-group input:hover {
    border-color: var(--border-hover);
}
.form-group input:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px var(--gold-dim);
    background: var(--bg-hover);
}
.form-group input::placeholder {
    color: var(--text-dim);
}
.form-group input:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.password-wrapper {
    position: relative;
}
.password-wrapper input {
    padding-right: 50px;
}
.pw-toggle {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    padding: 4px 8px;
    border-radius: 4px;
    transition: var(--transition);
}
.pw-toggle:hover {
    color: var(--gold);
    background: var(--gold-dim);
}

.help-text {
    font-size: 12px;
    color: var(--text-dim);
    margin-top: 6px;
}

/* ───────────────────────────────────────────
   BUTTONS
   ─────────────────────────────────────────── */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 28px;
    border-radius: var(--radius-xs);
    font-size: 14px;
    font-weight: 600;
    font-family: inherit;
    border: none;
    cursor: pointer;
    transition: var(--transition);
    text-decoration: none;
    white-space: nowrap;
    letter-spacing: 0.3px;
}
.btn-primary {
    background: var(--gold);
    color: #0a0a0a;
    box-shadow: 0 4px 16px rgba(201,168,76,0.2);
}
.btn-primary:hover:not(:disabled) {
    background: var(--gold-light);
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(201,168,76,0.3);
}
.btn-primary:active:not(:disabled) {
    transform: translateY(0);
}
.btn-ghost {
    background: transparent;
    color: var(--text-secondary);
    border: 1.5px solid var(--border);
}
.btn-ghost:hover {
    background: var(--bg-hover);
    color: var(--text);
    border-color: var(--text-dim);
}
.btn-block {
    width: 100%;
}

/* ───────────────────────────────────────────
   DIVIDER
   ─────────────────────────────────────────── */
.divider {
    display: flex;
    align-items: center;
    gap: 16px;
    margin: 28px 0;
    color: var(--text-dim);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 1.5px;
}
.divider::before, .divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--border);
}

/* ───────────────────────────────────────────
   TWO COLUMN LAYOUT
   ─────────────────────────────────────────── */
.two-col {
    display: grid;
    grid-template-columns: 1fr;
    gap: 24px;
}

/* ───────────────────────────────────────────
   SECURITY SECTION
   ─────────────────────────────────────────── */
.security-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 24px;
}
.security-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px 0;
    border-bottom: 1px solid var(--border);
}
.security-item:last-child { border-bottom: none; }
.security-icon {
    width: 44px;
    height: 44px;
    background: var(--gold-dim);
    border-radius: var(--radius-xs);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--gold);
    font-size: 18px;
    flex-shrink: 0;
}
.security-info { flex: 1; }
.security-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 2px;
}
.security-desc {
    font-size: 12px;
    color: var(--text-muted);
}
.security-status {
    font-size: 12px;
    color: var(--success);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* ───────────────────────────────────────────
   RESPONSIVE
   ─────────────────────────────────────────── */
@media (min-width: 1024px) {
    .sidebar { transform: translateX(0) !important; }
    .sidebar-overlay { display: none !important; }
    .main-content { margin-left: var(--sidebar-w); }
    .hamburger { display: none; }
    .topbar-time { display: block; }
    .two-col { grid-template-columns: 2fr 1fr; }
    .page-content { padding: 32px; }
}

@media (max-width: 480px) {
    .page-content { padding: 16px; }
    .profile-header { padding: 24px 20px; }
    .profile-avatar-large { width: 60px; height: 60px; font-size: 24px; }
    .profile-name { font-size: 20px; }
    .card-body { padding: 20px; }
    .card-header { padding: 16px 20px; }
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
       
        <div class="nav-item">
            <a href="agent_transactions.php">
                <span class="nav-icon"><i class="fas fa-receipt"></i></span>
                My Transactions
            </a>
        </div>
       
        
        <div class="nav-section">Account</div>
        <div class="nav-item active">
            <a href="agent_profile.php">
                <span class="nav-icon"><i class="fas fa-user"></i></span>
                My Account
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
            <div class="topbar-title">My Account</div>
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

        <!-- Flash Messages -->
        <?php if($error): ?>
            <div class="flash flash-error">
                <i class="fas fa-circle-exclamation"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="flash flash-success">
                <i class="fas fa-circle-check"></i>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
        <?php endif; ?>

        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar-large">
                <?= strtoupper(substr($username,0,1)) ?>
            </div>
            <div class="profile-info">
                <div class="profile-name"><?= htmlspecialchars($username) ?></div>
                <div class="profile-email"><?= htmlspecialchars($email) ?></div>
                <div class="profile-meta">
                    <span><i class="fas fa-shield-halved"></i> Secure Account</span>
                    <span><i class="fas fa-id-badge"></i> Agent ID: #<?= $_SESSION['id'] ?></span>
                </div>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div class="two-col">
            <div class="main-col">

                <!-- Account Form -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Account Information</div>
                    </div>
                    <div class="card-body">
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
                                        placeholder="Leave blank to keep current password"
                                        autocomplete="new-password"
                                    >
                                    <button type="button" class="pw-toggle" onclick="togglePassword()">
                                        <i class="fas fa-eye" id="eyeIcon"></i>
                                    </button>
                                </div>
                                <div class="help-text">Only enter if you want to change your password</div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-block" id="submitBtn">
                                <i class="fas fa-save" style="font-size:13px;"></i>
                                Save Changes
                            </button>
                        </form>
                    </div>
                </div>

            </div>

            <div class="side-col">

                <!-- Security Status -->
                <div class="security-card">
                    <div style="font-family:'Playfair Display',serif;font-size:16px;font-weight:600;margin-bottom:16px;">
                        Security Status
                    </div>
                    
                    <div class="security-item">
                        <div class="security-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="security-info">
                            <div class="security-title">Email Verified</div>
                            <div class="security-desc"><?= htmlspecialchars($email) ?></div>
                        </div>
                        <div class="security-status">
                            <i class="fas fa-check-circle" style="font-size:14px;"></i> Active
                        </div>
                    </div>
                    
                    <div class="security-item">
                        <div class="security-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <div class="security-info">
                            <div class="security-title">Password</div>
                            <div class="security-desc">Last changed recently</div>
                        </div>
                        <div class="security-status">
                            <i class="fas fa-check-circle" style="font-size:14px;"></i> Secure
                        </div>
                    </div>
                    
                    <div class="security-item">
                        <div class="security-icon">
                            <i class="fas fa-mobile-screen"></i>
                        </div>
                        <div class="security-info">
                            <div class="security-title">Two-Factor Auth</div>
                            <div class="security-desc">OTP via email enabled</div>
                        </div>
                        <div class="security-status">
                            <i class="fas fa-check-circle" style="font-size:14px;"></i> Enabled
                        </div>
                    </div>
                </div>

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

// ─── PASSWORD TOGGLE ───
function togglePassword() {
    const pw = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if (pw.type === 'password') {
        pw.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        pw.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// ─── FORM LOADING ───
document.getElementById('accountForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="font-size:13px;"></i> Saving...';
});

// ─── FLASH AUTO-DISMISS ───
const flash = document.querySelector('.flash');
if (flash) {
    setTimeout(() => {
        flash.style.opacity = '0';
        flash.style.transform = 'translateY(-8px)';
        flash.style.transition = 'all 0.4s';
        setTimeout(() => flash.remove(), 400);
    }, 5000);
}
</script>

</body>
</html>