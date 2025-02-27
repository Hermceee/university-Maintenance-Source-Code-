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

// Fetch the request ID from the URL
if (!isset($_GET['request_id'])) {
    echo "No request ID provided.";
    exit;
}

$request_id = $_GET['request_id'];
$user_id = $_SESSION['user_id']; // Assuming User ID is stored in session

// Fetch the maintenance request details for the user
$query = $conn->prepare("SELECT * FROM maintenance_requests WHERE request_id = ? AND user_id = ?");
$query->bind_param('ss', $request_id, $user_id); // Check if the user is the request submitter
$query->execute();
$request_result = $query->get_result();

if ($request_result->num_rows === 0) {
    echo "No maintenance request found or you do not have permission to view it.";
    exit;
}

$request_details = $request_result->fetch_assoc();

// Fetch associated files for the request
$file_query = $conn->prepare("SELECT * FROM maintenance_requests_files WHERE request_id = ?");
$file_query->bind_param('s', $request_id);
$file_query->execute();
$file_result = $file_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Details</title>
    <link rel="stylesheet" href="../../css/common/view_details.css">
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

    <h2>Request Details</h2>

    <!-- Maintenance Request Details -->
    <section class="request-details">
        <table>
            <tr>
                <th>Request ID</th>
                <td><?php echo htmlspecialchars($request_details['request_id']); ?></td>
            </tr>
            <tr>
                <th>Creator's User ID</th>
                <td><?php echo htmlspecialchars($request_details['user_id']); ?></td>
            </tr>
            <tr>
                <th>Head of Unit ID</th>
                <td><?php echo htmlspecialchars($request_details['hou_id']); ?></td>
            </tr>
            <tr>
                <th>Sub-department</th>
                <td><?php echo htmlspecialchars($request_details['sub_department_name']); ?></td>
            </tr>
            <tr>
                <th>Issue Type</th>
                <td><?php echo htmlspecialchars($request_details['issue_name']); ?></td>
            </tr>
            <tr>
                <th>Description</th>
                <td class="normal-case"><?php echo htmlspecialchars($request_details['description']); ?></td>
            </tr>
            <tr>
                <th>Head Of Unit's Approval Status</th>
                <td><?php echo htmlspecialchars($request_details['status']); ?></td>
            </tr>
            <tr>
                <th>Maintenance Department's Status</th> <!-- Added maintenance status field -->
                <td><?php echo htmlspecialchars($request_details['maintenance_status']); ?></td>
            </tr>
            <tr>
                <th>Created At</th>
                <td><?php echo htmlspecialchars($request_details['created_at']); ?></td>
            </tr>
            <tr>
                <th>Updated At</th>
                <td><?php echo htmlspecialchars($request_details['updated_at']); ?></td>
            </tr>
            <tr>
                <th>Unit ID</th>
                <td><?php echo htmlspecialchars($request_details['unit_id']); ?></td>
            </tr>
        </table>
    </section>

    <!-- Comments Section -->
    <h2>Comments</h2>
    <section class="comments-section">
        <p><?php echo htmlspecialchars($request_details['comments'] ?? 'No comments available for this request.'); ?></p>
    </section>

    <!-- Associated Files -->
    <h2>Attachments</h2>
    <section class="attachments">
        <?php if ($file_result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>File ID</th>
                        <th>File Name</th>
                        <th>View</th>
                        <th>Download</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($file = $file_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($file['file_id']); ?></td>
                            <td><?php echo htmlspecialchars($file['file_name']); ?></td>
                            <td>
                                <a href="<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank" class="view-file-button">View</a>
                            </td>
                            <td>
                                <a href="../../php/common/download_file.php?file=<?php echo urlencode($file['file_path']); ?>" class="download-file-button">Download</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No attachments available for this request.</p>
        <?php endif; ?>
    </section>

    <!-- Footer Section -->
    <footer>
        <p>&copy; 2024 University Maintenance System. All rights reserved.</p>
    </footer>
</body>
</html>
