<?php
// ===================== SESSION =====================
session_start();

// ===================== DATABASE =====================
require_once 'config.php';
$conn = get_db_connection();

// ===================== SECURITY =====================
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../user_side/contact_us.php");
    exit();
}

// ===================== CSRF CHECK =====================
if (
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    die("Invalid CSRF token.");
}

// ===================== GET FORM DATA =====================
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$message = trim($_POST['message'] ?? '');

// ===================== VALIDATION =====================
if (empty($name) || empty($email) || empty($message)) {
    header("Location: ../user_side/contact_us.php?msg=Please fill in all required fields.");
    exit();
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../user_side/contact_us.php?msg=Invalid email address.");
    exit();
}

// ===================== INSERT TO DATABASE =====================
$stmt = $conn->prepare("
    INSERT INTO contact_messages (name, email, phone, message)
    VALUES (?, ?, ?, ?)
");

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("ssss", $name, $email, $phone, $message);

if ($stmt->execute()) {

    // Success
    header("Location: ../user_side/contact_us.php?msg=Message sent successfully!");
    exit();

} else {

    // Database error
    die("Database insert failed: " . $stmt->error);
}

$stmt->close();
$conn->close();
?>