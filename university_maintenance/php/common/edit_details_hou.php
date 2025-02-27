<?php
require_once("../database/db_connect.php");
include("../../php/common/session_manager.php"); // Include session management script

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

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comments'])) {
    $new_comment = trim($_POST['comments']);

    $insert_query = $conn->prepare("UPDATE maintenance_requests SET comments = ? WHERE request_id = ?");
    $insert_query->bind_param('ss', $new_comment, $request_id);

    if ($insert_query->execute()) {
        echo "<!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Updating...</title>
            <style>
                body {
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    margin: 0;
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                }
                .message {
                    text-align: center;
                    background: #e9ffe9;
                    border: 1px solid #b3ffb3;
                    padding: 20px;
                    border-radius: 10px;
                    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
                    color: #228B22;
                }
                .message h1 {
                    margin: 0;
                    font-size: 24px;
                }
                .message p {
                    margin: 10px 0 0;
                    font-size: 16px;
                }
            </style>
        </head>
        <body>
            <div class='message'>
                <h1>Comment updated successfully!</h1>
                <p>Redirecting you to the dashboard...</p>
            </div>
            <script>
                setTimeout(function() {
                    window.location.href = '../../php/HOU_functions/track_request.php'; // Replace with the correct path to your dashboard
                }, 1500);
            </script>
        </body>
        </html>";
        exit;
    } else {
        echo "<script>alert('Failed to update the comment. Please try again.');</script>";
    }
}

// Fetch the maintenance request details
$query = $conn->prepare("SELECT * FROM maintenance_requests WHERE request_id = ?");
$query->bind_param('s', $request_id);
$query->execute();
$request_result = $query->get_result();

if ($request_result->num_rows === 0) {
    echo "No maintenance request found.";
    exit;
}

$request_details = $request_result->fetch_assoc();

// Fetch associated files for the request
$file_query = $conn->prepare("SELECT * FROM maintenance_requests_files WHERE request_id = ?");
$file_query->bind_param('s', $request_id);
$file_query->execute();
$file_result = $file_query->get_result();

// Fetch the user role
$user_role_query = $conn->prepare("SELECT role FROM users WHERE matric_or_staff_id = ?");
$user_role_query->bind_param('s', $user_id); // Use matric_or_staff_id instead of user_id
$user_role_query->execute();
$user_role_result = $user_role_query->get_result();
$user_role = $user_role_result->fetch_assoc()['role'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Details</title>
    <link rel="stylesheet" href="../../css/common/edit_details_hou.css">
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

    <h2>Request Details</h2>

    <!-- Maintenance Request Details -->
    <section class="request-details">
        <table>
            <tr>
                <th>Request ID</th>
                <td><?php echo htmlspecialchars($request_details['request_id']); ?></td>
            </tr>
            <tr>
                <th>User ID</th>
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
                <th>Unit's Approval Status</th>
                <td><?php echo htmlspecialchars($request_details['status']); ?></td>
            </tr>
            <tr>
                <th>Maintenance Department's Status</th>
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
        <?php if ($user_role === 'HOU'): ?>
            <form method="POST" action="">
                <textarea name="comments" id="comments" rows="4" required><?php echo htmlspecialchars($request_details['comments'] ?? ''); ?></textarea>
                <button type="submit" class="update-comment-btn">Update Comment</button>
            </form>
        <?php else: ?>
            <p><?php echo htmlspecialchars($request_details['comments'] ?? 'No comments available for this request.'); ?></p>
        <?php endif; ?>
    </section>

    <!-- Associated Files -->
    <h3>Attachments</h3>
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
