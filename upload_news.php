<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "school");
$message = "";

// Insert news
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_news'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $visibility = $_POST['visibility'];

    $stmt = $conn->prepare("INSERT INTO news_events (title, content, visibility, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("sss", $title, $content, $visibility);
    $stmt->execute();
    $message = "✅ News/Event added successfully!";
}

// Delete news
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM news_events WHERE id = $id");
    $message = "🗑️ Deleted successfully!";
}

// Load for edit
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $result = $conn->query("SELECT * FROM news_events WHERE id = $id");
    $editData = $result->fetch_assoc();
}

// Update news
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_news'])) {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    $visibility = $_POST['visibility'];

    $stmt = $conn->prepare("UPDATE news_events SET title=?, content=?, visibility=? WHERE id=?");
    $stmt->bind_param("sssi", $title, $content, $visibility, $id);
    $stmt->execute();
    $message = "✏️ News/Event updated successfully!";
}

// Load all news
$news = $conn->query("SELECT * FROM news_events ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>📢 Upload News - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="admin_style.css">

	
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; display: flex; min-height: 100vh; }

        .sidebar {
            width: 240px;
            background: #002b5c;
            color: white;
            padding-top: 20px;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            overflow-y: auto;
            transition: 0.3s ease;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 22px;
            font-weight: bold;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            transition: background 0.2s;
            font-size: 15px;
        }

        .sidebar a:hover {
            background-color: #014a99;
        }


        .main-content {
            margin-left: 220px;
            width: calc(100% - 220px);
            padding: 30px;
        }

        .topbar {
            background: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
        }
        .topbar h1 { font-size: 22px; color: #003366; }
        .logout {
            background: #ff4d4d;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
        }
        .logout:hover { background: #cc0000; }

        form, table {
            background: white;
            padding: 20px;
            margin-top: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px #ccc;
        }
        input, textarea, select {
            width: 100%;
            padding: 8px;
            margin-top: 10px;
        }
        button {
            background: green;
            color: white;
            padding: 10px 15px;
            margin-top: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover { background: darkgreen; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background: #003366;
            color: white;
        }

        .message { color: green; font-weight: bold; margin-top: 10px; }

        .actions a {
            margin-right: 10px;
            color: blue;
            text-decoration: none;
        }
        .actions a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .sidebar, .main-content { width: 100%; position: static; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>

<!-- Sidebar Navigation -->
<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php" class="active">🏠 Dashboard</a>

    <!-- Admissions section -->
    <details>
        <summary>🎓 Admissions</summary>
        <a href="admin_admissions.php">• Manage Admissions</a>
        <a href="admin_contact_student.php">• Student Messages</a>
    </details>

    <!-- Academics section -->
    <details>
        <summary>📚 Academics</summary>
        <a href="admin_manage_results.php">• Manage Results</a>
        <a href="admin_subjects.php">• Subjects</a>
        <a href="admin_upload_syllabus.php">• Upload Syllabus</a>
        <a href="admin_upload_teacher.php">• Teachers</a>
    </details>

    <!-- Media section -->
    <details>
        <summary>🖼️ Media & News</summary>
        <a href="admin_upload_media.php">• Media Gallery</a>
        <a href="upload_news.php">• News & Events</a>
    </details>

    <!-- Communication section -->
    <details>
        <summary>👥 Interaction</summary>
        <a href="admin_contact.php">• Contact Messages</a>
        <a href="admin_faqs.php">• FAQs</a>
        <a href="admin_manage_testimonials.php">• Testimonials</a>
        <a href="subscribers.php">• Subscribers</a>
    </details>

    <!-- Staff management -->
    <a href="admin_manage_staffs.php">👩‍🏫 Manage Staff</a>
    <a href="admin_logout.php">🚪 Logout</a>
</div>




<div class="main-content">
    <div class="topbar">
        <h1>📰 Manage News & Events</h1>
        <a href="admin_logout.php" class="logout">Logout</a>
    </div>

    <?php if ($message): ?>
        <p class="message"><?= $message ?></p>
    <?php endif; ?>

    <?php if (isset($editData)): ?>
        <h3>✏️ Edit News/Event</h3>
        <form method="POST">
            <input type="hidden" name="id" value="<?= $editData['id'] ?>">
            <input type="text" name="title" value="<?= htmlspecialchars($editData['title']) ?>" required>
            <textarea name="content" rows="4" required><?= htmlspecialchars($editData['content']) ?></textarea>
            <select name="visibility" required>
                <option value="public" <?= $editData['visibility'] == 'public' ? 'selected' : '' ?>>Public</option>
                <option value="private" <?= $editData['visibility'] == 'private' ? 'selected' : '' ?>>Private</option>
            </select>
            <button type="submit" name="update_news">Update</button>
            <a href="upload_news.php">Cancel</a>
        </form>
    <?php else: ?>
        <h3>➕ Post New News/Event</h3>
        <form method="POST">
            <input type="text" name="title" placeholder="News Title" required>
            <textarea name="content" rows="4" placeholder="Event Details..." required></textarea>
            <select name="visibility" required>
                <option value="public">Public</option>
                <option value="private">Private</option>
            </select>
            <button type="submit" name="add_news">Post</button>
        </form>
    <?php endif; ?>

    <h3>📋 All News & Events</h3>
    <table>
        <tr>
            <th>Title</th>
            <th>Visibility</th>
            <th>Posted On</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $news->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['title']) ?></td>
            <td><?= $row['visibility'] ?></td>
            <td><?= $row['created_at'] ?></td>
            <td class="actions">
                <a href="?edit=<?= $row['id'] ?>">✏️ Edit</a>
                <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this news?')">🗑️ Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>
