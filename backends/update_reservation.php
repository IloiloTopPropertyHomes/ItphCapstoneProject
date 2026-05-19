<?php
session_start();
require_once '../backends/config.php';
$conn = get_db_connection();

if(!isset($_SESSION['id'])) {
    echo json_encode(['success'=>false, 'message'=>'Not authorized']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if(!$id || !$action){
    echo json_encode(['success'=>false, 'message'=>'Invalid data']);
    exit;
}

if($action === 'confirm'){
    $stmt = $conn->prepare("UPDATE reservations SET status='Confirmed' WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success'=>true]);
    exit;
}

if($action === 'done'){
    // Update reservation to Done
    $stmt = $conn->prepare("UPDATE reservations SET status='Done', seen=0 WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success'=>true]);
    exit;
}

echo json_encode(['success'=>false, 'message'=>'Unknown action']);
?>