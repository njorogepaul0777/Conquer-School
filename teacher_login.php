<?php
session_start();
$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$step = "email";
$error = "";
$email_value = "";
$login_type = ""; // 'teacher' or 'staff'

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email_value = $_POST['email'];

    if (isset($_POST['check_email'])) {
        $email = $conn->real_escape_string($_POST['email']);

        // 🔄 Clear any old session data first
        unset($_SESSION['staff_id'], $_SESSION['staff_name'], $_SESSION['staff_email'], $_SESSION['staff_role'], $_SESSION['staff_logged_in']);
        unset($_SESSION['teacher_id'], $_SESSION['teacher_name'], $_SESSION['teacher_email'], $_SESSION['teacher_logged_in']);

        // First check in staff_users
        $stmt = $conn->prepare("SELECT id, full_name, password, role FROM staff_users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Staff found
            $stmt->bind_result($id, $full_name, $hashed_password, $role);
            $stmt->fetch();

            $_SESSION['staff_id'] = $id;
            $_SESSION['staff_name'] = $full_name;
            $_SESSION['staff_email'] = $email;
            $_SESSION['staff_role'] = $role;
            $login_type = "staff";
            $step = "password";

        } else {
            // Check in teachers
            $stmt->close();
            $stmt = $conn->prepare("SELECT id, full_name, password FROM teachers WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($id, $full_name, $hashed_password);
                $stmt->fetch();

                $_SESSION['teacher_id'] = $id;
                $_SESSION['teacher_name'] = $full_name;
                $_SESSION['teacher_email'] = $email;
                $login_type = "teacher";

                $step = empty($hashed_password) ? "set_password" : "password";
            } else {
                $error = "❌ No user found with that email.";
                $step = "email";
            }
        }
        $stmt->close();
    }

    // Staff password login
    elseif (isset($_POST['password_step']) && isset($_SESSION['staff_email'])) {
        $email = $_SESSION['staff_email'];
        $password_input = $_POST['password'];

        $stmt = $conn->prepare("SELECT id, full_name, password, role FROM staff_users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($id, $full_name, $hashed_password, $role);
        $stmt->fetch();

        if (password_verify($password_input, $hashed_password)) {
            $_SESSION['staff_logged_in'] = true;
            $_SESSION['staff_id'] = $id;
            $_SESSION['staff_name'] = $full_name;
            $_SESSION['staff_role'] = $role;

            // Redirect based on role
            switch ($role) {
                case 'admin':
                    header("Location: admin_dashboard.php");
                    break;
                case 'accounts':
                    header("Location: accounts_dashboard.php");
                    break;
                case 'librarian':
                    header("Location: librarian_dashboard.php");
                    break;
                case 'nurse':
                    header("Location: nurse_dashboard.php");
                    break;
                case 'secretary':
                    header("Location: secretary_dashboard.php");
                    break;
                default:
                    $error = "❌ Role not recognized.";
                    $step = "password";
            }
            exit();
        } else {
            $error = "❌ Incorrect password.";
            $step = "password";
        }
        $stmt->close();
    }

    // Teacher password login
    elseif (isset($_POST['password_step']) && isset($_SESSION['teacher_email'])) {
        $email = $_SESSION['teacher_email'];
        $password_input = $_POST['password'];

        $stmt = $conn->prepare("SELECT id, full_name, password FROM teachers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($id, $full_name, $hashed_password);
        $stmt->fetch();

        if (password_verify($password_input, $hashed_password)) {
            $_SESSION['teacher_logged_in'] = true;
            header("Location: teacher_dashboard.php");
            exit();
        } else {
            $error = "❌ Incorrect password.";
            $step = "password";
        }
        $stmt->close();
    }

    // Teacher first time set password
    elseif (isset($_POST['set_password_step']) && isset($_SESSION['teacher_id'])) {
        $teacher_id = $_SESSION['teacher_id'];
        $new_password = $_POST['new_password'];
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE teachers SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed, $teacher_id);
        if ($stmt->execute()) {
            $_SESSION['teacher_logged_in'] = true;
            header("Location: teacher_dashboard.php");
            exit();
        } else {
            $error = "❌ Failed to set password.";
            $step = "set_password";
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <style>
        body {
            font-family: Arial;
            background: #f0f2f5;
            padding: 50px;
        }
        form {
            background: white;
            padding: 30px;
            max-width: 400px;
            margin: auto;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.2);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }
        input {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            margin-top: 20px;
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover {
            background: #0056b3;
        }
        p.error {
            color: red;
            text-align: center;
        }
    </style>
</head>
<body>

<form method="POST">
    <h2>User Login</h2>

    <?php if (!empty($error)): ?>
        <p class="error"><?= $error ?></p>
    <?php endif; ?>

    <label>Email:</label>
    <input type="email" name="email" value="<?= htmlspecialchars($email_value) ?>" <?= ($step !== "email" ? "readonly" : "") ?> required>

    <?php if ($step === "email"): ?>
        <button type="submit" name="check_email">Next</button>

    <?php elseif ($step === "password"): ?>
        <label>Password:</label>
        <input type="password" name="password" required>
        <button type="submit" name="password_step">Login</button>

    <?php elseif ($step === "set_password"): ?>
        <label>Set Password:</label>
        <input type="password" name="new_password" required>
        <button type="submit" name="set_password_step">Set & Login</button>
    <?php endif; ?>
</form>

</body>
</html>
