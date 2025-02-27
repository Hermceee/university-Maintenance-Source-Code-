<?php
// Start output buffering
ob_start();

require_once("../database/db_connect.php"); // Corrected the relative path for the database connection
include("../../php/common/session_manager.php"); // Include session management script

// Define a flag to check for successful operations
$success_redirect = false;

// Handle adding new sub-department or issue
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_sub_department'])) {
        $new_sub_dept_name = $_POST['sub_dept_name'];

        // Check if the sub-department already exists
        $sub_dept_check_sql = "SELECT * FROM sub_departments WHERE name = ?";
        $stmt_check = $conn->prepare($sub_dept_check_sql);
        $stmt_check->bind_param('s', $new_sub_dept_name);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $_SESSION['error_message'] = "Sub-department '$new_sub_dept_name' already exists!";
        } else {
            $sql = "INSERT INTO sub_departments (name) VALUES (?)";
            $bind_param_type = 's';
            $bind_param_value = $new_sub_dept_name;
        }

        $stmt_check->close();
    } elseif (isset($_POST['add_issue'])) {
        $issue_type = $_POST['issue_type'];
        $sub_department_id = $_POST['sub_department_id'];

        // Check if the issue already exists
        $issue_check_sql = "SELECT * FROM issues WHERE issue_type = ? AND sub_department_id = ?";
        $stmt_check = $conn->prepare($issue_check_sql);
        $stmt_check->bind_param('si', $issue_type, $sub_department_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            $_SESSION['error_message'] = "Issue '$issue_type' already exists for this sub-department!";
        } else {
            $sql = "INSERT INTO issues (issue_type, sub_department_id) VALUES (?, ?)";
            $bind_param_type = 'si';
            $bind_param_value = [$issue_type, $sub_department_id];
        }

        $stmt_check->close();
    } elseif (isset($_POST['delete_sub_department'])) {
        $sub_dept_id = $_POST['sub_dept_id'];
        $sql = "DELETE FROM sub_departments WHERE id = ?";
        $bind_param_type = 'i';
        $bind_param_value = $sub_dept_id;
    } elseif (isset($_POST['delete_issue'])) {
        $issue_id = $_POST['issue_id'];
        $sql = "DELETE FROM issues WHERE id = ?";
        $bind_param_type = 'i';
        $bind_param_value = $issue_id;
    }

    if (isset($sql) && !isset($_SESSION['error_message'])) {
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            if (is_array($bind_param_value)) {
                $stmt->bind_param($bind_param_type, ...$bind_param_value);
            } else {
                $stmt->bind_param($bind_param_type, $bind_param_value);
            }

            if ($stmt->execute()) {
                // Display meaningful success messages
                if (isset($_POST['add_sub_department'])) {
                    $_SESSION['success_message'] = "Sub-department '$new_sub_dept_name' added successfully!";
                } elseif (isset($_POST['add_issue'])) {
                    $_SESSION['success_message'] = "Issue '$issue_type' added successfully to sub-department!";
                } elseif (isset($_POST['delete_sub_department'])) {
                    $_SESSION['success_message'] = "Sub-department deleted successfully!";
                } elseif (isset($_POST['delete_issue'])) {
                    $_SESSION['success_message'] = "Issue deleted successfully!";
                }

                $success_redirect = true;
            } else {
                $_SESSION['error_message'] = "Error: " . $stmt->error;
            }

            $stmt->close();
        } else {
            $_SESSION['error_message'] = "Error preparing statement: " . $conn->error;
        }
    }

    // If success, trigger a JavaScript redirect
    if ($success_redirect) {
        echo '<script type="text/javascript">
                setTimeout(function() {
                    window.location.href = "manage_issue & sub_depts.php";
                }, 1000); // 1 second delay
              </script>';
    }
}

// Fetch all sub-departments and issues for display
$sub_dept_sql = "SELECT id, name FROM sub_departments";
$sub_dept_result = $conn->query($sub_dept_sql);

$issue_sql = "SELECT issues.id, issue_type, sub_department_id, sub_departments.name as sub_dept_name FROM issues 
              JOIN sub_departments ON issues.sub_department_id = sub_departments.id";
$issue_result = $conn->query($issue_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sub-Departments and Issues</title>
    <link rel="stylesheet" href="../../css/admin_functions/manage_roles_units.css">
    <script>
        // JavaScript confirmation function before deletion
        function confirmDeletion(event, item) {
            if (!confirm('Are you sure you want to delete this ' + item + '?')) {
                event.preventDefault(); // Stop the form submission if not confirmed
            }
        }
    </script>
    <style>
        .delete-button {
            background-color: red;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
        }
        .delete-button:hover {
            background-color: darkred;
        }
        .success-message {
            color: green;
            font-weight: bold;
        }
        .error-message {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <header>
        <img src="../../images/UI LOGO.jpeg" alt="University Logo" class="logo">
        <h1>University of Ibadan Maintenance Department</h1>
        <a href="../../php/dashboards/system_admin_dashboard.php" class="logout-button">MENU</a>
    </header>

    <main>
        <div class="management-container">
            <?php if (isset($_SESSION['success_message'])) : ?>
                <p class="success-message"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></p>
            <?php elseif (isset($_SESSION['error_message'])) : ?>
                <p class="error-message"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
            <?php endif; ?>

            <!-- Manage Sub-Departments Section -->
            <section>
                <div class="management-section">
                    <h2>Add new Sub-Departments</h2>
                    <form method="POST" action="manage_issue & sub_depts.php" class="management-form">
                        <input type="text" name="sub_dept_name" placeholder="Sub-Department Name" required>
                        <button type="submit" name="add_sub_department">Add New Sub-Department</button>
                    </form>

                    <h2>Existing Sub-Departments</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Sub-Department Name</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $sub_dept_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td>
                                        <form method="POST" action="manage_issue & sub_depts.php" onsubmit="confirmDeletion(event, 'sub-department')">
                                            <input type="hidden" name="sub_dept_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                            <button type="submit" name="delete_sub_department" class="delete-button">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Manage Issues Section -->
            <section>
                <div class="management-section">
                    <h2>Add new Issues</h2>
                    <form method="POST" action="manage_issue & sub_depts.php" class="management-form">
                        <input type="text" name="issue_type" placeholder="Issue Type" required>
                        <select name="sub_department_id" required>
                            <option value="" disabled selected>Select Sub-Department</option>
                            <?php 
                            $sub_dept_result->data_seek(0); 
                            while ($row = $sub_dept_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($row['id']); ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                        <button type="submit" name="add_issue">Add New Issue</button>
                    </form>

                    <h2>Existing Issues</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>S/N</th>
                                <th>Issue Type</th>
                                <th>Sub-Department</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $serial = 1; while ($row = $issue_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $serial++; ?></td>
                                    <td><?php echo htmlspecialchars($row['issue_type']); ?></td>
                                    <td><?php echo htmlspecialchars($row['sub_dept_name']); ?></td>
                                    <td>
                                        <form method="POST" action="manage_issue & sub_depts.php" onsubmit="confirmDeletion(event, 'issue')">
                                            <input type="hidden" name="issue_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                            <button type="submit" name="delete_issue" class="delete-button">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 University of Ibadan Maintenance Department. All rights reserved.</p>
    </footer>
</body>
</html>
