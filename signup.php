<?php
session_start();
$conn = new mysqli("localhost", "root", "", "school");

$message = "";

// When the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admission_no = $conn->real_escape_string($_POST['admission_no']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Check if admission_no exists in students_admitted and status=1 (approved)
    $stmt = $conn->prepare("SELECT * FROM students_admitted WHERE admission_no=? AND status=1");
    $stmt->bind_param("s", $admission_no);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // 2. Check if already signed up
        $check_user = $conn->prepare("SELECT * FROM student_users WHERE admission_no=?");
        $check_user->bind_param("s", $admission_no);
        $check_user->execute();
        $user_result = $check_user->get_result();

        if ($user_result->num_rows === 0) {
            // 3. Check passwords match
            if ($password === $confirm_password) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // 4. Insert into student_users
                $insert = $conn->prepare("INSERT INTO student_users (admission_no, password) VALUES (?, ?)");
                $insert->bind_param("ss", $admission_no, $hashed_password);
                if ($insert->execute()) {
                    $message = "<span class='success'>✅ Account created successfully! <a href='login.php'>Login here</a>.</span>";
                } else {
                    $message = "<span class='error'>❌ Error creating account: ".$insert->error."</span>";
                }
            } else {
                $message = "<span class='error'>❌ Passwords do not match!</span>";
            }
        } else {
            $message = "<span class='error'>❌ This admission number has already signed up!</span>";
        }
    } else {
        $message = "<span class='error'>❌ Admission number not found or not approved yet!</span>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Sign Up - Conquer High School</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(to right, #74ebd5, #ACB6E5);
        min-height: 100vh;
        display: flex; justify-content: center; align-items: center;
    }
    .signup-container {
        background: #fff;
        padding: 40px 30px;
        width: 350px;
        border-radius: 12px;
        box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    }
    .signup-container h2 {
        text-align: center;
        color: #003366;
        margin-bottom: 25px;
    }
    .form-group {
        position: relative;
        margin-bottom: 25px;
    }
    .form-group input {
        width: 100%;
        padding: 12px 10px 12px 10px;
        font-size: 16px;
        border: 1px solid #ccc;
        border-radius: 6px;
        outline: none;
        background: transparent;
    }
    .form-group label {
        position: absolute;
        left: 12px;
        top: 12px;
        background: #fff;
        padding: 0 5px;
        color: #999;
        font-size: 14px;
        transition: 0.3s ease;
        pointer-events: none;
    }
    .form-group input:focus + label,
    .form-group input:not(:placeholder-shown) + label {
        top: -10px;
        left: 8px;
        color: #007BFF;
        font-size: 12px;
    }
    button {
        width: 100%;
        padding: 12px;
        background: #007BFF;
        color: #fff;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        cursor: pointer;
        transition: background 0.3s;
    }
    button:hover {
        background: #0056b3;
    }
    .message {
        text-align: center;
        margin: 10px 0;
        font-size: 14px;
    }
    .error { color: red; }
    .success { color: green; }
    .note {
        font-size: 12px;
        color: #555;
        text-align: center;
        margin-top: 10px;
    }
    .login-link {
        text-align: center;
        margin-top: 15px;
        font-size: 13px;
    }
    .login-link a {
        color: #007BFF;
        text-decoration: none;
    }
    .login-link a:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>

<div class="signup-container">
    <h2>Student Sign Up</h2>
    <div class="message"><?= $message ?></div>
    <form method="POST" autocomplete="off">
        <div class="form-group">
            <input type="text" name="admission_no" required placeholder=" " />
            <label>Admission Number</label>
        </div>

        <div class="form-group">
            <input type="password" name="password" required placeholder=" " />
            <label>Password</label>
        </div>

        <div class="form-group">
            <input type="password" name="confirm_password" required placeholder=" " />
            <label>Confirm Password</label>
        </div>

        <button type="submit">Create Account</button>
    </form>
    <div class="note">* Use your approved Admission Number provided by the school.</div>
    <div class="login-link">Already have an account? <a href="login.php">Login here</a></div>
</div>

</body>
</html>
