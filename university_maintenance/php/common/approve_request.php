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

// Handle Approve/Reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve'])) {
        // Check if comments are present (meaning the user is trying to reject as well)
        if (!empty($_POST['comments'])) {
            echo "<script>alert('Rejection comments should not be filled out when approving the request.');</script>";
            header("Refresh: 2.5; URL=" . $_SERVER['HTTP_REFERER']); // Redirect back to the page after 2.5 seconds
            exit;
        }

        // Update the request status to "approved" and maintenance_status to "submitted"
        $updateQuery = $conn->prepare("UPDATE maintenance_requests SET status = 'approved', maintenance_status = 'submitted', updated_at = NOW() WHERE request_id = ?");
        $updateQuery->bind_param('s', $request_id);
        $updateQuery->execute();
        
        // Success message and redirection
        echo '<div id="message" style="text-align:center; padding:20px; background-color: #4CAF50; color: white;">
                <strong>Request Approved Successfully!</strong> You will be redirected shortly.
              </div>';
        echo '<script>
                setTimeout(function() {
                    window.location.href = "../../php/dashboards/hou_dashboard.php"; // Redirect to HOU Dashboard
                }, 2000); // 2 seconds delay
              </script>';
        exit;
    } elseif (isset($_POST['reject'])) {
        // Ensure rejection includes comments
        $comments = $_POST['comments'] ?? '';
        if (empty($comments)) {
            echo "<script>alert('Rejection comments are required.');</script>";
        } else {
            // Update the request status to "rejected" and save comments, keeping maintenance_status null
            $updateQuery = $conn->prepare("UPDATE maintenance_requests SET status = 'rejected', comments = ?, updated_at = NOW() WHERE request_id = ?");
            $updateQuery->bind_param('ss', $comments, $request_id);
            $updateQuery->execute();
            
            // Success message and redirection
            echo '<div id="message" style="text-align:center; padding:20px; background-color: #f44336; color: white;">
                    <strong>Request Rejected Successfully!</strong> You will be redirected shortly.
                  </div>';
            echo '<script>
                    setTimeout(function() {
                        window.location.href = "../../php/dashboards/hou_dashboard.php"; // Redirect to HOU Dashboard
                    }, 1500); // 2 seconds delay
                  </script>';
            exit;
        }
    }
}
?>
