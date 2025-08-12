<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "school";
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Fetch subjects
$subjectQuery = $conn->query("SELECT id, name FROM subject");

// Delete teacher
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    $stmt = $conn->prepare("SELECT profile_photo FROM teachers WHERE id = ?");
    $stmt->bind_param("i", $deleteId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!empty($result['profile_photo']) && file_exists($result['profile_photo'])) {
        unlink($result['profile_photo']);
    }

    $stmt = $conn->prepare("DELETE FROM teachers WHERE id = ?");
    $stmt->bind_param("i", $deleteId);
    $stmt->execute();
    $stmt->close();

    header("Location: admin_upload_teacher.php?msg=deleted");
    exit();
}

// Fetch teacher for editing
$editData = null;
if (isset($_GET['edit'])) {
    $editId = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM teachers WHERE id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Form submission
$success = '';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $gender = $_POST['gender'];
    $bio = trim($_POST['bio']);
    $subject_id = intval($_POST['subject_id']);
    $photoPath = '';

    // Handle file upload
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];
        $uploadDir = 'teacher_photos/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowedExt) && $_FILES['profile_photo']['size'] <= 2 * 1024 * 1024) {
            $uniqueName = 'teacher_' . uniqid() . '.' . $ext;
            $targetPath = $uploadDir . $uniqueName;
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $targetPath)) {
                $photoPath = $targetPath;
            }
        }
    }

    if (isset($_POST['update_id'])) {
        // Update teacher
        $id = intval($_POST['update_id']);
        if ($photoPath) {
            $stmt = $conn->prepare("UPDATE teachers SET full_name=?, email=?, phone=?, subject_id=?, gender=?, bio=?, profile_photo=? WHERE id=?");
            $stmt->bind_param("sssisisi", $name, $email, $phone, $subject_id, $gender, $bio, $photoPath, $id);
        } else {
            $stmt = $conn->prepare("UPDATE teachers SET full_name=?, email=?, phone=?, subject_id=?, gender=?, bio=? WHERE id=?");
            $stmt->bind_param("sssissi", $name, $email, $phone, $subject_id, $gender, $bio, $id);
        }
        $stmt->execute();
        $stmt->close();
        header("Location: admin_upload_teacher.php?msg=updated");
        exit();
    } else {
        // Insert teacher
        $stmt = $conn->prepare("INSERT INTO teachers (full_name, email, phone, subject_id, gender, bio, profile_photo) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssisss", $name, $email, $phone, $subject_id, $gender, $bio, $photoPath);
        $stmt->execute();
        $stmt->close();
        $success = "Teacher added.";
    }
}

