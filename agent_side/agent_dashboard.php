<?php
// ─── YOUR ORIGINAL BACKEND CODE ───
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://www.gstatic.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self';");
header("X-Content-Type-Options: nosniff"); header("X-Frame-Options: SAMEORIGIN"); header("Referrer-Policy: no-referrer-when-downgrade");
session_start();
require_once __DIR__ . '/../backends/config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

$conn = get_db_connection();

// Auth check
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}
if(isset($_POST['confirm_reservation'])){
    $reservation_id = $_POST['reservation_id'];

    // Fetch reservation details
    $stmt = $conn->prepare("SELECT fullname, email, property FROM reservations WHERE id = ?");
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $stmt->bind_result($client_name, $client_email, $property);
    $stmt->fetch();
    $stmt->close();

    // Update reservation status to Confirmed
    $update = $conn->prepare("UPDATE reservations SET status = 'Confirmed', agent_id = ? WHERE id = ?");
    $update->bind_param("ii", $_SESSION['id'], $reservation_id);
    $update->execute();
    $update->close();

    // Prepare email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'itph934@gmail.com';
        $mail->Password   = 'bjhg rpeh ywaw eofo';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('itph934@gmail.com', 'ITPH ADMIN');
        $mail->addAddress($client_email, $client_name);

        $agent_name = $_SESSION['username'];

        $mail->isHTML(true);
        $mail->Subject = "Your Property Appointment is Confirmed";
        $mail->Body    = "
            <p>Good morning <strong>$client_name</strong>,</p>
            <p>Your appointment has been approved and the property <strong>$property</strong> is ready to view.</p>
            <p>Please look for <strong>$agent_name</strong> for your property appointment.</p>
            <p>Thank you for choosing <strong>Iloilo Top Property Homes</strong> for choosing your dream house!</p>
            <p>- ITPH ADMIN</p>
        ";

        $mail->send();
        $_SESSION['message'] = "Reservation confirmed and email sent to client!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }

    header("Location: agent_dashboard.php");
    exit;
}
// Fetch agent info
$stmt = $conn->prepare("SELECT username, gmail FROM agents WHERE id = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$stmt->bind_result($username, $email);
$stmt->fetch();
$stmt->close();

$_SESSION['username'] = $username;
$_SESSION['email'] = $email;

$agent_id = $_SESSION['id'];

// Total reservations claimed by this agent
$claimed_query = $conn->prepare("SELECT COUNT(*) as total_claimed FROM reservations WHERE agent_id = ?");
$claimed_query->bind_param("i", $agent_id);
$claimed_query->execute();
$claimed_result = $claimed_query->get_result()->fetch_assoc();
$total_claimed = $claimed_result['total_claimed'] ?? 0;
$claimed_query->close();

// Total done deals handled by this agent
$done_query = $conn->prepare("SELECT COUNT(*) as total_done FROM reservations WHERE agent_id = ? AND status = 'Done'");
$done_query->bind_param("i", $agent_id);
$done_query->execute();
$done_result = $done_query->get_result()->fetch_assoc();
$total_done = $done_result['total_done'] ?? 0;
$done_query->close();

// ================= AGENT PERFORMANCE DATA =================

// Arrays for chart
$months = [];
$claimed_counts = [];
$done_counts = [];

// Fetch monthly claimed reservations by this agent
$claimed_monthly_query = $conn->prepare("
    SELECT 
        MONTH(created_at) as month_num,
        MONTHNAME(created_at) as month_name,
        COUNT(*) as claimed_total
    FROM reservations
    WHERE agent_id = ? AND YEAR(created_at) = YEAR(CURDATE())
    GROUP BY MONTH(created_at), MONTHNAME(created_at)
    ORDER BY MONTH(created_at)
");
$claimed_monthly_query->bind_param("i", $agent_id);
$claimed_monthly_query->execute();
$claimed_result = $claimed_monthly_query->get_result();

// Map month number to claimed total
$claimed_map = [];
while($row = $claimed_result->fetch_assoc()){
    $claimed_map[$row['month_num']] = (int)$row['claimed_total'];
}
$claimed_monthly_query->close();

// Fetch monthly done deals handled by this agent
$done_monthly_query = $conn->prepare("
    SELECT 
        MONTH(created_at) as month_num,
        COUNT(*) as done_total
    FROM reservations
    WHERE agent_id = ? AND YEAR(created_at) = YEAR(CURDATE())
        AND status = 'Done'
    GROUP BY MONTH(created_at)
    ORDER BY MONTH(created_at)
");
$done_monthly_query->bind_param("i", $agent_id);
$done_monthly_query->execute();
$done_result = $done_monthly_query->get_result();

// Map month number to done total
$done_map = [];
while($row = $done_result->fetch_assoc()){
    $done_map[$row['month_num']] = (int)$row['done_total'];
}
$done_monthly_query->close();

// Prepare arrays for chart (Jan-Dec)
for($m=1; $m<=12; $m++){
    $months[] = date("M", mktime(0,0,0,$m,1));
    $claimed_counts[] = $claimed_map[$m] ?? 0;
    $done_counts[] = $done_map[$m] ?? 0;
}


/* ================= DATA ================= */

// Recent reservations
$reservations = [];
$res_query = $conn->query("SELECT * FROM reservations ORDER BY id DESC LIMIT 5");
while($row = $res_query->fetch_assoc()){
    $reservations[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Agent Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
/* ═══════════════════════════════════════════════════════════
   REAL ESTATE AGENT DASHBOARD — DARK LUXURY THEME
   Gold accent · Clean hierarchy · Smooth interactions
   ═══════════════════════════════════════════════════════════ */

@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap');

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
   CHART
   ─────────────────────────────────────────── */
.chart-wrap {
    padding: 24px;
    position: relative;
    height: 320px;
}

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
   BUTTONS
   ─────────────────────────────────────────── */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 9px 18px;
    border-radius: var(--radius-xs);
    font-size: 12px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: var(--transition);
    text-decoration: none;
    white-space: nowrap;
    font-family: inherit;
    letter-spacing: 0.3px;
}
.btn-sm { padding: 7px 14px; font-size: 11px; }
.btn-success {
    background: var(--success);
    color: #fff;
    box-shadow: 0 4px 12px rgba(90,171,122,0.2);
}
.btn-success:hover {
    background: #4d9a6b;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(90,171,122,0.3);
}
.btn-ghost {
    background: transparent;
    color: var(--text-muted);
    border: 1.5px solid var(--border);
}
.btn-ghost:hover {
    background: var(--bg-hover);
    color: var(--text);
    border-color: var(--text-dim);
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
   TWO COLUMN LAYOUT
   ─────────────────────────────────────────── */
.two-col {
    display: grid;
    grid-template-columns: 1fr;
    gap: 24px;
}

/* ───────────────────────────────────────────
   CONFIRMATION MODAL
   ─────────────────────────────────────────── */
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.8);
    backdrop-filter: blur(12px);
    z-index: 200;
    align-items: center;
    justify-content: center;
    padding: 20px;
    opacity: 0;
    transition: opacity 0.3s;
}
.modal-overlay.active { 
    display: flex; 
    opacity: 1; 
}
.modal {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    width: 100%;
    max-width: 440px;
    box-shadow: var(--shadow-lg);
    transform: scale(0.95) translateY(20px);
    transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.modal-overlay.active .modal {
    transform: scale(1) translateY(0);
}
.modal-header {
    padding: 28px 28px 0;
}
.modal-header h3 {
    font-family: 'Playfair Display', serif;
    font-size: 22px;
    font-weight: 600;
    margin-bottom: 6px;
}
.modal-header p {
    color: var(--text-muted);
    font-size: 14px;
}
.modal-body {
    padding: 24px 28px;
}
.modal-detail {
    background: var(--bg-elevated);
    border: 1px solid var(--border);
    border-radius: var(--radius-xs);
    padding: 18px;
    margin-bottom: 20px;
}
.modal-detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    font-size: 13.5px;
    border-bottom: 1px solid var(--border);
}
.modal-detail-row:last-child { border-bottom: none; }
.modal-detail-label { color: var(--text-muted); }
.modal-detail-value { 
    color: var(--text); 
    font-weight: 500; 
    text-align: right;
    max-width: 60%;
}
.modal-footer {
    padding: 0 28px 28px;
    display: flex;
    gap: 12px;
}
.modal-footer .btn { flex: 1; }

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
    .two-col { grid-template-columns: 2fr 1fr; }
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
    .chart-wrap { padding: 16px; height: 260px; }
    table { font-size: 12px; min-width: 500px; }
    thead th, tbody td { padding: 10px 14px; }
    .modal-header { padding: 20px 20px 0; }
    .modal-body, .modal-footer { padding-left: 20px; padding-right: 20px; }
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
        <div class="nav-item active">
            <a href="agent_dashboard.php">
                <span class="nav-icon"><i class="fas fa-chart-line"></i></span>
                Dashboard
            </a>
        </div>
        <div class="nav-item">
            <a href="my_transactions.php">
                <span class="nav-icon"><i class="fas fa-calendar-check"></i></span>
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
            <div class="topbar-title">Dashboard</div>
        </div>
        <div class="topbar-right">
            <div class="topbar-time" id="clock">--:--</div>
            <button class="notification-btn">
                <i class="fas fa-bell"></i>
                <span class="notification-dot"></span>
            </button>
        </div>
    </header>

    <!-- ─── CONTENT ─── -->
    <div class="page-content">

        <!-- Flash Messages -->
        <?php if(isset($_SESSION['message'])): ?>
            <div class="flash flash-success">
                <i class="fas fa-circle-check"></i>
                <span><?= htmlspecialchars($_SESSION['message']) ?></span>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        <?php if(isset($_SESSION['error'])): ?>
            <div class="flash flash-error">
                <i class="fas fa-circle-exclamation"></i>
                <span><?= htmlspecialchars($_SESSION['error']) ?></span>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Welcome Hero -->
        <div class="welcome-hero">
            <h2>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></h2>
            <p>Manage your appointments and client messages.</p>
            <div class="welcome-meta">
                <span><i class="fas fa-clock"></i> Agent Portal</span>
                <span><i class="fas fa-shield-halved"></i> Secure Session</span>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Appointments Claimed</div>
                <div class="stat-number"><?= $total_claimed ?></div>
                <div class="stat-icon"><i class="fas fa-handshake"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Done Deals</div>
                <div class="stat-number"><?= $total_done ?></div>
                <div class="stat-icon"><i class="fas fa-check"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Pending</div>
                <div class="stat-number">0</div>
                <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Close Rate</div>
                <div class="stat-number"><?= $total_claimed > 0 ? round(($total_done / $total_claimed) * 100) : 0 ?>%</div>
                <div class="stat-icon"><i class="fas fa-chart-pie"></i></div>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div class="two-col">
            <div class="main-col">

                <!-- Chart -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Agent Performance</div>
                        <div style="font-size:12px;color:var(--text-muted);"><?= date('Y') ?></div>
                    </div>
                    <div class="card-body">
                        <div class="chart-wrap">
                            <canvas id="agentPerformanceChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Reservations Table -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Recent Reservations</div>
                    </div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Property</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if(!empty($reservations)):
                                foreach($reservations as $row): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:500;color:var(--text);"><?= htmlspecialchars($row['fullname']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars($row['phone']) ?></td>
                                    <td><?= htmlspecialchars($row['property']) ?></td>
                                    <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <?php if($row['status'] === 'Done' && $row['agent_id'] == $_SESSION['id']): ?>
                                            <span class="badge badge-success"><i class="fas fa-check" style="font-size:9px;"></i> Done by you</span>
                                        <?php elseif($row['status'] === 'Confirmed' && $row['agent_id'] == $_SESSION['id']): ?>
                                            <span class="badge badge-gold"><i class="fas fa-check-double" style="font-size:9px;"></i> Confirmed</span>
                                        <?php elseif($row['status'] === 'Pending'): ?>
                                            <span class="badge badge-warning"><i class="fas fa-clock" style="font-size:9px;"></i> Pending</span>
                                        <?php else: ?>
                                            <span class="badge badge-muted"><?= htmlspecialchars($row['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($row['status'] === 'Done' && $row['agent_id'] == $_SESSION['id']): ?>
                                            <span style="color:var(--text-muted);font-size:12px;font-style:italic;">Completed</span>
                                        <?php elseif($row['agent_id'] == $_SESSION['id'] && $row['status'] !== 'Confirmed'): ?>
                                            <button class="btn btn-success btn-sm" onclick="openConfirmModal(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['fullname'])) ?>', '<?= htmlspecialchars(addslashes($row['property'])) ?>')">
                                                <i class="fas fa-check" style="font-size:10px;"></i> Confirm
                                            </button>
                                        <?php elseif($row['status'] === 'Confirmed' && $row['agent_id'] == $_SESSION['id']): ?>
                                            <span style="color:var(--gold);font-size:12px;"><i class="fas fa-check-double" style="font-size:10px;"></i> Yours</span>
                                        <?php elseif(empty($row['agent_id'])): ?>
                                            <button class="btn btn-success btn-sm" onclick="openConfirmModal(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['fullname'])) ?>', '<?= htmlspecialchars(addslashes($row['property'])) ?>')">
                                                <i class="fas fa-hand" style="font-size:10px;"></i> Claim
                                            </button>
                                        <?php else: ?>
                                            <span style="color:var(--text-muted);font-size:12px;"><i class="fas fa-lock" style="font-size:10px;"></i> Taken</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <i class="fas fa-inbox"></i>
                                            <p>No reservations yet</p>
                                            <span>New client appointments will appear here</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <div class="side-col">

                <!-- Messages -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Recent Messages</div>
                    </div>
                    <div class="card-body">
                        <div class="empty-state" style="padding:40px 20px;">
                            <i class="fas fa-envelope-open"></i>
                            <p>No messages yet</p>
                            <span>Client messages will appear here</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>
</div>

<!-- ─── CONFIRMATION MODAL ─── -->
<div class="modal-overlay" id="confirmModal" onclick="if(event.target===this)closeConfirmModal()">
    <div class="modal">
        <div class="modal-header">
            <h3>Confirm Reservation</h3>
            <p>Review details before confirming</p>
        </div>
        <div class="modal-body">
            <div class="modal-detail">
                <div class="modal-detail-row">
                    <span class="modal-detail-label">Client</span>
                    <span class="modal-detail-value" id="modalClient">--</span>
                </div>
                <div class="modal-detail-row">
                    <span class="modal-detail-label">Property</span>
                    <span class="modal-detail-value" id="modalProperty">--</span>
                </div>
                <div class="modal-detail-row">
                    <span class="modal-detail-label">Agent</span>
                    <span class="modal-detail-value"><?= htmlspecialchars($_SESSION['username']) ?></span>
                </div>
            </div>
            <p style="font-size:13px;color:var(--text-muted);line-height:1.6;">
                This will send a confirmation email to the client and assign this reservation to you.
            </p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeConfirmModal()">Cancel</button>
            <form method="POST" style="flex:1;">
                <input type="hidden" name="reservation_id" id="modalResId">
                <button type="submit" name="confirm_reservation" class="btn btn-success" style="width:100%;">
                    <i class="fas fa-check" style="font-size:11px;"></i> Confirm & Notify
                </button>
            </form>
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

// ─── CHART ───
const ctx = document.getElementById('agentPerformanceChart').getContext('2d');

const gradientGold = ctx.createLinearGradient(0, 0, 0, 320);
gradientGold.addColorStop(0, 'rgba(201,168,76,0.15)');
gradientGold.addColorStop(1, 'rgba(201,168,76,0)');

const gradientGreen = ctx.createLinearGradient(0, 0, 0, 320);
gradientGreen.addColorStop(0, 'rgba(90,171,122,0.15)');
gradientGreen.addColorStop(1, 'rgba(90,171,122,0)');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [
            {
                label: 'Reservations Claimed',
                data: <?= json_encode($claimed_counts) ?>,
                borderColor: 'rgba(201,168,76,1)',
                backgroundColor: gradientGold,
                borderWidth: 2.5,
                tension: 0.4,
                fill: true,
                pointRadius: 5,
                pointBackgroundColor: 'rgba(201,168,76,1)',
                pointBorderColor: '#0a0a0a',
                pointBorderWidth: 2.5,
                pointHoverRadius: 7,
            },
            {
                label: 'Done Deals',
                data: <?= json_encode($done_counts) ?>,
                borderColor: 'rgba(90,171,122,1)',
                backgroundColor: gradientGreen,
                borderWidth: 2.5,
                tension: 0.4,
                fill: true,
                pointRadius: 5,
                pointBackgroundColor: 'rgba(90,171,122,1)',
                pointBorderColor: '#0a0a0a',
                pointBorderWidth: 2.5,
                pointHoverRadius: 7,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: {
                position: 'top',
                align: 'end',
                labels: {
                    color: '#a39e96',
                    font: { size: 12, family: 'Inter' },
                    usePointStyle: true,
                    pointStyle: 'circle',
                    padding: 20,
                    boxWidth: 8,
                }
            },
            tooltip: {
                backgroundColor: '#161616',
                titleColor: '#f0ece3',
                bodyColor: '#a39e96',
                borderColor: '#2a2a2a',
                borderWidth: 1,
                padding: 14,
                cornerRadius: 10,
                displayColors: true,
                titleFont: { size: 13, weight: '600' },
                bodyFont: { size: 12 },
            }
        },
        scales: {
            x: {
                ticks: { color: '#6b6560', font: { size: 11 } },
                grid: { color: 'rgba(42,42,42,0.4)', drawBorder: false }
            },
            y: {
                ticks: { color: '#6b6560', font: { size: 11 }, padding: 10 },
                grid: { color: 'rgba(42,42,42,0.4)', drawBorder: false },
                beginAtZero: true,
            }
        }
    }
});

// ─── MODAL ───
function openConfirmModal(id, client, property) {
    document.getElementById('modalResId').value = id;
    document.getElementById('modalClient').textContent = client;
    document.getElementById('modalProperty').textContent = property;
    document.getElementById('confirmModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeConfirmModal() {
    document.getElementById('confirmModal').classList.remove('active');
    document.body.style.overflow = '';
}

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

// ─── ESCAPE KEY ───
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeConfirmModal();
});
</script>

</body>
</html>