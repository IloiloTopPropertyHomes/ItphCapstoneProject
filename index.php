<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://www.gstatic.com https://cdn.botpress.cloud https://files.bpcontent.cloud; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; img-src 'self' data: https:; connect-src 'self' https://cdn.botpress.cloud https://files.bpcontent.cloud wss://*.botpress.cloud https://*.botpress.cloud; frame-src https://cdn.botpress.cloud; frame-ancestors 'self'; base-uri 'self';");
header("X-Content-Type-Options: nosniff"); 
header("X-Frame-Options: SAMEORIGIN"); 
header("Referrer-Policy: no-referrer-when-downgrade");
session_start();
require_once ('backends/config.php');

$conn = get_db_connection();
if (isset($_SESSION['user_id'])) {

    $id = $_SESSION['user_id'];

    $stmt = $conn->prepare("
        UPDATE auth_logs
        SET last_activity = NOW(),
            session_status = 'online'
        WHERE user_id = ?
          AND role = 'customer'
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();
}
if (isset($_SESSION['user_id'])) {
    $conn->query("
        UPDATE auth_logs
        SET activity_time = NOW()
        WHERE user_id = {$_SESSION['user_id']}
        ORDER BY id DESC
        LIMIT 1
    ");
}

$properties = [];
$query = "SELECT * FROM propertiies ORDER BY created_at DESC LIMIT 3";
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['gallery_images'] = [];
        if (!empty($row['image'])) {
            $gallery = json_decode($row['image'], true);
            if (is_array($gallery)) {
                $row['gallery_images'] = $gallery;
            } else {
                $row['gallery_images'] = explode(',', $row['image']);
            }
        }
        $properties[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Iloilo Top Property Homes</title>

    
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Cormorant+Garamond:wght@400;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
<link rel="stylesheet" href="user_side/css/common.css">

<style>
/* =====================
   LOADER
===================== */
#loader {
    position: fixed;
    width: 100%;
    height: 100vh;
    background: var(--cream);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    z-index: 99999;
    transition: opacity 0.6s ease;
}
body.loading > *:not(#loader) { visibility: hidden; }
.house { position: relative; width: 100px; height: 90px; margin-bottom: 24px; animation: bounce 1.6s infinite ease-in-out; }
.roof { position: absolute; width: 0; height: 0; border-left: 50px solid transparent; border-right: 50px solid transparent; border-bottom: 52px solid var(--gold); top: -42px; }
.body-house { width: 100px; height: 70px; background: var(--white); border: 2px solid #ddd; position: absolute; bottom: 0; }
.door { width: 26px; height: 40px; background: var(--gold-dark); position: absolute; bottom: 0; left: 37px; border-radius: 3px 3px 0 0; }
@keyframes bounce { 0%,100%{ transform: translateY(0); } 50%{ transform: translateY(-10px); } }
.loading-text { font-size: 13px; color: var(--gold-dark); margin-bottom: 16px; letter-spacing: 0.15em; text-transform: uppercase; font-weight: 400; }
.progress-bar { width: 160px; height: 2px; background: #ddd; border-radius: 10px; overflow: hidden; }
.progress { width: 0%; height: 100%; background: var(--gold); animation: load 2.5s forwards; }
@keyframes load { from{ width: 0%; } to{ width: 100%; } }

/* =====================
   HERO
===================== */
.hero {
    position: relative;
    height: 100vh;
    min-height: 600px;
    overflow: hidden;
    display: flex;
    align-items: center;
}
.hero-img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    z-index: 0;
    transform: scale(1.04);
    transition: transform 8s ease;
}
.hero.loaded .hero-img { transform: scale(1); }
.hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(105deg, rgba(10,10,20,0.72) 0%, rgba(10,10,20,0.32) 65%, transparent 100%);
    z-index: 1;
}
.hero-content {
    position: relative;
    z-index: 2;
    padding: 0 60px;
    max-width: 620px;
}
.hero-eyebrow {
    display: inline-block;
    font-size: 0.7rem;
    font-weight: 400;
    letter-spacing: 0.25em;
    text-transform: uppercase;
    color: var(--gold-light);
    margin-bottom: 20px;
    opacity: 0;
    transform: translateY(12px);
    animation: fadeUp 0.8s 0.4s forwards;
}
.hero-content h1 {
    font-family: 'Montserrat', sans-serif;
    font-size: clamp(2.2rem, 5vw, 3.6rem);
    font-weight: 400;
    line-height: 1.2;
    color: #fff;
    margin-bottom: 22px;
    opacity: 0;
    transform: translateY(16px);
    animation: fadeUp 0.9s 0.6s forwards;
}
.hero-content h1 em { font-style: italic; color: var(--gold-light); }
.hero-content p {
    font-size: 0.95rem;
    color: rgba(255,255,255,0.75);
    line-height: 1.75;
    margin-bottom: 36px;
    max-width: 430px;
    opacity: 0;
    animation: fadeUp 0.9s 0.8s forwards;
}
@keyframes fadeUp { to{ opacity: 1; transform: translateY(0); } }

/* Search bar */
.hero-search {
    display: flex;
    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 4px;
    overflow: hidden;
    opacity: 0;
    animation: fadeUp 0.9s 1s forwards;
    max-width: 420px;
}
.hero-search select {
    flex: 1;
    background: transparent;
    border: none;
    padding: 14px 16px;
    font-family: 'Montserrat', sans-serif;
    font-size: 0.85rem;
    color: #fff;
    outline: none;
    appearance: none;
    cursor: pointer;
}
.hero-search select option { color: var(--text); background: #fff; }
.hero-search button {
    background: var(--gold);
    border: none;
    padding: 0 22px;
    color: #fff;
    font-size: 1rem;
    cursor: pointer;
    transition: background 0.2s;
    display: flex;
    align-items: center;
}
.hero-search button:hover { background: var(--gold-dark); }

/* Hero scroll cue */
.hero-scroll {
    position: absolute;
    bottom: 36px;
    left: 60px;
    z-index: 2;
    display: flex;
    align-items: center;
    gap: 10px;
    color: rgba(255,255,255,0.5);
    font-size: 0.7rem;
    letter-spacing: 0.2em;
    text-transform: uppercase;
}
.hero-scroll .line {
    width: 40px;
    height: 1px;
    background: rgba(255,255,255,0.3);
    position: relative;
    overflow: hidden;
}
.hero-scroll .line::after {
    content: '';
    position: absolute;
    left: -100%;
    top: 0;
    width: 100%;
    height: 100%;
    background: var(--gold);
    animation: slideLine 2s infinite;
}
@keyframes slideLine { 0%{ left: -100%; } 100%{ left: 100%; } }

/* =====================
   SECTION LABEL STYLE
===================== */
.section-label {
    font-size: 0.68rem;
    letter-spacing: 0.22em;
    text-transform: uppercase;
    color: var(--gold);
    font-weight: 400;
    margin-bottom: 14px;
    display: block;
}
.section-heading {
    font-family: 'Montserrat', sans-serif;
    font-weight: 600;
    font-size: clamp(1.8rem, 4vw, 2.6rem);
    color: var(--dark);
    line-height: 1.25;
}

/* =====================
   PROPERTIES FEATURE STRIP
===================== */
.feature-strip {
    background: var(--cream);
    padding: 80px 0 90px;
}
.feature-strip-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
    align-items: center;
}
.feature-strip-text {
    padding: 40px 60px 40px 0;
}
.feature-strip-text p {
    font-size: 0.9rem;
    color: var(--text-muted);
    line-height: 1.8;
    margin: 16px 0 28px;
    max-width: 420px;
    font-weight: 400;
}
.strip-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    color: var(--gold);
    font-size: 0.78rem;
    font-weight: 400;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    text-decoration: none;
    border-bottom: 1px solid var(--gold);
    padding-bottom: 4px;
    transition: gap 0.3s, color 0.2s;
}
.strip-btn:hover { gap: 16px; color: var(--gold-dark); border-color: var(--gold-dark); }

/* Video tile */
.feature-strip-video {
    position: relative;
    height: 520px;
    overflow: hidden;
    border-radius: 2px;
}
.feature-strip-video video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.feature-strip-video .video-label {
    position: absolute;
    top: 24px;
    right: 24px;
    background: rgba(255,255,255,0.92);
    backdrop-filter: blur(8px);
    padding: 12px 18px;
    border-radius: 2px;
    border-left: 3px solid var(--gold);
}
.video-label .vl-sub { font-size: 0.65rem; letter-spacing: 0.18em; text-transform: uppercase; color: var(--gold); }
.video-label .vl-title { font-family: 'Montserrat', sans-serif; font-size: 1.2rem; color: var(--dark); font-weight: 600; }

/* Second strip - reversed */
.feature-strip-2 { background: var(--cream); }
.feature-strip-grid.reverse { direction: rtl; }
.feature-strip-grid.reverse > * { direction: ltr; }
.feature-strip-grid.reverse .feature-strip-text { padding: 40px 0 40px 60px; }

/* =====================
   SERVICES SECTION
===================== */
.services-section {
    background: var(--dark);
    padding: 90px 0;
}
.services-section .section-label { color: var(--gold-light); }
.services-section .section-heading { color: #fff; }
.services-section .section-heading span { color: var(--gold-light); font-style: italic; }

.service-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1px;
    background: rgba(255,255,255,0.07);
    margin-top: 60px;
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 2px;
    overflow: hidden;
}
.service-card {
    background: var(--dark);
    padding: 44px 36px;
    transition: background 0.35s ease;
    position: relative;
    overflow: hidden;
}
.service-card::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 0;
    height: 2px;
    background: var(--gold);
    transition: width 0.4s ease;
}
.service-card:hover { background: rgba(191,161,88,0.07); }
.service-card:hover::after { width: 100%; }
.service-card .svc-num {
    font-family: 'Montserrat', sans-serif;
    font-size: 3.5rem;
    font-weight: 300;
    color: rgba(191,161,88,0.12);
    line-height: 1;
    margin-bottom: 20px;
    transition: color 0.3s;
}
.service-card:hover .svc-num { color: rgba(191,161,88,0.25); }
.service-card .svc-icon {
    width: 44px;
    height: 44px;
    border: 1px solid rgba(191,161,88,0.3);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    color: var(--gold);
    font-size: 1.1rem;
    transition: background 0.3s, border-color 0.3s;
}
.service-card:hover .svc-icon { background: rgba(191,161,88,0.1); border-color: var(--gold); }
.service-card h5 {
    font-size: 1rem;
    font-weight: 500;
    color: #fff;
    margin-bottom: 12px;
    letter-spacing: 0.02em;
}
.service-card p { font-size: 0.88rem; color: rgba(255,255,255,0.45); line-height: 1.75; }

/* =====================
   RECENT LISTINGS
===================== */
.listings-section {
    padding: 90px 0 100px;
    background: var(--cream);
}
.listings-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 50px;
}
.view-all {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.75rem;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    color: var(--gold);
    text-decoration: none;
    border-bottom: 1px solid var(--gold);
    padding-bottom: 2px;
    transition: gap 0.3s;
}
.view-all:hover { gap: 14px; color: var(--gold-dark); }

