<?php
session_start();

if (!isset($_SESSION['teacher_name'])) {
    $_SESSION['teacher_name'] = 'Mr. David'; // Test fallback
}
$teacher = $_SESSION['teacher_name'];

$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle reply update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_text'], $_POST['message_id'])) {
    $msgId = (int) $_POST['message_id'];
    $replyText = $conn->real_escape_string(trim($_POST['reply_text']));
    if (!empty($replyText)) {
        if ($conn->query("UPDATE teacher_messages SET reply = '$replyText', reply_time = NOW() WHERE id = $msgId AND teacher_name = '$teacher'")) {
            $_SESSION['success_message'] = "Reply saved successfully.";
            header("Location: " . strtok($_SERVER["REQUEST_URI"], '?') . '?' . http_build_query(array_diff_key($_GET, ['edit' => ''])));
            exit;
        }
    }
}

// Stats
$total = $conn->query("SELECT COUNT(*) as total FROM teacher_messages WHERE teacher_name = '$teacher'")->fetch_assoc()['total'];
$withFiles = $conn->query("SELECT COUNT(*) as total FROM teacher_messages WHERE teacher_name = '$teacher' AND file_path IS NOT NULL AND file_path != ''")->fetch_assoc()['total'];
$noReply = $conn->query("SELECT COUNT(*) as total FROM teacher_messages WHERE teacher_name = '$teacher' AND (reply IS NULL OR reply = '')")->fetch_assoc()['total'];

// Filters
$fileFilter = $_GET['file_filter'] ?? 'all';
$dateFilter = $_GET['date_filter'] ?? 'all';
$searchAdm = $_GET['search_adm'] ?? '';

$sql = "
    SELECT m.*, s.full_name 
    FROM teacher_messages m 
    LEFT JOIN students_admitted s ON m.admission_no = s.admission_no 
    WHERE m.teacher_name = '" . $conn->real_escape_string($teacher) . "' 
";

if ($fileFilter === 'with_files') {
    $sql .= " AND m.file_path IS NOT NULL AND m.file_path != '' ";
} elseif ($fileFilter === 'no_files') {
    $sql .= " AND (m.file_path IS NULL OR m.file_path = '') ";
}

if ($dateFilter === 'today') {
    $sql .= " AND DATE(m.sent_at) = CURDATE() ";
} elseif ($dateFilter === 'this_week') {
    $sql .= " AND YEARWEEK(m.sent_at, 1) = YEARWEEK(CURDATE(), 1) ";
} elseif ($dateFilter === 'this_month') {
    $sql .= " AND MONTH(m.sent_at) = MONTH(CURDATE()) AND YEAR(m.sent_at) = YEAR(CURDATE()) ";
}

if (!empty($searchAdm)) {
    $sql .= " AND m.admission_no LIKE '%" . $conn->real_escape_string($searchAdm) . "%' ";
}

