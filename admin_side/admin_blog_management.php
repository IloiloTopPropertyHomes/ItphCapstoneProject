<?php
session_start();
require_once __DIR__ . '/../backends/config.php';

header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self';");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: no-referrer-when-downgrade");

// Auth check
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$conn = get_db_connection();

// Admin info
$stmt = $conn->prepare("SELECT username, gmail FROM admin_users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$stmt->bind_result($username, $admin_email);
if ($stmt->fetch()) {
    $_SESSION['username'] = $username;
    $_SESSION['email']    = $admin_email;
}
$stmt->close();

/* ═══════════════════════════════════════════════════════
   ACTIVE TAB  (?tab=vlogs  |  ?tab=news,  default=vlogs)
   ═══════════════════════════════════════════════════════ */
$activeTab = ($_GET['tab'] ?? 'vlogs') === 'news' ? 'news' : 'vlogs';

/* ═══════════════════════════════════════════════════════
   HELPERS
   ═══════════════════════════════════════════════════════ */
function isActive($f)    { return basename($_SERVER['PHP_SELF']) === $f ? 'active' : ''; }
function isDropdownActive($arr) {
    foreach ($arr as $f) { if (basename($_SERVER['PHP_SELF']) === $f) return 'open'; }
    return '';
}
function make_slug(string $title): string {
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    return preg_replace('/[\s-]+/', '-', $slug);
}
function unique_slug(mysqli $conn, string $base, ?int $excludeId = null): string {
    $slug = $base; $i = 1;
    while (true) {
        $stmt = $excludeId
            ? $conn->prepare("SELECT id FROM news WHERE slug=? AND id != ?")
            : $conn->prepare("SELECT id FROM news WHERE slug=?");
        $excludeId ? $stmt->bind_param("si", $slug, $excludeId) : $stmt->bind_param("s", $slug);
        $stmt->execute(); $stmt->store_result();
        if ($stmt->num_rows === 0) { $stmt->close(); break; }
        $stmt->close(); $slug = $base . '-' . $i++;
    }
    return $slug;
}

/* ═══════════════════════════════════════════════════════
   VLOG ACTIONS
   ═══════════════════════════════════════════════════════ */

/* VLOG UPLOAD */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_vlog'])) {
    $title       = trim($_POST['title']);
    $category    = $_POST['category'];
    $description = trim($_POST['description'] ?? '');

    if (isset($_FILES['video']) && $_FILES['video']['error'] === 0) {
        $uploadDir = "../uploads/vlogs/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $videoName  = time() . "_" . basename($_FILES["video"]["name"]);
        if (move_uploaded_file($_FILES["video"]["tmp_name"], $uploadDir . $videoName)) {
            $stmt = $conn->prepare("INSERT INTO vlogs (title, category, description, video_path, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssss", $title, $category, $description, $videoName);
            $stmt->execute(); $stmt->close();
            header("Location: " . $_SERVER['PHP_SELF'] . "?tab=vlogs&flash=vlog_uploaded"); exit;
        }
    }
}

/* VLOG DELETE */
if (isset($_GET['delete_vlog'])) {
    $id = intval($_GET['delete_vlog']);
    $stmt = $conn->prepare("SELECT video_path FROM vlogs WHERE id=?");
    $stmt->bind_param("i", $id); $stmt->execute();
    $stmt->bind_result($videoPath); $stmt->fetch(); $stmt->close();
    $filePath = "../uploads/vlogs/" . $videoPath;
    if (file_exists($filePath)) unlink($filePath);
    $stmt = $conn->prepare("DELETE FROM vlogs WHERE id=?");
    $stmt->bind_param("i", $id); $stmt->execute(); $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=vlogs&flash=vlog_deleted"); exit;
}

/* ═══════════════════════════════════════════════════════
   NEWS ACTIONS
   ═══════════════════════════════════════════════════════ */

/* NEWS CREATE */
if (isset($_POST['create_news'])) {
    $title    = trim($_POST['title']);
    $excerpt  = trim($_POST['excerpt'] ?? '');
    $content  = trim($_POST['content']);
    $category = $_POST['category'] ?? 'News';
    $author   = trim($_POST['author'] ?? 'Admin');
    $featured = isset($_POST['featured']) ? 1 : 0;
    $status   = $_POST['status'] ?? 'published';
    $slug     = unique_slug($conn, make_slug($title));
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $uploadDir = "../uploads/news/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image_name = time() . '_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $image_name)) $image_path = $image_name;
    }
    $stmt = $conn->prepare("INSERT INTO news (title, slug, excerpt, content, image_path, category, author, featured, status) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->bind_param("sssssssss", $title, $slug, $excerpt, $content, $image_path, $category, $author, $featured, $status);
    $stmt->execute(); $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=news&flash=news_created"); exit;
}

/* NEWS UPDATE */
if (isset($_POST['update_news'])) {
    $id       = (int)$_POST['news_id'];
    $title    = trim($_POST['title']);
    $excerpt  = trim($_POST['excerpt'] ?? '');
    $content  = trim($_POST['content']);
    $category = $_POST['category'] ?? 'News';
    $author   = trim($_POST['author'] ?? 'Admin');
    $featured = isset($_POST['featured']) ? 1 : 0;
    $status   = $_POST['status'] ?? 'published';
    $slug     = unique_slug($conn, make_slug($title), $id);
    $cur = $conn->prepare("SELECT image_path FROM news WHERE id=?");
    $cur->bind_param("i", $id); $cur->execute();
    $cur->bind_result($old_image); $cur->fetch(); $cur->close();
    $image_path = $old_image;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $uploadDir = "../uploads/news/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image_name = time() . '_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $image_name)) {
            if ($old_image && file_exists($uploadDir . $old_image)) unlink($uploadDir . $old_image);
            $image_path = $image_name;
        }
    }
    $stmt = $conn->prepare("UPDATE news SET title=?, slug=?, excerpt=?, content=?, image_path=?, category=?, author=?, featured=?, status=? WHERE id=?");
    $stmt->bind_param("sssssssssi", $title, $slug, $excerpt, $content, $image_path, $category, $author, $featured, $status, $id);
    $stmt->execute(); $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=news&flash=news_updated"); exit;
}

/* NEWS DELETE */
if (isset($_GET['delete_news'])) {
    $id = (int)$_GET['delete_news'];
    $cur = $conn->prepare("SELECT image_path FROM news WHERE id=?");
    $cur->bind_param("i", $id); $cur->execute();
    $cur->bind_result($img); $cur->fetch(); $cur->close();
    if ($img && file_exists("../uploads/news/" . $img)) unlink("../uploads/news/" . $img);
    $stmt = $conn->prepare("DELETE FROM news WHERE id=?");
    $stmt->bind_param("i", $id); $stmt->execute(); $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=news&flash=news_deleted"); exit;
}

/* NEWS TOGGLE STATUS */
if (isset($_POST['toggle_status'])) {
    $id = (int)$_POST['news_id']; $new_status = $_POST['new_status'];
    $stmt = $conn->prepare("UPDATE news SET status=? WHERE id=?");
    $stmt->bind_param("si", $new_status, $id); $stmt->execute(); $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=news&flash=news_status"); exit;
}

