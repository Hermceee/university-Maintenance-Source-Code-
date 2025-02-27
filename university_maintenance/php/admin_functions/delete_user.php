<?php
require_once("../database/db_connect.php");
session_start();

// Check if the user is logged in and has the right role
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // Connect to the database and check if the user is associated with any maintenance requests
    $sql = "SELECT COUNT(*) FROM maintenance_requests WHERE user_id = ? OR hou_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $user_id, $user_id);  // assuming user_id and hou_id are strings
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_row();

    if ($row[0] > 0) {
        // The user is associated with maintenance requests, show error message
        echo '<div style="background-color: #f44336; color: white; padding: 20px; text-align: center; font-weight: bold;">
                This user is currently associated with one or more maintenance requests, and therefore cannot be deleted to preserve data integrity. To deactivate this user, you can change their role to "Inactive" instead.
              </div>';
        
        // JavaScript to redirect back to user_management.php after 2 seconds
        echo '<script>
                setTimeout(function() {
                    window.location.href = "user_management.php";
                }, 2000); // Redirect after 2 seconds
              </script>';

        exit();
    }

    // Proceed with user deletion if not associated with any requests
    $delete_sql = "DELETE FROM users WHERE matric_or_staff_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param('s', $user_id);
    if ($delete_stmt->execute()) {
        // Successfully deleted, redirect back to user management
        header("Location: user_management.php?delete=success");
        exit();
    } else {
        echo "Error deleting user: " . $conn->error;
    }
} else {
    echo "No user ID provided.";
}
?>
