<?php
// Start output buffering
ob_start();

require_once("../database/db_connect.php");
include("../../php/common/session_manager.php"); // Include session management script

// Handle adding a new unit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_unit'])) {
        $new_unit_id = trim($_POST['unit_id']);
        $new_unit_name = trim($_POST['unit_name']);

        // Validate inputs
        if (!empty($new_unit_id) && !empty($new_unit_name)) {
            // Check if unit_id or unit_name already exists in the database
            $check_sql = "SELECT COUNT(*) FROM units WHERE unit_id = ? OR unit_name = ?";
            $check_stmt = $conn->prepare($check_sql);

            if ($check_stmt) {
                $check_stmt->bind_param('ss', $new_unit_id, $new_unit_name);
                $check_stmt->execute();
                $check_stmt->bind_result($count);
                $check_stmt->fetch();
                $check_stmt->close();

                if ($count > 0) {
                    $_SESSION['error_message'] = "Unit ID or Name already exists. Please choose a different one.";
                } else {
                    // Insert the new unit
                    $sql = "INSERT INTO units (unit_id, unit_name) VALUES (?, ?)";
                    $stmt = $conn->prepare($sql);

                    if ($stmt) {
                        $stmt->bind_param('ss', $new_unit_id, $new_unit_name);
                        if ($stmt->execute()) {
                            $_SESSION['success_message'] = "Unit added successfully!";
                        } else {
                            $_SESSION['error_message'] = "Error: " . $stmt->error;
                        }
                        $stmt->close();
                    } else {
                        $_SESSION['error_message'] = "Error preparing statement: " . $conn->error;
                    }
                }
            } else {
                $_SESSION['error_message'] = "Error checking existing units: " . $conn->error;
            }
        } else {
            $_SESSION['error_message'] = "Unit ID and Name cannot be empty.";
        }

        // Redirect to the same page
        header("Location: manage_units.php");
        exit();
    }

    // Handle deleting a unit
    if (isset($_POST['delete_unit'])) {
        $unit_id_to_delete = trim($_POST['unit_id']);

        if (!empty($unit_id_to_delete)) {
            $sql = "DELETE FROM units WHERE unit_id = ?";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param('s', $unit_id_to_delete);
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Unit deleted successfully!";
                } else {
                    $_SESSION['error_message'] = "Error: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $_SESSION['error_message'] = "Error preparing statement: " . $conn->error;
            }
        } else {
            $_SESSION['error_message'] = "Unit ID cannot be empty.";
        }

        // Redirect to the same page
        header("Location: manage_units.php");
        exit();
    }
}

// Fetch all units for display
$unit_sql = "SELECT unit_id, unit_name FROM units";
$unit_result = $conn->query($unit_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Units</title>
    <link rel="stylesheet" href="../../css/admin_functions/manage_roles_units.css">
    <script>
        // Function to check if both fields are filled
        function checkFields() {
            var unitId = document.getElementById("unit_id").value.trim();
            var unitName = document.getElementById("unit_name").value.trim();
            var addButton = document.getElementById("add_unit_button");

            if (unitId !== "" && unitName !== "") {
                addButton.disabled = false; // Enable the button
                addButton.style.backgroundColor = ''; // Reset to original color
            } else {
                addButton.disabled = true; // Disable the button
                addButton.style.backgroundColor = '#ccc'; // Change background to gray
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

            <!-- Section for Adding New Unit -->
            <section class="management-section">
                <h2>Add New Unit</h2>
                <form method="POST" action="manage_units.php" class="management-form">
                    <input type="text" id="unit_id" name="unit_id" placeholder="Unit ID" required oninput="checkFields()">
                    <input type="text" id="unit_name" name="unit_name" placeholder="Unit Name" required oninput="checkFields()">
                    <button type="submit" id="add_unit_button" name="add_unit" disabled>Add New Unit</button>
                </form>
            </section>

            <!-- Section for Displaying Existing Units -->
            <section class="management-section">
                <h2>Existing Units</h2>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Unit ID</th>
                            <th>Unit Name</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($unit_result->num_rows > 0): ?>
                            <?php $serial = 1; while ($row = $unit_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $serial++; ?></td>
                                    <td><?php echo htmlspecialchars($row['unit_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['unit_name']); ?></td>
                                    <td>
                                        <form method="POST" action="manage_units.php">
                                            <input type="hidden" name="unit_id" value="<?php echo htmlspecialchars($row['unit_id']); ?>">
                                            <button type="submit" name="delete_unit" class="delete-button">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">No units found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
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
