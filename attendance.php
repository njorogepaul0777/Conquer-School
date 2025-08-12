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
        /* Reset and base */
*, *::before, *::after {
  box-sizing: border-box;
}
body {
  margin: 0;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: #f8f9fa;
  color: #2c3e50;
  line-height: 1.5;
  min-height: 100vh;
  display: flex;
  flex-direction: row;
  transition: background-color 0.3s ease, color 0.3s ease;
}
body.dark {
  background: #121212;
  color: #ddd;
}
a {
  color: inherit;
  text-decoration: none;
  transition: color 0.3s ease;
}
a:hover, a:focus {
  color: #2980b9;
  outline: none;
}

/* Sidebar */
.sidebar {
  width: 250px;
  background-color: #2c3e50; /* Dark blue-gray base */
  color: #ecf0f1; /* Off-white text */
  height: 100vh;
  position: fixed;
  top: 0; left: 0;
  overflow-y: auto;
  box-shadow: 2px 0 8px rgba(0, 0, 0, 0.25);
  display: flex;
  flex-direction: column;
  transition: width 0.3s ease, background-color 0.3s ease;
  z-index: 100;
}
body.collapsed .sidebar {
  width: 75px;
}

/* Sidebar Header */
.sidebar h2 {
  font-weight: 700;
  font-size: 1.5rem;
  padding: 1rem 1.5rem;
  background-color: #22313f; /* Slightly darker */
  display: flex;
  justify-content: space-between;
  align-items: center;
  user-select: none;
  color: #ecf0f1;
}

/* Collapse toggle button */
.collapse-toggle {
  background: none;
  border: none;
  color: #ecf0f1;
  font-size: 1.3rem;
  cursor: pointer;
  padding: 0;
  transition: color 0.3s ease;
}
.collapse-toggle:hover, .collapse-toggle:focus {
  color: #1abc9c; /* Turquoise highlight */
  outline: none;
}

/* Navigation Section */
.section {
  display: flex;
  flex-direction: column;
  margin-top: 1rem;
  user-select: none;
}

/* Links and dropdown toggles */
.section a,
.dropdown-toggle {
  padding: 0.85rem 1.5rem;
  color: #ecf0f1;
  display: flex;
  align-items: center;
  font-size: 1rem;
  cursor: pointer;
  border-left: 4px solid transparent;
  transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
  justify-content: space-between;
  background-color: transparent;
}

/* Icon spacing and color */
.section a i,
.dropdown-toggle i {
  margin-right: 1rem;
  min-width: 20px;
  text-align: center;
  color: #ecf0f1;
  transition: color 0.3s ease;
}

/* Hover and focus states for links and dropdown toggles */
.section a:hover,
.section a:focus,
.dropdown-toggle:hover,
.dropdown-toggle:focus {
  background-color: #1abc9c; /* Turquoise background on hover */
  border-left-color: #16a085; /* Darker turquoise border */
  color: #fff; /* White text on hover */
  outline: none;
}
.section a:hover i,
.dropdown-toggle:hover i,
.section a:focus i,
.dropdown-toggle:focus i {
  color: #fff;
}

/* Dropdown toggle arrow */
.dropdown-toggle .arrow {
  color: #ecf0f1; /* Light arrow */
  transition: transform 0.3s ease, color 0.3s ease;
}

/* Arrow color on hover and focus */
.dropdown-toggle:hover .arrow,
.dropdown-toggle:focus .arrow {
  color: #fff; /* White arrow on hover */
}

/* Dropdown open state */
.dropdown.open > .dropdown-toggle {
  background-color: #1abc9c; /* Keep turquoise background */
  border-left-color: #16a085;
  color: #fff;
}
.dropdown.open > .dropdown-toggle .arrow {
  color: #fff;
  transform: rotate(180deg);
}
/* Hide dropdown menus by default */
.dropdown-menu {
  display: none;
  flex-direction: column;
  background-color: #34495e;
  margin-left: 0.5rem;
  border-left: 4px solid #16a085;
  user-select: none;
  padding-left: 0.5rem;
  font-size: 0.95rem;
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.3s ease;
}

/* Show dropdown menu when .dropdown has .open */
.dropdown.open > .dropdown-menu {
  display: flex;
  max-height: 500px; /* or some large enough value */
}
  