/* NEWS TOGGLE FEATURED */
if (isset($_POST['toggle_featured'])) {
    $id = (int)$_POST['news_id']; $featured = (int)$_POST['new_featured'];
    $stmt = $conn->prepare("UPDATE news SET featured=? WHERE id=?");
    $stmt->bind_param("ii", $featured, $id); $stmt->execute(); $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF'] . "?tab=news&flash=news_featured"); exit;
}

/* ═══════════════════════════════════════════════════════
   FETCH – VLOGS
   ═══════════════════════════════════════════════════════ */
$vlogResult = $conn->query("SELECT id, title, category, description, video_path, created_at FROM vlogs ORDER BY created_at DESC");
$totalVlogs = $vlogResult->num_rows;
$allVlogs   = [];
while ($r = $vlogResult->fetch_assoc()) $allVlogs[] = $r;

$vlogCatResult = $conn->query("SELECT category, COUNT(*) as cnt FROM vlogs GROUP BY category");
$vlogCatCounts = ['tour' => 0, 'tips' => 0, 'subdivision' => 0];
while ($r = $vlogCatResult->fetch_assoc()) {
    if (isset($vlogCatCounts[$r['category']])) $vlogCatCounts[$r['category']] = $r['cnt'];
}

$vlogCategoryLabels = ['tour' => 'Property Tour', 'tips' => 'Real Estate Tips', 'subdivision' => 'Subdivision'];

/* ═══════════════════════════════════════════════════════
   FETCH – NEWS
   ═══════════════════════════════════════════════════════ */
$edit_row = null;
if (isset($_GET['edit'])) {
    $eid  = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM news WHERE id=?");
    $stmt->bind_param("i", $eid); $stmt->execute();
    $edit_row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    $activeTab = 'news';
}

$filter_status = $_GET['status'] ?? 'all';
$filter_cat    = $_GET['cat']    ?? 'all';
$newsCats      = ['News', 'Announcement', 'Update', 'Event', 'Press Release'];

$where_clauses = []; $bind_types = ''; $bind_vals = [];
if ($filter_status !== 'all') { $where_clauses[] = "status=?";   $bind_types .= 's'; $bind_vals[] = $filter_status; }
if ($filter_cat    !== 'all') { $where_clauses[] = "category=?"; $bind_types .= 's'; $bind_vals[] = $filter_cat; }
$sql = "SELECT * FROM news" . ($where_clauses ? " WHERE " . implode(" AND ", $where_clauses) : "") . " ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
if ($bind_types) $stmt->bind_param($bind_types, ...$bind_vals);
$stmt->execute();
$all_news = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

$total_news      = $conn->query("SELECT COUNT(*) FROM news")->fetch_row()[0] ?? 0;
$total_published = $conn->query("SELECT COUNT(*) FROM news WHERE status='published'")->fetch_row()[0] ?? 0;
$total_draft     = $conn->query("SELECT COUNT(*) FROM news WHERE status='draft'")->fetch_row()[0] ?? 0;
$total_featured  = $conn->query("SELECT COUNT(*) FROM news WHERE featured=1")->fetch_row()[0] ?? 0;
$total_views     = $conn->query("SELECT COALESCE(SUM(views),0) FROM news")->fetch_row()[0] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Blog & News Management — ITPH Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
    --bg:            #0f172a;
    --bg-card:       #1e293b;
    --bg-hover:      #334155;
    --bg-input:      #0f172a;
    --border:        #334155;
    --text-primary:  #f1f5f9;
    --text-secondary:#94a3b8;
    --text-muted:    #64748b;
    --accent:        #3b82f6;
    --accent-light:  rgba(59,130,246,.15);
    --accent-glow:   rgba(59,130,246,.25);
    --success:       #22c55e;
    --success-dim:   rgba(34,197,94,.15);
    --warning:       #f59e0b;
    --warning-dim:   rgba(245,158,11,.15);
    --danger:        #ef4444;
    --danger-dim:    rgba(239,68,68,.15);
    --purple:        #a855f7;
    --purple-dim:    rgba(168,85,247,.15);
    --radius:        12px;
    --radius-sm:     8px;
    --shadow-lg:     0 10px 15px -3px rgba(0,0,0,.4);
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text-primary);line-height:1.6;}
::-webkit-scrollbar{width:6px;height:6px;}
::-webkit-scrollbar-track{background:transparent;}
::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px;}

/* ── LAYOUT ── */
.dashboard{display:flex;min-height:100vh;}

