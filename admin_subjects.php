<?php
// Start the session
session_start();

// Redirect to login if admin not logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// DELETE subject handler
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM subject WHERE id = $id");
    $message = "<p style='color:red;'>❌ Subject deleted successfully.</p>";
}

// UPDATE subject handler
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_subject'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $curriculum = $_POST['curriculum'];
    $category = $_POST['category'];

    if (!empty($name) && !empty($curriculum)) {
        $stmt = $conn->prepare("UPDATE subject SET name=?, curriculum=?, category=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $curriculum, $category, $id);
        $stmt->execute();
        $message = "<p style='color:green;'>✅ Subject updated successfully.</p>";
    } else {
        $message = "<p style='color:red;'>❌ Name and Curriculum are required.</p>";
    }
}

// ADD subject handler
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

// Fetch all subjects
$subjects = $conn->query("SELECT * FROM subject ORDER BY name");

// If editing, fetch specific subject data
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $edit_data = $conn->query("SELECT * FROM subject WHERE id = $edit_id")->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin: Manage Subjects</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* General page layout */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            background: #f4f7fa;
            min-height: 100vh;
        }

        /* Sidebar styling */
        .sidebar {
            width: 240px;
            background: #002b5c;
            color: white;
            padding-top: 20px;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            overflow-y: auto;
        }
        .sidebar h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 22px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            padding-bottom: 10px;
        }
        .sidebar a, .sidebar summary {
            display: block;
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            font-size: 15px;
            cursor: pointer;
        }
        .sidebar a:hover, .sidebar summary:hover {
            background: #014a99;
        }
        details a {
            padding-left: 35px;
            font-size: 14px;
        }

        /* Arrow indicators for collapsible menus */
        summary {
            position: relative;
        }
        summary::after {
            content: "▼"; /* Down arrow */
            position: absolute;
            right: 20px;
            transition: transform 0.2s;
        }
        details[open] summary::after {
            transform: rotate(180deg); /* Rotate arrow up when open */
        }

        /* Main content area */
        .main-content {
            margin-left: 240px;
            padding: 20px;
            flex: 1;
        }

        /* Top navigation bar */
        .topbar {
            background: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
            border-radius: 8px;
        }
        .topbar h1 {
            font-size: 20px;
            color: #003366;
        }
        .logout {
            background: #ff4d4d;
            padding: 8px 16px;
            color: white;
            border-radius: 4px;
            text-decoration: none;
        }
        .logout:hover {
            background: #cc0000;
        }

        /* Form and table containers */
        .form-container, .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        /* Form styling */
        form label {
            font-weight: bold;
            display: block;
            margin-top: 10px;
        }
        form input, form select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        form button {
            background: #004080;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        form button:hover {
            background: #0055aa;
        }

        /* Table styling */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            border-radius: 5px;
            overflow: hidden;
        }
        th, td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
        }
        th {
            background: #003366;
            color: white;
        }

        /* Message display */
        .message {
            margin-top: 10px;
            text-align: center;
            font-weight: bold;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; position: relative; }
            .main-content { margin-left: 0; width: 100%; }
        }
    </style>
</head>
<body>

<!-- Sidebar navigation menu -->
<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php">🏠 Dashboard</a>
    <details>
        <summary>🎓 Admissions</summary>
        <a href="admin_admissions.php">• Manage Admissions</a>
        <a href="admin_contact_student.php">• Student Messages</a>
    </details>
    <details>
        <summary>📚 Academics</summary>
        <a href="admin_manage_results.php">• Manage Results</a>
        <a href="admin_subjects.php">• Subjects</a>
        <a href="admin_upload_syllabus.php">• Upload Syllabus</a>
        <a href="admin_upload_teacher.php">• Teachers</a>
    </details>
    <details>
        <summary>🖼️ Media & News</summary>
        <a href="admin_upload_media.php">• Media Gallery</a>
        <a href="upload_news.php">• News & Events</a>
    </details>
    <details>
        <summary>👥 Interaction</summary>
        <a href="admin_contact.php">• Contact Messages</a>
        <a href="admin_faqs.php">• FAQs</a>
        <a href="admin_manage_testimonials.php">• Testimonials</a>
        <a href="subscribers.php">• Subscribers</a>
    </details>
    <a href="admin_manage_staffs.php">👩‍🏫 Manage Staff</a>
    <a href="admin_logout.php">🚪 Logout</a>
</div>

<!-- Main content section -->
<div class="main-content">
    <!-- Top bar with title and logout button -->
    <div class="topbar">
        <h1>📘 Manage Subjects</h1>
        <a href="admin_logout.php" class="logout">Logout</a>
    </div>

    <!-- Display success/error message -->
    <?php if (isset($message)) echo "<div class='message'>{$message}</div>"; ?>

    <!-- Form for adding or editing a subject -->
    <div class="form-container">
        <form method="POST">
            <input type="hidden" name="id" value="<?= $edit_data['id'] ?? '' ?>">
            <label>Subject Name:</label>
            <input type="text" name="name" value="<?= $edit_data['name'] ?? '' ?>" required>
            <label>Curriculum:</label>
            <select name="curriculum" required>
                <option value="">-- Select Curriculum --</option>
                <option value="8-4-4" <?= isset($edit_data) && $edit_data['curriculum'] == '8-4-4' ? 'selected' : '' ?>>8-4-4</option>
                <option value="CBC" <?= isset($edit_data) && $edit_data['curriculum'] == 'CBC' ? 'selected' : '' ?>>CBC</option>
            </select>
            <label>Category:</label>
            <select name="category">
                <option value="">-- Optional: Select Category --</option>
                <option value="science" <?= isset($edit_data) && $edit_data['category'] == 'science' ? 'selected' : '' ?>>Science</option>
                <option value="humanities" <?= isset($edit_data) && $edit_data['category'] == 'humanities' ? 'selected' : '' ?>>Humanities</option>
                <option value="language" <?= isset($edit_data) && $edit_data['category'] == 'language' ? 'selected' : '' ?>>Language</option>
                <option value="technology" <?= isset($edit_data) && $edit_data['category'] == 'technology' ? 'selected' : '' ?>>Technology</option>
                <option value="business" <?= isset($edit_data) && $edit_data['category'] == 'business' ? 'selected' : '' ?>>Business</option>
                <option value="personal_dev" <?= isset($edit_data) && $edit_data['category'] == 'personal_dev' ? 'selected' : '' ?>>Personal Development</option>
            </select>
            <button type="submit" name="<?= isset($edit_data) ? 'update_subject' : 'add_subject' ?>">
                <?= isset($edit_data) ? '✏️ Update Subject' : '➕ Add Subject' ?>
            </button>
        </form>
    </div>

    <!-- Table listing all subjects -->
    <div class="table-container">
        <?php if ($subjects && $subjects->num_rows > 0): ?>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Subject</th>
                    <th>Curriculum</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                <?php while ($row = $subjects->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['curriculum']) ?></td>
                        <td><?= htmlspecialchars($row['category']) ?></td>
                        <td><?= isset($row['is_active']) && $row['is_active'] ? '✅ Active' : '❌ Inactive' ?></td>
                        <td>
                            <a href="?edit=<?= $row['id'] ?>">✏️ Edit</a> |
                            <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')">🗑️ Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p style="text-align:center;">No subjects found.</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
