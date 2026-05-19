<?php
session_start();
require_once '../backends/config.php';

$conn = get_db_connection();

// Make sure the form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the reservation ID and payment type from the form
    $reservation_id = $_POST['reservation_id'] ?? null;
    $payment_type = $_POST['payment_type'] ?? null;

    // Basic validation
    if (!$reservation_id || !$payment_type) {
        echo "Reservation ID and payment type are required.";
        exit;
    }

    // Update the reservation: mark as Done and set payment type
    $stmt = $conn->prepare("UPDATE reservations SET status = 'Done', payment_type = ? WHERE id = ?");
    $stmt->bind_param("si", $payment_type, $reservation_id);

    if ($stmt->execute()) {
        // Redirect back to agent appointments page with success message
        header("Location: ../admin_side/agent_appointments.php?success=1");
        exit;
    } else {
        echo "Error updating reservation: " . $conn->error;
    }
} else {
    // Prevent direct access
    header("Location: agent_appointments.php");
    exit;
}