.prop-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 28px;
}
.prop-card {
    background: var(--white);
    border-radius: 2px;
    overflow: hidden;
    text-decoration: none;
    color: inherit;
    display: flex;
    flex-direction: column;
    transition: transform 0.35s ease, box-shadow 0.35s ease;
    border: 1px solid rgba(191,161,88,0.1);
}
.prop-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 18px 40px rgba(0,0,0,0.1);
    color: inherit;
}
.prop-img-wrap {
    position: relative;
    height: 230px;
    overflow: hidden;
}
.prop-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s ease;
}
.prop-card:hover .prop-img-wrap img { transform: scale(1.05); }
.prop-tag {
    position: absolute;
    top: 16px;
    left: 16px;
    background: var(--gold);
    color: #fff;
    font-size: 0.65rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    padding: 5px 11px;
    border-radius: 1px;
    font-weight: 400;
}
.prop-body {
    padding: 24px 24px 20px;
    display: flex;
    flex-direction: column;
    flex: 1;
}
.prop-body h6 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1rem;
    font-weight: 800;
    color: var(--dark);
    letter-spacing: 0.03em;
    text-transform: uppercase;
    margin-bottom: 6px;
}
.prop-type {
    font-size: 0.72rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--gold);
    margin-bottom: 10px;
    font-weight: 400;
}
.prop-location {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 0.78rem;
    color: var(--text-muted);
    margin-bottom: 12px;
}
.prop-location i { color: var(--gold); font-size: 0.8rem; }
.prop-desc {
    font-size: 0.85rem;
    color: var(--text-muted);
    line-height: 1.7;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    flex: 1;
    margin-bottom: 20px;
}
.prop-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 18px;
    border-top: 1px solid rgba(191,161,88,0.12);
}
.prop-specs {
    display: flex;
    gap: 14px;
    font-size: 0.78rem;
    color: var(--text-muted);
}
.prop-specs span { display: flex; align-items: center; gap: 4px; }
.prop-specs i { color: var(--gold); font-size: 0.8rem; }
.prop-price {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.3rem;
    font-weight: 600;
    color: var(--gold-dark);
}

