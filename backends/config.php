<?php
/*
------------------------------------
LOAD ENV (OPTIONAL BUT RECOMMENDED)
------------------------------------
If you use a .env file, uncomment this
------------------------------------
*/
// require_once __DIR__ . '/vendor/autoload.php';
// $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
// $dotenv->load();


/*
------------------------------------
DATABASE SETTINGS
------------------------------------
Use environment variables if available
------------------------------------
*/

$DB_HOST = $_ENV['DB_HOST'] ?? "localhost";
$DB_USER = $_ENV['DB_USER'] ?? "root"; // NOT root
$DB_PASS = $_ENV['DB_PASS'] ?? "";
$DB_NAME = $_ENV['DB_NAME'] ?? "secure_app";


/*
------------------------------------
ENCRYPTION KEY
------------------------------------
MUST be 32 characters for AES-256
------------------------------------
*/

$ENCRYPTION_KEY = $_ENV['ENCRYPTION_KEY'] ?? "CHANGE_THIS_TO_A_SECURE_32_CHAR_KEY!";


/*
------------------------------------
API KEY (HIDE THIS IN .ENV)
------------------------------------
*/

$api_key = $_ENV['API_KEY'] ?? "HIDE_THIS_IN_ENV";


/*
------------------------------------
DATABASE CONNECTION FUNCTION
------------------------------------
Zero Trust: No info leakage
------------------------------------
*/

function get_db_connection(){

    global $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME;

    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

    if ($conn->connect_error) {
        error_log("Database Error: " . $conn->connect_error);
        die("Something went wrong. Please try again later.");
    }

    return $conn;
}


/*
------------------------------------
ENCRYPT DATA FUNCTION (SECURE)
------------------------------------
Uses RANDOM IV (Zero Trust)
------------------------------------
*/

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

/*
------------------------------------
INPUT SANITIZATION
------------------------------------
Zero Trust: Never trust input
------------------------------------
*/

function sanitize_input($input){

    if (!isset($input)) return '';

    $input = trim($input);
    $input = strip_tags($input); // remove HTML
    $input = htmlspecialchars($input, ENT_QUOTES, "UTF-8");

    return $input;
}


/*
------------------------------------
LOGGING FUNCTION
------------------------------------
Zero Trust: Monitor everything
------------------------------------
*/

function log_event($message){

    $log_file = __DIR__ . "/security.log";
    $time = date("Y-m-d H:i:s");

    file_put_contents(
        $log_file,
        "[$time] $message" . PHP_EOL,
        FILE_APPEND
    );
}


/*
------------------------------------
OPTIONAL: RATE LIMIT (BASIC)
------------------------------------
Prevent spam / abuse
------------------------------------
*/

function rate_limit($key, $limit = 5, $seconds = 60){

    session_start();

    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = [
            'count' => 1,
            'time' => time()
        ];
        return true;
    }

    $data = $_SESSION['rate_limit'][$key];

    if (time() - $data['time'] > $seconds) {
        $_SESSION['rate_limit'][$key] = [
            'count' => 1,
            'time' => time()
        ];
        return true;
    }

    if ($data['count'] >= $limit) {
        return false;
    }

    $_SESSION['rate_limit'][$key]['count']++;
    return true;
}
function log_transaction($conn, $user_id, $role, $action, $details = '') {
    $stmt = $conn->prepare("
        INSERT INTO transaction_logs (user_id, role, action, details) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bind_param("isss", $user_id, $role, $action, $details);
    $stmt->execute();
    $stmt->close();
}
function logAuth($conn, $user_id, $role, $fullname, $email, $status, $method, $session_status) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    $stmt = $conn->prepare("
        INSERT INTO auth_logs 
        (user_id, role, fullname, email, login_status, login_method, session_status, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("issssssss",
        $user_id,
        $role,
        $fullname,
        $email,
        $status,
        $method,
        $session_status,
        $ip,
        $user_agent
    );

    $stmt->execute();
    $stmt->close();
}