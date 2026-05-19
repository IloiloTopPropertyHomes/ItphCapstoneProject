<?php
require 'backends/config.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = sanitize_input($_POST['password']);
    $phone = sanitize_input($_POST['phone']); // new phone field

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format");
    }

    // Optional: Validate phone format (basic)
    if (!preg_match('/^[0-9]{7,15}$/', $phone)) {
        die("Invalid phone number. Only digits allowed, 7-15 characters.");
    }

    // Hash password
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Encrypt email and phone before storing
    $encrypted_email = encrypt_data($email);
    $encrypted_phone = encrypt_data($phone);

    $conn = get_db_connection();

    // Parameterized query
    $stmt = $conn->prepare("INSERT INTO users (email, password_hash, phone) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $encrypted_email, $password_hash, $encrypted_phone);

    if ($stmt->execute()) {
        echo "Registration successful! <a href='login.php'>Login here</a>";
    } else {
        if ($conn->errno == 1062) { // Duplicate email
            echo "Email already registered";
        } else {
            echo "Database error: " . $conn->error;
        }
    }

    $stmt->close();
    $conn->close();
}
?>

<h2>Registration</h2>
<form method="POST">
    Email: <input type="email" name="email" required><br>
    Phone: <input type="text" name="phone" required><br> <!-- new field -->
    Password: <input type="password" name="password" required><br>
    <button type="submit">Register</button>
    <p>Or register with:</p>
<a href="<?php echo $google_login_url; ?>" 
   style="display:inline-block; padding:10px 20px; background:#4285F4; color:white; text-decoration:none; border-radius:5px;">
   Register with Google
</a>
</form>