// Fetch all teachers
$teacherList = $conn->query("SELECT t.*, s.name AS subject_name FROM teachers t LEFT JOIN subject s ON t.subject_id = s.id");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Teachers</title>
    <style>
        /* ====== General Page Styling ====== */
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #f9fafb;
            margin: 0;
            padding: 0;
            display: flex;
        }

        /* ====== Sidebar Styling ====== */
        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            height: 100vh;
            padding: 20px;
            position: fixed;
            overflow-y: auto;
        }
        .sidebar h2 {
            font-size: 18px;
            margin-bottom: 20px;
        }
        .sidebar a {
            display: block;
            padding: 8px;
            color: white;
            text-decoration: none;
            margin: 4px 0;
            border-radius: 4px;
        }
        .sidebar a:hover {
            background: #34495e;
        }

        /* Arrow animation like subjects page */
        details summary {
            cursor: pointer;
            padding: 8px;
            position: relative;
            list-style: none;
            border-radius: 4px;
        }
        details summary:hover {
            background: #34495e;
        }
        details summary::-webkit-details-marker {
            display: none;
        }
        details summary::after {
            content: "▼";
            position: absolute;
            right: 10px;
            font-size: 12px;
            transition: transform 0.2s ease;
        }
        details[open] summary::after {
            transform: rotate(180deg);
        }
        details a {
            padding-left: 20px;
            font-size: 14px;
        }

        /* ====== Main Content ====== */
        .main-content {
            margin-left: 270px;
            padding: 20px;
            flex: 1;
        }

        /* ====== Form Styling ====== */
        form {
            background: white;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        form label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
        }
        form input, form select, form textarea, form button {
            width: 100%;
            padding: 8px;
            margin-top: 4px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        form button {
            background: #007bff;
            color: white;
            border: none;
            margin-top: 10px;
            cursor: pointer;
        }
        form button:hover {
            background: #0056b3;
        }

        /* ====== Table Styling ====== */
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background: #007bff;
            color: white;
        }
        img {
            max-width: 60px;
            border-radius: 4px;
        }
        .success {
            color: green;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
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

<div class="main-content">
    <h1><?= $editData ? "Edit Teacher" : "Add New Teacher" ?></h1>

    <form method="POST" enctype="multipart/form-data">
        <?php if ($editData): ?>
            <input type="hidden" name="update_id" value="<?= $editData['id'] ?>">
        <?php endif; ?>

        <label>Full Name</label>
        <input type="text" name="full_name" value="<?= htmlspecialchars($editData['full_name'] ?? '') ?>" required>

        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($editData['email'] ?? '') ?>">

        <label>Phone</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($editData['phone'] ?? '') ?>">

        <label>Subject</label>
        <select name="subject_id" required>
            <option value="">-- Select Subject --</option>
            <?php
            $subjectQuery->data_seek(0);
            while ($row = $subjectQuery->fetch_assoc()):
            ?>
                <option value="<?= $row['id'] ?>" <?= ($editData && $editData['subject_id'] == $row['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Gender</label>
        <select name="gender" required>
            <option value="">-- Select Gender --</option>
            <option <?= ($editData && $editData['gender'] == 'Male') ? 'selected' : '' ?>>Male</option>
            <option <?= ($editData && $editData['gender'] == 'Female') ? 'selected' : '' ?>>Female</option>
            <option <?= ($editData && $editData['gender'] == 'Other') ? 'selected' : '' ?>>Other</option>
        </select>

        <label>Short Bio</label>
        <textarea name="bio" rows="4" required><?= htmlspecialchars($editData['bio'] ?? '') ?></textarea>

        <label>Profile Photo</label>
        <input type="file" name="profile_photo" accept="image/*">
        <?php if (!empty($editData['profile_photo']) && file_exists($editData['profile_photo'])): ?>
            <br><img src="<?= htmlspecialchars($editData['profile_photo']) ?>" width="80" style="margin-top:10px;">
        <?php endif; ?>

        <button type="submit"><?= $editData ? "Update Teacher" : "Add Teacher" ?></button>

        <?php if ($success): ?>
            <p class="success"><?= $success ?></p>
        <?php endif; ?>
    </form>

    <h2>All Teachers</h2>
    <?php if (isset($_GET['msg'])): ?>
        <p class="success"><?= $_GET['msg'] == 'deleted' ? 'Teacher deleted.' : 'Teacher updated.' ?></p>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Photo</th>
                <th>Name</th>
                <th>Subject</th>
                <th>Contact</th>
                <th>Gender</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($teacher = $teacherList->fetch_assoc()): ?>
            <tr>
                <td>
                    <?php if ($teacher['profile_photo'] && file_exists($teacher['profile_photo'])): ?>
                        <img src="<?= htmlspecialchars($teacher['profile_photo']) ?>" alt="Photo">
                    <?php else: ?>
                        <em>No Photo</em>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($teacher['full_name']) ?></td>
                <td><?= htmlspecialchars($teacher['subject_name']) ?></td>
                <td><?= htmlspecialchars($teacher['email']) ?><br><?= htmlspecialchars($teacher['phone']) ?></td>
                <td><?= htmlspecialchars($teacher['gender']) ?></td>
                <td>
                    <a href="?edit=<?= $teacher['id'] ?>">✏️ Edit</a> |
                    <a href="?delete=<?= $teacher['id'] ?>" onclick="return confirm('Delete this teacher?')">🗑️ Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
<?php $conn->close(); ?>