/* ── SIDEBAR ── */
.sidebar{width:260px;background:var(--bg-card);border-right:1px solid var(--border);position:fixed;height:100vh;left:0;top:0;z-index:100;overflow-y:auto;overflow-x:hidden;transition:transform .3s ease;}
.sidebar-header{padding:24px 20px;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--bg-card);z-index:10;}
.logo{display:flex;align-items:center;gap:12px;text-decoration:none;color:inherit;}
.logo-icon{width:40px;height:40px;background:linear-gradient(135deg,var(--accent),#60a5fa);border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;font-size:18px;color:white;flex-shrink:0;}
.logo-text{font-size:18px;font-weight:700;line-height:1.2;}
.logo-text span{color:var(--accent);}
.logo-sub{font-size:11px;color:var(--text-muted);margin-top:2px;}
.sidebar-nav{padding:16px 12px;}
.nav-section{padding:16px 16px 8px;font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;}
.nav-link{display:flex;align-items:center;gap:12px;padding:12px 16px;margin-bottom:4px;color:var(--text-secondary);text-decoration:none;border-radius:var(--radius-sm);font-size:14px;font-weight:500;transition:all .2s;border:none;background:none;width:100%;cursor:pointer;font-family:inherit;text-align:left;}
.nav-link:hover{background:var(--accent-light);color:var(--text-primary);}
.nav-link.active{background:var(--accent-light);color:var(--accent);}
.nav-link.logout{color:var(--danger);}
.nav-link.logout:hover{background:var(--danger-dim);}
.nav-link i{width:20px;text-align:center;font-size:16px;flex-shrink:0;}
.nav-link i.dropdown-arrow{margin-left:auto;font-size:12px;transition:transform .2s;width:auto;}
.nav-dropdown{margin-bottom:4px;}
.nav-dropdown.open>.nav-link .dropdown-arrow{transform:rotate(180deg);}
.nav-dropdown.open>.dropdown-menu{max-height:200px;opacity:1;}
.dropdown-menu{max-height:0;opacity:0;overflow:hidden;transition:all .3s ease;padding-left:48px;}
.dropdown-item{display:block;padding:10px 16px;color:var(--text-muted);text-decoration:none;font-size:13px;font-weight:500;border-radius:6px;transition:all .2s;margin-bottom:2px;}
.dropdown-item:hover{color:var(--text-primary);background:rgba(59,130,246,.05);}
.dropdown-item.active{color:var(--accent);background:rgba(59,130,246,.1);}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:99;backdrop-filter:blur(4px);}
.sidebar-overlay.active{display:block;}

/* ── MAIN ── */
.main-content{flex:1;margin-left:260px;min-height:100vh;}

/* ── TOPBAR ── */
.topbar{height:64px;background:var(--bg-card);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 24px;position:sticky;top:0;z-index:50;}
.topbar-left{display:flex;align-items:center;gap:16px;}
.menu-toggle{display:none;background:none;border:none;color:var(--text-primary);font-size:20px;cursor:pointer;padding:8px;border-radius:var(--radius-sm);}
.menu-toggle:hover{background:var(--bg-hover);}
.page-title{font-size:20px;font-weight:600;}
.breadcrumb{font-size:13px;color:var(--text-muted);}
.topbar-right{display:flex;align-items:center;gap:16px;}
.user-menu{display:flex;align-items:center;gap:12px;padding:6px 12px;border-radius:var(--radius-sm);cursor:pointer;transition:all .2s;}
.user-menu:hover{background:var(--bg-hover);}
.user-avatar{width:36px;height:36px;background:linear-gradient(135deg,var(--accent),#60a5fa);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:14px;color:white;}
.user-info{text-align:right;}
.user-name{font-size:14px;font-weight:600;}
.user-role{font-size:12px;color:var(--text-muted);}

/* ── FLASH ── */
.flash-bar{display:flex;align-items:center;gap:8px;padding:10px 20px;margin:16px 24px 0;border-radius:var(--radius-sm);font-size:13px;font-weight:600;animation:fadeSlide .4s ease;}
.flash-bar.success{background:var(--success-dim);color:var(--success);border:1px solid rgba(34,197,94,.25);}
.flash-bar.info{background:var(--accent-light);color:var(--accent);border:1px solid rgba(59,130,246,.25);}
.flash-bar.warning{background:var(--warning-dim);color:var(--warning);border:1px solid rgba(245,158,11,.25);}
@keyframes fadeSlide{from{opacity:0;transform:translateY(-8px);}to{opacity:1;transform:translateY(0);}}

/* ── CONTENT ── */
.content{padding:24px;max-width:1400px;}

/* ── TAB SWITCHER ── */
.tab-switcher{display:flex;gap:4px;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:6px;margin-bottom:24px;width:fit-content;}
.tab-btn{padding:10px 24px;border-radius:var(--radius-sm);border:none;background:transparent;color:var(--text-secondary);font-size:14px;font-weight:600;cursor:pointer;font-family:inherit;transition:all .2s;display:flex;align-items:center;gap:8px;}
.tab-btn:hover{color:var(--text-primary);}
.tab-btn.active{background:var(--accent);color:white;box-shadow:0 2px 8px var(--accent-glow);}

/* ── STATS ── */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px;}
.stat-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:20px;position:relative;overflow:hidden;transition:all .3s;}
.stat-card:hover{border-color:var(--accent);transform:translateY(-2px);box-shadow:var(--shadow-lg);}
.stat-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;}
.stat-icon{width:44px;height:44px;border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;font-size:18px;}
.stat-icon.blue{background:var(--accent-light);color:#60a5fa;}
.stat-icon.green{background:var(--success-dim);color:#4ade80;}
.stat-icon.amber{background:var(--warning-dim);color:#fbbf24;}
.stat-icon.purple{background:var(--purple-dim);color:#c084fc;}
.stat-icon.red{background:var(--danger-dim);color:#f87171;}
.stat-value{font-size:28px;font-weight:700;margin-bottom:2px;}
.stat-label{font-size:13px;color:var(--text-secondary);}
.stat-trend{font-size:11px;font-weight:600;padding:3px 7px;border-radius:20px;}
.stat-trend.up{background:var(--success-dim);color:#4ade80;}

/* ── CARD ── */
.card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:24px;overflow:hidden;}
.card-header{padding:20px 24px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;}
.card-title{font-size:16px;font-weight:600;}
.card-subtitle{font-size:13px;color:var(--text-muted);margin-top:3px;}
.card-body{padding:24px;}

/* ── FORM ── */
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
.form-group{display:flex;flex-direction:column;gap:7px;}
.form-group.span2{grid-column:1/-1;}
label{font-size:12px;font-weight:600;color:var(--text-secondary);letter-spacing:.3px;text-transform:uppercase;}
label .req{color:var(--danger);margin-left:2px;}
input[type="text"],input[type="url"],select,textarea{background:var(--bg-input);border:1px solid var(--border);border-radius:var(--radius-sm);color:var(--text-primary);font-family:'Inter',sans-serif;font-size:14px;padding:10px 14px;outline:none;transition:border-color .2s,box-shadow .2s;width:100%;}
input:focus,select:focus,textarea:focus{border-color:var(--accent);box-shadow:0 0 0 3px var(--accent-light);}
select{cursor:pointer;}
textarea{resize:vertical;min-height:110px;}
.file-zone{border:2px dashed var(--border);border-radius:var(--radius-sm);padding:22px;text-align:center;cursor:pointer;transition:border-color .2s,background .2s;position:relative;}
.file-zone:hover,.file-zone.drag{border-color:var(--accent);background:var(--accent-light);}
.file-zone input[type="file"]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;padding:0;border:none;background:none;}
.file-zone-icon{font-size:26px;color:var(--text-muted);margin-bottom:7px;}
.file-zone-text{font-size:13px;color:var(--text-secondary);}
.file-zone-hint{font-size:11px;color:var(--text-muted);margin-top:4px;}
.file-name{font-size:12px;color:var(--accent);margin-top:6px;display:none;font-family:monospace;}
.img-preview{width:100%;max-height:180px;object-fit:cover;border-radius:var(--radius-sm);border:1px solid var(--border);margin-top:10px;display:none;}
.checkbox-row{display:flex;align-items:center;gap:10px;}
.checkbox-row input[type="checkbox"]{width:18px;height:18px;accent-color:var(--accent);cursor:pointer;}
.checkbox-row label{text-transform:none;font-size:14px;font-weight:500;color:var(--text-primary);cursor:pointer;}

/* ── CATEGORY PILLS (vlogs) ── */
.cat-pills{display:flex;gap:8px;flex-wrap:wrap;}
.cat-pill{display:flex;align-items:center;gap:6px;padding:7px 14px;border-radius:99px;border:1.5px solid var(--border);background:transparent;color:var(--text-secondary);font-size:13px;font-weight:500;cursor:pointer;font-family:inherit;transition:all .2s;}
.cat-pill:hover{border-color:var(--accent);color:var(--text-primary);}
.cat-pill.selected{border-color:transparent;color:#fff;}
.cat-pill.selected.tour{background:var(--accent);}
.cat-pill.selected.tips{background:var(--success);}
.cat-pill.selected.subdivision{background:var(--warning);}

/* ── BUTTONS ── */
.btn{display:inline-flex;align-items:center;gap:8px;padding:10px 18px;border-radius:var(--radius-sm);font-size:13px;font-weight:600;cursor:pointer;transition:all .2s;border:none;font-family:inherit;text-decoration:none;}
.btn:active{transform:scale(.97);}
.btn-primary{background:var(--accent);color:white;}
.btn-primary:hover{background:#2563eb;box-shadow:0 4px 12px var(--accent-glow);}
.btn-warning{background:var(--warning-dim);color:var(--warning);border:1px solid rgba(245,158,11,.25);}
.btn-warning:hover{background:var(--warning);color:white;}
.btn-danger{background:var(--danger-dim);color:var(--danger);border:1px solid rgba(239,68,68,.25);}
.btn-danger:hover{background:var(--danger);color:white;}
.btn-ghost{background:var(--bg-hover);color:var(--text-secondary);border:1px solid var(--border);}
.btn-ghost:hover{color:var(--text-primary);}
.btn-sm{padding:6px 12px;font-size:12px;}
.btn-row{display:flex;align-items:center;gap:10px;padding-top:8px;flex-wrap:wrap;}

/* ── FILTER / SEARCH ── */
.table-toolbar{display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:14px 24px;border-bottom:1px solid var(--border);background:rgba(255,255,255,.02);}
.search-box{position:relative;flex:1;min-width:200px;}
.search-box i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:13px;pointer-events:none;}
.search-box input{padding-left:36px;padding-top:8px;padding-bottom:8px;font-size:13px;}
.filter-group{display:flex;gap:6px;flex-wrap:wrap;}
.filter-pill{padding:7px 14px;border-radius:100px;border:1px solid var(--border);background:transparent;color:var(--text-secondary);font-size:12px;font-weight:600;cursor:pointer;font-family:inherit;transition:all .2s;}
.filter-pill:hover{border-color:var(--accent);color:var(--text-primary);}
.filter-pill.active{background:var(--accent);border-color:var(--accent);color:white;}
.count-badge{font-size:11px;font-weight:700;background:var(--bg-hover);color:var(--text-muted);padding:3px 9px;border-radius:99px;border:1px solid var(--border);}

/* ── TABLE ── */
.table-wrap{overflow-x:auto;}
table{width:100%;border-collapse:collapse;min-width:700px;}
th{text-align:left;padding:12px 16px;font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border);white-space:nowrap;background:rgba(255,255,255,.02);}
td{padding:14px 16px;font-size:14px;border-bottom:1px solid var(--border);vertical-align:middle;}
tr:hover td{background:rgba(255,255,255,.02);}
tr:last-child td{border-bottom:none;}
.td-title{font-weight:600;max-width:220px;}
.td-title small{display:block;font-size:11px;color:var(--text-muted);font-family:monospace;margin-top:2px;font-weight:400;}
.td-excerpt{font-size:13px;color:var(--text-secondary);max-width:200px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;}
.td-desc{font-size:13px;color:var(--text-secondary);max-width:200px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;}
.td-desc.empty{color:var(--text-muted);font-style:italic;}
.news-thumb{width:70px;height:50px;object-fit:cover;border-radius:6px;border:1px solid var(--border);display:block;}
.no-thumb{width:70px;height:50px;background:var(--bg-hover);border-radius:6px;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:var(--text-muted);font-size:16px;}
.video-thumb{border-radius:var(--radius-sm);border:1px solid var(--border);max-width:160px;display:block;background:#000;}

/* ── BADGES ── */
.badge{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:100px;font-size:11px;font-weight:600;}
.badge-tour{background:var(--accent-light);color:#60a5fa;}
.badge-tips{background:var(--success-dim);color:#4ade80;}
.badge-subdivision{background:var(--warning-dim);color:#fbbf24;}
.badge-cat{background:var(--accent-light);color:#60a5fa;}
.badge-published{background:var(--success-dim);color:#4ade80;}
.badge-draft{background:var(--warning-dim);color:#fbbf24;}
.badge-dot{width:6px;height:6px;border-radius:50%;}

/* ── EMPTY STATE ── */
.empty-state{text-align:center;padding:60px 24px;color:var(--text-muted);}
.empty-state i{font-size:48px;margin-bottom:16px;opacity:.4;}
.empty-state p{font-size:15px;}

/* ── MODAL ── */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:500;align-items:center;justify-content:center;backdrop-filter:blur(4px);}
.modal-bg.show{display:flex;}
.modal{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:28px;max-width:420px;width:90%;animation:modalIn .25s ease;}
@keyframes modalIn{from{transform:scale(.94);opacity:0;}to{transform:scale(1);opacity:1;}}
.modal-icon{width:48px;height:48px;background:var(--danger-dim);border-radius:50%;display:flex;align-items:center;justify-content:center;color:var(--danger);font-size:22px;margin-bottom:16px;}
.modal h3{font-size:17px;margin-bottom:8px;}
.modal p{font-size:13.5px;color:var(--text-secondary);margin-bottom:22px;line-height:1.6;}
.modal-btns{display:flex;gap:10px;justify-content:flex-end;}

/* ── PANEL VISIBILITY ── */
.tab-panel{display:none;}
.tab-panel.active{display:block;}

/* ── RESPONSIVE ── */
@media(max-width:768px){
    .sidebar{transform:translateX(-100%);}
    .sidebar.open{transform:translateX(0);}
    .main-content{margin-left:0;}
    .menu-toggle{display:block;}
    .content{padding:16px;}
    .form-grid{grid-template-columns:1fr;}
    .stats-grid{grid-template-columns:1fr 1fr;}
    .user-info{display:none;}
    .tab-switcher{width:100%;}
    .tab-btn{flex:1;justify-content:center;}
}
@media(max-width:480px){
    .stats-grid{grid-template-columns:1fr;}
    .filter-group{display:none;}
}
</style>
</head>
<body>

<!-- VLOG DELETE MODAL -->
<div class="modal-bg" id="vlogDeleteModal">
    <div class="modal">
        <div class="modal-icon"><i class="fa-solid fa-trash"></i></div>
        <h3>Delete this vlog?</h3>
        <p>This will permanently remove the video and its data. This action cannot be undone.</p>
        <div class="modal-btns">
            <button class="btn btn-ghost" onclick="closeModal('vlogDeleteModal')">Cancel</button>
            <a href="#" id="vlogConfirmDeleteBtn" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Delete</a>
        </div>
    </div>
</div>

<!-- NEWS DELETE MODAL -->
<div class="modal-bg" id="newsDeleteModal">
    <div class="modal">
        <div class="modal-icon"><i class="fa-solid fa-trash"></i></div>
        <h3>Delete this article?</h3>
        <p id="newsDeleteText">This will permanently remove the article and its image. This action cannot be undone.</p>
        <div class="modal-btns">
            <button class="btn btn-ghost" onclick="closeModal('newsDeleteModal')">Cancel</button>
            <a href="#" id="newsConfirmDeleteBtn" class="btn btn-danger"><i class="fa-solid fa-trash"></i> Delete</a>
        </div>
    </div>
</div>

<div class="dashboard">
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="../admin_side/index.php" class="logo">
                <div class="logo-icon"><i class="fa-solid fa-building"></i></div>
                <div>
                    <div class="logo-text">ITPH <span>Admin</span></div>
                    <div class="logo-sub">Real Estate Management</div>
                </div>
            </a>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section">Main</div>
            <a href="../admin_side/index.php" class="nav-link <?= isActive('index.php') ?>">
                <i class="fa-solid fa-chart-line"></i><span>Dashboard</span>
            </a>
            <a href="../admin_side/ad_account.php" class="nav-link <?= isActive('ad_account.php') ?>">
                <i class="fa-solid fa-user-cog"></i><span>My Account</span>
            </a>
            <div class="nav-section">Management</div>
            <div class="nav-dropdown <?= isDropdownActive(['customer_ban.php','customer_appointments.php']) ?>">
                <button class="nav-link" onclick="toggleDropdown(this)">
                    <i class="fa-solid fa-users"></i><span>Manage Customers</span>
                    <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                </button>
                <div class="dropdown-menu">
                    <a href="../admin_side/customer_ban.php" class="dropdown-item <?= isActive('customer_ban.php') ?>">Ban / Unban</a>
                    <a href="../admin_side/customer_appointments.php" class="dropdown-item <?= isActive('customer_appointments.php') ?>">Appointments History</a>
                </div>
            </div>
            <div class="nav-dropdown <?= isDropdownActive(['add-property.php','update_properties.php']) ?>">
                <button class="nav-link" onclick="toggleDropdown(this)">
                    <i class="fa-solid fa-house"></i><span>Properties</span>
                    <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                </button>
                <div class="dropdown-menu">
                    <a href="../backends/add-property.php" class="dropdown-item <?= isActive('add-property.php') ?>">Add Property</a>
                    <a href="../admin_side/update_properties.php" class="dropdown-item <?= isActive('update_properties.php') ?>">Update Property</a>
                </div>
            </div>
            <a href="../admin_side/admin_blog_management.php" class="nav-link active">
                <i class="fa-solid fa-photo-film"></i><span>Blog & News</span>
            </a>
            <a href="../admin_side/transaction.php" class="nav-link <?= isActive('transaction.php') ?>">
                <i class="fa-solid fa-money-bill-transfer"></i><span>Transactions</span>
            </a>
            <a href="../admin_side/admin_message.php" class="nav-link <?= isActive('admin_message.php') ?>">
                <i class="fa-solid fa-envelope"></i><span>Messages</span>
            </a>
            <a href="../admin_side/manage_agent.php" class="nav-link <?= isActive('manage_agent.php') ?>">
                <i class="fa-solid fa-user-tie"></i><span>Manage Agents</span>
            </a>
            <div class="nav-section">System</div>
            <a href="../admin_side/audit_log.php" class="nav-link <?= isActive('audit_log.php') ?>">
                <i class="fa-solid fa-shield-halved"></i><span>Audit Log</span>
            </a>
            <a href="logout.php" class="nav-link logout">
                <i class="fa-solid fa-right-from-bracket"></i><span>Logout</span>
            </a>
        </nav>
    </aside>

    <!-- MAIN -->
    <main class="main-content">

        <!-- TOPBAR -->
        <header class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle" onclick="toggleSidebar()">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div>
                    <div class="page-title">Blog & News Management</div>
                    <div class="breadcrumb">Home / Blog & News</div>
                </div>
            </div>
            <div class="topbar-right">
                <div class="user-menu">
                    <div class="user-info">
                        <div class="user-name"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></div>
                        <div class="user-role">Administrator</div>
                    </div>
                    <div class="user-avatar"><?= strtoupper(substr($_SESSION['username'] ?? 'A', 0, 1)) ?></div>
                </div>
            </div>
        </header>

        <!-- FLASH MESSAGES -->
        <?php
        $flashMap = [
            'vlog_uploaded' => ['success', '<i class="fa-solid fa-check-circle"></i> Vlog uploaded successfully!'],
            'vlog_deleted'  => ['info',    '<i class="fa-solid fa-trash"></i> Vlog deleted.'],
            'news_created'  => ['success', '<i class="fa-solid fa-check-circle"></i> Article published!'],
            'news_updated'  => ['success', '<i class="fa-solid fa-check-circle"></i> Article updated!'],
            'news_deleted'  => ['info',    '<i class="fa-solid fa-trash"></i> Article deleted.'],
            'news_status'   => ['warning', '<i class="fa-solid fa-toggle-on"></i> Status changed.'],
            'news_featured' => ['info',    '<i class="fa-solid fa-star"></i> Featured status updated.'],
        ];
        if (isset($_GET['flash']) && isset($flashMap[$_GET['flash']])):
            [$ftype, $fmsg] = $flashMap[$_GET['flash']]; ?>
        <div class="flash-bar <?= $ftype ?>" id="flashBar">
            <?= $fmsg ?>
            <button onclick="document.getElementById('flashBar').remove()" style="margin-left:auto;background:none;border:none;color:inherit;cursor:pointer;font-size:16px;">×</button>
        </div>
        <?php endif; ?>

        <div class="content">

            <!-- TAB SWITCHER -->
            <div class="tab-switcher">
                <button class="tab-btn <?= $activeTab==='vlogs'?'active':'' ?>" onclick="switchTab('vlogs')">
                    <i class="fa-solid fa-video"></i> Vlog Management
                </button>
                <button class="tab-btn <?= $activeTab==='news'?'active':'' ?>" onclick="switchTab('news')">
                    <i class="fa-solid fa-newspaper"></i> News Management
                </button>
            </div>

            <!-- ══════════════════════════════════════
                 VLOG TAB
                 ══════════════════════════════════════ -->
            <div class="tab-panel <?= $activeTab==='vlogs'?'active':'' ?>" id="tab-vlogs">

                <!-- VLOG STATS -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header"><div class="stat-icon blue"><i class="fa-solid fa-video"></i></div></div>
                        <div class="stat-value"><?= $totalVlogs ?></div>
                        <div class="stat-label">Total Vlogs</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header"><div class="stat-icon blue"><i class="fa-solid fa-house-chimney-window"></i></div></div>
                        <div class="stat-value"><?= $vlogCatCounts['tour'] ?></div>
                        <div class="stat-label">Property Tours</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header"><div class="stat-icon green"><i class="fa-solid fa-lightbulb"></i></div></div>
                        <div class="stat-value"><?= $vlogCatCounts['tips'] ?></div>
                        <div class="stat-label">Real Estate Tips</div>
                    </div>
                </div>

                <!-- VLOG UPLOAD FORM -->
                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title"><i class="fa-solid fa-cloud-arrow-up" style="color:var(--accent);margin-right:8px;"></i>Upload New Vlog</div>
                            <div class="card-subtitle">Fill in the details and attach a video file</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Vlog Title <span class="req">*</span></label>
                                    <input type="text" name="title" placeholder="e.g. Monticello Model Unit Tour" required>
                                </div>
                                <div class="form-group">
                                    <label>Category <span class="req">*</span></label>
                                    <input type="hidden" name="category" id="vlogCategoryInput" value="tour">
                                    <div class="cat-pills">
                                        <button type="button" class="cat-pill tour selected" data-value="tour" onclick="selectVlogCat(this)">
                                            <i class="fa-solid fa-house-chimney"></i> Property Tour
                                        </button>
                                        <button type="button" class="cat-pill tips" data-value="tips" onclick="selectVlogCat(this)">
                                            <i class="fa-solid fa-lightbulb"></i> RE Tips
                                        </button>
                                        <button type="button" class="cat-pill subdivision" data-value="subdivision" onclick="selectVlogCat(this)">
                                            <i class="fa-solid fa-city"></i> Subdivision
                                        </button>
                                    </div>
                                </div>
                                <div class="form-group span2">
                                    <label>Description <span style="color:var(--text-muted);text-transform:none;font-weight:400;">(optional)</span></label>
                                    <textarea name="description" placeholder="Brief summary of what this vlog covers…"></textarea>
                                </div>
                                <div class="form-group span2">
                                    <label>Video File <span class="req">*</span></label>
                                    <div class="file-zone" id="vlogFileZone">
                                        <input type="file" name="video" accept="video/*" required id="videoInput" onchange="onVideoChange(this)">
                                        <div class="file-zone-icon"><i class="fa-solid fa-film"></i></div>
                                        <div class="file-zone-text">Drag & drop a video or <strong>click to browse</strong></div>
                                        <div class="file-zone-hint">Supports MP4, MOV, AVI, WebM</div>
                                        <div class="file-name" id="videoFileName"></div>
                                    </div>
                                </div>
                                <div class="form-group span2">
                                    <div class="btn-row">
                                        <button type="submit" name="upload_vlog" class="btn btn-primary">
                                            <i class="fa-solid fa-cloud-arrow-up"></i> Upload Vlog
                                        </button>
                                        <button type="reset" class="btn btn-ghost" onclick="resetVlogForm()">
                                            <i class="fa-solid fa-rotate-left"></i> Reset
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- VLOG TABLE -->
                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">Uploaded Vlogs</div>
                            <div class="card-subtitle"><?= $totalVlogs ?> video<?= $totalVlogs !== 1 ? 's' : '' ?> on record</div>
                        </div>
                        <span class="count-badge" id="vlogVisibleCount"><?= $totalVlogs ?> shown</span>
                    </div>
                    <div class="table-toolbar">
                        <div class="search-box">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="vlogSearch" placeholder="Search by title or description…" oninput="filterVlogs()">
                        </div>
                        <div class="filter-group">
                            <button class="filter-pill active" data-cat="all" onclick="setVlogFilter(this)">All</button>
                            <button class="filter-pill" data-cat="tour" onclick="setVlogFilter(this)">Tours</button>
                            <button class="filter-pill" data-cat="tips" onclick="setVlogFilter(this)">Tips</button>
                            <button class="filter-pill" data-cat="subdivision" onclick="setVlogFilter(this)">Subdivisions</button>
                        </div>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($allVlogs)): ?>
                        <div class="empty-state">
                            <i class="fa-solid fa-video-slash"></i>
                            <p>No vlogs yet. Upload your first one above!</p>
                        </div>
                        <?php else: ?>
                        <div class="table-wrap">
                            <table id="vlogTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Title & Date</th>
                                        <th>Category</th>
                                        <th>Description</th>
                                        <th>Preview</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($allVlogs as $i => $row):
                                    $catLabel = $vlogCategoryLabels[$row['category']] ?? ucfirst($row['category']);
                                    $catIcons = ['tour' => 'fa-house-chimney', 'tips' => 'fa-lightbulb', 'subdivision' => 'fa-city'];
                                    $ico = $catIcons[$row['category']] ?? 'fa-tag';
                                ?>
                                <tr data-cat="<?= htmlspecialchars($row['category']) ?>"
                                    data-title="<?= htmlspecialchars(strtolower($row['title'])) ?>"
                                    data-desc="<?= htmlspecialchars(strtolower($row['description'] ?? '')) ?>">
                                    <td style="color:var(--text-muted);font-family:monospace;font-size:12px;"><?= str_pad($i+1,2,'0',STR_PAD_LEFT) ?></td>
                                    <td>
                                        <div class="td-title">
                                            <?= htmlspecialchars($row['title']) ?>
                                            <small><?= date('M d, Y', strtotime($row['created_at'])) ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= htmlspecialchars($row['category']) ?>">
                                            <i class="fa-solid <?= $ico ?>"></i>
                                            <?= htmlspecialchars($catLabel) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['description'])): ?>
                                            <div class="td-desc"><?= htmlspecialchars($row['description']) ?></div>
                                        <?php else: ?>
                                            <div class="td-desc empty">No description</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <video class="video-thumb" width="160" controls preload="none">
                                            <source src="../uploads/vlogs/<?= htmlspecialchars($row['video_path']) ?>" type="video/mp4">
                                        </video>
                                    </td>
                                    <td>
                                        <button class="btn btn-danger btn-sm"
                                            onclick="openVlogDeleteModal(<?= $row['id'] ?>, '<?= addslashes(htmlspecialchars($row['title'])) ?>')">
                                            <i class="fa-solid fa-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div><!-- /tab-vlogs -->

            <!-- ══════════════════════════════════════
                 NEWS TAB
                 ══════════════════════════════════════ -->
            <div class="tab-panel <?= $activeTab==='news'?'active':'' ?>" id="tab-news">

                <!-- NEWS STATS -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon blue"><i class="fa-solid fa-newspaper"></i></div>
                            <span class="stat-trend up"><i class="fa-solid fa-arrow-trend-up"></i> Active</span>
                        </div>
                        <div class="stat-value"><?= $total_news ?></div>
                        <div class="stat-label">Total Articles</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header"><div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div></div>
                        <div class="stat-value"><?= $total_published ?></div>
                        <div class="stat-label">Published</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header"><div class="stat-icon amber"><i class="fa-solid fa-file-pen"></i></div></div>
                        <div class="stat-value"><?= $total_draft ?></div>
                        <div class="stat-label">Drafts</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header"><div class="stat-icon purple"><i class="fa-solid fa-star"></i></div></div>
                        <div class="stat-value"><?= $total_featured ?></div>
                        <div class="stat-label">Featured</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header"><div class="stat-icon blue"><i class="fa-solid fa-eye"></i></div></div>
                        <div class="stat-value"><?= number_format($total_views) ?></div>
                        <div class="stat-label">Total Views</div>
                    </div>
                </div>

                <!-- NEWS CREATE / EDIT FORM -->
                <div class="card" id="newsFormCard">
                    <div class="card-header">
                        <div>
                            <div class="card-title">
                                <?= $edit_row
                                    ? '<i class="fa-solid fa-pen" style="color:var(--warning);margin-right:8px;"></i>Edit Article'
                                    : '<i class="fa-solid fa-plus-circle" style="color:var(--accent);margin-right:8px;"></i>Create New Article' ?>
                            </div>
                            <div class="card-subtitle"><?= $edit_row ? 'Update the article details below' : 'Fill in the fields to publish a news article' ?></div>
                        </div>
                        <?php if ($edit_row): ?>
                        <a href="admin_blog_management.php?tab=news" class="btn btn-ghost btn-sm">
                            <i class="fa-solid fa-xmark"></i> Cancel Edit
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" id="newsForm">
                            <?php if ($edit_row): ?>
                                <input type="hidden" name="news_id" value="<?= $edit_row['id'] ?>">
                            <?php endif; ?>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Article Title <span class="req">*</span></label>
                                    <input type="text" name="title" placeholder="e.g. ITPH Opens New Branch in Iloilo City" required
                                        value="<?= htmlspecialchars($edit_row['title'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label>Category</label>
                                    <select name="category">
                                        <?php foreach ($newsCats as $cat): ?>
                                        <option value="<?= $cat ?>" <?= ($edit_row['category'] ?? 'News') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Author</label>
                                    <input type="text" name="author" placeholder="Admin"
                                        value="<?= htmlspecialchars($edit_row['author'] ?? 'Admin') ?>">
                                </div>
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status">
                                        <option value="published" <?= ($edit_row['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Published</option>
                                        <option value="draft" <?= ($edit_row['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                                    </select>
                                </div>
                                <div class="form-group span2">
                                    <label>Excerpt <span style="color:var(--text-muted);text-transform:none;font-weight:400;">(short summary)</span></label>
                                    <input type="text" name="excerpt" placeholder="A short description shown in article listings…"
                                        value="<?= htmlspecialchars($edit_row['excerpt'] ?? '') ?>">
                                </div>
                                <div class="form-group span2">
                                    <label>Full Content <span class="req">*</span></label>
                                    <textarea name="content" rows="8" placeholder="Write the full article content here…" required><?= htmlspecialchars($edit_row['content'] ?? '') ?></textarea>
                                </div>
                                <div class="form-group span2">
                                    <label>Cover Image</label>
                                    <div class="file-zone" id="newsFileZone">
                                        <input type="file" name="image" accept="image/*" id="imgInput" onchange="previewImg(this)">
                                        <div class="file-zone-icon"><i class="fa-solid fa-image"></i></div>
                                        <div class="file-zone-text">Drag & drop an image or <strong>click to browse</strong></div>
                                        <div class="file-zone-hint">JPG, PNG, WebP · Max 5MB recommended</div>
                                        <div class="file-name" id="imgName"></div>
                                    </div>
                                    <?php if (!empty($edit_row['image_path'])): ?>
                                    <img src="../uploads/news/<?= htmlspecialchars($edit_row['image_path']) ?>"
                                         alt="Current cover" class="img-preview" id="imgPreview" style="display:block;">
                                    <?php else: ?>
                                    <img src="" alt="" class="img-preview" id="imgPreview">
                                    <?php endif; ?>
                                </div>
                                <div class="form-group span2">
                                    <div class="checkbox-row">
                                        <input type="checkbox" name="featured" id="featuredCheck"
                                            <?= ($edit_row['featured'] ?? 0) ? 'checked' : '' ?>>
                                        <label for="featuredCheck"><i class="fa-solid fa-star" style="color:var(--warning);"></i> Mark as Featured Article</label>
                                    </div>
                                </div>
                                <div class="form-group span2">
                                    <div class="btn-row">
                                        <?php if ($edit_row): ?>
                                        <button type="submit" name="update_news" class="btn btn-warning">
                                            <i class="fa-solid fa-floppy-disk"></i> Save Changes
                                        </button>
                                        <?php else: ?>
                                        <button type="submit" name="create_news" class="btn btn-primary">
                                            <i class="fa-solid fa-paper-plane"></i> Publish Article
                                        </button>
                                        <button type="submit" name="create_news" onclick="document.querySelector('[name=status]').value='draft'" class="btn btn-ghost">
                                            <i class="fa-solid fa-file-pen"></i> Save as Draft
                                        </button>
                                        <?php endif; ?>
                                        <button type="reset" class="btn btn-ghost" onclick="resetNewsForm()">
                                            <i class="fa-solid fa-rotate-left"></i> Reset
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- NEWS TABLE -->
                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">All Articles</div>
                            <div class="card-subtitle"><?= $total_news ?> article<?= $total_news !== 1 ? 's' : '' ?> total</div>
                        </div>
                        <span class="count-badge" id="newsTableCount"><?= count($all_news) ?> shown</span>
                    </div>
                    <div class="table-toolbar">
                        <div class="search-box">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="newsSearch" placeholder="Search by title, author, category…" oninput="filterNews()">
                        </div>
                        <div class="filter-group">
                            <button class="filter-pill <?= $filter_status==='all'?'active':'' ?>" onclick="applyNewsFilter('status','all')">All</button>
                            <button class="filter-pill <?= $filter_status==='published'?'active':'' ?>" onclick="applyNewsFilter('status','published')">Published</button>
                            <button class="filter-pill <?= $filter_status==='draft'?'active':'' ?>" onclick="applyNewsFilter('status','draft')">Drafts</button>
                        </div>
                        <div class="filter-group">
                            <?php foreach ($newsCats as $cat): ?>
                            <button class="filter-pill <?= $filter_cat===$cat?'active':'' ?>" onclick="applyNewsFilter('cat','<?= $cat ?>')"><?= $cat ?></button>
                            <?php endforeach; ?>
                            <button class="filter-pill <?= $filter_cat==='all'?'active':'' ?>" onclick="applyNewsFilter('cat','all')">All Cats</button>
                        </div>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($all_news)): ?>
                        <div class="empty-state">
                            <i class="fa-solid fa-newspaper"></i>
                            <p>No articles found. Create your first one above!</p>
                        </div>
                        <?php else: ?>
                        <div class="table-wrap">
                            <table id="newsTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Cover</th>
                                        <th>Title & Slug</th>
                                        <th>Category</th>
                                        <th>Author</th>
                                        <th>Status</th>
                                        <th>Views</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($all_news as $i => $row): ?>
                                <tr data-title="<?= htmlspecialchars(strtolower($row['title'])) ?>"
                                    data-author="<?= htmlspecialchars(strtolower($row['author'])) ?>"
                                    data-cat="<?= htmlspecialchars(strtolower($row['category'])) ?>">
                                    <td style="color:var(--text-muted);font-size:12px;font-family:monospace;"><?= str_pad($i+1,2,'0',STR_PAD_LEFT) ?></td>
                                    <td>
                                        <?php if ($row['image_path']): ?>
                                        <img src="../uploads/news/<?= htmlspecialchars($row['image_path']) ?>" alt="" class="news-thumb">
                                        <?php else: ?>
                                        <div class="no-thumb"><i class="fa-solid fa-image"></i></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="td-title">
                                            <?php if ($row['featured']): ?><i class="fa-solid fa-star" style="color:var(--warning);font-size:11px;margin-right:4px;"></i><?php endif; ?>
                                            <?= htmlspecialchars($row['title']) ?>
                                            <small>/<?= htmlspecialchars($row['slug']) ?></small>
                                        </div>
                                        <?php if ($row['excerpt']): ?>
                                        <div class="td-excerpt"><?= htmlspecialchars($row['excerpt']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge badge-cat"><i class="fa-solid fa-tag"></i> <?= htmlspecialchars($row['category']) ?></span></td>
                                    <td style="color:var(--text-secondary);font-size:13px;"><?= htmlspecialchars($row['author']) ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="news_id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="new_status" value="<?= $row['status']==='published' ? 'draft' : 'published' ?>">
                                            <button type="submit" name="toggle_status" class="badge <?= $row['status']==='published' ? 'badge-published' : 'badge-draft' ?>" style="cursor:pointer;border:none;">
                                                <span class="badge-dot" style="background:<?= $row['status']==='published' ? 'var(--success)' : 'var(--warning)' ?>;"></span>
                                                <?= ucfirst($row['status']) ?>
                                            </button>
                                        </form>
                                        <form method="POST" style="display:inline;margin-left:5px;">
                                            <input type="hidden" name="news_id" value="<?= $row['id'] ?>">
                                            <input type="hidden" name="new_featured" value="<?= $row['featured'] ? 0 : 1 ?>">
                                            <button type="submit" name="toggle_featured" title="<?= $row['featured'] ? 'Remove featured' : 'Mark featured' ?>"
                                                class="badge" style="cursor:pointer;border:none;background:<?= $row['featured'] ? 'var(--purple-dim)' : 'var(--bg-hover)' ?>;color:<?= $row['featured'] ? '#c084fc' : 'var(--text-muted)' ?>;">
                                                <i class="fa-solid fa-star"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td style="font-family:monospace;font-size:13px;"><?= number_format($row['views']) ?></td>
                                    <td style="color:var(--text-secondary);font-size:12px;font-family:monospace;white-space:nowrap;"><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                            <a href="?tab=news&edit=<?= $row['id'] ?>#newsFormCard" class="btn btn-ghost btn-sm">
                                                <i class="fa-solid fa-pen"></i> Edit
                                            </a>
                                            <button class="btn btn-danger btn-sm" onclick="openNewsDeleteModal(<?= $row['id'] ?>, '<?= addslashes(htmlspecialchars($row['title'])) ?>')">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div><!-- /tab-news -->

        </div><!-- /content -->
    </main>
</div>

<script>
/* ── SIDEBAR ─────────────────────────── */
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('active');
}
function toggleDropdown(btn) {
    const d = btn.parentElement;
    const wasOpen = d.classList.contains('open');
    document.querySelectorAll('.nav-dropdown.open').forEach(x => { if (x !== d) x.classList.remove('open'); });
    d.classList.toggle('open', !wasOpen);
}
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.nav-dropdown').forEach(d => {
        if (d.querySelector('.dropdown-item.active')) d.classList.add('open');
    });
});

/* ── TAB SWITCHER ────────────────────── */
function switchTab(tab) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    document.querySelector('.tab-btn[onclick="switchTab(\'' + tab + '\')"]').classList.add('active');
    const url = new URL(window.location.href);
    url.searchParams.set('tab', tab);
    history.replaceState(null, '', url.toString());
}

/* ── MODAL HELPERS ───────────────────── */
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
document.querySelectorAll('.modal-bg').forEach(m => m.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('show'); }));

/* ── VLOG ACTIONS ────────────────────── */
function selectVlogCat(btn) {
    document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('selected'));
    btn.classList.add('selected');
    document.getElementById('vlogCategoryInput').value = btn.dataset.value;
}
function onVideoChange(input) {
    const d = document.getElementById('videoFileName');
    if (input.files && input.files[0]) {
        d.textContent = '📎 ' + input.files[0].name;
        d.style.display = 'block';
        document.getElementById('vlogFileZone').style.borderColor = 'var(--accent)';
    }
}
function resetVlogForm() {
    document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('selected'));
    document.querySelector('.cat-pill.tour').classList.add('selected');
    document.getElementById('vlogCategoryInput').value = 'tour';
    document.getElementById('videoFileName').style.display = 'none';
    document.getElementById('vlogFileZone').style.borderColor = '';
}
const vlogZone = document.getElementById('vlogFileZone');
if (vlogZone) {
    vlogZone.addEventListener('dragover', e => { e.preventDefault(); vlogZone.classList.add('drag'); });
    vlogZone.addEventListener('dragleave', () => vlogZone.classList.remove('drag'));
    vlogZone.addEventListener('drop', e => { e.preventDefault(); vlogZone.classList.remove('drag'); });
}
function openVlogDeleteModal(id, title) {
    document.getElementById('vlogConfirmDeleteBtn').href = '?tab=vlogs&delete_vlog=' + id;
    document.querySelector('#vlogDeleteModal h3').textContent = 'Delete "' + title + '"?';
    document.getElementById('vlogDeleteModal').classList.add('show');
}
let vlogActiveFilter = 'all';
function setVlogFilter(btn) {
    document.querySelectorAll('[onclick^="setVlogFilter"]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    vlogActiveFilter = btn.dataset.cat;
    filterVlogs();
}
function filterVlogs() {
    const q = document.getElementById('vlogSearch').value.toLowerCase();
    const rows = document.querySelectorAll('#vlogTable tbody tr');
    let visible = 0;
    rows.forEach(row => {
        const matchCat    = vlogActiveFilter === 'all' || row.dataset.cat === vlogActiveFilter;
        const matchSearch = !q || row.dataset.title.includes(q) || row.dataset.desc.includes(q);
        row.style.display = matchCat && matchSearch ? '' : 'none';
        if (matchCat && matchSearch) visible++;
    });
    document.getElementById('vlogVisibleCount').textContent = visible + ' shown';
}

/* ── NEWS ACTIONS ────────────────────── */
function previewImg(input) {
    const name    = document.getElementById('imgName');
    const preview = document.getElementById('imgPreview');
    const zone    = document.getElementById('newsFileZone');
    if (input.files && input.files[0]) {
        name.textContent = '📎 ' + input.files[0].name;
        name.style.display = 'block';
        zone.style.borderColor = 'var(--accent)';
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    }
}
const newsZone = document.getElementById('newsFileZone');
if (newsZone) {
    newsZone.addEventListener('dragover', e => { e.preventDefault(); newsZone.classList.add('drag'); });
    newsZone.addEventListener('dragleave', () => newsZone.classList.remove('drag'));
    newsZone.addEventListener('drop', e => { e.preventDefault(); newsZone.classList.remove('drag'); });
}
function resetNewsForm() {
    document.getElementById('imgName').style.display = 'none';
    const p = document.getElementById('imgPreview');
    if (p) p.style.display = 'none';
    if (newsZone) newsZone.style.borderColor = '';
}
function openNewsDeleteModal(id, title) {
    document.getElementById('newsConfirmDeleteBtn').href = '?tab=news&delete_news=' + id;
    document.getElementById('newsDeleteText').textContent = 'You are about to permanently delete "' + title + '". This cannot be undone.';
    document.getElementById('newsDeleteModal').classList.add('show');
}
function filterNews() {
    const q = document.getElementById('newsSearch').value.toLowerCase();
    const rows = document.querySelectorAll('#newsTable tbody tr');
    let visible = 0;
    rows.forEach(row => {
        const match = !q || row.dataset.title.includes(q) || row.dataset.author.includes(q) || row.dataset.cat.includes(q);
        row.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    document.getElementById('newsTableCount').textContent = visible + ' shown';
}
function applyNewsFilter(key, val) {
    const url = new URL(window.location.href);
    url.searchParams.set('tab', 'news');
    url.searchParams.set(key, val);
    window.location.href = url.toString();
}

/* ── AUTO-DISMISS FLASH ──────────────── */
setTimeout(() => {
    const f = document.getElementById('flashBar');
    if (f) { f.style.transition = 'opacity .5s'; f.style.opacity = '0'; setTimeout(() => f.remove(), 500); }
}, 5000);

/* ── SCROLL TO EDIT FORM ─────────────── */
<?php if ($edit_row): ?>
document.getElementById('newsFormCard').scrollIntoView({ behavior: 'smooth', block: 'start' });
<?php endif; ?>
</script>
</body>
</html>