<?php
require_once("../database/db_connect.php");
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
echo "User ID: " . $_SESSION['user_id'] . "<br>";

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirect to login if not authenticated
    exit;
}

// Fetch the sub-unit ID of the logged-in user
$user_id = $_SESSION['user_id']; // Assuming User ID is stored in session
$sub_unit_query = $conn->prepare("SELECT sub_unitid FROM users WHERE matric_or_staff_id = ?");
$sub_unit_query->bind_param('s', $user_id);
$sub_unit_query->execute();
$sub_unit_result = $sub_unit_query->get_result();
$sub_unit_data = $sub_unit_result->fetch_assoc();
$sub_unitid = $sub_unit_data['sub_unitid']; // Fetch the sub-unit ID

// Fetch maintenance requests that match the sub_unitid and required conditions
$query = $conn->prepare("
    SELECT * FROM maintenance_requests 
    WHERE sub_unitid = ? 
    AND status = 'approved' 
    AND maintenance_status = 'approved' 
    AND director_status = 'approved'
");
$query->bind_param('s', $sub_unitid);
$query->execute();
$result = $query->get_result();

// Fetch status descriptions from the request_statuses table
$status_query = $conn->prepare("SELECT status, description FROM request_statuses");
$status_query->execute();
$status_result = $status_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ongoing Maintenance Requests</title>
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

    <h2>Ongoing Maintenance Requests</h2>

    <!-- Requests Table -->
    <table>
        <thead>
            <tr>
                <th>Request ID</th>
                <th>Unit Approval Status</th>
                <th>Maintenance Department's Status</th>
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
					    <a href="../../php/common/view_details_hou.php?request_id=<?php echo urlencode($row['request_id']); ?>" class="view-details-button">View Details</a>
                        <a href="../../php/common/edit_details_hou.php?request_id=<?php echo urlencode($row['request_id']); ?>" class="view-details-button">Edit Details</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Status Descriptions -->
    <h3>Maintenance Status Descriptions</h3>
    <table>
        <thead>
            <tr>
                <th>Status</th>
                <th>Meaning</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($status_row = $status_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($status_row['status']); ?></td>
                    <td><?php echo htmlspecialchars($status_row['description']); ?></td>
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
