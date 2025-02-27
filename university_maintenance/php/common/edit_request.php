<?php
require_once("../database/db_connect.php");
include("../../php/common/session_manager.php"); // Include session management script

// Fetch the user's role from the database
function getUserRole($user_id) {
    global $conn;
    // Change user_id to matric_or_staff_id
    $query = $conn->prepare("SELECT role FROM users WHERE matric_or_staff_id = ?");
    $query->bind_param('s', $user_id);  // Assuming user_id is a string
    $query->execute();
    $result = $query->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['role'] : null;
}

// Fetch sub-departments for the dropdown
$subDeptQuery = "SELECT * FROM sub_departments";
$sub_dept_result = $conn->query($subDeptQuery);

// Function to get HOU ID from maintenance_requests table
function getHOUIdFromRequest($request_id) {
    global $conn;
    $query = $conn->prepare("SELECT hou_id FROM maintenance_requests WHERE request_id = ?");
    $query->bind_param('s', $request_id);
    $query->execute();
    $result = $query->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['hou_id'] : null;
}

// Fetch request data from the maintenance_requests table
if (isset($_GET['request_id'])) {
    $request_id = $_GET['request_id'];

    $requestQuery = $conn->prepare("SELECT user_id, unit_id, hou_id, sub_department_name, issue_name, description, comments FROM maintenance_requests WHERE request_id = ?");
    $requestQuery->bind_param('s', $request_id);
    $requestQuery->execute();
    $requestResult = $requestQuery->get_result();
    $requestRow = $requestResult->fetch_assoc();

    $user_id = $requestRow['user_id'];
    $unit_id = $requestRow['unit_id'];
    $hou_id = getHOUIdFromRequest($request_id);
    $current_sub_department = $requestRow['sub_department_name'];
    $current_issue_name = $requestRow['issue_name'];
    $current_description = $requestRow['description']; // Get current description
    $current_comments = $requestRow['comments']; // Get current comments

    // Fetch user role
    $role = getUserRole($user_id);
} else {
    // Handle the case when the request_id is not passed
    die("Request ID is required.");
}

// Check if the form is submitted
if (isset($_POST['submit_request'])) {
    // Get the selected sub-department and issue type from the form
    $sub_department_id = $_POST['sub_department_id'];
    $issue_type_id = $_POST['issue_type_id'];
    $description = $_POST['description']; // Get the description from the form
    $comments = $_POST['comments']; // Get the comments from the form

    // Get the sub-department name and issue type name
    $subDeptQuery = $conn->prepare("SELECT name FROM sub_departments WHERE id = ?");
    $subDeptQuery->bind_param('i', $sub_department_id);
    $subDeptQuery->execute();
    $subDeptResult = $subDeptQuery->get_result();
    $subDeptRow = $subDeptResult->fetch_assoc();
    $sub_department_name = $subDeptRow['name'];

    $issueQuery = $conn->prepare("SELECT issue_type FROM issues WHERE id = ?");
    $issueQuery->bind_param('i', $issue_type_id);
    $issueQuery->execute();
    $issueResult = $issueQuery->get_result();
    $issueRow = $issueResult->fetch_assoc();
    $issue_name = $issueRow['issue_type'];

    // Handle file uploads
    if (isset($_FILES['files'])) {
        $uploaded_files = $_FILES['files'];
        $file_ids = [];
        
        // Create folder path using unit_id and role
$upload_folder = "../../uploads/{$unit_id}/roles/{$role}/{$user_id}/{$request_id}/";
        if (!is_dir($upload_folder)) {
            mkdir($upload_folder, 0777, true);  // Create the folder if it doesn't exist
        }

        // Process each file uploaded
        for ($i = 0; $i < count($uploaded_files['name']); $i++) {
            $file_name = $uploaded_files['name'][$i];
            $file_tmp = $uploaded_files['tmp_name'][$i];
            $file_type = $uploaded_files['type'][$i];
            $file_error = $uploaded_files['error'][$i];
            $file_size = $uploaded_files['size'][$i];

            // Check if there was an error uploading the file
            if ($file_error === UPLOAD_ERR_OK) {
                // Generate a unique suffix for the file (timestamp or random string)
                $file_suffix = time() . '-' . uniqid();
                $file_id = $request_id . '-' . $file_suffix;  // File ID in the format of requestid + suffix
                $file_path = $upload_folder . $file_id . '.' . pathinfo($file_name, PATHINFO_EXTENSION);

                // Move the uploaded file to the appropriate folder
                if (move_uploaded_file($file_tmp, $file_path)) {
                    // Insert the file into the database
                    $file_query = $conn->prepare("INSERT INTO maintenance_requests_files (request_id, file_id, file_name, file_path) VALUES (?, ?, ?, ?)");
                    $file_query->bind_param('ssss', $request_id, $file_id, $file_name, $file_path);
                    $file_query->execute();
                    $file_ids[] = $file_id;  // Store the file ID for later use (if needed)
                } else {
                    echo "Error uploading file: $file_name";
                }
            }
        }
    }

    // Update the maintenance request with the new sub-department, issue name, description, and comments
    $updateQuery = $conn->prepare("UPDATE maintenance_requests SET sub_department_name = ?, issue_name = ?, description = ?, comments = ?, status = 'pending approval' WHERE request_id = ?");
    $updateQuery->bind_param('sssss', $sub_department_name, $issue_name, $description, $comments, $request_id);
    if ($updateQuery->execute()) {
        // Redirect to pending_approvals.php after successful submission
        header("Location: ../../php/normal_functions/pending_approvals.php");
        exit(); // Make sure to stop further execution after redirect
    } else {
        echo "Error updating request.";
    }
}

