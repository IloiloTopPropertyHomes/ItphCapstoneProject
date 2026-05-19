<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://www.gstatic.com https://cdn.botpress.cloud https://files.bpcontent.cloud; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; img-src 'self' data: https:; connect-src 'self' https://cdn.botpress.cloud https://files.bpcontent.cloud wss://*.botpress.cloud https://*.botpress.cloud; frame-src https://cdn.botpress.cloud; frame-ancestors 'self'; base-uri 'self';");
header("X-Content-Type-Options: nosniff"); 
header("X-Frame-Options: SAMEORIGIN"); 
header("Referrer-Policy: no-referrer-when-downgrade");
session_start();
require_once __DIR__ . '/../backends/config.php';
$conn = get_db_connection();

if(!isset($_GET['id'])){
    die("Property not found.");
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM propertiies WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$prop = $result->fetch_assoc();

if(!$prop){
    die("Property not found."); 
}

$property_page = $prop['property_page'] ?? '';
$type_map = [
    'monticello' => 'Monticello Homes Pavia',
    'amani'      => 'Amani Homes',
];
$type = $type_map[$property_page] ?? 'Unknown Type';

$title       = $prop['title'];
$location    = $prop['location'];
$description = $prop['description'];
$price       = $prop['price'];
$bedrooms    = $prop['bedrooms'];
$bathrooms   = $prop['bathrooms'];
$avail_units = $prop['available_units'] ?? 0;

$images = [];
$imageQuery = $conn->prepare("SELECT image FROM property_images WHERE property_id=?");
$imageQuery->bind_param("i", $id);
$imageQuery->execute();
$imageResult = $imageQuery->get_result();
while($img = $imageResult->fetch_assoc()){
    $images[] = decrypt_data($img['image']);
}

$stmt = $conn->prepare("UPDATE propertiies SET views = views + 1 WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

// Check profile completeness if logged in
$profileIncomplete = false;
if(isset($_SESSION['user_id'])){
    $userStmt = $conn->prepare("SELECT fullname, email, phone FROM customers WHERE id=?");
    $userStmt->bind_param("i", $_SESSION['user_id']);
    $userStmt->execute();
    $userData = $userStmt->get_result()->fetch_assoc();
    $userStmt->close();
    $profileIncomplete = empty($userData['fullname']) || empty($userData['email']) || empty($userData['phone']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
       <script src="https://cdn.botpress.cloud/webchat/v3.6/inject.js"></script>
<script src="https://files.bpcontent.cloud/2026/05/13/12/20260513123611-N0BSRPKC.js" defer></script>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($title) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/view_property.css">
</head>
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

/* =====================
   BASE
===================== */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Montserrat', sans-serif;
    font-weight: 400;
    color: var(--text);
    background: var(--white);
}

/* =====================
   TOP CONTACT BAR
===================== */
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
    transition: transform 0.3s ease, opacity 0.3s ease;
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    letter-spacing: 0.04em;
    border-bottom: 1px solid rgba(191,161,88,0.15);
}
.top-contact.hidden { transform: translateY(-100%); opacity: 0; }
.top-contact .social-icons a { margin-left: 14px; color: #555; transition: color 0.2s; }
.top-contact .social-icons a:hover { color: var(--gold); }

/* =====================
   NAVBAR
===================== */
.navbar {
    position: fixed;
    top: 30px;
    width: 100%;
    z-index: 1040;
    background: rgba(255,255,255,0.82);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    transition: top 0.4s ease, box-shadow 0.4s ease;
    border-bottom: 1px solid rgba(191,161,88,0.12);
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
.navbar .nav-link:hover { color: var(--gold); }
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
.navbar .btn-reserve:hover {
    background: transparent;
    border-color: var(--gold);
    color: var(--gold);
}
.dropdown-menu { background: #fafaf7; border: 1px solid rgba(191,161,88,0.15); border-radius: 4px; }
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
.footer { background-color: var(--gold); color: #fff; width: 100%; padding-top: 50px; padding-bottom: 24px; }
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
 /* =========================
   CAROUSEL
========================= */
.carousel-section {
    position: relative;
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 30px;
}

.carousel-img-wrapper {
    position: relative;
    width: 100%;
    height: 680px;
    overflow: hidden;
}

.carousel-img-wrapper::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(to bottom, transparent 50%, rgba(10,9,6,.5) 100%);
    pointer-events: none;
    z-index: 1;
}

#propertyCarousel { position: relative; }
.carousel-inner { border-radius: 0; }

.carousel-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 7s ease, opacity .9s ease;
    transform: scale(1.03);
}

.carousel-item.active .carousel-img { transform: scale(1); }

/* Dot indicators */
.carousel-dots {
    position: absolute;
    bottom: 24px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 8px;
    z-index: 10;
}

.carousel-dot {
    width: 28px;
    height: 3px;
    background: rgba(255,255,255,.4);
    border-radius: 2px;
    cursor: pointer;
    transition: background .3s, width .3s;
}

.carousel-dot.active {
    background: #bfa158;
    width: 44px;
}

/* Counter */
.carousel-counter {
    position: absolute;
    bottom: 24px;
    right: 44px;
    z-index: 10;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: 2px;
    color: rgba(255,255,255,.75);
    text-transform: uppercase;
}

/* Arrows */
.custom-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    justify-content: center;
    align-items: center;
    width: 52px;
    height: 52px;
    background: rgba(255,255,255,.12);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.3);
    border-radius: 50%;
    padding: 0;
    cursor: pointer;
    z-index: 10;
    font-size: 1.1rem;
    color: #fff;
    opacity: 0;
    transition: opacity .3s, background .3s, transform .3s;
}
/* =========================
   IMAGE VIEWER (FACEBOOK STYLE)
========================= */
.image-viewer {
    display: none;
    position: fixed;
    z-index: 9999;
    inset: 0;
    background: rgba(0,0,0,0.95);
    justify-content: center;
    align-items: center;
}

.image-viewer img {
    max-width: 90%;
    max-height: 90%;
    object-fit: contain;
    animation: zoomIn .3s ease;
}

@keyframes zoomIn {
    from { transform: scale(0.9); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.close-viewer {
    position: absolute;
    top: 20px;
    right: 30px;
    font-size: 40px;
    color: #fff;
    cursor: pointer;
}

.viewer-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    font-size: 40px;
    color: #fff;
    background: rgba(255,255,255,0.1);
    border: none;
    padding: 10px 16px;
    cursor: pointer;
}

.viewer-arrow.left { left: 20px; }
.viewer-arrow.right { right: 20px; }

.viewer-arrow:hover {
    background: rgba(255,255,255,0.3);
}

.clickable-img {
    cursor: zoom-in;
}

#propertyCarousel:hover .custom-arrow { opacity: 1; }
.prev-arrow { left: 20px; }
.next-arrow { right: 20px; }

.custom-arrow:hover {
    background: rgba(191,161,88,.8);
    border-color: #bfa158;
    transform: translateY(-50%) scale(1.08);
}

/* =========================
   LAYOUT GRID
========================= */
.property-layout {
    max-width: 1200px;
    margin: 56px auto 80px;
    padding: 0 30px;
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 60px;
}

/* =========================
   LEFT — CONTENT
========================= */
.property-content {
    animation: fadeUp .7s ease both;
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(28px); }
    to   { opacity: 1; transform: translateY(0); }
}

.property-community {
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: 3.5px;
    color: #bfa158;
    text-transform: uppercase;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.property-community::before {
    content: '';
    display: inline-block;
    width: 32px;
    height: 1px;
    background: #bfa158;
}

.property-main-title {
    font-family: 'Cormorant Garamond', Garamond, Georgia, serif;
    font-size: 3.2rem;
    font-weight: 400;
    letter-spacing: 1px;
    color: #1a1a16;
    line-height: 1.12;
    margin: 0 0 22px 0;
    text-transform: uppercase;
}

.property-meta-row {
    display: flex;
    align-items: center;
    gap: 28px;
    margin-bottom: 28px;
    flex-wrap: wrap;
}

.property-meta-item {
    display: flex;
    align-items: center;
    gap: 7px;
    font-size: .82rem;
    font-weight: 600;
    letter-spacing: 1.5px;
    color: #555;
    text-transform: uppercase;
}

.property-meta-item i { color: #bfa158; font-size: 1rem; }

.meta-divider {
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: #bfa158;
    flex-shrink: 0;
}

.description-section {
    border-top: 1px solid #e0ddd3;
    padding-top: 32px;
}

.discover-title {
    font-family: 'Cormorant Garamond', Garamond, Georgia, serif;
    font-size: 1.9rem;
    font-weight: 400;
    font-style: italic;
    color: #bfa158;
    margin: 0 0 18px 0;
}

.property-description {
    font-size: .96rem;
    line-height: 1.85;
    color: #555;
    max-width: 600px;
}

/* =========================
   RIGHT — PRICE CARD
========================= */
.property-sidebar {
    animation: fadeUp .7s .15s ease both;
}

.price-card {
    background: #fff;
    border: 1px solid #e6e2d7;
    border-top: 3px solid #bfa158;
    padding: 36px 32px 32px;
    position: sticky;
    top: 140px;
}

.price-card-label {
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: 3px;
    color: #bfa158;
    text-transform: uppercase;
    margin-bottom: 8px;
}

.price-card-value {
    font-family: 'Cormorant Garamond', Garamond, Georgia, serif;
    font-size: 2.4rem;
    font-weight: 600;
    color: #1a1a16;
    line-height: 1;
    margin-bottom: 28px;
}

.price-card-divider {
    height: 1px;
    background: #e6e2d7;
    margin: 0 0 24px;
}

.specs-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 28px;
}

.spec-item { display: flex; flex-direction: column; gap: 4px; }

.spec-label {
    font-size: .68rem;
    font-weight: 700;
    letter-spacing: 2px;
    color: #999;
    text-transform: uppercase;
}

.spec-value {
    font-size: 1.05rem;
    font-weight: 600;
    color: #1a1a16;
}

.units-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f6f0e3;
    color: #8a6d2a;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    padding: 7px 14px;
    border-radius: 2px;
    margin-bottom: 20px;
}

/* Primary CTA */
.btn-vpreserve {
    display: block;
    width: 100%;
    text-align: center;
    background: #bfa158;
    border: 1px solid #bfa158;
    color: #fff;
    padding: 15px 20px;
    border-radius: 0;
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    text-decoration: none;
    transition: background .3s, color .3s, transform .2s;
    cursor: pointer;
}

.btn-vpreserve:hover {
    background: transparent;
    color: #bfa158;
    transform: translateY(-2px);
    text-decoration: none;
}

/* Secondary outline */
.btn-card-outline {
    display: block;
    width: 100%;
    text-align: center;
    background: transparent;
    border: 1px solid #ccc;
    color: #888;
    padding: 14px 20px;
    border-radius: 0;
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    cursor: pointer;
    margin-top: 10px;
    transition: background .3s, color .3s, border-color .3s;
    text-decoration: none;
}

.btn-card-outline:hover {
    border-color: #bfa158;
    color: #bfa158;
    text-decoration: none;
}

/* Sold out state */
.btn-sold-out {
    display: block;
    width: 100%;
    text-align: center;
    background: #e0ddd3;
    border: none;
    color: #999;
    padding: 15px 20px;
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
    cursor: not-allowed;
    border-radius: 0;
}

.login-prompt {
    text-align: center;
    margin-top: 14px;
    font-size: .8rem;
    color: #999;
}

.login-prompt a { color: #bfa158; }



/* =========================
   RESPONSIVE
========================= */
@media (max-width: 1024px) {
    .property-layout { grid-template-columns: 1fr; gap: 40px; }
    .price-card { position: static; }
    .carousel-img-wrapper { height: 500px; }
}

@media (max-width: 768px) {
    .property-main-title { font-size: 2.2rem; }
    .carousel-img-wrapper { height: 360px; }
    .carousel-section { padding: 0; }
    .property-layout { padding: 0 20px; margin-top: 36px; }
    .prev-arrow { left: 10px; }
    .next-arrow { right: 10px; }
}

@media (max-width: 576px) {
    .carousel-img-wrapper { height: 260px; }
    .property-main-title { font-size: 1.8rem; }
    .specs-grid { grid-template-columns: 1fr; }
}

/* =====================
   DARK MODE SWITCH
===================== */

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

/* BODY */
body.dark-mode{
    background:#121212;
    color:#e5e5e5;
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

/* TOP CONTACT */
body.dark-mode .top-contact{
    background:rgba(18,18,18,0.9);
    border-bottom:1px solid rgba(255,255,255,0.08);
    color:#ddd;
}

body.dark-mode .top-contact .social-icons a{
    color:#ddd;
}

/* NAVBAR */
body.dark-mode .navbar{
    background:rgba(20,20,20,0.9);
    border-bottom:1px solid rgba(255,255,255,0.06);
}

body.dark-mode .navbar .nav-link{
    color:#f1f1f1;
}

body.dark-mode .navbar .nav-link:hover{
    color:var(--gold-light);
}

body.dark-mode .dropdown-menu{
    background:#1d1d1d;
    border:1px solid rgba(255,255,255,0.08);
}

body.dark-mode .dropdown-menu p,
body.dark-mode .dropdown-menu h6{
    color:#f1f1f1 !important;
}

/* PROPERTY CONTENT */
body.dark-mode .property-main-title,
body.dark-mode .spec-value,
body.dark-mode .price-card-value{
    color:#fff;
}

body.dark-mode .property-description,
body.dark-mode .property-meta-item{
    color:#cfcfcf;
}

body.dark-mode .description-section{
    border-top:1px solid rgba(255,255,255,0.08);
}

/* PRICE CARD */
body.dark-mode .price-card{
    background:#1c1c1c;
    border:1px solid rgba(255,255,255,0.08);
}

body.dark-mode .price-card-divider{
    background:rgba(255,255,255,0.08);
}

body.dark-mode .spec-label{
    color:#999;
}

/* CHAT */
body.dark-mode #chat-window{
    background:#1a1a1a;
    border:1px solid rgba(255,255,255,0.08);
}

body.dark-mode #chat-messages{
    background:#111;
}

body.dark-mode .msg-bot{
    background:#2a2a2a;
    color:#f1f1f1;
}

body.dark-mode .chat-input-area{
    background:#1a1a1a;
    border-top:1px solid rgba(255,255,255,0.08);
}

body.dark-mode .chat-input-area input{
    background:#2a2a2a;
    border:1px solid rgba(255,255,255,0.08);
    color:#fff;
}

/* FOOTER */
body.dark-mode .footer{
    background:#171717;
}

body.dark-mode .footer-about-text,
body.dark-mode .footer a,
body.dark-mode .footer-contact span{
    color:#d4d4d4;
}

/* IMAGE VIEWER */
body.dark-mode .image-viewer{
    background:rgba(0,0,0,0.97);
}
</style>
<body>

<!-- TOP CONTACT -->
<div class="top-contact">
    <div>ITPH.com.ph | (+63) 927 933 3923</div>
    <div>
        <i class="bi bi-facebook me-2"></i>
        <i class="bi bi-instagram me-2"></i>
        <i class="bi bi-tiktok"></i>
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



<!-- ═══════════════════ CAROUSEL ═══════════════════ -->
<div class="carousel-section">
  <div id="propertyCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="false">
    <div class="carousel-img-wrapper">
      <div class="carousel-inner" style="height:100%;">
        <?php $active = true; foreach($images as $img): ?>
        <div class="carousel-item <?= $active ? 'active' : '' ?>" style="height:100%;">
          <img class="carousel-img clickable-img"
     src="../photo/uploads/<?= htmlspecialchars($img) ?>"
     alt="<?= htmlspecialchars($title) ?>"
     data-index="<?= $active ? 0 : array_search($img, $images) ?>">
        </div>
        <?php $active = false; endforeach; ?>
      </div>

      <button class="custom-arrow prev-arrow" aria-label="Previous"><i class="bi bi-chevron-left"></i></button>
      <button class="custom-arrow next-arrow" aria-label="Next"><i class="bi bi-chevron-right"></i></button>

      <div class="carousel-dots" id="carouselDots"></div>
      <div class="carousel-counter" id="carouselCounter">1 / <?= count($images) ?></div>
    </div>
  </div>
</div>
<!-- IMAGE VIEWER MODAL -->
<div id="imageViewer" class="image-viewer">
    <span class="close-viewer">&times;</span>

    <button class="viewer-arrow left">&#10094;</button>
    <img id="viewerImg" src="">
    <button class="viewer-arrow right">&#10095;</button>
</div>

<!-- ═══════════════════ CONTENT GRID ═══════════════════ -->
<div class="property-layout">

  <!-- LEFT: Property Details -->
  <div class="property-content">
    <p class="property-community"><?= htmlspecialchars($type) ?></p>
    <h1 class="property-main-title"><?= htmlspecialchars($title) ?></h1>

    <div class="property-meta-row">
      <div class="property-meta-item">
        <i class="bi bi-geo-alt-fill"></i>
        <span><?= htmlspecialchars($location) ?></span>
      </div>
      <div class="meta-divider"></div>
      <div class="property-meta-item">
        <i class="bi bi-door-open"></i>
        <span><?= $bedrooms ?> Bedrooms</span>
      </div>
      <div class="meta-divider"></div>
      <div class="property-meta-item">
        <i class="bi bi-droplet"></i>
        <span><?= $bathrooms ?> Bathrooms</span>
      </div>
    </div>

    <div class="description-section">
      <h3 class="discover-title">Discover your dream space</h3>
      <p class="property-description"><?= htmlspecialchars($description) ?></p>
    </div>
  </div>

  <!-- RIGHT: Price Card -->
  <div class="property-sidebar">
    <div class="price-card">

      <p class="price-card-label">Price Range From</p>
      <p class="price-card-value">PHP <?= number_format($price) ?></p>

      <div class="price-card-divider"></div>

      <div class="specs-grid">
        <div class="spec-item">
          <span class="spec-label">Bedrooms</span>
          <span class="spec-value"><?= $bedrooms ?></span>
        </div>
        <div class="spec-item">
          <span class="spec-label">Bathrooms</span>
          <span class="spec-value"><?= $bathrooms ?></span>
        </div>
        <div class="spec-item">
          <span class="spec-label">Location</span>
          <span class="spec-value"><?= htmlspecialchars($location) ?></span>
        </div>
        <div class="spec-item">
          <span class="spec-label">Type</span>
          <span class="spec-value"><?= htmlspecialchars($type) ?></span>
        </div>
      </div>

      <div class="price-card-divider"></div>

      <?php if($avail_units > 0): ?>
        <div class="units-badge">
          <i class="bi bi-house-check-fill"></i>
          <span><?= $avail_units ?> Unit<?= $avail_units > 1 ? 's' : '' ?> Available</span>
        </div>

        <?php if(isset($_SESSION['user_id'])): ?>
          <?php if($profileIncomplete): ?>
            <a href="account.php" class="btn-vpreserve">Complete Account First</a>
          <?php else: ?>
            <a href="reservation.php?house=<?= urlencode($title) ?>&property_page=<?= urlencode($prop['property_page']) ?>"
               class="btn-vpreserve">Book Appointment</a>
          <?php endif; ?>
        <?php else: ?>
          <a href="../user_side/login.php" class="btn-vpreserve">Log in to Book</a>
          <p class="login-prompt">Not a member? <a href="../user_side/register.php">Create an account</a></p>
        <?php endif; ?>

      <?php else: ?>
        <div class="units-badge" style="background:#f0eded; color:#999;">
          <i class="bi bi-house-x-fill"></i>
          <span>No Units Available</span>
        </div>
        <div class="btn-sold-out">Sold Out</div>
      <?php endif; ?>

    </div>
  </div>

</div>
<!-- ═══════════════════ END CONTENT GRID ═══════════════════ -->

<!-- FOOTER -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4 text-center">
                <div class="footer-logo-text mb-2">ITPH</div>
                <hr class="footer-divider">
                <p class="footer-about-text">Bringing quality living closer to your future. Iloilo Top Property Homes presents beautiful houses within well-planned subdivisions in Iloilo, providing a safe environment and modern living for homeowners.</p>
            </div>
            <div class="col-md-2 mb-4">
                <h6>Quick Links</h6>
                <ul class="list-unstyled">
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="../user_side/about_us.php">About Us</a></li>
                    <li><a href="../user_side/news.php">Latest News</a></li>
                    <li><a href="../user_side/vlogs.php">Vlogs</a></li>
                </ul>
            </div>
            <div class="col-md-2 mb-4">
                <h6>Properties</h6>
                <ul class="list-unstyled">
                    <li><a href="../user_side/monticello.php">Monticello</a></li>
                    <li><a href="../user_side/amani.php">Amani Homes</a></li>
                </ul>
            </div>
            <div class="col-md-4 mb-4">
                <h6>Tools</h6>
                <ul class="list-unstyled">
                    <li><a href="../user_side/contact_us.php">Contact Us</a></li>
                    <li><a href="../user_side/reservation.php">Reserve Now</a></li>
                </ul>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12 text-center">
                <a href="#top" class="back-to-top">➤ Back to Top</a>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12 text-center footer-contact">
                <span><i class="bi bi-geo-alt-fill"></i> Pavia, Iloilo City</span>
                &nbsp;|&nbsp;
                <span><i class="bi bi-envelope-fill"></i> ITPH.com</span>
                &nbsp;|&nbsp;
                <span><i class="bi bi-telephone-fill"></i> (+63) 912 345 6789</span>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12 text-center footer-social">
                <a href="https://www.facebook.com/profile.php?id=61558274993355"><i class="bi bi-facebook"></i></a>
                <a href="#"><i class="bi bi-instagram"></i></a>
                <a href="#"><i class="bi bi-tiktok"></i></a>
            </div>
        </div>
        <hr class="footer-bottom-divider">
        <div class="row">
            <div class="col-12 text-center bottom-footer">
                © 2026 Iloilo Top Property Homes. All rights reserved.
                <a href="#">Privacy Policy</a> | <a href="#">Terms and Conditions</a>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const carouselEl = document.getElementById('propertyCarousel');
    const bsCarousel = new bootstrap.Carousel(carouselEl, { interval: 3000, wrap: true });

    const items = carouselEl.querySelectorAll('.carousel-item');
    const total = items.length;
    const dotsContainer = document.getElementById('carouselDots');
    const counter = document.getElementById('carouselCounter');

    items.forEach((_, i) => {
        const d = document.createElement('div');
        d.className = 'carousel-dot' + (i === 0 ? ' active' : '');
        d.addEventListener('click', () => bsCarousel.to(i));
        dotsContainer.appendChild(d);
    });

    carouselEl.querySelector('.prev-arrow').addEventListener('click', () => bsCarousel.prev());
    carouselEl.querySelector('.next-arrow').addEventListener('click', () => bsCarousel.next());

    carouselEl.addEventListener('slid.bs.carousel', function (e) {
        const idx = e.to;
        dotsContainer.querySelectorAll('.carousel-dot').forEach((d, i) => d.classList.toggle('active', i === idx));
        counter.textContent = (idx + 1) + ' / ' + total;
    });

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
});

