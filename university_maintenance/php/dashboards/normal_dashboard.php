<?php
session_start(); // Ensure this is at the very top of the file

include("../../php/database/db_connect.php"); // Include database connection

// Check if the session is valid for the current user
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../php/profiles/login.php");
    exit();
}


// Ensure session data is valid for the current user
if ($_SESSION['role'] !== 'Normal') {
    // Redirect if the role is not 'Normal', meaning it's an invalid session for this page
    header("Location: ../../php/profiles/logout.php");
    exit();
}

session_regenerate_id(true); // Regenerate session ID for security

// Fetch the unit_id from the database
$user_id = $_SESSION['user_id'];
$sql = "SELECT unit_id FROM users WHERE matric_or_staff_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$has_unitid = !empty($row['unit_id']); // Check if unit_id is present

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | University of Ibadan Maintenance Department</title>
    <link rel="stylesheet" href="../../css/dashboards/normal_dashboard.css">
    <style>
        .button.disabled {
            pointer-events: none; /* Disable click */
            opacity: 0.5; /* Greyed out effect */
        }
        .warning-message {
            color: red;
            font-weight: bold;
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <header>
        <a href="../html/profiles/index.html">
            <img src="../../images/UI LOGO.jpeg" alt="University Logo" class="logo">
        </a>
        <h1>University of Ibadan Maintenance Department</h1>
        <div class="header-right">
            <a href="../php/edit_profile.php" class="profile-icon">
                <img src="../../images/profile_icon.png" alt="Profile Icon">
            </a>
            <a href="../../php/profiles/logout.php" class="logout-button">LOG OUT</a>
        </div>
    </header>
        
    <main>
        <h2>USER'S DASHBOARD</h2>

        <?php if (!$has_unitid): ?>
            <p class="warning-message">You need to be assigned a unit ID before you can access the system's functionality. Please contact the maintenance department for assistance, you can access this from the homepage.</p>
        <?php endif; ?>

        <section class="user-functions">
            <a href="../../php/normal_functions/create_request.php" class="button <?= $has_unitid ? '' : 'disabled' ?>">Create Request</a>
            <a href="../../php/normal_functions/pending_approvals.php" class="button <?= $has_unitid ? '' : 'disabled' ?>">Pending Approvals</a>
            <a href="../../php/normal_functions/track_requests.php" class="button <?= $has_unitid ? '' : 'disabled' ?>">Track Ongoing Requests</a>
            <a href="../../php/normal_functions/requests_history.php" class="button <?= $has_unitid ? '' : 'disabled' ?>">Requests History</a>
        </section>
    </main>

    <footer>
        <p>&copy; 2024 University Maintenance System. All rights reserved.</p>
    </footer>
</body>
</html>