$sql .= " ORDER BY m.sent_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard - Messages</title>
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

        .filters {
            margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;
        }
        .filters select, .filters input[type="text"] {
            padding: 8px; border: 1px solid #ccc; border-radius: 5px;
        }

        .message-card {
            background: white; padding: 15px; margin-bottom: 15px;
            border-radius: 8px; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }

        .message-card h3 { margin: 0; color: #34495e; }
        .message-card small { color: #999; }
        .message-card p { margin: 10px 0; }

        .reply-section textarea {
            width: 100%; min-height: 80px; margin-top: 10px;
            padding: 8px; border-radius: 5px; border: 1px solid #ccc;
        }

        .submit-btn {
            background: #3498db; color: white; padding: 8px 14px;
            border: none; border-radius: 5px; margin-top: 10px; cursor: pointer;
            display: inline-block; text-decoration: none;
        }
        .submit-btn:hover { background: #2980b9; }

        .cancel-btn { background-color: #95a5a6 !important; }
        .edit-btn { background: #ffc107 !important; color: black; }

        .file-link {
            display: inline-block; margin-top: 5px; color: #27ae60; font-weight: bold;
        }
        .file-link:hover { text-decoration: underline; }

        #clock {
            font-weight: bold;
            font-size: 0.9em;
        }

        .alert-success {
            background: #d4edda; color: #155724;
            border: 1px solid #c3e6cb; border-radius: 5px;
            padding: 10px 15px; margin-bottom: 20px;
        }
		h1 {
			color: black;
		}
    </style>
    <script>
        function updateClock() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString();
            document.getElementById("clock").textContent = timeStr;
        }
        setInterval(updateClock, 1000);
        window.onload = updateClock;
    </script>
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
    <h1>Student Messages</h1>

    <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="alert-success"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>

    <div style="margin: 20px 0; background: #ecf0f1; padding: 15px 20px; border-radius: 8px; display: flex; justify-content: space-between; flex-wrap: wrap; color: black;">
        <div><strong>👋 Welcome, <?= htmlspecialchars($teacher) ?>!</strong></div>
        <div>🕒 Time: <span id="clock"></span></div>
        <div>Total: <strong><?= $total ?></strong></div>
        <div>📎 Files: <strong><?= $withFiles ?></strong></div>
        <div>🕘 No Reply: <strong><?= $noReply ?></strong></div>
    </div>

    <form class="filters" method="GET">
        <select name="file_filter">
            <option value="all" <?= $fileFilter === 'all' ? 'selected' : '' ?>>All</option>
            <option value="with_files" <?= $fileFilter === 'with_files' ? 'selected' : '' ?>>With Files</option>
            <option value="no_files" <?= $fileFilter === 'no_files' ? 'selected' : '' ?>>No Files</option>
        </select>

        <select name="date_filter">
            <option value="all" <?= $dateFilter === 'all' ? 'selected' : '' ?>>All Time</option>
            <option value="today" <?= $dateFilter === 'today' ? 'selected' : '' ?>>Today</option>
            <option value="this_week" <?= $dateFilter === 'this_week' ? 'selected' : '' ?>>This Week</option>
            <option value="this_month" <?= $dateFilter === 'this_month' ? 'selected' : '' ?>>This Month</option>
        </select>

        <input type="text" name="search_adm" placeholder="Admission No" value="<?= htmlspecialchars($searchAdm) ?>">
        <button type="submit" class="submit-btn">Filter</button>
    </form>

    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="message-card">
                <h3><?= htmlspecialchars($row['full_name']) ?> (<?= htmlspecialchars($row['admission_no']) ?>)</h3>
                <small>Sent: <?= htmlspecialchars($row['sent_at']) ?></small>
                <p><?= nl2br(htmlspecialchars($row['message'])) ?></p>

                <?php if (!empty($row['file_path']) && file_exists($row['file_path'])): ?>
                    <a class="file-link" href="<?= htmlspecialchars($row['file_path']) ?>" target="_blank">📎 View Attachment</a>
                <?php elseif (!empty($row['file_path'])): ?>
                    <div class="file-link" style="color:red;">❌ Attachment not found</div>
                <?php endif; ?>

                <div class="reply-section">
                    <?php if (isset($_GET['edit']) && $_GET['edit'] == $row['id']): ?>
                        <form method="POST">
                            <textarea name="reply_text" required autofocus><?= htmlspecialchars($row['reply']) ?></textarea>
                            <input type="hidden" name="message_id" value="<?= $row['id'] ?>">
                            <button type="submit" class="submit-btn">💬 Save Reply</button>
                            <a href="teacher_messages.php?<?= http_build_query($_GET) ?>" class="submit-btn cancel-btn">Cancel</a>
                        </form>
                    <?php else: ?>
                        <p><strong>Reply:</strong> <?= $row['reply'] ? nl2br(htmlspecialchars($row['reply'])) : '<em>No reply yet</em>' ?></p>
                        <?php if (!empty($row['reply_time'])): ?>
                            <small>🕒 <?= date("d M Y, h:i A", strtotime($row['reply_time'])) ?></small>
                        <?php endif; ?>
                        <br>
                        <a href="?<?= http_build_query(array_merge($_GET, ['edit' => $row['id']])) ?>" class="submit-btn edit-btn">✏️ Edit</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No messages found.</p>
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
