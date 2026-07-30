<?php

header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://www.gstatic.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self';");
header("X-Content-Type-Options: nosniff"); header("X-Frame-Options: SAMEORIGIN"); header("Referrer-Policy: no-referrer-when-downgrade");
session_start();
require_once __DIR__ . '/../backends/config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

$conn = get_db_connection();

// Auth check
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}
if(isset($_POST['confirm_reservation'])){
    $reservation_id = $_POST['reservation_id'];

    // Fetch reservation details
    $stmt = $conn->prepare("SELECT fullname, email, property FROM reservations WHERE id = ?");
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $stmt->bind_result($client_name, $client_email, $property);
    $stmt->fetch();
    $stmt->close();

    // Update reservation status to Confirmed
    $update = $conn->prepare("UPDATE reservations SET status = 'Confirmed', agent_id = ? WHERE id = ?");
    $update->bind_param("ii", $_SESSION['id'], $reservation_id);
    $update->execute();
    $update->close();

    // Prepare email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'itph934@gmail.com';
        $mail->Password   = 'bjhg rpeh ywaw eofo';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('itph934@gmail.com', 'ITPH ADMIN');
        $mail->addAddress($client_email, $client_name);

        $agent_name = $_SESSION['username'];

        $mail->isHTML(true);
        $mail->Subject = "Your Property Appointment is Confirmed";
        $mail->Body    = "
            <p>Good morning <strong>$client_name</strong>,</p>
            <p>Your appointment has been approved and the property <strong>$property</strong> is ready to view.</p>
            <p>Please look for <strong>$agent_name</strong> for your property appointment.</p>
            <p>Please bring the requirements for a smooth transaction.</p>
            <p>Thank you for choosing <strong>Iloilo Top Property Homes</strong> for choosing your dream house!</p>
            <p>- ITPH ADMIN</p>
        ";

        $mail->send();
        $_SESSION['message'] = "Reservation confirmed and email sent to client!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }

    header("Location: agent_dashboard.php");
    exit;
}

