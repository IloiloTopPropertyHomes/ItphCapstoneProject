<?php
declare(strict_types=1);

header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self';");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Referrer-Policy: strict-origin-when-cross-origin");

session_start();
session_regenerate_id(true);

require_once __DIR__ . '/../backends/config.php';

$conn = get_db_connection();

define('MAX_ATTEMPTS', 5);

function logAuth($conn, $uid, $role, $name, $email, $status, $method, $sess) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    $stmt = $conn->prepare("
        INSERT INTO auth_logs 
        (user_id, role, fullname, email, login_status, login_method, session_status, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "issssssss",
        $uid,
        $role,
        $name,
        $email,
        $status,
        $method,
        $sess,
        $ip,
        $ua
    );

    $stmt->execute();
    $stmt->close();
}
$isOtpStep = false;
$maskedEmail = '';
$email = '';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL) ?: '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {

        $error = "Please enter both email and password.";

    } else {

        $stmt = $conn->prepare("
            SELECT id, username, password, login_attempts, status
            FROM agents
            WHERE gmail = ?
        ");

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $agent = $result->fetch_assoc();

            if ($agent['status'] === 'suspended') {

                $error = "Account suspended. Contact administrator.";

            } elseif ((int)$agent['login_attempts'] >= MAX_ATTEMPTS) {

                $error = "Account locked. Contact administrator.";

            } elseif (password_verify($password, $agent['password'])) {

                $_SESSION['id'] = $agent['id'];
                $_SESSION['username'] = $agent['username'];
                $_SESSION['gmail'] = $email;
                $_SESSION['type'] = 'agent';

                $reset = $conn->prepare("
                    UPDATE agents
                    SET login_attempts = 0,
                        last_login = NOW()
                    WHERE id = ?
                ");

                $reset->bind_param("i", $agent['id']);
                $reset->execute();
                $reset->close();

                logAuth(
                    $conn,
                    $agent['id'],
                    'agent',
                    $agent['username'],
                    $email,
                    'success',
                    'email',
                    'online'
                );

                header("Location: agent_dashboard.php");
                exit;

            } else {

                $attempts = (int)$agent['login_attempts'] + 1;
                $remaining = MAX_ATTEMPTS - $attempts;

                $upd = $conn->prepare("
                    UPDATE agents
                    SET login_attempts = ?
                    WHERE id = ?
                ");

                $upd->bind_param("ii", $attempts, $agent['id']);
                $upd->execute();
                $upd->close();

                $error = $remaining > 0
                    ? "Invalid password. {$remaining} attempts left."
                    : "Account locked. Contact administrator.";
            }

        } else {

            $error = "Invalid credentials.";
        }

        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Login — RealEstate</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --bg: #0a0a0a;
            --bg-card: #141414;
            --bg-input: #1a1a1a;
            --gold: #c9a84c;
            --gold-light: #e8d5a3;
            --gold-dim: rgba(201,168,76,0.12);
            --text: #f0ece3;
            --text-secondary: #9a958d;
            --text-muted: #6b6560;
            --border: #2a2a2a;
            --error: #e05252;
            --error-bg: rgba(224,82,82,0.08);
            --success: #5aab7a;
            --success-bg: rgba(90,171,122,0.08);
            --radius: 12px;
            --radius-sm: 8px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* Ambient glow */
        body::before {
            content: '';
            position: fixed;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(201,168,76,0.05) 0%, transparent 70%);
            top: -100px;
            right: -100px;
            pointer-events: none;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 40px 36px;
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
        }

        /* Brand */
        .brand {
            text-align: center;
            margin-bottom: 32px;
        }
        .brand-icon {
            width: 52px;
            height: 52px;
            background: var(--gold-dim);
            border: 1px solid rgba(201,168,76,0.2);
            border-radius: var(--radius-sm);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            color: var(--gold);
            font-size: 22px;
        }
        .brand h1 {
            font-family: 'Playfair Display', serif;
            font-size: 26px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .brand h1 span { color: var(--gold); }
        .brand p {
            font-size: 12px;
            color: var(--text-muted);
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        /* Step indicator */
        .steps {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 28px;
        }
        .step {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--text-muted);
        }
        .step.active { color: var(--gold); }
        .step.done { color: var(--success); }
        .step-dot {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            border: 2px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 600;
        }
        .step.active .step-dot {
            border-color: var(--gold);
            background: var(--gold-dim);
            color: var(--gold);
        }
        .step.done .step-dot {
            border-color: var(--success);
            background: var(--success-bg);
            color: var(--success);
        }
        .step-line {
            width: 32px;
            height: 2px;
            background: var(--border);
            border-radius: 1px;
        }
        .step-line.done { background: var(--success); }

        /* Alerts */
        .alert {
            padding: 12px 14px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .alert-error {
            background: var(--error-bg);
            border: 1px solid rgba(224,82,82,0.2);
            color: #e8a0a0;
        }
        .alert-success {
            background: var(--success-bg);
            border: 1px solid rgba(90,171,122,0.2);
            color: #90d4aa;
        }
        .alert i { margin-top: 2px; font-size: 14px; }

        /* Form */
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 6px;
        }
        .input-wrap {
            position: relative;
        }
        .form-group input {
            width: 100%;
            padding: 13px 16px;
            background: var(--bg-input);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            color: var(--text);
            font-size: 15px;
            font-family: inherit;
            outline: none;
            transition: all 0.2s;
        }
        .form-group input:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px var(--gold-dim);
        }
        .form-group input::placeholder { color: var(--text-muted); }
        .form-group input:disabled { opacity: 0.5; cursor: not-allowed; }

        /* Password toggle */
        .pw-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 15px;
        }
        .pw-toggle:hover { color: var(--gold); }

        /* OTP specific styling */
        .otp-field input {
            text-align: center;
            letter-spacing: 8px;
            font-size: 20px;
            font-weight: 600;
            font-family: 'Inter', monospace;
        }

        /* Buttons */
        .btn {
            width: 100%;
            padding: 13px 20px;
            border-radius: var(--radius-sm);
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-primary {
            background: var(--gold);
            color: #0a0a0a;
        }
        .btn-primary:hover:not(:disabled) {
            background: var(--gold-light);
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(201,168,76,0.2);
        }
        .btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .btn-ghost {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border);
        }
        .btn-ghost:hover {
            background: rgba(255,255,255,0.03);
            color: var(--text);
        }
        .btn-link {
            background: none;
            border: none;
            color: var(--gold);
            font-size: 13px;
            padding: 0;
            width: auto;
        }
        .btn-link:hover { text-decoration: underline; }
        .btn-link:disabled {
            color: var(--text-muted);
            cursor: not-allowed;
            text-decoration: none;
        }

        /* Spinner */
        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid transparent;
            border-top-color: currentColor;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: none;
        }
        .btn.loading .spinner { display: inline-block; }
        .btn.loading .btn-text { opacity: 0.8; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Timer */
        .timer {
            text-align: center;
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 16px;
        }
        .timer span { color: var(--gold); font-weight: 600; }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 24px;
            font-size: 13px;
            color: var(--text-muted);
        }
        .login-footer a {
            color: var(--gold);
            text-decoration: none;
        }
        .login-footer a:hover { text-decoration: underline; }

        /* Security badge */
        .security {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 28px;
            font-size: 11px;
            color: var(--text-muted);
        }
        .security i { color: var(--success); }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 20px 0;
            color: var(--text-muted);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-card { padding: 32px 24px; }
            .brand h1 { font-size: 22px; }
        }
    </style>
