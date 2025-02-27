<?php
require_once("../database/db_connect.php");
include("../../php/common/session_manager.php"); // Include session management script

$errors = [];
$successMessage = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['id'], $_POST['password'], $_POST['confirm_password'])) {
        $id = trim($_POST['id']);
        $password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']);

        // Ensure passwords match
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        } else {
            // Hash the new password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Check if the ID exists in the database
            $checkSql = "SELECT matric_or_staff_id FROM users WHERE matric_or_staff_id = ?";
            $stmt = $conn->prepare($checkSql);

            if ($stmt) {
                $stmt->bind_param("s", $id);
                if ($stmt->execute()) {
                    $stmt->store_result();
                    if ($stmt->num_rows > 0) {
                        // Update the new password
                        $sql = "UPDATE users SET password = ? WHERE matric_or_staff_id = ?";
                        $stmt = $conn->prepare($sql);

                        if ($stmt) {
                            $stmt->bind_param("ss", $hashed_password, $id);
                            if ($stmt->execute()) {
                                if ($stmt->affected_rows > 0) {
                                    $successMessage = "Password has been updated successfully. Redirecting to login page...";
                                    header("refresh:3;url=../../html/profiles/login.html");
                                    exit();
                                } else {
                                    $errors[] = "No changes were made. The new password may be the same as the old one.";
                                }
                            } else {
                                $errors[] = "Error executing update statement: " . $stmt->error;
                            }
                        } else {
                            $errors[] = "Error preparing update statement.";
                        }
                    } else {
                        $errors[] = "User ID does not exist.";
                    }
                } else {
                    $errors[] = "Error executing check statement: " . $stmt->error;
                }
            } else {
                $errors[] = "Error preparing check statement.";
            }

            $stmt->close();
        }
    } else {
        $errors[] = "All fields are required.";
    }

    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="../../css/profiles/login.css">
    <style>
        .show-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }
        .error {
            color: red;
        }
        .success {
            color: green;
        }
    </style>
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
        <h2>Reset Password</h2>
        <?php if (!empty($errors)): ?>
            <div class="error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($successMessage): ?>
            <div class="success">
                <p><?php echo htmlspecialchars($successMessage); ?></p>
            </div>
        <?php endif; ?>

        <form action="" method="post">
            <label for="id">Enter your ID:</label>
            <input type="text" id="id" name="id" required>
            
            <label for="password">New Password:</label>
            <div style="position: relative;">
                <input type="password" id="password" name="password" required>
                <span class="show-password" onclick="togglePassword()">👁️</span>
            </div>
            
            <label for="confirm_password">Confirm Password:</label>
            <div style="position: relative;">
                <input type="password" id="confirm_password" name="confirm_password" required>
                <span class="show-password" onclick="togglePassword()">👁️</span>
            </div>
            
            <button type="submit">Submit</button>
        </form>

        <script>
            function togglePassword() {
                const passwordFields = document.querySelectorAll('#password, #confirm_password');
                passwordFields.forEach(field => {
                    field.type = field.type === 'password' ? 'text' : 'password';
                });
            }
        </script>
    </main>

    <footer>
        <p>&copy; 2024 University Maintenance System. All rights reserved.</p>
    </footer>
</body>
</html>
