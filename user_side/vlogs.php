<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://www.gstatic.com https://cdn.botpress.cloud https://files.bpcontent.cloud; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; img-src 'self' data: https:; connect-src 'self' https://cdn.botpress.cloud https://files.bpcontent.cloud wss://*.botpress.cloud https://*.botpress.cloud; frame-src https://cdn.botpress.cloud; frame-ancestors 'self'; base-uri 'self';");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: no-referrer-when-downgrade");

session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => !empty($_SERVER['HTTPS']),
    'cookie_samesite' => 'Strict'
]);

require_once '../backends/config.php';
$conn = get_db_connection();

$vlogs_result = $conn->query("SELECT * FROM vlogs ORDER BY created_at DESC");

$news_result  = $conn->query("SELECT * FROM news ORDER BY created_at DESC LIMIT 6");
$news_items   = [];
if ($news_result) {
    while ($nr = $news_result->fetch_assoc()) {
        $news_items[] = $nr;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Media — ITPH</title>
   <script src="https://cdn.botpress.cloud/webchat/v3.6/inject.js"></script>
<script src="https://files.bpcontent.cloud/2026/05/13/12/20260513123611-N0BSRPKC.js" defer></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400;1,600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
<link rel="stylesheet" href="css/common.css">

<style>
/* ======================================================
   ITPH MEDIA — VLOGS.PHP
   Aesthetic: Refined Luxury Editorial
   ====================================================== */

:root {
    --gold: #bfa158;
    --gold-dark: #8c7a45;
    --gold-light: #d4b97a;
    --gold-subtle: rgba(191,161,88,0.12);
    --dark: #1a1a2e;
    --dark-2: #16161e;
    --dark-3: #1e1e28;
    --green: #185c3d;
    --lightgreen: #0D2B1F;
    --cream: #f6f6f0;
    --cream-2: #f0ede3;
    --text: #3a3a50;
    --text-muted: #7a7a8a;
    --white: #ffffff;
    --border: rgba(191,161,88,0.18);
    --radius: 4px;
    --shadow-card: 0 8px 32px rgba(0,0,0,0.08);
    --shadow-hover: 0 24px 56px rgba(0,0,0,0.14);
    --transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
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
.navbar .nav-link.active-link { color: var(--gold-dark); }
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

/* ===================== HERO ===================== */
.hero {
    min-height: 60vh;
    background: var(--green);
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
    color: var(--gold);
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
.hero-stats {
    display: flex;
    gap: 40px;
    margin-top: 40px;
}
.hero-stat-num {
    font-family: 'Cormorant Garamond', serif;
    font-size: 3rem;
    font-weight: 600;
    color: var(--gold-light);
    line-height: 1;
    display: block;
}
.hero-stat-lbl {
    font-size: 0.62rem;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.35);
    margin-top: 4px;
    display: block;
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
    50%       { transform: translateX(-50%) translateY(8px); }}

/* ---- FILTER BAR ---- */
.filter-section {
    padding: 0;
    background: var(--white);
    position: sticky;
    top: 60px;
    z-index: 100;
    border-bottom: 1px solid rgba(201,168,76,0.12);
    box-shadow: 0 4px 20px rgba(0,0,0,0.04);
}
.filter-inner {
    display: flex;
    align-items: stretch;
    gap: 0;
    overflow-x: auto;
    scrollbar-width: none;
}
.filter-inner::-webkit-scrollbar { display: none; }
.filter-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 18px 24px;
    font-size: 0.73rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    font-family: 'DM Sans', sans-serif;
    font-weight: 400;
    color: var(--text-muted);
    background: transparent;
    border: none;
    cursor: pointer;
    white-space: nowrap;
    border-bottom: 2px solid transparent;
    transition: var(--transition);
    position: relative;
}
.filter-tab:hover { color: var(--gold); background: var(--gold-subtle); }
.filter-tab.active {
    color: var(--gold);
    border-bottom-color: var(--gold);
    background: var(--gold-subtle);
    font-weight: 500;
}
.filter-tab .tab-count {
    font-size: 0.65rem;
    padding: 2px 7px;
    border-radius: 20px;
    background: rgba(201,168,76,0.15);
    color: var(--gold);
    font-weight: 500;
}
.filter-tab.active .tab-count {
    background: var(--gold);
    color: #fff;
}
.filter-divider {
    width: 1px;
    background: var(--border);
    margin: 10px 0;
    align-self: stretch;
}
.filter-search-wrap {
    margin-left: auto;
    display: flex;
    align-items: center;
    padding: 0 20px;
    border-left: 1px solid var(--border);
    gap: 10px;
}
.filter-search-wrap i { color: var(--text-muted); font-size: 0.85rem; }
.filter-search-wrap input {
    border: none;
    outline: none;
    font-family: 'DM Sans', sans-serif;
    font-size: 0.8rem;
    font-weight: 300;
    color: var(--text);
    background: transparent;
    width: 180px;
}
.filter-search-wrap input::placeholder { color: var(--text-muted); }

/* ---- CONTENT PANELS ---- */
.media-section { padding: 60px 0 100px; background: var(--cream); }
.content-panel { display: none; }
.content-panel.active { display: block; animation: panelIn 0.4s cubic-bezier(0.4,0,0.2,1); }
@keyframes panelIn {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ---- SECTION HEADING ---- */
.panel-heading {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    margin-bottom: 36px;
    flex-wrap: wrap;
    gap: 12px;
}
.panel-heading-left {}
.panel-heading-eyebrow {
    font-size: 0.62rem;
    letter-spacing: 0.28em;
    text-transform: uppercase;
    color: var(--gold);
    display: flex; align-items: center; gap: 10px;
    margin-bottom: 8px;
}
.panel-heading-eyebrow::before {
    content: '';
    width: 28px; height: 1px;
    background: var(--gold);
}
.panel-heading h2 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(1.6rem, 3vw, 2.2rem);
    font-weight: 400;
    color: var(--dark);
    line-height: 1.2;
}
.panel-heading h2 em { font-style: italic; color: var(--gold-dark); }
.results-count {
    font-size: 0.72rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--text-muted);
}
.results-count strong { color: var(--gold); font-weight: 500; }

/* ---- UNIFORM VLOG CARDS ---- */
.vlog-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 28px;
}
@media (max-width: 991px) { .vlog-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 576px)  { .vlog-grid { grid-template-columns: 1fr; gap: 20px; } }

