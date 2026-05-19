<?php
require_once __DIR__ . '/../vendor/autoload.php';

$google_client = new Google_Client();
$google_client->setClientId('797067537916-be6fnm7qjmr8j7qh5l0330hcquvtnlll.apps.googleusercontent.com');
$google_client->setClientSecret('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');
$google_client->setRedirectUri('http://localhost/recapstone/user_side/google_callback.php');

$google_client->addScope('email');
$google_client->addScope('profile');