<?php
session_start();
if (!isset($_SESSION['id'])) { header('Location: login.php'); exit; }
require_once '../includes/config.php';
require_once '../includes/db.php';