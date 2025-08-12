<?php
session_start();

// Check login
if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['teacher_logged_in'])) {
    header("Location: login.php");
    exit();
}

// DB connection
$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Get filters
$class = $_GET['class'] ?? '';
$stream = $_GET['stream'] ?? '';
$date = $_GET['date'] ?? date("Y-m-d");
$edit_mode = isset($_GET['edit']) && $_GET['edit'] == 1;

// Build WHERE clause
$where = "WHERE 1=1";
if ($class != '') {
    $where .= " AND s.class = '{$conn->real_escape_string($class)}'";
}
if ($stream != '') {
    $where .= " AND s.stream = '{$conn->real_escape_string($stream)}'";
}
if ($date != '') {
    $where .= " AND a.date = '{$conn->real_escape_string($date)}'";
}

// Get options
$classOptions = $conn->query("SELECT DISTINCT class FROM students_admitted");
$streamOptions = $conn->query("SELECT DISTINCT stream FROM students_admitted");

// Handle update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_attendance'])) {
    foreach ($_POST['status'] as $student_id => $status) {
        $student_id = intval($student_id);
        $safe_status = $conn->real_escape_string($status);
        $conn->query("UPDATE attendance SET status='$safe_status' WHERE student_id=$student_id AND date='$date'");
    }
    $update_message = "✅ Attendance records updated successfully!";
}

// Query attendance
$query = "SELECT s.id as student_id, s.full_name, s.admission_no, s.class, s.stream, a.status
          FROM attendance a
          JOIN students_admitted s ON a.student_id = s.id
          $where
          ORDER BY s.full_name";
$result = $conn->query($query);

