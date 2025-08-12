<?php
session_start();
$conn = new mysqli("localhost", "root", "", "school");

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admission_no = $conn->real_escape_string($_POST['admission_no']);
    $password = $_POST['password'];

    // Check if the student exists in student_users
    $stmt = $conn->prepare("SELECT * FROM student_users WHERE admission_no=?");
    $stmt->bind_param("s", $admission_no);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
			
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['admission_no'] = $user['admission_no'];
			
            header("Location: student_dashboard.php"); // redirect after successful login
            exit;
        } else {
            $message = "<span class='error'>❌ Incorrect password!</span>";
        }
    } else {
        $message = "<span class='error'>❌ Admission Number not found! Please sign up first.</span>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Login - Conquer High School</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(to right, #74ebd5, #ACB6E5);
        min-height: 100vh;
        display: flex; justify-content: center; align-items: center;
    }
    .login-container {
        background: #fff;
        padding: 40px 30px;
        width: 350px;
        border-radius: 12px;
        box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    }
    .login-container h2 {
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
    .signup-link {
        text-align: center;
        margin-top: 15px;
        font-size: 13px;
    }
    .signup-link a {
        color: #007BFF;
        text-decoration: none;
    }
    .signup-link a:hover {
        text-decoration: underline;
    }
</style>
</head>
<body>

<div class="login-container">
    <h2>Student Login</h2>
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

        <button type="submit">Login</button>
    </form>
    <div class="signup-link">Don't have an account? <a href="signup.php">Sign up here</a></div>
</div>

</body>
</html>