.vlog-card {
    background: var(--cream);
    border-radius: var(--radius);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    transition: var(--transition);
    box-shadow: var(--shadow-card);
    cursor: pointer;
    border: 1px solid rgba(201,168,76,0.08);
}
.vlog-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-hover);
    border-color: rgba(201,168,76,0.2);
}

/* Uniform 16:9 thumbnail */
.vlog-thumb {
    position: relative;
    width: 100%;
    aspect-ratio: 16 / 9;
    background: var(--dark-2);
    overflow: hidden;
    flex-shrink: 0;
}
.vlog-thumb video {
    width: 100%; height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.5s ease;
}
.vlog-card:hover .vlog-thumb video { transform: scale(1.04); }

/* Overlay on hover */
.vlog-thumb-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(
        to top,
        rgba(15,15,20,0.85) 0%,
        rgba(15,15,20,0.2) 50%,
        transparent 100%
    );
    opacity: 0;
    transition: opacity 0.35s ease;
    display: flex; align-items: center; justify-content: center;
}
.vlog-card:hover .vlog-thumb-overlay { opacity: 1; }
.play-btn {
    width: 54px; height: 54px;
    border-radius: 50%;
    background: rgba(201,168,76,0.9);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 1.2rem;
    transform: scale(0.8);
    transition: transform 0.3s ease;
    backdrop-filter: blur(4px);
}
.vlog-card:hover .play-btn { transform: scale(1); }

/* Category pill */
.vlog-cat-pill {
    position: absolute;
    top: 12px; left: 12px;
    font-size: 0.58rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    font-weight: 500;
    padding: 5px 12px;
    border-radius: 40px;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
}
.vlog-cat-pill.cat-tour {
    background: rgba(201,168,76,0.88);
    color: #fff;
}
.vlog-cat-pill.cat-tips {
    background: rgba(42,125,79,0.88);
    color: #fff;
}

/* Card body — equal height with flex */
.vlog-card-body {
    padding: 22px 22px 20px;
    display: flex;
    flex-direction: column;
    flex: 1;
}
.vlog-card-date {
    font-size: 0.67rem;
    letter-spacing: 0.08em;
    color: var(--text-muted);
    margin-bottom: 10px;
    display: flex; align-items: center; gap: 6px;
}
.vlog-card-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--dark);
    line-height: 1.4;
    margin-bottom: 10px;
    transition: color 0.2s;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.vlog-card:hover .vlog-card-title { color: var(--gold-dark); }
.vlog-card-desc {
    font-size: 0.8rem;
    color: var(--text-muted);
    line-height: 1.75;
    margin-bottom: 18px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    flex: 1;
}
.vlog-watch-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.68rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--gold);
    text-decoration: none;
    font-weight: 500;
    border-top: 1px solid var(--border);
    padding-top: 16px;
    margin-top: auto;
    transition: gap 0.2s;
}
.vlog-watch-btn:hover { gap: 14px; color: var(--gold-dark); }

/* ---- FEATURED CARD (always first) ---- */
.vlog-card.featured {
    grid-column: 1 / -1;
    flex-direction: row;
    max-height: 340px;
}
.vlog-card.featured .vlog-thumb {
    width: 55%;
    aspect-ratio: unset;
    flex-shrink: 0;
}
.vlog-card.featured .vlog-card-body {
    padding: 32px 32px 28px;
}
.vlog-card.featured .vlog-card-title {
    font-size: 1.45rem;
    -webkit-line-clamp: 3;
}
.vlog-card.featured .vlog-card-desc { -webkit-line-clamp: 3; }
.featured-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.6rem;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: var(--gold);
    border: 1px solid rgba(201,168,76,0.35);
    padding: 5px 12px;
    border-radius: 40px;
    margin-bottom: 14px;
    width: fit-content;
}
@media (max-width: 768px) {
    .vlog-card.featured { flex-direction: column; max-height: none; }
    .vlog-card.featured .vlog-thumb { width: 100%; aspect-ratio: 16/9; }
}

/* ---- EMPTY / NO RESULTS ---- */
.empty-state, .no-results {
    text-align: center;
    padding: 80px 20px;
}
.empty-state i, .no-results i {
    font-size: 2.8rem;
    color: rgba(201,168,76,0.25);
    display: block;
    margin-bottom: 18px;
}
.empty-state p, .no-results p { color: var(--text-muted); font-size: 0.88rem; }
.no-results { display: none; }

