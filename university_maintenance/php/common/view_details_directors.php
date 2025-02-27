<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once("../database/db_connect.php");


// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirect to login if not authenticated
    exit;
}

$user_id = $_SESSION['user_id']; // Assuming User ID is stored in session

// Fetch the user's role from the database
$query = $conn->prepare("SELECT role FROM users WHERE matric_or_staff_id = ?");
$query->bind_param('s', $user_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    echo "User not found.";
    exit;
}

$user_data = $result->fetch_assoc();
$user_role = $user_data['role']; // Use the role fetched from the database

if ($user_role !== 'Admin' && $user_role !== 'director') {
    echo "You do not have the necessary permissions.";
    exit;
}

$request_id = $_GET['request_id'];

// Fetch the maintenance request details
$query = $conn->prepare("SELECT * FROM maintenance_requests WHERE request_id = ?");
$query->bind_param('s', $request_id); // No need to check for user_id or hou_id
$query->execute();
$request_result = $query->get_result();

if ($request_result->num_rows === 0) {
    echo "No maintenance request found or you do not have permission to view it.";
    exit;
}

$request_details = $request_result->fetch_assoc();

// Handle Approve/Reject actions within the same page
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // For Approval
    if (isset($_POST['approve'])) {
        $sub_unit_id = $_POST['sub_unitid'] ?? null;

        // Check if sub-unit is selected for approval
        if (empty($sub_unit_id)) {
            echo "<script>alert('You must select a sub-unit before approving the request.');</script>";
        } else {
            // Update the request status to "approved" and assign the sub-unit
            $updateQuery = $conn->prepare("UPDATE maintenance_requests SET status = 'approved', maintenance_status = 'approved', sub_unitid = ?, director_status = 'approved', updated_at = NOW() WHERE request_id = ?");
            $updateQuery->bind_param('ss', $sub_unit_id, $request_id);
            $updateQuery->execute();
            
            // Success message and redirection
            echo '<div id="message" style="text-align:center; padding:20px; background-color: #4CAF50; color: white;">
                    <strong>Request Approved Successfully!</strong> You will be redirected shortly.
                  </div>';
            echo '<script>
                    setTimeout(function() {
                        window.location.href = "../../php/admin_functions/submitted_requests.php"; // Redirect to HOU Dashboard
                    }, 2000); // 2 seconds delay
                  </script>';
            exit;
        }
    }

    // For Rejection
// For Rejection
elseif (isset($_POST['reject'])) {
    // Ensure rejection includes comments
    $comments = $_POST['comments'] ?? '';
    
    if (empty($comments)) {
        echo "<script>alert('Rejection comments are required.');</script>";
    } else {
        // Update the request status to "rejected", save comments in d_comments, and update director_status
        $updateQuery = $conn->prepare("UPDATE maintenance_requests SET director_status = 'rejected', d_comments = ?, sub_unitid = NULL, updated_at = NOW() WHERE request_id = ?");
        $updateQuery->bind_param('ss', $comments, $request_id);
        $updateQuery->execute();

        // Success message and redirection
        echo '<div id="message" style="text-align:center; padding:20px; background-color: #f44336; color: white;">
                <strong>Request Rejected Successfully!</strong> You will be redirected shortly.
              </div>';
        echo '<script>
                setTimeout(function() {
                    window.location.href = "../../php/admin_functions/submitted_requests.php"; // Redirect
                }, 2000);
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

// Fetch subunit options for the Director
$subunit_query = $conn->prepare("SELECT sub_unitid, sub_unitname FROM md_sub_units");
$subunit_query->execute();
$subunit_result = $subunit_query->get_result();
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
            <a href="../../php/dashboards/system_admin_dashboard.php" class="Menu">MENU</a>
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

    <!-- Comment Section -->
    <section class="comment-section">
        <h2>Comments (for approval or rejection)</h2>
        <div class="textarea-wrapper">
            <textarea id="reject-comments" name="comments" placeholder="Comments are required when rejecting a request. You may leave the comment box empty when approving, unless you have something important to note regarding the request."></textarea>
            <div id="error-message" class="error-message" style="display: none;">
                <span style="color: red; font-size: 0.9em; display: flex; align-items: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="red" width="16" height="16" style="margin-right: 4px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.007v-.008H12v.008zm-8.485 5.254a9.969 9.969 9.969 1z" />
                    </svg>
                    Rejection comments are required.
                </span>
            </div>
        </div>
    </section>

 <!-- Sub-Unit Selection for Director or Admin -->
<?php if ($_SESSION['role'] === 'Director' || $_SESSION['role'] === 'Admin'): ?>
    <section class="comment-section">
        <h2>Select Sub-unit</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="sub_unit">Select Sub-unit:</label>
                <select name="sub_unitid" id="sub_unit">
                    <option value="" disabled selected>Select a sub-unit</option> <!-- Placeholder -->
                    <?php 
                    // Limiting subunits shown for selection (example: based on availability or specific condition)
                    while ($subunit = $subunit_result->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($subunit['sub_unitid']); ?>" 
                            <?php echo ($subunit['sub_unitid'] == $request_details['sub_unitid']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($subunit['sub_unitname']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </form>
    </section>
<?php endif; ?>


    <!-- Button Container: Display Approve and Reject on the same line -->
    <div class="buttons-container">
        <form method="POST" action="">
            <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request_details['request_id']); ?>">
                        <input type="hidden" name="sub_unitid" id="hidden-subunit">
			<button type="submit" name="approve" class="approve-btn">Approve</button>
        </form>
        <form method="POST" action="">
            <input type="hidden" name="request_id" value="<?php echo htmlspecialchars($request_details['request_id']); ?>">
    <input type="hidden" name="comments" id="hidden-comments"> <!-- Make sure this exists -->
            <button type="submit" name="reject" class="reject-btn">Reject</button>
        </form>
    </div>


<script>
    // Update hidden subunit input on change
    document.getElementById('sub_unit').addEventListener('change', function () {
        document.getElementById('hidden-subunit').value = this.value;
    });

    // Ensure rejection comment is filled before submitting the reject form
    document.querySelector('.reject-btn').addEventListener('click', function (event) {
        var comments = document.getElementById('reject-comments').value.trim();
        var hiddenComments = document.getElementById('hidden-comments');

        if (!comments) {
            event.preventDefault(); // Prevent form submission
            document.getElementById('error-message').style.display = 'block'; // Show error message
        } else {
            hiddenComments.value = comments; // Set hidden input value
        }
    });
</script>
<!-- Footer Section -->
<footer>
    <p>&copy; 2024 University Maintenance System. All rights reserved.</p>
</footer>
</body>
</html>
