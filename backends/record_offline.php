<?php
session_start();
require_once __DIR__ . '/../backends/config.php';

$conn = get_db_connection();

if(isset($_POST['record_offline'])){
    $agent_id = $_POST['agent_id'];
    $customer_name = htmlspecialchars(trim($_POST['customer_name']));
    $property = htmlspecialchars(trim($_POST['property']));
    $amount = $_POST['amount'];

 // Insert offline transaction
$stmt = $conn->prepare("
    INSERT INTO transaction_logs 
        (user_id, user_type, action, reference_type, reference_id, amount, mode, created_at)
    VALUES (?, 'agent', ?, 'property', NULL, ?, 'offline', NOW())
");
$action = "Completed offline deal for $customer_name - $property";
$stmt->bind_param("isd", $agent_id, $action, $amount);
    if($stmt->execute()){
        $_SESSION['message'] = "Offline deal recorded successfully!";
    } else {
        $_SESSION['error'] = "Error recording offline deal: " . $stmt->error;
    }
    $stmt->close();
    header("Location: agent_dashboard.php");
    exit;
}
?>