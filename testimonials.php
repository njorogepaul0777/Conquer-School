<?php
// --------------------
// DATABASE CONNECTION
// --------------------
$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --------------------
// HANDLE FORM SUBMISSION
// --------------------
$msg = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $author = htmlspecialchars(trim($_POST['author']));
    $content = htmlspecialchars(trim($_POST['content']));

    if (!empty($author) && !empty($content)) {
        $stmt = $conn->prepare("INSERT INTO testimonials (author, content) VALUES (?, ?)");
        $stmt->bind_param("ss", $author, $content);
        if ($stmt->execute()) {
            $msg = "<span style='color: green;'>✅ Thank you! Your testimonial is awaiting approval.</span>";
        } else {
            $msg = "<span style='color: red;'>❌ Something went wrong. Please try again later.</span>";
        }
    } else {
        $msg = "<span style='color: red;'>❗ Please fill in all fields.</span>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Submit Testimonial - Conquer High School</title>
  <link rel="stylesheet" href="style.css" />
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f4f4f4;
      color: #333;
      margin: 0;
      padding: 0;
    }
    body.dark {
      background: #121212;
      color: #ddd;
    }

    /* NAVBAR */
    .navbar {
      background: #002244;
      color: white;
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .logo {
      font-size: 1.5rem;
      font-weight: bold;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .logo img {
      height: 40px;
    }
    .nav-links {
      list-style: none;
      display: flex;
      gap: 20px;
    }
    .nav-links a {
      color: white;
      text-decoration: none;
      font-weight: bold;
    }
    .nav-links a:hover {
      color: orange;
    }
    .login-btn {
      background: orange;
      padding: 5px 10px;
      border-radius: 5px;
    }
    .toggle-dark {
      cursor: pointer;
    }

    /* FORM STYLES */
    .testimonial-form {
      max-width: 600px;
      margin: 40px auto;
      padding: 20px;
      background: white;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    body.dark .testimonial-form {
      background: #1e1e1e;
    }
    .testimonial-form h2 {
      text-align: center;
      margin-bottom: 20px;
    }
    .testimonial-form input, .testimonial-form textarea {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }
    .testimonial-form textarea {
      resize: vertical;
      height: 120px;
    }
    .testimonial-form button {
      background: orange;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
      width: 100%;
    }

    .message {
      text-align: center;
      margin-top: 10px;
    }

    @media (max-width: 768px) {
      .nav-links {
        display: none;
        flex-direction: column;
        background: #002244;
        position: absolute;
        top: 60px;
        right: 0;
        width: 200px;
        padding: 10px;
      }
      .nav-links.show {
        display: flex;
      }
      .hamburger {
        display: flex;
        flex-direction: column;
        gap: 4px;
        cursor: pointer;
      }
      .hamburger div {
        width: 25px;
        height: 3px;
        background: white;
      }
    }
  </style>
</head>
<body>

<!-- NAVBAR -->
<header class="navbar">
  <div class="logo">
    <img src="logo.png" alt="Logo">
    CONQUER HIGH SCHOOL
  </div>
  <nav>
    <div class="hamburger" onclick="toggleMenu(this)">
      <div></div><div></div><div></div>
    </div>
    <ul class="nav-links" id="navLinks">
      <li><a href="index.php">Home</a></li>
      <li><a href="about.php">About</a></li>
      <li><a href="academics.php">Academics</a></li>
      <li><a href="admission_upload_form.php">Admissions</a></li>
      <li><a href="contact.php">Contact</a></li>
      <li><a href="login.php" class="login-btn">Login</a></li>
      <li><span class="toggle-dark" onclick="toggleDark()">🌙</span></li>
    </ul>
  </nav>
</header>

<!-- TESTIMONIAL FORM -->
<div class="testimonial-form">
  <h2>Submit Your Testimonial</h2>
  <form method="post">
    <input type="text" name="author" placeholder="Your Name" required />
    <textarea name="content" placeholder="Write your testimonial..." required></textarea>
    <button type="submit">Submit Testimonial</button>
  </form>
  <?php if ($msg): ?>
    <div class="message"><?= $msg ?></div>
  <?php endif; ?>
</div>

<!-- JAVASCRIPT -->
<script>
function toggleMenu(el) {
  document.getElementById("navLinks").classList.toggle("show");
  el.classList.toggle("active");
}
function toggleDark() {
  document.body.classList.toggle("dark");
}
</script>
</body>
</html>