// Check if the delete request is triggered
if (isset($_GET['delete_file'])) {
    $file_id_to_delete = $_GET['delete_file'];

    // Fetch the file path from the database
    $fileQuery = $conn->prepare("SELECT file_path FROM maintenance_requests_files WHERE file_id = ?");
    $fileQuery->bind_param('s', $file_id_to_delete);
    $fileQuery->execute();
    $fileResult = $fileQuery->get_result();
    $fileRow = $fileResult->fetch_assoc();

    if ($fileRow) {
        $file_path = $fileRow['file_path'];

        // Check if the file exists in the directory and delete it
        if (file_exists($file_path)) {
            unlink($file_path); // Delete the file from the server
            echo "File deleted successfully.";

            // Delete the file record from the database
            $deleteQuery = $conn->prepare("DELETE FROM maintenance_requests_files WHERE file_id = ?");
            $deleteQuery->bind_param('s', $file_id_to_delete);
            $deleteQuery->execute();
        } else {
            echo "File not found.";
        }
    } else {
        echo "File record not found in the database.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Maintenance Request</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="../../css/normal_functions/create_request.css">
</head>
<body>
    <header>
        <img src="../../images/UI LOGO.jpeg" alt="University Logo" class="logo">
        <h1>University of Ibadan Maintenance Department</h1>
        <div class="header-right">
            <a href="../../php/dashboards/normal_dashboard.php" class="Menu">MENU</a>
        </div>
    </header>

    <h2>Update Maintenance Request</h2>

    <form method="POST" enctype="multipart/form-data">
        <label for="request_id">Request ID:</label>
        <input type="text" id="request_id" name="request_id" value="<?php echo htmlspecialchars($request_id); ?>" readonly>

        

        <label for="current_sub_department">Current Sub-department:</label>
        <input type="text" id="current_sub_department" name="current_sub_department" value="<?php echo htmlspecialchars($current_sub_department); ?>" readonly>

        <label for="current_issue_name">Current Issue Name:</label>
        <input type="text" id="current_issue_name" name="current_issue_name" value="<?php echo htmlspecialchars($current_issue_name); ?>" readonly>

        <label for="sub_department">Select Sub-department:</label>
        <select id="sub_department" name="sub_department_id" required>
            <option value="" disabled selected>Select Sub-department</option>
            <?php while ($row = $sub_dept_result->fetch_assoc()): ?>
                <option value="<?php echo htmlspecialchars($row['id']); ?>"><?php echo htmlspecialchars($row['name']); ?></option>
            <?php endwhile; ?>
        </select>

        <label for="issue_type">Select Issue Type:</label>
        <select id="issue_type" name="issue_type_id" required>
            <option value="" disabled selected>Select Issue Type</option>
        </select>

        <label for="description"> Enter Description:</label>
        <textarea id="description" name="description" required><?php echo htmlspecialchars($current_description); ?></textarea>

       <label for="comments">HOU Comments:</label>
<textarea id="comments" name="comments" readonly><?php echo htmlspecialchars($current_comments); ?></textarea>


        <label for="files">Upload Files:</label>
        <input type="file" name="files[]" id="files" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.txt">

        <button type="submit" name="submit_request">Request Approval</button>
    </form>

    <script>
        $('#sub_department').on('change', function() {
            const subDeptId = $(this).val();
            if (subDeptId) {
                $.ajax({
                    url: '',
                    type: 'POST',
                    data: { sub_department_id: subDeptId, get_issue_types: true },
                    success: function(data) {
                        $('#issue_type').html(data);
                    },
                    error: function() {
                        alert('An error occurred while fetching issue types.');
                    }
                });
            } else {
                $('#issue_type').html('<option value="">Select Issue Type</option>');
            }
        });
    </script>

    <?php
    if (isset($_POST['get_issue_types'])) {
        $subDeptId = $_POST['sub_department_id'];
        $stmt = $conn->prepare("SELECT id, issue_type FROM issues WHERE sub_department_id = ?");
        $stmt->bind_param('i', $subDeptId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo '<option value="' . htmlspecialchars($row['id']) . '">' . htmlspecialchars($row['issue_type']) . '</option>';
            }
        } else {
            echo '<option value="">No issue types available</option>';
        }
        $stmt->close();
        exit;
    }
    ?>

    <footer>
        <p>&copy; 2024 University Maintenance System. All rights reserved.</p>
    </footer>
</body>
</html>
