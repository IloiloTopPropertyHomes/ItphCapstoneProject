    <?php
    session_start();
    require_once __DIR__ . '/../backends/config.php';
    require_once __DIR__ . '/../backends/send_email.php';
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once 'notification_helper.php';
    use Dompdf\Dompdf;
    use Dompdf\Options;

    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://www.gstatic.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; frame-ancestors 'self'; base-uri 'self';");
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: SAMEORIGIN");
    header("Referrer-Policy: no-referrer-when-downgrade");

    $conn = get_db_connection();
    $type = $_GET['type'] ?? 'recent';

    // Auto-logout inactive sessions
    $conn->query("UPDATE auth_logs SET session_status='offline' WHERE session_status='online' AND activity_time < NOW() - INTERVAL 15 MINUTE");

    // Fetch reservations
    if ($type === 'recent') {
        $res_query = $conn->query("SELECT * FROM reservations WHERE status != 'Done' ORDER BY id DESC LIMIT 10");
    } else {
        $res_query = $conn->query("SELECT * FROM reservations WHERE status='Done' ORDER BY id DESC LIMIT 10");
    }
$countResult = $conn->query("
SELECT COUNT(*) AS total
FROM notifications
WHERE is_read = 0
");

$unreadCount = $countResult->fetch_assoc()['total'];
$notifications = $conn->query("
SELECT *
FROM notifications
ORDER BY created_at DESC
LIMIT 8
");
    // Payment handlers
    if (isset($_POST['payment_cash'])) {
        $id = (int)$_POST['reservation_id'];
        $stmt = $conn->prepare("SELECT fullname, email, property FROM reservations WHERE id=?F");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($client_name, $client_email, $property);
        $stmt->fetch();
        $stmt->close();

        $update = $conn->prepare("UPDATE reservations SET status='Done' WHERE id=?");
        $update->bind_param("i", $id);
        $update->execute();
        $update->close();

        $subject = "Your Property is Ready for Cash Payment!";
        $body = "Hi {$client_name},<br><br>Your property <strong>{$property}</strong> is ready for cash payment! Please go to the office for paper signing of property rights and payment.<br><br>— Admin Team";
        send_gmail_notification($client_email, $client_name, $subject, $body);

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    if (isset($_POST['payment_installment'])) {
        $id = (int)$_POST['reservation_id'];
        $stmt = $conn->prepare("SELECT fullname, email, property, payment_type FROM reservations WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($client_name, $client_email, $property, $payment_type);
        $stmt->fetch();
        $stmt->close();

        $update = $conn->prepare("UPDATE reservations SET status='Done' WHERE id=?");
        $update->bind_param("i", $id);
        $update->execute();
        $update->close();

        $subject = "Congratulations! Your reservation is Done";
        $body = "Hi {$client_name},<br><br>Your payment for <strong>{$property}</strong> is now completed. Thank you for your payment!<br><br>— Admin Team";
        send_gmail_notification($client_email, $client_name, $subject, $body);

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    if (isset($_POST['send_notification'])) {

        $id = (int)$_POST['reservation_id'];
        $payment_method = $_POST['payment_method'];

        // Current logged in admin/agent
        $agent_id = (int)$_SESSION['id'];

        // Get reservation details
        $stmt = $conn->prepare("
            SELECT fullname, email, property 
            FROM reservations 
            WHERE id=?
        ");

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($user_name, $user_email, $property_name);
        $stmt->fetch();
        $stmt->close();

        // Update reservation
        $update = $conn->prepare("
            UPDATE reservations 
            SET 
                notification_sent = 1,
                payment_type = ?,
                status = 'Done',
                agent_id = ?
            WHERE id = ?
        ");

        $update->bind_param("sii", $payment_method, $agent_id, $id);
        $update->execute();
        $update->close();

        // Email subject
        $subject = "Property Payment Confirmation";

        // Email message
        if ($payment_method === "Cash") {

            $body = "
            Hi {$user_name},<br><br>

            Congratulations! Your property 
            <strong>{$property_name}</strong> 
            is approved for <strong>Cash Payment</strong>.<br><br>

            Please visit the office for full payment,
            contract signing, certificates, and key claiming.<br><br>

            Thank you for choosing ITPH Property!<br><br>

            — Admin Team
            ";

        } else {

            $body = "
            Hi {$user_name},<br><br>

            Congratulations! Your property 
            <strong>{$property_name}</strong> 
            is approved for <strong>Installment Payment</strong>.<br><br>

            Please visit the office for installment agreement,
            contract signing, certificates, and payment scheduling.<br><br>

            Thank you for choosing ITPH Prnotifyfoperty!<br><br>

            — Admin Team
            ";
        }

        // Send email
        send_gmail_notification(
            $user_email,
            $user_name,
            $subject,
            $body
        );

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    //approved done
// =====================================================
// ADMIN CLOSES DEAL AFTER AGENT FORWARDS IT
// =====================================================
// ADMIN CLOSES DEAL AFTER AGENT FORWARDS IT
// =====================================================
if (isset($_POST['approve_done'])) {

    $reservation_id = (int)$_POST['reservation_id'];

    // Get reservation details
    $stmt = $conn->prepare("
        SELECT 
            fullname,
            email,
            property,
            payment_type,
            agent_id
        FROM reservations
        WHERE id = ?
          AND status = 'Waiting Admin Approval'
        LIMIT 1
    ");

    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $reservation = $result->fetch_assoc();

    $stmt->close();

    // Check if reservation exists
    if (!$reservation) {

        $_SESSION['error'] = "This transaction is not waiting for admin approval.";

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // -------------------------------------------------
    // CLOSE THE DEAL
    // -------------------------------------------------
    $stmt = $conn->prepare("
        UPDATE reservations
        SET status = 'Done'
        WHERE id = ?
          AND status = 'Waiting Admin Approval'
    ");

    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $stmt->close();

    // -------------------------------------------------
    // SEND CUSTOMER EMAIL
    // -------------------------------------------------
    $subject = "Your Property Transaction is Complete";

    if ($reservation['payment_type'] === "Cash" ||
        $reservation['payment_type'] === "Spot Cash") {

        $payment_text = "Cash";

        $body = "
            <p>Dear <strong>" .
            htmlspecialchars($reservation['fullname']) .
            "</strong>,</p>

            <p>
                Congratulations! Your purchase of
                <strong>" .
                htmlspecialchars($reservation['property']) .
                "</strong>
                has been successfully completed through
                <strong>Cash Payment</strong>.
            </p>

            <p>
                Your transaction has been officially closed
                by the administration.
            </p>

            <p>
                Thank you for choosing
                <strong>Iloilo Top Property Homes</strong>.
            </p>

            <p>
                Regards,<br>
                ITPH Administration
            </p>
        ";

    } else {

        $payment_text = "Installment";

        $body = "
            <p>Dear <strong>" .
            htmlspecialchars($reservation['fullname']) .
            "</strong>,</p>

            <p>
                Congratulations! Your installment transaction for
                <strong>" .
                htmlspecialchars($reservation['property']) .
                "</strong>
                has been successfully completed.
            </p>

            <p>
                Your transaction has been officially closed
                by the administration.
            </p>

            <p>
                Thank you for choosing
                <strong>Iloilo Top Property Homes</strong>.
            </p>

            <p>
                Regards,<br>
                ITPH Administration
            </p>
        ";
    }

    send_gmail_notification(
        $reservation['email'],
        $reservation['fullname'],
        $subject,
        $body
    );

    $_SESSION['success'] =
        "Deal closed successfully. Payment: " . $payment_text;

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
    // Auth check
    if (!isset($_SESSION['id'])) {
        header("Location: login.php");
        exit;
    }

    // Admin info
    $stmt = $conn->prepare("SELECT username, gmail FROM admin_users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();
    $stmt->bind_result($username, $admin_email);
    if ($stmt->fetch()) {
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $admin_email;
    }
    $stmt->close();

    /* ================= CONTACT MESSAGES ================= */
    $contact_query = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 10");
    $contact_messages = [];
    while ($row = $contact_query->fetch_assoc()) {
        $contact_messages[] = $row;
    }

    /* ================= COUNTS ================= */
    $total_properties = $conn->query("SELECT COUNT(*) AS total FROM propertiies")->fetch_assoc()['total'] ?? 0;
    $total_users = $conn->query("SELECT COUNT(*) AS total FROM user_login")->fetch_assoc()['total'] ?? 0;
    $total_reservations = $conn->query("SELECT COUNT(*) AS total FROM reservations")->fetch_assoc()['total'] ?? 0;
    $total_blogs = $conn->query("SELECT COUNT(*) AS total FROM vlogs")->fetch_assoc()['total'] ?? 0;

    $total_online_customers = $conn->query("
    SELECT COUNT(DISTINCT user_id) AS total
    FROM auth_logs
    WHERE role='customer'
    AND last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ")->fetch_assoc()['total'] ?? 0;
    $total_online_agents = $conn->query("SELECT COUNT(DISTINCT user_id) AS total FROM auth_logs WHERE role='agent' AND session_status='online'")->fetch_assoc()['total'] ?? 0;
    $total_online_admins = $conn->query("SELECT COUNT(DISTINCT user_id) AS total FROM auth_logs WHERE role='admin' AND session_status='online'")->fetch_assoc()['total'] ?? 0;



    $monthly_data = [];

    $result = $conn->query("
    SELECT
        MONTH(created_at) AS month_num,
        MONTHNAME(created_at) AS month,
        COUNT(*) AS total
    FROM reservations
    WHERE YEAR(created_at) = YEAR(CURDATE())
    GROUP BY MONTH(created_at)
    ORDER BY MONTH(created_at)
    ");

    while ($row = $result->fetch_assoc()) {

        $monthly_data[] = [
            'month_num' => (int)$row['month_num'],
            'month' => $row['month'],
            'total' => (int)$row['total']
        ];

    }

    /* ================= APPOINTMENT ANALYSIS ================= */
/* ================= APPOINTMENTS OVERVIEW FILTER ================= */

$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('n');

$stmt = $conn->prepare("
    SELECT
        DAY(created_at) AS day,
        COUNT(*) AS total
    FROM reservations
    WHERE MONTH(created_at)=?
      AND YEAR(created_at)=YEAR(CURDATE())
    GROUP BY DAY(created_at)
    ORDER BY DAY(created_at)
");

$stmt->bind_param("i", $selectedMonth);
$stmt->execute();
$result = $stmt->get_result();

$days = [];
$totals = [];

while ($row = $result->fetch_assoc()) {
    $days[] = (int)$row['day'];
    $totals[] = (int)$row['total'];
}




    // Current Month Property Views
    $currentViews = $conn->query("
    SELECT SUM(views) AS total
    FROM propertiies
    WHERE MONTH(created_at)=MONTH(CURRENT_DATE())
    AND YEAR(created_at)=YEAR(CURRENT_DATE())
    ")->fetch_assoc()['total'] ?? 0;


    // Previous Month Property Views
    $previousViews = $conn->query("
    SELECT SUM(views) AS total
    FROM propertiies
    WHERE MONTH(created_at)=MONTH(DATE_SUB(CURRENT_DATE(),INTERVAL 1 MONTH))
    AND YEAR(created_at)=YEAR(DATE_SUB(CURRENT_DATE(),INTERVAL 1 MONTH))
    ")->fetch_assoc()['total'] ?? 0;


    // Current Month Contact Messages
    $currentMessages = $conn->query("
    SELECT COUNT(*) AS total
    FROM contact_messages
    WHERE MONTH(created_at)=MONTH(CURRENT_DATE())
    AND YEAR(created_at)=YEAR(CURRENT_DATE())
    ")->fetch_assoc()['total'] ?? 0;


    // Previous Month Contact Messages
    $previousMessages = $conn->query("
    SELECT COUNT(*) AS total
    FROM contact_messages
    WHERE MONTH(created_at)=MONTH(DATE_SUB(CURRENT_DATE(),INTERVAL 1 MONTH))
    AND YEAR(created_at)=YEAR(DATE_SUB(CURRENT_DATE(),INTERVAL 1 MONTH))
    ")->fetch_assoc()['total'] ?? 0;


    // Current Month Added Properties
    $currentProperties = $conn->query("
    SELECT COUNT(*) AS total
    FROM propertiies
    WHERE MONTH(created_at)=MONTH(CURRENT_DATE())
    AND YEAR(created_at)=YEAR(CURRENT_DATE())
    ")->fetch_assoc()['total'] ?? 0;


    $currentMonthAppointments = 0;
    $previousMonthAppointments = 0;

    $totalMonths = count($monthly_data);

    if ($totalMonths >= 2) {

        $previousMonthAppointments = $monthly_data[$totalMonths - 2]['total'];
        $currentMonthAppointments = $monthly_data[$totalMonths - 1]['total'];

    } elseif ($totalMonths == 1) {

        $currentMonthAppointments = $monthly_data[0]['total'];

    }

    $appointmentTrend = 0;

    if ($previousMonthAppointments > 0) {

        $appointmentTrend =
            (($currentMonthAppointments - $previousMonthAppointments)
            / $previousMonthAppointments) * 100;

    }


    // Possible Reasons
    $analysis = [];

    if($currentMonthAppointments < $previousMonthAppointments){

        if($currentViews < $previousViews){

            $analysis[] = "Property views decreased compared to last month.";

        }

        if($currentMessages < $previousMessages){

            $analysis[] = "Customer inquiries have decreased.";

        }

        if($currentProperties == 0){

            $analysis[] = "No new properties were added this month.";

        }

        if(empty($analysis)){

            $analysis[] = "Appointment decline may be due to seasonal demand.";

        }

    }


    // Recommendation
    $recommendation = "Current appointment trend is stable.";

    if($currentViews < $previousViews){

        $recommendation =
        "Increase social media marketing to attract more buyers.";

    }
    elseif($currentMessages < $previousMessages){

        $recommendation =
        "Improve customer communication and follow-up.";

    }
    elseif($currentProperties == 0){

        $recommendation =
        "Add more property listings to attract customers.";

    }
    /* ================= PROPERTY REVENUE (DONE DEALS) ================= */
$revenueQuery = $conn->query("
SELECT
    r.property AS title,
    COUNT(*) AS total_done_deals
FROM reservations r
WHERE r.status = 'Done'
GROUP BY r.property
ORDER BY total_done_deals DESC
");

  
   $propertyRevenueLabels = [];
$propertyRevenueValues = [];

while ($row = $revenueQuery->fetch_assoc()) {
    $propertyRevenueLabels[] = $row['title'];              // House name
    $propertyRevenueValues[] = (int)$row['total_done_deals']; // Number of Done Deals
}
    /* ================= CHART DATA ================= */
    $view_query = $conn->query("SELECT title, views FROM propertiies WHERE views > 0 ORDER BY views DESC LIMIT 10");
    $pie_data = [];
    while ($row = $view_query->fetch_assoc()) {
        $pie_data[] = [$row['title'], (int)$row['views']];
    }

    $gender_query = $conn->query("SELECT gender, COUNT(*) AS total FROM reservations GROUP BY gender ORDER BY total DESC");
    $gender_data = [];
    while ($row = $gender_query->fetch_assoc()) {
        $gender_data[] = [$row['gender'], (int)$row['total']];
    }

    $location_query = $conn->query("SELECT location, COUNT(*) AS total FROM reservations GROUP BY location ORDER BY total DESC");
    $location_data = [];
    while ($row = $location_query->fetch_assoc()) {
        $location_data[] = [$row['location'], (int)$row['total']];
    }


    $agent_query = $conn->query("
        SELECT a.username, (COUNT(r.id) + COUNT(t.id)) AS total_done 
        FROM agents a 
        LEFT JOIN reservations r ON r.agent_id = a.id AND r.status='Done' 
        LEFT JOIN transaction_logs t ON t.id = a.id AND t.mode='offline' 
        GROUP BY a.id ORDER BY total_done DESC
    ");
    $agent_labels = [];
    $agent_values = [];
    while ($row = $agent_query->fetch_assoc()) {
        $agent_labels[] = $row['username'];
        $agent_values[] = (int)$row['total_done'];
    }

    // ================= ASSIGN AGENT =================
if (isset($_POST['assign_agent'])) {

    $reservation_id = (int)$_POST['reservation_id'];
    $agent_id = (int)$_POST['agent_id'];

    // Make sure the selected agent exists
    $check = $conn->prepare("SELECT id FROM agents WHERE id = ?");
    $check->bind_param("i", $agent_id);
    $check->execute();
    $check_result = $check->get_result();

    if ($check_result->num_rows === 0) {
        $check->close();

        $_SESSION['error'] = "Selected agent does not exist.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    $check->close();

    // Assign agent to reservation
    $stmt = $conn->prepare("
        UPDATE reservations
        SET 
            agent_id = ?,
            status = 'Confirmed',
            notification_sent = 0
        WHERE id = ?
    ");

    $stmt->bind_param("ii", $agent_id, $reservation_id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['success'] = "Agent assigned successfully.";

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
    if (isset($_POST['done_deal'])) {
        $reservation_id = (int)$_POST['reservation_id'];
        $agent_id = (int)$_SESSION['id'];

        $stmt = $conn->prepare("SELECT fullname, email, property FROM reservations WHERE id=?");
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $stmt->bind_result($client_name, $client_email, $property_name);
        $stmt->fetch();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE reservations SET status='Done', agent_id=? WHERE id=?");
        $stmt->bind_param("ii", $agent_id, $reservation_id);
        $stmt->execute();
        $stmt->close();

        $subject = "Congratulations! Your reservation is Done";
        $body = "Hi $client_name,<br><br>Your reservation for <strong>$property_name</strong> is now completed. Thank you for your payment!<br><br>— Admin Team";
        send_gmail_notification($client_email, $client_name, $subject, $body);

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

  
    // ================= PDF REPORT DOWNLOAD =================
    if (isset($_GET['download_report'])) {

    // Clean ALL previous output before sending PDF
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    $options = new Options();
    $options->set('isRemoteEnabled', true);

    $dompdf = new Dompdf($options);

    
        ?>

        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>ITPH Analytics Report</title>

            <style>
                body{
                    font-family: Arial, sans-serif;
                    color:#222;
                    padding:20px;
                    font-size:13px;
                }

                h1{
                    text-align:center;
                    color:#2563eb;
                    margin-bottom:5px;
                }

                .generated{
                    text-align:center;
                    margin-bottom:25px;
                    color:#666;
                }

                .section{
                    margin-top:35px;
                }

                .section h2{
                    background:#2563eb;
                    color:white;
                    padding:10px;
                    font-size:18px;
                }

                .stats{
                    margin-top:15px;
                }

                .stats div{
                    margin-bottom:8px;
                    padding:8px;
                    background:#f3f4f6;
                    border-left:4px solid #2563eb;
                }

                table{
                    width:100%;
                    border-collapse:collapse;
                    margin-top:15px;
                }

                table, th, td{
                    border:1px solid #ccc;
                }

                th{
                    background:#2563eb;
                    color:white;
                    padding:10px;
                    text-align:left;
                }

                td{
                    padding:8px;
                }

                tr:nth-child(even){
                    background:#f9fafb;
                }
            </style>
        </head>

        <body>

            <h1>ITPH ANALYTICS REPORT</h1>

            <div class="generated">
                Generated on <?= date("F d, Y h:i A") ?>
            </div>

            <!-- SYSTEM STATS -->
            <div class="section">
                <h2>System Statistics</h2>

                <div class="stats">
                    <div><strong>Total Properties:</strong> <?= $total_properties ?></div>
                    <div><strong>Total Users:</strong> <?= $total_users ?></div>
                    <div><strong>Total Reservations:</strong> <?= $total_reservations ?></div>
                    <div><strong>Total Blogs:</strong> <?= $total_blogs ?></div>
                </div>
            </div>

            <!-- ONLINE USERS -->
            <div class="section">
                <h2>Online Users</h2>

                <div class="stats">
                    <div><strong>Customers Online:</strong> <?= $total_online_customers ?></div>
                    <div><strong>Agents Online:</strong> <?= $total_online_agents ?></div>
                    <div><strong>Admins Online:</strong> <?= $total_online_admins ?></div>
                </div>
            </div>

            <!-- MONTHLY RESERVATIONS -->
            <div class="section">
                <h2>Monthly Reservations</h2>

                <table>
                    <tr>
                        <th>Month</th>
                        <th>Total Appointments</th>
                    </tr>

                    <?php foreach ($monthly_data as $md): ?>
                        <tr>
                            <td><?= htmlspecialchars($md['month']) ?></td>
                            <td><?= $md['total'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <!-- PROPERTY VIEWS -->
            <div class="section">
                <h2>Property Views</h2>

                <table>
                    <tr>
                        <th>Property</th>
                        <th>Views</th>
                    </tr>

                    <?php foreach ($pie_data as $pd): ?>
                        <tr>
                            <td><?= htmlspecialchars($pd[0]) ?></td>
                            <td><?= $pd[1] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">
                    Property Revenue Distribution
                </div>

                <div class="card-subtitle">
                    Revenue generated from completed deals
                </div>
            </div>
        </div>

        <div class="card-body">
            <div class="chart-container">
                <canvas id="revenuePieChart"></canvas>
            </div>
        </div>
    </div>
            <!-- GENDER -->
            <div class="section">
                <h2>Gender Analytics</h2>

                <table>
                    <tr>
                        <th>Gender</th>
                        <th>Total</th>
                    </tr>

                    <?php foreach ($gender_data as $gd): ?>
                        <tr>
                            <td><?= htmlspecialchars($gd[0]) ?></td>
                            <td><?= $gd[1] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <!-- LOCATION -->
            <div class="section">
                <h2>Location Analytics</h2>

                <table>
                    <tr>
                        <th>Location</th>
                        <th>Total Appointments</th>
                    </tr>

                    <?php foreach ($location_data as $ld): ?>
                        <tr>
                            <td><?= htmlspecialchars($ld[0]) ?></td>
                            <td><?= $ld[1] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <!-- AGENTS -->
            <div class="section">
                <h2>Agent Performance</h2>

                <table>
                    <tr>
                        <th>Agent</th>
                        <th>Done Deals</th>
                    </tr>

                    <?php for ($i = 0; $i < count($agent_labels); $i++): ?>
                        <tr>
                            <td><?= htmlspecialchars($agent_labels[$i]) ?></td>
                            <td><?= $agent_values[$i] ?></td>
                        </tr>
                    <?php endfor; ?>
                </table>
            </div>
            <!-- BEST SELLING PROPERTIES -->
<div class="section">

    <h2>Best Selling Properties (Done Deals)</h2>

    <table>
        <tr>
            <th>Rank</th>
            <th>Property</th>
            <th>Done Deals</th>
        </tr>

        <?php
        $bestSelling = $conn->query("
            SELECT
                property,
                COUNT(*) AS total_done_deals
            FROM reservations
            WHERE status='Done'
            GROUP BY property
            ORDER BY total_done_deals DESC
        ");

        $rank = 1;

        while($row = $bestSelling->fetch_assoc()):
        ?>

        <tr>
            <td><?= $rank++ ?></td>
            <td><?= htmlspecialchars($row['property']) ?></td>
            <td><?= $row['total_done_deals'] ?></td>
        </tr>

        <?php endwhile; ?>

    </table>

</div>

            <!-- RESERVATIONS -->
            <div class="section">
                <h2>Appointments</h2>

                <table>
                    <tr>
                        <th>Client</th>
                        <th>Email</th>
                        <th>Property</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>

                    <?php
                    $reservation_export = $conn->query("
                        SELECT fullname,email,property,status,created_at
                        FROM reservations
                        ORDER BY id DESC
                    ");

                    while($r = $reservation_export->fetch_assoc()):
                    ?>

                    <tr>
                        <td><?= htmlspecialchars($r['fullname']) ?></td>
                        <td><?= htmlspecialchars($r['email']) ?></td>
                        <td><?= htmlspecialchars($r['property']) ?></td>
                        <td><?= htmlspecialchars($r['status']) ?></td>
                        <td><?= htmlspecialchars($r['created_at']) ?></td>
                    </tr>

                    <?php endwhile; ?>
                </table>
            </div>

            <!-- CONTACT MESSAGES -->
            <div class="section">
                <h2>Contact Messages</h2>

                <table>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Message</th>
                        <th>Date</th>
                    </tr>

                    <?php foreach ($contact_messages as $msg): ?>
                        <tr>
                            <td><?= htmlspecialchars($msg['name']) ?></td>
                            <td><?= htmlspecialchars($msg['email']) ?></td>
                            <td><?= htmlspecialchars($msg['phone']) ?></td>
                            <td><?= htmlspecialchars($msg['message']) ?></td>
                            <td><?= htmlspecialchars($msg['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

        </body>
        </html>

        <?php

        $html = ob_get_clean();

        $dompdf->loadHtml($html);

        $dompdf->setPaper('A4', 'portrait');

        $dompdf->render();

        $dompdf->stream(
            "ITPH_Analytics_Report.pdf",
            ["Attachment" => true]
        );

        exit;
    }

    // Helper functions for sidebar
    function isActive($filename) {
        return basename($_SERVER['PHP_SELF']) === $filename ? 'active' : '';
    }
    function isDropdownActive($filenames) {
        foreach ($filenames as $filename) {
            if (basename($_SERVER['PHP_SELF']) === $filename) return 'open';
        }
        return '';
    }

    // ================= AVAILABLE AGENTS =================
$agents_query = $conn->query("
    SELECT id, username
    FROM agents
    ORDER BY username ASC
");
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Dashboard — ITPH</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <link rel="stylesheet" href="assets/admin.css">
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        
    </head>

    <body>
        <div class="dashboard">
            
            <!-- SIDEBAR -->
            <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
            
            <aside class="sidebar" id="sidebar">
                <div class="sidebar-header">
                    <a href="../admin_side/index.php" class="logo">
                        <div class="logo-icon">
                            <i class="fa-solid fa-building"></i>
                        </div>
                        <div>
                            <div class="logo-text">ITPH <span>Admin</span></div>
                            <div class="logo-sub">Real Estate Management</div>
                        </div>
                    </a>
                </div>

                <nav class="sidebar-nav">
                    <div class="nav-section">Main</div>
                    
                    <a href="../admin_side/index.php" class="nav-link <?= isActive('index.php') ?>">
                        <i class="fa-solid fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>

                    <a href="../admin_side/ad_account.php" class="nav-link <?= isActive('ad_account.php') ?>">
                        <i class="fa-solid fa-user-cog"></i>
                        <span>My Account</span>
                    </a>

                    <div class="nav-section">Management</div>

                    <div class="nav-dropdown <?= isDropdownActive(['customer_ban.php', 'customer_appointments.php']) ?>">
                        <button class="nav-link" onclick="toggleDropdown(this)">
                            <i class="fa-solid fa-users"></i>
                            <span>Manage Customers</span>
                            <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a href="../admin_side/customer_ban.php" class="dropdown-item <?= isActive('customer_ban.php') ?>">
                                Ban / Unban
                            </a>
                            <a href="../admin_side/customer_appointments.php" class="dropdown-item <?= isActive('customer_appointments.php') ?>">
                                Appointments History
                            </a>
                        </div>
                    </div>

                    <div class="nav-dropdown <?= isDropdownActive(['add-property.php', 'update_properties.php']) ?>">
                        <button class="nav-link" onclick="toggleDropdown(this)">
                            <i class="fa-solid fa-house"></i>
                            <span>Properties</span>
                            <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a href="../backends/add-property.php" class="dropdown-item <?= isActive('add-property.php') ?>">
                                Add Property
                            </a>
                            <a href="../admin_side/update_properties.php" class="dropdown-item <?= isActive('update_properties.php') ?>">
                                Update Property
                            </a>
                        </div>
                    </div>

                    <a href="../admin_side/admin_blog_management.php" class="nav-link <?= isActive('admin_blog_management.php') ?>">
                        <i class="fa-solid fa-newspaper"></i>
                        <span>Blog & News</span>
                    </a>

                    <a href="../admin_side/transaction.php" class="nav-link <?= isActive('transaction.php') ?>">
                        <i class="fa-solid fa-money-bill-transfer"></i>
                        <span>Transactions</span>
                    </a>

                    <a href="../admin_side/admin_message.php" class="nav-link <?= isActive('admin_message.php') ?>">
                        <i class="fa-solid fa-envelope"></i>
                        <span>Messages</span>
                    </a>

                    <a href="../admin_side/manage_agent.php" class="nav-link <?= isActive('manage_agent.php') ?>">
                        <i class="fa-solid fa-user-tie"></i>
                        <span>Manage Agents</span>
                    </a>

                    <div class="nav-section">System</div>

                    <a href="../admin_side/audit_log.php" class="nav-link <?= isActive('audit_log.php') ?>">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Audit Log</span>
                    </a>

                    <a href="logout.php" class="nav-link logout">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>Logout</span>
                    </a>
                </nav>
            </aside>

            <!-- MAIN CONTENT -->
            <main class="main-content">
                <!-- TOPBAR -->
                <header class="topbar">
                    <div class="topbar-left">
                        <button class="menu-toggle" onclick="toggleSidebar()">
                            <i class="fa-solid fa-bars"></i>
                        </button>
                        <div>
                            <div class="page-title">Dashboard</div>
                            <div class="breadcrumb">Home / Dashboard</div>
                        </div>
                    </div>

                    <div class="topbar-right">
       <div class="notification">

    <button class="notification-btn">
        <i class="fa-solid fa-bell"></i>

        <?php if($unreadCount > 0): ?>
            <span class="notification-count">
                <?= $unreadCount ?>
            </span>
        <?php endif; ?>
    </button>

    <div class="notification-dropdown">

        <?php if($notifications->num_rows > 0): ?>

            <?php while($row = $notifications->fetch_assoc()): ?>

                <div class="notification-item">

                    <strong><?= htmlspecialchars($row['title']) ?></strong>

                    <p><?= htmlspecialchars($row['message']) ?></p>

                    <small><?= $row['created_at'] ?></small>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="notification-item">
                No notifications.
            </div>

        <?php endif; ?>

    </div>

</div>
<div class="notification-dropdown">

<?php if($notifications->num_rows > 0): ?>

    <?php while($row = $notifications->fetch_assoc()): ?>

        <div class="notification-item">

            <strong><?= htmlspecialchars($row['title']) ?></strong>

            <p><?= htmlspecialchars($row['message']) ?></p>

            <small><?= $row['created_at'] ?></small>

        </div>

    <?php endwhile; ?>

<?php else: ?>

    <div class="notification-item">
        No notifications.
    </div>

<?php endif; ?>

</div>
                        <div class="user-menu">
                            <div class="user-info">
                                <div class="user-name"><?= htmlspecialchars($_SESSION['username']) ?></div>
                                <div class="user-role">Administrator</div>
                            </div>
                            <div class="user-avatar">
                                <?= strtoupper(substr($_SESSION['username'], 0, 1)) ?>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- CONTENT -->
                <div class="content">

                    <!-- STATS -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-icon blue">
                                    <i class="fa-solid fa-house"></i>
                                </div>
                                <span class="stat-trend up">
                                    <i class="fa-solid fa-arrow-trend-up"></i> Active
                                </span>
                            </div>
                            <div class="stat-value"><?= $total_properties ?></div>
                            <div class="stat-label">Total Properties</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-icon green">
                                    <i class="fa-solid fa-users"></i>
                                </div>
                                <span class="stat-trend up">
                                    <i class="fa-solid fa-arrow-trend-up"></i> Growing
                                </span>
                            </div>
                            <div class="stat-value"><?= $total_users ?></div>
                            <div class="stat-label">Registered Users</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-icon amber">
                                    <i class="fa-solid fa-calendar-check"></i>
                                </div>
                                <span class="stat-trend up">
                                    <i class="fa-solid fa-arrow-trend-up"></i> +12%
                                </span>
                            </div>
                            <div class="stat-value"><?= $total_reservations ?></div>
                            <div class="stat-label">Total Appointments</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-header">
                                <div class="stat-icon purple">
                                    <i class="fa-solid fa-newspaper"></i>
                                </div>
                            </div>
                            <div class="stat-value"><?= $total_blogs ?></div>
                            <div class="stat-label">Blog Articles</div>
                        </div>
                    </div>

                    <!-- ONLINE USERS -->
                    <div class="online-bar">
                        <div class="online-pill">
                            <span class="online-dot green"></span>
                            <?= $total_online_customers ?> Customers Online
                        </div>
                        <div class="online-pill">
                            <span class="online-dot blue"></span>
                            <?= $total_online_agents ?> Agents Online
                        </div>
                        <div class="online-pill">
                            <span class="online-dot amber"></span>
                            <?= $total_online_admins ?> Admins Online
                        </div>
                    </div>

                    <!-- CHARTS SECTION -->
                    <div class="charts-grid">
                        <!-- MAIN CHART -->
                    <!-- ================= APPOINTMENT ANALYSIS ================= -->

    <div class="card">

        <div class="card-header">

            <div>

                <div class="card-title">
                    <i class="fa-solid fa-chart-line"></i>
                    Appointment Analysis
                </div>

                <div class="card-subtitle">
                    System generated appointment insights
                </div>

            </div>

        </div>

        <div class="card-body">

            <div class="stats-grid">

                <div class="stat-card">

                    <div class="stat-value">
                        <?= $currentMonthAppointments ?>
                    </div>

                    <div class="stat-label">
                        Current Month
                    </div>

                </div>

                <div class="stat-card">

                    <div class="stat-value">
                        <?= $previousMonthAppointments ?>
                    </div>

                    <div class="stat-label">
                        Previous Month
                    </div>

                </div>

                <div class="stat-card">

                    <div class="stat-value">

                        <?php

                        if($appointmentTrend < 0){

                            echo "<span style='color:#ef4444;'>▼ "
                            . number_format(abs($appointmentTrend),1)
                            . "%</span>";

                        }else{

                            echo "<span style='color:#22c55e;'>▲ "
                            . number_format($appointmentTrend,1)
                            . "%</span>";

                        }

                        ?>

                    </div>

                    <div class="stat-label">
                        Appointment Trend
                    </div>

                </div>

            </div>

            <hr style="margin:25px 0;border:1px solid #334155;">

            <h3 style="margin-bottom:20px;color:#60a5fa;">
        <i class="fa-solid fa-lightbulb"></i>
        Business Insights
    </h3>

    <?php if($currentMonthAppointments < $previousMonthAppointments): ?>

    <div style="
    background:#2b1b1b;
    border-left:5px solid #ef4444;
    padding:18px;
    border-radius:10px;
    margin-bottom:20px;
    ">

    <strong style="color:#f87171;">
    Appointments have decreased by
    <?= number_format(abs($appointmentTrend),1) ?>%
    compared to last month.
    </strong>

    <br><br>

    Possible reasons detected by the system:

    <ul style="margin-top:10px;padding-left:20px;line-height:2;">

    <?php foreach($analysis as $reason): ?>

    <li><?= htmlspecialchars($reason) ?></li>

    <?php endforeach; ?>

    </ul>

    </div>

    <?php else: ?>

    <div style="
    background:#123322;
    border-left:5px solid #22c55e;
    padding:18px;
    border-radius:10px;
    margin-bottom:20px;
    ">

    <strong style="color:#4ade80;">

    Appointments are stable this month.

    </strong>

    <br><br>

    No significant decline has been detected.

    </div>

    <?php endif; ?>
            <hr style="margin:25px 0;border:1px solid #334155;">

            <h3 style="margin-bottom:15px;color:#22c55e;">

                Recommended Action

            </h3>

            <div style="
                background:#0f172a;
                border-left:5px solid #22c55e;
                padding:15px;
                border-radius:8px;
            ">

                <?= htmlspecialchars($recommendation) ?>

            </div>

        </div>

    </div>
                       <div class="card">

    <div class="card-header"
         style="display:flex;justify-content:space-between;align-items:center;">

        <div>
            <div class="card-title">
                Appointments Overview -
                <?= date("F", mktime(0,0,0,$selectedMonth,1)); ?>
            </div>

            <div class="card-subtitle">
                Daily appointments
            </div>
        </div>

        <form method="GET">

            <select
                name="month"
                onchange="this.form.submit()"
                style="padding:8px 12px;border-radius:8px;">

                <?php for($m=1;$m<=12;$m++): ?>

                    <option
                        value="<?= $m ?>"
                        <?= $selectedMonth==$m?'selected':'' ?>>

                        <?= date("F", mktime(0,0,0,$m,1)); ?>

                    </option>

                <?php endfor; ?>

            </select>

        </form>

    </div>

    <div class="card-body">
        <div class="chart-container">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>

</div>

                     
                    </div>

                    <!-- ANALYTICS CARD -->
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <div class="card-title">Analytics Reports</div>
                                <div class="card-subtitle">Detailed breakdown of your data</div>
                            </div>
                            <div class="selector-group">
                                <button class="selector-btn active" onclick="switchChart('views', this)">Views</button>
                                <button class="selector-btn" onclick="switchChart('gender', this)">Gender</button>
                                <button class="selector-btn" onclick="switchChart('location', this)">Location</button>
                                <button class="selector-btn" onclick="switchChart('realtor', this)">Agents</button>
                                <button class="selector-btn" onclick="switchChart('revenue', this)">Revenue</button>
                               
    
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="analyticsChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- RESERVATIONS TABLE -->
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <div class="card-title">Recent Appointments</div>
                                <div class="card-subtitle">Latest booking activity</div>
                            </div>
                            <a href="?download_report=1" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-file-pdf"></i> Download Report
    </a>
                        </div>
                        <div class="card-body">
                            <div class="tabs">
                                <button class="tab active" onclick="switchTab('recent', this)">Recent</button>
                                <button class="tab" onclick="switchTab('done', this)">Completed</button>
                            </div>

                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Client</th>
                                            <th>Property</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="reservationTable">
                                        <?php if ($res_query && $res_query->num_rows > 0):
                                            while ($row = $res_query->fetch_assoc()):
                                               $status_class = match ($row['status']) {
    'Confirmed' => 'badge-confirmed',
    'Waiting Admin Approval' => 'badge-waiting',
    'Done' => 'badge-done',
    default => 'badge-pending'
};

$status_color = match ($row['status']) {
    'Confirmed' => 'blue',
    'Waiting Admin Approval' => 'purple',
    'Done' => 'green',
    default => 'amber'
};
                                        ?>
                                                <tr>
                                                    <td>
                                                        <div class="td-name"><?= htmlspecialchars($row['fullname']) ?></div>
                                                        <div class="td-email"><?= htmlspecialchars($row['email']) ?></div>
                                                    </td>
                                                    <td><?= htmlspecialchars($row['property']) ?></td>
                                                    <td><?= htmlspecialchars($row['created_at'] ?? 'N/A') ?></td>
                                                    <td>
                                                        <span class="badge <?= $status_class ?>">
                                                            <span class="badge-dot" style="background: var(--<?= $status_color ?>)"></span>
                                                            <?= htmlspecialchars($row['status']) ?>
                                                        </span>
                                                    </td>
                                                 <td class="actions-cell">

    <?php if ($row['status'] === 'Pending'): ?>

        <!-- ASSIGN AGENT -->
        <form method="POST" class="assign-agent-form">

            <input
                type="hidden"
                name="reservation_id"
                value="<?= (int)$row['id'] ?>"
            >

            <select
                name="agent_id"
                class="agent-select"
                required
            >
                <option value="">Select Agent</option>

                <?php
                $agents_query = $conn->query("
                    SELECT id, username
                    FROM agents
                    ORDER BY username ASC
                ");

                while ($agent = $agents_query->fetch_assoc()):
                ?>

                    <option value="<?= (int)$agent['id'] ?>">
                        <?= htmlspecialchars($agent['username']) ?>
                    </option>

                <?php endwhile; ?>

            </select>

            <button
                type="submit"
                name="assign_agent"
                class="btn btn-primary btn-sm assign-btn"
            >
                <i class="fa-solid fa-user-plus"></i>
                Assign
            </button>

        </form>


    <?php elseif ($row['status'] === 'Confirmed'): ?>

        <!-- AGENT HAS BEEN ASSIGNED -->

        <?php
        $assigned_agent_name = 'Agent';

        if (!empty($row['agent_id'])) {

            $agent_stmt = $conn->prepare("
                SELECT username
                FROM agents
                WHERE id = ?
                LIMIT 1
            ");

            $agent_stmt->bind_param("i", $row['agent_id']);
            $agent_stmt->execute();
            $agent_result = $agent_stmt->get_result();

            if ($agent_result->num_rows > 0) {
                $agent_data = $agent_result->fetch_assoc();
                $assigned_agent_name = $agent_data['username'];
            }

            $agent_stmt->close();
        }
        ?>

        <div class="assigned-info">
            <span class="assigned-label">
                <i class="fa-solid fa-user-check"></i>
                Assigned
            </span>

            <small>
                <?= htmlspecialchars($assigned_agent_name) ?>
            </small>
        </div>


    <?php elseif ($row['status'] === 'Waiting Admin Approval'): ?>

        <!-- AGENT FORWARDED COMPLETED DEAL -->

        <div class="approval-action">

            <div class="approval-info">

                <span class="approval-badge">
                    <i class="fa-solid fa-clock"></i>
                    Waiting for Admin
                </span>

                <?php if (!empty($row['payment_type'])): ?>

                    <small>
                        Payment:
                        <strong>
                            <?= htmlspecialchars($row['payment_type']) ?>
                        </strong>
                    </small>

                <?php endif; ?>

            </div>

            <!-- ADMIN CLOSE DEAL -->
            <form method="POST">

                <input
                    type="hidden"
                    name="reservation_id"
                    value="<?= (int)$row['id'] ?>"
                >

                <button
                    type="submit"
                    name="approve_done"
                    class="btn btn-success btn-sm"
                    onclick="return confirm('Are you sure you want to close this deal?');"
                >
                    <i class="fa-solid fa-check-double"></i>
                    Close Deal
                </button>

            </form>

        </div>


    <?php elseif ($row['status'] === 'Done'): ?>

        <!-- COMPLETED -->

        <span class="assigned-label completed">
            <i class="fa-solid fa-check"></i>
            Completed
        </span>

    <?php endif; ?>

</td>
                                                </tr>
                                            <?php endwhile;
                                        else: ?>
                                            <tr>
                                                <td colspan="5">
                                                    <div class="empty-state">
                                                        <i class="fa-solid fa-inbox"></i>
                                                        <p>No Appointments found</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    
        <script>
            // ===== SIDEBAR & DROPDOWN =====
            function toggleSidebar() {
                document.getElementById('sidebar').classList.toggle('open');
                document.getElementById('sidebarOverlay').classList.toggle('active');
            }

            function toggleDropdown(button) {
                const dropdown = button.parentElement;
                const wasOpen = dropdown.classList.contains('open');
                document.querySelectorAll('.nav-dropdown.open').forEach(d => {
                    if (d !== dropdown) d.classList.remove('open');
                });
                dropdown.classList.toggle('open', !wasOpen);
            }

            // Auto-open active dropdowns
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.nav-dropdown').forEach(dropdown => {
                    if (dropdown.querySelector('.dropdown-item.active')) {
                        dropdown.classList.add('open');
                    }
                });
            });

            // ===== CHART COLORS =====
            const colors = {
                primary: '#3b82f6',
                primaryLight: 'rgba(59, 130, 246, 0.2)',
                success: '#22c55e',
                warning: '#f59e0b',
                purple: '#a855f7',
                pink: '#ec4899',
                cyan: '#06b6d4',
                text: '#94a3b8',
                grid: '#334155'
            };

            // ===== MONTHLY LINE CHART =====
            const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
            const monthlyGradient = monthlyCtx.createLinearGradient(0, 0, 0, 350);
            monthlyGradient.addColorStop(0, colors.primaryLight);
            monthlyGradient.addColorStop(1, 'rgba(59, 130, 246, 0)');

            new Chart(monthlyCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($days) ?>,
                    datasets: [{
                        label: 'Reservations',
                       data: <?= json_encode($totals) ?>,
                        borderColor: colors.primary,
                        backgroundColor: monthlyGradient,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: colors.primary,
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            titleColor: '#f1f5f9',
                            bodyColor: '#94a3b8',
                            borderColor: '#334155',
                            borderWidth: 1,
                            padding: 12,
                            cornerRadius: 8,
                            displayColors: false
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: colors.text }
                        },
                        y: {
                            grid: { color: colors.grid, drawBorder: false },
                            ticks: { color: colors.text, padding: 10 },
                            beginAtZero: true
                        }
                    }
                }
            });

           
            // ===== ANALYTICS CHART =====
            let analyticsChart = null;
            const analyticsCtx = document.getElementById('analyticsChart').getContext('2d');

            const chartData = {
                views: {
                    labels: [<?php foreach ($pie_data as $pd) { echo "'" . addslashes($pd[0]) . "',"; } ?>],
                    data: [<?php foreach ($pie_data as $pd) { echo $pd[1] . ','; } ?>],
                    label: 'Property Views',
                    color: colors.primary
                },
                gender: {
                    labels: [<?php foreach ($gender_data as $gd) { echo "'" . $gd[0] . "',"; } ?>],
                    data: [<?php foreach ($gender_data as $gd) { echo $gd[1] . ','; } ?>],
                    label: 'Reservations by Gender',
                    color: colors.success
                },
                location: {
                    labels: [<?php foreach ($location_data as $ld) { echo "'" . addslashes($ld[0]) . "',"; } ?>],
                    data: [<?php foreach ($location_data as $ld) { echo $ld[1] . ','; } ?>],
                    label: 'Appointments by Location',
                    color: colors.warning
                },
                realtor: {
                    labels: [<?php echo "'" . implode("','", $agent_labels) . "'"; ?>],
                    data: [<?php echo implode(",", $agent_values); ?>],
                    label: 'Done Deals by Agent',
                    color: colors.purple
                },
                revenue: {
            labels: [<?php echo "'" . implode("','", array_map('addslashes', $propertyRevenueLabels)) . "'"; ?>],
            data: [<?php echo implode(",", $propertyRevenueValues); ?>],
            label: 'Revenue Generated (₱)',
            color: '#10b981'
        }
            };
                                        

            function drawAnalyticsChart(type) {
                const data = chartData[type];
                if (analyticsChart) analyticsChart.destroy();

                analyticsChart = new Chart(analyticsCtx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: data.label,
                            data: data.data,
                            backgroundColor: data.color + '33',
                            borderColor: data.color,
                            borderWidth: 2,
                            borderRadius: 6,
                            borderSkipped: false
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1e293b',
                                titleColor: '#f1f5f9',
                                bodyColor: '#94a3b8',
                                borderColor: '#334155',
                                borderWidth: 1,
                                padding: 12,
                                cornerRadius: 8
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                ticks: { color: colors.text }
                            },
                            y: {
                                grid: { color: colors.grid, drawBorder: false },
                                ticks: { color: colors.text },
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            drawAnalyticsChart('views');

            function switchChart(type, btn) {
                document.querySelectorAll('.selector-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                drawAnalyticsChart(type);
            }

            // ===== TAB SWITCHING =====
            function switchTab(type, btn) {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                btn.classList.add('active');
                window.location.href = '?type=' + type;
            }
            new Chart(document.getElementById("revenuePieChart"),{

        type:"pie",

        data:{

            labels:<?= json_encode($propertyRevenueLabels) ?>,

            datasets:[{

                data:<?= json_encode($propertyRevenueValues) ?>,

                backgroundColor:[
                    "#3b82f6",
                    "#22c55e",
                    "#f59e0b",
                    "#ef4444",
                    "#8b5cf6",
                    "#06b6d4",
                    "#ec4899",
                    "#14b8a6"
                ]

            }]

        },

        options:{
            responsive:true,
            plugins:{
                legend:{
                    position:"bottom"
                }
            }
        }

    });
    const revenueChart = new Chart(document.getElementById('analyticsChart'), {
    type: 'pie',
    data: {
        labels: <?= json_encode($propertyRevenueLabels) ?>,
        datasets: [{
            label: 'Done Deals',
            data: <?= json_encode($propertyRevenueValues) ?>,
            borderWidth: 1
        }]
    }
});
document.addEventListener("DOMContentLoaded", function () {

    const bell = document.querySelector(".notification-btn");
    const dropdown = document.querySelector(".notification-dropdown");

    if (!bell || !dropdown) return;

    bell.addEventListener("click", function(e){

        e.preventDefault();
        e.stopPropagation();

        dropdown.style.display =
            dropdown.style.display === "block"
                ? "none"
                : "block";

    });

    document.addEventListener("click", function(){

        dropdown.style.display = "none";

    });

    dropdown.addEventListener("click", function(e){

        e.stopPropagation();

    });

});
        </script>
    </body>
    </html>