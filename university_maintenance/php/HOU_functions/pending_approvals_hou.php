<?php
// Retrieve correct session name from cookies
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include("../../php/database/db_connect.php"); // Include database connection
// Ensure the user is logged in and is an HOU
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'HOU') {
    header('Location: login.php'); // Redirect to login if not authenticated or not an HOU
    exit;
}

// Fetch the logged-in user's unit_id and hou_id
$user_id = $_SESSION['user_id']; // Assuming User ID is stored in session
$query = $conn->prepare("SELECT unit_id FROM users WHERE matric_or_staff_id = ?");
$query->bind_param('s', $user_id);
$query->execute();
$userResult = $query->get_result();
$userRow = $userResult->fetch_assoc();
$user_unit_id = $userRow['unit_id'];

// Fetch pending maintenance requests for the user's unit
$requestQuery = $conn->prepare("SELECT * FROM maintenance_requests WHERE unit_id = ? AND hou_id = ? AND status = 'pending approval'");
$requestQuery->bind_param('ss', $user_unit_id, $user_id);
$requestQuery->execute();
$requestResult = $requestQuery->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Maintenance Requests</title>
    <link rel="stylesheet" href="../../css/normal_functions/pending_approvals.css">
</head>
<body>

    <!-- Header Section -->
    <header>
            <img src="../../images/UI LOGO.jpeg" alt="University Logo" class="logo">
        </a>
        <h1>University of Ibadan Maintenance Department</h1>
        <div class="header-right">
            <a href="../../php/dashboards/HOU_dashboard.php" class="Menu">MENU</a>
        </div>
    </header>

    <h2>Pending Maintenance Requests</h2>

    <!-- Requests Table -->
    <table>
        <thead>
            <tr>
                <th>Request ID</th>
                <th>Action</th> <!-- Added column for the "Approve", "Reject", and "View Details" buttons -->
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $requestResult->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['request_id']); ?></td> <!-- Request ID -->
                    <td>
                        <!-- Approve, Reject, and View Details buttons -->
                        <a href="../../php/common/view_details_hou.php?request_id=<?php echo urlencode($row['request_id']); ?>" class="view-details-button">check</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Footer Section -->
    <footer>
        <p>&copy; 2024 University Maintenance System. All rights reserved.</p>
    </footer>

</body>
</html>
