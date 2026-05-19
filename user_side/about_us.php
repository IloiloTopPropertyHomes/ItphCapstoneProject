<?php
// =================== SECURITY HEADERS ===================
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://www.gstatic.com https://cdn.botpress.cloud https://files.bpcontent.cloud; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; img-src 'self' data: https:; connect-src 'self' https://cdn.botpress.cloud https://files.bpcontent.cloud wss://*.botpress.cloud https://*.botpress.cloud; frame-src https://cdn.botpress.cloud; frame-ancestors 'self'; base-uri 'self';");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: no-referrer-when-downgrade");

session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => isset($_SERVER['HTTPS']),
    'cookie_samesite' => 'Strict'
]);

require_once '../backends/config.php';
$conn = get_db_connection();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// =================== LIVE STATS FROM DB ===================
$total_properties   = $conn->query("SELECT COUNT(*) AS t FROM propertiies")->fetch_assoc()['t'] ?? 0;
$total_users        = $conn->query("SELECT COUNT(*) AS t FROM user_login")->fetch_assoc()['t'] ?? 0;
$total_reservations = $conn->query("SELECT COUNT(*) AS t FROM reservations")->fetch_assoc()['t'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>About Us — ITPH</title>
   <script src="https://cdn.botpress.cloud/webchat/v3.6/inject.js"></script>
<script src="https://files.bpcontent.cloud/2026/05/13/12/20260513123611-N0BSRPKC.js" defer></script>
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
.top-contact .social-icons a { margin-left: 14px; color: #555; transition: color 0.2s; }
.top-contact .social-icons a:hover { color: var(--gold); }


/* ===================== HERO ===================== */
.hero {
    min-height: 100vh;
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
    top: -10%;
    right: -10%;
    width: 520px;
    height: 520px;
    border-radius: 50%;
    border: 1px solid rgba(191,161,88,0.08);
}
.hero-lines::after {
    content: '';
    position: absolute;
    top: 10%;
    right: 5%;
    width: 340px;
    height: 340px;
    border-radius: 50%;
    border: 1px solid rgba(191,161,88,0.06);
}
.hero-content { position: relative; z-index: 2; }
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
    width: 36px;
    height: 1px;
    background: var(--gold-light);
}
.hero h1 {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(3rem, 7vw, 6rem);
    font-weight: 400;
    color: #fff;
    line-height: 1.1;
    margin-bottom: 28px;
}
.hero h1 em { color: var(--gold-light); font-style: italic; }
.hero-desc {
    font-size: 0.95rem;
    color: rgba(255,255,255,0.5);
    line-height: 1.9;
    max-width: 480px;
    margin-bottom: 40px;
}
.hero-cta {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: var(--gold);
    color: #fff;
    padding: 14px 32px;
    font-size: 0.78rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    text-decoration: none;
    border-radius: 2px;
    transition: all 0.3s;
    font-weight: 400;
}
.hero-cta:hover { background: var(--gold-dark); color: #fff; gap: 16px; }
.hero-image-col { position: relative; }
.hero-img-frame {
    position: relative;
    margin-left: 30px;
}
.hero-img-frame img {
    width: 100%;
    height: 560px;
    object-fit: cover;
    display: block;
}
.hero-img-frame::before {
    content: '';
    position: absolute;
    top: -18px;
    left: -18px;
    right: 18px;
    bottom: 18px;
    border: 1px solid rgba(191,161,88,0.3);
    pointer-events: none;
    z-index: 0;
}
.hero-img-frame::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(to bottom, transparent 60%, rgba(26,26,46,0.7));
    z-index: 1;
}
.hero-badge {
    position: absolute;
    bottom: 30px;
    left: -20px;
    z-index: 3;
    background: var(--gold);
    color: #fff;
    padding: 20px 28px;
    text-align: center;
}
.hero-badge .num {
    font-family: 'Cormorant Garamond', serif;
    font-size: 2.8rem;
    font-weight: 600;
    line-height: 1;
    display: block;
}
.hero-badge .lbl {
    font-size: 0.65rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    opacity: 0.85;
    margin-top: 4px;
    display: block;
}
.scroll-hint {
    position: absolute;
    bottom: 36px;
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
    50% { transform: translateX(-50%) translateY(8px); }
}

/* ===================== STATS ===================== */
.stats-bar { background: var(--gold); padding: 0; }
.stat-item {
    padding: 36px 20px;
    text-align: center;
    position: relative;
    color: #fff;
}
.stat-item + .stat-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 25%;
    height: 50%;
    width: 1px;
    background: rgba(255,255,255,0.25);
}
.stat-num {
    font-family: 'Cormorant Garamond', serif;
    font-size: 3rem;
    font-weight: 600;
    line-height: 1;
    display: block;
}
.stat-label {
    font-size: 0.68rem;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    opacity: 0.82;
    margin-top: 6px;
    display: block;
}

