<?php
require_once("../database/db_connect.php");
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
echo "Session ID: " . session_id() . "<br>";
echo "User ID: " . $_SESSION['user_id'] . "<br>";
echo "Role: " . $_SESSION['role'] . "<br>";

// Fetch all units for the filter dropdown
$unit_sql = "SELECT unit_id FROM units";
$unit_result = $conn->query($unit_sql);

// Fetch all roles from the role table
$role_sql = "SELECT role_name FROM roles";
$role_result = $conn->query($role_sql);

// Handle search or filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$search_query = '%' . $search . '%';

$filter_unit_id = isset($_GET['filter_unit']) ? $_GET['filter_unit'] : '';
$filter_role = isset($_GET['filter_role']) ? $_GET['filter_role'] : '';

$sql = "SELECT matric_or_staff_id, name, role, unit_id FROM users WHERE (matric_or_staff_id LIKE ? OR name LIKE ? OR unit_id LIKE ?)";
$params = [$search_query, $search_query, $search_query];

if ($filter_unit_id) {
    $sql .= " AND unit_id = ?";
    $params[] = $filter_unit_id;
}

if ($filter_role) {
    $sql .= " AND role = ?";
    $params[] = $filter_role;
}

$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat('s', count($params)), ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" href="../../css/admin_functions/user_management1.css">
</head>
<body>
    <header>
        <img src="../../images/UI LOGO.jpeg" alt="University Logo" class="logo">
        <h1>University of Ibadan Maintenance Department</h1>
        <div class="header-right">
            <a href="../../php/dashboards/system_admin_dashboard.php" class="Menu">MENU</a>
        </div>
    </header>

    <main>
        <h2>USER MANAGEMENT</h2>

        <div class="filters">
            <form method="GET" action="user_management.php" class="search-filter-form">
                <input type="text" name="search" placeholder="Search by ID, Name, or Unit ID" value="<?php echo htmlspecialchars($search); ?>" id="searchInput"/>
                <button type="submit" id="searchButton">Search</button>

                <label for="filter_unit">Unit:</label>
                <select name="filter_unit" id="unitFilter">
                    <option value="">All Units</option>
                    <?php while ($row = $unit_result->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($row['unit_id']); ?>" <?php if ($filter_unit_id == $row['unit_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($row['unit_id']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <label for="filter_role">Role:</label>
                <select name="filter_role" id="roleFilter">
                    <option value="">All Roles</option>
                    <?php while ($row = $role_result->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($row['role_name']); ?>" <?php if ($filter_role == $row['role_name']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($row['role_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <button type="submit" id="filterButton">Filter</button>
            </form>

            <a href="add_user.php" class="add-user-button">Add User</a>
        </div>

        <table>
    <thead>
        <tr>
            <th>S/N</th> <!-- Serial Number Column -->
            <th>Staff ID</th>
            <th>Name</th>
            <th>Role</th>
            <th>Unit ID</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php 
        $sn = 1; // Initialize serial number counter
        while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $sn++; ?></td> <!-- Display serial number -->
                <td><?php echo htmlspecialchars($row['matric_or_staff_id']); ?></td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td><?php echo htmlspecialchars($row['role']); ?></td>
                <td><?php echo htmlspecialchars($row['unit_id']); ?></td>
                <td>
    <a href="../../php/admin_functions/edit_user.php?id=<?php echo urlencode($row['matric_or_staff_id']); ?>" class="edit-button">VIEW / EDIT</a>
<a href="../../php/admin_functions/delete_user.php?id=<?php echo urlencode($row['matric_or_staff_id']); ?>" class="delete-button" onclick="return confirm('Are you sure you want to delete this user?')">DELETE</a>
</td>

            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

    </main>

    <footer>
        <p>&copy; 2024 University Maintenance System. All rights reserved.</p>
    </footer>
</body>
</html>

<?php
$conn->close();
?>
