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

// Fetch requests submitted by the logged-in user with the specified status
$user_id = $_SESSION['user_id']; // Assuming User ID is stored in session
$request_query = $conn->prepare("SELECT * FROM maintenance_requests WHERE user_id = ? AND status = 'approved'");
$request_query->bind_param('s', $user_id);
$request_query->execute();
$requests_result = $request_query->get_result();

// Fetch status descriptions from the database
$status_query = $conn->prepare("SELECT * FROM request_statuses");
$status_query->execute();
$statuses_result = $status_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Maintenance Requests</title>
    <link rel="stylesheet" href="../../css/normal_functions/pending_approvals.css">
</head>
<body>
    <!-- Header Section -->
    <header>
        <img src="../../images/UI LOGO.jpeg" alt="University Logo" class="logo">
        <h1>University of Ibadan Maintenance Department</h1>
        <div class="header-right">
            <a href="../../php/dashboards/normal_dashboard.php" class="Menu">MENU</a>
        </div>
    </header>

    <h2>ONGOING REQUESTS</h2>

    <!-- Requests Table -->
    <table>
        <thead>
            <tr>
                <th>Request ID</th>
                <th>Unit Approval Status</th>
                <th>Maintenance Department's Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $requests_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['request_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                    <td><?php echo htmlspecialchars($row['maintenance_status']); ?></td>
                    <td>
                        <a href="../../php/common/view_details.php?request_id=<?php echo urlencode($row['request_id']); ?>" class="view-details-button">View Details</a>
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
            <?php while ($row = $statuses_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                    <td><?php echo htmlspecialchars($row['description']); ?></td>
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
