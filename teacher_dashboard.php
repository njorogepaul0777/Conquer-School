<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    header("Location: teacher_login.php");
    exit();
}
$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$teacher_id = $_SESSION['teacher_id'];
$sql = "SELECT full_name, profile_photo FROM teachers WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 1) {
    $teacher = $result->fetch_assoc();
    $name = htmlspecialchars($teacher['full_name']);
    $photo = htmlspecialchars($teacher['profile_photo']);
} else {
    $name = "Teacher";
    $photo = "default.png";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Teacher Dashboard - Conquer High School</title>
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
  />
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

/* Dashboard Cards */
.cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 1.5rem;
  margin-top: 2rem;
}
.card {
  background-color: #fefefe;
  border-radius: 12px;
  padding: 1.5rem 1rem;
  box-shadow: 0 8px 20px rgb(0 0 0 / 0.05);
  text-align: center;
  cursor: pointer;
  transition: box-shadow 0.3s ease, transform 0.15s ease;
  user-select: none;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.7rem;
  color: #34495e;
  font-weight: 600;
  font-size: 1rem;
}
.card i {
  font-size: 2.3rem;
  color: #3498db;
  transition: color 0.3s ease;
}
.card:hover {
  box-shadow: 0 12px 28px rgb(0 0 0 / 0.15);
  transform: translateY(-5px);
}
.card:hover i {
  color: #2980b9;
}
.card a {
  flex-grow: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  width: 100%;
  color: inherit;
  font-weight: 600;
}
.card a:focus {
  outline: 2px solid #3498db;
  outline-offset: 2px;
}

/* Footer */
footer {
  text-align: center;
  padding: 1rem 0;
  margin-top: 3rem;
  font-size: 0.9rem;
  color: #7f8c8d;
  user-select: none;
  border-top: 1px solid #ddd;
}
body.dark footer {
  color: #aaa;
  border-color: #444;
}
footer a {
  color: #2980b9;
  text-decoration: none;
}
footer a:hover, footer a:focus {
  text-decoration: underline;
  outline: none;
}

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

  <!-- Main Content -->
  <main class="main" role="main" tabindex="-1">
    <header>
      <button
        class="mobile-toggle"
        aria-label="Toggle sidebar menu"
        aria-expanded="false"
        aria-controls="sidebar"
        onclick="toggleSidebar()"
      >
        <i class="fas fa-bars"></i>
      </button>

      <div class="profile-info">
        <img src="teacher_photos/<?= $photo ?>" alt="Profile photo of <?= $name ?>" loading="lazy" />
        <strong>Welcome, <?= $name ?></strong>
        <div class="clock" id="clock" aria-live="polite" aria-atomic="true" aria-label="Current time"></div>
      </div>

      <button class="toggle-btn" aria-pressed="false" aria-label="Toggle dark mode" onclick="toggleDarkMode()">
        <i class="fas fa-moon" id="darkIcon"></i>
      </button>
    </header>

    <!-- Dashboard Cards -->
    <section class="cards" aria-label="Teacher dashboard quick links">
      <div class="card" role="button" tabindex="0" onclick="location.href='teacher_profile.php'">
        <i class="fas fa-user"></i>
        <a href="teacher_profile.php" tabindex="-1" aria-hidden="true">My Profile</a>
      </div>
      <div class="card" role="button" tabindex="0" onclick="location.href='upload_results.php'">
        <i class="fas fa-upload"></i>
        <a href="upload_results.php" tabindex="-1" aria-hidden="true">Upload Results</a>
      </div>
      <div class="card" role="button" tabindex="0" onclick="location.href='view_results.php'">
        <i class="fas fa-eye"></i>
        <a href="view_results.php" tabindex="-1" aria-hidden="true">View Results</a>
      </div>
      <div class="card" role="button" tabindex="0" onclick="location.href='teacher_messages.php'">
        <i class="fas fa-envelope"></i>
        <a href="teacher_messages.php" tabindex="-1" aria-hidden="true">Messages</a>
      </div>
      <div class="card" role="button" tabindex="0" onclick="location.href='view_announcements.php'">
        <i class="fas fa-bullhorn"></i>
        <a href="view_announcements.php" tabindex="-1" aria-hidden="true">Announcements</a>
      </div>
      <div class="card" role="button" tabindex="0" onclick="location.href='teacher_resources.php'">
        <i class="fas fa-book-open"></i>
        <a href="teacher_resources.php" tabindex="-1" aria-hidden="true">Resources</a>
      </div>
      <div class="card" role="button" tabindex="0" onclick="location.href='attendance.php'">
        <i class="fas fa-user-check"></i>
        <a href="attendance.php" tabindex="-1" aria-hidden="true">Attendance</a>
      </div>
      <div class="card" role="button" tabindex="0" onclick="location.href='logout.php'">
        <i class="fas fa-sign-out-alt"></i>
        <a href="logout.php" tabindex="-1" aria-hidden="true" onclick="return confirm('Logout?')">Logout</a>
      </div>
    </section>

    <!-- Footer -->
    <footer>
      &copy; <?= date("Y") ?> Conquer High School | <a href="#">Privacy</a> | <a href="#">Help</a>
    </footer>
  </main>

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
</body>
</html>
