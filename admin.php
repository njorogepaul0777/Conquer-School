<?php
$conn = new mysqli("localhost", "root", "", "school");

$username = 'admin';
$password = password_hash('123456', PASSWORD_DEFAULT); // Secure hashing

$stmt = $conn->prepare("INSERT INTO admin_users (username, password) VALUES (?, ?)");
$stmt->bind_param("ss", $username, $password);
$stmt->execute();

echo "Admin user created.";
?>
