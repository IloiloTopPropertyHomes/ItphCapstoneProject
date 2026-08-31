<?php
// =====================================================================
// news.php — Single Article View (+ fallback listing when no ?id=)
// Matches the visual language of vlogs.php: gold/cream/dark palette,
// Montserrat + Cormorant Garamond, dark-mode toggle, same nav/footer.
// =====================================================================

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

// ---------------------------------------------------------------------
// Make sure a likes table exists (safe to run every load — no-op after
// the first time). One row per (news_id, liker key) so a visitor can
// only like an article once.
// ---------------------------------------------------------------------
$conn->query("
    CREATE TABLE IF NOT EXISTS news_likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        news_id INT NOT NULL,
        liker_key VARCHAR(191) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_like (news_id, liker_key),
        KEY idx_news (news_id)
    )
");

// CSRF token (shared pattern with contact_us.php)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// A stable per-visitor key so likes persist across a session without
// requiring an account: logged-in users are keyed by their account,
// guests are keyed by their PHP session id.
function liker_key() {
    if (!empty($_SESSION['user_id'])) return 'user_' . $_SESSION['user_id'];
    if (!empty($_SESSION['fullname'])) return 'guest_' . md5($_SESSION['fullname']);
    return 'sess_' . session_id();
}

// ---------------------------------------------------------------------
// AJAX endpoint: toggle like (POST, same file, no page reload)
// ---------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_like') {
    header('Content-Type: application/json');

    $news_id = (int)($_POST['news_id'] ?? 0);
    $token   = $_POST['csrf_token'] ?? '';

    if (!$news_id || !hash_equals($csrf_token, $token)) {
        echo json_encode(['ok' => false, 'error' => 'Invalid request']);
        exit;
    }

    $key = liker_key();

    $check = $conn->prepare("SELECT id FROM news_likes WHERE news_id=? AND liker_key=?");
    $check->bind_param("is", $news_id, $key);
    $check->execute();
    $check->store_result();
    $alreadyLiked = $check->num_rows > 0;
    $check->close();

    if ($alreadyLiked) {
        $del = $conn->prepare("DELETE FROM news_likes WHERE news_id=? AND liker_key=?");
        $del->bind_param("is", $news_id, $key);
        $del->execute();
        $del->close();
        $liked = false;
    } else {
        $ins = $conn->prepare("INSERT IGNORE INTO news_likes (news_id, liker_key) VALUES (?, ?)");
        $ins->bind_param("is", $news_id, $key);
        $ins->execute();
        $ins->close();
        $liked = true;
    }

    $cnt = $conn->prepare("SELECT COUNT(*) AS c FROM news_likes WHERE news_id=?");
    $cnt->bind_param("i", $news_id);
    $cnt->execute();
    $count = $cnt->get_result()->fetch_assoc()['c'] ?? 0;
    $cnt->close();

    echo json_encode(['ok' => true, 'liked' => $liked, 'count' => (int)$count]);
    exit;
}

// ---------------------------------------------------------------------
// Load the requested article
// ---------------------------------------------------------------------
$news_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$article = null;

if ($news_id) {
    $stmt = $conn->prepare("SELECT * FROM news WHERE id=? AND status='published'");
    $stmt->bind_param("i", $news_id);
    $stmt->execute();
    $article = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($article) {
        // Count a view once per session per article
        if (empty($_SESSION['viewed_news'][$news_id])) {
            $conn->query("UPDATE news SET views = views + 1 WHERE id=" . (int)$news_id);
            $_SESSION['viewed_news'][$news_id] = true;
            $article['views'] = ($article['views'] ?? 0) + 1;
        }
    }
}

// Current like state + count for this article
$likeCount = 0;
$hasLiked  = false;
if ($article) {
    $key = liker_key();
    $lc = $conn->prepare("SELECT COUNT(*) AS c FROM news_likes WHERE news_id=?");
    $lc->bind_param("i", $news_id);
    $lc->execute();
    $likeCount = (int)($lc->get_result()->fetch_assoc()['c'] ?? 0);
    $lc->close();

    $lk = $conn->prepare("SELECT id FROM news_likes WHERE news_id=? AND liker_key=?");
    $lk->bind_param("is", $news_id, $key);
    $lk->execute();
    $lk->store_result();
    $hasLiked = $lk->num_rows > 0;
    $lk->close();
}

// Related articles (exclude current, most recent 3)
$related = [];
$relQ = $article
    ? $conn->prepare("SELECT id, title, image_path, category, created_at FROM news WHERE status='published' AND id != ? ORDER BY created_at DESC LIMIT 3")
    : $conn->prepare("SELECT id, title, image_path, category, created_at FROM news WHERE status='published' ORDER BY created_at DESC LIMIT 6");