/* ---- LOAD MORE ---- */
.load-more-wrap {
    text-align: center;
    margin-top: 52px;
}
.btn-load-more {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: transparent;
    color: var(--gold);
    padding: 13px 40px;
    font-size: 0.74rem;
    letter-spacing: 0.16em;
    text-transform: uppercase;
    border: 1.5px solid rgba(201,168,76,0.4);
    border-radius: var(--radius);
    cursor: pointer;
    font-family: 'DM Sans', sans-serif;
    font-weight: 400;
    transition: var(--transition);
}
.btn-load-more:hover { background: var(--gold); color: #fff; border-color: var(--gold); }

/* ---- NEWS PANEL ---- */
.news-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 28px;
}
@media (max-width: 991px) { .news-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 576px)  { .news-grid { grid-template-columns: 1fr; gap: 20px; } }

.news-card {
    background: var(--cream);
    border-radius: var(--radius);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    border: 1px solid rgba(201,168,76,0.08);
    box-shadow: var(--shadow-card);
    transition: var(--transition);
    text-decoration: none;
}
.news-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-hover);
    border-color: rgba(201,168,76,0.2);
}

.news-card-img {
    position: relative;
    width: 100%;
    aspect-ratio: 16 / 9;
    background: var(--dark-2);
    overflow: hidden;
    flex-shrink: 0;
}
.news-card-img img {
    width: 100%; height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
    display: block;
}
.news-card:hover .news-card-img img { transform: scale(1.04); }
.news-img-placeholder {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    color: rgba(201,168,76,0.25);
    font-size: 2rem;
}
.news-badge {
    position: absolute;
    top: 12px; left: 12px;
    font-size: 0.58rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    font-weight: 500;
    padding: 5px 12px;
    border-radius: 40px;
    background: rgba(201,168,76,0.88);
    color: #fff;
    backdrop-filter: blur(8px);
}

.news-card-body {
    padding: 22px 22px 20px;
    display: flex;
    flex-direction: column;
    flex: 1;
}
.news-card-date {
    font-size: 0.67rem;
    letter-spacing: 0.08em;
    color: var(--text-muted);
    margin-bottom: 10px;
    display: flex; align-items: center; gap: 6px;
}
.news-card-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--dark);
    line-height: 1.4;
    margin-bottom: 10px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    transition: color 0.2s;
}
.news-card:hover .news-card-title { color: var(--gold-dark); }
.news-card-excerpt {
    font-size: 0.8rem;
    color: var(--text-muted);
    line-height: 1.75;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    flex: 1;
    margin-bottom: 18px;
}
.news-read-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 0.68rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--gold);
    font-weight: 500;
    border-top: 1px solid var(--border);
    padding-top: 16px;
    margin-top: auto;
    transition: gap 0.2s;
}
.news-card:hover .news-read-link { gap: 14px; }

/* Featured news */
.news-card.featured {
    grid-column: 1 / -1;
    flex-direction: row;
    max-height: 340px;
}
.news-card.featured .news-card-img {
    width: 55%; aspect-ratio: unset; flex-shrink: 0;
}
.news-card.featured .news-card-body { padding: 32px 32px 28px; }
.news-card.featured .news-card-title { font-size: 1.45rem; -webkit-line-clamp: 3; }
@media (max-width: 768px) {
    .news-card.featured { flex-direction: column; max-height: none; }
    .news-card.featured .news-card-img { width: 100%; aspect-ratio: 16/9; }
}
.news-empty { text-align: center; padding: 70px 20px; }
.news-empty i { font-size: 2.4rem; color: rgba(201,168,76,0.25); display: block; margin-bottom: 16px; }
.news-empty p { color: var(--text-muted); font-size: 0.88rem; }

.view-all-link {
    font-size: 0.7rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--gold);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding-bottom: 2px;
    border-bottom: 1px solid rgba(201,168,76,0.35);
    transition: gap 0.2s;
}
.view-all-link:hover { gap: 12px; color: var(--gold-dark); }

/* ---- CTA SECTION ---- */
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
.cta-section {
    padding: 100px 0;
    background: var(--cream);
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
    font-family: 'Playfair Display', serif;
    font-size: clamp(2rem, 4vw, 3.2rem);
    color: var(--dark);
    font-weight: 400;
    margin-bottom: 16px;
    line-height: 1.2;
}
.cta-section h2 em { font-style: italic; color: var(--gold-dark); }
.cta-section p { font-size: 0.88rem; color: var(--text-muted); max-width: 440px; margin: 0 auto 36px; line-height: 2; }
.cta-btns { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }
.btn-gold {
    background: var(--gold); color: #fff;
    padding: 13px 36px; font-size: 0.75rem;
    letter-spacing: 0.14em; text-transform: uppercase;
    text-decoration: none; border-radius: var(--radius);
    border: 1.5px solid var(--gold);
    transition: var(--transition);
    font-family: 'DM Sans', sans-serif;
}
.btn-gold:hover { background: transparent; color: var(--gold); }
.btn-outline-dark {
    background: transparent; color: var(--text);
    padding: 13px 36px; font-size: 0.75rem;
    letter-spacing: 0.14em; text-transform: uppercase;
    text-decoration: none; border-radius: var(--radius);
    border: 1.5px solid rgba(44,44,58,0.2);
    transition: var(--transition); font-family: 'DM Sans', sans-serif;
}
.btn-outline-dark:hover { border-color: var(--gold); color: var(--gold-dark); }

