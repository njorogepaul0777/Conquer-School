<?php
session_start();
$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if (!isset($_SESSION['teacher_id'])) {
    header("Location: teacher_login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $password = $_POST['password'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $teacher_id = $_SESSION['teacher_id'];

    $stmt = $conn->prepare("UPDATE teachers SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $teacher_id);
    if ($stmt->execute()) {
        unset($_SESSION['teacher_id']); // Clear ID
        header("Location: teacher_login.php?msg=password_set");
        exit();
    } else {
        $error = "Failed to set password.";
    }
    $stmt->close();
}
$conn->close();
?>

<form method="POST">
  <h2>Set Your Password</h2>
  <?php if (!empty($error)) echo "<p style='color:red;'>$error</p>"; ?>
  <label>New Password:</label><br>
  <input type="password" name="password" required><br><br>
  <button type="submit">Set Password</button>
</form>