if ($article) { $relQ->bind_param("i", $news_id); }
$relQ->execute();
$relRes = $relQ->get_result();
while ($r = $relRes->fetch_assoc()) { $related[] = $r; }
$relQ->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $article ? htmlspecialchars($article['title']) . ' — ITPH News' : 'Latest News — ITPH' ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400;1,600&family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
<link rel="stylesheet" href="css/common.css">

<style>
:root {
    --gold: #bfa158;
    --gold-dark: #8c7a45;
    --gold-light: #d4b97a;
    --gold-subtle: rgba(191,161,88,0.12);
    --dark: #1a1a2e;
    --dark-2: #16161e;
    --green: #0D2B1F;
    --lightgreen: #0D2B1F;
    --cream: #f6f6f0;
    --text: #3a3a50;
    --text-muted: #7a7a8a;
    --white: #ffffff;
    --border: rgba(191,161,88,0.18);
    --radius: 4px;
    --shadow-card: 0 8px 32px rgba(0,0,0,0.08);
    --shadow-hover: 0 24px 56px rgba(0,0,0,0.14);
    --heart: #e0577a;
    --transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Montserrat', sans-serif; font-weight: 300; color: var(--text); background: var(--white); overflow-x: hidden; }

/* ===================== TOP CONTACT ===================== */
.top-contact { color:#555; font-size:.78rem; padding:6px 30px; display:flex; justify-content:space-between; align-items:center; position:fixed; top:0; width:100%; z-index:1050; background:rgba(255,255,255,.85); backdrop-filter:blur(12px); -webkit-backdrop-filter:blur(12px); letter-spacing:.04em; border-bottom:1px solid rgba(191,161,88,.15); transition:transform .3s ease, opacity .3s ease; }
.top-contact.hidden { transform:translateY(-100%); opacity:0; }
.top-contact .social-icons a { margin-left:14px; color:#555; transition:color .2s; text-decoration:none; }
.top-contact .social-icons a:hover { color:var(--gold); }

/* ===================== NAVBAR ===================== */
.navbar { position:fixed; top:30px; width:100%; z-index:1040; background:rgba(255,255,255,.88); backdrop-filter:blur(14px); -webkit-backdrop-filter:blur(14px); border-bottom:1px solid rgba(191,161,88,.12); transition:top .4s ease, box-shadow .4s ease; }
.navbar.scrolled { top:0; box-shadow:0 2px 20px rgba(0,0,0,.06); }
.navbar .navbar-brand { font-family:'Montserrat', sans-serif; font-weight:700; font-size:1.5rem; color:var(--gold); letter-spacing:.05em; }
.navbar .nav-link { margin-left:20px; color:var(--text); font-size:.82rem; font-weight:400; letter-spacing:.08em; text-transform:uppercase; transition:color .2s; }
.navbar .nav-link:hover, .navbar .nav-link.active-link { color:var(--gold-dark); }
.navbar .btn-reserve { background:var(--gold); border:1px solid transparent; color:#fff; padding:8px 22px; border-radius:2px; font-size:.78rem; font-weight:400; letter-spacing:.1em; text-transform:uppercase; transition:all .3s ease; }
.navbar .btn-reserve:hover { background:transparent; border-color:var(--gold); color:var(--gold); }

/* ===================== THEME SWITCH ===================== */
.theme-switch { width:68px; height:34px; background:#d8d8d8; border-radius:50px; position:relative; cursor:pointer; transition:all .35s ease; display:flex; align-items:center; padding:4px; margin-left:14px; box-shadow:inset 0 2px 6px rgba(0,0,0,.12); }
.theme-switch-slider { width:26px; height:26px; border-radius:50%; background:#fff; display:flex; align-items:center; justify-content:center; position:absolute; left:4px; transition:all .35s ease; box-shadow:0 3px 10px rgba(0,0,0,.18); }
.theme-switch i { position:absolute; font-size:.78rem; }
.sun-icon { color:#f5b301; opacity:1; }
.moon-icon { color:#fff; opacity:0; }
body.dark-mode .theme-switch { background:#2d3250; }
body.dark-mode .theme-switch-slider { left:38px; background:#1c1c1c; }
body.dark-mode .sun-icon { opacity:0; }
body.dark-mode .moon-icon { opacity:1; }

/* ===================== ARTICLE HERO ===================== */
.article-hero {
    position: relative;
    min-height: 62vh;
    display: flex;
    align-items: flex-end;
    background: var(--dark);
    padding-top: 80px;
    overflow: hidden;
}
.article-hero-img {
    position: absolute; inset: 0;
    width: 100%; height: 100%;
    object-fit: cover;
    opacity: .55;
    cursor: zoom-in;
}
.article-hero-noimg {
    position: absolute; inset: 0;
    background: linear-gradient(135deg, #1a1a2e 0%, #24243e 100%);
}
.article-hero::after {
    content:'';
    position:absolute; inset:0;
    background: linear-gradient(to top, rgba(15,15,20,.96) 5%, rgba(15,15,20,.55) 55%, rgba(15,15,20,.25) 100%);
}
.article-hero-content { position: relative; z-index: 2; padding: 70px 0 46px; width: 100%; }
.back-link {
    display:inline-flex; align-items:center; gap:8px;
    font-size:.72rem; letter-spacing:.14em; text-transform:uppercase;
    color: rgba(255,255,255,.55); text-decoration:none; margin-bottom:28px;
    transition: gap .2s, color .2s;
}
.back-link:hover { gap:12px; color: var(--gold-light); }
.article-badge {
    display:inline-flex; align-items:center; gap:6px;
    font-size:.6rem; letter-spacing:.2em; text-transform:uppercase; font-weight:500;
    padding:6px 14px; border-radius:40px;
    background: rgba(191,161,88,.9); color:#fff; margin-bottom:18px;
}
.article-title {
    font-family:'Cormorant Garamond', serif;
    font-size: clamp(2.2rem, 5vw, 3.6rem);
    font-weight: 400; color:#fff; line-height:1.15; max-width: 880px; margin-bottom: 22px;
}
.article-meta {
    display:flex; flex-wrap:wrap; align-items:center; gap: 22px;
    font-size:.78rem; color: rgba(255,255,255,.5); letter-spacing:.03em;
}
.article-meta span { display:flex; align-items:center; gap:7px; }
.article-meta i { color: var(--gold-light); }

/* ===================== VIEW FULL PHOTO BADGE ===================== */
.view-photo-badge {
    position: absolute;
    z-index: 3;
    bottom: 24px;
    right: 24px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(20,20,26,.55);
    border: 1px solid rgba(255,255,255,.25);
    color: #fff;
    padding: 9px 16px;
    border-radius: 40px;
    font-size: .72rem;
    letter-spacing: .06em;
    text-transform: uppercase;
    cursor: pointer;
    backdrop-filter: blur(6px);
    transition: background .25s ease, border-color .25s ease, transform .15s ease;
}
.view-photo-badge:hover { background: rgba(191,161,88,.35); border-color: var(--gold-light); }
.view-photo-badge:active { transform: scale(.96); }
.view-photo-badge i { font-size: .9rem; }
@media (max-width: 576px) {
    .view-photo-badge { bottom: 16px; right: 14px; font-size:.64rem; padding:7px 12px; }
}

/* ===================== LIKE BUTTON ===================== */
.like-btn {
    display:inline-flex; align-items:center; gap:9px;
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.18);
    color: #fff;
    padding: 9px 18px;
    border-radius: 40px;
    cursor: pointer;
    font-family:'Montserrat', sans-serif;
    font-size:.78rem; letter-spacing:.04em;
    transition: background .25s ease, border-color .25s ease, transform .15s ease;
    backdrop-filter: blur(6px);
}
.like-btn:hover { border-color: rgba(224,87,122,.5); background: rgba(224,87,122,.12); }
.like-btn .heart-icon { font-size: 1rem; transition: transform .25s cubic-bezier(.34,1.56,.64,1), color .2s ease; color: rgba(255,255,255,.7); }
.like-btn.liked { border-color: rgba(224,87,122,.6); background: rgba(224,87,122,.16); }
.like-btn.liked .heart-icon { color: var(--heart); }
.like-btn.pop .heart-icon { transform: scale(1.35); }
.like-btn .like-count { font-weight:500; min-width: 12px; text-align:left; }
.like-btn:active { transform: scale(.96); }

/* Secondary (light background) variant used at end of article */
.like-btn.on-light {
    background: var(--cream);
    border: 1px solid var(--border);
    color: var(--text);
}
.like-btn.on-light:hover { background: rgba(224,87,122,.06); border-color: rgba(224,87,122,.4); }
.like-btn.on-light .heart-icon { color: var(--text-muted); }
.like-btn.on-light.liked { background: rgba(224,87,122,.08); border-color: rgba(224,87,122,.5); }
.like-btn.on-light.liked .heart-icon { color: var(--heart); }

/* ===================== ARTICLE BODY ===================== */
.article-body-section { background: var(--cream); padding: 64px 0 20px; }
.article-wrap { max-width: 740px; margin: 0 auto; }
.article-text {
    font-family:'Montserrat', sans-serif;
    font-size: 1.02rem;
    line-height: 2;
    color: var(--text);
    font-weight: 300;
}
.article-text p { margin-bottom: 26px; }
.article-text p:first-of-type::first-letter {
    font-family: 'Playfair Display', serif;
    font-size: 3.6rem;
    font-weight: 700;
    float: left;
    line-height: .82;
    margin: 6px 10px 0 0;
    color: var(--gold-dark);
}
.article-divider { width: 48px; height: 2px; background: var(--gold); margin: 40px 0; border: none; }
.article-engage {
    display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;
    padding: 22px 0 8px;
}
.engage-label { font-size:.78rem; color: var(--text-muted); letter-spacing:.03em; }
.share-row { display:flex; gap:10px; }
.share-btn {
    width:38px; height:38px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    border:1px solid var(--border); color: var(--text-muted);
    text-decoration:none; transition: var(--transition);
    font-size: .9rem;
}
.share-btn:hover { background: var(--gold); color:#fff; border-color: var(--gold); }

/* ===================== RELATED ===================== */
.related-section { background: var(--cream); padding: 70px 0 90px; }
.related-heading { text-align:center; margin-bottom: 40px; }
.section-eyebrow { font-size:.65rem; letter-spacing:.26em; text-transform:uppercase; color:var(--gold); font-weight:400; margin-bottom:14px; display:flex; align-items:center; justify-content:center; gap:10px; }
.section-eyebrow::before, .section-eyebrow::after { content:''; display:inline-block; width:28px; height:1px; background:var(--gold); }
.related-heading h2 { font-family:'Playfair Display', serif; font-size: clamp(1.6rem, 3vw, 2.3rem); color: var(--dark); font-weight:400; }
.related-heading h2 em { font-style: italic; color: var(--gold-dark); }

.news-grid { display:grid; grid-template-columns: repeat(3,1fr); gap:28px; }
@media (max-width:991px){ .news-grid{ grid-template-columns:repeat(2,1fr);} }
@media (max-width:576px){ .news-grid{ grid-template-columns:1fr; gap:20px;} }
.news-card { background:var(--white); border-radius:var(--radius); overflow:hidden; display:flex; flex-direction:column; border:1px solid rgba(201,168,76,.08); box-shadow: var(--shadow-card); transition: var(--transition); text-decoration:none; }
.news-card:hover { transform: translateY(-8px); box-shadow: var(--shadow-hover); border-color: rgba(201,168,76,.2); }
.news-card-img { position:relative; width:100%; aspect-ratio:16/9; background:var(--dark-2); overflow:hidden; }
.news-card-img img { width:100%; height:100%; object-fit:cover; transition: transform .5s ease; display:block; }
.news-card:hover .news-card-img img { transform: scale(1.04); }
.news-img-placeholder { width:100%; height:100%; display:flex; align-items:center; justify-content:center; color: rgba(201,168,76,.25); font-size:2rem; }
.news-card-body { padding: 20px 20px 18px; display:flex; flex-direction:column; flex:1; }
.news-card-date { font-size:.67rem; color: var(--text-muted); margin-bottom:8px; display:flex; align-items:center; gap:6px; }
.news-card-title { font-family:'Playfair Display', serif; font-size:1rem; font-weight:700; color:var(--dark); line-height:1.4; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; transition: color .2s; }
.news-card:hover .news-card-title { color: var(--gold-dark); }
.news-read-link { display:inline-flex; align-items:center; gap:8px; font-size:.66rem; letter-spacing:.14em; text-transform:uppercase; color:var(--gold); font-weight:500; border-top:1px solid var(--border); padding-top:14px; margin-top:16px; transition: gap .2s; }
.news-card:hover .news-read-link { gap:14px; }

/* ===================== CTA (matches contact_us.php) ===================== */
.cta-section { padding:100px 0; background: var(--cream); text-align:center; position:relative; overflow:hidden; }
.cta-section::before { content:''; position:absolute; left:50%; top:50%; transform:translate(-50%,-50%); width:600px; height:600px; border-radius:50%; border:1px solid rgba(191,161,88,.07); pointer-events:none; }
.cta-section::after { content:''; position:absolute; left:50%; top:50%; transform:translate(-50%,-50%); width:900px; height:900px; border-radius:50%; border:1px solid rgba(191,161,88,.04); pointer-events:none; }
.cta-section h2 { font-family:'Playfair Display', serif; font-size: clamp(2rem,4vw,3.2rem); color:var(--dark); font-weight:400; margin-bottom:16px; line-height:1.2; }
.cta-section h2 em { font-style:italic; color:var(--gold-dark); }
.cta-section p { font-size:.88rem; color:var(--text-muted); max-width:440px; margin:0 auto 36px; line-height:2; }
.cta-btns { display:flex; gap:14px; justify-content:center; flex-wrap:wrap; }
.btn-gold { background:var(--gold); color:#fff; padding:13px 36px; font-size:.75rem; letter-spacing:.14em; text-transform:uppercase; text-decoration:none; border-radius:var(--radius); border:1.5px solid var(--gold); transition: var(--transition); }
.btn-gold:hover { background:transparent; color:var(--gold); }
.btn-outline-dark { background:transparent; color:var(--text); padding:13px 36px; font-size:.75rem; letter-spacing:.14em; text-transform:uppercase; text-decoration:none; border-radius:var(--radius); border:1.5px solid rgba(44,44,58,.2); transition: var(--transition); }
.btn-outline-dark:hover { border-color:var(--gold); color:var(--gold-dark); }

/* ===================== EMPTY STATE (no article found) ===================== */
.notfound-section { min-height: 50vh; display:flex; align-items:center; justify-content:center; text-align:center; padding: 140px 20px 60px; }
.notfound-section i { font-size: 3rem; color: rgba(201,168,76,.3); margin-bottom: 18px; display:block; }
.notfound-section h2 { font-family:'Playfair Display', serif; font-size: 1.8rem; color: var(--dark); margin-bottom: 10px; }
.notfound-section p { color: var(--text-muted); font-size:.9rem; margin-bottom: 26px; }

/* ===================== IMAGE LIGHTBOX (full photo viewer) ===================== */
.img-lightbox {
    position: fixed; inset: 0; z-index: 2000;
    background: rgba(10,10,14,.94);
    display: flex; align-items: center; justify-content: center;
    padding: 40px;
    opacity: 0; visibility: hidden;
    transition: opacity .3s ease, visibility .3s ease;
}
.img-lightbox.active { opacity: 1; visibility: visible; }
.img-lightbox img {
    max-width: 100%; max-height: 100%;
    object-fit: contain;
    border-radius: 4px;
    box-shadow: 0 20px 60px rgba(0,0,0,.5);
    transform: scale(.96);
    transition: transform .3s ease;
}
.img-lightbox.active img { transform: scale(1); }
.img-lightbox-close {
    position: absolute; top: 22px; right: 30px;
    width: 44px; height: 44px; border-radius: 50%;
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.2);
    color: #fff; font-size: 1.3rem;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: background .2s ease;
}
.img-lightbox-close:hover { background: rgba(224,87,122,.25); border-color: rgba(224,87,122,.5); }
@media (max-width: 576px) {
    .img-lightbox { padding: 20px; }
    .img-lightbox-close { top: 14px; right: 14px; width:38px; height:38px; font-size:1.1rem; }
}

/* ===================== FOOTER ===================== */
.footer { background-color: var(--green); color:#fff; width:100%; padding-top:50px; padding-bottom:24px; }
.footer .container { max-width:1200px; margin:0 auto; padding:0 20px; }
.footer-logo-text { font-family:'Montserrat', sans-serif; font-size:3.5rem; font-weight:600; color:var(--gold); text-align:center; display:block; text-shadow:1px 1px 2px #fff; }
.footer h6 { font-weight:500; margin-bottom:14px; color:#fdd07b; font-size:.78rem; letter-spacing:.14em; text-transform:uppercase; }
.footer a { color: rgba(255,255,255,.8); text-decoration:none; font-size:.88rem; }
.footer a:hover { color:#fff; text-decoration:underline; }
.footer-divider { width:40px; height:1px; background-color: rgba(255,255,255,.5); border:none; margin:12px auto 18px; }
.footer-about-text { font-size:.87rem; line-height:1.7; color: rgba(255,255,255,.8); }
.footer-contact span { font-size:.85rem; display:inline-block; margin:0 6px; }
.footer-social a { color:#fff; margin:0 7px; font-size:1rem; display:inline-flex; width:38px; height:38px; align-items:center; justify-content:center; border-radius:50%; border:1px solid rgba(255,255,255,.4); transition:all .3s ease; }
.footer-social a:hover { background:#fff; color:var(--gold); transform:scale(1.15); }
.back-to-top { color: rgba(255,255,255,.7); text-decoration:none; font-size:.8rem; letter-spacing:.1em; transition:color .2s; }
.back-to-top:hover { color:#fff; }

/* ===================== DARK MODE ===================== */
body.dark-mode .navbar .nav-link:hover,
body.dark-mode .navbar .nav-link.active-link {
    color: var(--gold-light);
}
body { transition: background 1s ease, color 1s ease; }
body * { transition: background 1s ease, border-color 1s ease, color 1s ease, opacity 1s ease; }
body.dark-mode { background:#121212; color:#e5e5e5; }
body.dark-mode .top-contact, body.dark-mode .navbar { background: rgba(18,18,18,.92); border-bottom-color: rgba(255,255,255,.06); }
body.dark-mode .top-contact { color:#ddd; }
body.dark-mode .navbar .nav-link { color: rgba(255,255,255,.85); }

body.dark-mode .article-body-section { background:#121212; }
body.dark-mode .article-text { color: rgba(255,255,255,.72); }
body.dark-mode .article-divider { background: var(--gold-light); }
body.dark-mode .engage-label { color: rgba(255,255,255,.4); }
body.dark-mode .share-btn { border-color: rgba(255,255,255,.14); color: rgba(255,255,255,.6); }
body.dark-mode .like-btn.on-light { background:#1c1c1c; border-color: rgba(255,255,255,.12); color:#e5e5e5; }
body.dark-mode .like-btn.on-light .heart-icon { color: rgba(255,255,255,.45); }

body.dark-mode .related-section { background:#121212; }
body.dark-mode .related-heading h2 { color:#f0f0f8; }
body.dark-mode .news-card { background:#1c1c1c; border-color: rgba(255,255,255,.05); }
body.dark-mode .news-card-title { color:#f0f0f8; }
body.dark-mode .news-card:hover .news-card-title { color: var(--gold-light); }
body.dark-mode .news-card-date { color: rgba(255,255,255,.4); }
body.dark-mode .news-read-link { border-top-color: rgba(255,255,255,.07); }

body.dark-mode .cta-section { background:#0A0A14; }
body.dark-mode .cta-section h2 { color:#f0f0f8; }
body.dark-mode .cta-section p { color: rgba(255,255,255,.4); }
body.dark-mode .btn-outline-dark { color: rgba(255,255,255,.6); border-color: rgba(255,255,255,.12); }
body.dark-mode .footer { background:#0A0A14; }
body.dark-mode .notfound-section h2 { color:#f0f0f8; }

/* ===================== RESPONSIVE ===================== */
@media (max-width: 576px) {
    .article-hero { min-height: 54vh; }
    .article-engage { flex-direction: column; align-items: flex-start; }
    .article-text p:first-of-type::first-letter { font-size: 2.8rem; }
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
        <li class="nav-item"><a class="nav-link active-link" href="vlogs.php">Media</a></li>
      </ul>

      <?php if(isset($_SESSION['user_id'])): ?>
      <?php
        $nav_fullname = $_SESSION['fullname'] ?? '';
        $nav_initials = strtoupper(implode('', array_map(fn($w) => $w[0], explode(' ', trim($nav_fullname)))));
        $nav_initials = substr($nav_initials, 0, 2);
      ?>
      <div class="dropdown" style="display:flex; align-items:center; gap:10px;">
        <a href="account.php" title="My Account" style="width:38px;height:38px;border-radius:50%;background:var(--green);color:#fff;font-size:.75rem;font-weight:600;letter-spacing:.05em;display:flex;align-items:center;justify-content:center;border:2px solid var(--gold-light);text-decoration:none;">
          <?= htmlspecialchars($nav_initials) ?>
        </a>
        <a href="user_side/log_out.php" title="Logout" style="color:#7a7a8a;font-size:1.1rem;">
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

<?php if ($article): ?>

    <!-- ===================== ARTICLE HERO ===================== -->
    <section class="article-hero">
        <?php if (!empty($article['image_path'])): ?>
            <img class="article-hero-img" id="heroImg" src="../uploads/news/<?= htmlspecialchars($article['image_path']) ?>" alt="<?= htmlspecialchars($article['title']) ?>" title="Click to view full photo">
            <div class="view-photo-badge" id="viewPhotoBadge">
                <i class="bi bi-arrows-fullscreen"></i> View Full Photo
            </div>
        <?php else: ?>
            <div class="article-hero-noimg"></div>
        <?php endif; ?>

        <div class="container article-hero-content">
            <a href="vlogs.php" class="back-link" data-aos="fade-right"><i class="bi bi-arrow-left"></i> Back to Media</a>

            <div class="article-badge" data-aos="fade-up"><?= htmlspecialchars($article['category'] ?? 'News') ?></div>
            <h1 class="article-title" data-aos="fade-up" data-aos-delay="80"><?= htmlspecialchars($article['title']) ?></h1>

            <div class="article-meta" data-aos="fade-up" data-aos-delay="160">
                <span><i class="bi bi-calendar3"></i> <?= date('F d, Y', strtotime($article['created_at'])) ?></span>
                <?php if (!empty($article['author'])): ?>
                <span><i class="bi bi-person"></i> <?= htmlspecialchars($article['author']) ?></span>
                <?php endif; ?>
                <span><i class="bi bi-eye"></i> <?= number_format($article['views'] ?? 0) ?> views</span>

                <button type="button"
                        class="like-btn <?= $hasLiked ? 'liked' : '' ?>"
                        id="likeBtnTop"
                        data-news-id="<?= (int)$article['id'] ?>"
                        aria-pressed="<?= $hasLiked ? 'true' : 'false' ?>"
                        aria-label="Like this article">
                    <i class="bi <?= $hasLiked ? 'bi-heart-fill' : 'bi-heart' ?> heart-icon"></i>
                    <span class="like-count"><?= $likeCount ?></span>
                </button>
            </div>
        </div>
    </section>

    <!-- ===================== ARTICLE BODY ===================== -->
    <section class="article-body-section">
        <div class="container">
            <div class="article-wrap">

                <?php if (!empty($article['excerpt'])): ?>
                <p style="font-family:'Playfair Display',serif; font-style:italic; font-size:1.15rem; color:var(--gold-dark); line-height:1.7; margin-bottom:34px;">
                    <?= htmlspecialchars($article['excerpt']) ?>
                </p>
                <?php endif; ?>

                <div class="article-text">
                    <?php
                    // Render stored content as paragraphs
                    $paragraphs = preg_split('/\r\n\r\n|\n\n/', trim($article['content']));
                    foreach ($paragraphs as $para) {
                        $para = trim($para);
                        if ($para === '') continue;
                        echo '<p>' . nl2br(htmlspecialchars($para)) . '</p>';
                    }
                    ?>
                </div>

                <hr class="article-divider">

                <div class="article-engage">
                    <span class="engage-label">Enjoyed this article?</span>
                    <div style="display:flex; align-items:center; gap:14px; flex-wrap:wrap;">
                        <button type="button"
                                class="like-btn on-light <?= $hasLiked ? 'liked' : '' ?>"
                                id="likeBtnBottom"
                                data-news-id="<?= (int)$article['id'] ?>"
                                aria-pressed="<?= $hasLiked ? 'true' : 'false' ?>"
                                aria-label="Like this article">
                            <i class="bi <?= $hasLiked ? 'bi-heart-fill' : 'bi-heart' ?> heart-icon"></i>
                            <span>Like</span>
                            <span class="like-count"><?= $likeCount ?></span>
                        </button>
                        <div class="share-row">
                            <a class="share-btn" title="Share on Facebook" target="_blank"
                               href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode('https://' . ($_SERVER['HTTP_HOST'] ?? '') . $_SERVER['REQUEST_URI']) ?>">
                                <i class="bi bi-facebook"></i>
                            </a>
                            <a class="share-btn" title="Copy link" id="copyLinkBtn" href="#"><i class="bi bi-link-45deg"></i></a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

<?php else: ?>

    <!-- ===================== NOT FOUND ===================== -->
    <section class="notfound-section">
        <div>
            <i class="bi bi-newspaper"></i>
            <h2>Article Not Found</h2>
            <p>This story may have been moved, unpublished, or no longer exists.</p>
            <a href="vlogs.php" class="btn-gold">Back to Media</a>
        </div>
    </section>

<?php endif; ?>

<!-- ===================== RELATED / MORE NEWS ===================== -->
<?php if (!empty($related)): ?>
<section class="related-section">
    <div class="container">
        <div class="related-heading" data-aos="fade-up">
            <div class="section-eyebrow">Keep Reading</div>
            <h2><?= $article ? 'More <em>Stories</em>' : 'Latest <em>News</em>' ?></h2>
        </div>
        <div class="news-grid">
            <?php foreach ($related as $ri => $r): ?>
            <a href="news.php?id=<?= $r['id'] ?>" class="news-card" data-aos="fade-up" data-aos-delay="<?= ($ri % 3) * 80 ?>">
                <div class="news-card-img">
                    <?php if (!empty($r['image_path'])): ?>
                        <img src="../uploads/news/<?= htmlspecialchars($r['image_path']) ?>" alt="<?= htmlspecialchars($r['title']) ?>">
                    <?php else: ?>
                        <div class="news-img-placeholder"><i class="bi bi-newspaper"></i></div>
                    <?php endif; ?>
                </div>
                <div class="news-card-body">
                    <div class="news-card-date"><i class="bi bi-calendar3"></i> <?= date('M d, Y', strtotime($r['created_at'])) ?></div>
                    <div class="news-card-title"><?= htmlspecialchars($r['title']) ?></div>
                    <span class="news-read-link">Read More <i class="bi bi-arrow-right"></i></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ===================== CTA ===================== -->
<section class="cta-section">
    <div class="container" style="position:relative;z-index:2;">
        <div class="section-eyebrow justify-content-center" data-aos="fade-up">Ready to See More?</div>
        <h2 data-aos="fade-up" data-aos-delay="80">Explore Our <em>Available</em><br>Properties Today.</h2>
        <p data-aos="fade-up" data-aos-delay="160">Loved what you read? Take the next step and explore our listings in person — or reserve online today.</p>
        <div class="cta-btns" data-aos="fade-up" data-aos-delay="240">
            <a href="all_properties.php" class="btn-gold">View Properties</a>
            <a href="contact_us.php" class="btn-outline-dark">Contact Us</a>
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
            <div class="col-12 text-center"><a href="#top" class="back-to-top">↑ Back to Top</a></div>
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

<!-- ===================== IMAGE LIGHTBOX (full photo viewer) ===================== -->
<?php if ($article && !empty($article['image_path'])): ?>
<div class="img-lightbox" id="imgLightbox">
    <div class="img-lightbox-close" id="lightboxClose"><i class="bi bi-x-lg"></i></div>
    <img src="../uploads/news/<?= htmlspecialchars($article['image_path']) ?>" alt="<?= htmlspecialchars($article['title']) ?>">
</div>
<?php endif; ?>

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

// Dark Mode (persisted, shared behavior with vlogs.php)
const toggle = document.getElementById('darkModeToggle');
if (localStorage.getItem('darkMode') === 'enabled') document.body.classList.add('dark-mode');
toggle.addEventListener('click', () => {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', document.body.classList.contains('dark-mode') ? 'enabled' : 'disabled');
});

// ===================== LIKE / HEART BUTTON =====================
const csrfToken = "<?= $csrf_token ?>";

async function toggleLike(btn) {
    const newsId = btn.dataset.newsId;
    if (!newsId) return;

    // Optimistic UI + pop animation
    btn.classList.add('pop');
    setTimeout(() => btn.classList.remove('pop'), 250);
    btn.disabled = true;

    try {
        const fd = new FormData();
        fd.append('action', 'toggle_like');
        fd.append('news_id', newsId);
        fd.append('csrf_token', csrfToken);

        const res = await fetch(window.location.pathname + window.location.search, { method: 'POST', body: fd });
        const data = await res.json();

        if (data.ok) {
            document.querySelectorAll(`.like-btn[data-news-id="${newsId}"]`).forEach(b => {
                b.classList.toggle('liked', data.liked);
                b.setAttribute('aria-pressed', data.liked ? 'true' : 'false');
                const icon = b.querySelector('.heart-icon');
                icon.classList.toggle('bi-heart-fill', data.liked);
                icon.classList.toggle('bi-heart', !data.liked);
                b.querySelector('.like-count').textContent = data.count;
            });
        }
    } catch (err) {
        console.error('Like request failed', err);
    } finally {
        btn.disabled = false;
    }
}

document.querySelectorAll('.like-btn').forEach(btn => {
    btn.addEventListener('click', () => toggleLike(btn));
});

// Copy link
const copyBtn = document.getElementById('copyLinkBtn');
if (copyBtn) {
    copyBtn.addEventListener('click', (e) => {
        e.preventDefault();
        navigator.clipboard.writeText(window.location.href).then(() => {
            const icon = copyBtn.querySelector('i');
            icon.classList.remove('bi-link-45deg');
            icon.classList.add('bi-check2');
            setTimeout(() => { icon.classList.remove('bi-check2'); icon.classList.add('bi-link-45deg'); }, 1500);
        });
    });
}

// ===================== IMAGE LIGHTBOX (full photo viewer) =====================
const heroImg = document.getElementById('heroImg');
const viewPhotoBadge = document.getElementById('viewPhotoBadge');
const lightbox = document.getElementById('imgLightbox');
const lightboxClose = document.getElementById('lightboxClose');

if (lightbox) {
    function openLightbox() {
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    function closeLightbox() {
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (heroImg) heroImg.addEventListener('click', openLightbox);
    if (viewPhotoBadge) viewPhotoBadge.addEventListener('click', openLightbox);

    lightboxClose.addEventListener('click', closeLightbox);
    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) closeLightbox();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && lightbox.classList.contains('active')) closeLightbox();
    });
}
</script>
</body>
</html>