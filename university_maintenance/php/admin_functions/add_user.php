<?php 
require 'db_connect.php'; // Include database connection
include("../../php/common/session_manager.php"); // Include session management script

// Prevent caching of this page
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // For proxy caches
echo "Session ID: " . session_id() . "<br>";
echo "User ID: " . $_SESSION['user_id'] . "<br>";
echo "Role: " . $_SESSION['role'] . "<br>";

// Fetch all units for the dropdown
$unit_sql = "SELECT unit_id FROM units"; // Adjust table name as needed
$unit_result = $conn->query($unit_sql);

// Fetch all roles for the dropdown
$role_sql = "SELECT role_name FROM roles"; // Adjust table name as needed
$role_result = $conn->query($role_sql);

// Handle adding new users
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $matric_or_staff_id = $_POST['matric_or_staff_id'];
    $name = $_POST['name'];
    $phone_number = $_POST['phone_number'];
    $department = $_POST['department'];
    $faculty = $_POST['faculty'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Hash password
    $role = $_POST['role'];
    $unit_id = $_POST['unit_id'];

    $insert_sql = "INSERT INTO users (matric_or_staff_id, name, phone_number, department, faculty, email, password, role, unit_id)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param('sssssssss', $matric_or_staff_id, $name, $phone_number, $department, $faculty, $email, $password, $role, $unit_id);

    if ($stmt->execute()) {
        $success_message = "User successfully added.";
    } else {
        $error_message = "Error adding user: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New User</title>
    <link rel="stylesheet" href="../css/add_user.css">
    <!-- Include Font Awesome for icons -->
</head>
<body>
    <header>
        <a href="../html/index.html">
            <img src="../images/UI LOGO.jpeg" alt="University Logo" class="logo">
        </a>
        <h1>University of Ibadan Maintenance Department</h1>
        <a href="../php/logout.php" class="logout-button">Logout</a>
    </header>
    <main>
        <h2>Add New User</h2>
        <?php if (isset($success_message)) : ?>
            <p><?php echo $success_message; ?></p>
        <?php elseif (isset($error_message)) : ?>
            <p><?php echo $error_message; ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <input type="text" name="matric_or_staff_id" placeholder="Matric/Staff ID" required>
            <input type="text" name="name" placeholder="Name" required>
            <input type="text" name="phone_number" placeholder="Phone Number" required>
            <input type="text" name="department" placeholder="Department" required>
            <input type="text" name="faculty" placeholder="Faculty" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <select name="role" required>
                <option value="">Select Role</option>
                <?php while ($role_row = $role_result->fetch_assoc()) : ?>
                    <option value="<?php echo htmlspecialchars($role_row['role_name']); ?>">
                        <?php echo htmlspecialchars($role_row['role_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <select name="unit_id" required>
                <option value="">Select Unit</option>
                <?php while ($unit_row = $unit_result->fetch_assoc()) : ?>
                    <option value="<?php echo htmlspecialchars($unit_row['unit_id']); ?>">
                        <?php echo htmlspecialchars($unit_row['unit_id']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <button type="submit" name="add_user">Add User</button>
        </form>
    </main>
    <footer>
        <p>&copy; <?php echo date('Y'); ?> University of Ibadan Maintenance Department. All rights reserved.</p>
    </footer>
</body>
</html>
