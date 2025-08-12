<?php
session_start();
if (!isset($_SESSION['teacher_logged_in'])) {
    header("Location: teacher_login.php");
    exit();
}

$teacher_name = $_SESSION['teacher_name'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Teacher Panel</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9;
        }
        .sidebar {
            height: 100vh;
            width: 220px;
            position: fixed;
            background-color: #002244;
            padding-top: 20px;
            color: white;
        }
        .sidebar h3 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 20px;
        }
        .sidebar a {
            display: block;
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            transition: background 0.3s;
        }
        .sidebar a:hover {
            background-color: #004080;
        }
        .content {
            margin-left: 240px;
            padding: 30px;
        }
        .content h2 {
            color: #007bff;
        }
        form {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.2);
            max-width: 600px;
            margin-top: 20px;
        }
        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }
        input, select, textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            margin-top: 20px;
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: bold;
        }
        button:hover {
            background: #0056b3;
        }
        .profile-photo {
            text-align: center;
            margin-top: 15px;
        }
        .profile-photo img {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #007bff;
        }
        .msg-success {
            color: green;
            text-align: center;
        }
        .msg-error {
            color: red;
            text-align: center;
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

<div class="content">
    <h2>Update Profile</h2>

    <?php
    $conn = new mysqli("localhost", "root", "", "school");
    if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

    $teacher_id = $_SESSION['teacher_id'];
    $success = "";
    $error = "";

    $result = $conn->query("SELECT * FROM teachers WHERE id = $teacher_id");
    $teacher = $result->fetch_assoc();

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $full_name = $conn->real_escape_string($_POST['full_name']);
        $phone = $conn->real_escape_string($_POST['phone']);
        $bio = $conn->real_escape_string($_POST['bio']);
        $gender = $conn->real_escape_string($_POST['gender']);

        $photoPath = $teacher['profile_photo'];

        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
            $uploadDir = 'teacher_photos/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            $ext = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
            $uniqueName = 'teacher_' . uniqid() . '.' . $ext;
            $targetPath = $uploadDir . $uniqueName;

            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $targetPath)) {
                if (!empty($teacher['profile_photo']) && file_exists($teacher['profile_photo'])) {
                    unlink($teacher['profile_photo']);
                }
                $photoPath = $conn->real_escape_string($targetPath);
            } else {
                $error = "❌ Failed to upload photo.";
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
    $conn->close();
    ?>

    <?php if ($success): ?>
        <p class="msg-success"><?= $success ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
        <p class="msg-error"><?= $error ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <label>Full Name:</label>
        <input type="text" name="full_name" value="<?= htmlspecialchars($teacher['full_name']) ?>" required>

        <label>Phone:</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($teacher['phone']) ?>">

        <label>Bio:</label>
        <textarea name="bio" rows="3" required><?= htmlspecialchars($teacher['bio']) ?></textarea>

        <label>Gender:</label>
        <select name="gender" required>
            <option value="">-- Select --</option>
            <option value="Male" <?= $teacher['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= $teacher['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
            <option value="Other" <?= $teacher['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
        </select>

        <label>Profile Photo:</label>
        <input type="file" name="profile_photo" accept="image/*">
        <div class="profile-photo">
            <?php if (!empty($teacher['profile_photo']) && file_exists($teacher['profile_photo'])): ?>
                <img src="<?= $teacher['profile_photo'] ?>" alt="Profile Photo">
            <?php else: ?>
                <p>No photo uploaded</p>
            <?php endif; ?>
        </div>

        <button type="submit">Update Profile</button>
    </form>
</div>

</body>
</html>
