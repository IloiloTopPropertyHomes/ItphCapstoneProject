<?php
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://www.gstatic.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self';");
header("X-Content-Type-Options: nosniff"); header("X-Frame-Options: SAMEORIGIN"); header("Referrer-Policy: no-referrer-when-downgrade");
require_once '../backends/config.php';
$conn = get_db_connection();
function encrypt_data($data){
    global $ENCRYPTION_KEY;
    $cipher = "AES-256-CBC";
    $iv = "1234567890123456"; // fixed IV
    $encrypted = openssl_encrypt($data, $cipher, $ENCRYPTION_KEY, OPENSSL_RAW_DATA, $iv);
    return base64_encode($encrypted); // safe for MySQL
}

function decrypt_data($data){
    global $ENCRYPTION_KEY;
    $cipher = "AES-256-CBC";
    $iv = "1234567890123456"; // same IV
    $decoded = base64_decode($data);
    return openssl_decrypt($decoded, $cipher, $ENCRYPTION_KEY, OPENSSL_RAW_DATA, $iv);
}
// Decrypt function


$result = $conn->query("SELECT * FROM reservations ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Reservations</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-5">
<h2>Reservations</h2>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Property</th>
            <th>Date</th>
            <th>Time</th>
            <th>Created At</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo decrypt_data($row['fullname']); ?></td>
            <td><?php echo decrypt_data($row['email']); ?></td>
            <td><?php echo decrypt_data($row['phone']); ?></td>
            <td><?php echo htmlspecialchars($row['property']); ?></td>
            <td><?php echo $row['date']; ?></td>
            <td><?php echo $row['time']; ?></td>
            <td><?php echo $row['created_at']; ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
</div>
</body>
</html>