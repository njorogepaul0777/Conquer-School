<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Approve
if (isset($_GET['approve'])) {
    $id = intval($_GET['approve']);
    $conn->query("UPDATE testimonials SET approved = 1 WHERE id = $id");
}

// Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM testimonials WHERE id = $id");
}

// Fetch all
$result = $conn->query("SELECT * FROM testimonials ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Testimonials - Admin</title>
     <link rel="stylesheet" href="admin_style.css">

  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      background: #f9f9f9;
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
      margin-left: 240px;
      padding: 20px;
    }
    h1 {
      color: #003366;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      background: white;
    }
    table, th, td {
      border: 1px solid #ccc;
    }
    th, td {
      padding: 10px;
      text-align: left;
    }
    .btn {
      padding: 5px 10px;
      text-decoration: none;
      border-radius: 4px;
      margin-right: 5px;
    }
    .approve { background: green; color: white; }
    .delete { background: red; color: white; }
    .approved-label { color: green; font-weight: bold; }
    .pending-label { color: orange; font-weight: bold; }
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
  <h1>Manage Testimonials</h1>

  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Author</th>
        <th>Content</th>
        <th>Status</th>
        <th>Created At</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $result->fetch_assoc()): ?>
      <tr>
        <td><?= $row['id'] ?></td>
        <td><?= htmlspecialchars($row['author']) ?></td>
        <td><?= nl2br(htmlspecialchars($row['content'])) ?></td>
        <td>
          <?= $row['approved'] ? "<span class='approved-label'>Approved</span>" : "<span class='pending-label'>Pending</span>" ?>
        </td>
        <td><?= $row['created_at'] ?></td>
        <td>
          <?php if (!$row['approved']): ?>
            <a href="?approve=<?= $row['id'] ?>" class="btn approve">Approve</a>
          <?php endif; ?>
          <a href="?delete=<?= $row['id'] ?>" class="btn delete" onclick="return confirm('Delete this testimonial?')">Delete</a>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>

</body>
</html>
<?php $conn->close(); ?>