</head>
<body>

    <div class="login-card">
        
        <!-- Brand -->
        <div class="brand">
            <div class="brand-icon"><i class="fas fa-building"></i></div>
            <h1>Real<span>Estate</span></h1>
            <p>Agent Portal</p>
        </div>

        <!-- Steps -->
        <div class="steps">
            <div class="step <?= $isOtpStep ? 'done' : 'active' ?>">
                <div class="step-dot"><?= $isOtpStep ? '<i class="fas fa-check" style="font-size:10px;"></i>' : '1' ?></div>
                <span>Sign In</span>
            </div>
            <div class="step-line <?= $isOtpStep ? 'done' : '' ?>"></div>
            <div class="step <?= $isOtpStep ? 'active' : '' ?>">
                <div class="step-dot">2</div>
                <span>Verify</span>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="alert alert-error"><i class="fas fa-circle-exclamation"></i><span><?= htmlspecialchars($error) ?></span></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fas fa-circle-check"></i><span><?= htmlspecialchars($success) ?></span></div>
        <?php endif; ?>

        <!-- ═══════════════════════════════════════
             STEP 1: CREDENTIALS
             ═══════════════════════════════════════ -->
        <?php if (!$isOtpStep): ?>
            
            <form method="POST" id="credForm">
                <input type="hidden" name="action" value="send_otp">
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" placeholder="agent@example.com" required 
                           autocomplete="email" value="<?= htmlspecialchars($email) ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <input type="password" id="password" name="password" placeholder="Enter password" 
                               required autocomplete="current-password">
                        <button type="button" class="pw-toggle" onclick="togglePw()" aria-label="Show password">
                            <i class="fas fa-eye" id="eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <span class="spinner"></span>
                    <span class="btn-text">Continue</span>
                    <i class="fas fa-arrow-right" style="font-size:12px;"></i>
                </button>
            </form>

            <div class="login-footer">
                <a href="forgot_password.php">Forgot password?</a>
            </div>

        <!-- ═══════════════════════════════════════
             STEP 2: OTP VERIFICATION
             ═══════════════════════════════════════ -->
        <?php else: ?>
            
            <form method="POST" id="otpForm">
                <input type="hidden" name="action" value="verify_otp">
                
                <p style="text-align:center; color:var(--text-secondary); font-size:14px; margin-bottom:20px;">
                    Enter the 6-digit code sent to<br>
                    <strong style="color:var(--text);"><?= htmlspecialchars($maskedEmail) ?></strong>
                </p>

                <!-- ✅ SINGLE OTP INPUT FIELD -->
                <div class="form-group otp-field">
                    <label for="otp">Verification Code</label>
                    <input 
                        type="text" 
                        id="otp" 
                        name="otp" 
                        placeholder="000000" 
                        required 
                        maxlength="6"
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        autocomplete="one-time-code"
                        autofocus
                    >
                </div>

                <button type="submit" class="btn btn-primary" id="verifyBtn">
                    <span class="spinner"></span>
                    <span class="btn-text">Verify & Sign In</span>
                </button>
            </form>

            <div class="divider">or</div>

            <!-- Separate forms for resend to avoid conflicts -->
            <form method="POST" style="margin-bottom:8px;">
                <input type="hidden" name="action" value="resend_otp">
                <button type="submit" class="btn btn-ghost" id="resendBtn">
                    <span class="spinner"></span>
                    <span class="btn-text">Resend Code</span>
                </button>
            </form>

            <a href="?reset=1" class="btn btn-ghost" style="text-decoration:none;">Back to Login</a>

            <div class="timer">
                Expires in <span id="countdown">05:00</span>
            </div>

        <?php endif; ?>

        <!-- Security -->
        <div class="security">
            <i class="fas fa-shield-halved"></i>
            <span>Protected with 2-Factor Authentication</span>
        </div>

    </div>

    <script>
        // Password toggle
        function togglePw() {
            const pw = document.getElementById('password');
            const eye = document.getElementById('eye');
            if (pw.type === 'password') {
                pw.type = 'text';
                eye.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                pw.type = 'password';
                eye.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // Loading states
        function setupLoading(formId, btnId) {
            const form = document.getElementById(formId);
            const btn = document.getElementById(btnId);
            if (!form || !btn) return;
            form.addEventListener('submit', () => {
                btn.disabled = true;
                btn.classList.add('loading');
            });
        }

        setupLoading('credForm', 'submitBtn');
        setupLoading('otpForm', 'verifyBtn');

        // OTP auto-submit when 6 digits entered
        const otpInput = document.getElementById('otp');
        if (otpInput) {
            otpInput.addEventListener('input', (e) => {
                // Strip non-digits
                otpInput.value = otpInput.value.replace(/\D/g, '');
                // Auto-submit at 6 digits
                if (otpInput.value.length === 6) {
                    document.getElementById('otpForm').submit();
                }
            });
            // Focus on load
            otpInput.focus();
        }

        // Countdown timer
        const countdownEl = document.getElementById('countdown');
        if (countdownEl) {
            let seconds = <?= isset($_SESSION['otp_expiry']) ? max(0, $_SESSION['otp_expiry'] - time()) : 300 ?>;
            
            function tick() {
                if (seconds <= 0) {
                    countdownEl.textContent = 'Expired';
                    countdownEl.style.color = 'var(--error)';
                    document.getElementById('verifyBtn')?.setAttribute('disabled', 'true');
                    return;
                }
                const m = Math.floor(seconds / 60);
                const s = seconds % 60;
                countdownEl.textContent = `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
                seconds--;
            }
            tick();
            setInterval(tick, 1000);
        }

        // Resend cooldown
        const resendBtn = document.getElementById('resendBtn');
        if (resendBtn) {
            let cooldown = <?= isset($_SESSION['otp_resend']) ? max(0, $_SESSION['otp_resend'] - time()) : 0 ?>;
            if (cooldown > 0) {
                resendBtn.disabled = true;
                const origText = resendBtn.querySelector('.btn-text').textContent;
                const interval = setInterval(() => {
                    cooldown--;
                    resendBtn.querySelector('.btn-text').textContent = `Wait ${cooldown}s`;
                    if (cooldown <= 0) {
                        resendBtn.disabled = false;
                        resendBtn.querySelector('.btn-text').textContent = origText;
                        clearInterval(interval);
                    }
                }, 1000);
            }
        }
    </script>
</body>
</html>