<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://www.gstatic.com https://cdn.botpress.cloud https://files.bpcontent.cloud; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; img-src 'self' data: https:; connect-src 'self' https://cdn.botpress.cloud https://files.bpcontent.cloud wss://*.botpress.cloud https://*.botpress.cloud; frame-src https://cdn.botpress.cloud; frame-ancestors 'self'; base-uri 'self';");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: no-referrer");

session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'cookie_samesite' => 'Strict'
]);
require_once '../backends/config.php';
$conn = get_db_connection();

if (isset($_SESSION['user_id'])) {

    $id = $_SESSION['user_id'];

    $stmt = $conn->prepare("
        UPDATE auth_logs
        SET
            last_activity = NOW(),
            session_status = 'online'
        WHERE
            user_id = ?
            AND role = 'customer'
    ");

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}
require_once __DIR__ . '/../vendor/autoload.php';



require_once __DIR__ . '/google_config.php';


$client->addScope("email");
$client->addScope("profile");
require_once '../backends/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
$conn = get_db_connection();

$message = "";
$ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

// ==================== CSRF ====================
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ==================== LOGIN ATTEMPTS ====================
if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
$show_forgot_password = $_SESSION['login_attempts'] >= 5;

// ==================== SEND OTP ====================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_otp'])) {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF failed");
    }

    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);

    $_SESSION['temp_email'] = $email;

    $stmt = $conn->prepare("SELECT id, fullname, email FROM customers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    $customer = $result->fetch_assoc();

    if ($customer) {

        $otp = random_int(100000, 999999);

        $_SESSION['otp_code'] = $otp;
        $_SESSION['otp_expires'] = time() + 300;
        $_SESSION['otp_sent'] = true;

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'itph934@gmail.com';
            $mail->Password = 'ultx hrmp btdi jzpo';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('itph934@gmail.com', 'ITPH OTP');
            $mail->addAddress($email, $customer['fullname']);

            $mail->isHTML(true);
            $mail->Subject = "OTP Code";
            $mail->Body = "<h3>Hello {$customer['fullname']}</h3><h2>$otp</h2>";

            $mail->send();

            $message = "OTP sent successfully.";

        } catch (Exception $e) {
            $message = "Email failed.";
        }

    } else {
        $message = "Email not found.";
    }
}

// ==================== VERIFY OTP ====================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_otp'])) {

    $otp_input = trim($_POST['otp']);

    if (!isset($_SESSION['otp_code'])) {
        $message = "Send OTP first.";
    }
    elseif (time() > $_SESSION['otp_expires']) {
        $message = "OTP expired.";
        unset($_SESSION['otp_code']);
    }
    elseif ($otp_input != $_SESSION['otp_code']) {
        $message = "Invalid OTP.";
    }
    else {
        $_SESSION['otp_verified'] = true;
        $message = "OTP verified.";
    }
}

