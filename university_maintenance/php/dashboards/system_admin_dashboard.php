<?php
session_start(); // Ensure this is at the very top of the file
include("../../php/database/db_connect.php"); // Include database connection

header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // For proxy caches
echo "Session ID: " . session_id() . "<br>";
echo "User ID: " . $_SESSION['user_id'] . "<br>";
echo "Role: " . $_SESSION['role'] . "<br>";

// Check if the user is logged in, if not redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../php/profiles/login.php"); // Redirect to login if not logged in
    exit(); // Ensure that the rest of the script does not run
}

session_regenerate_id(true); // Regenerate session ID to avoid session fixation
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../../css/dashboards/system_admin_dashboard.css">
</head>
<body>
    <header>
        <a href="../../html/profiles/index.html">
            <img src="../../images/UI LOGO.jpeg" alt="University Logo" class="logo">
        </a>
        <h1>University of Ibadan Maintenance Department</h1>
        <a href="../../php/profiles/logout.php" class="logout-button">LOG OUT</a>
    </header>

    <main>
        <h2>ADMIN'S DASHBOARD</h2>
        <section class="admin-functions">
            <a href="../../php/admin_functions/user_management.php" class="button">User Management</a>
            <a href="../../php/admin_functions/manage_issue & sub_depts.php" class="button">Manage Sub-Depts & Issues</a>
                                    <a href="../../php/admin_functions/manage_roles.php" class="button">Manage Roles</a>
                                    <a href="../../php/admin_functions/manage_units.php" class="button">Manage units</a>

						<a href="../../php/admin_functions/manage_sub_units.php" class="button">Manage Sub-units</a>
			<a href="../../php/admin_functions/submitted_requests.php" class="button">VIEW SUBMITTED REQUESTS</a>
            <a href="../../html/maintenance_allocation.html" class="button">Request Allocation</a>
            <a href="../../html/reports.html" class="button">Reports & Analytics</a>
        </section>
    </main>


    <footer>
        <p>&copy; 2024 University Maintenance System. All rights reserved.</p>
    </footer>
</body>
</html>
