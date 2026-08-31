<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js https://cdnjs.cloudflare.com https://www.gstatic.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; img-src 'self' data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self';"); 
header("X-Content-Type-Options: nosniff"); 
header("X-Frame-Options: SAMEORIGIN"); 
header("Referrer-Policy: no-referrer-when-downgrade");
session_start();
require_once '../backends/config.php';
$conn = get_db_connection();

// Auth check
if (!isset($_SESSION['fullname'])) {
    header("Location: login.php?redirect=notif.php");
    exit();
}

$user_name = $_SESSION['fullname'];

// Mark notification as seen
if(isset($_POST['seen_id'])) {
    $seen_id = (int)$_POST['seen_id'];
    $stmt = $conn->prepare("UPDATE reservations SET seen=1 WHERE id=?");
    $stmt->bind_param("i", $seen_id);
    $stmt->execute();
    $stmt->close();
    header("Location: notifications.php");
    exit();
}

// Fetch notifications
$stmt = $conn->prepare("SELECT id, property, date, status, notification_sent,seen FROM reservations WHERE fullname=? AND status IN ('Confirmed','Done') ORDER BY created_at DESC");
$stmt->bind_param("s", $user_name);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications - ITPH</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="css/common.css">
<link rel="stylesheet" href="css/index.css">

<style>
.account-hero {
    position: relative;
    background: url('../photo/nbg.jpg') center/cover no-repeat;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 60px 15px;
}
.account-hero::before {
    content: "";
    position: absolute;
    top:0; left:0; right:0; bottom:0;
    background: rgba(0,0,0,0.6);
    z-index: 1;
}
.account-card {
    position: relative;
    z-index: 2;
    background: rgba(255,255,255,0.95);
    padding: 30px;
    border-radius: 12px;
    max-width: 900px;
    width: 100%;
    box-shadow: 0 8px 20px rgba(0,0,0,0.3);
}
.account-card table {
    background: white;
    border-radius: 8px;
}
.account-card table th,
.account-card table td {
    vertical-align: middle;
}
.notification-button {
    margin-top: 5px;
}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light shadow-sm">
<div class="container">
    <a class="navbar-brand" href="../index.php">ITPH</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
            <li class="nav-item">
                <a href="account.php" class="nav-link">My Account</a>
            </li>
            <li class="nav-item">
                <a href="reservation.php" class="nav-link">Book Now</a>
            </li>
            <li class="nav-item">
                <a href="notifications.php" class="nav-link active">Notifications</a>
            </li>
            <li class="nav-item">
                <a href="logout.php" class="btn btn-danger ms-2">Logout</a>
            </li>
        </ul>
    </div>
</div>
</nav>

<section class="account-hero">
<div class="account-card">
    <h3 class="mb-3 text-center">Notifications</h3>
    <?php if(count($notifications) > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered text-center">
                <thead class="table-light">
                    <tr>
                        <th>Property</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($notifications as $n): ?>
                    <tr>
                        <td><?= htmlspecialchars($n['property']) ?></td>
                        <td><?= htmlspecialchars($n['date']) ?></td>
                        <td>
                            <?= htmlspecialchars($n['status']) ?>
                            <?php if($n['status'] == 'Done'): ?>
                                <span class="text-success fw-bold">✔️</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if(!$n['seen']): ?>
                                <form method="POST">
                                    <input type="hidden" name="seen_id" value="<?= $n['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-primary notification-button">Seen</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted">Seen</span>
                                <?php if($n['status'] == 'Done'): ?>
                                    <div class="text-success mt-1">🎉 The house is yours!</div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-center">You have no notifications.</p>
    <?php endif; ?>
</div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>