<?php
session_start();

require_once '../backends/config.php';
require_once __DIR__ . '/google_config.php';

$conn = get_db_connection();

if (isset($_GET['code'])) {
    $token = $google_client->fetchAccessTokenWithAuthCode($_GET['code']);

    if (!isset($token['error'])) {
        $google_client->setAccessToken($token['access_token']);

        $google_service = new Google_Service_Oauth2($google_client);
        $google_account_info = $google_service->userinfo->get();

        $google_id = $google_account_info->id;
        $fullname = $google_account_info->name;
        $email = $google_account_info->email;
        $profile_picture = $google_account_info->picture;

        $stmt = $conn->prepare("SELECT * FROM customers WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            $update = $conn->prepare("UPDATE customers SET google_id = ?, profile_picture = ? WHERE email = ?");
            $update->bind_param("sss", $google_id, $profile_picture, $email);
            $update->execute();

            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['email'] = $user['email'];
        } else {
            $random_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

            $insert = $conn->prepare("INSERT INTO customers (fullname, email, password, google_id, profile_picture) VALUES (?, ?, ?, ?, ?)");
            $insert->bind_param("sssss", $fullname, $email, $random_password, $google_id, $profile_picture);
            $insert->execute();

            $new_user_id = $conn->insert_id;

            session_regenerate_id(true);
            $_SESSION['user_id'] = $new_user_id;
            $_SESSION['fullname'] = $fullname;
            $_SESSION['email'] = $email;
        }

        header("Location: ../index.php");
        exit();
    }
}

header("Location: login.php?error=google_login_failed");
exit();