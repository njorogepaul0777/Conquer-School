<?php
session_start();
if (!isset($_SESSION['teacher_logged_in'])) {
    header("Location: teacher_login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$teacher_id = $_SESSION['teacher_id'];
$message = "";

// Handle deletion
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $res = $conn->query("SELECT file_path FROM teacher_resources WHERE id = $id AND teacher_id = $teacher_id");
    if ($res && $res->num_rows > 0) {
        $file = $res->fetch_assoc()['file_path'];
        if (file_exists($file)) unlink($file);
        $conn->query("DELETE FROM teacher_resources WHERE id = $id AND teacher_id = $teacher_id");
        $message = "Resource deleted.";
    }
}

// Handle editing
if (isset($_POST['edit_id'])) {
    $edit_id = (int) $_POST['edit_id'];
    $new_title = $conn->real_escape_string($_POST['edit_title']);
    $conn->query("UPDATE teacher_resources SET title = '$new_title' WHERE id = $edit_id AND teacher_id = $teacher_id");
    $message = "Resource updated.";
}

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['edit_id'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $file = $_FILES['resource_file'];

    if ($file['error'] === 0) {
        $uploadDir = "uploads/resources/";
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

        $filename = basename($file['name']);
        $targetPath = $uploadDir . time() . "_" . $filename;
        $fileType = pathinfo($filename, PATHINFO_EXTENSION);

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $stmt = $conn->prepare("INSERT INTO teacher_resources (teacher_id, title, file_path, file_type) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $teacher_id, $title, $targetPath, $fileType);
            $stmt->execute();
            $message = "Resource uploaded successfully.";
        } else {
            $message = "Upload failed.";
        }
    } else {
        $message = "File error.";
    }
}

// Fetch resources
$resources = [];
$search_query = "";
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_query = $conn->real_escape_string($_GET['search']);
    $res = $conn->query("SELECT * FROM teacher_resources WHERE teacher_id = $teacher_id AND title LIKE '%$search_query%' ORDER BY uploaded_at DESC");
} else {
    $res = $conn->query("SELECT * FROM teacher_resources WHERE teacher_id = $teacher_id ORDER BY uploaded_at DESC");
}
if ($res === false){
	die("error" . $conn->error);
}
while ($row = $res->fetch_assoc()) $resources[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Teaching Resources - Conquer High School</title>
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

/* Toggle dark mode button */
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

/* Message box */
.message {
  background: #dff0d8;
  color: #3c763d;
  padding: 10px;
  margin-bottom: 20px;
  border-left: 5px solid #3c763d;
  border-radius: 4px;
  user-select: none;
}

/* Form styles */
form {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 3px 8px rgba(0,0,0,0.1);
  margin-bottom: 40px;
  max-width: 600px;
}

form input[type="text"], form input[type="file"] {
  width: 100%;
  padding: 12px;
  margin: 10px 0;
  border-radius: 6px;
  border: 1px solid #ccc;
}

form button {
  background: #007bff;
  color: white;
  padding: 10px 18px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

form button:hover {
  background: #0056b3;
}

/* Search box styling */
.search-box {
  margin-bottom: 30px;
  max-width: 600px;
  display: flex;
  gap: 10px;
}
.search-box input[type="text"] {
  flex-grow: 1;
  padding: 10px 12px;
  border-radius: 6px;
  border: 1px solid #ccc;
}

/* Table */
table {
  width: 100%;
  background: white;
  border-collapse: collapse;
  box-shadow: 0 3px 8px rgba(0,0,0,0.1);
  max-width: 900px;
}

th, td {
  padding: 14px;
  border: 1px solid #ddd;
  text-align: left;
  vertical-align: middle;
}

th {
  background: #007bff;
  color: white;
}

/* Preview thumbnails */
.preview {
  max-width: 150px;
  max-height: 100px;
  object-fit: cover;
  border-radius: 6px;
  cursor: pointer;
}

/* Edit form in table */
.edit-form input {
  width: auto;
  padding: 5px;
  border-radius: 4px;
  border: 1px solid #ccc;
  margin-top: 10px;
}

/* Modal styles */
#previewModal {
  display: none;
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.7);
  justify-content: center;
  align-items: center;
  z-index: 9999;
}
#previewModal > div {
  background: white;
  padding: 20px;
  max-width: 90%;
  max-height: 90%;
  border-radius: 10px;
  position: relative;
}
#previewModal > div > span {
  position: absolute;
  top: 10px;
  right: 15px;
  font-size: 24px;
  cursor: pointer;
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
}

