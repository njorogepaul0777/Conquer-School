<?php
session_start();
$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if (!isset($_SESSION['teacher_email'])) {
    header("Location: teacher_login.php");
    exit();
}

$email = $_SESSION['teacher_email'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password_input = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, full_name, password FROM teachers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($id, $full_name, $hashed_password);
    $stmt->fetch();

    if (password_verify($password_input, $hashed_password)) {
        $_SESSION['teacher_logged_in'] = true;
        $_SESSION['teacher_name'] = $full_name;
        $_SESSION['teacher_id'] = $id;
        header("Location: teacher_dashboard.php");
        exit();
    } else {
        $error = "Incorrect password.";
    }

    $stmt->close();
}
$conn->close();
?>

<form method="POST">
  <h2>Enter Your Password</h2>
  <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
  <label>Password:</label><br>
  <input type="password" name="password" required><br><br>
  <button type="submit">Login</button>
</form>