// ==================== LOGIN ====================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF failed");
    }

    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = trim($_POST['password']);

    if (!isset($_SESSION['otp_verified'])) {
        $message = "Verify OTP first.";
    }
    else {

        $stmt = $conn->prepare("SELECT * FROM customers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        $customer = $result->fetch_assoc();

        if ($customer && password_verify($password, $customer['password'])) {

            session_regenerate_id(true);

            $_SESSION['user_id'] = $customer['id'];
            $_SESSION['fullname'] = $customer['fullname'];
            $_SESSION['email'] = $customer['email'];

            
$log = $conn->prepare("
INSERT INTO auth_logs
(user_id, role, fullname, email, login_status, login_method, session_status, ip_address, user_agent, last_activity)
VALUES (?, 'customer', ?, ?, 'success', 'email', 'online', ?, ?, NOW())
");

$log->bind_param(
    "issss",
    $customer['id'],
    $customer['fullname'],
    $customer['email'],
    $ip,
    $userAgent
);

$log->execute();
$log->close();

            $message = "Invalid credentials.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - ITPH</title>
   <script src="https://cdn.botpress.cloud/webchat/v3.6/inject.js"></script>
<script src="https://files.bpcontent.cloud/2026/05/13/12/20260513123611-N0BSRPKC.js" defer></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap');
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Montserrat', sans-serif;
    background: linear-gradient(rgba(0,0,0,0.65), rgba(0,0,0,0.65)),
                url('../photo/nbg.jpg') center/cover no-repeat fixed;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 24px;
}
.auth-wrap {
    width: 100%;
    max-width: 440px;
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 24px 60px rgba(0,0,0,0.45);
}
.card-top {
    background: #1a1a2e;
    padding: 32px 32px 28px;
    text-align: center;
}
.brand {
    font-size: 26px;
    font-weight: 800;
    color: #bfa158;
    letter-spacing: 0.08em;
    margin-bottom: 6px;
}
.card-top p {
    font-size: 10px;
    color: rgba(255,255,255,0.4);
    letter-spacing: 0.2em;
    text-transform: uppercase;
}
.steps {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 24px;
}
.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    flex: 1;
}
.step-circle {
    width: 30px; height: 30px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700;
    border: 1.5px solid rgba(191,161,88,0.35);
    color: rgba(191,161,88,0.45);
}
.step-circle.done { background: #bfa158; border-color: #bfa158; color: #fff; }
.step-label {
    font-size: 9px;
    color: rgba(255,255,255,0.3);
    letter-spacing: 0.1em;
    text-transform: uppercase;
    text-align: center;
}
.step-label.done { color: rgba(191,161,88,0.85); }
.step-line {
    flex: 1; height: 1px;
    background: rgba(191,161,88,0.18);
    margin-bottom: 20px;
    max-width: 44px;
}
.card-body { padding: 28px 32px 32px; }
.section-tag {
    font-size: 9px;
    font-weight: 700;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: #bfa158;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.section-tag::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #f0efe9;
}
.field-group { margin-bottom: 20px; }
.form-label {
    display: block;
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    color: #7a7a8a;
    margin-bottom: 7px;
}
.field-row { display: flex; gap: 8px; align-items: stretch; }
.form-control {
    width: 100%;
    padding: 11px 14px;
    border: 1.5px solid #e8e5dc;
    border-radius: 8px;
    font-family: 'Montserrat', sans-serif;
    font-size: 14px;
    color: #1a1a2e;
    background: #fafaf7;
    outline: none;
    transition: border-color .2s, background .2s;
    margin-bottom: 0;
}
.form-control:focus { border-color: #bfa158; background: #fff; box-shadow: 0 0 0 3px rgba(191,161,88,0.1); }
.form-control:disabled { background: #f0efe9; color: #aaa; cursor: not-allowed; }
.btn-gold {
    background: #bfa158;
    border: none; color: #fff;
    padding: 11px 18px;
    border-radius: 8px;
    font-family: 'Montserrat', sans-serif;
    font-size: 11px; font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    cursor: pointer;
    transition: background .2s;
    white-space: nowrap;
}
.btn-gold:hover { background: #a88d45; }
.btn-gold.full { width: 100%; padding: 13px; font-size: 13px; margin-top: 4px; }
.btn-outline {
    background: transparent;
    border: 1.5px solid #e8e5dc;
    color: #1a1a2e;
    padding: 11px 18px;
    border-radius: 8px;
    font-family: 'Montserrat', sans-serif;
    font-size: 11px; font-weight: 700;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    cursor: pointer;
    transition: all .2s;
    white-space: nowrap;
}
.btn-outline:hover { border-color: #bfa158; color: #bfa158; }
.divider { height: 1px; background: #f0efe9; margin: 22px 0; }
.hint { font-size: 12px; color: #aaa; text-align: center; margin-top: 18px; }
.hint a { color: #bfa158; text-decoration: none; font-weight: 600; }
.hint a:hover { text-decoration: underline; }
.alert { padding: 10px 14px; border-radius: 8px; font-size: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
.alert-info { background: #fdf3e0; border: 1px solid #f0d99a; color: #7a5c00; }
.alert-danger { background: #fdf0f0; border: 1px solid #f5c6c6; color: #7a1a1a; }
.alert-success { background: #f0f9f0; border: 1px solid #b8e0b8; color: #1a5c1a; }
.btn-google{

display:flex;

align-items:center;

justify-content:center;

gap:12px;

width:100%;

margin-top:14px;

padding:13px;

border:1.5px solid #e8e5dc;

border-radius:8px;

background:#fff;

font-family:'Montserrat',sans-serif;

font-weight:700;

font-size:13px;

color:#1a1a2e;

text-decoration:none;

transition:.25s;

}

.btn-google:hover{

border-color:#bfa158;

background:#fafaf7;

color:#1a1a2e;

}

.btn-google img{

width:20px;

height:20px;

}
</style>

</head>
<body>
<div class="auth-wrap">

  <div class="card-top">
    <div class="brand">ITPH</div>
    <p>Secure sign-in with OTP verification</p>
    <div class="steps">
      <div class="step">
        <div class="step-circle <?= isset($_SESSION['otp_sent']) ? 'done' : '' ?>">1</div>
        <div class="step-label <?= isset($_SESSION['otp_sent']) ? 'done' : '' ?>">Send OTP</div>
      </div>
      <div class="step-line"></div>
      <div class="step">
        <div class="step-circle <?= isset($_SESSION['otp_verified']) ? 'done' : '' ?>">2</div>
        <div class="step-label <?= isset($_SESSION['otp_verified']) ? 'done' : '' ?>">Verify</div>
      </div>
      <div class="step-line"></div>
      <div class="step">
        <div class="step-circle">3</div>
        <div class="step-label">Sign in</div>
        
      </div>
    </div>
  </div>

  <div class="card-body">

    <?php if ($message): ?>
    <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

      <div class="section-tag">Step 1 — Send OTP</div>

      <div class="field-group">
        <label class="form-label">Email address</label>
        <div class="field-row">
          <input type="email" name="email" class="form-control"
            placeholder="you@example.com"
            value="<?= htmlspecialchars($_SESSION['temp_email'] ?? '') ?>"
            required style="flex:1">
          <button type="submit" name="send_otp" class="btn-gold">Send OTP</button>
        </div>
      </div>

      <div class="section-tag">Step 2 — Verify OTP</div>

      <div class="field-group">
        <label class="form-label">One-time password</label>
        <div class="field-row">
          <input type="text" name="otp" class="form-control"
            placeholder="6-digit code"
            value="<?= htmlspecialchars($_POST['otp'] ?? '') ?>"
            style="flex:1;letter-spacing:0.18em">
          <button type="submit" name="verify_otp" class="btn-outline">Verify</button>
        </div>
      </div>

      <div class="divider"></div>

      <div class="section-tag">Step 3 — Sign in</div>

      <div class="field-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control"
          placeholder="Enter your password"
          <?= isset($_SESSION['otp_verified']) ? '' : 'disabled' ?>>
      </div>

      <button type="submit" name="login" class="btn-gold full">Sign In</button>
     <a href="<?= htmlspecialchars($client->createAuthUrl()) ?>" class="btn-google">
    <img src="https://developers.google.com/identity/images/g-logo.png" alt="">
    Continue with Google
</a>

    </form>

    <p class="hint">No account? <a href="register.php">Register here</a></p>

  </div>
</div>
</body>
<script src="https://cdn.botpress.cloud/webchat/v3.6/inject.js"></script>
<script src="https://files.bpcontent.cloud/2026/05/16/02/20260516020411-RS0TP9AJ.js" defer></script>
</html>