<?php
session_start();
if (!isset($_SESSION['teacher_logged_in'])) {
    header("Location: teacher_login.php");
    exit();
}

$teacher_name = $_SESSION['teacher_name'];

$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function getGrade($subject, $total) {
    $sciences = ['Math', 'Physics', 'Chemistry', 'Biology'];
    if (in_array($subject, $sciences)) {
        if ($total >= 75) return 'A';
        elseif ($total >= 70) return 'A-';
        elseif ($total >= 65) return 'B+';
        elseif ($total >= 60) return 'B';
        elseif ($total >= 55) return 'B-';
        elseif ($total >= 50) return 'C+';
        elseif ($total >= 45) return 'C';
        elseif ($total >= 40) return 'C-';
        elseif ($total >= 35) return 'D+';
        elseif ($total >= 30) return 'D';
        elseif ($total >= 25) return 'D-';
        else return 'E';
    } else {
        if ($total >= 90) return 'A';
        elseif ($total >= 80) return 'A-';
        elseif ($total >= 70) return 'B+';
        elseif ($total >= 65) return 'B';
        elseif ($total >= 60) return 'B-';
        elseif ($total >= 55) return 'C+';
        elseif ($total >= 50) return 'C';
        elseif ($total >= 45) return 'C-';
        elseif ($total >= 40) return 'D+';
        elseif ($total >= 35) return 'D';
        elseif ($total >= 30) return 'D-';
        else return 'E';
    }
}

function calculateTotal($cat, $exam) {
    $cat_scaled = ($cat !== null && $cat !== '') ? ($cat / 50) * 30 : 0;
    $exam_scaled = ($exam !== null && $exam !== '') ? ($exam / 100) * 70 : 0;
    return $cat_scaled + $exam_scaled;
}

$subjects = [];
$result = $conn->query("SELECT name FROM subject WHERE curriculum = '8-4-4' ORDER BY name ASC");
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row['name'];
}