/* Hide text when sidebar collapsed */
body.collapsed .sidebar h2 span,
body.collapsed .sidebar .section a span,
body.collapsed .sidebar .dropdown-toggle span {
  display: none;
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
  <main class="main" id="main" tabindex="-1" role="main">
    <header>
      <button class="mobile-toggle" aria-label="Toggle menu" onclick="toggleMobileSidebar()">
        <i class="fas fa-bars"></i>
      </button>
      <h1>Teaching Resources</h1>
      <div class="profile-info" aria-label="User info">
        <strong><?= htmlspecialchars($_SESSION['teacher_name'] ?? 'Teacher') ?></strong>
        <button class="toggle-btn" id="darkModeToggle" aria-label="Toggle dark mode" title="Toggle dark mode">
          <i class="fas fa-moon"></i>
        </button>
        <div class="clock" id="clock" aria-live="polite" aria-atomic="true">--:--:--</div>
      </div>
    </header>

    <?php if ($message): ?>
      <div class="message" role="alert"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" aria-label="Upload new teaching resource">
      <h2>Upload New Resource</h2>
      <input
        type="text"
        name="title"
        placeholder="Resource Title"
        required
        aria-required="true"
      />
      <input
        type="file"
        name="resource_file"
        required
        aria-required="true"
        accept=".pdf,.jpg,.jpeg,.png,.gif,.mp4,.mov,.webm"
      />
      <button type="submit">Upload</button>
    </form>

    <form method="GET" class="search-box" role="search" aria-label="Search resources">
      <input
        type="text"
        name="search"
        placeholder="Search resources..."
        value="<?= htmlspecialchars($search_query) ?>"
        aria-label="Search resources"
      />
      <button type="submit">Search</button>
    </form>

    <h2>My Uploaded Resources</h2>
    <?php if (count($resources) === 0): ?>
      <p>No resources uploaded yet.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Title & Preview</th>
            <th>Type</th>
            <th>Uploaded</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($resources as $r): ?>
            <tr>
              <td>
                <strong><?= htmlspecialchars($r['title']) ?></strong><br />
                <?php
                $ext = strtolower($r['file_type']);
                $previewType = "";
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $previewType = "image";
                    echo "<img class='preview' src='{$r['file_path']}' onclick=\"openModal('{$r['file_path']}', '{$previewType}')\" alt='Resource preview'>";
                } elseif ($ext === 'pdf') {
                    $previewType = "pdf";
                    echo "<img class='preview' src='https://img.icons8.com/color/96/pdf.png' onclick=\"openModal('{$r['file_path']}', '{$previewType}')\" alt='PDF icon'>";
                } elseif (in_array($ext, ['mp4', 'mov', 'webm'])) {
                    $previewType = "video";
                    echo "<img class='preview' src='https://img.icons8.com/ios-filled/100/video.png' onclick=\"openModal('{$r['file_path']}', '{$previewType}')\" alt='Video icon'>";
                }
                ?>
                <form method="POST" class="edit-form" aria-label="Edit resource title">
                  <input type="hidden" name="edit_id" value="<?= $r['id'] ?>" />
                  <input type="text" name="edit_title" value="<?= htmlspecialchars($r['title']) ?>" required aria-required="true" />
                  <button type="submit" aria-label="Save title for <?= htmlspecialchars($r['title']) ?>">Save</button>
                </form>
              </td>
              <td><?= strtoupper(htmlspecialchars($r['file_type'])) ?></td>
              <td><?= date("d M Y, H:i", strtotime($r['uploaded_at'])) ?></td>
              <td>
                <a href="<?= $r['file_path'] ?>" download aria-label="Download <?= htmlspecialchars($r['title']) ?>"><i class="fas fa-download"></i></a>
                <a href="?delete=<?= $r['id'] ?>" onclick="return confirm('Delete this file?')" aria-label="Delete <?= htmlspecialchars($r['title']) ?>"><i class="fas fa-trash"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <!-- Modal Preview -->
      <div
        id="previewModal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modalTitle"
        style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.7); justify-content:center; align-items:center; z-index:9999;"
      >
        <div style="background:white; padding:20px; max-width:90%; max-height:90%; border-radius:10px; position:relative;">
          <button
            onclick="closeModal()"
            style="position:absolute; top:10px; right:15px; font-size:24px; cursor:pointer; background:none; border:none;"
            aria-label="Close preview"
          >&times;</button>
          <div id="modalContent"></div>
        </div>
      </div>
    <?php endif; ?>
  </main>

  <script>
    // Sidebar collapse toggle
    function toggleCollapse() {
      document.body.classList.toggle('collapsed');
      const icon = document.getElementById('collapseIcon');
      if (document.body.classList.contains('collapsed')) {
        icon.classList.remove('fa-angle-double-left');
        icon.classList.add('fa-angle-double-right');
      } else {
        icon.classList.add('fa-angle-double-left');
        icon.classList.remove('fa-angle-double-right');
      }
    }

    // Dropdown toggles
    document.querySelectorAll('.dropdown-toggle').forEach(btn => {
      btn.addEventListener('click', () => {
        const parent = btn.parentElement;
        const expanded = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', !expanded);
        parent.classList.toggle('open');
      });
    });

    // Dark mode toggle
    const darkModeToggle = document.getElementById('darkModeToggle');
    darkModeToggle.addEventListener('click', () => {
      document.body.classList.toggle('dark');
      // Optionally save preference in localStorage
      if (document.body.classList.contains('dark')) {
        localStorage.setItem('darkMode', 'on');
      } else {
        localStorage.setItem('darkMode', 'off');
      }
    });

    // Load dark mode preference
    if (localStorage.getItem('darkMode') === 'on') {
      document.body.classList.add('dark');
    }

    // Mobile sidebar toggle
    function toggleMobileSidebar() {
      document.body.classList.toggle('sidebar-open');
    }

    // Close sidebar on outside click (mobile)
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.sidebar') && !e.target.closest('.mobile-toggle')) {
        document.body.classList.remove('sidebar-open');
      }
    });

    // Clock update
    function updateClock() {
      const now = new Date();
      document.getElementById('clock').innerText = now.toLocaleTimeString();
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Modal preview functions
    function openModal(filePath, type) {
      let html = "";
      if (type === "image") {
        html = `<img src="${filePath}" style="max-width:100%; max-height:80vh;" alt="Resource preview">`;
      } else if (type === "pdf") {
        html = `<iframe src="${filePath}" style="width:90vw; height:80vh;" frameborder="0"></iframe>`;
      } else if (type === "video") {
        html = `<video controls style="max-width:100%; max-height:80vh;">
                  <source src="${filePath}" type="video/mp4" />
                  Your browser does not support the video tag.
                </video>`;
      }
      document.getElementById("modalContent").innerHTML = html;
      document.getElementById("previewModal").style.display = "flex";
    }
    function closeModal() {
      document.getElementById("previewModal").style.display = "none";
      document.getElementById("modalContent").innerHTML = "";
    }
    window.addEventListener("keydown", function(e) {
      if (e.key === "Escape") closeModal();
    });
  </script>
</body>
</html>
