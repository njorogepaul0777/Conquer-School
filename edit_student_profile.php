<?php
session_start();
if (!isset($_SESSION['admission_no'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$admission_no = $_SESSION['admission_no'];
$errors = [];
$success = "";

// Fetch current student data
$stmt = $conn->prepare("SELECT * FROM students_admitted WHERE admission_no = ?");
$stmt->bind_param("s", $admission_no);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

function e($v) {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update personal info
    if (isset($_POST['update_info'])) {
        $full_name = trim($_POST['full_name']);
        $gender = trim($_POST['gender']);
        $date_of_birth = trim($_POST['date_of_birth']);
        $contact = trim($_POST['contact']);
        $email = trim($_POST['email']);
        $address = trim($_POST['address']);
        $parent_name = trim($_POST['parent_name']);
        $parent_contact = trim($_POST['parent_contact']);

        // Validate basic required fields
        if ($full_name === '') $errors[] = "Full Name is required.";
        if (!in_array($gender, ['Male','Female','Other'])) $errors[] = "Please select a valid gender.";
        if ($date_of_birth === '') $errors[] = "Date of Birth is required.";
        if ($contact === '') $errors[] = "Contact is required.";
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";

        if (empty($errors)) {
            // Update DB
            $update_stmt = $conn->prepare("UPDATE students_admitted SET full_name=?, gender=?, date_of_birth=?, contact=?, email=?, address=?, parent_name=?, parent_contact=? WHERE admission_no=?");
            $update_stmt->bind_param("sssssssss", $full_name, $gender, $date_of_birth, $contact, $email, $address, $parent_name, $parent_contact, $admission_no);
            if ($update_stmt->execute()) {
                $success = "Profile updated successfully.";
                // Refresh student data for form
                $student = [
                    'full_name'=>$full_name,
                    'gender'=>$gender,
                    'date_of_birth'=>$date_of_birth,
                    'contact'=>$contact,
                    'email'=>$email,
                    'address'=>$address,
                    'parent_name'=>$parent_name,
                    'parent_contact'=>$parent_contact
                ] + $student;
            } else {
                $errors[] = "Failed to update profile. Please try again.";
            }
            $update_stmt->close();
        }
    }

    // Change password
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($current_password === '' || $new_password === '' || $confirm_password === '') {
            $errors[] = "All password fields are required.";
        } else if ($new_password !== $confirm_password) {
            $errors[] = "New password and confirmation do not match.";
        } else if (strlen($new_password) < 6) {
            $errors[] = "New password must be at least 6 characters.";
        } else {
            if (!isset($student['password_hash'])) {
                $errors[] = "Password change is not supported currently.";
            } else if (!password_verify($current_password, $student['password_hash'])) {
                $errors[] = "Current password is incorrect.";
            } else {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $pass_stmt = $conn->prepare("UPDATE students_admitted SET password_hash=? WHERE admission_no=?");
                $pass_stmt->bind_param("ss", $new_hash, $admission_no);
                if ($pass_stmt->execute()) {
                    $success = "Password changed successfully. Please login again.";
                    $pass_stmt->close();
                    $stmt->close();
                    $conn->close();
                    $_SESSION['password_changed'] = true; // For auto logout on profile page
                    header("Location: student_profile.php");
                    exit();
                } else {
                    $errors[] = "Failed to update password.";
                }
                $pass_stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Edit Profile - Conquer High School</title>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<!-- FontAwesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
<style>
  /* Reset & base */
  * {
    margin: 0; padding: 0; box-sizing: border-box;
  }
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #eef2f7;
    color: #111;
    transition: background-color 0.3s, color 0.3s;
    display: flex;
    min-height: 100vh;
  }
  body.dark {
    background: #121212;
    color: #ddd;
  }

  /* Sidebar */
  .sidebar {
    position: fixed;
    top: 0; left: 0;
    width: 220px;
    height: 100vh;
    background: #003366;
    padding-top: 60px;
    display: flex;
    flex-direction: column;
    color: white;
    transition: transform 0.3s ease;
    z-index: 1000;
  }
  .sidebar.collapsed {
    transform: translateX(-100%);
  }
  .sidebar a {
    display: flex;
    align-items: center;
    padding: 15px 20px;
    color: white;
    text-decoration: none;
    border-bottom: 1px solid rgba(255,255,255,0.15);
    gap: 12px;
    font-size: 1rem;
    transition: background 0.3s;
  }
  .sidebar a:hover,
  .sidebar a.active {
    background: #005599;
  }
  .sidebar a i {
    width: 20px;
    text-align: center;
  }
  .sidebar .toggle-dark {
    margin: 20px;
    padding: 10px 15px;
    background: #005599;
    border-radius: 6px;
    cursor: pointer;
    user-select: none;
    text-align: center;
    font-weight: 600;
  }

  /* Navbar */
  .navbar {
    position: fixed;
    top: 0; left: 220px;
    right: 0;
    height: 60px;
    background: #003366;
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 30px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    transition: left 0.3s ease;
    z-index: 900;
  }
  .navbar.collapsed {
    left: 0;
  }
  .navbar .nav-links a {
    color: white;
    text-decoration: none;
    margin-left: 20px;
    font-weight: 500;
  }
  .navbar .nav-links a:hover,
  .navbar .nav-links a:focus {
    text-decoration: underline;
    outline: none;
  }
  .navbar .logout-btn {
    background: #b30000;
    border: none;
    padding: 6px 12px;
    border-radius: 5px;
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s;
  }
  .navbar .logout-btn:hover,
  .navbar .logout-btn:focus {
    background: #ff3333;
    outline: none;
  }

  /* Toggle Sidebar Button */
  .toggle-btn {
    position: fixed;
    top: 15px; left: 15px;
    background: #003366;
    border: none;
    color: white;
    padding: 10px 15px;
    font-size: 20px;
    cursor: pointer;
    z-index: 1100;
    border-radius: 5px;
  }
  .toggle-btn:focus {
    outline: 3px solid #005599;
  }

  /* Content */
  .content {
    margin-left: 220px;
    padding: 90px 30px 80px;
    max-width: 600px;
    flex: 1;
    transition: margin-left 0.3s ease;
  }
  .content.collapsed {
    margin-left: 0;
  }

  /* Forms */
  h1 {
    color: #003366;
    text-align: center;
    margin-bottom: 1rem;
  }
  form {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 0 10px #ccc;
    margin-bottom: 40px;
    color: #111;
    transition: background-color 0.3s, color 0.3s;
  }
  body.dark form {
    background: #1e1e1e;
    color: #ddd;
    box-shadow: 0 0 10px #222;
  }
  label {
    display: block;
    margin-top: 15px;
    font-weight: 600;
    color: #003366;
  }
  body.dark label {
    color: #aad4ff;
  }
  input[type=text], input[type=email], input[type=date], input[type=password], select, textarea {
    width: 100%;
    padding: 8px;
    margin-top: 5px;
    border-radius: 5px;
    border: 1px solid #ccc;
    font-size: 1rem;
    color: #111;
    background: white;
    transition: background-color 0.3s, color 0.3s;
  }
  body.dark input[type=text], body.dark input[type=email], body.dark input[type=date], body.dark input[type=password], body.dark select, body.dark textarea {
    background: #2a2a2a;
    color: #ddd;
    border: 1px solid #555;
  }
  button {
    margin-top: 20px;
    background: #003366;
    color: white;
    font-weight: 700;
    border: none;
    padding: 12px 20px;
    border-radius: 8px;
    cursor: pointer;
    width: 100%;
  }
  button:hover {
    background: #005599;
  }
  .messages {
    margin-bottom: 15px;
  }
  .messages .error {
    color: #b30000;
    background: #fdd;
    padding: 10px;
    border-radius: 6px;
  }
  .messages .success {
    color: #005500;
    background: #dfd;
    padding: 10px;
    border-radius: 6px;
  }
  .section-divider {
    margin-top: 40px;
    border-top: 2px solid #003366;
    padding-top: 20px;
    font-weight: 700;
    color: #003366;
    font-size: 1.2rem;
  }
  body.dark .section-divider {
    border-top-color: #aad4ff;
    color: #aad4ff;
  }

  /* Password visibility toggle */
  .password-wrapper {
    position: relative;
  }
  .password-toggle {
    position: absolute;
    right: 10px;
    top: 38px;
    cursor: pointer;
    font-size: 1.1rem;
    color: #555;
    user-select: none;
  }
  body.dark .password-toggle {
    color: #ccc;
  }

  /* Bottom Nav for mobile */
  .bottom-nav {
    display: none;
  }
  @media (max-width: 768px) {
    .sidebar {
      transform: translateX(-100%);
      position: fixed;
      z-index: 1100;
    }
    .sidebar.show {
      transform: translateX(0);
    }
    .navbar {
      left: 0 !important;
      padding-left: 60px;
    }
    .toggle-btn {
      display: block;
    }
    .content {
      margin-left: 0 !important;
      padding: 90px 15px 90px;
      max-width: 100%;
    }
    .bottom-nav {
      display: flex;
      background: #003366;
      position: fixed;
      bottom: 0;
      left: 0;
      width: 100%;
      justify-content: space-around;
      padding: 10px 0;
      z-index: 1200;
    }
    .bottom-nav a {
      color: white;
      font-size: 13px;
      text-decoration: none;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 4px;
      font-weight: 600;
    }
    .bottom-nav a:hover,
    .bottom-nav a:focus {
      text-decoration: underline;
      outline: none;
    }
    .bottom-nav a i {
      font-size: 18px;
    }
  }
</style>
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar" id="sidebar" aria-label="Main navigation">
  <a href="student_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
  <a href="student_profile.php" class="active" aria-current="page"><i class="fas fa-user"></i> Profile</a>
  <a href="select_result.php"><i class="fas fa-file-alt"></i> Results</a>
  <a href="student_fee.php"><i class="fas fa-money-check-alt"></i> Fee Portal</a>
  <a href="cbc_tracker.php"><i class="fas fa-brain"></i> CBC Skills</a>
  <a href="student_media_gallery.php"><i class="fas fa-photo-video"></i> Media Gallery</a>
  <a href="contact_admin.php"><i class="fas fa-envelope"></i> Contact Admin</a>
  <div class="toggle-dark" id="sidebar-dark-toggle" role="button" tabindex="0" aria-pressed="false" aria-label="Toggle dark mode">Toggle Dark Mode</div>
</nav>

<!-- Toggle sidebar button for mobile -->
<button class="toggle-btn" id="sidebarToggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="sidebar">
  <i class="fas fa-bars"></i>
</button>

<!-- Navbar -->
<header class="navbar" id="navbar">
  <div>Conquer High School - Student Portal</div>
  <div class="nav-links">
    <a href="student_profile.php" aria-current="page">Profile</a>
    <form style="display:inline" method="POST" action="logout.php" onsubmit="return confirm('Are you sure you want to logout?');" aria-label="Logout form">
      <button type="submit" class="logout-btn">Logout <i class="fas fa-sign-out-alt"></i></button>
    </form>
  </div>
</header>

<!-- Page Content -->
<main class="content" id="content" tabindex="-1">
  <h1>Edit Profile / Change Password</h1>

  <div class="messages" role="alert" aria-live="assertive" id="messages">
  <?php if (!empty($errors)): ?>
      <?php foreach ($errors as $error): ?>
          <div class="error"><?= e($error) ?></div>
      <?php endforeach; ?>
  <?php endif; ?>
  <?php if ($success): ?>
      <div class="success"><?= e($success) ?></div>
  <?php endif; ?>
  </div>

  <!-- Personal Info Update Form -->
  <form method="POST" novalidate>
      <div class="section-divider">Update Personal Information</div>
      <label for="full_name">Full Name</label>
      <input type="text" name="full_name" id="full_name" required value="<?= e($student['full_name'] ?? '') ?>" />

      <label for="gender">Gender</label>
      <select name="gender" id="gender" required>
          <?php
          $genders = ['Male', 'Female', 'Other'];
          foreach ($genders as $g) {
              $sel = (isset($student['gender']) && $student['gender'] === $g) ? 'selected' : '';
              echo "<option value=\"$g\" $sel>$g</option>";
          }
          ?>
      </select>

      <label for="date_of_birth">Date of Birth</label>
      <input type="date" name="date_of_birth" id="date_of_birth" required value="<?= e($student['date_of_birth'] ?? '') ?>" />

      <label for="contact">Contact Number</label>
      <input type="text" name="contact" id="contact" required pattern="[\d\s+\-]+" title="Numbers, spaces, + and - allowed" value="<?= e($student['contact'] ?? '') ?>" />

      <label for="email">Email</label>
      <input type="email" name="email" id="email" required value="<?= e($student['email'] ?? '') ?>" />

      <label for="address">Address</label>
      <textarea name="address" id="address" rows="3"><?= e($student['address'] ?? '') ?></textarea>

      <label for="parent_name">Parent/Guardian Name</label>
      <input type="text" name="parent_name" id="parent_name" value="<?= e($student['parent_name'] ?? '') ?>" />

      <label for="parent_contact">Parent/Guardian Contact</label>
      <input type="text" name="parent_contact" id="parent_contact" pattern="[\d\s+\-]+" title="Numbers, spaces, + and - allowed" value="<?= e($student['parent_contact'] ?? '') ?>" />

      <button type="submit" name="update_info">Save Changes</button>
  </form>

  <!-- Change Password Form -->
  <form method="POST" novalidate>
      <div class="section-divider">Change Password</div>
      <label for="current_password">Current Password</label>
      <div class="password-wrapper">
        <input type="password" name="current_password" id="current_password" required minlength="6" autocomplete="current-password" />
        <span class="password-toggle" tabindex="0" aria-label="Toggle current password visibility" role="button" aria-pressed="false" data-target="current_password"><i class="fas fa-eye"></i></span>
      </div>

      <label for="new_password">New Password</label>
      <div class="password-wrapper">
        <input type="password" name="new_password" id="new_password" required minlength="6" autocomplete="new-password" />
        <span class="password-toggle" tabindex="0" aria-label="Toggle new password visibility" role="button" aria-pressed="false" data-target="new_password"><i class="fas fa-eye"></i></span>
      </div>

      <label for="confirm_password">Confirm New Password</label>
      <div class="password-wrapper">
        <input type="password" name="confirm_password" id="confirm_password" required minlength="6" autocomplete="new-password" />
        <span class="password-toggle" tabindex="0" aria-label="Toggle confirm password visibility" role="button" aria-pressed="false" data-target="confirm_password"><i class="fas fa-eye"></i></span>
      </div>

      <button type="submit" name="change_password">Change Password</button>
  </form>
</main>

<!-- Bottom Navigation for Mobile -->
<nav class="bottom-nav" aria-label="Mobile navigation">
  <a href="student_dashboard.php"><i class="fas fa-home"></i><span>Home</span></a>
  <a href="student_profile.php" class="active" aria-current="page"><i class="fas fa-user"></i><span>Profile</span></a>
  <a href="select_result.php"><i class="fas fa-file-alt"></i><span>Results</span></a>
  <a href="student_fee.php"><i class="fas fa-money-check-alt"></i><span>Fees</span></a>
  <a href="contact_admin.php"><i class="fas fa-envelope"></i><span>Contact</span></a>
</nav>

<script>
  // Sidebar toggle for mobile
  const sidebar = document.getElementById('sidebar');
  const toggleBtn = document.getElementById('sidebarToggle');
  const navbar = document.getElementById('navbar');
  const content = document.getElementById('content');

  toggleBtn.addEventListener('click', () => {
    const isShown = sidebar.classList.toggle('show');
    toggleBtn.setAttribute('aria-expanded', isShown);
  });

  // Close sidebar on nav link click (mobile)
  sidebar.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => {
      if(window.innerWidth <= 768){
        sidebar.classList.remove('show');
        toggleBtn.setAttribute('aria-expanded', 'false');
      }
    });
  });

  // Dark mode toggle
  const darkToggle = document.getElementById('sidebar-dark-toggle');
  const prefersDarkScheme = window.matchMedia("(prefers-color-scheme: dark)");
  const currentTheme = localStorage.getItem("theme");

  if (currentTheme === "dark" || (!currentTheme && prefersDarkScheme.matches)) {
    document.body.classList.add("dark");
    darkToggle.setAttribute('aria-pressed', 'true');
  }

  darkToggle.addEventListener('click', () => {
    document.body.classList.toggle('dark');
    const isDark = document.body.classList.contains('dark');
    darkToggle.setAttribute('aria-pressed', isDark);
    localStorage.setItem("theme", isDark ? "dark" : "light");
  });
  darkToggle.addEventListener('keydown', e => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      darkToggle.click();
    }
  });

  // Password visibility toggles
  document.querySelectorAll('.password-toggle').forEach(toggle => {
    toggle.addEventListener('click', () => {
      const targetId = toggle.getAttribute('data-target');
      const input = document.getElementById(targetId);
      if (input.type === "password") {
        input.type = "text";
        toggle.setAttribute('aria-pressed', 'true');
        toggle.innerHTML = '<i class="fas fa-eye-slash"></i>';
      } else {
        input.type = "password";
        toggle.setAttribute('aria-pressed', 'false');
        toggle.innerHTML = '<i class="fas fa-eye"></i>';
      }
    });
    toggle.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        toggle.click();
      }
    });
  });

  // Auto-focus success message for screen readers
  window.addEventListener('DOMContentLoaded', () => {
    const successMsg = document.querySelector('.messages .success');
    if (successMsg) {
      successMsg.setAttribute('tabindex', '-1');
      successMsg.focus();
      // Optionally auto-hide after 5 seconds:
      setTimeout(() => { 
        successMsg.style.display = 'none'; 
      }, 5000);
    }
  });
</script>
</body>
</html>
