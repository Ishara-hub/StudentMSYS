<?php
require_once 'includes/auth.php';

// Destroy the session
logout();

// Redirect to login page
header("Location: login.php");
exit();
?>