/* =====================
   CHAT BUBBLE
===================== */
#chat-bubble {
    position: fixed;
    bottom: 28px;
    right: 28px;
    width: 54px;
    height: 54px;
    background: var(--gold);
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    cursor: pointer;
    box-shadow: 0 6px 20px rgba(191,161,88,0.35);
    z-index: 2000;
    transition: transform 0.3s ease, box-shadow 0.3s;
}
#chat-bubble:hover { transform: scale(1.08); box-shadow: 0 10px 28px rgba(191,161,88,0.45); }

#chat-window {
    position: fixed;
    bottom: 94px;
    right: 28px;
    width: 340px;
    height: 480px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
    display: none;
    flex-direction: column;
    overflow: hidden;
    z-index: 2000;
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

/* =====================
   FOOTER
===================== */
.footer { background-color: var(--dark); color: #fff; width: 100%; padding-top: 50px; padding-bottom: 24px; }
.footer .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.footer-logo-text { font-family: 'Montserrat', sans-serif; font-size: 3rem; font-weight: 700; color: var(--gold); text-align: center; margin: 0 auto; display: block; text-shadow: 1px 1px 2px #fff; }
.footer h6 { font-weight: 500; margin-bottom: 14px; color: #fdd07b; font-size: 0.78rem; letter-spacing: 0.14em; text-transform: uppercase; }
.footer a { color: rgba(255,255,255,0.8); text-decoration: none; font-size: 0.88rem; }
.footer a:hover { color: #fff; text-decoration: underline; }
.footer-divider { width: 40px; height: 1px; background-color: rgba(255,255,255,0.5); border: none; margin: 12px auto 18px; }
.footer-about-text { font-size: 0.87rem; line-height: 1.7; color: rgba(255,255,255,0.8); }
.footer-contact span { font-size: 0.85rem; display: inline-block; margin: 0 6px; }
.footer-contact i { margin-right: 5px; }
.footer-social a { color: #fff; margin: 0 7px; font-size: 1rem; display: inline-flex; width: 38px; height: 38px; align-items: center; justify-content: center; border-radius: 50%; border: 1px solid rgba(255,255,255,0.4); transition: all 0.3s ease; }
.footer-social a:hover { background: #fff; color: var(--gold); transform: scale(1.15); }
.back-to-top { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 0.8rem; letter-spacing: 0.1em; transition: color 0.2s; }
.back-to-top:hover { color: #fff; }

/* =====================
   RESPONSIVE
===================== */
@media (max-width: 992px) {
    .feature-strip-grid, .feature-strip-grid.reverse { grid-template-columns: 1fr; direction: ltr; }
    .feature-strip-text, .feature-strip-grid.reverse .feature-strip-text { padding: 36px 0; }
    .feature-strip-video { height: 340px; }
    .service-cards { grid-template-columns: 1fr; gap: 1px; }
    .prop-grid { grid-template-columns: 1fr; }
    .listings-header { flex-direction: column; align-items: flex-start; gap: 16px; }
}
@media (max-width: 768px) {
    .hero-content { padding: 0 28px; }
    .hero-content h1 { font-size: 2.2rem; }
    .hero-scroll { left: 28px; }
    .service-cards { grid-template-columns: 1fr; }
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

body.dark-mode .navbar .nav-link:hover {
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
body.dark-mode .hero-overlay {
    background: linear-gradient(105deg, rgba(10,10,20,0.85) 0%, rgba(10,10,20,0.5) 65%, transparent 100%);
}

body.dark-mode .hero-content h1 {
    color: #fff;
}

body.dark-mode .hero-content p {
    color: rgba(255,255,255,0.7);
}

body.dark-mode .hero-search {
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.15);
}

body.dark-mode .hero-search select {
    color: #fff;
}

body.dark-mode .hero-search select option {
    background: #1a1a2e;
    color: #fff;
}

body.dark-mode .hero-scroll {
    color: rgba(255,255,255,0.4);
}

body.dark-mode .hero-scroll .line {
    background: rgba(255,255,255,0.2);
}

/* Section headings */
body.dark-mode .section-heading {
    color: #fff;
}

body.dark-mode .section-label {
    color: var(--gold-light);
}

/* Feature Strips */
body.dark-mode .feature-strip {
    background: #1a1a1a;
}

body.dark-mode .feature-strip-2 {
    background: #1a1a1a;
}

body.dark-mode .feature-strip-text p {
    color: #b0b0b0;
}

body.dark-mode .strip-btn {
    color: var(--gold-light);
    border-color: var(--gold-light);
}

body.dark-mode .strip-btn:hover {
    color: var(--gold);
    border-color: var(--gold);
}

body.dark-mode .video-label {
    background: rgba(30,30,30,0.95);
}

body.dark-mode .video-label .vl-title {
    color: #fff;
}

/* Services Section */
body.dark-mode .services-section {
    background: #0d0d0d;
}

body.dark-mode .service-cards {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.05);
}

body.dark-mode .service-card {
    background: #0d0d0d;
}

body.dark-mode .service-card:hover {
    background: rgba(191,161,88,0.1);
}

body.dark-mode .service-card h5 {
    color: #fff;
}

body.dark-mode .service-card p {
    color: rgba(255,255,255,0.5);
}

/* Listings Section */
body.dark-mode .listings-section {
    background: #1a1a1a;
}

body.dark-mode .prop-card {
    background: #1c1c1c;
    border-color: rgba(255,255,255,0.06);
}

body.dark-mode .prop-card:hover {
    box-shadow: 0 18px 40px rgba(0,0,0,0.4);
}

body.dark-mode .prop-body h6 {
    color: #fff;
}

body.dark-mode .prop-location {
    color: #aaa;
}

body.dark-mode .prop-desc {
    color: #aaa;
}

body.dark-mode .prop-footer {
    border-color: rgba(255,255,255,0.08);
}

body.dark-mode .prop-specs {
    color: #aaa;
}

body.dark-mode .prop-price {
    color: var(--gold-light);
}

body.dark-mode .view-all {
    color: var(--gold-light);
    border-color: var(--gold-light);
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

/* Loader */
body.dark-mode #loader {
    background: #121212;
}

body.dark-mode .loading-text {
    color: var(--gold-light);
}

body.dark-mode .progress-bar {
    background: #333;
}

body.dark-mode .body-house {
    background: #ddd;
    border-color: #cccccc;
}
</style>
</head>
<body class="loading">

<!-- Loader -->
<div id="loader">
    <div class="house">
        <div class="roof"></div>
        <div class="body-house"></div>
        <div class="door"></div>
    </div>
    <div class="loading-text">Loading Properties</div>
    <div class="progress-bar"><div class="progress"></div></div>
</div>

<!-- Top Contact -->
<div class="top-contact">
    <div>ITPH.com.ph &nbsp;|&nbsp; (+63) 9123456789</div>
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
        <li class="nav-item"><a class="nav-link" href="">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="user_side/about_us.php">About Us</a></li>
        <li class="nav-item"><a class="nav-link" href="user_side/all_properties.php">Properties</a></li>
        <li class="nav-item"><a class="nav-link" href="user_side/contact_us.php">Contact Us</a></li>
        <!-- CHANGED: News & Blogs (with dropdown) → Media (plain link) -->
        <li class="nav-item"><a class="nav-link active-link" href="user_side/vlogs.php">Media</a></li>
      </ul>

      <?php if(isset($_SESSION['user_id'])): ?>
      <?php
        $nav_fullname = $_SESSION['fullname'] ?? '';
        $nav_initials = strtoupper(implode('', array_map(fn($w) => $w[0], explode(' ', trim($nav_fullname)))));
        $nav_initials = substr($nav_initials, 0, 2);
      ?>
      <div class="dropdown" style="display:flex; align-items:center; gap:10px;">
        <a href="user_side/account.php" title="My Account" style="
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
        <a href="user_side/login.php" class="btn btn-reserve">Log in</a>
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

<!-- ========================
     HERO
======================== -->
<section class="hero" id="top">
    <img src="photo/nbg2.png" alt="Iloilo property" class="hero-img">
    <div class="hero-overlay"></div>

    <div class="hero-content">
        <span class="hero-eyebrow">Iloilo Top Property Homes</span>
        <h1>Bringing quality living<br><em>closer to your future.</em></h1>
        <p>Beautiful houses within well-planned subdivisions in Iloilo — a safe environment and modern living, crafted for you.</p>

        <form class="hero-search" action="user_side/redirect.php" method="GET">
            <select name="property" required>
                <option disabled selected>Explore a Property</option>
                <option value="phrst.php">PHIRST Homes</option>
               
            </select>
            <button type="submit"><i class="bi bi-arrow-right"></i></button>
        </form>
    </div>

    <div class="hero-scroll">
        <div class="line"></div>
        Scroll to explore
    </div>
</section>



<!-- ========================
     FEATURE STRIP — SLIDE 1
======================== -->
<section class="feature-strip">
    <div class="container">
        <div class="feature-strip-grid">
            <div class="feature-strip-text" data-aos="fade-right" data-aos-duration="900">
                <span class="section-label">House &amp; Lot</span>
                <h2 class="section-heading">Amani<br>Mid Unit</h2>
                <p>A premium thematic community committed to providing a good life. Every detail within Amani is crafted for comfort, aesthetics, and lasting value.</p>
                <a href="user_side/monticello.php" class="strip-btn">Discover the Village <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="feature-strip-video" data-aos="fade-left" data-aos-duration="900">
                <video id="videoSlide1" autoplay muted playsinline loop>
                    <source src="photo/uploads/amani.mp4" type="video/mp4">
                </video>
                <div class="video-label">
                    <div class="vl-sub">Now Available</div>
                    <div class="vl-title">PHIRST</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========================
     FEATURE STRIP — SLIDE 2
======================== 
<section class="feature-strip feature-strip-2">
    <div class="container">
        <div class="feature-strip-grid reverse">
            <div class="feature-strip-text" data-aos="fade-left" data-aos-duration="900">
                <span class="section-label">House &amp; Lot</span>
                <h2 class="section-heading">Alice Intimo<br>Collection</h2>
                <p>Modern, premium homes in prime locations designed for a luxurious city lifestyle. Sophisticated architecture meets thoughtful community planning.</p>
                <a href="user_side/amani.php" class="strip-btn">Explore the Collection <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="feature-strip-video" data-aos="fade-right" data-aos-duration="900">
                <video id="videoSlide2" autoplay muted playsinline loop>
                    <source src="uploads/awtp1.mp4" type="video/mp4">
                </video>
                <div class="video-label">
                    <div class="vl-sub">Now Available</div>
                    <div class="vl-title">Alice Intimo</div>
                </div>
            </div>
        </div>
    </div>
</section>-->

<!-- ========================
     SERVICES
======================== -->
<section class="services-section">
    <div class="container">
        <span class="section-label" data-aos="fade-up">What We Offer</span>
        <h2 class="section-heading" data-aos="fade-up" data-aos-delay="100">
            Services built around<br><span>your home journey.</span>
        </h2>
        <div class="service-cards" data-aos="fade-up" data-aos-delay="200">
            <div class="service-card">
                <div class="svc-num">01</div>
                <div class="svc-icon"><i class="bi bi-person-check"></i></div>
                <h5>Trusted Agents</h5>
                <p>Professional, licensed agents ready to guide you every step of the way — from inquiry to turnover.</p>
            </div>
            <div class="service-card">
                <div class="svc-num">02</div>
                <div class="svc-icon"><i class="bi bi-building"></i></div>
                <h5>Premium Properties</h5>
                <p>Thoughtfully designed homes in well-planned communities that elevate everyday living.</p>
            </div>
            <div class="service-card">
                <div class="svc-num">03</div>
                <div class="svc-icon"><i class="bi bi-cash-stack"></i></div>
                <h5>Investment Opportunities</h5>
                <p>Grow your wealth through exclusive real estate investments with long-term returns in Iloilo's growing market.</p>
            </div>
        </div>
    </div>
</section>

<!-- ========================
     RECENT LISTINGS
======================== -->
<section class="listings-section">
    <div class="container">
        <div class="listings-header">
            <div data-aos="fade-up">
                <span class="section-label">Available Now</span>
                <h2 class="section-heading">Recent Listings</h2>
            </div>
            <a href="user_side/all_properties.php" class="view-all" data-aos="fade-up">
                View all <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="prop-grid">
            <?php if (!empty($properties)): ?>
                <?php foreach ($properties as $prop): ?>
                    <?php
                        $title = !empty($prop['title']) ? $prop['title'] : 'Property';
                        $propertyPage = !empty($prop['property_page']) ? $prop['property_page'] : '#';
                        $location = !empty($prop['location']) ? $prop['location'] : 'Iloilo';
                        $description = !empty($prop['description']) ? $prop['description'] : 'No description available.';
                        $first_image = 'image_7.jpg';
                        if (!empty($prop['gallery_images'])) $first_image = $prop['gallery_images'][0];
                    ?>
                    <a href="user_side/<?= htmlspecialchars($propertyPage) ?>.php" class="prop-card" data-aos="fade-up" data-aos-delay="<?= $loop++ * 80 ?>">
                        <div class="prop-img-wrap">
                            <img src="photo/uploads/<?= htmlspecialchars($first_image) ?>" alt="<?= htmlspecialchars($title) ?>">
                            <span class="prop-tag"><?= htmlspecialchars($propertyPage) ?></span>
                        </div>
                        <div class="prop-body">
                            <h6 style="text-transform:uppercase;"><?= htmlspecialchars($title) ?></h6>
                            <div class="prop-type"><?= htmlspecialchars($propertyPage) ?></div>
                            <div class="prop-location">
                                <i class="bi bi-geo-alt-fill"></i>
                                <?= htmlspecialchars($location) ?>
                            </div>
                            <p class="prop-desc"><?= htmlspecialchars($description) ?></p>
                            <div class="prop-footer">
                                <div class="prop-specs">
                                    <span><i class="bi bi-house-door"></i> <?= htmlspecialchars($prop['bedrooms']) ?> Bedroom</span>
                                    <span><i class="bi bi-droplet"></i> <?= htmlspecialchars($prop['bathrooms']) ?> Bathroom</span>
                                </div>
                                <div class="prop-price">₱<?= number_format($prop['price'], 0) ?></div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center" style="grid-column:1/-1; color:var(--text-muted);">No properties found at this time.</p>
            <?php endif; ?>

            
        </div>
    </div>
</section>

<!-- ========================
     FOOTER
======================== -->
<footer class="footer mt-0">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4 text-center text-md-start">
                <div class="footer-logo-text mb-2">ITPH</div>
                <hr class="footer-divider">
                <p class="footer-about-text">Bringing quality living closer to your future. Beautiful houses within well-planned subdivisions in Iloilo, providing a safe environment and modern living for homeowners.</p>
            </div>
            <div class="col-md-2 mb-4">
                <h6>Quick Links</h6>
                <ul class="list-unstyled" style="line-height:2;">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="user_side/about_us.php">About Us</a></li>
                    <li><a href="user_side/news.php">Latest News</a></li>
                    <li><a href="vlogs.php">Vlogs</a></li>
                </ul>
            </div>
            <div class="col-md-2 mb-4">
                <h6>Properties</h6>
                <ul class="list-unstyled" style="line-height:2;">
                    <li><a href="user_side/monticello.php">Monticello</a></li>
                    <li><a href="user_side/amani.php">Amani Homes</a></li>
                </ul>
            </div>
            <div class="col-md-4 mb-4">
                <h6>Tools</h6>
                <ul class="list-unstyled" style="line-height:2;">
                    <li><a href="user_side/contact_us.php">Contact Us</a></li>
                    <li><a href="user_side/reservation.php">Reserve Now</a></li>
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

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
AOS.init({ once: true, offset: 60 });

// Navbar scroll
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

// Hero image scale-in on load
window.addEventListener('load', () => {
    document.querySelector('.hero').classList.add('loaded');
});

// Loader
window.addEventListener('load', () => {
    const loader = document.getElementById('loader');
    setTimeout(() => {
        loader.style.opacity = '0';
        setTimeout(() => {
            loader.style.display = 'none';
            document.body.classList.remove('loading');
        }, 600);
    }, 500);
});
setTimeout(() => {
    const loader = document.getElementById('loader');
    if (loader) { loader.style.display = 'none'; document.body.classList.remove('loading'); }
}, 3500);

// Video IntersectionObserver
const videos = document.querySelectorAll('video');
const observer = new IntersectionObserver(entries => {
    entries.forEach(e => { if (e.isIntersecting) e.target.play(); else e.target.pause(); });
}, { threshold: 0.4 });
videos.forEach(v => observer.observe(v));

// Chat
function toggleChat() {
    const cw = document.getElementById('chat-window');
    cw.style.display = cw.style.display === 'flex' ? 'none' : 'flex';
}

async function sendChat() {
    const field = document.getElementById('user-input-field');
    const msgs = document.getElementById('chat-messages');
    const msg = field.value.trim();
    if (!msg) return;
    msgs.innerHTML += `<div class="msg msg-user">${msg}</div>`;
    field.value = '';
    msgs.scrollTop = msgs.scrollHeight;
    const lid = 'loading-' + Date.now();
    msgs.innerHTML += `<div class="msg msg-bot" id="${lid}">Typing…</div>`;
    msgs.scrollTop = msgs.scrollHeight;
    try {
        const fd = new FormData();
        fd.append('message', msg);
        const res = await fetch('../backends/chat.php', { method: 'POST', body: fd });
        const data = await res.json();
        document.getElementById(lid).innerText = data.reply;
    } catch {
        document.getElementById(lid).innerText = "I'm having trouble connecting. Please try again.";
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