<?php
// Start output buffering
ob_start();

require_once("../database/db_connect.php"); // Corrected the relative path for the database connection
include("../../php/common/session_manager.php"); // Include session management script

// Define a flag to check for successful operations
$success_redirect = false;

// Handle adding or deleting roles
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_role'])) {
        // Add role logic
        $new_role = $_POST['role_name'];

        // Check if the role already exists
        $check_role_sql = "SELECT COUNT(*) FROM roles WHERE role_name = ?";
        $stmt_check = $conn->prepare($check_role_sql);
        $stmt_check->bind_param('s', $new_role);
        $stmt_check->execute();
        $stmt_check->bind_result($role_count);
        $stmt_check->fetch();
        $stmt_check->close();

        if ($role_count > 0) {
            $_SESSION['error_message'] = "Error: Role already exists!";
        } else {
            // Insert new role if it doesn't exist
            $sql = "INSERT INTO roles (role_name) VALUES (?)";
            $bind_param_type = 's';
            $bind_param_value = $new_role;

            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param($bind_param_type, $bind_param_value);

                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Role added successfully!";
                    $success_redirect = true;
                } else {
                    $_SESSION['error_message'] = "Error: " . $stmt->error;
                }

                $stmt->close();
            } else {
                $_SESSION['error_message'] = "Error preparing statement: " . $conn->error;
            }
        }
    } elseif (isset($_POST['delete_role'])) {
        // Delete role logic
        $role_to_delete = $_POST['role_name'];
        $sql = "DELETE FROM roles WHERE role_name = ?";
        $bind_param_type = 's';
        $bind_param_value = $role_to_delete;

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($bind_param_type, $bind_param_value);

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Role deleted successfully!";
                $success_redirect = true;
            } else {
                $_SESSION['error_message'] = "Error: " . $stmt->error;
            }

            $stmt->close();
        } else {
            $_SESSION['error_message'] = "Error preparing statement: " . $conn->error;
        }
    }

    // If success, trigger a JavaScript redirect with a 1-second delay
    if ($success_redirect) {
        echo '<script type="text/javascript">
                setTimeout(function() {
                    window.location.href = "manage_roles.php"; // Redirects back to the same page
                }, 1000); // 1 second delay
              </script>';
    }
}

// Fetch all roles for display
$role_sql = "SELECT role_name FROM roles";
$role_result = $conn->query($role_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Roles</title>
    <link rel="stylesheet" href="../../css/admin_functions/manage_roles_units.css">

    <script>
        // Confirm deletion dialog
        function confirmDeletion(event, role) {
            if (!confirm(`Are you sure you want to delete this ${role}?`)) {
                event.preventDefault(); // Prevent form submission if the user cancels
            }
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
        <div class="management-container">
            <?php if (isset($_SESSION['success_message'])) : ?>
                <p class="success-message"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></p>
            <?php elseif (isset($_SESSION['error_message'])) : ?>
                <p class="error-message"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
            <?php endif; ?>

            <!-- Add New Role Section -->
            <div class="management-section">
                <h2>Add New Role</h2>
                <form method="POST" action="manage_roles.php" class="management-form">
                    <input type="text" name="role_name" placeholder="Enter New Role Name" required>
                    <button type="submit" name="add_role">Add Role</button>
                </form>
            </div>

            <!-- Existing Roles Section -->
            <div class="management-section">
                <h2>Existing Roles</h2>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Role Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $serial = 1; while ($row = $role_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $serial++; ?></td>
                                <td><?php echo htmlspecialchars($row['role_name']); ?></td>
                                <td>
                                    <form method="POST" action="manage_roles.php" onsubmit="confirmDeletion(event, 'role')">
                                        <input type="hidden" name="role_name" value="<?php echo htmlspecialchars($row['role_name']); ?>">
                                        <button type="submit" name="delete_role" class="delete-button">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 University of Ibadan. All Rights Reserved.</p>
    </footer>
</body>
</html>

<?php
// Close output buffering
ob_end_flush();
?>