/* ---- CHAT BUBBLE ---- */
#chat-bubble {
    position: fixed; bottom: 28px; right: 28px;
    width: 54px; height: 54px;
    background: var(--gold); color: #fff;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; cursor: pointer;
    box-shadow: 0 6px 24px rgba(201,168,76,0.4);
    z-index: 2000;
    transition: var(--transition);
}
#chat-bubble:hover { transform: scale(1.1); }
#chat-window {
    position: fixed; bottom: 94px; right: 28px;
    width: 340px; height: 480px;
    background: #fff; border-radius: 12px;
    box-shadow: 0 16px 48px rgba(0,0,0,0.16);
    display: none; flex-direction: column;
    overflow: hidden; z-index: 2000;
    border: 1px solid var(--border);
}
.chat-header { background: var(--gold); color: #fff; padding: 15px 18px; font-weight: 500; font-size: 0.88rem; display: flex; justify-content: space-between; align-items: center; }
#chat-messages { flex: 1; padding: 16px; overflow-y: auto; background: #fdfdfb; display: flex; flex-direction: column; gap: 10px; }
.msg { padding: 10px 14px; border-radius: 10px; max-width: 82%; font-size: 0.86rem; line-height: 1.6; }
.msg-user { align-self: flex-end; background: var(--gold); color: #fff; border-bottom-right-radius: 3px; }
.msg-bot  { align-self: flex-start; background: #f1f0ec; color: #333; border-bottom-left-radius: 3px; }
.chat-input-area { padding: 12px 14px; border-top: 1px solid #eee; display: flex; background: #fff; }
.chat-input-area input { flex: 1; border: 1px solid #e0e0e0; padding: 9px 12px; border-radius: 6px; font-family: 'DM Sans', sans-serif; font-size: 0.84rem; outline: none; }
.chat-input-area input:focus { border-color: var(--gold); }
.chat-input-area button { background: none; border: none; color: var(--gold); font-size: 1.2rem; margin-left: 8px; cursor: pointer; }

/* ===================== FOOTER ===================== */
.footer { background-color: var(--green); color: #fff; width: 100%; padding-top: 50px; padding-bottom: 24px; }
.footer .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.footer-logo-text { font-family: 'Montserrat', sans-serif; font-size: 3.2rem; font-weight: 700; color: var(--gold); text-align: center; margin: 0 auto; display: block; }
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

/* ---- DARK MODE TOGGLE ---- */
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

.sun-icon { color: #f5b301; opacity: 1; }
.moon-icon { color: #fff; opacity: 0; }


/* ---- DARK MODE ---- */
body {
    transition: background 1s ease, color 1s ease;
}

body * {
    transition: background 1s ease, border-color 1s ease,
                color 1s ease, opacity 1s ease;
}

.navbar {
    transition: top 0.4s ease, box-shadow 0.4s ease,
                background 1s ease, border-color 1s ease;
}

.top-contact {
    transition: transform 0.3s ease, opacity 0.3s ease,
                background 1s ease, border-color 1s ease, color 1s ease;
}

body.dark-mode {
    background: #121212;
    color: #e5e5e5;
}
body.dark-mode .navbar .nav-link:hover,
body.dark-mode .navbar .nav-link.active-link {
    color: var(--gold-light);
}

body.dark-mode .theme-switch { background: #2d3250; }
body.dark-mode .theme-switch-slider { left: 38px; background: #1c1c1c; }
body.dark-mode .sun-icon { opacity: 0; }
body.dark-mode .moon-icon { opacity: 1; }

body.dark-mode .top-contact,
body.dark-mode .navbar { background: rgba(18,18,18,0.92); border-bottom-color: rgba(255,255,255,0.06); }
body.dark-mode .top-contact { color: #ddd; }
body.dark-mode .navbar .nav-link { color: rgba(255,255,255,0.85); }

body.dark-mode .hero { background: #0a0a14; }
body.dark-mode .hero-bg-text { color: rgba(191,161,88,0.06); }
body.dark-mode .hero-lines::before { border-color: rgba(191,161,88,0.12); }
body.dark-mode .hero-lines::after { border-color: rgba(191,161,88,0.08); }

body.dark-mode .filter-section { background: #121212; border-bottom-color: rgba(255,255,255,0.06); }
body.dark-mode .filter-tab { color: rgba(255,255,255,0.45); }
body.dark-mode .filter-tab:hover,
body.dark-mode .filter-tab.active { color: var(--gold-light); background: rgba(191,161,88,0.08); }
body.dark-mode .filter-search-wrap { border-left-color: rgba(255,255,255,0.06); }
body.dark-mode .filter-search-wrap input { color: #e5e5e5; }

body.dark-mode .media-section { background: #121212; }
body.dark-mode .vlog-card { background: #1c1c1c; border-color: rgba(255,255,255,0.05); }
body.dark-mode .vlog-card-title { color: #f0f0f8; }
body.dark-mode .vlog-card:hover .vlog-card-title { color: var(--gold-light); }
body.dark-mode .vlog-card-desc,
body.dark-mode .vlog-card-date { color: rgba(255,255,255,0.4); }
body.dark-mode .vlog-watch-btn { border-top-color: rgba(255,255,255,0.07); }
body.dark-mode .panel-heading h2 { color: #f0f0f8; }
body.dark-mode .results-count { color: rgba(255,255,255,0.3); }

body.dark-mode .news-card { background: #1c1c1c; border-color: rgba(255,255,255,0.05); }
body.dark-mode .news-card-title { color: #f0f0f8; }
body.dark-mode .news-card:hover .news-card-title { color: var(--gold-light); }
body.dark-mode .news-card-excerpt,
body.dark-mode .news-card-date { color: rgba(255,255,255,0.4); }
body.dark-mode .news-read-link { border-top-color: rgba(255,255,255,0.07); }

body.dark-mode .cta-section { background: #0A0A14; }
body.dark-mode .cta-section h2 { color: #f0f0f8; }
body.dark-mode .cta-section p { color: rgba(255,255,255,0.4); }
body.dark-mode .btn-outline-dark { color: rgba(255,255,255,0.6); border-color: rgba(255,255,255,0.12); }

body.dark-mode .footer { background: #0A0A14; }
body.dark-mode #chat-window { background: #1a1a1a; border-color: rgba(255,255,255,0.07); }
body.dark-mode #chat-messages { background: #111; }
body.dark-mode .msg-bot { background: #2a2a2a; color: #f1f1f1; }
body.dark-mode .chat-input-area { background: #1a1a1a; border-top-color: rgba(255,255,255,0.06); }
body.dark-mode .chat-input-area input { background: #2a2a2a; border-color: rgba(255,255,255,0.07); color: #e5e5e5; }

/* ---- VIDEO MODAL ---- */
.modal-content { border-radius: 12px; overflow: hidden; }

/* ---- RESPONSIVE ---- */
@media (max-width: 768px) {
    .filter-section { position: static; }
    .filter-inner { flex-wrap: nowrap; overflow-x: auto; }
    .filter-search-wrap { display: none; }
}
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
        <li class="nav-item"><a class="nav-link" href="contact_us.php">Contact Us</a></li>
        <!-- CHANGED: News & Blogs (with dropdown) → Media (plain link) -->
        <li class="nav-item"><a class="nav-link active-link" href="vlogs.php">Media</a></li>
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
            background:var(--green); color:#fff;
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



<!-- ===================== HERO ===================== -->
<section class="hero" id="top">
    <div class="hero-bg-text">MEDIA</div>
    <div class="hero-lines"></div>
    <div class="container">
        <div class="hero-content">
            <div class="hero-label" data-aos="fade-right">ITPH Media</div>
            <h1 data-aos="fade-up" data-aos-delay="80">
                Watch, Explore,<br><em>Stay Informed.</em>
            </h1>
            <p class="hero-desc" data-aos="fade-up" data-aos-delay="160">
                Take virtual tours of our properties, get expert real estate tips, and stay updated with the latest news about Iloilo's finest subdivisions.
            </p>
            <div class="hero-stats" data-aos="fade-up" data-aos-delay="240">
                <div>
                    <span class="hero-stat-num">24+</span>
                    <span class="hero-stat-lbl">Property Tours</span>
                </div>
                <div>
                    <span class="hero-stat-num">2</span>
                    <span class="hero-stat-lbl">Subdivisions</span>
                </div>
                <div>
                    <span class="hero-stat-num">100%</span>
                    <span class="hero-stat-lbl">Real Footage</span>
                </div>
            </div>
        </div>
    </div>
    <div class="scroll-hint">
        <span>Scroll</span>
        <i class="bi bi-chevron-down"></i>
    </div>
</section>

<!-- FILTER BAR -->
<section class="filter-section">
    <div class="container-fluid px-0">
        <div class="filter-inner">
            <button class="filter-tab active" data-filter="all">
                <i class="bi bi-grid-3x3-gap-fill"></i> All Videos
                <span class="tab-count" id="count-all">0</span>
            </button>
            <div class="filter-divider"></div>
            <button class="filter-tab" data-filter="tour">
                <i class="bi bi-house-door"></i> Property Tours
                <span class="tab-count" id="count-tour">0</span>
            </button>
            <div class="filter-divider"></div>
            <button class="filter-tab" data-filter="tips">
                <i class="bi bi-lightbulb"></i> Real Estate Tips
                <span class="tab-count" id="count-tips">0</span>
            </button>
            <div class="filter-divider"></div>
            <button class="filter-tab" data-filter="news">
                <i class="bi bi-newspaper"></i> Latest News
                <?php if(count($news_items) > 0): ?>
                <span class="tab-count"><?= count($news_items) ?></span>
                <?php endif; ?>
            </button>

            <div class="filter-search-wrap" id="search-wrap">
                <i class="bi bi-search"></i>
                <input type="text" id="vlog-search" placeholder="Search videos…">
            </div>
        </div>
    </div>
</section>

<!-- MEDIA SECTION -->
<section class="media-section">
    <div class="container">

        <?php
        $vlogs = [];
        while ($row = $vlogs_result->fetch_assoc()) {
            if (!isset($row['category']) || empty($row['category'])) {
                $row['category'] = 'tour';
            }
            $vlogs[] = $row;
        }
        $total      = count($vlogs);
        $tourVlogs  = array_values(array_filter($vlogs, fn($v) => $v['category'] === 'tour'));
        $tipsVlogs  = array_values(array_filter($vlogs, fn($v) => $v['category'] === 'tips'));
        $tourCount  = count($tourVlogs);
        $tipsCount  = count($tipsVlogs);

        // Helper to render uniform vlog cards
        function renderVlogCard($row, $index, $isFeatured = false) {
            $cat      = htmlspecialchars($row['category']);
            $catLabel = ($cat === 'tips') ? 'Real Estate Tips' : 'Property Tour';
            $catClass = ($cat === 'tips') ? 'cat-tips' : 'cat-tour';
            $delay    = ($index % 3) * 80;
        ?>
        <div class="vlog-card <?= $isFeatured ? 'featured' : '' ?>"
             data-category="<?= $cat ?>"
             data-title="<?= strtolower(htmlspecialchars($row['title'])) ?>"
             data-aos="fade-up" data-aos-delay="<?= $delay ?>">
            <div class="vlog-thumb">
                <video preload="metadata">
                    <source src="../uploads/vlogs/<?= htmlspecialchars($row['video_path']) ?>" type="video/mp4">
                </video>
                <div class="vlog-thumb-overlay">
                    <div class="play-btn"><i class="bi bi-play-fill"></i></div>
                </div>
                <span class="vlog-cat-pill <?= $catClass ?>"><?= $isFeatured ? '★ Featured' : $catLabel ?></span>
            </div>
            <div class="vlog-card-body">
                <?php if ($isFeatured): ?>
                <div class="featured-badge"><i class="bi bi-star-fill"></i> Featured Video</div>
                <?php endif; ?>
                <div class="vlog-card-date">
                    <i class="bi bi-calendar3"></i>
                    <?= date("M d, Y", strtotime($row['created_at'])) ?>
                    &nbsp;·&nbsp; <i class="bi bi-tag"></i> <?= $catLabel ?>
                </div>
                <div class="vlog-card-title"><?= htmlspecialchars($row['title']) ?></div>
                <?php if (!empty($row['description'])): ?>
                <p class="vlog-card-desc"><?= htmlspecialchars($row['description']) ?></p>
                <?php else: ?>
                <p class="vlog-card-desc" style="opacity:0;pointer-events:none;">—</p>
                <?php endif; ?>
                <a href="#" class="vlog-watch-btn openVideoBtn"
                   data-bs-toggle="modal" data-bs-target="#videoModal"
                   data-video="../uploads/vlogs/<?= htmlspecialchars($row['video_path']) ?>"
                   data-title="<?= htmlspecialchars($row['title']) ?>"
                   data-description="<?= htmlspecialchars($row['description']) ?>"
                   data-date="<?= date("F d, Y", strtotime($row['created_at'])) ?>"
                   data-category="<?= $catLabel ?>">
                    Watch Now <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
        <?php } ?>

        <!-- PANEL: ALL VIDEOS -->
        <div class="content-panel active" id="panel-all">
            <?php if ($total === 0): ?>
                <div class="empty-state">
                    <i class="bi bi-camera-video-off"></i>
                    <p>No vlogs available yet. Check back soon!</p>
                </div>
            <?php else: ?>
                <div class="panel-heading">
                    <div class="panel-heading-left">
                        <div class="panel-heading-eyebrow">All Media</div>
                        <h2>Video <em>Library</em></h2>
                    </div>
                    <span class="results-count">
                        Showing <strong id="visible-count-all"><?= $total ?></strong> of <strong><?= $total ?></strong> videos
                    </span>
                </div>

                <div class="vlog-grid" id="vlog-grid-all">
                    <?php foreach ($vlogs as $i => $row):
                        renderVlogCard($row, $i, false);
                    endforeach; ?>
                </div>

                <div class="no-results" id="no-results-all">
                    <i class="bi bi-search"></i>
                    <p>No videos match your search. Try a different keyword.</p>
                </div>

                <?php if ($total > 6): ?>
                <div class="load-more-wrap" id="load-more-all-wrap" data-aos="fade-up">
                    <button class="btn-load-more" id="loadMoreBtnAll">
                        Load More <i class="bi bi-arrow-down"></i>
                    </button>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- PANEL: PROPERTY TOURS -->
        <div class="content-panel" id="panel-tour">
            <div class="panel-heading">
                <div class="panel-heading-left">
                    <div class="panel-heading-eyebrow">Video Tours</div>
                    <h2>Property <em>Tours</em></h2>
                </div>
                <span class="results-count"><strong><?= $tourCount ?></strong> Tour<?= $tourCount !== 1 ? 's' : '' ?></span>
            </div>
            <?php if (empty($tourVlogs)): ?>
                <div class="empty-state">
                    <i class="bi bi-house-slash"></i>
                    <p>No property tour videos yet. Check back soon!</p>
                </div>
            <?php else: ?>
                <div class="vlog-grid">
                    <?php foreach ($tourVlogs as $i => $row): renderVlogCard($row, $i); endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- PANEL: REAL ESTATE TIPS -->
        <div class="content-panel" id="panel-tips">
            <div class="panel-heading">
                <div class="panel-heading-left">
                    <div class="panel-heading-eyebrow">Expert Advice</div>
                    <h2>Real Estate <em>Tips</em></h2>
                </div>
                <span class="results-count"><strong><?= $tipsCount ?></strong> Tip<?= $tipsCount !== 1 ? 's' : '' ?></span>
            </div>
            <?php if (empty($tipsVlogs)): ?>
                <div class="empty-state">
                    <i class="bi bi-lightbulb-off"></i>
                    <p>No real estate tip videos yet. Check back soon!</p>
                </div>
            <?php else: ?>
                <div class="vlog-grid">
                    <?php foreach ($tipsVlogs as $i => $row): renderVlogCard($row, $i); endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- PANEL: NEWS -->
        <div class="content-panel" id="panel-news">
            <div class="panel-heading">
                <div class="panel-heading-left">
                    <div class="panel-heading-eyebrow">Stay Informed</div>
                    <h2>Latest <em>News</em></h2>
                </div>
                <a href="news.php" class="view-all-link">View All <i class="bi bi-arrow-right"></i></a>
            </div>

            <?php if (empty($news_items)): ?>
                <div class="news-empty">
                    <i class="bi bi-newspaper"></i>
                    <p>No news articles yet. <a href="news.php" style="color:var(--gold);">Visit the news page</a>.</p>
                </div>
            <?php else: ?>
                <div class="news-grid">
                    <?php foreach ($news_items as $ni => $news):
                        $isFeatureNews = ($ni === 0);
                        $newsImg = $news['image_path'] ?? ($news['thumbnail'] ?? '');
                    ?>
                    <a href="news.php?id=<?= $news['id'] ?>"
                       class="news-card <?= $isFeatureNews ? 'featured' : '' ?>"
                       data-aos="fade-up" data-aos-delay="<?= ($ni % 3) * 80 ?>">
                        <div class="news-card-img">
                            <?php if (!empty($newsImg)): ?>
                                <img src="../uploads/news/<?= htmlspecialchars($newsImg) ?>"
                                     alt="<?= htmlspecialchars($news['title']) ?>">
                            <?php else: ?>
                                <div class="news-img-placeholder"><i class="bi bi-newspaper"></i></div>
                            <?php endif; ?>
                            <span class="news-badge"><?= $isFeatureNews ? '★ Latest' : 'News' ?></span>
                        </div>
                        <div class="news-card-body">
                            <?php if ($isFeatureNews): ?>
                            <div class="featured-badge"><i class="bi bi-star-fill"></i> Top Story</div>
                            <?php endif; ?>
                            <div class="news-card-date">
                                <i class="bi bi-calendar3"></i>
                                <?= date("F d, Y", strtotime($news['created_at'])) ?>
                            </div>
                            <div class="news-card-title"><?= htmlspecialchars($news['title']) ?></div>
                            <?php if (!empty($news['content']) || !empty($news['excerpt'])): ?>
                            <p class="news-card-excerpt">
                                <?= htmlspecialchars($news['excerpt'] ?? strip_tags(substr($news['content'], 0, 200))) ?>
                            </p>
                            <?php else: ?>
                            <p class="news-card-excerpt" style="opacity:0;pointer-events:none;">—</p>
                            <?php endif; ?>
                            <span class="news-read-link">Read More <i class="bi bi-arrow-right"></i></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>

                <div class="load-more-wrap mt-4" data-aos="fade-up">
                    <a href="news.php" class="btn-load-more">View All News <i class="bi bi-arrow-right"></i></a>
                </div>
            <?php endif; ?>
        </div>

    </div><!-- /container -->
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="container" style="position:relative;z-index:2;">
        <div class="section-eyebrow justify-content-center" data-aos="fade-up">Ready to See More?</div>
        <h2 data-aos="fade-up" data-aos-delay="80">Explore Our <em>Available</em><br>Properties Today.</h2>
        <p data-aos="fade-up" data-aos-delay="160">Loved what you watched? Take the next step and explore our listings in person — or reserve online today.</p>
        <div class="cta-btns" data-aos="fade-up" data-aos-delay="240">
            <a href="all_properties.php" class="btn-gold">View Properties</a>
            <a href="contact_us.php" class="btn-outline-dark">Contact Us</a>
        </div>
    </div>
</section>

<!-- VIDEO MODAL -->
<div class="modal fade" id="videoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0" style="background:#111;border-radius:14px;overflow:hidden;">
            <button type="button" class="btn-close btn-close-white position-absolute"
                    data-bs-dismiss="modal"
                    style="top:18px;right:18px;z-index:10;"></button>
            <div class="row g-0">
                <div class="col-lg-8">
                    <video id="modalVideo" controls autoplay style="width:100%;height:100%;background:#000;display:block;">
                        <source id="modalVideoSource" src="" type="video/mp4">
                    </video>
                </div>
                <div class="col-lg-4 d-flex flex-column" style="background:#181820;color:#fff;padding:36px;">
                    <span id="modalCategoryLabel" style="font-size:.62rem;letter-spacing:.2em;text-transform:uppercase;color:var(--gold);margin-bottom:16px;"></span>
                    <h2 id="modalTitle" style="font-family:'Playfair Display',serif;font-size:1.8rem;line-height:1.2;margin-bottom:14px;color:#fff;font-weight:400;"></h2>
                    <div style="font-size:.76rem;color:rgba(255,255,255,0.4);margin-bottom:20px;letter-spacing:.06em;">
                        <i class="bi bi-calendar3 me-2"></i><span id="modalDate"></span>
                    </div>
                    <p id="modalDescription" style="line-height:1.9;color:rgba(255,255,255,0.6);font-size:.88rem;"></p>
                    <div class="mt-auto pt-4">
                        <a href="all_properties.php" class="btn btn-warning w-100"
                           style="letter-spacing:.12em;text-transform:uppercase;font-size:.72rem;padding:13px;border-radius:4px;">
                            Explore Properties
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
                    <li><a href="vlogs.php">Media</a></li>
                    <li><a href="news.php">News</a></li>
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
                    <li><a href="reservation.php">Book Now</a></li>
                    <li><a href="account.php">Account</a></li>
                </ul>
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

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
AOS.init({ once: true, offset: 50, duration: 650 });

// Scroll: hide topbar, stick navbar
window.addEventListener('scroll', () => {
    const nav = document.querySelector('.navbar');
    const top = document.querySelector('.top-contact');
    if (window.scrollY > 50) { nav.classList.add('scrolled'); top.classList.add('hidden'); }
    else { nav.classList.remove('scrolled'); top.classList.remove('hidden'); }
});

// Dark Mode
const toggle = document.getElementById('darkModeToggle');
if (localStorage.getItem('darkMode') === 'enabled') document.body.classList.add('dark-mode');
toggle.addEventListener('click', () => {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', document.body.classList.contains('dark-mode') ? 'enabled' : 'disabled');
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
    const uDiv = document.createElement('div'); uDiv.className = 'msg msg-user'; uDiv.textContent = msg; msgs.appendChild(uDiv);
    field.value = ''; msgs.scrollTop = msgs.scrollHeight;
    const bDiv = document.createElement('div'); bDiv.className = 'msg msg-bot'; bDiv.textContent = 'Typing…'; msgs.appendChild(bDiv);
    msgs.scrollTop = msgs.scrollHeight;
    try {
        const fd = new FormData(); fd.append('message', msg);
        const res = await fetch('../backends/chat.php', { method:'POST', body:fd });
        const data = await res.json();
        bDiv.textContent = data.reply;
    } catch { bDiv.textContent = 'Connection error. Please try later.'; }
    msgs.scrollTop = msgs.scrollHeight;
}

// Filter Tabs
const tabs    = document.querySelectorAll('.filter-tab');
const panels  = { all: document.getElementById('panel-all'), tour: document.getElementById('panel-tour'), tips: document.getElementById('panel-tips'), news: document.getElementById('panel-news') };
const searchW = document.getElementById('search-wrap');
let current   = 'all';

// Set initial counts
function initCounts() {
    const items = document.querySelectorAll('#vlog-grid-all .vlog-card');
    document.getElementById('count-all').textContent  = items.length;
    document.getElementById('count-tour').textContent = [...items].filter(c => c.dataset.category === 'tour').length;
    document.getElementById('count-tips').textContent = [...items].filter(c => c.dataset.category === 'tips').length;
}
initCounts();

function showPanel(filter) {
    current = filter;
    Object.values(panels).forEach(p => p && p.classList.remove('active'));
    (panels[filter] || panels.all).classList.add('active');
    if (searchW) searchW.style.display = filter === 'news' ? 'none' : '';
    if (document.getElementById('vlog-search')) document.getElementById('vlog-search').value = '';
    if (filter === 'all') filterCards('');
}

tabs.forEach(tab => {
    tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        showPanel(tab.dataset.filter);
    });
});

// Search
const searchInput = document.getElementById('vlog-search');
if (searchInput) {
    searchInput.addEventListener('input', () => filterCards(searchInput.value.toLowerCase()));
}

function filterCards(q) {
    const items = document.querySelectorAll('#vlog-grid-all .vlog-card');
    let count = 0;
    items.forEach(card => {
        const show = !q || (card.dataset.title || '').includes(q);
        card.style.display = show ? '' : 'none';
        if (show) count++;
    });
    const vc = document.getElementById('visible-count-all');
    if (vc) vc.textContent = count;
    const nr = document.getElementById('no-results-all');
    if (nr) nr.style.display = (count === 0 && q) ? 'block' : 'none';
}

// Load More
const allCards  = Array.from(document.querySelectorAll('#vlog-grid-all .vlog-card'));
const lmBtn     = document.getElementById('loadMoreBtnAll');
const lmWrap    = document.getElementById('load-more-all-wrap');
let maxVisible  = 6;

allCards.forEach((c, i) => { if (i >= maxVisible) c.style.display = 'none'; });

if (lmBtn) {
    lmBtn.addEventListener('click', () => {
        maxVisible += 3;
        allCards.forEach((c, i) => { if (i < maxVisible) c.style.display = ''; });
        if (maxVisible >= allCards.length && lmWrap) lmWrap.style.display = 'none';
    });
}

// Video Modal
const videoModal = document.getElementById('videoModal');
document.querySelectorAll('.openVideoBtn').forEach(btn => {
    btn.addEventListener('click', e => {
        e.preventDefault();
        document.getElementById('modalVideoSource').src = btn.dataset.video;
        document.getElementById('modalVideo').load();
        document.getElementById('modalTitle').textContent       = btn.dataset.title;
        document.getElementById('modalDescription').textContent = btn.dataset.description;
        document.getElementById('modalDate').textContent        = btn.dataset.date;
        document.getElementById('modalCategoryLabel').textContent = btn.dataset.category || 'Property Vlog';
    });
});
videoModal.addEventListener('hidden.bs.modal', () => {
    const mv = document.getElementById('modalVideo');
    mv.pause(); mv.currentTime = 0;
});

</script>
</body>
<script src="https://cdn.botpress.cloud/webchat/v3.6/inject.js"></script>
<script src="https://files.bpcontent.cloud/2026/05/16/02/20260516020411-RS0TP9AJ.js" defer></script>
</html>