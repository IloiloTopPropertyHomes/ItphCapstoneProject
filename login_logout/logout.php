<?php
session_start(); // Start the session

// Clear all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect the user to login page
header("Location: index.php");
exit;
?>