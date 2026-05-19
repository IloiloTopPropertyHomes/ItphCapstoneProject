<?php
session_start(); // Start session to track login
require 'backends/config.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = sanitize_input($_POST['password']);

    // Encrypt email to match database
    $encrypted_email = encrypt_data($email);

    $conn = get_db_connection();

    // Prepare statement with error check
    $stmt = $conn->prepare("SELECT id, password_hash FROM users WHERE email = ?");
    if (!$stmt) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }

    $stmt->bind_param("s", $encrypted_email);
    $stmt->execute();
    $stmt->bind_result($user_id, $password_hash);
    $stmt->fetch();
    $stmt->close();
    $conn->close();

    // Verify password
    if ($password_hash && password_verify($password, $password_hash)) {
        // Login successful — store user info in session
        $_SESSION['user_id'] = $user_id;   // needed for top bar
        $_SESSION['username'] = $email;    // optional, display name

        header("Location: index.php"); // Redirect to homepage
        exit;
    } else {
        $error_message = "Invalid email or password";
    }
}
?>

<h2>Login</h2>

<?php if (!empty($error_message)) echo "<p style='color:red;'>$error_message</p>"; ?>

<form method="POST">
    <label>Email:</label>
    <input type="email" name="email" required><br><br>

    <label>Password:</label>
    <input type="password" name="password" required><br><br>

    <button type="submit" class="btn">Login</button>
    <a href="#" style="display:inline-block; padding:10px 20px; background:#4285F4; color:white; text-decoration:none; border-radius:5px;">
    Login with Google
</a>
    
</form>