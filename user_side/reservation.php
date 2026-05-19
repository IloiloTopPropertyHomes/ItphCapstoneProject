<?php
// =================== SECURITY HEADERS ===================
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://www.gstatic.com https://cdn.botpress.cloud https://files.bpcontent.cloud; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; img-src 'self' data: https:; connect-src 'self' https://cdn.botpress.cloud https://files.bpcontent.cloud wss://*.botpress.cloud https://*.botpress.cloud; frame-src https://cdn.botpress.cloud; frame-ancestors 'self'; base-uri 'self';");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: no-referrer-when-downgrade");

session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => isset($_SERVER['HTTPS']),
    'cookie_samesite' => 'Strict'
]);

require_once '../backends/config.php';
require_once '../backends/submit_reservation.php';
$conn = get_db_connection();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    $redirect_url = "reservation.php?house=" . urlencode($_GET['house'] ?? '') . "&property_page=" . urlencode($_GET['property_page'] ?? '');
    header("Location: login.php?redirect=" . urlencode($redirect_url));
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Fetch logged-in user info
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT fullname, email, gender, location, status, phone, secondary_email FROM customers WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Get property info from GET
$house    = $_GET['house']         ?? '';
$property = $_GET['property_page'] ?? '';

// Build initials
$nav_fullname = $_SESSION['fullname'] ?? '';
$nav_initials = strtoupper(implode('', array_map(fn($w) => $w[0], explode(' ', trim($nav_fullname)))));
$nav_initials = substr($nav_initials, 0, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reserve a Property — ITPH</title>
   <script src="https://cdn.botpress.cloud/webchat/v3.6/inject.js"></script>
<script src="https://files.bpcontent.cloud/2026/05/13/12/20260513123611-N0BSRPKC.js" defer></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
<link rel="stylesheet" href="css/common.css">

<style>
:root {
    --gold: #bfa158;
    --gold-dark: #8c7a45;
    --gold-light: #d4b97a;
    --cream: #f6f6f0;
    --dark: #1a1a2e;
    --text: #3a3a50;
    --text-muted: #7a7a8a;
    --white: #ffffff;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Montserrat', sans-serif;
    font-weight: 300;
    color: var(--text);
    background: var(--white);
}

/* ===================== TOP CONTACT ===================== */
.top-contact {
    color: #555;
    font-size: 0.78rem;
    padding: 6px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: fixed;
    top: 0; width: 100%;
    z-index: 1050;
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    letter-spacing: 0.04em;
    border-bottom: 1px solid rgba(191,161,88,0.15);
    transition: transform 0.3s ease, opacity 0.3s ease;
}
.top-contact.hidden { transform: translateY(-100%); opacity: 0; }
.top-contact .social-icons a { margin-left: 14px; color: #555; transition: color 0.2s; }
.top-contact .social-icons a:hover { color: var(--gold); }

/* ===================== NAVBAR ===================== */
.navbar {
    position: fixed;
    top: 30px; width: 100%;
    z-index: 1040;
    background: rgba(255,255,255,0.88);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    border-bottom: 1px solid rgba(191,161,88,0.12);
    transition: top 0.4s ease, box-shadow 0.4s ease;
}
.navbar.scrolled { top: 0; box-shadow: 0 2px 20px rgba(0,0,0,0.06); }
.navbar .navbar-brand { font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 1.5rem; color: var(--gold); letter-spacing: 0.05em; }
.navbar .nav-link { margin-left: 20px; color: var(--text); font-size: 0.82rem; font-weight: 400; letter-spacing: 0.08em; text-transform: uppercase; transition: color 0.2s; }
.navbar .nav-link:hover, .navbar .nav-link.active-link { color: var(--gold); }
.navbar .btn-reserve { background: var(--gold); border: 1px solid transparent; color: #fff; padding: 8px 22px; border-radius: 2px; font-size: 0.78rem; font-weight: 400; letter-spacing: 0.1em; text-transform: uppercase; transition: all 0.3s ease; }
.navbar .btn-reserve:hover { background: transparent; border-color: var(--gold); color: var(--gold); }
.dropdown-menu { background: #fafaf7; border: 1px solid rgba(191,161,88,0.15); border-radius: 4px; }

/* ===================== PAGE BANNER ===================== */
.page-banner {
    background: var(--dark);
    padding: 130px 0 60px;
    position: relative;
    overflow: hidden;
}
.page-banner::before {
    content: 'RESERVE';
    position: absolute;
    right: -20px; top: 50%;
    transform: translateY(-50%);
    font-family: 'Montserrat', sans-serif;
    font-size: 9rem; font-weight: 700;
    color: rgba(191,161,88,0.05);
    white-space: nowrap; pointer-events: none;
    letter-spacing: 0.1em;
}
.page-banner .section-label { font-size: 0.68rem; letter-spacing: 0.22em; text-transform: uppercase; color: var(--gold-light); font-weight: 400; margin-bottom: 14px; display: block; }
.page-banner h1 { font-family: 'Cormorant Garamond', serif; font-weight: 400; font-size: clamp(2rem, 5vw, 3.2rem); color: #fff; line-height: 1.2; margin-bottom: 14px; }
.page-banner h1 em { font-style: italic; color: var(--gold-light); }
.page-banner p { font-size: 0.9rem; color: rgba(255,255,255,0.5); line-height: 1.8; max-width: 500px; }

/* ===================== MAIN LAYOUT ===================== */
.rsv-layout {
    background: var(--cream);
    padding: 60px 0 80px;
    min-height: 60vh;
}

/* ===================== SUMMARY PANEL ===================== */
.summary-panel {
    background: var(--dark);
    padding: 44px 36px;
    position: sticky;
    top: 90px;
    border-radius: 2px;
}
.summary-panel .eyebrow {
    font-size: 0.62rem;
    letter-spacing: 0.22em;
    text-transform: uppercase;
    color: var(--gold-light);
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}
.summary-panel .eyebrow::before {
    content: '';
    display: inline-block;
    width: 24px; height: 1px;
    background: var(--gold-light);
}
.summary-panel h3 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.9rem;
    font-weight: 400;
    color: #fff;
    margin-bottom: 30px;
    line-height: 1.2;
}
.summary-panel h3 em { font-style: italic; color: var(--gold-light); }
.summary-divider { height: 1px; background: rgba(191,161,88,0.15); margin: 28px 0; }
.summary-row {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 20px;
}
.summary-row i { color: var(--gold); font-size: 0.95rem; margin-top: 2px; flex-shrink: 0; }
.summary-row .sr-label { font-size: 0.65rem; letter-spacing: 0.14em; text-transform: uppercase; color: rgba(255,255,255,0.35); margin-bottom: 3px; }
.summary-row .sr-value { font-size: 0.88rem; color: rgba(255,255,255,0.8); font-weight: 400; }
.summary-steps { margin-top: 36px; }
.step-item {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 20px;
}
.step-num {
    width: 28px; height: 28px;
    border-radius: 50%;
    border: 1px solid rgba(191,161,88,0.3);
    color: var(--gold-light);
    font-size: 0.72rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.step-text { font-size: 0.82rem; color: rgba(255,255,255,0.45); line-height: 1.6; padding-top: 4px; }
.step-text strong { color: rgba(255,255,255,0.75); display: block; font-weight: 500; margin-bottom: 2px; }

/* ===================== FORM CARD ===================== */
.form-card {
    background: var(--white);
    border: 1px solid rgba(191,161,88,0.1);
    border-radius: 2px;
    padding: 48px 44px;
}
.form-card-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.7rem;
    font-weight: 400;
    color: var(--dark);
    margin-bottom: 6px;
}
.form-card-sub {
    font-size: 0.8rem;
    color: var(--text-muted);
    letter-spacing: 0.04em;
    margin-bottom: 36px;
}
.form-section-title {
    font-size: 0.63rem;
    letter-spacing: 0.22em;
    text-transform: uppercase;
    color: var(--gold);
    font-weight: 400;
    margin-bottom: 16px;
    margin-top: 32px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.form-section-title::after {
    content: '';
    flex: 1;
    height: 1px;
    background: rgba(191,161,88,0.15);
}

/* Custom form controls */
.form-label {
    font-size: 0.74rem;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--text-muted);
    font-weight: 400;
    margin-bottom: 7px;
}
.form-control, .form-select {
    border: 1px solid rgba(191,161,88,0.18);
    border-radius: 2px;
    padding: 11px 14px;
    font-family: 'Montserrat', sans-serif;
    font-size: 0.87rem;
    font-weight: 300;
    color: var(--text);
    background: var(--white);
    transition: border-color 0.25s, box-shadow 0.25s;
}
.form-control:focus, .form-select:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(191,161,88,0.1);
    outline: none;
}
.form-control[readonly] {
    background: #fafaf7;
    color: var(--text-muted);
    cursor: not-allowed;
    border-color: rgba(191,161,88,0.1);
}
.form-control[readonly]:focus { box-shadow: none; border-color: rgba(191,161,88,0.1); }
small.text-muted { font-size: 0.75rem; }

/* Property badge in form */
.property-badge {
    background: rgba(191,161,88,0.08);
    border: 1px solid rgba(191,161,88,0.2);
    border-radius: 2px;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 0.85rem;
    color: var(--dark);
    font-weight: 400;
}
.property-badge i { color: var(--gold); font-size: 1rem; }

/* Checkbox */
.form-check-input:checked { background-color: var(--gold); border-color: var(--gold); }
.form-check-input:focus { box-shadow: 0 0 0 3px rgba(191,161,88,0.15); border-color: var(--gold); }
.form-check-label { font-size: 0.83rem; color: var(--text-muted); }
.form-check-label a { color: var(--gold); text-decoration: none; }
.form-check-label a:hover { color: var(--gold-dark); text-decoration: underline; }

/* Buttons */
.btn-cancel {
    background: transparent;
    border: 1px solid rgba(191,161,88,0.25);
    color: var(--text-muted);
    padding: 12px 28px;
    font-size: 0.78rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    font-family: 'Montserrat', sans-serif;
    border-radius: 2px;
    cursor: pointer;
    transition: all 0.3s;
}
.btn-cancel:hover { border-color: var(--text-muted); color: var(--text); }
.btn-confirm {
    background: var(--gold);
    border: 1px solid var(--gold);
    color: #fff;
    padding: 12px 36px;
    font-size: 0.78rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    font-family: 'Montserrat', sans-serif;
    border-radius: 2px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
}
.btn-confirm:hover {
    background: transparent;
    color: #bfa158;
    transform: translateY(-2px);
    text-decoration: none;
}

/* ===================== CHAT ===================== */
#chat-bubble {
    position: fixed; bottom: 28px; right: 28px;
    width: 54px; height: 54px;
    background: var(--gold); color: #fff;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; cursor: pointer;
    box-shadow: 0 6px 20px rgba(191,161,88,0.35);
    z-index: 2000;
    transition: transform 0.3s ease, box-shadow 0.3s;
}
#chat-bubble:hover { transform: scale(1.08); box-shadow: 0 10px 28px rgba(191,161,88,0.45); }
#chat-window {
    position: fixed; bottom: 94px; right: 28px;
    width: 340px; height: 480px;
    background: #fff; border-radius: 8px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
    display: none; flex-direction: column;
    overflow: hidden; z-index: 2000;
    border: 1px solid rgba(191,161,88,0.15);
}
.chat-header { background: var(--gold); color: #fff; padding: 14px 18px; font-weight: 500; font-size: 0.9rem; display: flex; justify-content: space-between; align-items: center; }
#chat-messages { flex: 1; padding: 16px; overflow-y: auto; background: #fdfdfb; display: flex; flex-direction: column; gap: 10px; }
.msg { padding: 10px 14px; border-radius: 10px; max-width: 82%; font-size: 0.87rem; line-height: 1.5; }
.msg-user { align-self: flex-end; background: var(--gold); color: #fff; border-bottom-right-radius: 2px; }
.msg-bot { align-self: flex-start; background: #f1f0ec; color: #333; border-bottom-left-radius: 2px; }
.chat-input-area { padding: 12px 14px; border-top: 1px solid #eee; display: flex; background: #fff; }
.chat-input-area input { flex: 1; border: 1px solid #ddd; padding: 9px 12px; border-radius: 4px; font-family: 'Montserrat', sans-serif; font-size: 0.85rem; outline: none; }
.chat-input-area button { background: none; border: none; color: var(--gold); font-size: 1.3rem; margin-left: 8px; cursor: pointer; }

/* ===================== MODAL ===================== */
.modal-content { border-radius: 4px; border: none; }
.modal-header { background: var(--dark); color: #fff; border-bottom: 1px solid rgba(191,161,88,0.15); }
.modal-title { font-family: 'Cormorant Garamond', serif; font-size: 1.4rem; font-weight: 400; }
.modal-header .btn-close { filter: invert(1); }

/* ===================== FOOTER ===================== */
.footer { background-color: var(--gold); color: #fff; width: 100%; padding-top: 50px; padding-bottom: 24px; }
.footer .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.footer-logo-text { font-family: 'Montserrat', sans-serif; font-size: 3.5rem; font-weight: 600; color: var(--gold); text-align: center; margin: 0 auto; display: block; text-shadow: 1px 1px 2px #fff; }
.footer h6 { font-weight: 500; margin-bottom: 14px; color: #fdd07b; font-size: 0.78rem; letter-spacing: 0.14em; text-transform: uppercase; }
.footer a { color: rgba(255,255,255,0.8); text-decoration: none; font-size: 0.88rem; }
.footer a:hover { color: #fff; text-decoration: underline; }
.footer-divider { width: 40px; height: 1px; background-color: rgba(255,255,255,0.5); border: none; margin: 12px auto 18px; }
.footer-about-text { font-size: 0.87rem; line-height: 1.7; color: rgba(255,255,255,0.8); }
.footer-contact span { font-size: 0.85rem; display: inline-block; margin: 0 6px; }
.footer-social a { color: #fff; margin: 0 7px; font-size: 1rem; display: inline-flex; width: 38px; height: 38px; align-items: center; justify-content: center; border-radius: 50%; border: 1px solid rgba(255,255,255,0.4); transition: all 0.3s ease; }
.footer-social a:hover { background: #fff; color: var(--gold); transform: scale(1.15); }
.back-to-top { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.8rem; letter-spacing: 0.1em; transition: color 0.2s; }
.back-to-top:hover { color: #fff; }

/* ===================== RESPONSIVE ===================== */
@media (max-width: 992px) {
    .summary-panel { position: static; margin-bottom: 28px; }
    .form-card { padding: 32px 24px; }
    .page-banner::before { display: none; }
}
@media (max-width: 576px) {
    .page-banner { padding: 120px 20px 50px; }
}

/* ===================== DARK MODE GLOBAL ===================== */

.theme-switch{
    width:68px;
    height:34px;
    background:#d8d8d8;
    border-radius:50px;
    position:relative;
    cursor:pointer;
    transition:all .35s ease;
    display:flex;
    align-items:center;
    padding:4px;
    margin-left:14px;
    box-shadow:inset 0 2px 6px rgba(0,0,0,0.12);
}

.theme-switch-slider{
    width:26px;
    height:26px;
    border-radius:50%;
    background:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    position:absolute;
    left:4px;
    transition:all .35s ease;
    box-shadow:0 3px 10px rgba(0,0,0,0.18);
}

.theme-switch i{
    position:absolute;
    font-size:.78rem;
}

.sun-icon{
    color:#f5b301;
    opacity:1;
}

.moon-icon{
    color:#fff;
    opacity:0;
}

/* ACTIVE DARK MODE */
body.dark-mode .theme-switch{
    background:#2d3250;
}

body.dark-mode .theme-switch-slider{
    left:38px;
    background:#1c1c1c;
    transition: all .35s ease;
}

body.dark-mode .sun-icon{
    opacity:0;
}

body.dark-mode .moon-icon{
    opacity:1;
}
body.dark-mode {
    background: #121212 !important;
    color: #e5e5e5;
}

body.dark-mode html {
    background: #121212;
}

/* Navbar now transitions background and border */
.navbar {
    transition: top 1s ease, box-shadow 1s ease, 
                background 1s ease, border-color 1s ease;
}

/* Top contact bar now transitions background, border, and color */
.top-contact {
    transition: transform 1s ease, opacity 1s ease,
                background 1s ease, border-color 1s ease, color 1s ease;
}
body {
    transition: transform 1s ease, opacity 1s ease,
                background 1s ease, border-color 1s ease, color 1s ease;
}

body * {
    transition: transform 1s ease, opacity 1s ease,
                background 1s ease, border-color 1s ease, color 1s ease;
}

/* ===================== TOP BAR ===================== */
body.dark-mode .top-contact {
    background: rgba(20,20,20,0.92);
    color: #ddd;
    border-bottom: 1px solid rgba(191,161,88,0.15);
}

body.dark-mode .top-contact .social-icons a {
    color: #ccc;
}

/* ===================== NAVBAR ===================== */
body.dark-mode .navbar {
    background: rgba(18,18,18,0.95);
    border-bottom: 1px solid rgba(191,161,88,0.12);
}

body.dark-mode .navbar .nav-link {
    color: #ddd;
}

body.dark-mode .navbar .nav-link:hover,
body.dark-mode .navbar .nav-link.active-link {
    color: var(--gold-light);
}

body.dark-mode .navbar-brand {
    color: var(--gold-light);
}

/* dropdown */
body.dark-mode .dropdown-menu {
    background: #1f1f1f;
    border: 1px solid rgba(191,161,88,0.12);
}

body.dark-mode .dropdown-menu h6,
body.dark-mode .dropdown-menu p {
    color: #eee !important;
}

/* ===================== PAGE BACKGROUNDS ===================== */
body.dark-mode .rsv-layout {
    background: #181818;
}

body.dark-mode .page-banner {
    background: #111;
}

/* ===================== FORM CARD ===================== */
body.dark-mode .form-card {
    background: #1f1f1f;
    border: 1px solid rgba(191,161,88,0.12);
}

body.dark-mode .form-card-title,
body.dark-mode .form-label,
body.dark-mode .form-check-label,
body.dark-mode .property-badge {
    color: #f1f1f1;
}

body.dark-mode .form-card-sub,
body.dark-mode small.text-muted {
    color: #b5b5b5 !important;
}

/* inputs */
body.dark-mode .form-control,
body.dark-mode .form-select {
    background: #2a2a2a;
    color: #fff;
    border: 1px solid rgba(191,161,88,0.18);
}

body.dark-mode .form-control::placeholder {
    color: #aaa;
}

body.dark-mode .form-control:focus,
body.dark-mode .form-select:focus {
    background: #2a2a2a;
    color: #fff;
    border-color: var(--gold);
}

body.dark-mode .form-control[readonly] {
    background: #252525;
    color: #ccc;
}

/* property badge */
body.dark-mode .property-badge {
    background: #252525;
    border: 1px solid rgba(191,161,88,0.18);
}

/* ===================== SUMMARY PANEL ===================== */
body.dark-mode .summary-panel {
    background: #141414;
}

body.dark-mode .summary-row .sr-value {
    color: #ddd;
}

body.dark-mode .step-text {
    color: rgba(255,255,255,0.6);
}

/* ===================== CHAT ===================== */
body.dark-mode #chat-window {
    background: #1e1e1e;
    border: 1px solid rgba(191,161,88,0.15);
}

body.dark-mode #chat-messages {
    background: #171717;
}

body.dark-mode .msg-bot {
    background: #2b2b2b;
    color: #fff;
}

body.dark-mode .chat-input-area {
    background: #1e1e1e;
    border-top: 1px solid #333;
}

body.dark-mode .chat-input-area input {
    background: #2a2a2a;
    color: #fff;
    border: 1px solid #444;
}

/* ===================== MODAL ===================== */
body.dark-mode .modal-content {
    background: #1f1f1f;
    color: #fff;
}

body.dark-mode .modal-body {
    color: #ddd;
}

/* ===================== FOOTER ===================== */
body.dark-mode .footer {
    background: #0f0f0f;
}

body.dark-mode .footer a {
    color: #ccc;
}

body.dark-mode .footer a:hover {
    color: #fff;
}

/* ===================== BUTTON FIXES ===================== */
body.dark-mode .btn-cancel {
    color: #ccc;
    border-color: rgba(255,255,255,0.2);
}

body.dark-mode .btn-cancel:hover {
    color: #fff;
    border-color: #fff;
}

body.dark-mode .btn-confirm {
    background: var(--gold);
}

/* ===================== SCROLL / SHADOW FIX ===================== */
body.dark-mode .navbar.scrolled {
    box-shadow: 0 2px 20px rgba(0,0,0,0.4);
}
</style>
</head>
<body id="top">

<!-- Top Contact -->
<div class="top-contact">
    <div>ITPH.com.ph &nbsp;|&nbsp; (+63) 927 933 3923</div>
    <div class="social-icons">
        <a href="#"><i class="bi bi-facebook"></i></a>
        <a href="#"><i class="bi bi-instagram"></i></a>
        <a href="#"><i class="bi bi-tiktok"></i></a>
    </div>
</div>

<!-- ===================== NAVBAR ===================== -->
<nav class="navbar navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand" href="index.php">ITPH</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav me-3">
        <li class="nav-item"><a class="nav-link" href="../index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="about_us.php">About Us</a></li>
        <li class="nav-item"><a class="nav-link active-link" href="all_properties.php">Properties</a></li>
        <li class="nav-item"><a class="nav-link" href="contact_us.php">Contact Us</a></li>
        <!-- CHANGED: News & Blogs (with dropdown) → Media (plain link) -->
        <li class="nav-item"><a class="nav-link" href="vlogs.php">Media</a></li>
      </ul>

      <?php if(isset($_SESSION['user_id'])): ?>
      <?php
        $nav_fullname = $_SESSION['fullname'] ?? '';
        $nav_initials = strtoupper(implode('', array_map(fn($w) => $w[0], explode(' ', trim($nav_fullname)))));
        $nav_initials = substr($nav_initials, 0, 2);
      ?>
      <div class="dropdown" style="display:flex; align-items:center; gap:10px;">
        <a href="account.php" title="My Account" style="
            width:38px; height:38px; border-radius:50%;
            background:var(--gold); color:#fff;
            font-size:0.75rem; font-weight:600; letter-spacing:0.05em;
            display:flex; align-items:center; justify-content:center;
            border:2px solid var(--gold-light); text-decoration:none;
            transition:transform .2s, box-shadow .2s;
        " onmouseover="this.style.transform='scale(1.07)';this.style.boxShadow='0 4px 14px rgba(191,161,88,0.35)'"
           onmouseout="this.style.transform='scale(1)';this.style.boxShadow='none'">
          <?= htmlspecialchars($nav_initials) ?>
        </a>
        <a href="log_out.php" title="Logout" style="color:#7a7a8a;font-size:1.1rem;transition:color .2s;"
           onmouseover="this.style.color='#e74c3c'" onmouseout="this.style.color='#7a7a8a'">
          <i class="bi bi-box-arrow-right"></i>
        </a>
      </div>
      <?php else: ?>
        <a href="login.php" class="btn btn-reserve">Log in</a>
      <?php endif; ?>

      <div class="theme-switch" id="darkModeToggle">
          <div class="theme-switch-slider">
              <i class="bi bi-sun-fill sun-icon"></i>
              <i class="bi bi-moon-fill moon-icon"></i>
          </div>
      </div>
    </div>
  </div>
</nav>



<!-- Page Banner -->
<section class="page-banner">
    <div class="container">
        <span class="section-label" data-aos="fade-up">Iloilo Top Property Homes</span>
        <h1 data-aos="fade-up" data-aos-delay="80">Book an <em>Appointment</em><br>for Your Dream Home.</h1>
        <p data-aos="fade-up" data-aos-delay="160">Fill in your details below and one of our property consultants will be in touch to confirm your visit.</p>
    </div>
</section>

<!-- Main Layout -->
<div class="rsv-layout">
    <div class="container">
        <div class="row g-4 align-items-start">

            <!-- Left: Summary Panel -->
            <div class="col-lg-4" data-aos="fade-right">
                <div class="summary-panel">
                    <div class="eyebrow">Reservation Summary</div>
                    <h3>Your <em>Selected</em> Property</h3>

                    <?php if($house || $property): ?>
                    <div class="summary-row">
                        <i class="bi bi-houses-fill"></i>
                        <div>
                            <div class="sr-label">Property</div>
                            <div class="sr-value"><?= htmlspecialchars($house ?: 'N/A') ?></div>
                        </div>
                    </div>
                    <div class="summary-row">
                        <i class="bi bi-grid-fill"></i>
                        <div>
                            <div class="sr-label">Subdivision</div>
                            <div class="sr-value"><?= htmlspecialchars($property ?: 'N/A') ?></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="summary-row">
                        <i class="bi bi-person-fill"></i>
                        <div>
                            <div class="sr-label">Applicant</div>
                            <div class="sr-value"><?= htmlspecialchars($user['fullname']) ?></div>
                        </div>
                    </div>
                    <div class="summary-row">
                        <i class="bi bi-envelope-fill"></i>
                        <div>
                            <div class="sr-label">Email</div>
                            <div class="sr-value"><?= htmlspecialchars($user['email']) ?></div>
                        </div>
                    </div>

                    <div class="summary-divider"></div>

                    <div class="summary-steps">
                        <div class="step-item">
                            <div class="step-num">1</div>
                            <div class="step-text">
                                <strong>Fill the Form</strong>
                                Choose your preferred date, time, and meeting type.
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-num">2</div>
                            <div class="step-text">
                                <strong>Await Confirmation</strong>
                                Our consultant will reach out to confirm your schedule.
                            </div>
                        </div>
                        <div class="step-item">
                            <div class="step-num">3</div>
                            <div class="step-text">
                                <strong>Visit the Property</strong>
                                Experience your future home in person.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Form Card -->
            <div class="col-lg-8" data-aos="fade-left" data-aos-delay="80">
                <div class="form-card">
                    <h2 class="form-card-title">Appointment Form</h2>
                    <p class="form-card-sub">All pre-filled fields are pulled from your account. You may update them in your profile.</p>

                    <form action="/recapstone/backends/submit_reservation.php" method="POST" id="rsv-form">
                        <input type="hidden" name="redirect" value="/recapstone/user_side/account.php#reservations">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                        <!-- Personal Info -->
                        <div class="form-section-title">Personal Information</div>
                        <div class="row g-3">
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" name="fullname"
                                       value="<?= htmlspecialchars($user['fullname']) ?>" readonly>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Gender</label>
                                <input type="text" class="form-control" name="gender"
                                       value="<?= htmlspecialchars($user['gender']) ?>" readonly>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Residency Status</label>
                                <input type="text" class="form-control" name="stats"
                                       value="<?= htmlspecialchars($user['status']) ?>" readonly>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Location</label>
                                <input type="text" class="form-control" name="location"
                                       value="<?= htmlspecialchars($user['location']) ?>" readonly>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control" name="email"
                                       value="<?= htmlspecialchars($user['email']) ?>" readonly>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Contact Number</label>
                                <input type="tel" class="form-control" name="phone"
                                       placeholder="Enter your number"
                                       pattern="[0-9+ ]{10,15}"
                                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>" readonly>
                            </div>
                        </div>

                        <!-- Property -->
                        <div class="form-section-title">Selected Property</div>
                        <div class="property-badge mb-3">
                            <i class="bi bi-house-heart-fill"></i>
                            <span><?= htmlspecialchars($house . ($property ? ' — ' . $property : '')) ?></span>
                        </div>
                        <input type="hidden" name="property_page" value="<?= htmlspecialchars($house . ' - ' . $property) ?>">

                        <!-- Schedule -->
                        <div class="form-section-title">Schedule & Meeting</div>
                        <div class="row g-3">
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Preferred Date</label>
                                <input type="date" class="form-control" name="date" id="reservation-date" required>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label">Preferred Time</label>
                                <input type="time" class="form-control" name="time" id="reservation-time"
                                       required min="08:00" max="20:00" step="900">
                                <small class="text-muted">Business hours: 8:00 AM – 8:00 PM</small>
                            </div>
                            <div class="col-12 mb-2">
                                <label class="form-label">Preferred Meeting Type</label>
                                <select class="form-select" name="meeting_type" required>
                                    <option value="">Select an option</option>
                                    <option value="Onsite">🏠 Onsite — Visit the Property</option>
                                    <option value="Office">🏢 Office — Meet at Our Office</option>
                                    <option value="Outside">☕ Outside — Coffee Shop</option>
                                </select>
                            </div>
                        </div>

                        <!-- Terms -->
                        <div class="mt-4 mb-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="agreeTerms" required>
                                <label class="form-check-label" for="agreeTerms">
                                    I agree to the
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a>
                                    and
                                    <a href="#" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a>
                                </label>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="d-flex justify-content-between align-items-center pt-2">
                            <button type="button" class="btn-cancel" onclick="history.back()">
                                <i class="bi bi-arrow-left me-1"></i> Cancel
                            </button>
                            <button type="submit" class="btn-confirm" id="submit-btn">
                                Confirm Appointment <i class="bi bi-check2-circle"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Terms Modal -->
<div class="modal fade" id="termsModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Terms and Conditions</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="font-size:0.88rem;line-height:1.8;color:#555;">
        <p>By submitting this reservation form, you agree to the terms set by Iloilo Top Property Homes. All reservation appointments are subject to availability and confirmation by our team.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Privacy Modal -->
<div class="modal fade" id="privacyModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Privacy Policy</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="font-size:0.88rem;line-height:1.8;color:#555;">
        <p>Your personal information is collected solely for the purpose of processing your property reservation. We do not share your data with third parties without your consent.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Footer -->
<footer class="footer mt-0">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4 text-center text-md-start">
                <div class="footer-logo-text mb-2">ITPH</div>
                <hr class="footer-divider">
                <p class="footer-about-text">Bringing quality living closer to your future. Iloilo Top Property Homes presents beautiful houses within well-planned subdivisions in Iloilo.</p>
            </div>
            <div class="col-md-2 mb-4">
                <h6>Quick Links</h6>
                <ul class="list-unstyled" style="line-height:2.1;">
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="about_us.php">About Us</a></li>
                    <li><a href="news.php">Latest News</a></li>
                    <li><a href="vlogs.php">Vlogs</a></li>
                </ul>
            </div>
            <div class="col-md-2 mb-4">
                <h6>Properties</h6>
                <ul class="list-unstyled" style="line-height:2.1;">
                    <li><a href="monticello.php">Monticello</a></li>
                    <li><a href="amani.php">Amani Homes</a></li>
                </ul>
            </div>
            <div class="col-md-4 mb-4">
                <h6>Tools</h6>
                <ul class="list-unstyled" style="line-height:2.1;">
                    <li><a href="contact_us.php">Contact Us</a></li>
                    <li><a href="reservation.php">Reserve Now</a></li>
                </ul>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12 text-center">
                <a href="#top" class="back-to-top">↑ Back to Top</a>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12 text-center footer-contact">
                <span><i class="bi bi-geo-alt-fill"></i> Pavia, Iloilo City</span> &nbsp;|&nbsp;
                <span><i class="bi bi-envelope-fill"></i> ITPH.com</span> &nbsp;|&nbsp;
                <span><i class="bi bi-telephone-fill"></i> (+63) 912 345 6789</span>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12 text-center footer-social">
                <a href="#"><i class="bi bi-facebook"></i></a>
                <a href="#"><i class="bi bi-instagram"></i></a>
                <a href="#"><i class="bi bi-tiktok"></i></a>
            </div>
        </div>
        <hr style="border-color:rgba(255,255,255,0.2);margin:20px 0 14px;">
        <div class="row">
            <div class="col-12 text-center" style="font-size:0.8rem;color:rgba(255,255,255,0.6);">
                © 2026 Iloilo Top Property Homes. All rights reserved. &nbsp;
                <a href="#">Privacy Policy</a> | <a href="#">Terms and Conditions</a>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
AOS.init({ once: true, offset: 50, duration: 700 });

// Scroll: hide top contact
window.addEventListener('scroll', () => {
    const navbar    = document.querySelector('.navbar');
    const topContact = document.querySelector('.top-contact');
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
        topContact.classList.add('hidden');
    } else {
        navbar.classList.remove('scrolled');
        topContact.classList.remove('hidden');
    }
});

// Date: block past dates
const dateInput = document.getElementById('reservation-date');
const today = new Date().toISOString().split('T')[0];
dateInput.setAttribute('min', today);

// Time + date validation on submit
const rsvForm = document.getElementById('rsv-form');
rsvForm.addEventListener('submit', function(e) {
    // Past date check
    if (dateInput.value && dateInput.value < today) {
        e.preventDefault();
        alert('Please select a future date.');
        dateInput.value = today;
        return;
    }

    // Time bounds check & convert to 12h
    const timeInput = document.getElementById('reservation-time');
    if (timeInput.value) {
        const [hour, minute] = timeInput.value.split(':').map(Number);
        if (hour < 8 || hour > 20 || (hour === 20 && minute > 0)) {
            e.preventDefault();
            alert('Please select a time between 8:00 AM and 8:00 PM.');
            return;
        }
        const ampm   = hour >= 12 ? 'PM' : 'AM';
        const hour12 = hour % 12 || 12;
        timeInput.value = `${hour12}:${minute.toString().padStart(2,'0')} ${ampm}`;
    }
});

// Chat
function toggleChat() {
    const cw = document.getElementById('chat-window');
    cw.style.display = cw.style.display === 'flex' ? 'none' : 'flex';
}

async function sendChat() {
    const field = document.getElementById('user-input-field');
    const msgs  = document.getElementById('chat-messages');
    const msg   = field.value.trim();
    if (!msg) return;

    const userDiv = document.createElement('div');
    userDiv.className = 'msg msg-user';
    userDiv.textContent = msg;
    msgs.appendChild(userDiv);
    field.value = '';
    msgs.scrollTop = msgs.scrollHeight;

    const botDiv = document.createElement('div');
    botDiv.className = 'msg msg-bot';
    botDiv.textContent = 'Typing…';
    msgs.appendChild(botDiv);
    msgs.scrollTop = msgs.scrollHeight;

    try {
        const fd = new FormData();
        fd.append('message', msg);
        fd.append('csrf_token', '<?= $csrf_token ?>');
        const res  = await fetch('../backends/chat.php', { method: 'POST', body: fd });
        const data = await res.json();
        botDiv.textContent = data.reply;
    } catch {
        botDiv.textContent = 'Connection error. Please try later.';
    }
    msgs.scrollTop = msgs.scrollHeight;
}
// =====================
// DARK MODE SWITCH
// =====================

const darkToggle = document.getElementById('darkModeToggle');

// Load saved theme
if(localStorage.getItem('darkMode') === 'enabled'){
    document.body.classList.add('dark-mode');
}

darkToggle.addEventListener('click', () => {
    document.body.classList.toggle('dark-mode');

    if(document.body.classList.contains('dark-mode')){
        localStorage.setItem('darkMode', 'enabled');
    } else {
        localStorage.setItem('darkMode', 'disabled');
    }
});
</script>
</body>
<script src="https://cdn.botpress.cloud/webchat/v3.6/inject.js"></script>
<script src="https://files.bpcontent.cloud/2026/05/16/02/20260516020411-RS0TP9AJ.js" defer></script>
</html>