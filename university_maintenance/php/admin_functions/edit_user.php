<?php
require_once("../database/db_connect.php");
include("../../php/common/session_manager.php"); // Include session management script

// Initialize variables
$user = [];
$unit_result = null;
$sub_unit_result = null;
$roles_result = null; // For storing roles from the database

// Fetch user data to view/edit
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE matric_or_staff_id = ?");
    $stmt->bind_param('s', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        die("User not found.");
    }

    $stmt->close();
}

// Handle form submission for updating user details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_mode'])) {
    $user_id = $_POST['matric_or_staff_id']; // Hidden field to keep the user ID
    $name = $_POST['name'];
    $phone_number = $_POST['phone_number'];
    $department = $_POST['department'];
    $faculty = $_POST['faculty'];
    $email = $_POST['email'];
    $role_id = $_POST['role']; // Role ID from dropdown
    $unit_id = $_POST['unit_id'];
    $sub_unit_id = $_POST['sub_unit_id']; // Sub-unit ID from the dropdown

    // Fetch the role name based on the selected role ID
    $role_sql = "SELECT role_name FROM roles WHERE id = ?";
    $role_stmt = $conn->prepare($role_sql);
    $role_stmt->bind_param('s', $role_id);
    $role_stmt->execute();
    $role_result = $role_stmt->get_result();

    if ($role_result->num_rows > 0) {
        $role_name = $role_result->fetch_assoc()['role_name'];
    } else {
        die("Invalid role selected.");
    }

    // Check if the new role is "HOU" and if another user already has that role in the same unit
    if ($role_name === "HOU" && !empty($unit_id)) {
        $check_hou_sql = "SELECT COUNT(*) FROM users WHERE unit_id = ? AND role = 'HOU'";
        $check_hou_stmt = $conn->prepare($check_hou_sql);
        $check_hou_stmt->bind_param('s', $unit_id);
        $check_hou_stmt->execute();
        $check_hou_result = $check_hou_stmt->get_result();
        $hou_count = $check_hou_result->fetch_row()[0];

        if ($hou_count > 0) {
            $error_message = "Error: This unit already has a Head of Unit (HOU). A unit cannot have more than one HOU.";
        } else {
            // Prepare the SQL statement to update the user data, excluding unit_id if it's empty
            if (empty($unit_id)) {
                $update_stmt = $conn->prepare("
                    UPDATE users 
                    SET name = ?, phone_number = ?, department = ?, faculty = ?, email = ?, role = ? 
                    WHERE matric_or_staff_id = ?
                ");
                $update_stmt->bind_param('sssssss', $name, $phone_number, $department, $faculty, $email, $role_name, $user_id);
            } else {
                $update_stmt = $conn->prepare("
                    UPDATE users 
                    SET name = ?, phone_number = ?, department = ?, faculty = ?, email = ?, role = ?, unit_id = ?, sub_unit_id = ? 
                    WHERE matric_or_staff_id = ?
                ");
                $update_stmt->bind_param('sssssssss', $name, $phone_number, $department, $faculty, $email, $role_name, $unit_id, $sub_unit_id, $user_id);
            }

            if ($update_stmt->execute()) {
                // Redirect to the user management page with success message
                header("Location: user_management.php?update=success");
                exit();
            } else {
                $error_message = "Error updating user: " . $conn->error;
            }
            $update_stmt->close();
        }
    } else {
        // Prepare the SQL statement to update the user data, excluding unit_id if it's empty
        if (empty($unit_id)) {
            $update_stmt = $conn->prepare("
                UPDATE users 
                SET name = ?, phone_number = ?, department = ?, faculty = ?, email = ?, role = ? 
                WHERE matric_or_staff_id = ?
            ");
            $update_stmt->bind_param('sssssss', $name, $phone_number, $department, $faculty, $email, $role_name, $user_id);
        } else {
            $update_stmt = $conn->prepare("
                UPDATE users 
                SET name = ?, phone_number = ?, department = ?, faculty = ?, email = ?, role = ?, unit_id = ?, sub_unitid = ? 
                WHERE matric_or_staff_id = ?
            ");
            $update_stmt->bind_param('sssssssss', $name, $phone_number, $department, $faculty, $email, $role_name, $unit_id, $sub_unit_id, $user_id);
        }

        if ($update_stmt->execute()) {
            // Redirect to the user management page with success message
            header("Location: user_management.php?update=success");
            exit();
        } else {
            $error_message = "Error updating user: " . $conn->error;
        }
        $update_stmt->close();
    }
}

// Fetch all units for the unit dropdown
$unit_sql = "SELECT unit_id FROM units";
$unit_result = $conn->query($unit_sql);

// Fetch all sub-units for the sub-unit dropdown
$sub_unit_sql = "SELECT sub_unitid, sub_unitname FROM md_sub_units";
$sub_unit_result = $conn->query($sub_unit_sql);

// Fetch all roles for the roles dropdown
$roles_sql = "SELECT id, role_name FROM roles";
$roles_result = $conn->query($roles_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View/Edit User</title>
    <link rel="stylesheet" href="../css/system_admin_dashboard.css"> <!-- For header and footer -->
    <link rel="stylesheet" href="../../css/admin_functions/edit_user.css"> <!-- For page-specific styles -->
    <script>
        // JavaScript function to toggle between view and edit mode
        function toggleEditMode() {
            var isEditMode = document.getElementById('editToggle').checked;
            var elements = document.querySelectorAll('.editable');
            elements.forEach(function (element) {
                element.disabled = !isEditMode; // Enable or disable form fields
            });

            // Show/hide the Update button based on the edit mode
            var updateButton = document.getElementById('updateButton');
            updateButton.style.display = isEditMode ? 'block' : 'none';
        }
    </script>
</head>
<body>
    <header>
        <img src="../../images/UI LOGO.jpeg" alt="University Logo" class="logo">
        <h1>University of Ibadan Maintenance Department</h1>
        <a href="../../php/dashboards/system_admin_dashboard.php" class="logout-button">MENU</a>
    </header>

    <main>
        <h2>View/Edit User Information</h2>

        <!-- View/Edit Mode Toggle -->
        <div class="toggle-section">
            <label for="editToggle">Edit Mode:</label>
            <input type="checkbox" id="editToggle" onchange="toggleEditMode()">
        </div>

        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <!-- View/Edit User Form -->
        <form method="POST">
            <input type="hidden" name="matric_or_staff_id" value="<?php echo htmlspecialchars($user['matric_or_staff_id'] ?? ''); ?>">

            <!-- User Info Table -->
            <table class="user-info-table">
                <tr>
                    <td><label for="name">Name:</label></td>
                    <td><input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" class="editable" disabled></td>
                </tr>
                <tr>
                    <td><label for="phone_number">Phone Number:</label></td>
                    <td><input type="text" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>" class="editable" disabled></td>
                </tr>
                <tr>
                    <td><label for="department">Department:</label></td>
                    <td><input type="text" id="department" name="department" value="<?php echo htmlspecialchars($user['department'] ?? ''); ?>" class="editable" disabled></td>
                </tr>
                <tr>
                    <td><label for="faculty">Faculty:</label></td>
                    <td><input type="text" id="faculty" name="faculty" value="<?php echo htmlspecialchars($user['faculty'] ?? ''); ?>" class="editable" disabled></td>
                </tr>
                <tr>
                    <td><label for="email">Email:</label></td>
                    <td><input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" class="editable" disabled></td>
                </tr>
                <tr>
                    <td><label for="role">Role:</label></td>
                    <td>
                        <select id="role" name="role" class="editable" disabled>
                            <option value="">Select Role</option>
                            <?php if ($roles_result): ?>
                                <?php while ($role = $roles_result->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($role['id']); ?>" <?php if ($user['role'] === $role['role_name']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($role['role_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><label for="unit_id">Unit ID:</label></td>
                    <td>
                        <select id="unit_id" name="unit_id" class="editable" disabled>
                            <option value="">Select Unit</option>
                            <?php if ($unit_result): ?>
                                <?php while ($row = $unit_result->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($row['unit_id']); ?>" <?php if ($user['unit_id'] === $row['unit_id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($row['unit_id']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </td>
                </tr>
                <!-- New Sub-Unit Selection -->
                <tr>
                    <td><label for="sub_unit_id">Sub Unit:</label></td>
                    <td>
                        <select id="sub_unit_id" name="sub_unit_id" class="editable">
                            <option value="">Select Sub-Unit</option>
                            <?php if ($sub_unit_result): ?>
                                <?php while ($row = $sub_unit_result->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($row['sub_unitid']); ?>" <?php if ($user['sub_unitid'] === $row['sub_unitid']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($row['sub_unitname']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </td>
                </tr>
            </table>

            <!-- Buttons -->
            <div class="button-section">
                <button type="submit" name="edit_mode" id="updateButton" style="display:none;">UPDATE USER INFO</button>
            </div>
        </form>
    </main>

    <footer>
        <p>&copy; 2024 University Maintenance System. All rights reserved.</p>
    </footer>
</body>
</html>

<?php
$conn->close();
?>
