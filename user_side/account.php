<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://www.gstatic.com https://cdn.botpress.cloud https://files.bpcontent.cloud; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; img-src 'self' data: https:; connect-src 'self' https://cdn.botpress.cloud https://files.bpcontent.cloud wss://*.botpress.cloud https://*.botpress.cloud; frame-src https://cdn.botpress.cloud; frame-ancestors 'self'; base-uri 'self';");
header("X-Content-Type-Options: nosniff"); 
header("X-Frame-Options: SAMEORIGIN"); 
header("Referrer-Policy: no-referrer-when-downgrade");
session_start();
require_once '../backends/config.php';
$conn = get_db_connection();

if (!isset($_SESSION['fullname'])) {
    header("Location: login.php?redirect=account.php");
    exit();
}

$user_name = $_SESSION['fullname'];
$user_id   = $_SESSION['user_id'];

// Handle "Mark as Seen"
if (isset($_POST['seen_id'])) {
    $seen_id = (int)$_POST['seen_id'];
    $stmt = $conn->prepare("UPDATE reservations SET seen=1 WHERE id=?");
    $stmt->bind_param("i", $seen_id);
    $stmt->execute();
    $stmt->close();
    header("Location: account.php");
    exit();
}

// Handle account update
if (isset($_POST['update_account'])) {
    $fullname        = trim($_POST['fullname']);
    $email           = trim($_POST['email']);
    $secondary_email = trim($_POST['secondary_email']);
    $gender          = trim($_POST['gender']);
    $location        = trim($_POST['location']);
    $status          = trim($_POST['status']);
    $phone           = trim($_POST['phone']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Primary email is invalid.";
    } elseif (!empty($secondary_email) && !filter_var($secondary_email, FILTER_VALIDATE_EMAIL)) {
        $message = "Secondary email is invalid.";
    } else {
        $stmt = $conn->prepare("UPDATE customers SET fullname=?, email=?, secondary_email=?, gender=?, location=?, status=?, phone=? WHERE id=?");
        $stmt->bind_param("sssssssi", $fullname, $email, $secondary_email, $gender, $location, $status, $phone, $user_id);
        if ($stmt->execute()) {
            $_SESSION['fullname'] = $fullname;
            $_SESSION['email']    = $email;
            header("Location: account.php?updated=1");
            exit();
        } else {
            $message = "Failed to update account.";
        }
        $stmt->close();
    }
}

