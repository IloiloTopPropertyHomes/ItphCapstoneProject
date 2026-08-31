<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://www.gstatic.com https://cdn.botpress.cloud https://files.bpcontent.cloud; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; img-src 'self' data: https:; connect-src 'self' https://cdn.botpress.cloud https://files.bpcontent.cloud wss://*.botpress.cloud https://*.botpress.cloud; frame-src https://cdn.botpress.cloud; frame-ancestors 'self'; base-uri 'self';");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: no-referrer-when-downgrade");

session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']),
    'cookie_samesite' => 'Strict'
]);

require_once '../backends/config.php';

$conn = get_db_connection();

$message = "";
$message_type = "";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        die("Invalid CSRF token.");
    }

    $fullname = htmlspecialchars(trim($_POST['fullname'] ?? ''));
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    $gender = htmlspecialchars(trim($_POST['gender'] ?? ''));
    $location = htmlspecialchars(trim($_POST['location'] ?? ''));
    $status = htmlspecialchars(trim($_POST['status'] ?? ''));

    $check_stmt = $conn->prepare("SELECT id FROM customers WHERE email = ?");
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        $message = "Email is already registered.";
        $message_type = "error";
    } elseif (
        empty($fullname) ||
        empty($gender) ||
        empty($location) ||
        empty($status) ||
        !$email
    ) {
        $message = "Please fill up all fields correctly.";
        $message_type = "error";
    } elseif (empty($password) || empty($confirm_password)) {
        $message = "Password fields are required.";
        $message_type = "error";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
        $message_type = "error";
    } elseif (
        !preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&]).{8,}$/', $password)
    ) {
        $message = "Password does not meet the required credentials.";
        $message_type = "error";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            INSERT INTO customers
            (fullname, email, password, gender, location, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssssss",
            $fullname,
            $email,
            $hashed_password,
            $gender,
            $location,
            $status
        );

        if ($stmt->execute()) {
            $message = "Registration successful! You can now login.";
            $message_type = "success";
            header("refresh:2;url=login.php");
        } else {
            $message = "Registration failed.";
            $message_type = "error";
        }

        $stmt->close();
    }

    $check_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - ITPH</title>
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
.form-control, .form-select {
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
    appearance: auto;
}
.form-control:focus, .form-select:focus {
    border-color: #bfa158;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(191,161,88,0.1);
}
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
.divider { height: 1px; background: #f0efe9; margin: 22px 0; }
.hint { font-size: 12px; color: #aaa; text-align: center; margin-top: 18px; }
.hint a { color: #bfa158; text-decoration: none; font-weight: 600; }
.hint a:hover { text-decoration: underline; }
.alert {
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.alert-success { background: #f0f9f0; border: 1px solid #b8e0b8; color: #1a5c1a; }
.alert-error { background: #fdf0f0; border: 1px solid #f5c6c6; color: #7a1a1a; }
.password-wrapper { position: relative; }
.toggle-password {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    border: none;
    background: none;
    color: #aaa;
    cursor: pointer;
    font-size: 14px;
    padding: 0;
}
.toggle-password:hover { color: #bfa158; }
.password-hint {
    display: block;
    margin-top: 6px;
    color: #aaa;
    font-size: 11px;
    line-height: 1.5;
}
</style>

</head>
<body>
<div class="auth-wrap">

  <div class="card-top">
    <div class="brand">ITPH</div>
    <p>Create your account to get started</p>
  </div>

  <div class="card-body">

    <?php if (!empty($message)): ?>
    <div class="alert alert-<?= $message_type === 'error' ? 'error' : 'success' ?>">
      <i class="fas fa-<?= $message_type === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
      <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

      <div class="section-tag">Personal Information</div>

      <div class="field-group">
        <label class="form-label">Full Name</label>
        <input type="text" name="fullname" class="form-control" placeholder="John Doe" required
          value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>">
      </div>

      <div class="field-row" style="margin-bottom: 20px;">
        <div style="flex:1">
          <label class="form-label">Gender</label>
          <select name="gender" class="form-select" required>
            <option value="">Select</option>
            <option value="Male" <?= (($_POST['gender'] ?? '') === 'Male') ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= (($_POST['gender'] ?? '') === 'Female') ? 'selected' : '' ?>>Female</option>
            <option value="Other" <?= (($_POST['gender'] ?? '') === 'Other') ? 'selected' : '' ?>>Other</option>
          </select>
        </div>
        <div style="flex:1">
          <label class="form-label">Location</label>
          <select name="location" class="form-select" required>
            <option value="">Select</option>
            <option value="Northern Iloilo" <?= (($_POST['location'] ?? '') === 'Northern Iloilo') ? 'selected' : '' ?>>Northern Iloilo</option>
            <option value="Central Iloilo" <?= (($_POST['location'] ?? '') === 'Central Iloilo') ? 'selected' : '' ?>>Central Iloilo</option>
            <option value="Southern Iloilo" <?= (($_POST['location'] ?? '') === 'Southern Iloilo') ? 'selected' : '' ?>>Southern Iloilo</option>
          </select>
        </div>
      </div>

      <div class="field-row" style="margin-bottom: 20px;">
        <div style="flex:1">
          <label class="form-label">Status</label>
          <select name="status" class="form-select" required>
            <option value="">Select</option>
            <option value="Local" <?= (($_POST['status'] ?? '') === 'Local') ? 'selected' : '' ?>>Local</option>
            <option value="OFW" <?= (($_POST['status'] ?? '') === 'OFW') ? 'selected' : '' ?>>OFW</option>
          </select>
        </div>
      </div>

      <div class="divider"></div>

      <div class="section-tag">Account Details</div>

      <div class="field-group">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="you@example.com" required
          value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>

      <div class="field-row" style="margin-bottom: 8px;">
        <div style="flex:1">
          <label class="form-label">Password</label>
          <div class="password-wrapper">
            <input type="password" name="password" id="password" class="form-control" required
              value="<?= htmlspecialchars($_POST['password'] ?? '') ?>">
            <button type="button" class="toggle-password" onclick="togglePassword('password', this)">
              <i class="fa-solid fa-eye"></i>
            </button>
          </div>
        </div>
        <div style="flex:1">
          <label class="form-label">Confirm Password</label>
          <div class="password-wrapper">
            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required
              value="<?= htmlspecialchars($_POST['confirm_password'] ?? '') ?>">
            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', this)">
              <i class="fa-solid fa-eye"></i>
            </button>
          </div>
        </div>
      </div>
      <small class="password-hint" style="margin-bottom: 20px; display: block;">
        Minimum 8 chars, 1 letter, 1 number, 1 special char (@$!%*#?&)
      </small>

      <button type="submit" class="btn-gold full">
        <i class="fas fa-user-plus me-2"></i> Create Account
      </button>

    </form>

    <p class="hint">Already have an account? <a href="login.php">Sign In Here</a></p>

  </div>
</div>

<script>
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = "password";
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

</body>
<script src="https://cdn.botpress.cloud/webchat/v3.6/inject.js"></script>
<script src="https://files.bpcontent.cloud/2026/05/16/02/20260516020411-RS0TP9AJ.js" defer></script>
</html>