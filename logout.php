<?php
session_start();

// Clear all session data
session_unset();
session_destroy();

// Prevent browser from caching the previous page
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

// Redirect to login page
header("Location: index.php");
exit;
?>
