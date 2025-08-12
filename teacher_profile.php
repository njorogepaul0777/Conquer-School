<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    header("Location: teacher_login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$teacher_id = intval($_SESSION['teacher_id']);
$result = $conn->query("SELECT * FROM teachers WHERE id = $teacher_id");
$teacher = $result->fetch_assoc();

$success = $error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $conn->real_escape_string(trim($_POST['full_name']));
    $phone = $conn->real_escape_string(trim($_POST['phone']));
    $bio = $conn->real_escape_string(trim($_POST['bio']));
    $gender = $conn->real_escape_string(trim($_POST['gender']));
    $photoPath = $teacher['profile_photo'];

    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === 0) {
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));

        if (in_array($ext, $allowedTypes)) {
            $uploadDir = 'teacher_photos/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $uniqueName = 'teacher_' . uniqid() . '.' . $ext;
            $targetPath = $uploadDir . $uniqueName;

            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $targetPath)) {
                if (!empty($teacher['profile_photo']) && file_exists($teacher['profile_photo'])) {
                    unlink($teacher['profile_photo']);
                }
                $photoPath = $conn->real_escape_string($targetPath);
            } else {
                $error = "❌ Failed to upload the photo.";
            }
        } else {
            $error = "❌ Invalid image type. Allowed: JPG, PNG, GIF.";
        }
    }

    if (empty($error)) {
        $sql = "UPDATE teachers SET full_name='$full_name', phone='$phone', bio='$bio', gender='$gender', profile_photo='$photoPath' WHERE id=$teacher_id";
        if ($conn->query($sql)) {
            $success = "✅ Profile updated successfully.";
            $result = $conn->query("SELECT * FROM teachers WHERE id = $teacher_id");
            $teacher = $result->fetch_assoc();
            $_SESSION['teacher_name'] = $teacher['full_name'];
        } else {
            $error = "❌ Failed to update profile.";
        }
    }
}

