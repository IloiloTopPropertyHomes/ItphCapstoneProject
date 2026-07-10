<?php
require_once __DIR__ . '/../vendor/autoload.php';

$client = new Google_Client();

$client->setClientId('797067537916-bjupepenkgbbfpems4ifdvjl4esb4gh7.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('http://localhost/recapstone/user_side/google_callback.php');

$client->addScope('email');
$client->addScope('profile');

