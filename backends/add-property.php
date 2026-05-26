<?php
session_start();

header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://www.gstatic.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self';");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: no-referrer-when-downgrade");

require_once __DIR__ . '/../backends/config.php';

$conn = get_db_connection();

// Auth check
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

// Helper functions
function isActive($filename) {
    return basename($_SERVER['PHP_SELF']) === $filename ? 'active' : '';
}

function isDropdownActive($filenames) {
    foreach ($filenames as $filename) {
        if (basename($_SERVER['PHP_SELF']) === $filename) {
            return 'open';
        }
    }
    return '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Add Property — ITPH Admin</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

:root {
    --bg: #0f172a;
    --bg-card: #1e293b;
    --bg-hover: #334155;
    --border: #334155;
    --text-primary: #f1f5f9;
    --text-secondary: #94a3b8;
    --text-muted: #64748b;
    --accent: #3b82f6;
    --accent-light: rgba(59, 130, 246, 0.15);
    --success: #22c55e;
    --danger: #ef4444;
    --radius: 12px;
    --radius-sm: 8px;
    --shadow: 0 4px 6px -1px rgba(0,0,0,.3);
    --shadow-lg: 0 10px 15px -3px rgba(0,0,0,.4);
}

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:'Inter',sans-serif;
    background:var(--bg);
    color:var(--text-primary);
    line-height:1.6;
}

/* LAYOUT */

.dashboard{
    display:flex;
    min-height:100vh;
}

/* SIDEBAR */

.sidebar{
    width:260px;
    background:var(--bg-card);
    border-right:1px solid var(--border);
    position:fixed;
    top:0;
    left:0;
    height:100vh;
    overflow-y:auto;
    z-index:100;
    transition:transform .3s ease;
}

.sidebar-header{
    padding:24px 20px;
    border-bottom:1px solid var(--border);
}

.logo{
    display:flex;
    align-items:center;
    gap:12px;
    text-decoration:none;
    color:inherit;
}

