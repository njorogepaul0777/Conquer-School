<?php
session_start();
$conn = new mysqli("localhost", "root", "", "school");

// Only allow admin to access
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Add Staff
if (isset($_POST['add_staff'])) {
    $name = $_POST['full_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role'];

    $stmt = $conn->prepare("INSERT INTO staff_users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $password, $role);

    if ($stmt->execute()) {
        $message = "Staff added successfully!";
    } else {
        $message = "Error: Email may already exist.";
    }
}

// Delete Staff
if (isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    $conn->query("DELETE FROM staff_users WHERE id = $id");
    $message = "Staff deleted successfully.";
}

// Edit Staff
if (isset($_POST['update_staff'])) {
    $id = $_POST['staff_id'];
    $name = $_POST['full_name'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    if (!empty($_POST['new_password'])) {
        $password = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE staff_users SET full_name=?, email=?, role=?, password=? WHERE id=?");
        $stmt->bind_param("ssssi", $name, $email, $role, $password, $id);
    } else {
        $stmt = $conn->prepare("UPDATE staff_users SET full_name=?, email=?, role=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $email, $role, $id);
    }

    if ($stmt->execute()) {
        $message = "Staff updated successfully.";
    } else {
        $message = "Error updating staff.";
    }
}

// Fetch all staff
$staffs = $conn->query("SELECT * FROM staff_users ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Staff - Admin</title>
    <link rel="stylesheet" href="admin_style.css">

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            display: flex;
            background: #f4f4f4;
        }

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
            padding: 30px;
            flex: 1;
        }
		button{
			background-color: #002b5c;
			color: white;
		}

        h2 { color: white; }
		h1 {color: white; background: #002b5c; text-align: center; padding: 20px; margin: 0;}
        .message { padding: 10px; margin-bottom: 15px; background: #e7f3e7; border-left: 5px solid #4CAF50; }
        table { width: 100%; border-collapse: collapse; background:#fff; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
        form { margin-bottom: 20px; background:#fff; padding: 15px; border-radius: 8px; }
        input, select, button{ padding: 8px; margin: 5px 0; width: 100%; box-sizing: border-box; }
        .actions form { display:inline; margin:0 2px; }
        .edit-form { background:#eef; padding:10px; margin:10px 0; }
        .edit-btn { background:#2196F3; color:white; border:none; padding:6px 10px; }
        .delete-btn { background:#f44336; color:white; border:none; padding:6px 10px; }
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
    <h1>Manage Staff</h1>

    <?php if (isset($message)): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Add Staff Form -->
    <form method="POST">
        <h3>Add New Staff</h3>
        <input type="text" name="full_name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <select name="role" required>
            <option value="">-- Select Role --</option>
            <option value="admin">Admin</option>
            <option value="accounts">Accounts</option>
            <option value="librarian">Librarian</option>
            <option value="nurse">Nurse</option>
            <option value="secretary">Secretary</option>
        </select>
        <button type="submit" name="add_staff">Add Staff</button>
    </form>

    <!-- Staff Table -->
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($staffs->num_rows > 0): $count = 1; ?>
                <?php while($s = $staffs->fetch_assoc()): ?>
                <tr>
                    <td><?= $count++ ?></td>
                    <td><?= htmlspecialchars($s['full_name']) ?></td>
                    <td><?= htmlspecialchars($s['email']) ?></td>
                    <td><?= htmlspecialchars($s['role']) ?></td>
                    <td><?= date("M d, Y", strtotime($s['created_at'])) ?></td>
                    <td class="actions">
                        <button onclick="document.getElementById('edit<?= $s['id'] ?>').style.display='table-row';" class="edit-btn">Edit</button>
                        <form method="POST" onsubmit="return confirm('Delete this staff?');">
                            <input type="hidden" name="delete_id" value="<?= $s['id'] ?>">
                            <button type="submit" class="delete-btn">Delete</button>
                        </form>
                    </td>
                </tr>

                <!-- Edit Form Row -->
                <tr id="edit<?= $s['id'] ?>" style="display:none;">
                    <td colspan="6">
                        <form method="POST" class="edit-form">
                            <input type="hidden" name="staff_id" value="<?= $s['id'] ?>">
                            <input type="text" name="full_name" value="<?= htmlspecialchars($s['full_name']) ?>" required>
                            <input type="email" name="email" value="<?= htmlspecialchars($s['email']) ?>" required>
                            <select name="role" required>
                                <option value="admin" <?= $s['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="accounts" <?= $s['role'] == 'accounts' ? 'selected' : '' ?>>Accounts</option>
                                <option value="librarian" <?= $s['role'] == 'librarian' ? 'selected' : '' ?>>Librarian</option>
                                <option value="nurse" <?= $s['role'] == 'nurse' ? 'selected' : '' ?>>Nurse</option>
                                <option value="secretary" <?= $s['role'] == 'secretary' ? 'selected' : '' ?>>Secretary</option>
                            </select>
                            <input type="password" name="new_password" placeholder="New Password (optional)">
                            <button type="submit" name="update_staff">Save Changes</button>
                            <button type="button" onclick="document.getElementById('edit<?= $s['id'] ?>').style.display='none';">Cancel</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
            <tr><td colspan="6" style="text-align:center;">No staff found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
