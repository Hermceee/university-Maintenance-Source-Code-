<?php
require_once("../database/db_connect.php");

$errors = [];
$successMessage = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['name'], $_POST['phone_number'], $_POST['id'], $_POST['department'], $_POST['faculty'], $_POST['email'], $_POST['password'], $_POST['password_confirm'])) {
        $name = $_POST['name'];
        $phone_number = $_POST['phone_number'];
        $id = $_POST['id'];
        $department = $_POST['department'];
        $faculty = $_POST['faculty'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $password_confirm = $_POST['password_confirm'];

        if ($password !== $password_confirm) {
            $errors[] = "Passwords do not match.";
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Check if the ID already exists
            $checkSql = "SELECT matric_or_staff_id FROM users WHERE matric_or_staff_id = ?";
            $stmt = $conn->prepare($checkSql);
            $stmt->bind_param("s", $id);

            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    // ID already exists
                    $errors[] = "This ID is already registered.";
                } else {
                    // ID does not exist, proceed with registration
                    $default_role = 'Normal'; // Set the default role here

                    $sql = "INSERT INTO users (name, phone_number, matric_or_staff_id, department, faculty, email, password, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);

                    if ($stmt) {
                        $stmt->bind_param("ssssssss", $name, $phone_number, $id, $department, $faculty, $email, $hashed_password, $default_role);

                        if ($stmt->execute()) {
                            $successMessage = "Registration successful! Redirecting to login page...";
                            header("refresh:3;url=../../php/profiles/login.php");
                            exit();
                        } else {
                            $errors[] = "Error: Could not execute SQL statement.";
                        }
                    } else {
                        $errors[] = "Error: Could not prepare SQL statement.";
                    }
                }
            } else {
                $errors[] = "Error: Could not execute SQL statement.";
            }

            $stmt->close();
        }

        $conn->close();
    } else {
        $errors[] = "All fields are required.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Maintenance - Register</title>
    <link rel="stylesheet" href="../../css/profiles/register.css">
    <style>
        .show-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }
        .password-wrapper {
            position: relative;
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
            <a href="../html/about.html" class="nav-link">About</a>
            <a href="../html/contact.html" class="nav-link">Contact Us</a>
        </nav>
    </header>

    <main>
        <h2>Register</h2>
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
    <table class="form-table">
        <tr>
            <td class="label-cell"><label for="name">Name:</label></td>
            <td><input type="text" id="name" name="name" required></td>
        </tr>
        <tr>
            <td class="label-cell"><label for="phone_number">Phone Number:</label></td>
            <td><input type="text" id="phone_number" name="phone_number" required></td>
        </tr>
        <tr>
            <td class="label-cell"><label for="id">ID (Staff Number):</label></td>
            <td><input type="text" id="id" name="id" required></td>
        </tr>
        <tr>
            <td class="label-cell"><label for="department">Department:</label></td>
            <td><input type="text" id="department" name="department" required></td>
        </tr>
        <tr>
            <td class="label-cell"><label for="faculty">Faculty:</label></td>
            <td><input type="text" id="faculty" name="faculty" required></td>
        </tr>
        <tr>
            <td class="label-cell"><label for="email">Email:</label></td>
            <td><input type="email" id="email" name="email" required></td>
        </tr>
        <tr>
            <td class="label-cell"><label for="password">Password:</label></td>
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
        <tr>
            <td class="label-cell"><label for="password_confirm">Confirm Password:</label></td>
            <td>
                <div class="password-wrapper">
                    <input type="password" id="password_confirm" name="password_confirm" required
                           minlength="8"
                           pattern="(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\W_]).*"
                           title="Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character.">
                    <span class="show-password" onclick="togglePassword('password_confirm')">👁️</span>
                </div>
            </td>
        </tr>
       
    </table>
	                <button type="submit">Submit</button>

</form>



        <div class="login-section">
    <p>Already registered?</p>
    <a href="../../php/profiles/login.php" class="alt-button">Login</a>
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
