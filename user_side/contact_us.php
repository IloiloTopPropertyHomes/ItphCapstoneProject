<?php
// ===================== SECURITY HEADERS =====================
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://www.gstatic.com https://cdn.botpress.cloud https://files.bpcontent.cloud; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; img-src 'self' data: https:; connect-src 'self' https://cdn.botpress.cloud https://files.bpcontent.cloud wss://*.botpress.cloud https://*.botpress.cloud; frame-src https://cdn.botpress.cloud; frame-ancestors 'self'; base-uri 'self';");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: no-referrer-when-downgrade");

// ===================== SESSION =====================
session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => !empty($_SERVER['HTTPS']),
    'cookie_samesite' => 'Strict'
]);

require_once '../backends/config.php';
$conn = get_db_connection();

// ===================== AUTH CHECK =====================
if (empty($_SESSION['fullname'])) {
    header("Location: login.php?redirect=contact_us.php");
    exit();
}

$user_name = $_SESSION['fullname'];

// ===================== FETCH USER =====================
$stmt = $conn->prepare("SELECT fullname, email FROM customers WHERE fullname = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("s", $user_name);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// ===================== INVALID USER =====================
if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// ===================== CSRF TOKEN =====================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact Us — ITPH</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400;1,600&display=swap" rel="stylesheet">
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
    overflow-x: hidden;
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
    top: 0;
    width: 100%;
    z-index: 1050;
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    letter-spacing: 0.04em;
    border-bottom: 1px solid rgba(191,161,88,0.15);
    transition: transform 0.3s ease, opacity 0.3s ease;
}
.top-contact.hidden { transform: translateY(-100%); opacity: 0; }
.top-contact .social-icons a { margin-left: 14px; color: #555; transition: color 0.2s; text-decoration: none; }
.top-contact .social-icons a:hover { color: var(--gold); }

/* ===================== NAVBAR ===================== */
.navbar {
    position: fixed;
    top: 30px;
    width: 100%;
    z-index: 1040;
    background: rgba(255,255,255,0.88);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    border-bottom: 1px solid rgba(191,161,88,0.12);
    transition: top 0.4s ease, box-shadow 0.4s ease;
}
.navbar.scrolled { top: 0; box-shadow: 0 2px 20px rgba(0,0,0,0.06); }
.navbar .navbar-brand {
    font-family: 'Montserrat', sans-serif;
    font-weight: 700;
    font-size: 1.5rem;
    color: var(--gold);
    letter-spacing: 0.05em;
}
.navbar .nav-link {
    margin-left: 20px;
    color: var(--text);
    font-size: 0.82rem;
    font-weight: 400;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    transition: color 0.2s;
}
.navbar .nav-link:hover,
.navbar .nav-link.active-link { color: var(--gold); }
.navbar .btn-reserve {
    background: var(--gold);
    border: 1px solid transparent;
    color: #fff;
    padding: 8px 22px;
    border-radius: 2px;
    font-size: 0.78rem;
    font-weight: 400;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    transition: all 0.3s ease;
}
.navbar .btn-reserve:hover { background: transparent; border-color: var(--gold); color: var(--gold); }
.dropdown-menu { background: #fafaf7; border: 1px solid rgba(191,161,88,0.15); border-radius: 4px; }

/* ===================== HERO ===================== */
.hero {
    min-height: 60vh;
    background: var(--dark);
    display: flex;
    align-items: center;
    position: relative;
    overflow: hidden;
    padding-top: 80px;
}
.hero-bg-text {
    position: absolute;
    left: -30px;
    bottom: -20px;
    font-family: 'Montserrat', sans-serif;
    font-size: clamp(6rem, 18vw, 18rem);
    font-weight: 700;
    color: rgba(191,161,88,0.04);
    white-space: nowrap;
    pointer-events: none;
    letter-spacing: -0.02em;
    line-height: 1;
    user-select: none;
}
.hero-lines {
    position: absolute;
    top: 0; right: 0;
    width: 40%;
    height: 100%;
    overflow: hidden;
    pointer-events: none;
}
.hero-lines::before {
    content: '';
    position: absolute;
    top: -10%; right: -10%;
    width: 520px; height: 520px;
    border-radius: 50%;
    border: 1px solid rgba(191,161,88,0.08);
}
.hero-lines::after {
    content: '';
    position: absolute;
    top: 10%; right: 5%;
    width: 340px; height: 340px;
    border-radius: 50%;
    border: 1px solid rgba(191,161,88,0.06);
}
.hero-content { position: relative; z-index: 2; padding: 60px 0; }
.hero-label {
    font-size: 0.68rem;
    letter-spacing: 0.28em;
    text-transform: uppercase;
    color: var(--gold-light);
    font-weight: 400;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.hero-label::before {
    content: '';
    display: inline-block;
    width: 36px; height: 1px;
    background: var(--gold-light);
}
.hero h1 {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(2.8rem, 6vw, 5rem);
    font-weight: 400;
    color: #fff;
    line-height: 1.1;
    margin-bottom: 20px;
}
.hero h1 em { color: var(--gold-light); font-style: italic; }
.hero-desc {
    font-size: 0.92rem;
    color: rgba(255,255,255,0.5);
    line-height: 1.9;
    max-width: 480px;
}
.scroll-hint {
    position: absolute;
    bottom: 28px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    color: rgba(255,255,255,0.3);
    font-size: 0.65rem;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    animation: scrollBounce 2s infinite;
}
.scroll-hint i { font-size: 1rem; }
@keyframes scrollBounce {
    0%, 100% { transform: translateX(-50%) translateY(0); }
    50%       { transform: translateX(-50%) translateY(8px); }
}

/* ===================== SECTION SHARED ===================== */
.section-eyebrow {
    font-size: 0.65rem;
    letter-spacing: 0.26em;
    text-transform: uppercase;
    color: var(--gold);
    font-weight: 400;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.section-eyebrow::before {
    content: '';
    display: inline-block;
    width: 28px; height: 1px;
    background: var(--gold);
}
.section-heading {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 400;
    color: var(--dark);
    line-height: 1.2;
    margin-bottom: 16px;
}
.section-heading em { font-style: italic; color: var(--gold-dark); }

/* ===================== INFO CARDS ===================== */
.info-section { padding: 90px 0 60px; background: var(--white); }
.info-card {
    padding: 40px 32px;
    background: var(--cream);
    border-bottom: 3px solid transparent;
    transition: border-color 0.3s, box-shadow 0.3s, transform 0.3s;
    height: 100%;
    text-align: center;
}
.info-card:hover {
    border-bottom-color: var(--gold);
    box-shadow: 0 12px 32px rgba(0,0,0,0.07);
    transform: translateY(-4px);
}
.info-icon {
    width: 64px; height: 64px;
    border: 1px solid rgba(191,161,88,0.3);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    color: var(--gold);
    font-size: 1.4rem;
    transition: background 0.3s, border-color 0.3s, color 0.3s;
}
.info-card:hover .info-icon { background: var(--gold); border-color: var(--gold); color: #fff; }
.info-card h5 {
    font-weight: 600;
    font-size: 0.82rem;
    color: var(--dark);
    letter-spacing: 0.1em;
    text-transform: uppercase;
    margin-bottom: 10px;
}
.info-card p { font-size: 0.88rem; color: var(--text-muted); line-height: 1.7; margin: 0; }

/* ===================== FORM SECTION ===================== */
.form-section { padding: 80px 0; background: var(--cream); }

.form-wrap {
    background: var(--white);
    padding: 52px 48px;
    position: relative;
}
.form-wrap::before {
    content: '';
    position: absolute;
    top: -12px; left: -12px;
    right: 12px; bottom: 12px;
    border: 1px solid rgba(191,161,88,0.2);
    pointer-events: none;
    z-index: 0;
}
.form-wrap > * { position: relative; z-index: 1; }

.form-control-custom {
    width: 100%;
    border: none;
    border-bottom: 1px solid rgba(191,161,88,0.3);
    border-radius: 0;
    padding: 14px 0;
    font-family: 'Montserrat', sans-serif;
    font-size: 0.88rem;
    font-weight: 300;
    color: var(--text);
    background: transparent;
    outline: none;
    transition: border-color 0.3s;
    margin-bottom: 28px;
    display: block;
}
.form-control-custom::placeholder { color: var(--text-muted); letter-spacing: 0.04em; }
.form-control-custom:focus { border-bottom-color: var(--gold); }
.form-control-custom[readonly] { color: var(--text-muted); cursor: default; }

textarea.form-control-custom {
    resize: none;
    min-height: 120px;
}

.btn-send {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: var(--gold);
    color: #fff;
    padding: 14px 36px;
    font-size: 0.78rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    border: 1px solid var(--gold);
    border-radius: 2px;
    cursor: pointer;
    font-family: 'Montserrat', sans-serif;
    font-weight: 400;
    transition: all 0.3s;
}
.btn-send:hover { background: transparent; color: var(--gold); gap: 16px; }

/* Image side */
.contact-img-wrap { position: relative; height: 100%; min-height: 440px; }
.contact-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.contact-img-wrap::after {
    content: '';
    position: absolute;
    bottom: -16px; right: -16px;
    width: 55%; height: 55%;
    border: 2px solid rgba(191,161,88,0.2);
    pointer-events: none;
    z-index: -1;
}
.contact-img-badge {
    position: absolute;
    top: 24px; left: -20px;
    background: var(--gold);
    color: #fff;
    padding: 18px 24px;
    z-index: 2;
}
.contact-img-badge .badge-num {
    font-family: 'Cormorant Garamond', serif;
    font-size: 2.2rem;
    font-weight: 600;
    line-height: 1;
    display: block;
}
.contact-img-badge .badge-lbl {
    font-size: 0.6rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    opacity: 0.85;
    margin-top: 4px;
    display: block;
}

/* ===================== MAP SECTION ===================== */
.map-section { padding: 90px 0; background: var(--dark); position: relative; overflow: hidden; }
.map-section::before {
    content: 'FIND US';
    position: absolute;
    left: 50%; top: 50%;
    transform: translate(-50%, -50%);
    font-family: 'Montserrat', sans-serif;
    font-size: clamp(4rem, 12vw, 12rem);
    font-weight: 700;
    color: rgba(191,161,88,0.04);
    white-space: nowrap;
    pointer-events: none;
    letter-spacing: 0.1em;
}
.map-frame {
    border: 1px solid rgba(191,161,88,0.2);
    overflow: hidden;
    position: relative;
    z-index: 1;
}
.map-frame iframe { display: block; }

/* ===================== CTA ===================== */
.cta-section {
    padding: 100px 0;
    background: var(--white);
    text-align: center;
    position: relative;
    overflow: hidden;
}
.cta-section::before {
    content: '';
    position: absolute;
    left: 50%; top: 50%;
    transform: translate(-50%, -50%);
    width: 600px; height: 600px;
    border-radius: 50%;
    border: 1px solid rgba(191,161,88,0.07);
    pointer-events: none;
}
.cta-section::after {
    content: '';
    position: absolute;
    left: 50%; top: 50%;
    transform: translate(-50%, -50%);
    width: 900px; height: 900px;
    border-radius: 50%;
    border: 1px solid rgba(191,161,88,0.04);
    pointer-events: none;
}
.cta-section h2 {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(2rem, 5vw, 3.4rem);
    color: var(--dark);
    font-weight: 400;
    margin-bottom: 16px;
    line-height: 1.2;
}
.cta-section h2 em { font-style: italic; color: var(--gold-dark); }
.cta-section p { font-size: 0.9rem; color: var(--text-muted); max-width: 460px; margin: 0 auto 36px; line-height: 1.9; }
.cta-btns { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }
.btn-gold {
    background: var(--gold); color: #fff;
    padding: 14px 36px; font-size: 0.78rem;
    letter-spacing: 0.14em; text-transform: uppercase;
    text-decoration: none; border-radius: 2px;
    transition: all 0.3s; border: 1px solid var(--gold);
    font-family: 'Montserrat', sans-serif;
}
.btn-gold:hover { background: transparent; color: var(--gold); }
.btn-outline-dark {
    background: transparent; color: var(--text);
    padding: 14px 36px; font-size: 0.78rem;
    letter-spacing: 0.14em; text-transform: uppercase;
    text-decoration: none; border-radius: 2px;
    border: 1px solid rgba(58,58,80,0.25);
    transition: all 0.3s; font-family: 'Montserrat', sans-serif;
}
.btn-outline-dark:hover { border-color: var(--gold); color: var(--gold-dark); }

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
.msg-bot  { align-self: flex-start; background: #f1f0ec; color: #333; border-bottom-left-radius: 2px; }
.chat-input-area { padding: 12px 14px; border-top: 1px solid #eee; display: flex; background: #fff; }
.chat-input-area input { flex: 1; border: 1px solid #ddd; padding: 9px 12px; border-radius: 4px; font-family: 'Montserrat', sans-serif; font-size: 0.85rem; outline: none; }
.chat-input-area button { background: none; border: none; color: var(--gold); font-size: 1.3rem; margin-left: 8px; cursor: pointer; }

/* ===================== FOOTER ===================== */
.footer { background-color: var(--dark); color: #fff; width: 100%; padding-top: 50px; padding-bottom: 24px; }
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
    .hero { min-height: auto; }
    .contact-img-wrap { min-height: 320px; margin-top: 48px; }
    .contact-img-badge { left: 0; }
    .form-wrap { padding: 36px 28px; }
    .form-wrap::before { display: none; }
}
@media (max-width: 576px) {
    .hero h1 { font-size: 2.5rem; }
    .form-section { padding: 60px 0; }
    .info-section { padding: 60px 0 40px; }
}

/* =====================
   DARK MODE SWITCH
===================== */
.theme-switch {
    width: 68px;
    height: 34px;
    background: #d8d8d8;
    border-radius: 50px;
    position: relative;
    cursor: pointer;
    transition: all .35s ease;
    display: flex;
    align-items: center;
    padding: 4px;
    margin-left: 14px;
    box-shadow: inset 0 2px 6px rgba(0,0,0,0.12);
}

.theme-switch-slider {
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    position: absolute;
    left: 4px;
    transition: all .35s ease;
    box-shadow: 0 3px 10px rgba(0,0,0,0.18);
}

.theme-switch i {
    position: absolute;
    font-size: .78rem;
}

.sun-icon {
    color: #f5b301;
    opacity: 1;
}

.moon-icon {
    color: #fff;
    opacity: 0;
}

/* =====================
   DARK MODE STYLES
===================== */

/* Switch active state */
body.dark-mode .theme-switch {
    background: #2d3250;
}

body.dark-mode .theme-switch-slider {
    left: 38px;
    background: #1c1c1c;
}

body.dark-mode .sun-icon {
    opacity: 0;
}

body.dark-mode .moon-icon {
    opacity: 1;
}

/* Body base - 1s transition */
body {
    transition: background 1s ease, color 1s ease, border-color 1s ease;
}

body * {
    transition: background 1s ease, color 1s ease, border-color 1s ease;
}

body.dark-mode {
    background: #121212;
    color: #e5e5e5;
}

/* Top Contact - 1s transition */
.top-contact {
    transition: transform 1s ease, opacity 1s ease, background 1s ease, border-color 1s ease, color 1s ease;
}

body.dark-mode .top-contact {
    background: rgba(18,18,18,0.9);
    border-bottom: 1px solid rgba(255,255,255,0.08);
    color: #ddd;
    transition: transform 1s ease, opacity 1s ease, background 1s ease, border-color 1s ease, color 1s ease;
}

body.dark-mode .top-contact .social-icons a {
    color: #ddd;
}

/* Navbar - 1s transition */
.navbar {
    transition: top 1s ease, box-shadow 1s ease, background 1s ease, border-color 1s ease;
}

body.dark-mode .navbar {
    background: rgba(20,20,20,0.9);
    border-bottom: 1px solid rgba(255,255,255,0.06);
    transition: top 1s ease, box-shadow 1s ease, background 1s ease, border-color 1s ease;
}

body.dark-mode .navbar .nav-link {
    color: #f1f1f1;
}

body.dark-mode .navbar .nav-link:hover,
body.dark-mode .navbar .nav-link.active-link {
    color: var(--gold-light);
}

body.dark-mode .dropdown-menu {
    background: #1d1d1d;
    border: 1px solid rgba(255,255,255,0.08);
}

body.dark-mode .dropdown-menu p,
body.dark-mode .dropdown-menu h6 {
    color: #f1f1f1 !important;
}

/* Hero Section */
body.dark-mode .hero {
    background: #0a0a14;
}

body.dark-mode .hero-bg-text {
    color: rgba(191,161,88,0.06);
}

body.dark-mode .hero-lines::before {
    border-color: rgba(191,161,88,0.12);
}

body.dark-mode .hero-lines::after {
    border-color: rgba(191,161,88,0.08);
}

body.dark-mode .hero-label {
    color: var(--gold-light);
}

body.dark-mode .hero-label::before {
    background: var(--gold-light);
}

body.dark-mode .hero h1 {
    color: #fff;
}

body.dark-mode .hero-desc {
    color: rgba(255,255,255,0.6);
}

body.dark-mode .scroll-hint {
    color: rgba(255,255,255,0.3);
}

/* Info Cards */
body.dark-mode .info-section {
    background: #121212;
}

body.dark-mode .info-card {
    background: #1c1c1c;
}

body.dark-mode .info-card:hover {
    box-shadow: 0 12px 32px rgba(0,0,0,0.3);
}

body.dark-mode .info-card h5 {
    color: #fff;
}

body.dark-mode .info-card p {
    color: #aaa;
}

/* Form Section */
body.dark-mode .form-section {
    background: #1a1a1a;
}

body.dark-mode .form-wrap {
    background: #1c1c1c;
}

body.dark-mode .form-wrap::before {
    border-color: rgba(191,161,88,0.15);
}

body.dark-mode .form-control-custom {
    color: #e5e5e5;
    border-bottom-color: rgba(255,255,255,0.15);
}

body.dark-mode .form-control-custom::placeholder {
    color: #888;
}

body.dark-mode .form-control-custom[readonly] {
    color: #888;
}

/* Map Section */
body.dark-mode .map-section {
    background: #0d0d0d;
}

body.dark-mode .map-frame {
    border-color: rgba(191,161,88,0.15);
}

/* CTA Section */
body.dark-mode .cta-section {
    background: #121212;
}

body.dark-mode .cta-section h2 {
    color: #fff;
}

body.dark-mode .cta-section p {
    color: #aaa;
}

body.dark-mode .btn-outline-dark {
    color: rgba(255,255,255,0.7);
    border-color: rgba(255,255,255,0.15);
}

body.dark-mode .btn-outline-dark:hover {
    border-color: var(--gold);
    color: var(--gold-light);
}

/* Chat */
body.dark-mode #chat-window {
    background: #1a1a1a;
    border: 1px solid rgba(255,255,255,0.08);
}

body.dark-mode #chat-messages {
    background: #111;
}

body.dark-mode .msg-bot {
    background: #2a2a2a;
    color: #f1f1f1;
}

body.dark-mode .chat-input-area {
    background: #1a1a1a;
    border-top: 1px solid rgba(255,255,255,0.08);
}

body.dark-mode .chat-input-area input {
    background: #2a2a2a;
    border: 1px solid rgba(255,255,255,0.08);
    color: #fff;
}

/* Footer */
body.dark-mode .footer {
    background: #171717;
}

body.dark-mode .footer-about-text,
body.dark-mode .footer a,
body.dark-mode .footer-contact span {
    color: #d4d4d4;
}
body.dark-mode .section-heading { color: #fff; }
</style>
</head>
<body id="top">

<!-- ===================== TOP CONTACT ===================== -->
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
        <li class="nav-item"><a class="nav-link" href="all_properties.php">Properties</a></li>
        <li class="nav-item"><a class="nav-link active-link" href="contact_us.php">Contact Us</a></li>
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



<!-- ===================== HERO ===================== -->
<section class="hero" id="top">
    <div class="hero-bg-text">CONTACT</div>
    <div class="hero-lines"></div>
    <div class="container">
        <div class="hero-content">
            <div class="hero-label" data-aos="fade-right">Get In Touch</div>
            <h1 data-aos="fade-up" data-aos-delay="80">
                Let's Start a<br><em>Conversation.</em>
            </h1>
            <p class="hero-desc" data-aos="fade-up" data-aos-delay="160">
                Whether you're looking for your first home, an investment property, or just need advice — our team is ready to help you every step of the way.
            </p>
        </div>
    </div>
    <div class="scroll-hint">
        <span>Scroll</span>
        <i class="bi bi-chevron-down"></i>
    </div>
</section>

<!-- ===================== INFO CARDS ===================== -->
<section class="info-section">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <div class="section-eyebrow justify-content-center">Reach Us</div>
            <h2 class="section-heading">Ways to <em>Connect With Us</em></h2>
        </div>
        <div class="row g-4">
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="0">
                <div class="info-card">
                    <div class="info-icon"><i class="bi bi-geo-alt"></i></div>
                    <h5>Our Location</h5>
                    <p>Pavia, Iloilo City<br>Western Visayas, Philippines</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="80">
                <div class="info-card">
                    <div class="info-icon"><i class="bi bi-telephone"></i></div>
                    <h5>Phone</h5>
                    <p>(+63) 927 933 3923<br>(+63) 912 345 6789</p>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="160">
                <div class="info-card">
                    <div class="info-icon"><i class="bi bi-envelope"></i></div>
                    <h5>Email</h5>
                    <p>hello@itph.com.ph<br>support@itph.com.ph</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===================== FORM + IMAGE ===================== -->
<section class="form-section" id="message">
    <div class="container">
        <div class="row align-items-stretch g-5">

            <!-- FORM -->
            <div class="col-lg-6" data-aos="fade-right">
                <div class="form-wrap">
                    <div class="section-eyebrow">Send a Message</div>
                    <h2 class="section-heading" style="margin-bottom:36px;">We'd Love to <em>Hear From You</em></h2>

                    <form action="../backends/send_message.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                        <input
                            type="text"
                            name="name"
                            class="form-control-custom"
                            placeholder="Full Name"
                            value="<?= htmlspecialchars($_SESSION['fullname'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            readonly
                            required
                        >

                        <input
                            type="email"
                            name="email"
                            class="form-control-custom"
                            placeholder="Email Address"
                            value="<?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            readonly
                            required
                        >

                        <input
                            type="text"
                            name="phone"
                            class="form-control-custom"
                            placeholder="Phone Number (optional)"
                        >

                        <textarea
                            name="message"
                            class="form-control-custom"
                            placeholder="Your message or inquiry…"
                            required
                        ></textarea>

                        <button type="submit" class="btn-send">
                            Send Message <i class="bi bi-arrow-right"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- IMAGE -->
            <div class="col-lg-6" data-aos="fade-left" data-aos-delay="100">
                <div class="contact-img-wrap">
                    <img src="../photo/uploads/contact.jpg" alt="ITPH Property">
                    <div class="contact-img-badge">
                        <span class="badge-num">24/7</span>
                        <span class="badge-lbl">Support Available</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ===================== MAP ===================== -->
<section class="map-section">
    <div class="container" style="position:relative;z-index:1;">
        <div class="text-center mb-5" data-aos="fade-up">
            <div class="section-eyebrow justify-content-center" style="color:var(--gold-light)">Our Location</div>
            <h2 class="section-heading" style="color:#fff;">Find Us on <em style="color:var(--gold-light);">the Map</em></h2>
            <p style="color:rgba(255,255,255,0.45);font-size:0.9rem;max-width:460px;margin:0 auto;line-height:1.9;">
                Visit our office or explore the location of our properties across Iloilo.
            </p>
        </div>
        <div class="map-frame" data-aos="fade-up" data-aos-delay="80">
            <iframe
                width="100%"
                height="460"
                style="border:0;"
                loading="lazy"
                allowfullscreen
                referrerpolicy="no-referrer-when-downgrade"
                src="https://www.google.com/maps?q=Pavia,+Iloilo+City&output=embed">
            </iframe>
        </div>
    </div>
</section>

<!-- ===================== CTA ===================== -->
<section class="cta-section">
    <div class="container" style="position:relative;z-index:2;">
        <div class="section-eyebrow justify-content-center" data-aos="fade-up">Ready to Begin?</div>
        <h2 data-aos="fade-up" data-aos-delay="80">Browse Our <em>Available</em><br>Properties Today.</h2>
        <p data-aos="fade-up" data-aos-delay="160">Explore curated listings and take the first step toward the home your family deserves.</p>
        <div class="cta-btns" data-aos="fade-up" data-aos-delay="240">
            <a href="all_properties.php" class="btn-gold">View Properties</a>
            <a href="reservation.php" class="btn-outline-dark">Reserve Now</a>
        </div>
    </div>
</section>

<!-- ===================== FOOTER ===================== -->
<footer class="footer mt-0">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4 text-center text-md-start">
                <div class="footer-logo-text mb-2">ITPH</div>
                <hr class="footer-divider">
                <p class="footer-about-text">Bringing quality living closer to your future. Iloilo Top Property Homes presents beautiful houses within well-planned subdivisions in Iloilo, providing a safe environment and modern living for homeowners.</p>
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
                    <li><a href="phrst.php">PHIRST</a></li>
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
        <hr style="border-color: rgba(255,255,255,0.2); margin: 20px 0 14px;">
        <div class="row">
            <div class="col-12 text-center" style="font-size:0.8rem; color:rgba(255,255,255,0.6);">
                © 2026 Iloilo Top Property Homes. All rights reserved. &nbsp;
                <a href="#">Privacy Policy</a> | <a href="#">Terms and Conditions</a>
            </div>
        </div>
    </div>
</footer>

<!-- ===================== SCRIPTS ===================== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
AOS.init({ once: true, offset: 60, duration: 700 });

// Scroll: hide top contact, stick navbar
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
if (localStorage.getItem('darkMode') === 'enabled') {
    document.body.classList.add('dark-mode');
}

darkToggle.addEventListener('click', () => {
    document.body.classList.toggle('dark-mode');

    if (document.body.classList.contains('dark-mode')) {
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