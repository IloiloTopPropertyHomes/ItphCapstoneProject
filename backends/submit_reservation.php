<?php
session_start();
require_once 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';

$conn = get_db_connection();

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = $_POST['fullname'];
    $gender   = $_POST['gender'];
    $email    = $_POST['email'];
    $phone    = $_POST['phone'];
    $property = $_POST['property_page'];
    $location = $_POST['location'];
    $stats    = $_POST['stats'] ?? '';
    $date     = $_POST['date'];
    $time     = $_POST['time'];
    $meeting_type = $_POST['meeting_type'];
    $status = 'Pending';
    $redirect = $_POST['redirect'] ?? '../user_side/account.php#reservations';

    // Insert reservation into DB
    $stmt = $conn->prepare("
        INSERT INTO reservations (fullname, gender, email, phone, property, location, stats, date, time, meeting_type, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "sssssssssss",
        $fullname, $gender, $email, $phone, $property,
        $location, $stats, $date, $time, $meeting_type, $status
    );

    if($stmt->execute()) {
        if($stmt->execute()) {


        // ===================== DECREMENT AVAILABLE UNITS =====================
        $property_name = $property;
        if (strpos($property, ' - ') !== false) {
            $parts = explode(' - ', $property);
            $property_name = trim($parts[0]);
        }
        
        if (!empty($property_name)) {
            $updateStmt = $conn->prepare("
                UPDATE propertiies 
                SET available_units = available_units - 1 
                WHERE title = ? AND available_units > 0
            ");
            $updateStmt->bind_param("s", $property_name);
            $updateStmt->execute();
            $updateStmt->close();
        }
        
        // ✅ Send email to all agents (your existing code)
        $agents = $conn->query("SELECT gmail FROM agents");
        $messageBody = "New appointment booked:\n\n"
                     . "Name: $fullname\n"
                     . "Email: $email\n"
                     . "Phone: $phone\n"
                     . "Property: $property\n"
                     . "Location: $location\n"
                     . "Residency Status: $stats\n"
                     . "Date: $date\n"
                     . "Time: $time\n"
                     . "Meeting Type: $meeting_type\n\n"
                     . "Please check the admin dashboard for details.";

        while($agent = $agents->fetch_assoc()) {
            $agentEmail = $agent['gmail'];
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'itph934@gmail.com';
                $mail->Password = 'bjhg rpeh ywaw eofo';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('itph934@gmail.com', 'ITPH Notifications');
                $mail->addAddress($agentEmail);
                $mail->Subject = 'New Appointment Booked';
                $mail->Body    = $messageBody;
                $mail->send();
            } catch (Exception $e) {
                error_log("Email to $agentEmail failed: " . $mail->ErrorInfo);
            }
        }

        // Redirect to account reservations page
        header("Location: " . $redirect);
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
}
?>