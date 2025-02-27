<?php
require_once("../database/db_connect.php");
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirect to login if not authenticated
    exit;
}

// Get the request ID from the POST data
if (!isset($_POST['request_id'])) {
    echo "No request ID provided.";
    exit;
}

$request_id = $_POST['request_id']; // Use POST data instead of GET
$user_id = $_SESSION['user_id']; // Assuming User ID is stored in session

// Fetch the maintenance request details
$query = $conn->prepare("SELECT * FROM maintenance_requests WHERE request_id = ? AND (user_id = ? OR hou_id = ?)");
$query->bind_param('sss', $request_id, $user_id, $user_id); // Check if the user is either the request submitter or the HOU
$query->execute();
$request_result = $query->get_result();

if ($request_result->num_rows === 0) {
    echo "No maintenance request found or you do not have permission to view it.";
    exit;
}

$request_details = $request_result->fetch_assoc();

// Handle Reject action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject'])) {
    // Ensure rejection includes comments
    $comments = isset($_POST['comments']) ? trim($_POST['comments']) : '';
    
    // Check if comments are provided
    if (empty($comments)) {
        echo "<script>alert('Rejection comments are required.');</script>";
        header("Refresh: 2.5; URL=" . $_SERVER['HTTP_REFERER']); // Redirect back to the previous page
        exit;
    } else {
        // Update the request status to "rejected" and save comments
        $updateQuery = $conn->prepare("UPDATE maintenance_requests SET status = 'rejected', comments = ?, updated_at = NOW() WHERE request_id = ?");
        $updateQuery->bind_param('ss', $comments, $request_id);
        
        // Execute the query
        if ($updateQuery->execute()) {
            // Display success message and redirect
            echo '<div id="message" style="text-align:center; padding:20px; background-color: #F44336; color: white;">
                    <strong>Request Rejected Successfully!</strong>
                  </div>';
            echo '<script>
                    setTimeout(function() {
                        window.location.href = "../../php/dashboards/hou_dashboard.php"; // Redirect to HOU Dashboard
                    }, 1500); // 1.5 seconds delay
                  </script>';
        } else {
            // Capture any database errors
            echo "Error updating record: " . $updateQuery->error;
        }
    }
}
?>