$name = htmlspecialchars($teacher['full_name']);
$photo = htmlspecialchars($teacher['profile_photo']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Teacher Profile - Conquer High School</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
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
    /* Form Card */
    .card {
      background-color: #fefefe;
      border-radius: 12px;
      padding: 2rem;
      box-shadow: 0 8px 20px rgb(0 0 0 / 0.05);
      max-width: 600px;
      margin: 0 auto;
      color: #34495e;
    }
    body.dark .card {
      background-color: #222;
      color: #ddd;
    }
    form label {
      display: block;
      margin-top: 1rem;
      font-weight: 600;
    }
    input, select, textarea {
      width: 100%;
      padding: 10px;
      margin-top: 0.3rem;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 1rem;
      font-family: inherit;
      color: inherit;
      background-color: inherit;
      transition: border-color 0.3s ease;
    }
    input:focus, select:focus, textarea:focus {
      outline: none;
      border-color: #3498db;
      box-shadow: 0 0 6px #3498dbaa;
    }
    button {
      margin-top: 1.5rem;
      padding: 12px;
      width: 100%;
      background-color: #2980b9;
      border: none;
      color: white;
      font-weight: 700;
      font-size: 1.1rem;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    button:hover, button:focus {
      background-color: #1c5980;
      outline: none;
    }

    .profile-photo img {
      margin-top: 1rem;
      border-radius: 50%;
      width: 90px;
      height: 90px;
      object-fit: cover;
      border: 3px solid #2980b9;
      display: block;
      margin-left: auto;
      margin-right: auto;
    }

    .msg-success {
      color: #27ae60;
      font-weight: 600;
      margin-bottom: 1rem;
      text-align: center;
    }
    .msg-error {
      color: #c0392b;
      font-weight: 600;
      margin-bottom: 1rem;
      text-align: center;
    }

    footer {
      text-align: center;
      margin-top: 3rem;
      font-size: 0.9rem;
      color: #7f8c8d;
      user-select: none;
      border-top: 1px solid #ddd;
      padding-top: 1rem;
    }
    body.dark footer {
      color: #aaa;
      border-color: #444;
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
  <!-- Main content -->
  <main class="main" role="main">
    <header>
      <button class="mobile-toggle" aria-label="Toggle menu" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
      </button>
      <div class="profile-info" aria-live="polite">
        <img src="<?= $photo ?>" alt="Profile photo of <?= $name ?>" loading="lazy" />
        <strong>Welcome, <?= $name ?></strong>
        <div class="clock" id="clock" aria-atomic="true" aria-live="off" aria-relevant="text"></div>
      </div>
      <button class="toggle-btn" aria-label="Toggle dark mode" onclick="toggleDarkMode()">
        <i class="fas fa-moon" id="darkIcon"></i>
      </button>
    </header>

    <section class="card" aria-label="Edit Teacher Profile Form">
      <h1>Edit Profile</h1>
      <?php if ($success): ?>
        <p class="msg-success" role="alert"><?= $success ?></p>
      <?php endif; ?>
      <?php if ($error): ?>
        <p class="msg-error" role="alert"><?= $error ?></p>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data" novalidate>
        <label for="full_name">Full Name:</label>
        <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($teacher['full_name']) ?>" required autocomplete="name" />

        <label for="phone">Phone:</label>
        <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($teacher['phone']) ?>" autocomplete="tel" />

        <label for="bio">Bio:</label>
        <textarea id="bio" name="bio" rows="3" required><?= htmlspecialchars($teacher['bio']) ?></textarea>

        <label for="gender">Gender:</label>
        <select id="gender" name="gender" required>
          <option value="" disabled <?= $teacher['gender'] === '' ? 'selected' : '' ?>>-- Select --</option>
          <option value="Male" <?= $teacher['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
          <option value="Female" <?= $teacher['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
          <option value="Other" <?= $teacher['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
        </select>

        <label for="profile_photo">Profile Photo:</label>
        <input type="file" id="profile_photo" name="profile_photo" accept="image/*" />

        <div class="profile-photo">
          <?php if (!empty($photo) && file_exists($photo)): ?>
            <img src="<?= $photo ?>" alt="Current profile photo" />
          <?php else: ?>
            <p style="font-style: italic; color: #777;">No photo uploaded</p>
          <?php endif; ?>
        </div>

        <button type="submit">Update Profile</button>
      </form>
    </section>

    <footer>
      &copy; <?= date("Y") ?> Conquer High School | <a href="#">Privacy</a> | <a href="#">Help</a>
    </footer>
  </main>

  <script>
    // Dark mode toggle
    function toggleDarkMode() {
      document.body.classList.toggle('dark');
      const isDark = document.body.classList.contains('dark');
      document.getElementById('darkIcon').className = isDark ? 'fas fa-sun' : 'fas fa-moon';
      localStorage.setItem('darkMode', isDark);
    }

    // Sidebar collapse toggle
    function toggleCollapse() {
      document.body.classList.toggle('collapsed');
      const isCollapsed = document.body.classList.contains('collapsed');
      document.getElementById('collapseIcon').className = isCollapsed ? 'fas fa-angle-double-right' : 'fas fa-angle-double-left';
      localStorage.setItem('sidebarCollapsed', isCollapsed);
    }

    // Sidebar mobile toggle
    function toggleSidebar() {
      document.body.classList.toggle('sidebar-open');
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function (e) {
      const sidebar = document.getElementById('sidebar');
      const toggle = document.querySelector('.mobile-toggle');
      if (window.innerWidth <= 768 && document.body.classList.contains('sidebar-open')) {
        if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
          document.body.classList.remove('sidebar-open');
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

      // Restore dark mode from localStorage
      if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark');
        document.getElementById('darkIcon').className = 'fas fa-sun';
      }

      // Restore sidebar collapse from localStorage
      if (localStorage.getItem('sidebarCollapsed') === 'true') {
        document.body.classList.add('collapsed');
        document.getElementById('collapseIcon').className = 'fas fa-angle-double-right';
      }
    };

    // Dropdown toggle behavior
    document.querySelectorAll('.dropdown-toggle').forEach(btn => {
      btn.addEventListener('click', () => {
        const parent = btn.closest('.dropdown');
        parent.classList.toggle('open');
      });
    });
  </script>
</body>
</html>
