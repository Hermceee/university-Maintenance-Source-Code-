<?php
// Start output buffering
ob_start();

require_once("../database/db_connect.php"); // Adjust the path as needed
include("../../php/common/session_manager.php"); // Include session management script

// Define a flag to check for successful operations
$success_redirect = false;

// Handle adding and deleting sub-units
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_sub_unit'])) {
        $sub_unitid = $_POST['sub_unitid'];  // Get the manually entered sub-unit id
        $sub_unit_name = $_POST['sub_unit_name'];
        $sub_department_id = $_POST['sub_department_id'];  // Get selected sub-department id

        // Check if sub_unitid or sub_unitname already exists for the same sub-department
        $check_sql = "SELECT COUNT(*) FROM md_sub_units WHERE sub_unitid = ? OR (sub_unitname = ? AND sub_department_id = ?)";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param('ssi', $sub_unitid, $sub_unit_name, $sub_department_id);
        $stmt->execute();
        $stmt->bind_result($existing_count);
        $stmt->fetch();
        $stmt->close();

        if ($existing_count > 0) {
            // If the sub_unitid or sub_unitname already exists, show an error message
            $_SESSION['error_message'] = "Error: Sub-unit ID '$sub_unitid' or Sub-unit Name '$sub_unit_name' already exists in this department.";
        } else {
            // Fetch sub-department name based on id
            $sub_dept_sql = "SELECT name FROM sub_departments WHERE id = ?";
            $stmt = $conn->prepare($sub_dept_sql);
            $stmt->bind_param('i', $sub_department_id);
            $stmt->execute();
            $stmt->bind_result($sub_dept_name);
            $stmt->fetch();
            $stmt->close();

            // Insert sub-unit with sub_unitid, sub_unitname, sub_department_id, and sub_department_name into md_sub_units table
            $sql = "INSERT INTO md_sub_units (sub_unitid, sub_unitname, sub_department_id, sub_department_name) 
                    VALUES (?, ?, ?, ?)";
            $bind_param_type = 'ssis'; // 's' for sub_unitid, 's' for sub_unitname, 'i' for sub_department_id, 's' for sub_department_name
            $bind_param_value = [$sub_unitid, $sub_unit_name, $sub_department_id, $sub_dept_name];

            // Continue with the prepared statement and execution as before
            try {
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param($bind_param_type, ...$bind_param_value);

                    if ($stmt->execute()) {
                        // Success message for adding sub-unit
                        $_SESSION['success_message'] = "New sub-unit added successfully";
                        $success_redirect = true;
                    } else {
                        $_SESSION['error_message'] = "Error: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $_SESSION['error_message'] = "Error preparing statement: " . $conn->error;
                }
            } catch (mysqli_sql_exception $e) {
                // Catch the duplicate entry exception and redirect with error message
                $_SESSION['error_message'] = "Error: Sub-unit ID '$sub_unitid' or Sub-unit Name '$sub_unit_name' already exists in this department.";
                // Redirect back to the same page
                header("Location: manage_sub_units.php");
                exit();
            }
        }
    } elseif (isset($_POST['delete_sub_unit'])) {
        $sub_unit_id = $_POST['sub_unit_id'];
        $sql = "DELETE FROM md_sub_units WHERE sub_unitid = ?";
        $bind_param_type = 's';
        $bind_param_value = [$sub_unit_id];
    }

    if (isset($sql) && !isset($_SESSION['error_message'])) {
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param($bind_param_type, ...$bind_param_value);

            if ($stmt->execute()) {
                // Success message for adding sub-unit
                if (isset($_POST['add_sub_unit'])) {
                    $_SESSION['success_message'] = "New sub-unit added successfully";
                } 
                // Success message for deleting sub-unit
                elseif (isset($_POST['delete_sub_unit'])) {
                    $_SESSION['success_message'] = "Sub-unit deleted successfully";
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

    // Redirect the user if the operation was successful
    if ($success_redirect) {
        echo '<script type="text/javascript">
                setTimeout(function() {
                    window.location.href = "manage_sub_units.php";
                }, 1000);
              </script>';
    }
}

// Fetch all sub-units for display
$sub_unit_sql = "SELECT sub_unitid, sub_unitname, sub_department_id, sub_department_name FROM md_sub_units";
$sub_unit_result = $conn->query($sub_unit_sql);

// Fetch all sub-departments for dropdown
$sub_dept_sql = "SELECT id, name FROM sub_departments";
$sub_dept_result = $conn->query($sub_dept_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Sub-Units</title>
    <link rel="stylesheet" href="../../css/admin_functions/manage_roles_units.css">
    <script>
        // Function to check if all required fields are filled out and enable/disable the button
        function checkFields() {
            var subUnitID = document.getElementsByName('sub_unitid')[0].value;
            var subUnitName = document.getElementsByName('sub_unit_name')[0].value;
            var subDept = document.getElementsByName('sub_department_id')[0].value;
            var addButton = document.getElementsByName('add_sub_unit')[0];

            // Enable button only if all fields are filled
            if (subUnitID && subUnitName && subDept) {
                addButton.disabled = false;
            } else {
                addButton.disabled = true;
            }
        }

        // Run checkFields whenever the user types or selects a field
        window.onload = function() {
            checkFields();  // Ensure button is checked when the page loads
            document.querySelectorAll('input, select').forEach(function(field) {
                field.addEventListener('input', checkFields);
            });
        };
    </script>
</head>
<body>
    <!-- Header -->
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

            <div class="management-section">
                <h2>Add New Sub-Unit</h2>
                <form method="POST" action="manage_sub_units.php" class="management-form">
                    <!-- Manually Entered Sub Unit ID -->
                    <input type="text" name="sub_unitid" placeholder="Sub-Unit ID (e.g., ELEC101)" required>

                    <!-- Sub-Unit Name -->
                    <input type="text" name="sub_unit_name" placeholder="Sub-Unit Name" required>

                    <!-- Sub-Department Dropdown -->
                    <select name="sub_department_id" required>
                        <option value="" disabled selected>Select Sub-Department</option>
                        <?php while ($row = $sub_dept_result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($row['id']); ?>">
                                <?php echo htmlspecialchars($row['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <!-- Submit Button (Initially Disabled) -->
                    <button type="submit" name="add_sub_unit" disabled>Add Sub-Unit</button>
                </form>
            </div>

            <div class="management-section">
                <h2>Existing Sub-Units</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Sub-Department ID</th>
                            <th>Sub-Department Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $sub_unit_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['sub_unitid']); ?></td>
                                <td><?php echo htmlspecialchars($row['sub_unitname']); ?></td>
                                <td><?php echo htmlspecialchars($row['sub_department_id']); ?></td>
                                <td><?php echo htmlspecialchars($row['sub_department_name']); ?></td>
                                <td>
                                    <form method="POST" action="manage_sub_units.php" onsubmit="confirmDeletion(event, 'sub-unit')">
                                        <input type="hidden" name="sub_unit_id" value="<?php echo htmlspecialchars($row['sub_unitid']); ?>">
                                        <button type="submit" name="delete_sub_unit" class="delete-button">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 University of Ibadan. All Rights Reserved.</p>
    </footer>
</body>
</html>
<?php
ob_end_flush();
?>
