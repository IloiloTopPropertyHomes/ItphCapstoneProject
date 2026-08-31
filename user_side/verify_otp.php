<?php
session_start();
require_once '../backends/config.php';

$message = "";
$message_type = "";
$otp_verified = false;

if (!isset($_SESSION['otp_code'])) {
    header("Location: login.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// VERIFY OTP
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_otp'])) {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF failed");
    }

    $otp_input = implode('', $_POST['otp'] ?? []);

    if (!isset($_SESSION['otp_code'])) {
        $message = "OTP session expired.";
        $message_type = "error";
    }
    elseif (time() > $_SESSION['otp_expires']) {
        $message = "OTP expired.";
        $message_type = "error";
    }
    elseif ($otp_input != $_SESSION['otp_code']) {
        $message = "Invalid OTP.";
        $message_type = "error";
    }
    else {
        $_SESSION['otp_verified'] = true;
        $otp_verified = true;
        $message = "Successfully Verified!";
        $message_type = "success";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Verify OTP</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body {
    margin:0;
    font-family: 'Montserrat', sans-serif;
    background: url('../photo/nbg.jpg') center/cover no-repeat;
}

.overlay {
    position: absolute;
    width:100%;
    height:100%;
    background: rgba(0,0,0,0.6);
}

.wrapper {
    position: relative;
    z-index: 2;
    height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
}

.card-box {
    background:white;
    padding:40px;
    border-radius:15px;
    width:100%;
    max-width:420px;
    text-align:center;
    box-shadow:0 10px 30px rgba(0,0,0,0.3);
}

.title {
    font-weight:800;
    margin-bottom:10px;
}

.subtitle {
    font-size:13px;
    color:#666;
    margin-bottom:25px;
}

.alert {
    font-size:14px;
    border:none;
}

.otp-boxes {
    display:flex;
    justify-content:space-between;
    gap:10px;
    margin-bottom:25px;
}

.otp-boxes input {
    width:45px;
    height:50px;
    text-align:center;
    font-size:22px;
    font-weight:700;
    border:2px solid #ddd;
    border-radius:10px;
    outline:none;
    transition:0.2s;
}

.otp-boxes input:focus {
    border-color:#bfa158;
    transform:scale(1.05);
}

.btn-gold {
    width:100%;
    background:#bfa158;
    color:white;
    border:none;
    padding:12px;
    font-weight:700;
    border-radius:10px;
    margin-top:10px;
}

.btn-gold:hover {
    background:#a88d45;
}

.success-box {
    background:#e8f5e9;
    color:#1b5e20;
    padding:15px;
    border-radius:10px;
    margin-bottom:15px;
    font-weight:600;
}

.btn-check {
    background:#28a745;
    color:white;
    border:none;
    padding:12px;
    width:100%;
    border-radius:10px;
    font-weight:700;
}

.btn-check:hover {
    background:#218838;
}

</style>
</head>
<body>

<div class="overlay"></div>

<div class="wrapper">
<div class="card-box">

    <h3 class="title">Verify OTP</h3>
    <p class="subtitle">Enter the 6-digit code sent to your email</p>

    <?php if($message): ?>
        <div class="alert alert-<?= $message_type == 'success' ? 'success' : 'danger'; ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <?php if(!$otp_verified): ?>

    <form method="POST">

        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="otp-boxes">
            <?php for($i=0; $i<6; $i++): ?>
                <input type="text" name="otp[]" maxlength="1" required>
            <?php endfor; ?>
        </div>

        <button type="submit" name="verify_otp" class="btn-gold">
            Verify OTP
        </button>

    </form>

    <?php else: ?>

        <div class="success-box">
            ✔ Successfully Verified
        </div>

        <form action="properties.php" method="GET">
            <button class="btn-check">
                Check Properties
            </button>
        </form>

    <?php endif; ?>

</div>
</div>

<script>
// auto move OTP input
const inputs = document.querySelectorAll('input[name="otp[]"]');

inputs.forEach((input, index) => {
    input.addEventListener('input', () => {
        if (input.value && index < inputs.length - 1) {
            inputs[index + 1].focus();
        }
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === "Backspace" && !input.value && index > 0) {
            inputs[index - 1].focus();
        }
    });
});
</script>

</body>
</html>