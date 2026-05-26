<?php
session_start();
require_once __DIR__ . '/../backends/config.php';
require_once __DIR__ . '/../backends/send_email.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://www.gstatic.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self';");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: no-referrer-when-downgrade");

$conn = get_db_connection();
$type = $_GET['type'] ?? 'recent';

// Auto-logout inactive sessions
$conn->query("UPDATE auth_logs SET session_status='offline' WHERE session_status='online' AND activity_time < NOW() - INTERVAL 15 MINUTE");

// Fetch reservations
if ($type === 'recent') {
    $res_query = $conn->query("SELECT * FROM reservations WHERE status != 'Done' ORDER BY id DESC LIMIT 10");
} else {
    $res_query = $conn->query("SELECT * FROM reservations WHERE status='Done' ORDER BY id DESC LIMIT 10");
}

// Payment handlers
if (isset($_POST['payment_cash'])) {
    $id = (int)$_POST['reservation_id'];
    $stmt = $conn->prepare("SELECT fullname, email, property FROM reservations WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($client_name, $client_email, $property);
    $stmt->fetch();
    $stmt->close();

    $update = $conn->prepare("UPDATE reservations SET status='Done' WHERE id=?");
    $update->bind_param("i", $id);
    $update->execute();
    $update->close();

    $subject = "Your Property is Ready for Cash Payment!";
    $body = "Hi {$client_name},<br><br>Your property <strong>{$property}</strong> is ready for cash payment! Please go to the office for paper signing of property rights and payment.<br><br>— Admin Team";
    send_gmail_notification($client_email, $client_name, $subject, $body);

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if (isset($_POST['payment_installment'])) {
    $id = (int)$_POST['reservation_id'];
    $stmt = $conn->prepare("SELECT fullname, email, property, payment_type FROM reservations WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($client_name, $client_email, $property, $payment_type);
    $stmt->fetch();
    $stmt->close();

    $update = $conn->prepare("UPDATE reservations SET status='Done' WHERE id=?");
    $update->bind_param("i", $id);
    $update->execute();
    $update->close();

    $subject = "Congratulations! Your reservation is Done";
    $body = "Hi {$client_name},<br><br>Your reservation for <strong>{$property}</strong> is now completed. Thank you for your payment!<br><br>— Admin Team";
    send_gmail_notification($client_email, $client_name, $subject, $body);

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if (isset($_POST['send_notification'])) {

    $id = (int)$_POST['reservation_id'];
    $payment_method = $_POST['payment_method'];

    // Current logged in admin/agent
    $agent_id = (int)$_SESSION['id'];

    // Get reservation details
    $stmt = $conn->prepare("
        SELECT fullname, email, property 
        FROM reservations 
        WHERE id=?
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($user_name, $user_email, $property_name);
    $stmt->fetch();
    $stmt->close();

    // Update reservation
    $update = $conn->prepare("
        UPDATE reservations 
        SET 
            notification_sent = 1,
            payment_type = ?,
            status = 'Done',
            agent_id = ?
        WHERE id = ?
    ");

    $update->bind_param("sii", $payment_method, $agent_id, $id);
    $update->execute();
    $update->close();

    // Email subject
    $subject = "Property Payment Confirmation";

    // Email message
    if ($payment_method === "Cash") {

        $body = "
        Hi {$user_name},<br><br>

        Congratulations! Your property 
        <strong>{$property_name}</strong> 
        is approved for <strong>Cash Payment</strong>.<br><br>

        Please visit the office for full payment,
        contract signing, certificates, and key claiming.<br><br>

        Thank you for choosing ITPH Property!<br><br>

        — Admin Team
        ";

    } else {

        $body = "
        Hi {$user_name},<br><br>

        Congratulations! Your property 
        <strong>{$property_name}</strong> 
        is approved for <strong>Installment Payment</strong>.<br><br>

        Please visit the office for installment agreement,
        contract signing, certificates, and payment scheduling.<br><br>

        Thank you for choosing ITPH Property!<br><br>

        — Admin Team
        ";
    }

    // Send email
    send_gmail_notification(
        $user_email,
        $user_name,
        $subject,
        $body
    );

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Auth check
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

// Admin info
$stmt = $conn->prepare("SELECT username, gmail FROM admin_users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$stmt->bind_result($username, $admin_email);
if ($stmt->fetch()) {
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $admin_email;
}
$stmt->close();

/* ================= CONTACT MESSAGES ================= */
$contact_query = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 10");
$contact_messages = [];
while ($row = $contact_query->fetch_assoc()) {
    $contact_messages[] = $row;
}

/* ================= COUNTS ================= */
$total_properties = $conn->query("SELECT COUNT(*) AS total FROM propertiies")->fetch_assoc()['total'] ?? 0;
$total_users = $conn->query("SELECT COUNT(*) AS total FROM user_login")->fetch_assoc()['total'] ?? 0;
$total_reservations = $conn->query("SELECT COUNT(*) AS total FROM reservations")->fetch_assoc()['total'] ?? 0;
$total_blogs = $conn->query("SELECT COUNT(*) AS total FROM vlogs")->fetch_assoc()['total'] ?? 0;

$total_online_customers = $conn->query("SELECT COUNT(DISTINCT user_id) AS total FROM auth_logs WHERE role='customer' AND session_status='online'")->fetch_assoc()['total'] ?? 0;
$total_online_agents = $conn->query("SELECT COUNT(DISTINCT user_id) AS total FROM auth_logs WHERE role='agent' AND session_status='online'")->fetch_assoc()['total'] ?? 0;
$total_online_admins = $conn->query("SELECT COUNT(DISTINCT user_id) AS total FROM auth_logs WHERE role='admin' AND session_status='online'")->fetch_assoc()['total'] ?? 0;

/* ================= CHART DATA ================= */
$view_query = $conn->query("SELECT title, views FROM propertiies WHERE views > 0 ORDER BY views DESC LIMIT 10");
$pie_data = [];
while ($row = $view_query->fetch_assoc()) {
    $pie_data[] = [$row['title'], (int)$row['views']];
}

$gender_query = $conn->query("SELECT gender, COUNT(*) AS total FROM reservations GROUP BY gender ORDER BY total DESC");
$gender_data = [];
while ($row = $gender_query->fetch_assoc()) {
    $gender_data[] = [$row['gender'], (int)$row['total']];
}

$location_query = $conn->query("SELECT location, COUNT(*) AS total FROM reservations GROUP BY location ORDER BY total DESC");
$location_data = [];
while ($row = $location_query->fetch_assoc()) {
    $location_data[] = [$row['location'], (int)$row['total']];
}

$monthly_data = [];
$result = $conn->query("SELECT MONTH(created_at) AS month_num, MONTHNAME(created_at) AS month, COUNT(*) AS total FROM reservations GROUP BY MONTH(created_at) ORDER BY MONTH(created_at)");
while ($row = $result->fetch_assoc()) {
    $monthly_data[] = [
        'month_num' => (int)$row['month_num'],
        'month' => $row['month'],
        'total' => (int)$row['total']
    ];
}

$agent_query = $conn->query("
    SELECT a.username, (COUNT(r.id) + COUNT(t.id)) AS total_done 
    FROM agents a 
    LEFT JOIN reservations r ON r.agent_id = a.id AND r.status='Done' 
    LEFT JOIN transaction_logs t ON t.id = a.id AND t.mode='offline' 
    GROUP BY a.id ORDER BY total_done DESC
");
$agent_labels = [];
$agent_values = [];
while ($row = $agent_query->fetch_assoc()) {
    $agent_labels[] = $row['username'];
    $agent_values[] = (int)$row['total_done'];
}

if (isset($_POST['confirm_booking'])) {
    $id = (int)$_POST['reservation_id'];
    $stmt = $conn->prepare("UPDATE reservations SET status='Confirmed', notification_sent=0 WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if (isset($_POST['done_deal'])) {
    $reservation_id = (int)$_POST['reservation_id'];
    $agent_id = (int)$_SESSION['id'];

    $stmt = $conn->prepare("SELECT fullname, email, property FROM reservations WHERE id=?");
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $stmt->bind_result($client_name, $client_email, $property_name);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE reservations SET status='Done', agent_id=? WHERE id=?");
    $stmt->bind_param("ii", $agent_id, $reservation_id);
    $stmt->execute();
    $stmt->close();

    $subject = "Congratulations! Your reservation is Done";
    $body = "Hi $client_name,<br><br>Your reservation for <strong>$property_name</strong> is now completed. Thank you for your payment!<br><br>— Admin Team";
    send_gmail_notification($client_email, $client_name, $subject, $body);

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

ob_end_flush();
// ================= PDF REPORT DOWNLOAD =================
if (isset($_GET['download_report'])) {

    $options = new Options();
    $options->set('isRemoteEnabled', true);

    $dompdf = new Dompdf($options);

    ob_start();
    ?>

    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>ITPH Analytics Report</title>

        <style>
            body{
                font-family: Arial, sans-serif;
                color:#222;
                padding:20px;
                font-size:13px;
            }

            h1{
                text-align:center;
                color:#2563eb;
                margin-bottom:5px;
            }

            .generated{
                text-align:center;
                margin-bottom:25px;
                color:#666;
            }

            .section{
                margin-top:35px;
            }

            .section h2{
                background:#2563eb;
                color:white;
                padding:10px;
                font-size:18px;
            }

            .stats{
                margin-top:15px;
            }

            .stats div{
                margin-bottom:8px;
                padding:8px;
                background:#f3f4f6;
                border-left:4px solid #2563eb;
            }

            table{
                width:100%;
                border-collapse:collapse;
                margin-top:15px;
            }

            table, th, td{
                border:1px solid #ccc;
            }

            th{
                background:#2563eb;
                color:white;
                padding:10px;
                text-align:left;
            }

            td{
                padding:8px;
            }

            tr:nth-child(even){
                background:#f9fafb;
            }
        </style>
    </head>

    <body>

        <h1>ITPH ANALYTICS REPORT</h1>

        <div class="generated">
            Generated on <?= date("F d, Y h:i A") ?>
        </div>

        <!-- SYSTEM STATS -->
        <div class="section">
            <h2>System Statistics</h2>

            <div class="stats">
                <div><strong>Total Properties:</strong> <?= $total_properties ?></div>
                <div><strong>Total Users:</strong> <?= $total_users ?></div>
                <div><strong>Total Reservations:</strong> <?= $total_reservations ?></div>
                <div><strong>Total Blogs:</strong> <?= $total_blogs ?></div>
            </div>
        </div>

        <!-- ONLINE USERS -->
        <div class="section">
            <h2>Online Users</h2>

            <div class="stats">
                <div><strong>Customers Online:</strong> <?= $total_online_customers ?></div>
                <div><strong>Agents Online:</strong> <?= $total_online_agents ?></div>
                <div><strong>Admins Online:</strong> <?= $total_online_admins ?></div>
            </div>
        </div>

        <!-- MONTHLY RESERVATIONS -->
        <div class="section">
            <h2>Monthly Reservations</h2>

            <table>
                <tr>
                    <th>Month</th>
                    <th>Total Reservations</th>
                </tr>

                <?php foreach ($monthly_data as $md): ?>
                    <tr>
                        <td><?= htmlspecialchars($md['month']) ?></td>
                        <td><?= $md['total'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- PROPERTY VIEWS -->
        <div class="section">
            <h2>Property Views</h2>

            <table>
                <tr>
                    <th>Property</th>
                    <th>Views</th>
                </tr>

                <?php foreach ($pie_data as $pd): ?>
                    <tr>
                        <td><?= htmlspecialchars($pd[0]) ?></td>
                        <td><?= $pd[1] ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- GENDER -->
        <div class="section">
            <h2>Gender Analytics</h2>

            <table>
                <tr>
                    <th>Gender</th>
                    <th>Total</th>
                </tr>

                <?php foreach ($gender_data as $gd): ?>
                    <tr>
                        <td><?= htmlspecialchars($gd[0]) ?></td>
                        <td><?= $gd[1] ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- LOCATION -->
        <div class="section">
            <h2>Location Analytics</h2>

            <table>
                <tr>
                    <th>Location</th>
                    <th>Total Reservations</th>
                </tr>

                <?php foreach ($location_data as $ld): ?>
                    <tr>
                        <td><?= htmlspecialchars($ld[0]) ?></td>
                        <td><?= $ld[1] ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- AGENTS -->
        <div class="section">
            <h2>Agent Performance</h2>

            <table>
                <tr>
                    <th>Agent</th>
                    <th>Done Deals</th>
                </tr>

                <?php for ($i = 0; $i < count($agent_labels); $i++): ?>
                    <tr>
                        <td><?= htmlspecialchars($agent_labels[$i]) ?></td>
                        <td><?= $agent_values[$i] ?></td>
                    </tr>
                <?php endfor; ?>
            </table>
        </div>

        <!-- RESERVATIONS -->
        <div class="section">
            <h2>Reservations</h2>

            <table>
                <tr>
                    <th>Client</th>
                    <th>Email</th>
                    <th>Property</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>

                <?php
                $reservation_export = $conn->query("
                    SELECT fullname,email,property,status,created_at
                    FROM reservations
                    ORDER BY id DESC
                ");

                while($r = $reservation_export->fetch_assoc()):
                ?>

                <tr>
                    <td><?= htmlspecialchars($r['fullname']) ?></td>
                    <td><?= htmlspecialchars($r['email']) ?></td>
                    <td><?= htmlspecialchars($r['property']) ?></td>
                    <td><?= htmlspecialchars($r['status']) ?></td>
                    <td><?= htmlspecialchars($r['created_at']) ?></td>
                </tr>

                <?php endwhile; ?>
            </table>
        </div>

        <!-- CONTACT MESSAGES -->
        <div class="section">
            <h2>Contact Messages</h2>

            <table>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Message</th>
                    <th>Date</th>
                </tr>

                <?php foreach ($contact_messages as $msg): ?>
                    <tr>
                        <td><?= htmlspecialchars($msg['name']) ?></td>
                        <td><?= htmlspecialchars($msg['email']) ?></td>
                        <td><?= htmlspecialchars($msg['phone']) ?></td>
                        <td><?= htmlspecialchars($msg['message']) ?></td>
                        <td><?= htmlspecialchars($msg['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

    </body>
    </html>

    <?php

    $html = ob_get_clean();

    $dompdf->loadHtml($html);

    $dompdf->setPaper('A4', 'portrait');

    $dompdf->render();

    $dompdf->stream(
        "ITPH_Analytics_Report.pdf",
        ["Attachment" => true]
    );

    exit;
}

// Helper functions for sidebar
function isActive($filename) {
    return basename($_SERVER['PHP_SELF']) === $filename ? 'active' : '';
}
function isDropdownActive($filenames) {
    foreach ($filenames as $filename) {
        if (basename($_SERVER['PHP_SELF']) === $filename) return 'open';
    }
    return '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — ITPH</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            --warning: #f59e0b;
            --danger: #ef4444;
            --radius: 12px;
            --radius-sm: 8px;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -2px rgba(0, 0, 0, 0.2);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.4);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text-primary);
            line-height: 1.6;
        }

        /* ===== LAYOUT ===== */
        .dashboard { display: flex; min-height: 100vh; }

        /* ===== SIDEBAR ===== */
        .sidebar {
            width: 260px;
            background: var(--bg-card);
            border-right: 1px solid var(--border);
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 100;
            overflow-y: auto;
            overflow-x: hidden;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            padding: 24px 20px;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            background: var(--bg-card);
            z-index: 10;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: inherit;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--accent), #60a5fa);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
            flex-shrink: 0;
        }

        .logo-text {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.2;
        }

        .logo-text span { color: var(--accent); }

        .logo-sub {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .sidebar-nav { padding: 16px 12px; }

        .nav-section {
            padding: 16px 16px 8px;
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            margin-bottom: 4px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s;
            border: none;
            background: none;
            width: 100%;
            cursor: pointer;
            font-family: inherit;
            text-align: left;
        }

        .nav-link:hover {
            background: var(--accent-light);
            color: var(--text-primary);
        }

        .nav-link.active {
            background: var(--accent-light);
            color: var(--accent);
        }

        .nav-link.logout { color: var(--danger); }

        .nav-link.logout:hover {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .nav-link i {
            width: 20px;
            text-align: center;
            font-size: 16px;
            flex-shrink: 0;
        }

        .nav-link i.dropdown-arrow {
            margin-left: auto;
            font-size: 12px;
            transition: transform 0.2s;
            width: auto;
        }

        .nav-dropdown { margin-bottom: 4px; }

        .nav-dropdown.open > .nav-link .dropdown-arrow { transform: rotate(180deg); }

        .nav-dropdown.open > .dropdown-menu {
            max-height: 200px;
            opacity: 1;
        }

        .dropdown-menu {
            max-height: 0;
            opacity: 0;
            overflow: hidden;
            transition: all 0.3s ease;
            padding-left: 48px;
        }

        .dropdown-item {
            display: block;
            padding: 10px 16px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            border-radius: 6px;
            transition: all 0.2s;
            margin-bottom: 2px;
        }

        .dropdown-item:hover {
            color: var(--text-primary);
            background: rgba(59, 130, 246, 0.05);
        }

        .dropdown-item.active {
            color: var(--accent);
            background: rgba(59, 130, 246, 0.1);
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 99;
            backdrop-filter: blur(4px);
        }

        .sidebar-overlay.active { display: block; }

        .sidebar::-webkit-scrollbar { width: 6px; }
        .sidebar::-webkit-scrollbar-track { background: transparent; }
        .sidebar::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            flex: 1;
            margin-left: 260px;
            min-height: 100vh;
        }

        /* ===== TOPBAR ===== */
        .topbar {
            height: 64px;
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-left { display: flex; align-items: center; gap: 16px; }

        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 20px;
            cursor: pointer;
            padding: 8px;
            border-radius: var(--radius-sm);
        }

        .menu-toggle:hover { background: var(--bg-hover); }

        .page-title { font-size: 20px; font-weight: 600; }

        .breadcrumb {
            font-size: 13px;
            color: var(--text-muted);
        }

        .topbar-right { display: flex; align-items: center; gap: 16px; }

        .notification-btn {
            position: relative;
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 18px;
            cursor: pointer;
            padding: 8px;
            border-radius: var(--radius-sm);
            transition: all 0.2s;
        }

        .notification-btn:hover {
            background: var(--bg-hover);
            color: var(--text-primary);
        }

        .notification-badge {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 8px;
            height: 8px;
            background: var(--danger);
            border-radius: 50%;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 0.2s;
        }

        .user-menu:hover { background: var(--bg-hover); }

        .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--accent), #60a5fa);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            color: white;
        }

        .user-info { text-align: right; }

        .user-name { font-size: 14px; font-weight: 600; }

        .user-role { font-size: 12px; color: var(--text-muted); }

        /* ===== CONTENT ===== */
        .content { padding: 24px; max-width: 1400px; }

        /* ===== STATS ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 24px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s;
        }

        .stat-card:hover {
            border-color: var(--accent);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .stat-icon.blue { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
        .stat-icon.green { background: rgba(34, 197, 94, 0.15); color: #4ade80; }
        .stat-icon.amber { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
        .stat-icon.purple { background: rgba(168, 85, 247, 0.15); color: #c084fc; }

        .stat-trend {
            font-size: 12px;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 20px;
        }

        .stat-trend.up { background: rgba(34, 197, 94, 0.15); color: #4ade80; }

        .stat-value { font-size: 32px; font-weight: 700; margin-bottom: 4px; }

        .stat-label { font-size: 14px; color: var(--text-secondary); }

        /* ===== ONLINE USERS ===== */
        .online-bar {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .online-pill {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 100px;
            font-size: 13px;
            font-weight: 500;
        }

        .online-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        .online-dot.green { background: var(--success); }
        .online-dot.blue { background: var(--accent); }
        .online-dot.amber { background: var(--warning); }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* ===== CARDS ===== */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            margin-bottom: 24px;
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title { font-size: 18px; font-weight: 600; }

        .card-subtitle {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 4px;
        }

        .card-body { padding: 24px; }

        /* ===== CHARTS ===== */
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        .chart-container { position: relative; height: 350px; }

        /* ===== ANALYTICS SELECTOR ===== */
        .selector-group { display: flex; gap: 8px; flex-wrap: wrap; }

        .selector-btn {
            padding: 8px 16px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text-secondary);
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .selector-btn:hover {
            border-color: var(--accent);
            color: var(--text-primary);
        }

        .selector-btn.active {
            background: var(--accent);
            border-color: var(--accent);
            color: white;
        }

        /* ===== TABLES ===== */
        .table-responsive { overflow-x: auto; }

        table { width: 100%; border-collapse: collapse; }

        th {
            text-align: left;
            padding: 12px 16px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        td {
            padding: 14px 16px;
            font-size: 14px;
            border-bottom: 1px solid var(--border);
            color: var(--text-primary);
        }

        tr:hover td { background: rgba(255, 255, 255, 0.02); }

        .td-name { font-weight: 600; }

        .td-email {
            color: var(--text-secondary);
            font-size: 13px;
        }

        /* ===== BADGES ===== */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 100px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-pending { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }
        .badge-confirmed { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
        .badge-done { background: rgba(34, 197, 94, 0.15); color: #4ade80; }

        .badge-dot { width: 6px; height: 6px; border-radius: 50%; }

        /* ===== BUTTONS ===== */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            font-family: inherit;
        }

        .btn-primary {
            background: var(--accent);
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-success { background: var(--success); color: white; }

        .btn-success:hover { background: #16a34a; }

        .btn-sm { padding: 6px 12px; font-size: 12px; }

        /* ===== TABS ===== */
        .tabs {
            display: flex;
            gap: 4px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }

        .tab {
            padding: 12px 20px;
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
            background: none;
            border-top: none;
            border-left: none;
            border-right: none;
            font-family: inherit;
        }

        .tab:hover { color: var(--text-primary); }

        .tab.active {
            color: var(--accent);
            border-bottom-color: var(--accent);
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: var(--text-muted);
        }

        .empty-state i { font-size: 48px; margin-bottom: 16px; opacity: 0.5; }

        /* ===== SCROLLBAR ===== */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--text-muted); }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1024px) {
            .charts-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .menu-toggle { display: block; }
            .stats-grid { grid-template-columns: 1fr; }
            .online-bar { justify-content: center; }
            .content { padding: 16px; }
            .card-header {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }
            .selector-group { width: 100%; }
            .selector-btn { flex: 1; text-align: center; }
            .user-info { display: none; }
        }
    </style>
</head>

<body>
    <div class="dashboard">
        
        <!-- SIDEBAR -->
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
        
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="../admin_side/index.php" class="logo">
                    <div class="logo-icon">
                        <i class="fa-solid fa-building"></i>
                    </div>
                    <div>
                        <div class="logo-text">ITPH <span>Admin</span></div>
                        <div class="logo-sub">Real Estate Management</div>
                    </div>
                </a>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">Main</div>
                
                <a href="../admin_side/index.php" class="nav-link <?= isActive('index.php') ?>">
                    <i class="fa-solid fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>

                <a href="../admin_side/ad_account.php" class="nav-link <?= isActive('ad_account.php') ?>">
                    <i class="fa-solid fa-user-cog"></i>
                    <span>My Account</span>
                </a>

                <div class="nav-section">Management</div>

                <div class="nav-dropdown <?= isDropdownActive(['customer_ban.php', 'customer_appointments.php']) ?>">
                    <button class="nav-link" onclick="toggleDropdown(this)">
                        <i class="fa-solid fa-users"></i>
                        <span>Manage Customers</span>
                        <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a href="../admin_side/customer_ban.php" class="dropdown-item <?= isActive('customer_ban.php') ?>">
                            Ban / Unban
                        </a>
                        <a href="../admin_side/customer_appointments.php" class="dropdown-item <?= isActive('customer_appointments.php') ?>">
                            Appointments History
                        </a>
                    </div>
                </div>

                <div class="nav-dropdown <?= isDropdownActive(['add-property.php', 'update_properties.php']) ?>">
                    <button class="nav-link" onclick="toggleDropdown(this)">
                        <i class="fa-solid fa-house"></i>
                        <span>Properties</span>
                        <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a href="../backends/add-property.php" class="dropdown-item <?= isActive('add-property.php') ?>">
                            Add Property
                        </a>
                        <a href="../admin_side/update_properties.php" class="dropdown-item <?= isActive('update_properties.php') ?>">
                            Update Property
                        </a>
                    </div>
                </div>

                <a href="../admin_side/admin_blog_management.php" class="nav-link <?= isActive('admin_blog_management.php') ?>">
                    <i class="fa-solid fa-newspaper"></i>
                    <span>Blog & News</span>
                </a>

                <a href="../admin_side/transaction.php" class="nav-link <?= isActive('transaction.php') ?>">
                    <i class="fa-solid fa-money-bill-transfer"></i>
                    <span>Transactions</span>
                </a>

                <a href="../admin_side/admin_message.php" class="nav-link <?= isActive('admin_message.php') ?>">
                    <i class="fa-solid fa-envelope"></i>
                    <span>Messages</span>
                </a>

                <a href="../admin_side/manage_agent.php" class="nav-link <?= isActive('manage_agent.php') ?>">
                    <i class="fa-solid fa-user-tie"></i>
                    <span>Manage Agents</span>
                </a>

                <div class="nav-section">System</div>

                <a href="../admin_side/audit_log.php" class="nav-link <?= isActive('audit_log.php') ?>">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span>Audit Log</span>
                </a>

                <a href="logout.php" class="nav-link logout">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span>Logout</span>
                </a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <!-- TOPBAR -->
            <header class="topbar">
                <div class="topbar-left">
                    <button class="menu-toggle" onclick="toggleSidebar()">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div>
                        <div class="page-title">Dashboard</div>
                        <div class="breadcrumb">Home / Dashboard</div>
                    </div>
                </div>

                <div class="topbar-right">
                    <button class="notification-btn">
                        <i class="fa-solid fa-bell"></i>
                        <span class="notification-badge"></span>
                    </button>
                    <div class="user-menu">
                        <div class="user-info">
                            <div class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                            <div class="user-role">Administrator</div>
                        </div>
                        <div class="user-avatar">
                            <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                        </div>
                    </div>
                </div>
            </header>

            <!-- CONTENT -->
            <div class="content">

                <!-- STATS -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon blue">
                                <i class="fa-solid fa-house"></i>
                            </div>
                            <span class="stat-trend up">
                                <i class="fa-solid fa-arrow-trend-up"></i> Active
                            </span>
                        </div>
                        <div class="stat-value"><?= $total_properties ?></div>
                        <div class="stat-label">Total Properties</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon green">
                                <i class="fa-solid fa-users"></i>
                            </div>
                            <span class="stat-trend up">
                                <i class="fa-solid fa-arrow-trend-up"></i> Growing
                            </span>
                        </div>
                        <div class="stat-value"><?= $total_users ?></div>
                        <div class="stat-label">Registered Users</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon amber">
                                <i class="fa-solid fa-calendar-check"></i>
                            </div>
                            <span class="stat-trend up">
                                <i class="fa-solid fa-arrow-trend-up"></i> +12%
                            </span>
                        </div>
                        <div class="stat-value"><?= $total_reservations ?></div>
                        <div class="stat-label">Total Appointments</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon purple">
                                <i class="fa-solid fa-newspaper"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?= $total_blogs ?></div>
                        <div class="stat-label">Blog Articles</div>
                    </div>
                </div>

                <!-- ONLINE USERS -->
                <div class="online-bar">
                    <div class="online-pill">
                        <span class="online-dot green"></span>
                        <?= $total_online_customers ?> Customers Online
                    </div>
                    <div class="online-pill">
                        <span class="online-dot blue"></span>
                        <?= $total_online_agents ?> Agents Online
                    </div>
                    <div class="online-pill">
                        <span class="online-dot amber"></span>
                        <?= $total_online_admins ?> Admins Online
                    </div>
                </div>

                <!-- CHARTS SECTION -->
                <div class="charts-grid">
                    <!-- MAIN CHART -->
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <div class="card-title">Appointments Overview</div>
                                <div class="card-subtitle">Monthly booking trends</div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="monthlyChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- PIE CHART -->
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <div class="card-title">Property Views</div>
                                <div class="card-subtitle">Most popular listings</div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="viewsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ANALYTICS CARD -->
                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">Analytics Reports</div>
                            <div class="card-subtitle">Detailed breakdown of your data</div>
                        </div>
                        <div class="selector-group">
                            <button class="selector-btn active" onclick="switchChart('views', this)">Views</button>
                            <button class="selector-btn" onclick="switchChart('gender', this)">Gender</button>
                            <button class="selector-btn" onclick="switchChart('location', this)">Location</button>
                            <button class="selector-btn" onclick="switchChart('realtor', this)">Agents</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="analyticsChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- RESERVATIONS TABLE -->
                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">Recent Reservations</div>
                            <div class="card-subtitle">Latest booking activity</div>
                        </div>
                        <a href="?download_report=1" class="btn btn-primary btn-sm">
    <i class="fa-solid fa-file-pdf"></i> Download Report
</a>
                    </div>
                    <div class="card-body">
                        <div class="tabs">
                            <button class="tab active" onclick="switchTab('recent', this)">Recent</button>
                            <button class="tab" onclick="switchTab('done', this)">Completed</button>
                        </div>

                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Client</th>
                                        <th>Property</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="reservationTable">
                                    <?php if ($res_query && $res_query->num_rows > 0):
                                        while ($row = $res_query->fetch_assoc()):
                                            $status_class = match ($row['status']) {
                                                'Confirmed' => 'badge-confirmed',
                                                'Done' => 'badge-done',
                                                default => 'badge-pending'
                                            };
                                            $status_color = match ($row['status']) {
                                                'Confirmed' => 'blue',
                                                'Done' => 'green',
                                                default => 'amber'
                                            };
                                    ?>
                                            <tr>
                                                <td>
                                                    <div class="td-name"><?= htmlspecialchars($row['fullname']) ?></div>
                                                    <div class="td-email"><?= htmlspecialchars($row['email']) ?></div>
                                                </td>
                                                <td><?= htmlspecialchars($row['property']) ?></td>
                                                <td><?= htmlspecialchars($row['created_at'] ?? 'N/A') ?></td>
                                                <td>
                                                    <span class="badge <?= $status_class ?>">
                                                        <span class="badge-dot" style="background: var(--<?= $status_color ?>)"></span>
                                                        <?= htmlspecialchars($row['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($row['status'] === 'Pending'): ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="reservation_id" value="<?= $row['id'] ?>">
                                                            <button type="submit" name="confirm_booking" class="btn btn-primary btn-sm">
                                                                <i class="fa-solid fa-check"></i> Confirm
                                                            </button>
                                                        </form>
                                                    <?php elseif ($row['status'] === 'Confirmed'): ?>

    <form method="POST" style="display:inline-flex; gap:8px; align-items:center;">
        <input type="hidden" name="reservation_id" value="<?= $row['id'] ?>">

        <select name="payment_method" required class="btn btn-sm" 
            style="background:#1e293b; color:white; border:1px solid #334155;">
            <option value="">Select Payment</option>
            <option value="Cash">Cash</option>
            <option value="Installment">Installment</option>
        </select>

        <button type="submit" name="send_notification" class="btn btn-success btn-sm">
            <i class="fa-solid fa-paper-plane"></i> Notify
        </button>
    </form>

<?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile;
                                    else: ?>
                                        <tr>
                                            <td colspan="5">
                                                <div class="empty-state">
                                                    <i class="fa-solid fa-inbox"></i>
                                                    <p>No reservations found</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- CONTACT MESSAGES -->
                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">Contact Messages</div>
                            <div class="card-subtitle">Recent inquiries from clients</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Contact</th>
                                        <th>Message</th>
                                        <th>Received</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($contact_messages)):
                                        foreach ($contact_messages as $msg): ?>
                                            <tr>
                                                <td>
                                                    <div class="td-name"><?= htmlspecialchars($msg['name']) ?></div>
                                                </td>
                                                <td>
                                                    <div class="td-email"><?= htmlspecialchars($msg['email']) ?></div>
                                                    <div style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($msg['phone']) ?></div>
                                                </td>
                                                <td style="max-width: 400px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                    <?= htmlspecialchars($msg['message']) ?>
                                                </td>
                                                <td><?= htmlspecialchars($msg['created_at']) ?></td>
                                            </tr>
                                        <?php endforeach;
                                    else: ?>
                                        <tr>
                                            <td colspan="4">
                                                <div class="empty-state">
                                                    <i class="fa-solid fa-envelope-open"></i>
                                                    <p>No messages yet</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
        // ===== SIDEBAR & DROPDOWN =====
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        }

        function toggleDropdown(button) {
            const dropdown = button.parentElement;
            const wasOpen = dropdown.classList.contains('open');
            document.querySelectorAll('.nav-dropdown.open').forEach(d => {
                if (d !== dropdown) d.classList.remove('open');
            });
            dropdown.classList.toggle('open', !wasOpen);
        }

        // Auto-open active dropdowns
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.nav-dropdown').forEach(dropdown => {
                if (dropdown.querySelector('.dropdown-item.active')) {
                    dropdown.classList.add('open');
                }
            });
        });

        // ===== CHART COLORS =====
        const colors = {
            primary: '#3b82f6',
            primaryLight: 'rgba(59, 130, 246, 0.2)',
            success: '#22c55e',
            warning: '#f59e0b',
            purple: '#a855f7',
            pink: '#ec4899',
            cyan: '#06b6d4',
            text: '#94a3b8',
            grid: '#334155'
        };

        // ===== MONTHLY LINE CHART =====
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyGradient = monthlyCtx.createLinearGradient(0, 0, 0, 350);
        monthlyGradient.addColorStop(0, colors.primaryLight);
        monthlyGradient.addColorStop(1, 'rgba(59, 130, 246, 0)');

        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: [<?php foreach ($monthly_data as $md) { echo "'" . substr($md['month'], 0, 3) . "',"; } ?>],
                datasets: [{
                    label: 'Reservations',
                    data: [<?php foreach ($monthly_data as $md) { echo $md['total'] . ','; } ?>],
                    borderColor: colors.primary,
                    backgroundColor: monthlyGradient,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: colors.primary,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleColor: '#f1f5f9',
                        bodyColor: '#94a3b8',
                        borderColor: '#334155',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8,
                        displayColors: false
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: colors.text }
                    },
                    y: {
                        grid: { color: colors.grid, drawBorder: false },
                        ticks: { color: colors.text, padding: 10 },
                        beginAtZero: true
                    }
                }
            }
        });

        // ===== VIEWS DOUGHNUT CHART =====
        const viewsCtx = document.getElementById('viewsChart').getContext('2d');
        new Chart(viewsCtx, {
            type: 'doughnut',
            data: {
                labels: [<?php foreach ($pie_data as $pd) { echo "'" . addslashes($pd[0]) . "',"; } ?>],
                datasets: [{
                    data: [<?php foreach ($pie_data as $pd) { echo $pd[1] . ','; } ?>],
                    backgroundColor: [
                        colors.primary, colors.success, colors.warning,
                        colors.purple, colors.pink, colors.cyan,
                        '#f97316', '#84cc16', '#14b8a6', '#6366f1'
                    ],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: colors.text,
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: { size: 12 }
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleColor: '#f1f5f9',
                        bodyColor: '#94a3b8',
                        borderColor: '#334155',
                        borderWidth: 1,
                        padding: 12,
                        cornerRadius: 8
                    }
                }
            }
        });

        // ===== ANALYTICS CHART =====
        let analyticsChart = null;
        const analyticsCtx = document.getElementById('analyticsChart').getContext('2d');

        const chartData = {
            views: {
                labels: [<?php foreach ($pie_data as $pd) { echo "'" . addslashes($pd[0]) . "',"; } ?>],
                data: [<?php foreach ($pie_data as $pd) { echo $pd[1] . ','; } ?>],
                label: 'Property Views',
                color: colors.primary
            },
            gender: {
                labels: [<?php foreach ($gender_data as $gd) { echo "'" . $gd[0] . "',"; } ?>],
                data: [<?php foreach ($gender_data as $gd) { echo $gd[1] . ','; } ?>],
                label: 'Reservations by Gender',
                color: colors.success
            },
            location: {
                labels: [<?php foreach ($location_data as $ld) { echo "'" . addslashes($ld[0]) . "',"; } ?>],
                data: [<?php foreach ($location_data as $ld) { echo $ld[1] . ','; } ?>],
                label: 'Reservations by Location',
                color: colors.warning
            },
            realtor: {
                labels: [<?php echo "'" . implode("','", $agent_labels) . "'"; ?>],
                data: [<?php echo implode(",", $agent_values); ?>],
                label: 'Done Deals by Agent',
                color: colors.purple
            }
        };

        function drawAnalyticsChart(type) {
            const data = chartData[type];
            if (analyticsChart) analyticsChart.destroy();

            analyticsChart = new Chart(analyticsCtx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: data.label,
                        data: data.data,
                        backgroundColor: data.color + '33',
                        borderColor: data.color,
                        borderWidth: 2,
                        borderRadius: 6,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            titleColor: '#f1f5f9',
                            bodyColor: '#94a3b8',
                            borderColor: '#334155',
                            borderWidth: 1,
                            padding: 12,
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: colors.text }
                        },
                        y: {
                            grid: { color: colors.grid, drawBorder: false },
                            ticks: { color: colors.text },
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        drawAnalyticsChart('views');

        function switchChart(type, btn) {
            document.querySelectorAll('.selector-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            drawAnalyticsChart(type);
        }

        // ===== TAB SWITCHING =====
        function switchTab(type, btn) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            btn.classList.add('active');
            window.location.href = '?type=' + type;
        }
    </script>
</body>
</html>