<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Check for POST request and email field
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["email"])) {
    $email = trim($_POST["email"]);

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: home.php?subscribed=0"); // Invalid email
        exit();
    }

    // Check if email already exists
    $check = $conn->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        header("Location: home.php?subscribed=2"); // Already subscribed
        exit();
    }

    // Insert new email
    $stmt = $conn->prepare("INSERT INTO newsletter_subscribers (email, subscribed_at) VALUES (?, NOW())");
    $stmt->bind_param("s", $email);
    if ($stmt->execute()) {
        header("Location: home.php?subscribed=1"); // Success
    } else {
        header("Location: home.php?subscribed=0"); // Insert error
    }

    $stmt->close();
    $check->close();
} else {
    header("Location: home.php"); // Invalid access
}
$conn->close();
?>
