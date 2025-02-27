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

// Fetch the unit ID of the logged-in HOU
$user_id = $_SESSION['user_id']; // Assuming User ID is stored in session
$unit_query = $conn->prepare("SELECT unit_id FROM users WHERE matric_or_staff_id = ?");
$unit_query->bind_param('s', $user_id);
$unit_query->execute();
$unit_result = $unit_query->get_result();
$unit_data = $unit_result->fetch_assoc();
$unit_id = $unit_data['unit_id']; // Fetch the unit ID

// Fetch completed maintenance requests approved by the HOU's unit
$query = $conn->prepare("SELECT * FROM maintenance_requests WHERE unit_id = ? AND status = 'approved' AND maintenance_status = 'completed'");
$query->bind_param('s', $unit_id);
$query->execute();
$result = $query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completed Maintenance Requests</title>
    <link rel="stylesheet" href="../../css/normal_functions/pending_approvals.css">
</head>
<body>
    <!-- Header Section -->
    <header>
        <img src="../../images/UI LOGO.jpeg" alt="University Logo" class="logo">
        <h1>University of Ibadan Maintenance Department</h1>
        <div class="header-right">
            <a href="../../php/dashboards/hou_dashboard.php" class="Menu">MENU</a>
        </div>
    </header>

    <h2>Completed Maintenance Requests</h2>

    <!-- Requests Table -->
    <table>
        <thead>
            <tr>
                <th>Request ID</th>
                <th>Approval Status</th>
                <th>Maintenance Status</th>
                <th>Action</th> <!-- Added column for the "View Details" button -->
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['request_id']); ?></td> <!-- Request ID -->
                    <td><?php echo htmlspecialchars($row['status']); ?></td> <!-- Approval Status -->
                    <td><?php echo htmlspecialchars($row['maintenance_status']); ?></td> <!-- Maintenance Status -->
                    <td>
                        <a href="../../php/common/view_details.php?request_id=<?php echo urlencode($row['request_id']); ?>" class="view-details-button">View Details</a>
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
