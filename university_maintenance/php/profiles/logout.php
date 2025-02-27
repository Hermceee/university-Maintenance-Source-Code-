<?php
session_start(); // Start the session
require_once("../database/db_connect.php"); // Include your database connection

// Ensure the user is logged in (check if session exists)
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Prepare the SQL statement to update the logout time with the new column names
    $logout_query = $conn->prepare("UPDATE login_security SET logout_time = NOW() WHERE userid = ? AND logout_time IS NULL");
    $logout_query->bind_param('s', $user_id); // Bind the user ID to the query
    $logout_query->execute();
    $logout_query->close();
}

// Clear all session variables
session_unset();

// Destroy the session
session_destroy();

// Prevent caching of the login page by sending headers
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

// Ensure the session data is completely cleared before redirecting
session_regenerate_id(true); // Regenerate session ID to avoid session fixation attacks

// Redirect to login page after logout
header("Location: ../../php/profiles/login.php");
exit();
?>