/* ===================== STORY ===================== */
.story-section { padding: 100px 0; background: var(--white); }
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
    width: 28px;
    height: 1px;
    background: var(--gold);
}
.section-heading {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 400;
    color: var(--dark);
    line-height: 1.2;
    margin-bottom: 24px;
}
.section-heading em { font-style: italic; color: var(--gold-dark); }
.story-text {
    font-size: 0.92rem;
    line-height: 2;
    color: var(--text-muted);
    margin-bottom: 18px;
}
.story-text strong { color: var(--dark); font-weight: 500; }
.story-img-wrap { position: relative; }
.story-img-wrap img {
    width: 100%;
    height: 500px;
    object-fit: cover;
    display: block;
}
.story-img-wrap::after {
    content: '';
    position: absolute;
    bottom: -20px;
    right: -20px;
    width: 60%;
    height: 60%;
    border: 2px solid rgba(191,161,88,0.2);
    pointer-events: none;
    z-index: -1;
}
.check-list {
    list-style: none;
    margin: 28px 0 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.check-list li {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    font-size: 0.88rem;
    color: var(--text);
}
.check-list li i {
    color: var(--gold);
    font-size: 1rem;
    margin-top: 2px;
    flex-shrink: 0;
}

/* ===================== MISSION VISION ===================== */
.mv-section {
    padding: 100px 0;
    background: var(--dark);
    position: relative;
    overflow: hidden;
}
.mv-section::before {
    content: 'PURPOSE';
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    font-family: 'Montserrat', sans-serif;
    font-size: clamp(4rem, 12vw, 12rem);
    font-weight: 700;
    color: rgba(191,161,88,0.04);
    white-space: nowrap;
    pointer-events: none;
    letter-spacing: 0.1em;
}
.mv-card {
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(191,161,88,0.14);
    padding: 50px 40px;
    height: 100%;
    position: relative;
    transition: background 0.4s, border-color 0.4s;
}
.mv-card:hover {
    background: rgba(191,161,88,0.06);
    border-color: rgba(191,161,88,0.3);
}
.mv-card .mv-icon {
    width: 60px;
    height: 60px;
    border: 1px solid rgba(191,161,88,0.3);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 28px;
    color: var(--gold-light);
    font-size: 1.4rem;
    transition: background 0.3s;
}
.mv-card:hover .mv-icon { background: var(--gold); color: #fff; border-color: var(--gold); }
.mv-card h4 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.7rem;
    color: #fff;
    font-weight: 400;
    margin-bottom: 16px;
}
.mv-card p { font-size: 0.88rem; color: rgba(255,255,255,0.5); line-height: 1.9; }
.mv-card .mv-num {
    position: absolute;
    top: 36px;
    right: 36px;
    font-family: 'Cormorant Garamond', serif;
    font-size: 4rem;
    font-weight: 600;
    color: rgba(191,161,88,0.08);
    line-height: 1;
}

/* ===================== VALUES ===================== */
.values-section { padding: 100px 0; background: var(--cream); }
.value-item {
    padding: 36px 32px;
    background: var(--cream);
    border-bottom: 3px solid transparent;
    transition: border-color 0.3s, box-shadow 0.3s, transform 0.3s;
    height: 100%;
}
.value-item:hover {
    border-bottom-color: var(--gold);
    box-shadow: 0 12px 32px rgba(0,0,0,0.07);
    transform: translateY(-4px);
}
.value-icon { font-size: 1.8rem; color: var(--gold); margin-bottom: 20px; display: block; }
.value-item h5 {
    font-family: 'Montserrat', sans-serif;
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--dark);
    letter-spacing: 0.06em;
    text-transform: uppercase;
    margin-bottom: 12px;
}
.value-item p { font-size: 0.85rem; color: var(--text-muted); line-height: 1.8; }

