<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$admin_name = $_SESSION['admin_name'] ?? 'Admin';

// Handle form submit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_message'])) {
    $teacher_id = intval($_POST['teacher_id']);
    $subject = $conn->real_escape_string($_POST['subject']);
    $message = $conn->real_escape_string($_POST['message']);

    $sql = "INSERT INTO messages (sender_type, sender_name, recipient_teacher_id, subject, message)
            VALUES ('admin', '$admin_name', $teacher_id, '$subject', '$message')";

    if ($conn->query($sql)) {
        $success_message = "✅ Message sent successfully!";
    } else {
        $error_message = "❌ Failed to send message: " . $conn->error;
    }
}

// Get teachers list
$teachers = $conn->query("SELECT id, full_name FROM teachers ORDER BY full_name");
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="admin_style.css">

    <title>Contact Teachers</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background: #f4f6f9; }
        .sidebar {
            height: 100vh; width: 220px; position: fixed; background-color: #002244; padding-top: 20px; color: white;
        }
        .sidebar h3 { text-align: center; margin-bottom: 30px; }
        .sidebar a {
            display: block; color: white; padding: 12px 20px; text-decoration: none; transition: background 0.3s;
        }
        .sidebar a:hover { background-color: #004080; }
        .content { margin-left: 240px; padding: 30px; }
        .content h2 { color: #002244; }
        form { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px #ccc; max-width: 600px; }
        select, input[type="text"], textarea, button {
            width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 5px;
        }
        button { background: #007bff; color: white; border: none; }
        button:hover { background: #0056b3; }
        .msg-success { color: green; font-weight: bold; margin-top: 10px; }
        .msg-error { color: red; font-weight: bold; margin-top: 10px; }
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


<div class="content">
    <h2>Contact Teachers</h2>

    <?php if (!empty($success_message)): ?>
        <p class="msg-success"><?= $success_message ?></p>
    <?php elseif (!empty($error_message)): ?>
        <p class="msg-error"><?= $error_message ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="teacher_id">Select Teacher:</label>
        <select name="teacher_id" id="teacher_id" required>
            <option value="">-- Select Teacher --</option>
            <?php while ($row = $teachers->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['full_name']) ?></option>
            <?php endwhile; ?>
        </select>

        <label for="subject">Subject:</label>
        <input type="text" name="subject" id="subject" required>

        <label for="message">Message:</label>
        <textarea name="message" id="message" rows="5" required></textarea>

        <button type="submit" name="send_message">Send Message</button>
    </form>
</div>

</body>
</html>
<?php $conn->close(); ?>
