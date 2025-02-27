<?php
session_start(); // Ensure this is at the very top of the file
include("../../php/common/session_manager.php"); // Include session management script
include("../../php/database/db_connect.php"); // Include database connection
// Debugging output
echo "Session ID: " . session_id() . "<br>";
echo "User ID: " . $_SESSION['user_id'] . "<br>";
echo "Role: " . $_SESSION['role'] . "<br>";

// Check if the user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../php/profiles/login.php"); // Redirect to login if not logged in
    exit(); // Ensure that the rest of the script does not run
}

session_regenerate_id(true); // Regenerate session ID to avoid session fixation

// Fetch sub_unitid from the database
$user_id = $_SESSION['user_id'];
$sql = "SELECT sub_unitid FROM users WHERE matric_or_staff_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$has_sub_unitid = !empty($row['sub_unitid']); // Check if sub_unitid is present

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOU Dashboard | University of Ibadan Maintenance Department</title>
    <link rel="stylesheet" href="../../css/dashboards/normal_dashboard.css">
    <style>
        .button.disabled {
            pointer-events: none; /* Disable click */
            opacity: 0.5; /* Greyed out effect */
        }
    </style>
</head>
<body>
    <header>
        <img src="../../images/UI LOGO.jpeg" alt="University Logo" class="logo">
        <h1>UIMD</h1>
        <div class="header-right">
            <a href="../../php/profiles/edit_profile.php" class="profile-icon">
                <img src="../../images/profile_icon.png" alt="Profile Icon">
            </a>
            <a href="../../php/profiles/logout.php" class="logout-button">LOG OUT</a>
        </div>
    </header>
        
    <main>
        <h2>SUB-UNIT'S DASHBOARD</h2>

        <?php if (!$has_sub_unitid): ?>
            <p style="color: red; text-align: center;">You need to be assigned a sub_unitid before you can use the system's functionality. Please contact the maintenance department, you can access this from the homepage.</p>
        <?php endif; ?>

        <section class="user-functions">
            <a href="../../php/Sub-unit_functions/track_request.php" class="button <?= $has_sub_unitid ? '' : 'disabled' ?>">view Ongoing Requests</a>
            <a href="../../php/HOU_functions/hou_requests_history.php" class="button <?= $has_sub_unitid ? '' : 'disabled' ?>">Requests History</a>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 University Maintenance System. All rights reserved.</p>
    </footer>
</body>
</html>