/* ===================== TEAM ===================== */
.team-section { padding: 100px 0; background: var(--cream); }
.team-card {
    background: var(--cream);
    overflow: hidden;
    position: relative;
    transition: box-shadow 0.4s, transform 0.4s;
}
.team-card:hover {
    box-shadow: 0 20px 50px rgba(0,0,0,0.1);
    transform: translateY(-6px);
}
.team-img-wrap {
    position: relative;
    height: 280px;
    overflow: hidden;
}
.team-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s ease;
    filter: grayscale(20%);
}
.team-card:hover .team-img-wrap img {
    transform: scale(1.06);
    filter: grayscale(0%);
}
.team-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(26,26,46,0.88) 0%, rgba(26,26,46,0.2) 55%, transparent 100%);
    display: flex;
    align-items: flex-end;
    padding: 24px;
    opacity: 0;
    transition: opacity 0.4s;
}
.team-card:hover .team-overlay { opacity: 1; }

/* Social link icons with tooltip */
.team-socials { display: flex; gap: 10px; align-items: center; }
.team-social-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 1.5px solid rgba(255,255,255,0.5);
    color: #fff;
    font-size: 0.9rem;
    text-decoration: none;
    position: relative;
    transition: background 0.22s, border-color 0.22s, transform 0.22s;
}
.team-social-link:hover {
    background: var(--gold);
    border-color: var(--gold);
    color: #fff;
    transform: translateY(-3px);
}
.team-tip {
    position: absolute;
    bottom: calc(100% + 9px);
    left: 50%;
    transform: translateX(-50%);
    background: rgba(26,26,46,0.96);
    color: #fff;
    font-size: 0.58rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    white-space: nowrap;
    padding: 4px 9px;
    border-radius: 3px;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s, transform 0.2s;
    transform: translateX(-50%) translateY(4px);
    font-family: 'Montserrat', sans-serif;
    font-weight: 500;
}
.team-social-link:hover .team-tip {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}
.team-tip::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    border: 4px solid transparent;
    border-top-color: rgba(26,26,46,0.96);
}

.team-info { padding: 24px 24px 28px; }
.team-info h5 {
    font-weight: 600;
    font-size: 0.95rem;
    color: var(--dark);
    letter-spacing: 0.04em;
    margin-bottom: 4px;
}
.team-info .role {
    font-size: 0.72rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: var(--gold);
    font-weight: 400;
}
.team-divider { width: 30px; height: 2px; background: var(--gold); margin: 12px 0; }
.team-info p { font-size: 0.82rem; color: var(--text-muted); line-height: 1.7; }

