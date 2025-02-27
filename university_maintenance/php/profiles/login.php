<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once("../../php/database/db_connect.php");

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

error_reporting(E_ALL);
ini_set('display_errors', 1);

$error_message = ''; // Initialize error message

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = trim($_POST['id']);
    $password = trim($_POST['password']);

    // Prepare and execute SQL query to check the user credentials
    $sql = "SELECT password, role FROM users WHERE matric_or_staff_id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param("s", $id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($db_password, $role);
        $stmt->fetch();

        if (password_verify($password, $db_password)) {
            // Regenerate session ID for added security
            session_regenerate_id(true);

            // Set session variables
            $_SESSION['user_id'] = $id;
            $_SESSION['role'] = $role;

            // Insert login details into login_security table
            $login_query = $conn->prepare("INSERT INTO login_security (userid, login_time) VALUES (?, NOW())");
            $login_query->bind_param('s', $id);
            $login_query->execute();

            // Redirect based on user role
            switch ($role) {
                case 'Inactive':
                    header("Location: ../../html/dashboards/inactive_dashboard.html");
                    break;
                case 'Normal':
                    header("Location: ../../php/dashboards/normal_dashboard.php");
                    break;
                case 'Sub-unit':
                    header("Location: ../../php/dashboards/Sub-unit_dashboard.php");
                    break;
                case 'HOU':
                    header("Location: ../../php/dashboards/hou_dashboard.php");
                    break;
                case 'Sub_admin':
                    header("Location: ../../php/dashboards/sub_admin_dashboard.html");
                    break;
                case 'Admin':
                    header("Location: ../../php/dashboards/system_admin_dashboard.php");
                    break;
                case 'Technician':
                    header("Location: ../../php/dashboards/technician_dashboard.html");
                    break;
                default:
                    echo "Unknown role.";
                    exit();
            }
            exit();
        } else {
            $error_message = "Invalid password.";
        }
    } else {
        $error_message = "Invalid user ID or password.";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Maintenance - Login</title>
    <link rel="stylesheet" href="../../css/profiles/login.css">
</head>
<body>
    <header>
        <a href="../../html/profiles/index.html">
            <img src="../../images/UI LOGO.jpeg" alt="University Logo" class="logo">
        </a>
        <h1>University of Ibadan Maintenance Department</h1>
        <nav>
            <a href="../../html/profiles/about.html" class="nav-link">About</a>
            <a href="../../html/profiles/contact.html" class="nav-link">Contact Us</a>
        </nav>
    </header>

    <main>
        <h2>Login</h2>
        <form action="login.php" method="post">
            <table>
                <tr>
                    <td><label for="id">ID (Staff Number):</label></td>
                    <td><input type="text" id="id" name="id" required></td>
                </tr>
                <tr>
                    <td><label for="password">Password:</label></td>
                    <td>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" required
                                   minlength="8"
                                   pattern="(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\W_]).*"
                                   title="Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character.">
                            <span class="show-password" onclick="togglePassword('password')">👁️</span>
                        </div>
                    </td>
                </tr>

                <!-- Display error message if credentials are invalid -->
                <?php if (!empty($error_message)) : ?>
                    <tr>
                        <td colspan="2" style="color: red; text-align: center;"><?php echo $error_message; ?></td>
                    </tr>
                <?php endif; ?>
            </table>
            <button type="submit">Login</button>
        </form>

        <div class="register-section">
            <p>Not registered yet?</p>
            <a href="../../php/profiles/register.php" class="alt-button">Register</a>
        </div>

        <div class="forgot-password-section">
            <p><a href="../../php/common/forgot_password.php" class="forgot-password-link">Forgot your password?</a></p>
        </div>
    </main>

    <footer>
        <p>&copy; 2024 University Maintenance System. All rights reserved.</p>
    </footer>

    <script>
        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            passwordField.type = passwordField.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>
