<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once("../database/db_connect.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Normal') {
    header('Location: login.php'); // Redirect to login if not authenticated or not an HOU
    exit;
}

// Fetch sub-departments for the dropdown
$subDeptQuery = "SELECT * FROM sub_departments";
$sub_dept_result = $conn->query($subDeptQuery);

// Function to get HOU ID
function getHOUId($user_unit_id) {
    global $conn;
    $houQuery = $conn->prepare("SELECT matric_or_staff_id FROM users WHERE unit_id = ? AND role = 'HOU'");
    $houQuery->bind_param('s', $user_unit_id);
    $houQuery->execute();
    $result = $houQuery->get_result();
    $houRow = $result->fetch_assoc();
    return $houRow ? $houRow['matric_or_staff_id'] : null;
}

// Function to generate a unique request ID
function generateRequestId($unit_id) {
    global $conn;

    $date = date('Ymd'); // Current date in format YYYYMMDD
    $increment = 0;

    do {
        $increment++;
        $request_id = "REQ-{$unit_id}-{$date}-" . str_pad($increment, 3, '0', STR_PAD_LEFT);

        // Check for uniqueness
        $checkQuery = $conn->prepare("SELECT 1 FROM maintenance_requests WHERE request_id = ?");
        $checkQuery->bind_param('s', $request_id);
        $checkQuery->execute();
        $checkQuery->store_result();
        $isDuplicate = $checkQuery->num_rows > 0;
        $checkQuery->close();
    } while ($isDuplicate);

    return $request_id;
}

// Function to generate a unique file ID based on request_id and suffix
function generateFileId($request_id, $file_name) {
    global $conn;
    $suffix = pathinfo($file_name, PATHINFO_FILENAME);
    $file_id = $request_id . '-' . $suffix;
    
    // Check if the generated file_id already exists
    $checkQuery = $conn->prepare("SELECT 1 FROM maintenance_requests_files WHERE file_id = ?");
    $checkQuery->bind_param('s', $file_id);
    $checkQuery->execute();
    $checkQuery->store_result();
    $isDuplicate = $checkQuery->num_rows > 0;
    $checkQuery->close();

    if ($isDuplicate) {
        $file_id = $request_id . '-' . uniqid();
    }
    
    return $file_id;
}

// Handle request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['submit_request'])) {
        $sub_department_id = $_POST['sub_department_id'];
        $issue_type_id = $_POST['issue_type_id'];
        $description = $_POST['description'];
        $files = $_FILES['files'];

        // Get the logged-in user's ID
        $user_id = $_SESSION['user_id'];

        // Get the user's unit_id and role
        $userQuery = $conn->prepare("SELECT unit_id, role, matric_or_staff_id FROM users WHERE matric_or_staff_id = ?");
        $userQuery->bind_param('s', $user_id);
        $userQuery->execute();
        $userResult = $userQuery->get_result();
        $userRow = $userResult->fetch_assoc();
        $user_unit_id = $userRow['unit_id'];
        $user_role = $userRow['role'];
        $user_user_id = $userRow['matric_or_staff_id'];

        // Get the HOU for this unit
        $hou_id = getHOUId($user_unit_id);

        // Fetch sub_department_name and issue_name based on user selection
        $subDeptQuery = $conn->prepare("SELECT name FROM sub_departments WHERE id = ?");
        $subDeptQuery->bind_param('i', $sub_department_id);
        $subDeptQuery->execute();
        $subDeptResult = $subDeptQuery->get_result();
        $sub_department_name = '';
        if ($subDeptResult->num_rows > 0) {
            $subDeptRow = $subDeptResult->fetch_assoc();
            $sub_department_name = $subDeptRow['name'];
        }

        $issueQuery = $conn->prepare("SELECT issue_type FROM issues WHERE id = ?");
        $issueQuery->bind_param('i', $issue_type_id);
        $issueQuery->execute();
        $issueResult = $issueQuery->get_result();
        $issue_name = '';
        if ($issueResult->num_rows > 0) {
            $issueRow = $issueResult->fetch_assoc();
            $issue_name = $issueRow['issue_type'];
        }

        // Generate request ID
        $request_id = generateRequestId($user_unit_id);

        // Insert request into the database
        $insertRequest = $conn->prepare("INSERT INTO maintenance_requests (request_id, user_id, hou_id, issue_type_id, description, status, unit_id, created_at, sub_department_name, issue_name) VALUES (?, ?, ?, ?, ?, 'pending approval', ?, NOW(), ?, ?)");
        $insertRequest->bind_param('ssssssss', $request_id, $user_id, $hou_id, $issue_type_id, $description, $user_unit_id, $sub_department_name, $issue_name);
        if (!$insertRequest->execute()) {
            error_log("Error inserting request: " . $insertRequest->error);
            die("An error occurred while submitting the maintenance request. Please try again.");
        }

        // Handle file uploads (if any)
        if (!empty($files['name'][0])) {
            // Define the base upload directory
            $base_upload_dir = "../../uploads/{$user_unit_id}/roles/{$user_role}/{$user_user_id}/{$request_id}/";
            
            // Create the directory structure if it doesn't exist
            if (!file_exists($base_upload_dir)) {
                mkdir($base_upload_dir, 0777, true);
            }

            foreach ($files['name'] as $key => $filename) {
                $fileTmpPath = $files['tmp_name'][$key];
                $fileSize = $files['size'][$key];
                $fileType = $files['type'][$key];
                $file_id = generateFileId($request_id, $filename);
                
                // Construct the upload file path
                $uploadFilePath = $base_upload_dir . $file_id . '.' . pathinfo($filename, PATHINFO_EXTENSION);

                // Move the file to the target directory and save to the database
                if (move_uploaded_file($fileTmpPath, $uploadFilePath)) {
                    // Insert file details into the maintenance_requests_files table
                    $insertFile = $conn->prepare("
                        INSERT INTO maintenance_requests_files 
                        (file_id, request_id, user_id, file_name, file_type, file_size, file_path) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $insertFile->bind_param(
                        'sssssis', 
                        $file_id, 
                        $request_id, 
                        $user_id, 
                        $filename, 
                        $fileType, 
                        $fileSize, 
                        $uploadFilePath
                    );
                    $insertFile->execute();
                }
            }
        }

        // Display a success message with a delay and then redirect
        echo '<div id="message" style="text-align:center; padding:20px; background-color: #4CAF50; color: white;">
                <strong>Request Submitted Successfully!</strong> Your request is waiting for approval.
              </div>';
        echo '<script>
                setTimeout(function() {
                    window.location.href = "../../php/dashboards/normal_dashboard.php";
                }, 1000); // 0.6 seconds delay
              </script>';
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Maintenance Request</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="../../css/normal_functions/create_request.css">
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

    <h2>Create Maintenance Request</h2>

    <?php
    $user_id = $_SESSION['user_id'];
    $userQuery = $conn->prepare("SELECT matric_or_staff_id, unit_id FROM users WHERE matric_or_staff_id = ?");
    $userQuery->bind_param('s', $user_id);
    $userQuery->execute();
    $userResult = $userQuery->get_result();
    $userRow = $userResult->fetch_assoc();
    $user_id = $userRow['matric_or_staff_id'];
    $user_unit_id = $userRow['unit_id'];
    $hou_id = getHOUId($user_unit_id);
    ?>

    <form method="POST" enctype="multipart/form-data">
       <input type="hidden" name="hou_id" id="hou_id" value="<?php echo htmlspecialchars($hou_id ?? ''); ?>">

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

        <label for="description">Description:</label>
        <textarea id="description" name="description" required></textarea>

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
