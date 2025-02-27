<?php
require_once("../database/db_connect.php");
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
echo "User ID: " . $_SESSION['user_id'] . "<br>";
echo "Role: " . $_SESSION['role'] . "<br>";
// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirect to login if not authenticated
    exit;
}

// Fetch requests submitted by the logged-in user with 'pending approval' or 'rejected' status
$user_id = $_SESSION['user_id']; // Assuming User ID is stored in session
$query = $conn->prepare("SELECT * FROM maintenance_requests WHERE user_id = ? AND (status = 'pending approval' OR status = 'rejected')");
$query->bind_param('s', $user_id);
$query->execute();
$result = $query->get_result();
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
    <h2>MY MAINTENANCE REQUESTS</h2>

    <!-- Requests Table -->
    <table>
        <thead>
            <tr>
                <th>Request ID</th>
                <th>Approval Status</th>
                <th>Action</th> <!-- Added column for the "View Details" and "Edit" button -->
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['request_id']); ?></td> <!-- Request ID -->
                    <td><?php echo htmlspecialchars($row['status']); ?></td> <!-- Status -->
                    <td>
                        <a href="../../php/common/view_details.php?request_id=<?php echo urlencode($row['request_id']); ?>" class="view-details-button">View Details</a>
                        <?php if ($row['status'] == 'rejected'): ?>
                            <a href="../../php/common/edit_request.php?request_id=<?php echo urlencode($row['request_id']); ?>" class="edit-button">Edit</a>
                        <?php endif; ?>
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