.logo-icon{
    width:40px;
    height:40px;
    border-radius:var(--radius-sm);
    background:linear-gradient(135deg,var(--accent),#60a5fa);
    display:flex;
    align-items:center;
    justify-content:center;
    color:#fff;
}

.logo-text{
    font-size:18px;
    font-weight:700;
}

.logo-text span{
    color:var(--accent);
}

.logo-sub{
    font-size:11px;
    color:var(--text-muted);
}

.sidebar-nav{
    padding:16px 12px;
}

.nav-section{
    padding:16px;
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.5px;
    color:var(--text-muted);
    font-weight:600;
}

.nav-link{
    display:flex;
    align-items:center;
    gap:12px;
    padding:12px 16px;
    margin-bottom:4px;
    border-radius:var(--radius-sm);
    text-decoration:none;
    color:var(--text-secondary);
    transition:.2s;
    font-size:14px;
    font-weight:500;
}

.nav-link:hover{
    background:var(--accent-light);
    color:var(--text-primary);
}

.nav-link.active{
    background:var(--accent-light);
    color:var(--accent);
}

.nav-link.logout{
    color:var(--danger);
}

.nav-link.logout:hover{
    background:rgba(239,68,68,.1);
}

.nav-link i{
    width:20px;
    text-align:center;
}

.main-content{
    flex:1;
    margin-left:260px;
    min-height:100vh;
}

/* TOPBAR */

.topbar{
    height:64px;
    background:var(--bg-card);
    border-bottom:1px solid var(--border);
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 24px;
    position:sticky;
    top:0;
    z-index:50;
}

.topbar-left{
    display:flex;
    align-items:center;
    gap:16px;
}

.page-title{
    font-size:20px;
    font-weight:600;
}

.breadcrumb{
    font-size:13px;
    color:var(--text-muted);
}

.topbar-right{
    display:flex;
    align-items:center;
    gap:16px;
}

.user-menu{
    display:flex;
    align-items:center;
    gap:12px;
}

.user-info{
    text-align:right;
}

.user-name{
    font-size:14px;
    font-weight:600;
}

.user-role{
    font-size:12px;
    color:var(--text-muted);
}

.user-avatar{
    width:36px;
    height:36px;
    border-radius:50%;
    background:linear-gradient(135deg,var(--accent),#60a5fa);
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:600;
}

/* CONTENT */

.content{
    padding:24px;
    max-width:1200px;
}

/* HERO */

.page-hero{
    background:var(--bg-card);
    border:1px solid var(--border);
    border-radius:var(--radius);
    padding:28px 24px;
    margin-bottom:24px;
    position:relative;
    overflow:hidden;
}

.page-hero::before{
    content:'';
    position:absolute;
    top:0;
    left:0;
    right:0;
    height:3px;
    background:linear-gradient(90deg,var(--accent),#60a5fa);
}

.hero-eyebrow{
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:2px;
    color:var(--accent);
    margin-bottom:8px;
    font-weight:600;
}

.hero-title{
    font-size:24px;
    font-weight:700;
    margin-bottom:6px;
}

.hero-desc{
    font-size:14px;
    color:var(--text-secondary);
}

/* CARD */

.card{
    background:var(--bg-card);
    border:1px solid var(--border);
    border-radius:var(--radius);
    overflow:hidden;
}

.card-header{
    padding:20px 24px;
    border-bottom:1px solid var(--border);
}

.card-title{
    font-size:18px;
    font-weight:600;
}

.card-body{
    padding:24px;
}

/* FORM */

.form-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:20px;
}

.form-group{
    margin-bottom:20px;
}

.form-group.full{
    grid-column:1/-1;
}

label{
    display:block;
    margin-bottom:8px;
    font-size:13px;
    font-weight:600;
    color:var(--text-secondary);
}

input,
select,
textarea{
    width:100%;
    padding:12px 14px;
    background:var(--bg);
    border:1px solid var(--border);
    border-radius:var(--radius-sm);
    color:var(--text-primary);
    font-size:14px;
    outline:none;
    transition:.2s;
    font-family:inherit;
}

input:focus,
select:focus,
textarea:focus{
    border-color:var(--accent);
    box-shadow:0 0 0 3px var(--accent-light);
}

textarea{
    resize:vertical;
    min-height:120px;
}

input[type="file"]{
    padding:10px;
}

/* BUTTON */

.btn{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:12px 20px;
    border:none;
    border-radius:var(--radius-sm);
    cursor:pointer;
    font-size:14px;
    font-weight:600;
    transition:.2s;
}

.btn-success{
    background:var(--success);
    color:#fff;
}

.btn-success:hover{
    background:#16a34a;
    transform:translateY(-1px);
    box-shadow:0 4px 12px rgba(34,197,94,.3);
}

/* RESPONSIVE */

@media(max-width:768px){

    .sidebar{
        transform:translateX(-100%);
    }

    .sidebar.open{
        transform:translateX(0);
    }

    .main-content{
        margin-left:0;
    }

    .content{
        padding:16px;
    }

    .form-grid{
        grid-template-columns:1fr;
    }

    .user-info{
        display:none;
    }
}

</style>
</head>

<body>

<div class="dashboard">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">

        <div class="sidebar-header">
            <a href="../admin_side/index.php" class="logo">
                <div class="logo-icon">
                    <i class="fa-solid fa-building"></i>
                </div>

                <div>
                    <div class="logo-text">
                        ITPH <span>Admin</span>
                    </div>
                    <div class="logo-sub">
                        Real Estate Management
                    </div>
                </div>
            </a>
        </div>

        <nav class="sidebar-nav">

            <div class="nav-section">Main</div>

            <a href="../admin_side/index.php" class="nav-link <?= isActive('index.php') ?>">
                <i class="fa-solid fa-chart-line"></i>
                Dashboard
            </a>

            <a href="../admin_side/ad_account.php" class="nav-link <?= isActive('ad_account.php') ?>">
                <i class="fa-solid fa-user-cog"></i>
                My Account
            </a>

            <div class="nav-section">Management</div>

            <a href="../admin_side/customer_ban.php" class="nav-link <?= isActive('customer_ban.php') ?>">
                <i class="fa-solid fa-users"></i>
                Customers
            </a>

            <a href="../admin_side/update_properties.php" class="nav-link <?= isActive('update_properties.php') ?>">
                <i class="fa-solid fa-house"></i>
                Properties
            </a>

            <a href="../admin_side/admin_blog_management.php" class="nav-link <?= isActive('admin_blog_management.php') ?>">
                <i class="fa-solid fa-newspaper"></i>
                Blogs
            </a>

            <a href="../admin_side/transaction.php" class="nav-link <?= isActive('transaction.php') ?>">
                <i class="fa-solid fa-money-bill-transfer"></i>
                Transactions
            </a>

            <a href="../admin_side/manage_agent.php" class="nav-link <?= isActive('manage_agent.php') ?>">
                <i class="fa-solid fa-user-tie"></i>
                Manage Agents
            </a>

            <div class="nav-section">System</div>

            <a href="../admin_side/audit_log.php" class="nav-link <?= isActive('audit_log.php') ?>">
                <i class="fa-solid fa-shield-halved"></i>
                Audit Log
            </a>

            <a href="logout.php" class="nav-link logout">
                <i class="fa-solid fa-right-from-bracket"></i>
                Logout
            </a>

        </nav>

    </aside>

    <!-- MAIN -->
    <main class="main-content">

        <!-- TOPBAR -->
        <header class="topbar">

            <div class="topbar-left">
                <div>
                    <div class="page-title">
                        Add Property
                    </div>

                    <div class="breadcrumb">
                        Home / Properties / Add Property
                    </div>
                </div>
            </div>

            <div class="topbar-right">

                <div class="user-menu">

                    <div class="user-info">
                        <div class="user-name">
                            <?= htmlspecialchars($_SESSION['username']) ?>
                        </div>

                        <div class="user-role">
                            Administrator
                        </div>
                    </div>

                    <div class="user-avatar">
                        <?= strtoupper(substr($_SESSION['username'],0,1)) ?>
                    </div>

                </div>

            </div>

        </header>

        <!-- CONTENT -->
        <div class="content">

            <!-- HERO -->
            <div class="page-hero">

                <div class="hero-eyebrow">
                    Property Management
                </div>

                <div class="hero-title">
                    Add New Property
                </div>

                <div class="hero-desc">
                    Create and publish a new property listing for the platform.
                </div>

            </div>

            <!-- FORM CARD -->
            <div class="card">

                <div class="card-header">
                    <div class="card-title">
                        Property Information
                    </div>
                </div>

                <div class="card-body">

                    <form action="../backends/admin_upload.php" method="POST" enctype="multipart/form-data">

                        <div class="form-grid">

                            <div class="form-group full">
                                <label>Property Title</label>
                                <input type="text" name="title" required>
                            </div>

                            <div class="form-group">
                                <label>Property Page</label>

                                <select name="property_page" required>
                                    <option value="">Select Property Page</option>
                                    <option value="monticello">Monticello</option>
                                    <option value="amani">Amani House</option>
                                    <option value="phrst">PHIRST</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Available Units</label>
                                <input type="number" name="available_units" min="0" required>
                            </div>

                            <div class="form-group">
                                <label>Price</label>
                                <input type="number" step="0.01" name="price" required>
                            </div>

                            <div class="form-group">
                                <label>Location</label>
                                <input type="text" name="location" required>
                            </div>

                            <div class="form-group">
                                <label>Bedrooms</label>
                                <input type="number" name="bedrooms" required>
                            </div>

                            <div class="form-group">
                                <label>Bathrooms</label>
                                <input type="number" name="bathrooms" required>
                            </div>

                            <div class="form-group full">
                                <label>Property Images</label>
                                <input type="file" name="images[]" multiple required>
                            </div>

                            <div class="form-group full">
                                <label>Description</label>
                                <textarea name="description" required></textarea>
                            </div>

                            <div class="form-group full">
                                <button type="submit" class="btn btn-success">
                                    <i class="fa-solid fa-floppy-disk"></i>
                                    Add Property
                                </button>
                            </div>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </main>

</div>

</body>
</html>