function toggleChat() {
    const chatWin = document.getElementById('chat-window');
    chatWin.style.display = (chatWin.style.display === 'flex') ? 'none' : 'flex';
}

async function sendChat() {
    const inputField = document.getElementById('user-input-field');
    const chatMessages = document.getElementById('chat-messages');
    const message = inputField.value.trim();
    if (!message) return;

    chatMessages.innerHTML += `<div class="msg msg-user">${message}</div>`;
    inputField.value = '';
    chatMessages.scrollTop = chatMessages.scrollHeight;

    const loadingId = "loading-" + Date.now();
    chatMessages.innerHTML += `<div class="msg msg-bot" id="${loadingId}">Typing...</div>`;
    chatMessages.scrollTop = chatMessages.scrollHeight;

    try {
        const formData = new FormData();
        formData.append('message', message);
        const response = await fetch('../backends/chat.php', { method: 'POST', body: formData });
        const data = await response.json();
        document.getElementById(loadingId).innerText = data.reply;
    } catch (error) {
        document.getElementById(loadingId).innerText = "I'm sorry, I'm having trouble connecting. Please try again later.";
    }
    chatMessages.scrollTop = chatMessages.scrollHeight;
}
// IMAGE VIEWER LOGIC
const viewer = document.getElementById('imageViewer');
const viewerImg = document.getElementById('viewerImg');
const closeViewer = document.querySelector('.close-viewer');
const images = document.querySelectorAll('.clickable-img');