/* ===================== CTA ===================== */
.cta-section {
    padding: 110px 0;
    background: var(--dark);
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
    font-size: clamp(2rem, 5vw, 3.8rem);
    color: #fff;
    font-weight: 400;
    margin-bottom: 20px;
    line-height: 1.2;
}
.cta-section h2 em { font-style: italic; color: var(--gold-light); }
.cta-section p {
    font-size: 0.9rem;
    color: rgba(255,255,255,0.45);
    max-width: 480px;
    margin: 0 auto 40px;
    line-height: 1.9;
}
.cta-btns { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }
.btn-gold {
    background: var(--gold);
    color: #fff;
    padding: 14px 36px;
    font-size: 0.78rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    text-decoration: none;
    border-radius: 2px;
    transition: all 0.3s;
    border: 1px solid var(--gold);
    font-family: 'Montserrat', sans-serif;
}
.btn-gold:hover { background: transparent; color: var(--gold-light); }
.btn-outline-gold {
    background: transparent;
    color: rgba(255,255,255,0.7);
    padding: 14px 36px;
    font-size: 0.78rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    text-decoration: none;
    border-radius: 2px;
    border: 1px solid rgba(255,255,255,0.2);
    transition: all 0.3s;
    font-family: 'Montserrat', sans-serif;
}
.btn-outline-gold:hover { border-color: var(--gold); color: var(--gold-light); }


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
.footer-social a {
    color: #fff;
    margin: 0 7px;
    font-size: 1rem;
    display: inline-flex;
    width: 38px; height: 38px;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    border: 1px solid rgba(255,255,255,0.4);
    transition: all 0.3s ease;
}
.footer-social a:hover { background: #fff; color: var(--gold); transform: scale(1.15); }
.back-to-top { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.8rem; letter-spacing: 0.1em; transition: color 0.2s; }
.back-to-top:hover { color: #fff; }

/* ===================== RESPONSIVE ===================== */
@media (max-width: 992px) {
    .hero { min-height: auto; padding: 130px 0 60px; }
    .hero-img-frame { margin: 40px 0 0; }
    .hero-img-frame img { height: 380px; }
    .stat-item + .stat-item::before { display: none; }
}
@media (max-width: 576px) {
    .hero h1 { font-size: 2.8rem; }
    .mv-card { padding: 36px 24px; }
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

/* Body base */
body.dark-mode {
    background: #121212;
    color: #e5e5e5;
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


/* Top Contact */
body.dark-mode .top-contact {
    background: rgba(18,18,18,0.9);
    border-bottom: 1px solid rgba(255,255,255,0.08);
    color: #ddd;
}

body.dark-mode .top-contact .social-icons a {
    color: #ddd;
}

/* Navbar */
body.dark-mode .navbar {
    background: rgba(20,20,20,0.9);
    border-bottom: 1px solid rgba(255,255,255,0.06);
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

body.dark-mode .hero-cta {
    background: var(--gold);
    color: #fff;
}

body.dark-mode .hero-cta:hover {
    background: var(--gold-dark);
}

body.dark-mode .hero-img-frame::before {
    border-color: rgba(191,161,88,0.2);
}

body.dark-mode .hero-badge {
    background: var(--gold);
}

body.dark-mode .scroll-hint {
    color: rgba(255,255,255,0.3);
}

/* Stats Bar */
body.dark-mode .stats-bar {
    background: #1a1a1a;
}

body.dark-mode .stat-item {
    color: #fff;
}

body.dark-mode .stat-item + .stat-item::before {
    background: rgba(255,255,255,0.15);
}

/* Story Section */
body.dark-mode .story-section {
    background: #121212;
}

body.dark-mode .section-heading {
    color: #fff;
}

body.dark-mode .story-text {
    color: #b0b0b0;
}

body.dark-mode .story-text strong {
    color: #fff;
}

body.dark-mode .check-list li {
    color: #ccc;
}

body.dark-mode .story-img-wrap::after {
    border-color: rgba(191,161,88,0.15);
}

/* Mission Vision Section */
body.dark-mode .mv-section {
    background: #0d0d0d;
}

body.dark-mode .mv-section::before {
    color: rgba(191,161,88,0.04);
}

body.dark-mode .mv-card {
    background: rgba(255,255,255,0.03);
    border-color: rgba(191,161,88,0.14);
}

body.dark-mode .mv-card:hover {
    background: rgba(191,161,88,0.06);
    border-color: rgba(191,161,88,0.3);
}

body.dark-mode .mv-card h4 {
    color: #fff;
}

body.dark-mode .mv-card p {
    color: rgba(255,255,255,0.5);
}

body.dark-mode .mv-card .mv-num {
    color: rgba(191,161,88,0.08);
}

/* Values Section */
body.dark-mode .values-section {
    background: #1a1a1a;
}

body.dark-mode .value-item {
    background: #1c1c1c;
}

body.dark-mode .value-item:hover {
    box-shadow: 0 12px 32px rgba(0,0,0,0.3);
}

body.dark-mode .value-item h5 {
    color: #fff;
}

body.dark-mode .value-item p {
    color: #aaa;
}

/* Team Section */
body.dark-mode .team-section {
    background: #1a1a1a;
}

body.dark-mode .team-card {
    background: #1c1c1c;
    transition: box-shadow 0.4s, transform 0.4s;
}

body.dark-mode .team-info h5 {
    color: #fff;
}

body.dark-mode .team-info p {
    color: #aaa;
}

body.dark-mode .team-overlay {
    background: linear-gradient(to top, rgba(10,10,20,0.95) 0%, rgba(10,10,20,0.4) 55%, transparent 100%);
    opacity: 0;
    transition: opacity 0.4s;
    
}

body.dark-mode .team-card:hover .team-overlay {
    opacity: 1;
}

body.dark-mode .team-social-link {
    border-color: rgba(255,255,255,0.5);
    color: #fff;
}

body.dark-mode .team-social-link:hover {
    background: var(--gold);
    border-color: var(--gold);
    color: #fff;
}

body.dark-mode .team-tip {
    background: rgba(20,20,30,0.96);
    color: #fff;
}

body.dark-mode .team-tip::after {
    border-top-color: rgba(20,20,30,0.96);
}


/* CTA Section */
body.dark-mode .cta-section {
    background: #0d0d0d;
}

body.dark-mode .cta-section::before,
body.dark-mode .cta-section::after {
    border-color: rgba(191,161,88,0.06);
}

body.dark-mode .cta-section h2 {
    color: #fff;
}

body.dark-mode .cta-section p {
    color: rgba(255,255,255,0.4);
}

body.dark-mode .btn-outline-gold {
    color: rgba(255,255,255,0.6);
    border-color: rgba(255,255,255,0.15);
}

body.dark-mode .btn-outline-gold:hover {
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
        <li class="nav-item"><a class="nav-link active-link" href="about_us.php">About Us</a></li>
        <li class="nav-item"><a class="nav-link" href="all_properties.php">Properties</a></li>
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
        <a href="user_side/log_out.php" title="Logout" style="color:#7a7a8a;font-size:1.1rem;transition:color .2s;"
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



<!-- ======================== HERO ======================== -->
<section class="hero" id="top">
    <div class="hero-bg-text">ITPH</div>
    <div class="hero-lines"></div>
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="hero-content">
                    <div class="hero-label" data-aos="fade-right">About Us</div>
                    <h1 data-aos="fade-up" data-aos-delay="80">
                        Built on<br><em>Trust, Quality</em><br>&amp; Community.
                    </h1>
                    <p class="hero-desc" data-aos="fade-up" data-aos-delay="160">
                        Iloilo Top Property Homes is more than a real estate platform — we are a community of believers in quality living, dedicated to connecting families with homes they truly deserve.
                    </p>
                    <a href="#story" class="hero-cta" data-aos="fade-up" data-aos-delay="240">
                        Discover Our Story <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left" data-aos-delay="160">
                <div class="hero-img-frame">
                    <img src="../photo/nbg.jpg" alt="ITPH Community">
                    <div class="hero-badge">
                        <span class="num"><?= $total_reservations ?>+</span>
                        <span class="lbl">Happy Families</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="scroll-hint">
        <span>Scroll</span>
        <i class="bi bi-chevron-down"></i>
    </div>
</section>

<!-- ======================== STATS ======================== -->
<div class="stats-bar">
    <div class="container-fluid px-0">
        <div class="row g-0">
            <div class="col-6 col-md-3 stat-item" data-aos="fade-up">
                <span class="stat-num counter" data-target="<?= $total_properties ?>">0</span>
                <span class="stat-label">Properties Listed</span>
            </div>
            <div class="col-6 col-md-3 stat-item" data-aos="fade-up" data-aos-delay="80">
                <span class="stat-num counter" data-target="<?= $total_users ?>">0</span>
                <span class="stat-label">Registered Users</span>
            </div>
            <div class="col-6 col-md-3 stat-item" data-aos="fade-up" data-aos-delay="160">
                <span class="stat-num counter" data-target="<?= $total_reservations ?>">0</span>
                <span class="stat-label">Total Reservations</span>
            </div>
            <div class="col-6 col-md-3 stat-item" data-aos="fade-up" data-aos-delay="240">
                <span class="stat-num counter" data-target="98">0</span>
                <span class="stat-label">% Client Satisfaction</span>
            </div>
        </div>
    </div>
</div>

<!-- ======================== OUR STORY ======================== -->
<section class="story-section" id="story">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5" data-aos="fade-right">
                <div class="story-img-wrap">
                    <img src="../photo/nbg.jpg" alt="Our Story">
                </div>
            </div>
            <div class="col-lg-7" data-aos="fade-left" data-aos-delay="80">
                <div class="section-eyebrow">Our Story</div>
                <h2 class="section-heading">Who We <em>Really Are</em></h2>
                <p class="story-text">
                    Iloilo Top Property Homes, known as <strong>ITPH</strong>, was founded with a single powerful belief — that every family in Iloilo deserves a safe, modern, and beautiful place to call home.
                </p>
                <p class="story-text">
                    We started as a small team passionate about real estate and community development. Today, we are a trusted platform showcasing premium subdivisions, offering reservation services, and providing comprehensive property information across Iloilo City and surrounding areas.
                </p>
                <ul class="check-list">
                    <li><i class="bi bi-check-circle-fill"></i> Transparent and reliable property listings</li>
                    <li><i class="bi bi-check-circle-fill"></i> Dedicated support from inquiry to move-in</li>
                    <li><i class="bi bi-check-circle-fill"></i> Carefully selected subdivisions in prime locations</li>
                    <li><i class="bi bi-check-circle-fill"></i> Affordable financing and reservation options</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- ======================== MISSION & VISION ======================== -->
<section class="mv-section">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <div class="section-eyebrow justify-content-center" style="color:var(--gold-light)">Our Purpose</div>
            <h2 class="section-heading" style="color:#fff;">What <em>Drives Us</em> Every Day</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-6" data-aos="fade-up" data-aos-delay="80">
                <div class="mv-card">
                    <span class="mv-num">01</span>
                    <div class="mv-icon"><i class="bi bi-bullseye"></i></div>
                    <h4>Our Mission</h4>
                    <p>To provide accessible and trustworthy property information while helping clients find the perfect home for their families — making the process simple, transparent, and rewarding.</p>
                </div>
            </div>
            <div class="col-md-6" data-aos="fade-up" data-aos-delay="160">
                <div class="mv-card">
                    <span class="mv-num">02</span>
                    <div class="mv-icon"><i class="bi bi-eye"></i></div>
                    <h4>Our Vision</h4>
                    <p>To become the most trusted real estate platform in Iloilo — a bridge that connects people to quality homes, investment opportunities, and communities built to last generations.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ======================== VALUES ======================== -->
<section class="values-section">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <div class="section-eyebrow justify-content-center">Core Values</div>
            <h2 class="section-heading">The Principles We <em>Live By</em></h2>
        </div>
        <div class="row g-4">
            <div class="col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="0">
                <div class="value-item">
                    <i class="bi bi-shield-check value-icon"></i>
                    <h5>Integrity</h5>
                    <p>We are honest in every transaction — no hidden fees, no false promises, just straightforward service.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="80">
                <div class="value-item">
                    <i class="bi bi-people value-icon"></i>
                    <h5>Community</h5>
                    <p>We build more than houses. We help create thriving neighborhoods where families flourish together.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="160">
                <div class="value-item">
                    <i class="bi bi-award value-icon"></i>
                    <h5>Excellence</h5>
                    <p>Every property we list meets our strict quality standards — because your family deserves nothing less.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3" data-aos="fade-up" data-aos-delay="240">
                <div class="value-item">
                    <i class="bi bi-heart value-icon"></i>
                    <h5>Care</h5>
                    <p>We listen, we guide, and we stand beside our clients from first inquiry all the way to move-in day.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ======================== TEAM ======================== -->
<section class="team-section">
    <div class="container">
        <div class="text-center mb-5" data-aos="fade-up">
            <div class="section-eyebrow justify-content-center">The People</div>
            <h2 class="section-heading">Meet the <em>Team Behind</em> ITPH</h2>
        </div>
        <div class="row g-4 justify-content-center">

            <!-- Card 1: Property Consultant -->
            <div class="col-sm-6 col-lg-4" data-aos="fade-up" data-aos-delay="0">
                <div class="team-card">
                    <div class="team-img-wrap">
                        <img src="../photo/profilepic.webp" alt="Property Consultant">
                        <div class="team-overlay">
                            <div class="team-socials">
                                <a href="https://facebook.com/itph" class="team-social-link" target="_blank" rel="noopener">
                                    <i class="bi bi-facebook"></i>
                                    <span class="team-tip">Facebook</span>
                                </a>
                                <a href="https://instagram.com/itph" class="team-social-link" target="_blank" rel="noopener">
                                    <i class="bi bi-instagram"></i>
                                    <span class="team-tip">Instagram</span>
                                </a>
                                <a href="mailto:consultant@itph.com.ph" class="team-social-link">
                                    <i class="bi bi-envelope-fill"></i>
                                    <span class="team-tip">Email</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="team-info">
                        <h5>Property Consultant</h5>
                        <div class="role">Sales Specialist</div>
                        <div class="team-divider"></div>
                        <p>Dedicated to helping clients find properties that match their lifestyle and budget perfectly.</p>
                    </div>
                </div>
            </div>

            <!-- Card 2: Marketing Manager -->
            <div class="col-sm-6 col-lg-4" data-aos="fade-up" data-aos-delay="100">
                <div class="team-card">
                    <div class="team-img-wrap">
                        <img src="../photo/profilepic.webp" alt="Marketing Manager">
                        <div class="team-overlay">
                            <div class="team-socials">
                                <a href="https://facebook.com/itph" class="team-social-link" target="_blank" rel="noopener">
                                    <i class="bi bi-facebook"></i>
                                    <span class="team-tip">Facebook</span>
                                </a>
                                <a href="https://instagram.com/itph" class="team-social-link" target="_blank" rel="noopener">
                                    <i class="bi bi-instagram"></i>
                                    <span class="team-tip">Instagram</span>
                                </a>
                                <a href="mailto:marketing@itph.com.ph" class="team-social-link">
                                    <i class="bi bi-envelope-fill"></i>
                                    <span class="team-tip">Email</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="team-info">
                        <h5>Marketing Manager</h5>
                        <div class="role">Property Marketing</div>
                        <div class="team-divider"></div>
                        <p>Crafting compelling stories around each property to connect the right home with the right family.</p>
                    </div>
                </div>
            </div>

            <!-- Card 3: Client Support -->
            <div class="col-sm-6 col-lg-4" data-aos="fade-up" data-aos-delay="200">
                <div class="team-card">
                    <div class="team-img-wrap">
                        <img src="../photo/profilepic.webp" alt="Client Support">
                        <div class="team-overlay">
                            <div class="team-socials">
                                <a href="https://facebook.com/itph" class="team-social-link" target="_blank" rel="noopener">
                                    <i class="bi bi-facebook"></i>
                                    <span class="team-tip">Facebook</span>
                                </a>
                                <a href="https://instagram.com/itph" class="team-social-link" target="_blank" rel="noopener">
                                    <i class="bi bi-instagram"></i>
                                    <span class="team-tip">Instagram</span>
                                </a>
                                <a href="mailto:support@itph.com.ph" class="team-social-link">
                                    <i class="bi bi-envelope-fill"></i>
                                    <span class="team-tip">Email</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="team-info">
                        <h5>Client Support</h5>
                        <div class="role">Customer Assistance</div>
                        <div class="team-divider"></div>
                        <p>Always available to answer questions, resolve concerns, and ensure a smooth experience for every client.</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- ======================== CTA ======================== -->
<section class="cta-section">
    <div class="container" style="position:relative;z-index:2;">
        <div class="section-eyebrow justify-content-center" style="color:var(--gold-light)" data-aos="fade-up">Ready to Begin?</div>
        <h2 data-aos="fade-up" data-aos-delay="80">Find Your <em>Dream Home</em><br>in Iloilo Today.</h2>
        <p data-aos="fade-up" data-aos-delay="160">Browse our curated listings and take the first step toward the life your family deserves.</p>
        <div class="cta-btns" data-aos="fade-up" data-aos-delay="240">
            <a href="all_properties.php" class="btn-gold">View Properties</a>
            <a href="contact_us.php" class="btn-outline-gold">Get in Touch</a>
        </div>
    </div>
</section>

<!-- ======================== FOOTER ======================== -->
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
        <hr style="border-color: rgba(255,255,255,0.2); margin: 20px 0 14px;">
        <div class="row">
            <div class="col-12 text-center" style="font-size:0.8rem; color:rgba(255,255,255,0.6);">
                © 2026 Iloilo Top Property Homes. All rights reserved. &nbsp;
                <a href="#">Privacy Policy</a> | <a href="#">Terms and Conditions</a>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
AOS.init({ once: true, offset: 60, duration: 700 });

// Scroll: hide top contact bar, stick navbar
window.addEventListener('scroll', () => {
    const navbar = document.querySelector('.navbar');
    const topContact = document.querySelector('.top-contact');
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
        topContact.classList.add('hidden');
    } else {
        navbar.classList.remove('scrolled');
        topContact.classList.remove('hidden');
    }
});

// Animated counters
const counters = document.querySelectorAll('.counter');
const speed = 120;
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const el = entry.target;
            const target = +el.getAttribute('data-target');
            let count = 0;
            const step = Math.ceil(target / speed);
            const update = () => {
                count = Math.min(count + step, target);
                el.textContent = count + (el.getAttribute('data-suffix') || '');
                if (count < target) requestAnimationFrame(update);
            };
            update();
            observer.unobserve(el);
        }
    });
}, { threshold: 0.5 });
counters.forEach(c => observer.observe(c));

// Chat toggle
function toggleChat() {
    const cw = document.getElementById('chat-window');
    cw.style.display = cw.style.display === 'flex' ? 'none' : 'flex';
}

// Chat send
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