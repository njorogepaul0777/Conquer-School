<?php
$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_subject'])) {
    $name = trim($_POST['name']);
    $curriculum = $_POST['curriculum'];
    $category = $_POST['category'];

    if (!empty($name) && !empty($curriculum)) {
        $stmt = $conn->prepare("INSERT INTO subject (name, curriculum, category) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $curriculum, $category);
        $stmt->execute();
        $message = "<p style='color:green;'>✅ Subject added successfully.</p>";
    } else {
        $message = "<p style='color:red;'>❌ Name and Curriculum are required.</p>";
    }
}

// Fetch subjects
$subjects = $conn->query("SELECT * FROM subjects ORDER BY name");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin: Manage Subjects</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f4f4f4; }
        h2 { text-align: center; }
        form, table { background: white; padding: 20px; margin: auto; max-width: 800px; border-radius: 10px; box-shadow: 0 0 10px #ccc; }
        input, select, button { padding: 10px; margin: 10px 0; width: 100%; }
        table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        th, td { padding: 8px; border: 1px solid #ccc; text-align: center; }
        button { background: #004080; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0055aa; }
        .message { text-align: center; }
    </style>
</head>
<body>

<h2>📘 Subject Management - Admin Panel</h2>

<?php if (isset($message)) echo "<div class='message'>{$message}</div>"; ?>

<form method="POST">
    <label>Subject Name:</label>
    <input type="text" name="name" required>

    <label>Curriculum:</label>
    <select name="curriculum" required>
        <option value="">-- Select Curriculum --</option>
        <option value="8-4-4">8-4-4</option>
        <option value="CBC">CBC</option>
    </select>

    <label>Category:</label>
    <select name="category">
        <option value="">-- Optional: Select Category --</option>
        <option value="science">Science</option>
        <option value="humanities">Humanities</option>
        <option value="language">Language</option>
        <option value="technology">Technology</option>
        <option value="business">Business</option>
        <option value="personal_dev">Personal Development</option>
    </select>

    <button type="submit" name="add_subject">➕ Add Subject</button>
</form>

<?php if ($subjects && $subjects->num_rows > 0): ?>
    <table>
        <tr>
            <th>ID</th>
            <th>Subject</th>
            <th>Curriculum</th>
            <th>Category</th>
            <th>Status</th>
        </tr>
        <?php while ($row = $subjects->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['curriculum']) ?></td>
                <td><?= htmlspecialchars($row['category']) ?></td>
                <td><?= $row['is_active'] ? '✅ Active' : '❌ Inactive' ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
<?php else: ?>
    <p style="text-align:center;">No subjects found.</p>
<?php endif; ?>

</body>
</html>