$students = [];
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['load'])) {
    $class = $_POST['class'];
    $stream = $_POST['stream'];
    $term = $_POST['term'];
    $year = $_POST['year'];

    $stmt = $conn->prepare("SELECT admission_no, full_name FROM students_admitted WHERE class = ? AND stream = ? ORDER BY LENGTH(admission_no), admission_no");
    $stmt->bind_param("ss", $class, $stream);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_results'])) {
    $term = $_POST['term'];
    $year = $_POST['year'];
    $class = $_POST['class'];
    $stream = $_POST['stream'];
    $uploaded_by = 'teacher';
    $marks = $_POST['marks'];

    foreach ($marks as $adm => $subjects_data) {
        foreach ($subjects_data as $sub => $scores) {
            $cat = isset($scores['cat']) && $scores['cat'] !== '' ? floatval($scores['cat']) : null;
            $exam = isset($scores['exam']) && $scores['exam'] !== '' ? floatval($scores['exam']) : null;

            if ($cat !== null || $exam !== null) {
                $total = calculateTotal($cat, $exam);
                $grade = getGrade($sub, $total);

                $check = $conn->prepare("SELECT id FROM results WHERE admission_no = ? AND subject = ? AND term = ? AND year = ?");
                $check->bind_param("sssi", $adm, $sub, $term, $year);
                $check->execute();
                $res = $check->get_result();

                if ($res->num_rows > 0) {
                    $update = $conn->prepare("UPDATE results SET cat_marks = ?, exam_marks = ?, total_marks = ?, grade = ?, uploaded_by = ? WHERE admission_no = ? AND subject = ? AND term = ? AND year = ?");
                    $update->bind_param("dddsssssi", $cat, $exam, $total, $grade, $uploaded_by, $adm, $sub, $term, $year);
                    $update->execute();
                } else {
                    $insert = $conn->prepare("INSERT INTO results (admission_no, subject, cat_marks, exam_marks, total_marks, grade, term, year, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $insert->bind_param("ssdddssis", $adm, $sub, $cat, $exam, $total, $grade, $term, $year, $uploaded_by);
                    $insert->execute();
                }
            }
        }
    }

    $message = "<p style='color:green;'>✅ Results uploaded/updated successfully!</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher - Upload 8-4-4 Results</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        form, .results-table {
            background: white;
            padding: 20px;
            margin-top: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px #ccc;
            overflow-x: auto;
        }

        label {
            font-weight: bold;
            margin-right: 10px;
        }

        input, select, button {
            padding: 7px;
            margin: 8px 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        button {
            background: green;
            color: white;
            border: none;
            cursor: pointer;
			width: 100%;
			padding: 10px;
			margin: 20px;
        }

        button:hover { background: darkgreen; }

        table {
            width: max-content;
            min-width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 6px 10px;
            text-align: center;
        }

        th {
            background: #003366;
            color: white;
        }

        @media (max-width: 768px) {
            .sidebar, .main-content {
                width: 100%;
                position: static;
            }
            .main-content {
                margin-left: 0;
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
<!-- Main Content -->
<div class="main">
    <div class="topbar">
        <h1>📊 Upload Results - 8-4-4 Curriculum</h1>
        <a href="teacher_logout.php" class="logout">Logout</a>
    </div>

    <?= $message ?>

    <!-- Load student form -->
    <form method="POST">
        <label>Class:</label>
        <input type="text" name="class" required>
        <label>Stream:</label>
        <input type="text" name="stream" required>
        <label>Term:</label>
        <select name="term" required>
            <option value="">-- Term --</option>
            <option>Term 1</option>
            <option>Term 2</option>
            <option>Term 3</option>
        </select>
        <label>Year:</label>
        <input type="number" name="year" value="<?= date('Y') ?>" required>
        <button type="submit" name="load">Load Students</button>
    </form>

    <!-- Results Table -->
    <?php if (!empty($students)): ?>
    <form method="POST" class="results-table">
        <input type="hidden" name="term" value="<?= htmlspecialchars($term) ?>">
        <input type="hidden" name="year" value="<?= htmlspecialchars($year) ?>">
        <input type="hidden" name="class" value="<?= htmlspecialchars($class) ?>">
        <input type="hidden" name="stream" value="<?= htmlspecialchars($stream) ?>">

        <table>
            <tr>
                <th>Full Name</th>
                <th>Adm No</th>
                <?php foreach ($subjects as $sub): ?>
                    <th colspan="2"><?= htmlspecialchars($sub) ?><br><small>CAT / Main</small></th>
                <?php endforeach; ?>
            </tr>
            <?php foreach ($students as $stu): ?>
            <tr>
                <td><?= htmlspecialchars($stu['full_name']) ?></td>
                <td><?= htmlspecialchars($stu['admission_no']) ?></td>
                <?php foreach ($subjects as $sub):
                    $preload = $conn->prepare("SELECT cat_marks, exam_marks FROM results WHERE admission_no = ? AND subject = ? AND term = ? AND year = ?");
                    $preload->bind_param("sssi", $stu['admission_no'], $sub, $term, $year);
                    $preload->execute();
                    $res = $preload->get_result();
                    $cat = $exam = '';
                    if ($res->num_rows > 0) {
                        $existing = $res->fetch_assoc();
                        $cat = ($existing['cat_marks'] !== null) ? intval($existing['cat_marks']) : '';
                        $exam = ($existing['exam_marks'] !== null) ? intval($existing['exam_marks']) : '';
                    }
                ?>
                    <td><input type="number" name="marks[<?= $stu['admission_no'] ?>][<?= $sub ?>][cat]" value="<?= $cat ?>" min="0" max="50"></td>
                    <td><input type="number" name="marks[<?= $stu['admission_no'] ?>][<?= $sub ?>][exam]" value="<?= $exam ?>" min="0" max="100"></td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </table>

        <br>
        <button type="submit" name="submit_results">✅ Submit All Results</button>
    </form>
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
