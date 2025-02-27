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

$user_id = $_SESSION['user_id']; // Get the logged-in user ID from session

// Fetch the user's role from the database
$role_query = $conn->prepare("SELECT role FROM users WHERE matric_or_staff_id = ?");
$role_query->bind_param('s', $user_id);
$role_query->execute();
$role_result = $role_query->get_result();
$user_role = $role_result->fetch_assoc()['role'] ?? null;

// Check if the user is a director or admin
$isDirectorOrAdmin = ($user_role === 'director' || $user_role === 'admin');

// Fetch maintenance requests with status 'approved' and maintenance_status 'submitted'
$query = $conn->prepare("SELECT * FROM maintenance_requests WHERE status = 'approved' AND maintenance_status = 'submitted' AND (director_status IS NULL OR director_status = 'rejected')");
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
            <a href="../../php/dashboards/system_admin_dashboard.php" class="Menu">MENU</a>
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
                        <a href="../../php/common/view_details_directors.php?request_id=<?php echo urlencode($row['request_id']); ?>" class="view-details-button">View Details</a>
                        <?php if ($isDirectorOrAdmin): ?>
                            <a href="../../php/common/edit_details_admin.php?request_id=<?php echo urlencode($row['request_id']); ?>" class="view-details-button">Edit Details</a>
                        <?php endif; ?>
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
