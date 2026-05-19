<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://www.gstatic.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self';");
header("X-Content-Type-Options: nosniff"); header("X-Frame-Options: SAMEORIGIN"); header("Referrer-Policy: no-referrer-when-downgrade");
session_start();
session_unset();
session_destroy();
header("Location: agentlog_in.php");
exit;
if(isset($_SESSION['id'])) {
    $userId = $_SESSION['id'];

    $updateLog = $conn->prepare("UPDATE auth_logs
    SET session_status='offline', login_status='logout', activity_time=NOW()
    WHERE user_id=?
    ORDER BY id DESC
    LIMIT 1");

    $updateLog->bind_param("i", $userId);
    $updateLog->execute();
    $updateLog->close();
}
?>