/* Dropdown menu links styling */
.dropdown-menu a {
  padding: 0.5rem 1rem;
  color: #ecf0f1;
  border-left: 4px solid transparent;
  transition: background-color 0.3s ease, border-color 0.3s ease;
}

.dropdown-menu a:hover,
.dropdown-menu a:focus {
  background-color: #1abc9c;
  border-left-color: #16a085;
  color: #fff;
  outline: none;
}


/* Hide text when sidebar collapsed */
.sidebar.collapsed .section a span,
.sidebar.collapsed .dropdown-toggle span,
body.collapsed .sidebar h2 span {
  display: none;
}

/* Keep icons visible with same colors when collapsed */
body.collapsed .sidebar {
  background-color: #2c3e50;
}
body.collapsed .sidebar .section a i,
body.collapsed .sidebar .dropdown-toggle i {
  color: #ecf0f1;
}

/* Main content */
.main {
  margin-left: 250px;
  flex-grow: 1;
  padding: 2rem;
  min-height: 100vh;
  transition: margin-left 0.3s ease;
  background-color: #fff;
}

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
		/* Responsive */
@media (max-width: 768px) {
  body {
    flex-direction: column;
  }
  .sidebar {
    position: fixed;
    width: 250px;
    left: -260px;
    top: 0;
    transition: left 0.3s ease;
    height: 100vh;
    z-index: 200;
  }
  body.sidebar-open .sidebar {
    left: 0;
    box-shadow: 2px 0 10px rgba(0,0,0,0.3);
  }
  body.collapsed .sidebar {
    width: 250px;
  }
  .main {
    margin-left: 0 !important;
    padding: 1rem 1rem 3rem;
  }
  header {
    padding: 1rem;
    border-bottom: 1px solid #ddd;
  }
  .mobile-toggle {
    display: block;
  }
  .profile-info strong {
    font-size: 1rem;
  }
  .cards {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
  }
}
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
<div class="main">
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
 <!-- Scripts -->
  <script>
    // Dark Mode toggle + save preference
    function toggleDarkMode() {
      document.body.classList.toggle('dark');
      const isDark = document.body.classList.contains('dark');
      document.getElementById('darkIcon').className = isDark ? 'fas fa-sun' : 'fas fa-moon';
      localStorage.setItem('darkMode', isDark);
      this.setAttribute('aria-pressed', isDark);
    }

    // Sidebar collapse toggle
    function toggleCollapse() {
      document.body.classList.toggle('collapsed');
      const isCollapsed = document.body.classList.contains('collapsed');
      document.getElementById('collapseIcon').className = isCollapsed ? 'fas fa-angle-double-right' : 'fas fa-angle-double-left';
      localStorage.setItem('sidebarCollapsed', isCollapsed);
    }

    // Mobile sidebar toggle
    function toggleSidebar() {
      const expanded = document.body.classList.toggle('sidebar-open');
      document.querySelector('.mobile-toggle').setAttribute('aria-expanded', expanded);
    }

    // Close sidebar on outside click (mobile)
    document.addEventListener('click', function (e) {
      const sidebar = document.getElementById('sidebar');
      const toggle = document.querySelector('.mobile-toggle');
      if (window.innerWidth <= 768 && document.body.classList.contains('sidebar-open')) {
        if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
          document.body.classList.remove('sidebar-open');
          toggle.setAttribute('aria-expanded', false);
        }
      }
    });

    // Live clock update
    function updateClock() {
      document.getElementById("clock").textContent = new Date().toLocaleTimeString();
    }

    window.onload = function () {
      updateClock();
      setInterval(updateClock, 1000);
      if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark');
        document.getElementById('darkIcon').className = 'fas fa-sun';
      }
      if (localStorage.getItem('sidebarCollapsed') === 'true') {
        document.body.classList.add('collapsed');
        document.getElementById('collapseIcon').className = 'fas fa-angle-double-right';
      }
    };

    // Dropdown menu toggle
    document.querySelectorAll('.dropdown-toggle').forEach(btn => {
      btn.addEventListener('click', () => {
        const parent = btn.closest('.dropdown');
        const expanded = parent.classList.toggle('open');
        btn.setAttribute('aria-expanded', expanded);
      });
    });
  </script>
</html>
<?php $conn->close(); ?>
