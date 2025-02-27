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

// Fetch the maintenance request details
$query = $conn->prepare("SELECT * FROM maintenance_requests WHERE request_id = ? AND (user_id = ? OR hou_id = ?)");
$query->bind_param('sss', $request_id, $user_id, $user_id); // Check if the user is either the request submitter or the HOU
$query->execute();
$request_result = $query->get_result();

if ($request_result->num_rows === 0) {
    echo "No maintenance request found or you do not have permission to view it.";
    exit;
}

$request_details = $request_result->fetch_assoc();

// Handle Approve/Reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve'])) {
        // Update the request status to "approved"
        $updateQuery = $conn->prepare("UPDATE maintenance_requests SET status = 'approved', updated_at = NOW() WHERE request_id = ?");
        $updateQuery->bind_param('s', $request_id);
        $updateQuery->execute();
        
        // Success message and redirection
        echo '<div id="message" style="text-align:center; padding:20px; background-color: #4CAF50; color: white;">
                <strong>Request Approved Successfully!</strong> You will be redirected shortly.
              </div>';
        echo '<script>
                setTimeout(function() {
                    window.location.href = "../../php/dashboards/hou_dashboard.php"; // Redirect to HOU Dashboard
                }, 2000); // 2 seconds delay
              </script>';
        exit;
    } elseif (isset($_POST['reject'])) {
        // Ensure rejection includes comments
        $comments = $_POST['comments'] ?? '';
        if (empty($comments)) {
            echo "<script>alert('Rejection comments are required.');</script>";
        } else {
            // Update the request status to "rejected" and save comments
            $updateQuery = $conn->prepare("UPDATE maintenance_requests SET status = 'rejected', comments = ?, updated_at = NOW() WHERE request_id = ?");
            $updateQuery->bind_param('ss', $comments, $request_id);
            $updateQuery->execute();
            
            // Success message and redirection
            echo '<div id="message" style="text-align:center; padding:20px; background-color: #f44336; color: white;">
                    <strong>Request Rejected Successfully!</strong> You will be redirected shortly.
                  </div>';
            echo '<script>
                    setTimeout(function() {
                        window.location.href = "../../php/dashboards/hou_dashboard.php"; // Redirect to HOU Dashboard
                    }, 2000); // 2 seconds delay
                  </script>';
            exit;
        }
    }
}

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
    <link rel="stylesheet" href="../../css/common/view_details_hou.css">
</head>
<body>
    <!-- Header Section -->
    <header>
        <img src="../../images/UI LOGO.jpeg" alt="University Logo" class="logo">
        <h1>University of Ibadan Maintenance Department</h1>
        <div class="header-right">
            <a href="../../php/HOU_functions/pending_approvals_hou.php" class="Menu">MENU</a>
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
                <th>Maintenance Department's Status</th>
                <td><?php echo htmlspecialchars($request_details['maintenance_status']); ?></td>
            </tr>
            <tr>
                <th>Created At</th>
                <td><?php echo htmlspecialchars($request_details['created_at']); ?></td>
            </tr>
            <tr>
                <th>Updated At</th> <!-- Added Updated At Field -->
                <td><?php echo htmlspecialchars($request_details['updated_at']); ?></td>
            </tr>
            <tr>
                <th>Unit ID</th>
                <td><?php echo htmlspecialchars($request_details['unit_id']); ?></td>
            </tr>
        </table>
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
<p class="no-attachments">No attachments available for this request.</p>
        <?php endif; ?>
    </section>

<?php if ($request_details['status'] === 'pending approval'): ?>
<section class="comment-section">
    <h3>Comments (for approval or rejection)</h3>
    <div class="textarea-wrapper">
        <textarea id="reject-comments" name="comments" placeholder="Comments are required when rejecting a request. You may leave the comment box empty when approving, unless you have something important to note regarding the request." required></textarea>
        <div id="error-message" class="error-message" style="display: none;">
            <span style="color: red; font-size: 0.9em; display: flex; align-items: center;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="red" width="16" height="16" style="margin-right: 4px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.007v-.008H12v.008zm-8.485 5.254a9.969 9.969 0 1116.97 0 9.969 9.969 0 01-16.97 0z" />
                </svg>
                Rejection comments are required.
            </span>
        </div>
    </div>
</section>

<!-- Button Container: Display Approve and Reject on the same line -->
<div class="buttons-container">
    <!-- Approve Button Form (Appears first as per your request) -->
    <form method="POST" action="../../php/common/approve_request.php">
        <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request_details['request_id']); ?>">
        <button type="submit" name="approve" class="approve-btn">Approve</button>
    </form>

    <!-- Reject Button Form (Comes second after Approve) -->
    <form id="reject-form" method="POST" action="../../php/common/reject_request.php">
        <!-- Hidden Field for Request ID -->
        <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request_details['request_id']); ?>">

        <!-- This is where the comment section data will be transferred automatically -->
        <input type="hidden" name="comments" id="hidden-comments">

        <button type="submit" name="reject" class="reject-btn">Reject</button>
    </form>
</div>

<script>
// Ensure rejection comment is filled before submitting the reject form
document.querySelector('.reject-btn').addEventListener('click', function(event) {
    var comments = document.getElementById('reject-comments');
    var errorMessage = document.getElementById('error-message');
    
    if (comments.value.trim() === "") {
        event.preventDefault(); // Prevent form submission
        comments.style.borderColor = "red"; // Highlight the textarea with red border
        errorMessage.style.display = 'block'; // Show the error message inside the section
    } else {
        comments.style.borderColor = ""; // Reset border color if valid
        document.getElementById('hidden-comments').value = comments.value; // Set the value to the hidden input field
        errorMessage.style.display = 'none'; // Hide error message if comments are provided
    }
});
</script>

<?php endif; ?>

<!-- Footer Section -->
<footer>
    <p>&copy; 2024 University Maintenance System. All rights reserved.</p>
</footer>
</body>
</html>