// Handle Confirm Booking
if (isset($_POST['confirm_booking'])) {
    $reservation_id = (int)$_POST['confirm_booking'];
    $stmt = $conn->prepare("UPDATE reservations SET status='Confirmed', seen=0 WHERE id=?");
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch user info
$stmt = $conn->prepare("SELECT id, fullname, email, secondary_email, created_at, gender, location, status, phone FROM customers WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// ==========================================
// FETCH ALL RESERVATIONS
// ==========================================
$stmt = $conn->prepare("
    SELECT *
    FROM reservations
    WHERE fullname = ?
    ORDER BY date DESC, id DESC
");

$stmt->bind_param("s", $user_name);
$stmt->execute();

$reservations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt->close();


// ==========================================
// FETCH AGENT + RATING INFORMATION
// ==========================================

foreach ($reservations as &$reservation) {

    $reservation['agent_data'] = null;
    $reservation['agent_rating'] = 0;
    $reservation['agent_rating_count'] = 0;
    $reservation['customer_rated'] = false;

    if (!empty($reservation['agent_id'])) {

        // -------------------------------
        // AGENT INFORMATION
        // -------------------------------
        $agentStmt = $conn->prepare("
            SELECT id, username, phone
            FROM agents
            WHERE id = ?
            LIMIT 1
        ");

        $agentStmt->bind_param(
            "i",
            $reservation['agent_id']
        );

        $agentStmt->execute();

        $reservation['agent_data'] =
            $agentStmt->get_result()->fetch_assoc();

        $agentStmt->close();


        // -------------------------------
        // AGENT RATING
        // -------------------------------
        $ratingStmt = $conn->prepare("
            SELECT
                AVG(rating) AS average_rating,
                COUNT(*) AS rating_count
            FROM agent_ratings
            WHERE agent_id = ?
        ");

        $ratingStmt->bind_param(
            "i",
            $reservation['agent_id']
        );

        $ratingStmt->execute();

        $ratingData =
            $ratingStmt->get_result()->fetch_assoc();

        $ratingStmt->close();


        $reservation['agent_rating'] =
            !empty($ratingData['average_rating'])
                ? round((float)$ratingData['average_rating'], 1)
                : 0;

        $reservation['agent_rating_count'] =
            (int)($ratingData['rating_count'] ?? 0);


        // -------------------------------
        // CHECK IF THIS CUSTOMER
        // ALREADY RATED THIS APPOINTMENT
        // -------------------------------
        $ratedStmt = $conn->prepare("
            SELECT id
            FROM agent_ratings
            WHERE reservation_id = ?
              AND customer_id = ?
            LIMIT 1
        ");

        $ratedStmt->bind_param(
            "ii",
            $reservation['id'],
            $user_id
        );

        $ratedStmt->execute();

        $ratedResult =
            $ratedStmt->get_result()->fetch_assoc();

        $ratedStmt->close();

        if ($ratedResult) {
            $reservation['customer_rated'] = true;
        }
    }
}

unset($reservation);

// Unread notifications
$unread_count = 0;
foreach ($reservations as $r) {
    if (in_array($r['status'], ['Confirmed', 'Done']) && !$r['seen']) $unread_count++;
}

// Initials for avatar
$initials = strtoupper(implode('', array_map(fn($w) => $w[0], explode(' ', trim($user['fullname'])))));
$initials = substr($initials, 0, 2);
// Handle Cancel Appointment
if (isset($_POST['cancel_booking'])) {
    $reservation_id = (int)$_POST['cancel_booking'];

$stmt = $conn->prepare("
UPDATE reservations
SET status='Cancelled',
    cancelled_at=NOW(),
    cancelled_by='Client',
    seen=0
WHERE id=? AND status IN ('Pending','Confirmed')
");
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF'] . "#reservations");
    exit();
}
// ===============================
// Handle Agent Rating
// ===============================
if (isset($_POST['submit_agent_rating'])) {

    $reservation_id = (int)$_POST['reservation_id'];
    $rating         = (int)$_POST['rating'];
    $review         = trim($_POST['review'] ?? '');

    // Validate rating
    if ($rating < 1 || $rating > 5) {
        $message = "Please select a rating from 1 to 5 stars.";
    } else {

        // Make sure this reservation belongs to the logged-in customer
        $stmt = $conn->prepare("
            SELECT id, agent_id, status
            FROM reservations
            WHERE id = ?
            AND fullname = ?
            AND status = 'Done'
            LIMIT 1
        ");

        $stmt->bind_param("is", $reservation_id, $user_name);
        $stmt->execute();

        $reservation_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$reservation_data) {

            $message = "Invalid transaction or transaction is not completed.";

        } elseif (empty($reservation_data['agent_id'])) {

            $message = "No agent is assigned to this transaction.";

        } else {

            // Check if already rated
            $stmt = $conn->prepare("
                SELECT id
                FROM agent_ratings
                WHERE reservation_id = ?
                LIMIT 1
            ");

            $stmt->bind_param("i", $reservation_id);
            $stmt->execute();

            $already_rated = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($already_rated) {

                $message = "You have already rated this transaction.";

            } else {

                // Save rating
                $stmt = $conn->prepare("
                    INSERT INTO agent_ratings
                    (reservation_id, agent_id, customer_id, rating, review)
                    VALUES (?, ?, ?, ?, ?)
                ");

                $stmt->bind_param(
                    "iiiis",
                    $reservation_id,
                    $reservation_data['agent_id'],
                    $user_id,
                    $rating,
                    $review
                );

                if ($stmt->execute()) {
                    $stmt->close();

                    header("Location: " . $_SERVER['PHP_SELF'] . "#reservations&rated=1");
                    exit();

                } else {

                    $message = "Unable to save your rating.";
                    $stmt->close();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
   <script src="https://cdn.botpress.cloud/webchat/v3.6/inject.js"></script>
<script src="https://files.bpcontent.cloud/2026/05/13/12/20260513123611-N0BSRPKC.js" defer></script>
<title>My Account — ITPH</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Cormorant+Garamond:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/common.css">
<style>
/* ===================== VARIABLES ===================== */
:root {
    --gold:        #bfa158;
    --gold-dark:   #8c7a45;
    --gold-light:  #d4b97a;
    --cream:       #f6f6f0;
    --dark:        #1a1a2e;
    --green:       #0D2B1F;
    --lightgreen:  #0D2B1F;
    --text:        #3a3a50;
    --text-muted:  #7a7a8a;
    --white:       #ffffff;
    --sidebar-w:   260px;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Montserrat', sans-serif; color: var(--text); background: var(--cream); min-height: 100vh; }

/* ===================== TOP BAR ===================== */
.top-bar {
    background: rgba(255,255,255,0.92);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(191,161,88,0.15);
    padding: 7px 30px;
    font-size: 0.75rem;
    color: #666;
    display: flex;
    justify-content: space-between;
    align-items: center;
    letter-spacing: 0.04em;
    position: fixed;
    top: 0; width: 100%; z-index: 1050;
}
.top-bar .social a { margin-left: 12px; color: #666; transition: color .2s; }
.top-bar .social a:hover { color: var(--gold); }

/* ===================== NAVBAR ===================== */
.navbar {
    position: fixed;
    top: 30px; width: 100%; z-index: 1040;
    background: rgba(255,255,255,0.88);
    backdrop-filter: blur(14px);
    border-bottom: 1px solid rgba(191,161,88,0.12);
    padding: 0 30px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.nav-brand {
    font-family: 'Montserrat', sans-serif;
    font-weight: 700;
    font-size: 1.4rem;
    color: var(--gold);
    letter-spacing: 0.06em;
    text-decoration: none;
}
.nav-links { display: flex; align-items: center; gap: 28px; list-style: none; }
.nav-links a {
    color: var(--text);
    font-size: 0.78rem;
    font-weight: 400;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    text-decoration: none;
    transition: color .2s;
}
.nav-links a:hover { color: var(--gold); }

/* Profile icon in nav */
.nav-profile {
    display: flex;
    align-items: center;
    gap: 10px;
    position: relative;
}
.profile-avatar {
    width: 38px; height: 38px;
    border-radius: 50%;
    background: var(--gold);
    color: #fff;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.05em;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    border: 2px solid var(--gold-light);
    transition: transform .2s, box-shadow .2s;
    text-decoration: none;
}
.profile-avatar:hover { transform: scale(1.07); box-shadow: 0 4px 14px rgba(191,161,88,0.35); color: #fff; }

.notif-dot {
    position: absolute;
    top: -2px; right: -2px;
    width: 10px; height: 10px;
    background: #e74c3c;
    border-radius: 50%;
    border: 2px solid #fff;
}

/* ===================== PAGE LAYOUT ===================== */
.page-wrap {
    padding-top: 100px; /* top-bar + navbar */
    min-height: 100vh;
    display: flex;
}

/* ===================== SIDEBAR ===================== */
.sidebar {
    width: var(--sidebar-w);
    min-height: calc(100vh - 90px);
    background: var(--green);
    padding: 36px 0 24px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    position: sticky;
    top: 90px;
    height: calc(100vh - 90px);
}
.sidebar-avatar {
    width: 72px; height: 72px;
    border-radius: 50%;
    background: var(--gold);
    color: #fff;
    font-size: 1.5rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 14px;
    border: 3px solid rgba(191,161,88,0.4);
}
.sidebar-name {
    text-align: center;
    color: #fff;
    font-weight: 500;
    font-size: 0.9rem;
    margin-bottom: 4px;
    padding: 0 16px;
}
.sidebar-member {
    text-align: center;
    font-size: 0.68rem;
    color: rgba(255,255,255,0.35);
    letter-spacing: 0.12em;
    text-transform: uppercase;
    margin-bottom: 30px;
}
.sidebar-divider { height: 1px; background: rgba(255,255,255,0.07); margin: 0 24px 20px; }
.sidebar-nav { flex: 1; }
.sidebar-nav a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 28px;
    color: rgba(255,255,255,0.5);
    font-size: 0.8rem;
    font-weight: 400;
    letter-spacing: 0.06em;
    text-decoration: none;
    transition: color .2s, background .2s;
    text-transform: uppercase;
}
.sidebar-nav a i { font-size: 0.95rem; width: 18px; }
.sidebar-nav a:hover, .sidebar-nav a.active {
    color: #fff;
    background: rgba(191,161,88,0.1);
    border-left: 2px solid var(--gold);
    padding-left: 26px;
}
.sidebar-logout {
    margin: 0 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px 20px;
    border: 1px solid rgba(191,161,88,0.3);
    border-radius: 2px;
    color: var(--gold-light);
    font-size: 0.75rem;
    font-weight: 400;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    text-decoration: none;
    transition: all .3s;
}
.sidebar-logout:hover {
    background: rgba(191,161,88,0.12);
    border-color: var(--gold);
    color: var(--gold-light);
}

/* ===================== MAIN CONTENT ===================== */
.main-content {
    flex: 1;
    padding: 36px 40px;
    overflow-y: auto;
}

/* ===================== SECTION LABELS ===================== */
.page-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 2rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 4px;
}
.page-subtitle {
    font-size: 0.78rem;
    color: var(--text-muted);
    letter-spacing: 0.1em;
    text-transform: uppercase;
    margin-bottom: 32px;
}

/* ===================== CARDS ===================== */
.card-panel {
    background: var(--white);
    border-radius: 4px;
    border: 1px solid rgba(191,161,88,0.1);
    padding: 28px 32px;
    margin-bottom: 28px;
    box-shadow: 0 2px 16px rgba(0,0,0,0.04);
}
.card-panel-title {
    font-size: 0.7rem;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: var(--gold);
    font-weight: 500;
    margin-bottom: 22px;
    padding-bottom: 14px;
    border-bottom: 1px solid rgba(191,161,88,0.12);
    display: flex;
    align-items: center;
    gap: 8px;
}

/* ===================== FORM ===================== */
.form-label {
    font-size: 0.72rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--text-muted);
    font-weight: 500;
    margin-bottom: 6px;
}
.form-control, .form-select {
    font-family: 'Montserrat', sans-serif;
    font-size: 0.88rem;
    color: var(--text);
    border: 1px solid rgba(191,161,88,0.2);
    border-radius: 2px;
    padding: 10px 14px;
    background: var(--cream);
    transition: border-color .2s, box-shadow .2s;
}
.form-control:focus, .form-select:focus {
    outline: none;
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(191,161,88,0.1);
    background: var(--white);
}
.btn-save {
    background: var(--gold);
    color: #fff;
    border: none;
    padding: 11px 28px;
    font-family: 'Montserrat', sans-serif;
    font-size: 0.75rem;
    font-weight: 500;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    border-radius: 2px;
    cursor: pointer;
    transition: background .25s, transform .2s;
}
.btn-save:hover { background: var(--gold-dark); transform: translateY(-1px); }

/* ===================== RESERVATIONS TABLE ===================== */
.res-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.res-table thead th {
    font-size: 0.65rem;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: var(--text-muted);
    font-weight: 500;
    padding: 10px 16px;
    border-bottom: 1px solid rgba(191,161,88,0.15);
    text-align: left;
    white-space: nowrap;
}
.res-table tbody td {
    padding: 16px;
    border-bottom: 1px solid rgba(191,161,88,0.07);
    vertical-align: middle;
    color: var(--text);
}
.res-table tbody tr:last-child td { border-bottom: none; }
.res-table tbody tr { transition: background .15s; }
.res-table tbody tr:hover { background: rgba(246,246,240,0.8); }

/* Status badges */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.68rem;
    font-weight: 500;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
.status-pending    { background: rgba(255,193,7,0.12);  color: #a07800; }
.status-confirmed  { background: rgba(40,167,69,0.1);   color: #1a6f33; }
.status-done       { background: rgba(191,161,88,0.15); color: var(--gold-dark); }
.status-accepted   { background: rgba(13,110,253,0.1);  color: #0a4bb5; }

/* Agent info */
.agent-box {
    background: rgba(191,161,88,0.06);
    border: 1px solid rgba(191,161,88,0.18);
    border-radius: 4px;
    padding: 10px 14px;
    font-size: 0.8rem;
    color: var(--text);
    line-height: 1.6;
}
.agent-box strong { color: var(--dark); }
.congrats-box {
    background: rgba(40,167,69,0.07);
    border: 1px solid rgba(40,167,69,0.2);
    border-radius: 4px;
    padding: 10px 14px;
    font-size: 0.8rem;
    color: #1a6f33;
    font-weight: 500;
}

/* Mark seen / confirm buttons */
.btn-seen {
    background: transparent;
    border: 1px solid rgba(191,161,88,0.4);
    color: var(--gold);
    padding: 5px 14px;
    border-radius: 2px;
    font-size: 0.7rem;
    font-weight: 500;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    cursor: pointer;
    transition: all .2s;
    font-family: 'Montserrat', sans-serif;
}
.btn-seen:hover { background: var(--gold); color: #fff; }
.btn-confirm {
    background: var(--dark);
    border: none;
    color: #fff;
    padding: 7px 16px;
    border-radius: 2px;
    font-size: 0.7rem;
    font-weight: 500;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    cursor: pointer;
    transition: background .2s;
    font-family: 'Montserrat', sans-serif;
    margin-top: 8px;
}
.btn-confirm:hover { background: var(--gold-dark); }

/* Empty state */
.empty-state {
    text-align: center;
    padding: 50px 20px;
    color: var(--text-muted);
}
.empty-state i { font-size: 2.5rem; color: rgba(191,161,88,0.25); margin-bottom: 14px; display: block; }
.empty-state p { font-size: 0.88rem; margin-bottom: 18px; }
.empty-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--gold);
    font-size: 0.76rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    text-decoration: none;
    border-bottom: 1px solid var(--gold);
    padding-bottom: 2px;
    transition: gap .3s;
}
.empty-link:hover { gap: 14px; color: var(--gold-dark); }

/* Alert */
.alert-success-custom {
    background: rgba(40,167,69,0.08);
    border: 1px solid rgba(40,167,69,0.2);
    color: #1a6f33;
    padding: 12px 18px;
    border-radius: 2px;
    font-size: 0.84rem;
    margin-bottom: 22px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.alert-danger-custom {
    background: rgba(220,53,69,0.07);
    border: 1px solid rgba(220,53,69,0.2);
    color: #8b1c2a;
    padding: 12px 18px;
    border-radius: 2px;
    font-size: 0.84rem;
    margin-bottom: 22px;
}

/* ===================== STAT TILES ===================== */
.stat-tiles {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 28px;
}
.stat-tile {
    background: var(--white);
    border: 1px solid rgba(191,161,88,0.1);
    border-radius: 4px;
    padding: 20px 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.03);
}
.stat-tile-icon {
    width: 44px; height: 44px;
    border-radius: 50%;
    background: rgba(191,161,88,0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--gold);
    font-size: 1.1rem;
    flex-shrink: 0;
}
.stat-tile-num {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.8rem;
    font-weight: 600;
    color: var(--dark);
    line-height: 1;
}
.stat-tile-label {
    font-size: 0.67rem;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-top: 2px;
}

/* ===================== RESPONSIVE ===================== */
@media (max-width: 900px) {
    .sidebar { display: none; }
    .main-content { padding: 24px 20px; }
    .stat-tiles { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 600px) {
    .stat-tiles { grid-template-columns: 1fr; }
    .res-table thead { display: none; }
    .res-table td { display: block; padding: 8px 12px; }
    .res-table td::before { content: attr(data-label); font-size: 0.65rem; text-transform: uppercase; letter-spacing: .12em; color: var(--text-muted); display: block; margin-bottom: 2px; }
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

/* Body base - 1s transition */
body {
    transition: background 1s ease, color 1s ease, border-color 1s ease;
}

body * {
    transition: background 1s ease, color 1s ease, border-color 1s ease;
}

body.dark-mode {
    background: #121212;
    color: #e5e5e5;
}

/* Top Bar - 1s transition */
.top-bar {
    transition: background 1s ease, border-color 1s ease, color 1s ease;
}

body.dark-mode .top-bar {
    background: rgba(18,18,18,0.92);
    border-bottom: 1px solid rgba(255,255,255,0.08);
    color: #ddd;
}

body.dark-mode .top-bar .social a {
    color: #ddd;
}

/* Navbar - 1s transition */
.navbar {
    transition: top 0.4s ease, box-shadow 0.4s ease, background 1s ease, border-color 1s ease;
}

body.dark-mode .navbar {
    background: rgba(20,20,20,0.88);
    border-bottom: 1px solid rgba(255,255,255,0.06);
}

body.dark-mode .navbar .nav-link {
    color: #f1f1f1;
}

body.dark-mode .navbar .nav-link:hover,
body.dark-mode .navbar .nav-link.active-link {
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

/* Page Wrap */
body.dark-mode .page-wrap {
    background: #121212;
}

/* Sidebar */
body.dark-mode .sidebar {
    background: #0A0A14;
}

body.dark-mode .sidebar-divider {
    background: rgba(255,255,255,0.07);
}

/* Main Content */
body.dark-mode .main-content {
    background: #121212;
}

/* Page Title */
body.dark-mode .page-title {
    color: #fff;
}

body.dark-mode .page-subtitle {
    color: #999;
}

/* Cards */
body.dark-mode .card-panel {
    background: #1c1c1c;
    border-color: rgba(255,255,255,0.06);
    box-shadow: 0 2px 16px rgba(0,0,0,0.2);
}

body.dark-mode .card-panel-title {
    border-bottom-color: rgba(255,255,255,0.08);
}

/* Form */
body.dark-mode .form-label {
    color: #aaa;
}

body.dark-mode .form-control,
body.dark-mode .form-select {
    color: #e5e5e5;
    background: #2a2a2a;
    border-color: rgba(255,255,255,0.1);
}

body.dark-mode .form-control:focus,
body.dark-mode .form-select:focus {
    background: #2a2a2a;
    box-shadow: 0 0 0 3px rgba(191,161,88,0.15);
}

/* Table */
body.dark-mode .res-table thead th {
    color: #999;
    border-bottom-color: rgba(255,255,255,0.1);
}

body.dark-mode .res-table tbody td {
    color: #ccc;
    border-bottom-color: rgba(255,255,255,0.05);
}

body.dark-mode .res-table tbody tr:hover {
    background: rgba(255,255,255,0.03);
}

/* Status badges */
body.dark-mode .status-pending    { background: rgba(255,193,7,0.15);  color: #f0c040; }
body.dark-mode .status-confirmed  { background: rgba(40,167,69,0.15);  color: #4caf50; }
body.dark-mode .status-done       { background: rgba(191,161,88,0.2);  color: var(--gold-light); }
body.dark-mode .status-accepted   { background: rgba(13,110,253,0.15); color: #5b9aff; }

/* Agent box */
body.dark-mode .agent-box {
    background: rgba(191,161,88,0.08);
    border-color: rgba(191,161,88,0.2);
    color: #ccc;
}

body.dark-mode .agent-box strong {
    color: #fff;
}

body.dark-mode .congrats-box {
    background: rgba(40,167,69,0.1);
    border-color: rgba(40,167,69,0.25);
    color: #4caf50;
}

/* Buttons */
body.dark-mode .btn-seen {
    border-color: rgba(191,161,88,0.3);
    color: var(--gold-light);
}

body.dark-mode .btn-seen:hover {
    background: var(--gold);
    color: #fff;
}

body.dark-mode .btn-confirm {
    background: var(--gold-dark);
}

body.dark-mode .btn-confirm:hover {
    background: var(--gold);
}

/* Empty state */
body.dark-mode .empty-state {
    color: #888;
}

body.dark-mode .empty-state i {
    color: rgba(191,161,88,0.2);
}

/* Alerts */
body.dark-mode .alert-success-custom {
    background: rgba(40,167,69,0.1);
    border-color: rgba(40,167,69,0.25);
    color: #4caf50;
}

body.dark-mode .alert-danger-custom {
    background: rgba(220,53,69,0.1);
    border-color: rgba(220,53,69,0.25);
    color: #e57373;
}

/* Stat tiles */
body.dark-mode .stat-tile {
    background: #1c1c1c;
    border-color: rgba(255,255,255,0.06);
    box-shadow: 0 2px 12px rgba(0,0,0,0.2);
}

body.dark-mode .stat-tile-num {
    color: #fff;
}

body.dark-mode .stat-tile-label {
    color: #999;
}

/* Footer */
body.dark-mode .footer {
    background: #0A0A14;
}

body.dark-mode .footer-about-text,
body.dark-mode .footer a,
body.dark-mode .footer-contact span {
    color: #d4d4d4;
}
.status-cancelled {
    background: rgba(220,53,69,.12);
    color: #c82333;
}
body.dark-mode .status-cancelled {
    background: rgba(220,53,69,.18);
    color: #ff8b8b;
}
.btn-cancel{
    background:#dc3545;
    color:#fff;
    border:none;
    padding:7px 16px;
    border-radius:3px;
    cursor:pointer;
    font-size:.72rem;
    font-weight:500;
    letter-spacing:.08em;
    text-transform:uppercase;
    margin-top:8px;
    transition:.2s;
}

.btn-cancel:hover{
    background:#b52a37;
}
/* =========================
   AGENT DROPDOWN
========================= */

.agent-dropdown {
    margin-top: 10px;
    border: 1px solid rgba(191,161,88,0.20);
    border-radius: 5px;
    overflow: hidden;
    background: rgba(191,161,88,0.04);
}

.agent-dropdown summary {
    list-style: none;
    cursor: pointer;
    padding: 12px 15px;
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--gold-dark);
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: background .2s;
}

.agent-dropdown summary::-webkit-details-marker {
    display: none;
}

.agent-dropdown summary:hover {
    background: rgba(191,161,88,0.10);
}

.agent-dropdown summary::after {
    content: "\F282";
    font-family: "bootstrap-icons";
    font-size: 0.9rem;
    transition: transform .25s ease;
}

.agent-dropdown[open] summary::after {
    transform: rotate(180deg);
}

.agent-dropdown-content {
    padding: 15px;
    border-top: 1px solid rgba(191,161,88,0.15);
}

.agent-detail-row {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 13px;
    font-size: 0.8rem;
}

.agent-detail-row:last-child {
    margin-bottom: 0;
}

.agent-detail-icon {
    color: var(--gold);
    width: 20px;
    flex-shrink: 0;
}

.agent-detail-label {
    display: block;
    font-size: 0.62rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: .08em;
    margin-bottom: 2px;
}

.agent-detail-value {
    color: var(--text);
    font-weight: 500;
}

.agent-phone {
    color: var(--gold-dark);
    text-decoration: none;
    font-weight: 600;
}

.agent-phone:hover {
    color: var(--gold);
}

/* =========================
   STAR RATING
========================= */

.agent-rating {
    margin-top: 14px;
    padding-top: 14px;
    border-top: 1px solid rgba(191,161,88,0.15);
}

.rating-stars {
    display: flex;
    gap: 4px;
    margin: 7px 0;
}

.rating-stars i {
    color: #d6d6d6;
    font-size: 1.15rem;
}

.rating-stars i.active {
    color: var(--gold);
}

.rating-number {
    font-size: 0.78rem;
    color: var(--text-muted);
}

.rating-number strong {
    color: var(--gold-dark);
}

.rating-form {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid rgba(191,161,88,0.15);
}

.rating-form-title {
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: .12em;
    color: var(--gold-dark);
    font-weight: 600;
    margin-bottom: 10px;
}

.rating-select {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid rgba(191,161,88,0.25);
    border-radius: 3px;
    background: var(--cream);
    color: var(--text);
    font-family: 'Montserrat', sans-serif;
    font-size: .78rem;
    margin-bottom: 8px;
}

.rating-review {
    width: 100%;
    min-height: 75px;
    resize: vertical;
    padding: 9px 10px;
    border: 1px solid rgba(191,161,88,0.25);
    border-radius: 3px;
    background: var(--cream);
    color: var(--text);
    font-family: 'Montserrat', sans-serif;
    font-size: .78rem;
    margin-bottom: 8px;
}

.btn-rate {
    background: var(--gold);
    color: #fff;
    border: none;
    padding: 8px 15px;
    border-radius: 3px;
    font-size: .68rem;
    font-weight: 600;
    letter-spacing: .08em;
    text-transform: uppercase;
    cursor: pointer;
}

.btn-rate:hover {
    background: var(--gold-dark);
}

.rating-complete {
    margin-top: 12px;
    padding: 9px 12px;
    background: rgba(40,167,69,.08);
    border: 1px solid rgba(40,167,69,.20);
    border-radius: 3px;
    color: #1a6f33;
    font-size: .75rem;
}

.deal-complete {
    margin-top: 10px;
    padding: 11px 13px;
    background: rgba(40,167,69,.08);
    border: 1px solid rgba(40,167,69,.22);
    border-radius: 4px;
    color: #1a6f33;
    font-size: .78rem;
}

.deal-complete strong {
    font-weight: 700;
}

body.dark-mode .agent-dropdown {
    background: rgba(191,161,88,0.07);
    border-color: rgba(255,255,255,.08);
}

body.dark-mode .agent-dropdown summary {
    color: var(--gold-light);
}

body.dark-mode .agent-dropdown-content {
    border-top-color: rgba(255,255,255,.08);
}

body.dark-mode .agent-detail-value {
    color: #ddd;
}

body.dark-mode .rating-select,
body.dark-mode .rating-review {
    background: #2a2a2a;
    color: #eee;
    border-color: rgba(255,255,255,.1);
}

body.dark-mode .rating-complete,
body.dark-mode .deal-complete {
    color: #4caf50;
}
</style>
</head>
<body>

<!-- Top Bar -->
<div class="top-bar">
    <span>ITPH.com.ph &nbsp;|&nbsp; (+63) 9123456789</span>
    <span class="social">
        <a href="#"><i class="bi bi-facebook"></i></a>
        <a href="#"><i class="bi bi-instagram"></i></a>
        <a href="#"><i class="bi bi-tiktok"></i></a>
    </span>
</div>

<!-- Navbar -->
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


<!-- Page Wrap -->
<div class="page-wrap">

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-avatar"><?= htmlspecialchars($initials) ?></div>
        <div class="sidebar-name"><?= htmlspecialchars($user['fullname']) ?></div>
        <div class="sidebar-member">Member since <?= isset($user['created_at']) ? date('Y', strtotime($user['created_at'])) : '—' ?></div>
        <div class="sidebar-divider"></div>
        <nav class="sidebar-nav">
            <a href="#profile" class="active" onclick="showTab('profile',this)">
                <i class="bi bi-person"></i> Profile
            </a>
            <a href="#reservations" onclick="showTab('reservations',this)">
                <i class="bi bi-calendar-check"></i> Appointments
                <?php if ($unread_count > 0): ?>
                    <span style="margin-left:auto;background:#e74c3c;color:#fff;border-radius:20px;padding:2px 8px;font-size:0.65rem;"><?= $unread_count ?></span>
                <?php endif; ?>
            </a>
            <a href="#mortgage" onclick="showTab('mortgage',this)">
    <i class="bi bi-calculator"></i> Mortgage Calculator
</a>
        </nav>
        <div class="sidebar-divider"></div>
        <a href="log_out.php" class="sidebar-logout">
            <i class="bi bi-box-arrow-right"></i> Sign Out
        </a>
    </aside>

    <!-- Main -->
    <main class="main-content">

        <?php if (isset($_GET['updated'])): ?>
        <div class="alert-success-custom"><i class="bi bi-check-circle-fill"></i> Account updated successfully.</div>
        <?php endif; ?>
        <?php if (!empty($message)): ?>
        <div class="alert-danger-custom"><i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['rated'])): ?>
<div class="alert-success-custom">
    <i class="bi bi-star-fill"></i>
    Thank you! Your agent rating has been submitted.
</div>
<?php endif; ?>

        <!-- Stat Tiles -->
        <?php
            $total = count($reservations);
            $confirmed = count(array_filter($reservations, fn($r) => $r['status'] === 'Confirmed'));
            $done = count(array_filter($reservations, fn($r) => $r['status'] === 'Done'));
        ?>
        <div class="stat-tiles">
            <div class="stat-tile">
                <div class="stat-tile-icon"><i class="bi bi-calendar-check"></i></div>
                <div>
                    <div class="stat-tile-num"><?= $total ?></div>
                    <div class="stat-tile-label">Total Appointments</div>
                </div>
            </div>
            <div class="stat-tile">
                <div class="stat-tile-icon"><i class="bi bi-check2-circle"></i></div>
                <div>
                    <div class="stat-tile-num"><?= $confirmed ?></div>
                    <div class="stat-tile-label">Confirmed</div>
                </div>
            </div>
            <div class="stat-tile">
                <div class="stat-tile-icon"><i class="bi bi-house-heart"></i></div>
                <div>
                    <div class="stat-tile-num"><?= $done ?></div>
                    <div class="stat-tile-label">Completed</div>
                </div>
            </div>
        </div>

        <!-- ===== PROFILE TAB ===== -->
        <div id="tab-profile">
            <div class="page-title">My Profile</div>
            <div class="page-subtitle">Manage your personal information</div>

            <div class="card-panel">
                <div class="card-panel-title"><i class="bi bi-person-vcard"></i> Personal Details</div>
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="fullname" class="form-control" value="<?= htmlspecialchars($user['fullname'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Primary Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Secondary Email</label>
                            <input type="email" name="secondary_email" class="form-control" value="<?= htmlspecialchars($user['secondary_email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">Select</option>
                                <option value="Male"   <?= ($user['gender'] ?? '') === 'Male'   ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= ($user['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Location</label>
                            <select name="location" class="form-select" required>
                                <option value="">Select</option>
                                <option value="Northern Iloilo" <?= ($user['location'] ?? '') === 'Northern Iloilo' ? 'selected' : '' ?>>Northern Iloilo</option>
                                <option value="Southern Iloilo" <?= ($user['location'] ?? '') === 'Southern Iloilo' ? 'selected' : '' ?>>Southern Iloilo</option>
                                <option value="Central Iloilo"  <?= ($user['location'] ?? '') === 'Central Iloilo'  ? 'selected' : '' ?>>Central Iloilo</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Residency Status</label>
                            <select name="status" class="form-select">
                                <option value="">Select</option>
                                <option value="Local"     <?= ($user['status'] ?? '') === 'Local'     ? 'selected' : '' ?>>Local</option>
                                <option value="OFW"       <?= ($user['status'] ?? '') === 'OFW'       ? 'selected' : '' ?>>OFW</option>
                                <option value="Foreigner" <?= ($user['status'] ?? '') === 'Foreigner' ? 'selected' : '' ?>>Foreigner</option>
                            </select>
                        </div>
                        <div class="col-12 mt-2">
                            <button type="submit" name="update_account" class="btn-save">Save Changes</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-panel" style="padding: 20px 32px;">
                <div style="font-size:0.72rem;letter-spacing:0.12em;text-transform:uppercase;color:var(--text-muted);">
                    Member since &nbsp;·&nbsp; <?= isset($user['created_at']) ? date('F d, Y', strtotime($user['created_at'])) : 'N/A' ?>
                </div>
            </div>
        </div>

        <!-- ===== RESERVATIONS TAB ===== -->
        <div id="tab-reservations" style="display:none;">
            <div class="page-title">Appointments</div>
            <div class="page-subtitle">Track your property viewing bookings</div>
            

            <div class="card-panel" style="padding: 0; overflow: hidden;">
                <?php if (count($reservations) > 0): ?>
                <div style="overflow-x:auto;">
                <table class="res-table">
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($reservations as $r): ?>

<?php

$statusClass = match($r['status']) {

    'Pending' =>
        'status-pending',

    'Accepted' =>
        'status-accepted',

    'Confirmed' =>
        'status-confirmed',

    'Done' =>
        'status-done',

    'Cancelled' =>
        'status-cancelled',

    default =>
        'status-pending',
};


$agent =
    $r['agent_data'] ?? null;

$agentName =
    $agent['username'] ?? 'Not Assigned';

$agentPhone =
    $agent['phone'] ?? 'Not Available';

$meetingType =
    strtoupper(
        $r['meeting_type'] ?? 'NOT SET'
    );

$agentRating =
    (float)($r['agent_rating'] ?? 0);

$ratingCount =
    (int)($r['agent_rating_count'] ?? 0);

$customerRated =
    !empty($r['customer_rated']);

?>

<tr>

    <!-- PROPERTY -->
    <td data-label="Property">

        <strong>
            <?= htmlspecialchars($r['property']) ?>
        </strong>

    </td>


    <!-- DATE -->
    <td data-label="Date">

        <?= htmlspecialchars($r['date']) ?>

    </td>


    <!-- TIME -->
    <td data-label="Time">

        <?= htmlspecialchars(
            $r['time'] ?? '—'
        ) ?>

    </td>


    <!-- PHONE -->
    <td data-label="Phone">

        <?= htmlspecialchars(
            $r['phone'] ?? '—'
        ) ?>

    </td>


    <!-- STATUS -->
    <td data-label="Status">

        <span class="status-badge <?= $statusClass ?>">

            <?php if ($r['status'] === 'Done'): ?>

                <i class="bi bi-check-circle-fill"></i>

            <?php elseif ($r['status'] === 'Confirmed'): ?>

                <i class="bi bi-calendar-check"></i>

            <?php elseif ($r['status'] === 'Cancelled'): ?>

                <i class="bi bi-x-circle-fill"></i>

            <?php endif; ?>

            <?= htmlspecialchars($r['status']) ?>

        </span>

    </td>


    <!-- ACTIONS -->
    <td data-label="Actions">


        <!-- ==================================
             MARK AS SEEN
        =================================== -->

        <?php if (
            in_array(
                $r['status'],
                ['Confirmed', 'Done']
            )
            && !$r['seen']
        ): ?>

            <form method="POST"
                  style="display:inline;">

                <input
                    type="hidden"
                    name="seen_id"
                    value="<?= (int)$r['id'] ?>"
                >

                <button
                    type="submit"
                    class="btn-seen">

                    Mark Seen

                </button>

            </form>

        <?php elseif ($r['seen']): ?>

            <span style="
                font-size:.72rem;
                color:var(--text-muted);
                letter-spacing:.08em;
            ">

                Seen ✓

            </span>

        <?php endif; ?>


        <!-- ==================================
             AGENT INFORMATION DROPDOWN
        =================================== -->

        <?php if (
            !empty($r['agent_id'])
            && $r['status'] !== 'Cancelled'
        ): ?>

            <details class="agent-dropdown">

                <summary>

                    <span>
                        <i class="bi bi-person-badge"></i>
                        Agent Information
                    </span>

                </summary>


                <div class="agent-dropdown-content">


                    <!-- AGENT NAME -->
                    <div class="agent-detail-row">

                        <div class="agent-detail-icon">

                            <i class="bi bi-person-fill"></i>

                        </div>

                        <div>

                            <span class="agent-detail-label">
                                Agent
                            </span>

                            <span class="agent-detail-value">

                                <?= htmlspecialchars(
                                    $agentName
                                ) ?>

                            </span>

                        </div>

                    </div>


                    <!-- PHONE -->
                    <div class="agent-detail-row">

                        <div class="agent-detail-icon">

                            <i class="bi bi-telephone-fill"></i>

                        </div>

                        <div>

                            <span class="agent-detail-label">
                                Contact Number
                            </span>

                            <?php if (
                                $agentPhone !==
                                'Not Available'
                            ): ?>

                                <a
                                    href="tel:<?= htmlspecialchars($agentPhone) ?>"
                                    class="agent-phone">

                                    <?= htmlspecialchars(
                                        $agentPhone
                                    ) ?>

                                </a>

                            <?php else: ?>

                                <span class="agent-detail-value">
                                    Not Available
                                </span>

                            <?php endif; ?>

                        </div>

                    </div>


                    <!-- MEETING TYPE -->
                    <div class="agent-detail-row">

                        <div class="agent-detail-icon">

                            <i class="bi bi-calendar-event"></i>

                        </div>

                        <div>

                            <span class="agent-detail-label">
                                Meeting Type
                            </span>

                            <span class="agent-detail-value">

                                <?= htmlspecialchars(
                                    $meetingType
                                ) ?>

                            </span>

                        </div>

                    </div>


                    <!-- ==================================
                         AGENT RATING
                    =================================== -->

                    <div class="agent-rating">

                        <span class="agent-detail-label">

                            <i class="bi bi-star-fill"></i>
                            Agent Rating

                        </span>


                        <div class="rating-stars">

                            <?php for (
                                $star = 1;
                                $star <= 5;
                                $star++
                            ): ?>

                                <i class="
                                    bi
                                    <?= $star <= round($agentRating)
                                        ? 'bi-star-fill active'
                                        : 'bi-star'
                                    ?>
                                "></i>

                            <?php endfor; ?>

                        </div>


                        <?php if ($ratingCount > 0): ?>

                            <div class="rating-number">

                                <strong>
                                    <?= number_format(
                                        $agentRating,
                                        1
                                    ) ?>
                                </strong>

                                / 5

                                ·

                                <?= $ratingCount ?>

                                rating<?= $ratingCount != 1 ? 's' : '' ?>

                            </div>

                        <?php else: ?>

                            <div class="rating-number">

                                No ratings yet.

                            </div>

                        <?php endif; ?>


                        <!-- ==================================
                             DEAL COMPLETE
                        =================================== -->

                        <?php if (
                            $r['status'] === 'Done'
                        ): ?>

                            <div class="deal-complete">

                                <i class="bi bi-check-circle-fill"></i>

                                <strong>
                                    Deal Completed
                                </strong>

                                <br>

                                Congratulations! Your property
                                transaction has been completed.

                            </div>


                            <!-- ==================================
                                 CUSTOMER RATING FORM
                            =================================== -->

                            <?php if (!$customerRated): ?>

                                <div class="rating-form">

                                    <div class="rating-form-title">

                                        <i class="bi bi-star"></i>

                                        Rate Your Agent

                                    </div>


                                    <form method="POST">

                                        <input
                                            type="hidden"
                                            name="reservation_id"
                                            value="<?= (int)$r['id'] ?>"
                                        >


                                        <select
                                            name="rating"
                                            class="rating-select"
                                            required
                                        >

                                            <option value="">
                                                Select Rating
                                            </option>

                                            <option value="5">
                                                ★★★★★ — Excellent
                                            </option>

                                            <option value="4">
                                                ★★★★☆ — Very Good
                                            </option>

                                            <option value="3">
                                                ★★★☆☆ — Good
                                            </option>

                                            <option value="2">
                                                ★★☆☆☆ — Fair
                                            </option>

                                            <option value="1">
                                                ★☆☆☆☆ — Poor
                                            </option>

                                        </select>


                                        <textarea
                                            name="review"
                                            class="rating-review"
                                            placeholder="Write a short review about your agent (optional)..."
                                        ></textarea>


                                        <button
                                            type="submit"
                                            name="submit_agent_rating"
                                            class="btn-rate"
                                        >

                                            <i class="bi bi-star-fill"></i>

                                            Submit Rating

                                        </button>

                                    </form>

                                </div>

                            <?php else: ?>

                                <div class="rating-complete">

                                    <i class="bi bi-check-circle-fill"></i>

                                    You have already rated this agent.

                                </div>

                            <?php endif; ?>

                        <?php endif; ?>

                    </div>

                </div>

            </details>

        <?php endif; ?>


        <!-- ==================================
             CONFIRM BOOKING
        =================================== -->

        <?php if (
            $r['status'] === 'Pending'
        ): ?>

            <form
                method="POST"
                style="margin-top:8px;"
            >

                <input
                    type="hidden"
                    name="confirm_booking"
                    value="<?= (int)$r['id'] ?>"
                >

                <button
                    type="submit"
                    class="btn-confirm"
                >

                    Confirm Booking

                </button>

            </form>

        <?php endif; ?>


        <!-- ==================================
             CANCEL APPOINTMENT
        =================================== -->

        <?php if (
            in_array(
                $r['status'],
                ['Pending', 'Confirmed']
            )
        ): ?>

            <form
                method="POST"
                style="display:inline;"
                onsubmit="
                    return confirm(
                        'Are you sure you want to cancel this appointment?'
                    );
                "
            >

                <input
                    type="hidden"
                    name="cancel_booking"
                    value="<?= (int)$r['id'] ?>"
                >

                <button
                    type="submit"
                    class="btn-cancel"
                >

                    <i class="bi bi-x-circle"></i>

                    Cancel Appointment

                </button>

            </form>

        <?php endif; ?>


        <!-- ==================================
             CANCELLED
        =================================== -->

        <?php if (
            $r['status'] === 'Cancelled'
        ): ?>

            <div style="
                margin-top:8px;
                font-size:.72rem;
                color:#c82333;
            ">

                <i class="bi bi-info-circle"></i>

                This appointment was cancelled.

            </div>

        <?php endif; ?>


    </td>

</tr>

<?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-calendar-x"></i>
                        <p>You haven't made any Appointments yet.</p>
                        <a href="all_properties.php" class="empty-link">Check Properties <i class="bi bi-arrow-right"></i></a>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
        <div id="tab-mortgage" style="display:none;">

    <div class="page-title">Mortgage Calculator</div>
    <div class="page-subtitle">
        Estimate your monthly housing loan payment
    </div>

    <div class="card-panel">

        <form id="mortgageForm">

            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">Property Price (₱)</label>
                    <input
                        type="number"
                        id="price"
                        class="form-control"
                        placeholder="2500000"
                        required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Down Payment (₱)</label>
                    <input
                        type="number"
                        id="down"
                        class="form-control"
                        placeholder="500000"
                        required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Annual Interest Rate (%)</label>
                    <input
                        type="number"
                        id="interest"
                        value="6.5"
                        step="0.01"
                        class="form-control"
                        required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Loan Term</label>
                    <select id="years" class="form-select">
                        <option value="5">5 Years</option>
                        <option value="10">10 Years</option>
                        <option value="15">15 Years</option>
                        <option value="20" selected>20 Years</option>
                        <option value="25">25 Years</option>
                        <option value="30">30 Years</option>
                    </select>
                </div>

                <div class="col-12">
                    <button
                        type="button"
                        class="btn-save"
                        onclick="calculateMortgage()">
                        Calculate
                    </button>
                </div>

            </div>

        </form>

        <hr>

        <div id="loanResult" style="display:none;">

            <h5 style="color:var(--gold-dark);margin-bottom:20px;">
                Loan Summary
            </h5>

            <div class="row">

                <div class="col-md-3">
                    <strong>Loan Amount</strong><br>
                    <span id="loanAmount"></span>
                </div>

                <div class="col-md-3">
                    <strong>Monthly Payment</strong><br>
                    <span id="monthlyPayment"></span>
                </div>

                <div class="col-md-3">
                    <strong>Total Interest</strong><br>
                    <span id="totalInterest"></span>
                </div>

                <div class="col-md-3">
                    <strong>Total Payment</strong><br>
                    <span id="totalPayment"></span>
                </div>

            </div>

        </div>

    </div>

</div>

    </main>
    
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>

    // ==========================================
// MORTGAGE CALCULATOR
// ==========================================

function formatPeso(value) {

    return "₱" + Number(value).toLocaleString(
        "en-PH",
        {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }
    );

}


function calculateMortgage() {

    const price =
        parseFloat(
            document.getElementById("price").value
        );

    const down =
        parseFloat(
            document.getElementById("down").value
        );

    const rate =
        parseFloat(
            document.getElementById("interest").value
        );

    const years =
        parseInt(
            document.getElementById("years").value
        );


    // Validation
    if (
        isNaN(price) ||
        isNaN(down) ||
        isNaN(rate) ||
        isNaN(years)
    ) {

        alert(
            "Please complete all fields."
        );

        return;

    }


    if (price <= 0) {

        alert(
            "Property price must be greater than zero."
        );

        return;

    }


    if (down < 0) {

        alert(
            "Down payment cannot be negative."
        );

        return;

    }


    if (down >= price) {

        alert(
            "Down payment must be less than the property price."
        );

        return;

    }


    if (rate < 0) {

        alert(
            "Interest rate cannot be negative."
        );

        return;

    }


    const loan =
        price - down;


    const monthlyRate =
        rate / 100 / 12;


    const payments =
        years * 12;


    let monthly;


    // Zero-interest loan
    if (monthlyRate === 0) {

        monthly =
            loan / payments;

    } else {

        monthly =
            loan *
            monthlyRate *
            Math.pow(
                1 + monthlyRate,
                payments
            ) /
            (
                Math.pow(
                    1 + monthlyRate,
                    payments
                ) - 1
            );

    }


    const totalPayment =
        monthly * payments;


    const totalInterest =
        totalPayment - loan;


    document.getElementById(
        "loanAmount"
    ).textContent =
        formatPeso(loan);


    document.getElementById(
        "monthlyPayment"
    ).textContent =
        formatPeso(monthly);


    document.getElementById(
        "totalInterest"
    ).textContent =
        formatPeso(totalInterest);


    document.getElementById(
        "totalPayment"
    ).textContent =
        formatPeso(totalPayment);


    document.getElementById(
        "loanResult"
    ).style.display = "block";

}
// ==========================================
// TAB SWITCHER
// ==========================================

function showTab(tabId, link = null) {

    // Hide every tab
    document.querySelectorAll("[id^='tab-']").forEach(function(tab) {
        tab.style.display = "none";
    });


    // Show selected tab
    const selectedTab =
        document.getElementById("tab-" + tabId);

    if (selectedTab) {
        selectedTab.style.display = "block";
    }


    // Remove active from sidebar
    document.querySelectorAll(".sidebar-nav a").forEach(function(a) {
        a.classList.remove("active");
    });


    // Add active class
    if (link) {

        link.classList.add("active");

    } else {

        const activeLink =
            document.querySelector(
                '.sidebar-nav a[href="#' + tabId + '"]'
            );

        if (activeLink) {
            activeLink.classList.add("active");
        }

    }


    // Update URL hash
    history.replaceState(
        null,
        "",
        "#" + tabId
    );
}


// ==========================================
// OPEN TAB FROM URL HASH
// ==========================================

function openTabFromHash() {

    let hash =
        window.location.hash.replace("#", "");


    // Default tab
    if (
        hash !== "profile" &&
        hash !== "reservations" &&
        hash !== "mortgage"
    ) {

        hash = "profile";

    }


    showTab(hash);
}


// Run when page loads
document.addEventListener(
    "DOMContentLoaded",
    function() {

        openTabFromHash();

    }
);


// Handle browser back/forward
window.addEventListener(
    "hashchange",
    function() {

        openTabFromHash();

    }
);

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