let currentIndex = 0;
let imgArray = [];

images.forEach((img, index) => {
    imgArray.push(img.src);

    img.addEventListener('click', () => {
        currentIndex = index;
        openViewer();
    });
});

function openViewer() {
    viewer.style.display = 'flex';
    viewerImg.src = imgArray[currentIndex];
}

function closeViewerFunc() {
    viewer.style.display = 'none';
}

function showNext() {
    currentIndex = (currentIndex + 1) % imgArray.length;
    viewerImg.src = imgArray[currentIndex];
}

function showPrev() {
    currentIndex = (currentIndex - 1 + imgArray.length) % imgArray.length;
    viewerImg.src = imgArray[currentIndex];
}

document.querySelector('.viewer-arrow.right').onclick = showNext;
document.querySelector('.viewer-arrow.left').onclick = showPrev;

closeViewer.onclick = closeViewerFunc;

// click outside image closes
viewer.addEventListener('click', (e) => {
    if (e.target === viewer) closeViewerFunc();
});

// keyboard support
document.addEventListener('keydown', (e) => {
    if (viewer.style.display === 'flex') {
        if (e.key === 'ArrowRight') showNext();
        if (e.key === 'ArrowLeft') showPrev();
        if (e.key === 'Escape') closeViewerFunc();
    }
});
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