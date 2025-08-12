<?php
$conn = new mysqli("localhost", "root", "", "school");
$news = $conn->query("SELECT * FROM news_events WHERE visibility = 'public' ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>News & Events - Conquer High School</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    * {box-sizing:border-box; margin:0; padding:0;}
    html, body {height:100%; font-family:Arial, sans-serif; background:#f4f4f4; color:#333; display:flex; flex-direction:column;}
    .navbar {
        position:sticky; top:0; background:#003366; color:#fff; padding:10px 20px;
        display:flex; justify-content:space-between; align-items:center; z-index:1000;
    }
    .navbar a {color:white; margin:0 10px; text-decoration:none;}
    .navbar a:hover {text-decoration:underline;}
    .toggle-dark {cursor:pointer; float:right; margin-top:-5px;}
    .header {text-align:center; padding:20px; background:#004080; color:#fff;}
    .container {padding:20px; max-width:1000px; margin:auto; flex:1;}
    .news-card {
        background:white; padding:15px; margin:15px 0; border-radius:8px;
        box-shadow:0 2px 8px rgba(0,0,0,0.1);
    }
    .news-card h3 {margin:0 0 10px; color:#004080;}
    .news-card small {color:#777;}
    .news-card p {max-height:60px; overflow:hidden;}
    .read-more-btn {
        padding:8px 12px; background:#003366; color:white; border:none;
        border-radius:5px; cursor:pointer; margin-top:10px;
    }
    .modal {
        display:none; position:fixed; top:0; left:0; width:100%; height:100%;
        background:rgba(0,0,0,0.6); justify-content:center; align-items:center;
        z-index:999;
    }
    .modal-content {
        background:white; padding:20px; border-radius:10px; max-width:600px;
        width:90%; max-height:80%; overflow-y:auto; position:relative;
    }
    .close-btn {
        position:absolute; top:10px; right:10px; background:red; color:white;
        border:none; border-radius:50%; width:30px; height:30px; font-weight:bold;
        cursor:pointer;
    }
    footer {
        text-align:center; background:#003366; color:white; padding:15px;
    }
    .bottom-nav {
        display:flex; justify-content:space-around; align-items:center;
        position:fixed; bottom:0; left:0; width:100%; background:#003366;
        padding:8px 0; z-index:1000;
    }
    .bottom-nav a {
        color:white; text-decoration:none; font-size:14px; text-align:center;
    }
    .bottom-nav a:hover {text-decoration:underline;}
    .dark {background:#121212; color:#ddd;}
</style>
</head>
<body>

<!-- Top Navbar -->
<div class="navbar">
    <div>🏫 Conquer High School</div>
    <div>
        <a href="home.php">Home</a>
        <a href="about.php">About</a>
        <a href="academics.php">Academics</a>
        <a href="admission_upload_form.php">Admissions</a>
        <a href="public_view_news.php">News</a>
        <a href="contact.php">Contact</a>
        <span class="toggle-dark" onclick="toggleDark()">🌙</span>
    </div>
</div>

<!-- Header -->
<div class="header">
    <h1>Latest News & Events</h1>
    <p>Stay updated with the latest happenings at Conquer High School</p>
</div>

<!-- News Cards Container -->
<div class="container">
<?php if ($news->num_rows > 0): ?>
    <?php while($row = $news->fetch_assoc()): ?>
        <div class="news-card">
            <h3><?= htmlspecialchars($row['title']) ?></h3>
            <small>Posted on <?= date("F j, Y", strtotime($row['created_at'])) ?></small>
            <p><?= nl2br(substr(htmlspecialchars($row['content']), 0, 150)) ?>...</p>
            <button class="read-more-btn"
                data-title="<?= htmlspecialchars($row['title']) ?>"
                data-content="<?= htmlspecialchars(nl2br($row['content'])) ?>">Read More</button>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <p>No public news or events found.</p>
<?php endif; ?>
</div>

<!-- Modal -->
<div class="modal" id="newsModal">
    <div class="modal-content">
        <button class="close-btn" onclick="closeModal()">×</button>
        <h2 id="modalTitle"></h2>
        <p id="modalContent"></p>
    </div>
</div>

<!-- Footer -->
<footer>
    &copy; <?= date('Y') ?> Conquer High School. All Rights Reserved.
    <br><a href="sitemap_view.php" style="color:white;">Sitemap</a> | 
    <a href="privacy.php" style="color:white;">Privacy Policy</a>
</footer>

<!-- Bottom Navbar (Mobile Friendly) -->
<div class="bottom-nav">
    <a href="home.php">🏠<br>Home</a>
    <a href="academics.php">📚<br>Academics</a>
    <a href="admission_upload_form.php">📝<br>Apply</a>
    <a href="public_view_news.php">📰<br>News</a>
    <a href="contact.php">📞<br>Contact</a>
</div>

<!-- JavaScript -->
<script>
const modal = document.getElementById("newsModal");
const modalTitle = document.getElementById("modalTitle");
const modalContent = document.getElementById("modalContent");

document.querySelectorAll(".read-more-btn").forEach(btn => {
    btn.addEventListener("click", function() {
        modalTitle.textContent = this.getAttribute("data-title");
        modalContent.innerHTML = this.getAttribute("data-content");
        modal.style.display = "flex";
    });
});

function closeModal() {
    modal.style.display = "none";
}
window.onclick = function(event) {
    if (event.target == modal) modal.style.display = "none";
}

function toggleDark() {
    document.body.classList.toggle('dark');
}
</script>

</body>
</html>
