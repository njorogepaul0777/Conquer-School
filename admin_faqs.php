<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Handle Add or Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question = $conn->real_escape_string($_POST['question']);
    $answer = $conn->real_escape_string($_POST['answer']);

    if (isset($_POST['faq_id']) && !empty($_POST['faq_id'])) {
        $faq_id = intval($_POST['faq_id']);
        $conn->query("UPDATE faqs SET question='$question', answer='$answer' WHERE id=$faq_id");
    } else {
        $conn->query("INSERT INTO faqs (question, answer) VALUES ('$question', '$answer')");
    }

    header("Location: admin_faqs.php");
    exit();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM faqs WHERE id=$id");
    header("Location: admin_faqs.php");
    exit();
}

// Handle Edit Load
$edit_faq = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $result = $conn->query("SELECT * FROM faqs WHERE id=$id");
    if ($result->num_rows > 0) {
        $edit_faq = $result->fetch_assoc();
    }
}

// Fetch All
$faqs = $conn->query("SELECT * FROM faqs ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage FAQs - Admin Panel</title>
    <link rel="stylesheet" href="admin_style.css">

    <style>
        body { font-family: Arial; margin: 0; background: #f4f4f4; }
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
            margin-left: 240px;
            padding: 20px;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        th, td { border: 1px solid #ccc; padding: 10px; }
        .form-box { border: 1px solid #ccc; padding: 20px; background: #f9f9f9; }
        .btn { padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; }
        .edit-btn { background: #007bff; color: white; }
        .delete-btn { background: #dc3545; color: white; }
        .save-btn { background: #28a745; color: white; margin-top: 10px; }
        h1 { color: #003366; }
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
    <h1>Manage FAQs</h1>

    <div class="form-box">
        <h3><?= $edit_faq ? 'Edit FAQ' : 'Add New FAQ' ?></h3>
        <form method="POST">
            <input type="hidden" name="faq_id" value="<?= $edit_faq['id'] ?? '' ?>">
            <label>Question:</label><br>
            <textarea name="question" rows="3" cols="80" required><?= htmlspecialchars($edit_faq['question'] ?? '') ?></textarea><br><br>

            <label>Answer:</label><br>
            <textarea name="answer" rows="5" cols="80" required><?= htmlspecialchars($edit_faq['answer'] ?? '') ?></textarea><br><br>

            <button class="btn save-btn" type="submit"><?= $edit_faq ? 'Update FAQ' : 'Add FAQ' ?></button>
            <?php if ($edit_faq): ?>
                <a href="admin_faqs.php" class="btn">Cancel</a>
            <?php endif; ?>
        </form>
    </div>

    <h3>All FAQs</h3>
    <table>
        <tr>
            <th>#</th>
            <th>Question</th>
            <th>Answer</th>
            <th>Actions</th>
        </tr>
        <?php while ($row = $faqs->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['question']) ?></td>
                <td><?= htmlspecialchars($row['answer']) ?></td>
                <td>
                    <a href="?edit=<?= $row['id'] ?>" class="btn edit-btn">Edit</a>
                    <a href="?delete=<?= $row['id'] ?>" class="btn delete-btn" onclick="return confirm('Delete this FAQ?')">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>
<?php $conn->close(); ?>
