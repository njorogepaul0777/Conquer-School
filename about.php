<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About Us - Conquer High School</title>
  <meta name="description" content="Learn about Conquer High School - our mission, vision, values, and unique features shaping future leaders.">
  <meta name="keywords" content="Conquer High School, About Us, Mission, Vision, Education, Kenya">
  <meta name="author" content="Conquer High School">
  <!-- Open Graph for social media sharing -->
  <meta property="og:title" content="About Us - Conquer High School">
  <meta property="og:description" content="Discover Conquer High School's mission, vision, and what sets us apart.">
  <meta property="og:type" content="website">
  <meta property="og:url" content="http://localhost/schoo/about.php">
  <meta property="og:image" content="pubpage.png">

  <style>
    /* Base Reset */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f4f6f8;
      color: #333;
      transition: background 0.3s, color 0.3s;
    }
    body.dark {
      background: #121212;
      color: #ddd;
    }

    /* Sticky Navbar */
    .navbar {
      position: sticky; top: 0;
      background: #002244; color: white;
      display: flex; justify-content: space-between; align-items: center;
      padding: 1rem 2rem; z-index: 1000;
    }
    .navbar .logo { font-size: 1.5rem; font-weight: bold; }
    .nav-links { list-style: none; display: flex; gap: 20px; }
    .nav-links a {
      color: white; text-decoration: none; font-weight: bold;
      transition: color 0.3s;
    }
    .nav-links a:hover { color: orange; }
    .toggle-dark { cursor: pointer; padding: 5px; font-size: 1.2rem; }

    /* About Section */
    .about-section {
      padding: 60px 20px;
      max-width: 900px;
      margin: 60px auto;
      background: #fff;
      box-shadow: 0 0 15px rgba(0,0,0,0.05);
      border-radius: 10px;
    }
    body.dark .about-section { background: #1e1e1e; }
    h1, h2 { text-align: center; color: #004080; }
    body.dark h1, body.dark h2 { color: orange; }
    .section { margin-top: 30px; }
    ul { padding-left: 20px; }
    .cta { text-align: center; margin-top: 40px; }
    p { text-align: center; margin-top: 40px; }
    .cta a {
      background: #004080; color: white;
      padding: 12px 24px; border-radius: 5px;
      text-decoration: none; transition: background 0.3s;
	 
    }
    .cta a:hover { background: #0066cc; }

    /* Bottom Navigation */
    .bottom-nav {
      background: #001d3d; padding: 10px;
      display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;
    }
    .bottom-nav a {
      color: white; text-decoration: none;
    }
    .bottom-nav a:hover { text-decoration: underline; }

    /* Footer */
    footer {
      background: #002244; color: white;
      text-align: center; padding: 20px;
    }

    /* Responsive Navbar (Hamburger) */
    .hamburger { display: none; flex-direction: column; cursor: pointer; gap: 5px; }
    .hamburger div { width: 25px; height: 3px; background: white; }
    @media(max-width:768px){
      .nav-links {
        display: none; flex-direction: column;
        background: #002244; position: absolute;
        right: 0; top: 60px; width: 200px; padding: 10px;
      }
      .nav-links.show { display: flex; }
      .hamburger { display: flex; }
    }
  </style>
</head>
<body>


<!-- Sticky Navbar -->
<header class="navbar">
  <div class="logo">🏫 CONQUER HIGH SCHOOL</div>
  <nav>
    <div class="hamburger" onclick="toggleMenu(this)">
      <div></div><div></div><div></div>
    </div>
    <ul class="nav-links" id="navLinks">
      <li><a href="index.php">Home</a></li>
      <li><a href="about.php">About</a></li>
      <li><a href="academics.php">Academics</a></li>
      <li><a href="admission_upload_form.php">Admissions</a></li>
      <li><a href="media_gallery.php">Gallery</a></li>
      <li><a href="public_view_news.php">News</a></li>
	  
      <li><a href="contact.php">Contact</a></li>
      <li><span class="toggle-dark" onclick="toggleDark()">🌙</span></li>
    </ul>
  </nav>
</header>

<!-- About Content -->
<div class="about-section">
  <h1>About Conquer High School</h1>

  <div class="section">
    <h2>Who We Are</h2>
    <p>Conquer High School is a forward-thinking institution committed to nurturing the minds and hearts of tomorrow's leaders. We provide a blend of academic excellence, moral guidance, and 21st-century skills.</p>
  </div>

  <div class="section">
    <h2>Our Mission</h2>
    <p>To empower every learner through quality education, strong values, and skill-based development to succeed in a dynamic world.</p>
  </div>

  <div class="section">
    <h2>Our Vision</h2>
    <p>To be a model school recognized for academic brilliance, digital innovation, and holistic learner development.</p>
  </div>

  <div class="section">
    <h2>What Makes Us Unique</h2>
    <ul>
      <li>Comprehensive CBC and 8-4-4 curriculum</li>
      <li>Qualified and dedicated teaching staff</li>
      <li>Smart classrooms and digital learning integration</li>
      <li>Science and computer laboratories</li>
      <li>Vibrant talent and co-curricular development programs</li>
      <li>Safe, serene, and disciplined learning environment</li>
    </ul>
  </div>

  <div class="section">
    <h2>Our Core Values</h2>
    <ul>
      <li>Integrity</li>
      <li>Excellence</li>
      <li>Creativity</li>
      <li>Respect</li>
      <li>Responsibility</li>
      <li>Teamwork</li>
    </ul>
  </div>

  <div class="section">
    <h2>Our Learners</h2>
    <p>We believe in guiding every student individually and helping them unlock their full potential academically, socially, and emotionally.</p>
  </div>

    <p><strong>Want to learn more?</strong></p>

  <div class="cta">
    <a href="contact.php">Contact Us</a>
  </div>
</div>


<!-- Footer -->
<footer>
  <p>&copy; <?= date("Y") ?> Conquer High School. All rights reserved.</p>
  <p><a href="sitemap_view.php" target="_blank">Sitemap</a> | <a href="privacy.php">Privacy Policy</a></p>
</footer>

<!-- Bottom Navigation Bar -->
<div class="bottom-nav">
  <a href="index.php">Home</a>
  <a href="about.php">About</a>
  <a href="academics.php">Academics</a>
  <a href="admission_upload_form.php">Admissions</a>
  <a href="media_gallery.php">Gallery</a>
  <a href="public_view_news.php">News</a>
  <a href="contact.php">Contact</a>
</div>

<script>
// Toggle dark mode
function toggleDark() {
  document.body.classList.toggle('dark');
}
// Mobile menu toggle
function toggleMenu(el) {
  const nav = document.getElementById('navLinks');
  nav.classList.toggle('show');
  el.classList.toggle('active');
}
</script>

</body>
</html>

