<?php
session_start();
if (!isset($_SESSION['teacher_logged_in'])) {
    header("Location: teacher_login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'];

// Get date from GET or default to today
$date = $_GET['date'] ?? date("Y-m-d");

// Get filters
$class = $_GET['class'] ?? '';
$stream = $_GET['stream'] ?? '';

// Build WHERE clause
$where = "WHERE 1=1";
if ($class != '') {
    $where .= " AND class = '{$conn->real_escape_string($class)}'";
}
if ($stream != '') {
    $where .= " AND stream = '{$conn->real_escape_string($stream)}'";
}

$students = $conn->query("SELECT * FROM students_admitted $where");

// Handle form submit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['attendance'])) {
    foreach ($_POST['attendance'] as $student_id => $status) {
        $student_id = intval($student_id);
        $safe_status = $conn->real_escape_string($status);

        $check = $conn->query("SELECT * FROM attendance WHERE student_id = $student_id AND date = '$date'");
        if ($check->num_rows == 0) {
            $conn->query("INSERT INTO attendance (student_id, teacher_id, date, status) 
                          VALUES ($student_id, $teacher_id, '$date', '$safe_status')");
        } else {
            $conn->query("UPDATE attendance SET status='$safe_status' WHERE student_id = $student_id AND date = '$date'");
        }
    }
    $message = "✅ Attendance saved successfully!";
}

$classOptions = $conn->query("SELECT DISTINCT class FROM students_admitted");
$streamOptions = $conn->query("SELECT DISTINCT stream FROM students_admitted");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Teacher Attendance</title>
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
body.collapsed .main {
  margin-left: 75px;
}
body.dark .main {
  background-color: #1f1f1f;
}

/* Header */
header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid #ddd;
}
body.dark header {
  border-color: #444;
}
.mobile-toggle {
  display: none;
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #2c3e50;
}
body.dark .mobile-toggle {
  color: #ddd;
}
.profile-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.profile-info img {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  box-shadow: 0 0 6px rgba(0,0,0,0.1);
}
.profile-info strong {
  font-size: 1.125rem;
  white-space: nowrap;
}
.clock {
  font-weight: 600;
  margin-left: 1rem;
  font-variant-numeric: tabular-nums;
  user-select: none;
}

.toggle-btn {
  background: none;
  border: none;
  font-size: 1.3rem;
  cursor: pointer;
  color: #2c3e50;
  transition: color 0.3s ease;
}
body.dark .toggle-btn {
  color: #ddd;
}
.toggle-btn:hover, .toggle-btn:focus {
  color: #3498db;
  outline: none;
}
        .content { margin-left: 240px; padding: 30px; }
        .main h2 { color: white; background: #2c3e50; align-items: center; padding: 40px; margin: 0 5px;}
        form { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px #ccc; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        th, td { padding: 10px; border: 1px solid #ddd; }
        th { background: #007bff; color: white; }
        select, input[type="date"], button { padding: 8px; }
        button { background: #007bff; color: white; border: none; border-radius: 5px; }
        button:hover { background: #0056b3; }
        .msg-success { color: green; margin-top: 10px; }
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
    <h2>Mark Attendance — <?= htmlspecialchars($date) ?></h2>

    <form method="GET">
        <label>Class:</label>
        <select name="class" onchange="this.form.submit()">
            <option value="">Select</option>
            <?php while ($row = $classOptions->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($row['class']) ?>" <?= ($class == $row['class']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['class']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Stream:</label>
        <select name="stream" onchange="this.form.submit()">
            <option value="">Select</option>
            <?php while ($row = $streamOptions->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($row['stream']) ?>" <?= ($stream == $row['stream']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['stream']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Date:</label>
        <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" onchange="this.form.submit()">
    </form>

    <?php if (!empty($message)) echo "<p class='msg-success'>$message</p>"; ?>

    <?php if ($class && $stream && $students->num_rows > 0): ?>
        <form method="POST">
            <table>
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Admission No</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $students->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><?= htmlspecialchars($row['admission_no']) ?></td>
                            <td>
                                <select name="attendance[<?= $row['id'] ?>]">
                                    <option value="Present">Present</option>
                                    <option value="Absent">Absent</option>
                                </select>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <button type="submit">Save Attendance</button>
        </form>
    <?php elseif ($class && $stream): ?>
        <p>No students found for selected class and stream.</p>
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
