<?php
// -------------------------------
// DATABASE CONNECTION
// -------------------------------
$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Fetch dynamic content
$newsQuery = $conn->query("SELECT title, content, created_at FROM news_events WHERE visibility='public' ORDER BY created_at DESC LIMIT 5");
$mediaQuery = $conn->query("SELECT file_path FROM media_gallery ORDER BY uploaded_at DESC LIMIT 5");
$testQuery = $conn->query("SELECT author, content FROM testimonials WHERE approved=1 ORDER BY created_at DESC LIMIT 5");
$noticeQuery = $conn->query("SELECT message FROM live_notices WHERE visible = 1 ORDER BY created_at DESC LIMIT 1");
$faqQuery = $conn->query("SELECT question, answer FROM faqs ORDER BY id DESC LIMIT 5");

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Conquer High School - Home</title>
  <meta name="description" content="Official website of Conquer High School - Providing quality education with excellence.">
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.css">
  <style>
    body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; color: #333; }
    .navbar { background: #002244; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 1000; }
    .logo { font-size: 1.5rem; font-weight: bold; display: flex; align-items: center; gap: 10px; }
    .logo img { height: 40px; }
    .nav-links { list-style: none; display: flex; gap: 20px; }
    .nav-links a { color: white; text-decoration: none; font-weight: bold; }
    .nav-links a:hover { color: orange; }
    .login-btn { background: orange; padding: 5px 10px; border-radius: 5px; }
    .toggle-dark { cursor: pointer; font-size: 1.2rem; }
    .hamburger { display: none; flex-direction: column; cursor: pointer; gap: 5px; }
    .hamburger div { width: 25px; height: 3px; background: white; }
    @media (max-width: 768px) {
      .nav-links { display: none; flex-direction: column; background: #002244; position: absolute; top: 60px; right: 0; width: 200px; padding: 10px; }
      .nav-links.show { display: flex; }
      .hamburger { display: flex; }
    }

  .flag-hero {
  background-image: url('pubpage1.jpg');
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  height: 700px;
  position: relative;
}
.login-dropdown {
  position: relative;
}

.login-dropdown .dropdown-menu {
  display: none;
  position: absolute;
  background-color: white;
  min-width: 160px;
  top: 100%;
  right: 0;
  z-index: 1000;
  border-radius: 5px;
  overflow: hidden;
  box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.login-dropdown .dropdown-menu li a {
  color: #002244;
  padding: 10px 15px;
  display: block;
  text-decoration: none;
  font-weight: normal;
}

.login-dropdown .dropdown-menu li a:hover {
  background-color: #f0f0f0;
}

.login-dropdown:hover .dropdown-menu {
  display: block;
}
/* === FAQ Section === */
.faq-container {
  max-width: 800px;
  margin: 0 auto;
  text-align: left;
  padding: 10px;
}

.faq-item {
  margin-bottom: 20px;
  padding: 15px;
  background: #f8f8f8;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.faq-item strong {
  font-size: 1rem;
  color: #002244;
}

.faq-item p {
  margin-top: 8px;
  color: #444;
}

.faq-link {
  text-align: center;
  margin-top: 20px;
}

.faq-link .btn {
  padding: 10px 20px;
  background: orange;
  color: white;
  border-radius: 5px;
  text-decoration: none;
  font-weight: bold;
}

body.dark .faq-item {
  background: #2c2c2c;
  color: #f1f1f1;
}

body.dark .faq-item strong {
  color: #f1f1f1;
}

body.dark .faq-item p {
  color: #ccc;
}







    .hero-message {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      z-index: 10;
      color: white;
      text-align: center;
      text-shadow: 2px 2px 5px rgba(0,0,0,0.6);
    }
    .hero-message h1 { font-size: 3rem; margin-bottom: 20px; }
    .hero-message .btn {
      background: orange;
      color: white;
      padding: 10px 20px;
      border-radius: 5px;
      text-decoration: none;
    }
	 .section-wrap {
      background: #fff;
      padding: 40px 20px;
      margin: 20px 0;
      text-align: center;
    }
    body.dark .section-wrap {
      background: #1e1e1e;
    }

    .news-container, .gallery-container, .testimonial-container {
      display: flex;
      justify-content: center;
      gap: 20px;
      flex-wrap: wrap;
      margin-top: 20px;
    }
    .news-item, .testimonial {
      background: #f1f1f1;
      padding: 20px;
      max-width: 300px;
      border-radius: 10px;
      transition: transform 0.3s;
    }
    body.dark .news-item, body.dark .testimonial {
      background: #2c2c2c;
    }
    .news-item:hover, .testimonial:hover {
      transform: scale(1.03);
    }
	footer {
      background: #002244;
      color: white;
      text-align: center;
      padding: 20px;
      margin-bottom: 60px;
    }
	.site-footer {
  background-color: #0b2242;
  color: #fff;
  padding: 40px 20px 20px;
  font-family: 'Segoe UI', sans-serif;
}

.footer-container {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  gap: 30px;
  max-width: 1200px;
  margin: auto;
}

.footer-section {
  flex: 1;
  min-width: 250px;
}

.footer-section h4 {
  font-size: 18px;
  margin-bottom: 15px;
  color: #ffc107;
}

.footer-section p,
.footer-section li {
  font-size: 14px;
  margin-bottom: 10px;
  line-height: 1.6;
}

.footer-section ul {
  list-style: none;
  padding: 0;
}

.footer-section a {
  color: #ffc107;
  text-decoration: none;
}

.footer-section a:hover {
  text-decoration: underline;
}

.footer-bottom {
  text-align: center;
  margin-top: 30px;
  padding-top: 20px;
  border-top: 1px solid #fff;
  font-size: 13px;
  color: #bbb;
}
.social-media .social-icons {
  display: flex;
  gap: 15px;
  margin-top: 10px;
}

.social-media .social-icons a {
  color: #ffc107;
  font-size: 20px;
  transition: color 0.3s ease;
}

.social-media .social-icons a:hover {
  color: #fff;
}


@media (min-width: 769px) {
  .bottom-nav {
    display: none;
  }
}
.bottom-nav {
  background: #001d3d;
  box-shadow: 0 -2px 6px rgba(0,0,0,0.2);
}

.bottom-nav a {
  padding: 5px;
  font-size: 13px;
  transition: color 0.3s ease;
}

.bottom-nav i {
  font-size: 20px;
}
body.dark .footer {
  background: #111;
  color: #ddd;
}
body.dark .footer a {
  color: #aaa;
}


    .live-notice { background: #ffcc00; padding: 10px; text-align: center; font-weight: bold; }
    #calendar { max-width: 900px; margin: 40px auto; }
    .newsletter { background: #eee; padding: 30px; max-width: 500px; margin: 40px auto; border-radius: 10px; text-align: center; }
    .newsletter input[type=email] { width: 70%; padding: 10px; margin-right: 10px; }
    .newsletter button { padding: 10px 15px; background: orange; color: white; border: none; border-radius: 5px; }
    .bottom-nav { position: fixed; bottom: 0; left: 0; width: 100%; background: #001d3d; padding: 10px 0; display: flex; justify-content: space-around; align-items: center; z-index: 999; }
    .bottom-nav a { color: white; text-decoration: none; font-size: 14px; display: flex; flex-direction: column; align-items: center; }
    .bottom-nav a:hover { color: orange; }
    .bottom-nav i { font-size: 18px; margin-bottom: 3px; }
    #topBtn { position: fixed; bottom: 70px; right: 20px; background: orange; color: white; border: none; padding: 10px; border-radius: 50%; display: none; cursor: pointer; z-index: 1000; }
  </style>
</head>
<body>
<header class="navbar">
  <div class="logo">
    <img src="school_logo.png" alt="Conquer High School Logo">
    CONQUER HIGH SCHOOL
  </div>
  <nav>
    <div class="hamburger" onclick="toggleMenu(this)">
      <div></div><div></div><div></div>
    </div>
    <ul class="nav-links" id="navLinks">
      <li><a href="home.php">Home</a></li>
      <li><a href="about.php">About</a></li>
      <li><a href="academics.php">Academics</a></li>
      <li><a href="admission_upload_form.php">Admissions</a></li>
      <li><a href="testimonials.php">Testimonials</a></li>
      <li><a href="contact.php">Contact Us</a></li>
      <li class="login-dropdown">
  <a href="#" class="login-btn">Login ▾</a>
  <ul class="dropdown-menu">
    <li><a href="admin_login.php">Admin Login</a></li>
    <li><a href="teacher_login.php">Staff Login</a></li>
    <li><a href="login.php">Student Login</a></li>
  </ul>
</li>

      <li><span class="toggle-dark" onclick="toggleDark()">🌙</span></li>
    </ul>
  </nav>
</header>


<?php if ($notice = $noticeQuery->fetch_assoc()): ?>
  <div class="live-notice">
    <?= htmlspecialchars($notice['message']) ?>
  </div>
<?php endif; ?>

<!-- FLAG HERO SECTION -->
<div class="flag-hero">
  <div class="hero-message">
    <h1>Welcome to Conquer High School</h1>
    <p>Empowering Future Leaders Through Quality Education</p>
    <a href="#about" class="btn">Explore More</a>
  </div>
</div>

<!--About-->
<section id="about" class="section-wrap">
<h2>About Us</h2>
<p>Conquer High School is commited to providing a nurturing and empowering learning environment. we focus on holistic education that prepares stdudents for global stage while perservung local values and cultural identity.</p>

</section> 
<!-- LATEST NEWS -->
<section class="section-wrap">
  <h2>Latest News</h2>
  <div class="news-container">
    <?php while($row = $newsQuery->fetch_assoc()): ?>
      <div class="news-item">
        <h3><?= htmlspecialchars($row['title']) ?></h3>
        <p><?= nl2br(htmlspecialchars(substr($row['content'], 0, 100))) ?>...</p>
        <small><em><?= date("M d, Y", strtotime($row['created_at'])) ?></em></small>
      </div>
    <?php endwhile; ?>
  </div>
  <a href="public_view_news.php" class="btn">View All News</a>
</section>
<!-- GALLERY -->
<section class="section-wrap">
  <h2>Media Gallery</h2>
  <div class="gallery-container">
    <?php while($media = $mediaQuery->fetch_assoc()): ?>
      <img src="<?= htmlspecialchars($media['file_path']) ?>" alt="Gallery image" onerror="this.src='default.jpg'">
    <?php endwhile; ?>
  </div>
  <a href="media_gallery.php" class="btn">View Full Gallery</a>
</section>
<!--faqs--->
<section class="section-wrap">
  <h2>Frequently Asked Questions</h2>
  <div class="faq-container">
    <?php if ($faqQuery && $faqQuery->num_rows > 0): ?>
      <?php while($faq = $faqQuery->fetch_assoc()): ?>
        <div class="faq-item">
          <strong>Q: <?= htmlspecialchars($faq['question']) ?></strong>
          <p>A: <?= htmlspecialchars($faq['answer']) ?></p>
        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p>No FAQs available at the moment.</p>
    <?php endif; ?>
  </div>
  <div class="faq-link">
    <a href="faq.php" class="btn">View All FAQs</a>
  </div>
</section>

<!-- TESTIMONIALS -->
<section class="section-wrap">
  <h2>Testimonials</h2>
  <div class="testimonial-container">
    <?php while($t = $testQuery->fetch_assoc()): ?>
      <div class="testimonial">
        "<?= htmlspecialchars($t['content']) ?>"<br><strong>- <?= htmlspecialchars($t['author']) ?></strong>
      </div>
    <?php endwhile; ?>
  </div>
</section>

<!-- ✅ NEWSLETTER SUBSCRIPTION SECTION -->
<section class="section-wrap" style="background: #f9f9f9;">
  <h2>Subscribe to Our Newsletter</h2>
  <form action="subscribe.php" method="POST" style="margin-top: 10px;">
    <input type="email" name="email" required placeholder="Enter your email"
           style="padding: 10px; width: 250px; max-width: 90%;">
    <button type="submit"
            style="padding: 10px 20px; background: orange; color: white; border: none; border-radius: 5px;">
      Subscribe
    </button>
  </form>
  <?php if (isset($_GET['subscribed'])): ?>
    <p style="color: <?= $_GET['subscribed'] == 1 ? 'green' : 'red' ?>; margin-top: 10px;">
      <?= $_GET['subscribed'] == 1 ? 'Thank you for subscribing!' : 'Invalid email address.' ?>
    </p>
  <?php endif; ?>
</section>

<!-- FOOTER -->
<footer class="site-footer">
  <div class="footer-container">
    <!-- About Section -->
    <div class="footer-section">
      <h4>About Conquer High School</h4>
      <p>Conquer High School is committed to academic excellence, discipline, and nurturing all-rounded students.
        <a href="about.php">Learn more →</a>
      </p>
    </div>

    <!-- Quick Links -->
    <div class="footer-section">
      <h4>Quick Links</h4>
      <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="admission_upload_form.php">Admissions</a></li>
        <li><a href="academics.php">Academics</a></li>
        <li><a href="faq.php">FAQs</a></li>
        <li><a href="contact.php">Contact Us</a></li>
      </ul>
    </div>

    <!-- Contact Information -->
    <div class="footer-section">
      <h4>Contact Info</h4>
      <p><strong>Address:</strong> P.O. Box 456, Nairobi, Kenya</p>
      <p><strong>Phone:</strong> +254 712 345678</p>
      <p><strong>Email:</strong> info@conquerhigh.ac.ke</p>
    </div>
  </div>

  <div class="footer-bottom">
<!-- Social Media -->
<div class="footer-section social-media">
  <h4>Follow Us</h4>
  <div class="social-icons">
    <a href="https://www.facebook.com/conquerhighschool" target="_blank" title="Facebook">
      <i class="fab fa-facebook-f"></i>
    </a>
    <a href="https://www.instagram.com/conquerhighschool" target="_blank" title="Instagram">
      <i class="fab fa-instagram"></i>
    </a>
    <a href="https://twitter.com/conquerhigh" target="_blank" title="X (Twitter)">
      <i class="fab fa-x-twitter"></i>
    </a>
  </div>
</div>


    <p>&copy; <?php echo date("Y"); ?> Conquer High School. All rights reserved.</p>
	 <p>
    <a href="sitemap.php" target="_blank">XML Sitemap</a> |
    <a href="sitemap_view.php" target="_blank">Human Sitemap</a> |
    <a href="policy.php">Privacy Policy</a> |
    <a href="terms.php">Terms of Service</a>
  </p>
    <p>Designed by Paul Njoroge</p>
  </div>
</footer>



<!-- BOTTOM NAV -->
<div class="bottom-nav">
  <a href="index.php"><i class="fas fa-home"></i>Home</a>
  <a href="about.php"><i class="fas fa-info-circle"></i>About</a>
  <a href="academics.php"><i class="fas fa-book"></i>Academics</a>
  <a href="admission_upload_form.php"><i class="fas fa-user-plus"></i>Admissions</a>
  <a href="media_gallery.php"><i class="fas fa-images"></i>Gallery</a>
  <a href="public_view_news.php"><i class="fas fa-newspaper"></i>News</a>
  <a href="contact.php"><i class="fas fa-envelope"></i>Contact</a>
</div>

<!-- SCROLL TO TOP BUTTON -->
<button id="topBtn" onclick="scrollTop()">↑</button>

<!-- JAVASCRIPT -->
<script>
function toggleDark() {
  document.body.classList.toggle('dark');
}

function toggleMenu(el) {
  document.getElementById("navLinks").classList.toggle("show");
  el.classList.toggle("active");
}

// Smooth scroll for "Explore More"
document.querySelector('.btn[href="#about"]').addEventListener('click', function(e) {
  e.preventDefault();
  document.querySelector('#about').scrollIntoView({ behavior: 'smooth' });
});

// Close mobile nav after link click
document.querySelectorAll('.nav-links a').forEach(link => {
  link.addEventListener('click', () => {
    document.getElementById('navLinks').classList.remove('show');
  });
});

// Scroll to top button
window.onscroll = function () {
  document.getElementById('topBtn').style.display = (window.scrollY > 200 ? 'block' : 'none');
};

function scrollTop() {
  window.scroll({ top: 0, behavior: 'smooth' });
}
</script>
</body>
</html>

