<?php
session_start();
// Clear session variables
$_SESSION = array();
// Destroy the session
session_destroy();

// Redirect back to the dashboard so they can browse and see the Login button
header("Location: dashboard.php");
exit();
?>