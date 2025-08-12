<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "school");
$message = "";

// Upload media
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['media_file'])) {
    $title = $_POST['title'];
    $category = $_POST['category'];
    $file = $_FILES['media_file'];
    $upload_dir = "uploads/media/";

    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    $filename = time() . "_" . basename($file["name"]);
    $target = $upload_dir . $filename;
    $filetype = strpos($file["type"], "video") !== false ? "video" : "image";

    if (move_uploaded_file($file["tmp_name"], $target)) {
        $stmt = $conn->prepare("INSERT INTO media_gallery (title, file_path, file_type, category) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $title, $target, $filetype, $category);
        $stmt->execute();
        $message = "<p style='color:green;'>✅ Media uploaded successfully!</p>";
    } else {
        $message = "<p style='color:red;'>❌ Upload failed.</p>";
    }
}

// Delete media
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $row = $conn->query("SELECT file_path FROM media_gallery WHERE id = $id")->fetch_assoc();
    if ($row && file_exists($row['file_path'])) unlink($row['file_path']);
    $conn->query("DELETE FROM media_gallery WHERE id = $id");
    $message = "<p style='color:red;'>🗑️ Media deleted successfully.</p>";
}

$media = $conn->query("SELECT * FROM media_gallery ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🖼️ Upload Media - Admin Panel</title>
	    <link rel="stylesheet" href="admin_style.css">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f4f8; display: flex; min-height: 100vh; }

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


        .main {
            margin-left: 220px;
            width: calc(100% - 220px);
            padding: 30px;
        }

        .topbar {
            background: white;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
        }

        .topbar h1 {
            font-size: 22px;
            color: #003366;
        }

        .logout {
            background: #cc0000;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
        }

        .logout:hover { background: #990000; }

        .message {
            margin-bottom: 10px;
            font-weight: bold;
        }

        .upload-form {
            background: white;
            padding: 20px;
            border-radius: 10px;
            max-width: 600px;
            box-shadow: 0 0 10px #ccc;
        }

        .upload-form input,
        .upload-form select {
            width: 100%;
            padding: 10px;
            margin-top: 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .upload-form button {
            padding: 12px;
            background: green;
            color: white;
            border: none;
            border-radius: 6px;
            margin-top: 15px;
            width: 100%;
            font-weight: bold;
            cursor: pointer;
        }

        .upload-form button:hover {
            background: darkgreen;
        }

        .gallery {
            margin-top: 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .item {
            background: white;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 0 5px #ccc;
            width: 240px;
        }

        .item video,
        .item img {
            max-width: 100%;
            height: 160px;
            object-fit: cover;
            border-radius: 5px;
        }

        .item p {
            font-weight: bold;
            margin: 8px 0 4px;
        }

        .item small {
            color: #555;
        }

        .item a {
            display: inline-block;
            margin-top: 5px;
            color: red;
            font-size: 14px;
            text-decoration: none;
        }

        .item a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .sidebar, .main { width: 100%; position: static; }
            .main { margin-left: 0; }
            .gallery { justify-content: center; }
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


<div class="main">
    <div class="topbar">
        <h1>🖼️ Upload Media</h1>
        <a href="admin_logout.php" class="logout">Logout</a>
    </div>

    <?= $message ?>

    <div class="upload-form">
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="title" placeholder="Media Title" required>
            <select name="category" required>
                <option value="">-- Select Category --</option>
                <option value="Events">Events</option>
                <option value="Sports">Sports</option>
                <option value="Academics">Academics</option>
            </select>
            <input type="file" name="media_file" accept="image/*,video/*" required>
            <button type="submit">Upload Media</button>
        </form>
    </div>

    <div class="gallery">
        <?php while ($row = $media->fetch_assoc()): ?>
            <div class="item">
                <p><?= htmlspecialchars($row['title']) ?></p>
                <?php if ($row['file_type'] === 'image'): ?>
                    <img src="<?= $row['file_path'] ?>" alt="Image">
                <?php else: ?>
                    <video src="<?= $row['file_path'] ?>" controls></video>
                <?php endif; ?>
                <small>Category: <?= htmlspecialchars($row['category']) ?></small><br>
                <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this media?')">Delete</a>
            </div>
        <?php endwhile; ?>
    </div>
</div>

</body>
</html>
