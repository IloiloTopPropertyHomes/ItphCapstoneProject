<?php
require_once __DIR__ . '/../backends/config.php';

$conn = get_db_connection();

$email = "itph934@gmail.com";
$newPassword = "itph@2026";

// PROPER HASH (bcrypt)
$hash = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE admin_users SET password=? WHERE gmail=?");
$stmt->bind_param("ss", $hash, $email);

if ($stmt->execute()) {
    echo "Password converted to bcrypt successfully!";
} else {
    echo "Error updating password";
}
?>