// Teacher name
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Attendance</title>
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

        form { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px #ccc; max-width: 900px; }
        select, input[type="date"] { padding: 8px; margin-right: 10px; }
        button, .edit-btn { padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 5px; text-decoration: none; }
        button:hover, .edit-btn:hover { background: #0056b3; }

        table { width: 100%; margin-top: 20px; border-collapse: collapse; background: white; }
        th, td { padding: 10px; border: 1px solid #ddd; }
        th { background: #007bff; color: white; }
        .status-present { color: green; font-weight: bold; }
        .status-absent { color: red; font-weight: bold; }

        .mark-btn {
            display: inline-block; margin-bottom: 15px; background: #28a745; color: white; padding: 8px 16px;
            text-decoration: none; border-radius: 5px;
        }
        .mark-btn:hover { background: #218838; }
        .msg-success { color: green; margin-top: 10px; font-weight: bold; }
        .summary { margin-top: 10px; font-weight: bold; }
        .summary span { margin-right: 15px; }
    </style>
</head>
<body>

 <!-- Sidebar -->
  <nav class="sidebar" id="sidebar" aria-label="Primary navigation">
    <h2>
      <span>Conquer HS</span>
      <button
        class="collapse-toggle"
        aria-expanded="true"
        aria-controls="sidebar"
        aria-label="Toggle sidebar"
        onclick="toggleCollapse()"
      >
        <i id="collapseIcon" class="fas fa-angle-double-left"></i>
      </button>
    </h2>

    <div class="section dropdown">
      <button class="dropdown-toggle" aria-expanded="false" aria-haspopup="true">
        <i class="fas fa-book"></i><span> Academics</span>
        <i class="fas fa-chevron-down arrow"></i>
      </button>
      <div class="dropdown-menu" role="menu" aria-label="Academics submenu">
        <a href="teacher_profile.php" role="menuitem"><i class="fas fa-user"></i><span> Profile</span></a>
        <a href="upload_results.php" role="menuitem"><i class="fas fa-file-upload"></i><span> Upload Results</span></a>
        <a href="teacher_resources.php" role="menuitem"><i class="fas fa-book-open"></i><span> Resources</span></a>
        <a href="attendance.php" role="menuitem"><i class="fas fa-user-check"></i><span> Attendance</span></a>
      </div>
    </div>

    <div class="section dropdown">
      <button class="dropdown-toggle" aria-expanded="false" aria-haspopup="true">
        <i class="fas fa-comments"></i><span> Communication</span>
        <i class="fas fa-chevron-down arrow"></i>
      </button>
      <div class="dropdown-menu" role="menu" aria-label="Communication submenu">
        <a href="teacher_messages.php" role="menuitem"><i class="fas fa-envelope"></i><span> Messages</span></a>
        <a href="view_announcements.php" role="menuitem"><i class="fas fa-bullhorn"></i><span> Announcements</span></a>
      </div>
    </div>

    <div class="section">
      <a href="logout.php" onclick="return confirm('Logout?')" role="link" tabindex="0"><i class="fas fa-sign-out-alt"></i><span> Logout</span></a>
    </div>
  </nav>
<div class="content">
    <?php if (isset($_SESSION['teacher_logged_in'])): ?>
        <a href="manage_attendance.php" class="mark-btn">➕ Mark Attendance</a>
    <?php endif; ?>

    <h2>View Attendance Records</h2>

    <?php if (!empty($update_message)): ?>
        <p class="msg-success"><?= $update_message ?></p>
    <?php endif; ?>

    <form method="GET">
        <label>Class:</label>
        <select name="class">
            <option value="">All</option>
            <?php while ($row = $classOptions->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($row['class']) ?>" <?= ($class == $row['class']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['class']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Stream:</label>
        <select name="stream">
            <option value="">All</option>
            <?php while ($row = $streamOptions->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($row['stream']) ?>" <?= ($stream == $row['stream']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['stream']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Date:</label>
        <input type="date" name="date" value="<?= htmlspecialchars($date) ?>">

        <button type="submit">View</button>

        <?php if (!$edit_mode): ?>
            <a href="?class=<?= urlencode($class) ?>&stream=<?= urlencode($stream) ?>&date=<?= urlencode($date) ?>&edit=1" class="edit-btn">Edit</a>
        <?php endif; ?>
    </form>

    <?php if ($result && $result->num_rows > 0): ?>
        <?php
            // Count summary
            $total_present = 0;
            $total_absent = 0;
            $rows = [];

            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
                if ($row['status'] === 'Present') $total_present++;
                elseif ($row['status'] === 'Absent') $total_absent++;
            }
        ?>

        <?php if (!$edit_mode): ?>
            <p class="summary">
                <span style="color: green;">✅ Present: <?= $total_present ?></span>
                <span style="color: red;">❌ Absent: <?= $total_absent ?></span>
                <span style="color: blue;">👥 Total: <?= ($total_present + $total_absent) ?></span>
            </p>
        <?php endif; ?>

        <?php if ($edit_mode): ?>
            <form method="POST">
                <table>
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Admission No</th>
                            <th>Class</th>
                            <th>Stream</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['full_name']) ?></td>
                                <td><?= htmlspecialchars($row['admission_no']) ?></td>
                                <td><?= htmlspecialchars($row['class']) ?></td>
                                <td><?= htmlspecialchars($row['stream']) ?></td>
                                <td>
                                    <select name="status[<?= $row['student_id'] ?>]">
                                        <option value="Present" <?= ($row['status'] == 'Present') ? 'selected' : '' ?>>Present</option>
                                        <option value="Absent" <?= ($row['status'] == 'Absent') ? 'selected' : '' ?>>Absent</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" name="update_attendance">Update Attendance</button>
            </form>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Admission No</th>
                        <th>Class</th>
                        <th>Stream</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><?= htmlspecialchars($row['admission_no']) ?></td>
                            <td><?= htmlspecialchars($row['class']) ?></td>
                            <td><?= htmlspecialchars($row['stream']) ?></td>
                            <td class="<?= $row['status'] === 'Present' ? 'status-present' : 'status-absent' ?>">
                                <?= htmlspecialchars($row['status']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php else: ?>
        <p style="margin-top: 20px;">No attendance records found for selected filters.</p>
    <?php endif; ?>
</div>

</body>
</html>
<?php $conn->close(); ?>