//done 
// =====================
// Agent marks transaction
// =====================
if(isset($_POST['agent_done'])){

    $reservation_id = (int)$_POST['reservation_id'];
    $action = $_POST['action'];

    if($action == "spot_cash"){

        $stmt = $conn->prepare("
            UPDATE reservations
            SET
                status='Waiting Admin Approval',
                payment_type='Spot Cash'
            WHERE id=?
        ");

    }else{

        $stmt = $conn->prepare("
            UPDATE reservations
            SET
                status='Waiting Admin Approval',
                payment_type='Installment'
            WHERE id=?
        ");

    }

    $stmt->bind_param("i",$reservation_id);
    $stmt->execute();

    header("Location: agent_dashboard.php");
    exit;
}

 
// Fetch agent info
$stmt = $conn->prepare("SELECT username, gmail FROM agents WHERE id = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$stmt->bind_result($username, $email);
$stmt->fetch();
$stmt->close();

$_SESSION['username'] = $username;
$_SESSION['email'] = $email;

$agent_id = $_SESSION['id'];

// Total reservations claimed by this agent
$claimed_query = $conn->prepare("SELECT COUNT(*) as total_claimed FROM reservations WHERE agent_id = ?");
$claimed_query->bind_param("i", $agent_id);
$claimed_query->execute();
$claimed_result = $claimed_query->get_result()->fetch_assoc();
$total_claimed = $claimed_result['total_claimed'] ?? 0;
$claimed_query->close();

// Total done deals handled by this agent
$done_query = $conn->prepare("SELECT COUNT(*) as total_done FROM reservations WHERE agent_id = ? AND status = 'Done'");
$done_query->bind_param("i", $agent_id);
$done_query->execute();
$done_result = $done_query->get_result()->fetch_assoc();
$total_done = $done_result['total_done'] ?? 0;
$done_query->close();

// ================= AGENT PERFORMANCE DATA =================

// Arrays for chart
$months = [];
$claimed_counts = [];
$done_counts = [];

// Fetch monthly claimed reservations by this agent
$claimed_monthly_query = $conn->prepare("
    SELECT 
        MONTH(created_at) as month_num,
        MONTHNAME(created_at) as month_name,
        COUNT(*) as claimed_total
    FROM reservations
    WHERE agent_id = ? AND YEAR(created_at) = YEAR(CURDATE())
    GROUP BY MONTH(created_at), MONTHNAME(created_at)
    ORDER BY MONTH(created_at)
");
$claimed_monthly_query->bind_param("i", $agent_id);
$claimed_monthly_query->execute();
$claimed_result = $claimed_monthly_query->get_result();

// Map month number to claimed total
$claimed_map = [];
while($row = $claimed_result->fetch_assoc()){
    $claimed_map[$row['month_num']] = (int)$row['claimed_total'];
}
$claimed_monthly_query->close();

// Fetch monthly done deals handled by this agent
$done_monthly_query = $conn->prepare("
    SELECT 
        MONTH(created_at) as month_num,
        COUNT(*) as done_total
    FROM reservations
    WHERE agent_id = ? AND YEAR(created_at) = YEAR(CURDATE())
        AND status = 'Done'
    GROUP BY MONTH(created_at)
    ORDER BY MONTH(created_at)
");
$done_monthly_query->bind_param("i", $agent_id);
$done_monthly_query->execute();
$done_result = $done_monthly_query->get_result();

// Map month number to done total
$done_map = [];
while($row = $done_result->fetch_assoc()){
    $done_map[$row['month_num']] = (int)$row['done_total'];
}
$done_monthly_query->close();

// Prepare arrays for chart (Jan-Dec)
for($m=1; $m<=12; $m++){
    $months[] = date("M", mktime(0,0,0,$m,1));
    $claimed_counts[] = $claimed_map[$m] ?? 0;
    $done_counts[] = $done_map[$m] ?? 0;
}


/* ================= DATA ================= */

// Recent reservations
$reservations = [];
$res_query = $conn->query("SELECT * FROM reservations ORDER BY id DESC LIMIT 5");
while($row = $res_query->fetch_assoc()){
    $reservations[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Agent Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="agent.css">
</head>

<body>
<div class="dashboard">

<!-- ─── SIDEBAR ─── -->
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-wordmark">Real<span>Estate</span></div>
        <div class="brand-sub">Agent Portal</div>
    </div>
    
    <nav class="sidebar-nav">
        <div class="nav-section">Main</div>
        <div class="nav-item active">
            <a href="agent_dashboard.php">
                <span class="nav-icon"><i class="fas fa-chart-line"></i></span>
                Dashboard
            </a>
        </div>
        <div class="nav-item">
            <a href="my_transactions.php">
                <span class="nav-icon"><i class="fas fa-calendar-check"></i></span>
                My Transactions
            </a>
        </div>
    
        
        <div class="nav-section">Account</div>
        <div class="nav-item">
            <a href="agent_account.php">
                <span class="nav-icon"><i class="fas fa-user"></i></span>
                Profile
            </a>
        </div>
        <div class="nav-item">
            <a href="logout.php">
                <span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span>
                Logout
            </a>
        </div>
    </nav>
    
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar">
                <?= strtoupper(substr($_SESSION['username'],0,1)) ?>
            </div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                <div class="sidebar-user-role">Real Estate Agent</div>
            </div>
        </div>
    </div>
</aside>

<div class="main-content">

    <!-- ─── TOPBAR ─── -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="hamburger" onclick="toggleSidebar()" id="hamburger">
                <span></span><span></span><span></span>
            </button>
            <div class="topbar-title">Dashboard</div>
        </div>
        <div class="topbar-right">
            <div class="topbar-time" id="clock">--:--</div>
            <button class="notification-btn">
                <i class="fas fa-bell"></i>
                <span class="notification-dot"></span>
            </button>
        </div>
    </header>

    <!-- ─── CONTENT ─── -->
    <div class="page-content">

        <!-- Flash Messages -->
        <?php if(isset($_SESSION['message'])): ?>
            <div class="flash flash-success">
                <i class="fas fa-circle-check"></i>
                <span><?= htmlspecialchars($_SESSION['message']) ?></span>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        <?php if(isset($_SESSION['error'])): ?>
            <div class="flash flash-error">
                <i class="fas fa-circle-exclamation"></i>
                <span><?= htmlspecialchars($_SESSION['error']) ?></span>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Welcome Hero -->
        <div class="welcome-hero">
            <h2>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></h2>
            <p>Manage your appointments and client messages.</p>
            <div class="welcome-meta">
                <span><i class="fas fa-clock"></i> Agent Portal</span>
                <span><i class="fas fa-shield-halved"></i> Secure Session</span>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Appointments Claimed</div>
                <div class="stat-number"><?= $total_claimed ?></div>
                <div class="stat-icon"><i class="fas fa-handshake"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Done Deals</div>
                <div class="stat-number"><?= $total_done ?></div>
                <div class="stat-icon"><i class="fas fa-check"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Pending</div>
                <div class="stat-number">0</div>
                <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Close Rate</div>
                <div class="stat-number"><?= $total_claimed > 0 ? round(($total_done / $total_claimed) * 100) : 0 ?>%</div>
                <div class="stat-icon"><i class="fas fa-chart-pie"></i></div>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div class="two-col">
            <div class="main-col">

                <!-- Chart -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Agent Performance</div>
                        <div style="font-size:12px;color:var(--text-muted);"><?= date('Y') ?></div>
                    </div>
                    <div class="card-body">
                        <div class="chart-wrap">
                            <canvas id="agentPerformanceChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Reservations Table -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Recent Reservations</div>
                    </div>
                    <div class="table-responsive">
                        <table> 
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Property</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if(!empty($reservations)):
                                foreach($reservations as $row): ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:500;color:var(--text);"><?= htmlspecialchars($row['fullname']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars($row['phone']) ?></td>
                                    <td><?= htmlspecialchars($row['property']) ?></td>
                                    <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <?php if($row['status'] === 'Done' && $row['agent_id'] == $_SESSION['id']): ?>
                                            <span class="badge badge-success"><i class="fas fa-check" style="font-size:9px;"></i> Done by you</span>
                                        <?php elseif($row['status'] === 'Confirmed' && $row['agent_id'] == $_SESSION['id']): ?>
                                            <span class="badge badge-gold"><i class="fas fa-check-double" style="font-size:9px;"></i> Confirmed</span>
                                        <?php elseif($row['status'] === 'Pending'): ?>
                                            <span class="badge badge-warning"><i class="fas fa-clock" style="font-size:9px;"></i> Pending</span>
                                        <?php else: ?>
                                            <span class="badge badge-muted"><?= htmlspecialchars($row['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                <td>

<?php if($row['status'] == 'Done' && $row['agent_id'] == $_SESSION['id']): ?>

    <span style="color:var(--text-muted);font-size:12px;font-style:italic;">
        Completed
    </span>

<?php elseif($row['status'] == 'Waiting Admin Approval' && $row['agent_id'] == $_SESSION['id']): ?>

    <button class="btn btn-warning btn-sm" disabled>
        <i class="fas fa-hourglass-half"></i>
        Waiting for Admin Approval
    </button>

<?php elseif($row['status'] == 'Confirmed' && $row['agent_id'] == $_SESSION['id']): ?>

<form method="POST" style="display:flex;gap:8px;align-items:center;">

    <input type="hidden"
           name="reservation_id"
           value="<?= $row['id'] ?>">

    <select name="action" required>

        <option value="">Done ▼</option>

        <option value="spot_cash">
            Spot Cash Completed
        </option>

        <option value="installment">
            Installment Requirements Received
        </option>

    </select>

    <button
        type="submit"
        name="agent_done"
        class="btn btn-success btn-sm">

        Submit

    </button>

</form>

<?php elseif(empty($row['agent_id'])): ?>

    <button
        class="btn btn-success btn-sm"
        onclick="openConfirmModal(
            <?= $row['id'] ?>,
            '<?= htmlspecialchars(addslashes($row['fullname'])) ?>',
            '<?= htmlspecialchars(addslashes($row['property'])) ?>'
        )">

        <i class="fas fa-hand"></i>
        Claim

    </button>

<?php else: ?>

    <span style="color:var(--text-muted);font-size:12px;">
        <i class="fas fa-lock"></i>
        Taken
    </span>

<?php endif; ?>

</td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <i class="fas fa-inbox"></i>
                                            <p>No Appointments yet</p>
                                            <span>New client appointments will appear here</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <div class="side-col">

                <!-- Messages -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Recent Messages</div>
                    </div>
                    <div class="card-body">
                        <div class="empty-state" style="padding:40px 20px;">
                            <i class="fas fa-envelope-open"></i>
                            <p>No messages yet</p>
                            <span>Client messages will appear here</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>
</div>

<!-- ─── CONFIRMATION MODAL ─── -->
<div class="modal-overlay" id="confirmModal" onclick="if(event.target===this)closeConfirmModal()">
    <div class="modal">
        <div class="modal-header">
            <h3>Confirm Reservation</h3>
            <p>Review details before confirming</p>
        </div>
        <div class="modal-body">
            <div class="modal-detail">
                <div class="modal-detail-row">
                    <span class="modal-detail-label">Client</span>
                    <span class="modal-detail-value" id="modalClient">--</span>
                </div>
                <div class="modal-detail-row">
                    <span class="modal-detail-label">Property</span>
                    <span class="modal-detail-value" id="modalProperty">--</span>
                </div>
                <div class="modal-detail-row">
                    <span class="modal-detail-label">Agent</span>
                    <span class="modal-detail-value"><?= htmlspecialchars($_SESSION['username']) ?></span>
                </div>
            </div>
            <p style="font-size:13px;color:var(--text-muted);line-height:1.6;">
                This will send a confirmation email to the client and assign this reservation to you.
            </p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeConfirmModal()">Cancel</button>
            <form method="POST" style="flex:1;">
                <input type="hidden" name="reservation_id" id="modalResId">
                <button type="submit" name="confirm_reservation" class="btn btn-success" style="width:100%;">
                    <i class="fas fa-check" style="font-size:11px;"></i> Confirm & Notify
                </button>
            </form>
        </div>
    </div>
</div>

<script>
// ─── SIDEBAR ───
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.querySelector('.sidebar-overlay').classList.toggle('active');
    document.getElementById('hamburger').classList.toggle('active');
}

// ─── CLOCK ───
function updateClock() {
    const now = new Date();
    document.getElementById('clock').textContent = now.toLocaleTimeString('en-US', { 
        hour: '2-digit', minute: '2-digit' 
    });
}
updateClock();
setInterval(updateClock, 1000);

// ─── CHART ───
const ctx = document.getElementById('agentPerformanceChart').getContext('2d');

const gradientGold = ctx.createLinearGradient(0, 0, 0, 320);
gradientGold.addColorStop(0, 'rgba(201,168,76,0.15)');
gradientGold.addColorStop(1, 'rgba(201,168,76,0)');

const gradientGreen = ctx.createLinearGradient(0, 0, 0, 320);
gradientGreen.addColorStop(0, 'rgba(90,171,122,0.15)');
gradientGreen.addColorStop(1, 'rgba(90,171,122,0)');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [
            {
                label: 'Reservations Claimed',
                data: <?= json_encode($claimed_counts) ?>,
                borderColor: 'rgba(201,168,76,1)',
                backgroundColor: gradientGold,
                borderWidth: 2.5,
                tension: 0.4,
                fill: true,
                pointRadius: 5,
                pointBackgroundColor: 'rgba(201,168,76,1)',
                pointBorderColor: '#0a0a0a',
                pointBorderWidth: 2.5,
                pointHoverRadius: 7,
            },
            {
                label: 'Done Deals',
                data: <?= json_encode($done_counts) ?>,
                borderColor: 'rgba(90,171,122,1)',
                backgroundColor: gradientGreen,
                borderWidth: 2.5,
                tension: 0.4,
                fill: true,
                pointRadius: 5,
                pointBackgroundColor: 'rgba(90,171,122,1)',
                pointBorderColor: '#0a0a0a',
                pointBorderWidth: 2.5,
                pointHoverRadius: 7,
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: {
                position: 'top',
                align: 'end',
                labels: {
                    color: '#a39e96',
                    font: { size: 12, family: 'Inter' },
                    usePointStyle: true,
                    pointStyle: 'circle',
                    padding: 20,
                    boxWidth: 8,
                }
            },
            tooltip: {
                backgroundColor: '#161616',
                titleColor: '#f0ece3',
                bodyColor: '#a39e96',
                borderColor: '#2a2a2a',
                borderWidth: 1,
                padding: 14,
                cornerRadius: 10,
                displayColors: true,
                titleFont: { size: 13, weight: '600' },
                bodyFont: { size: 12 },
            }
        },
        scales: {
            x: {
                ticks: { color: '#6b6560', font: { size: 11 } },
                grid: { color: 'rgba(42,42,42,0.4)', drawBorder: false }
            },
            y: {
                ticks: { color: '#6b6560', font: { size: 11 }, padding: 10 },
                grid: { color: 'rgba(42,42,42,0.4)', drawBorder: false },
                beginAtZero: true,
            }
        }
    }
});

// ─── MODAL ───
function openConfirmModal(id, client, property) {
    document.getElementById('modalResId').value = id;
    document.getElementById('modalClient').textContent = client;
    document.getElementById('modalProperty').textContent = property;
    document.getElementById('confirmModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeConfirmModal() {
    document.getElementById('confirmModal').classList.remove('active');
    document.body.style.overflow = '';
}

// ─── FLASH AUTO-DISMISS ───
const flash = document.querySelector('.flash');
if (flash) {
    setTimeout(() => {
        flash.style.opacity = '0';
        flash.style.transform = 'translateY(-8px)';
        flash.style.transition = 'all 0.4s';
        setTimeout(() => flash.remove(), 400);
    }, 5000);
}

// ─── ESCAPE KEY ───
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeConfirmModal();
});
</script>

</body>
</html>