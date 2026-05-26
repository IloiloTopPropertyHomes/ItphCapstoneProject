<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://www.gstatic.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self';");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: no-referrer-when-downgrade");

session_start();
require_once __DIR__ . '/../backends/config.php';

$conn = get_db_connection();
$error = '';
$email = '';
$password = '';

$ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, username, password FROM admin_users WHERE gmail=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($admin_id, $username, $db_password);
        $stmt->fetch();
        $loginSuccess = false;

        if (password_get_info($db_password)['algo'] !== 0) {
            $loginSuccess = password_verify($password, $db_password);
        } else {
            $loginSuccess = ($password === $db_password);
        }

        if ($loginSuccess) {
            $_SESSION['id'] = $admin_id;
            $_SESSION['username'] = $username;
            $_SESSION['gmail'] = $email;
            $_SESSION['type'] = 'admin';

       // Insert login log
$log = $conn->prepare("
    INSERT INTO auth_logs
    (
        user_id,
        role,
        fullname,
        email,
        login_status,
        login_method,
        session_status,
        ip_address,
        user_agent,
        activity_time
    )
    VALUES
    (?, 'admin', ?, ?, 'success', 'email', 'online', ?, ?, NOW())
");

if ($log) {

    $log->bind_param(
        "issss",
        $admin_id,
        $username,
        $email,
        $ip,
        $userAgent
    );

    if (!$log->execute()) {
        die("Auth log insert failed: " . $log->error);
    }

    $log->close();

} else {
    die("Prepare failed: " . $conn->error);
}

            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Admin not found.";
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login — RealEstate</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --primary-light: rgba(37, 99, 235, 0.1);
            --bg-dark: #0f172a;
            --bg-card: #1e293b;
            --bg-input: #334155;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --border: #334155;
            --error: #ef4444;
            --error-bg: rgba(239, 68, 68, 0.1);
            --success: #22c55e;
            --radius: 12px;
            --radius-sm: 8px;
            --shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
            --transition: all 0.2s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-dark);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
        }

        /* Animated background pattern */
        .bg-pattern {
            position: fixed;
            inset: 0;
            z-index: 0;
            opacity: 0.03;
            background-image: 
                radial-gradient(circle at 20% 50%, var(--primary) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, var(--primary) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, var(--primary) 0%, transparent 50%);
            pointer-events: none;
        }

        .auth-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            padding: 24px;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Logo/Brand Section */
        .brand {
            text-align: center;
            margin-bottom: 32px;
        }

        .brand-logo {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }

        .brand-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary), #3b82f6);
            border-radius: var(--radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .brand-name {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .brand-name span {
            color: var(--primary);
        }

        .brand-subtitle {
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 400;
        }

        /* Card */
        .auth-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 32px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
        }

        .auth-header {
            margin-bottom: 24px;
        }

        .auth-header h1 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .auth-header p {
            color: var(--text-secondary);
            font-size: 14px;
        }

        /* Alerts */
        .alert {
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: slideIn 0.3s ease;
        }

        .alert-error {
            background: var(--error-bg);
            color: var(--error);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            color: var(--success);
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-10px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* Form */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i.icon-left {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 14px;
            transition: var(--transition);
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px 12px 40px;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text-primary);
            font-size: 14px;
            font-family: inherit;
            transition: var(--transition);
            outline: none;
        }

        .form-group input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        .form-group input:focus + .icon-left,
        .form-group input:focus ~ .icon-left {
            color: var(--primary);
        }

        /* Password toggle */
        .password-wrapper .input-wrapper input {
            padding-right: 40px;
        }

        .pw-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px;
            font-size: 14px;
            transition: var(--transition);
        }

        .pw-toggle:hover {
            color: var(--text-primary);
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary), #3b82f6);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
            margin-top: 8px;
        }

        .btn-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        /* Footer */
        .auth-footer {
            text-align: center;
            margin-top: 24px;
            color: var(--text-muted);
            font-size: 12px;
        }

        .auth-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        /* Security badge */
        .security-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 20px;
            font-size: 12px;
            color: var(--text-muted);
        }

        .security-badge i {
            color: var(--success);
            font-size: 10px;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .auth-card {
                padding: 24px 20px;
            }
            
            .brand-name {
                font-size: 20px;
            }
        }

        /* Loading state */
        .btn-submit.loading {
            opacity: 0.8;
            pointer-events: none;
        }

        .btn-submit.loading::after {
            content: '';
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            margin-left: 8px;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

    <div class="bg-pattern"></div>

    <div class="auth-container">
        
        <!-- Brand -->
        <div class="brand">
            <div class="brand-logo">
                <div class="brand-icon">
                    <i class="fa-solid fa-building"></i>
                </div>
                <div class="brand-name">Real<span>Estate</span></div>
            </div>
            <div class="brand-subtitle">Admin Control Center</div>
        </div>

        <!-- Card -->
        <div class="auth-card">
            <div class="auth-header">
                <h1>Welcome back</h1>
                <p>Enter your credentials to access the dashboard</p>
            </div>

            <?php if (!empty($error)) : ?>
                <div class="alert alert-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)) : ?>
                <div class="alert alert-success">
                    <i class="fa-solid fa-check-circle"></i>
                    <?= htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                
                <div class="form-group">
                    <label>Email Address</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-envelope icon-left"></i>
                        <input 
                            type="email" 
                            name="email" 
                            placeholder="admin@example.com" 
                            required 
                            value="<?= htmlspecialchars($email) ?>"
                            autocomplete="email"
                        >
                    </div>
                </div>

                <div class="form-group password-wrapper">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <i class="fa-solid fa-lock icon-left"></i>
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            placeholder="••••••••" 
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="pw-toggle" onclick="togglePassword()" aria-label="Toggle password visibility">
                            <i class="fa-solid fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-submit" id="submitBtn">
                    Sign In to Dashboard
                </button>

            </form>

            <div class="security-badge">
                <i class="fa-solid fa-shield-halved"></i>
                <span>Secured with 256-bit encryption</span>
            </div>
        </div>

        <div class="auth-footer">
            <p>© <?= date('Y') ?> RealEstate Admin. All rights reserved.</p>
        </div>

    </div>

    <script>
        function togglePassword() {
            const pw = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            
            if (pw.type === 'password') {
                pw.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                pw.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Add loading state on submit
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('submitBtn');
            btn.classList.add('loading');
            btn.innerHTML = 'Authenticating...';
        });

        // Focus email on load
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('input[type="email"]').focus();
        });
    </script>

</body>
</html>