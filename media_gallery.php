<?php
$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$category = $_GET['category'] ?? 'all';
$search = $_GET['search'] ?? '';

$query = "SELECT * FROM media_gallery WHERE 1";
if ($category !== 'all') {
    $category = $conn->real_escape_string($category);
    $query .= " AND file_type = '$category'";
}
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $query .= " AND title LIKE '%$search%'";
}
$query .= " ORDER BY uploaded_at DESC";
$results = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Media Gallery - Conquer High School</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    html, body {
        height: 100%; font-family: Arial, sans-serif; background:#f4f4f4;
        color:#333; min-height:100vh; padding-bottom: 120px; display: flex;
        flex-direction: column; transition: 0.3s;
    }
    body.dark { background:#121212; color:#ddd; }

    .navbar {
        position: sticky; top: 0; background: #002244; color: white;
        padding: 10px 20px; z-index:999; display: flex;
        justify-content: space-between; align-items: center;
    }
    .logo { font-size: 20px; font-weight: bold; }
    .nav-links { display:flex; gap:15px; list-style:none; }
    .nav-links a { color:white; text-decoration:none; font-weight: bold; }
    .nav-links a:hover { color: orange; }
    .hamburger { display: none; cursor:pointer; flex-direction: column; gap:5px; }
    .hamburger div { width:25px; height:3px; background:white; }

    .filters {
        background:#fff; padding:20px; display:flex; justify-content:center;
        align-items:center; flex-wrap:wrap; gap:10px;
        box-shadow:0 2px 5px rgba(0,0,0,0.1);
    }
    .filters input, .filters select, .filters button {
        padding:8px; border:1px solid #ccc; border-radius:5px;
    }
    .filters button {
        background:#004080; color:white; cursor:pointer;
    }
    .filters button:hover { background:#0066cc; }

    .gallery {
        display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
        gap:15px; padding:20px; flex: 1;
    }
    .card {
        background:white; border-radius:10px; overflow:hidden;
        box-shadow:0 2px 6px rgba(0,0,0,0.1); transition:transform 0.3s;
    }
    .card:hover { transform:scale(1.03); }
    .card img, .card video {
        width:100%; height:180px; object-fit:cover;
    }
    .card h4 { text-align:center; padding:10px; font-size:16px; }

    .lightbox {
        display:none; position:fixed; top:0; left:0; width:100%; height:100%;
        background:rgba(0,0,0,0.8); justify-content:center; align-items:center;
        z-index:1000;
    }
    .lightbox-content {
        position:relative; background:white; padding:10px;
        max-width:90%; max-height:90%;
    }
    .lightbox-content img, .lightbox-content video {
        width:100%; max-height:80vh;
    }
    .close-btn {
        position:absolute; top:10px; right:10px; background:red;
        color:white; border:none; padding:5px 10px; cursor:pointer;
    }

    footer {
        background: #002244; color: white; text-align: center;
        padding: 15px;
    }
    footer a { color:white; text-decoration:none; margin: 0 5px; }
    footer a:hover { text-decoration: underline; }

    .bottom-nav {
        position: fixed; bottom: 0; width: 100%; background: #001d3d;
        display: flex; justify-content: space-around; align-items: center;
        padding: 10px; z-index: 999;
    }
    .bottom-nav a {
        color: white; text-decoration: none; font-size: 13px;
        text-align: center;
    }
    .bottom-nav a:hover { color: orange; }
    .bottom-nav a i { display: block; font-size: 18px; margin-bottom: 2px; }

    #topBtn {
        position:fixed; bottom:90px; right:20px; display:none;
        background:#004080; color:white; padding:10px;
        border:none; border-radius:50%; cursor:pointer; z-index:1000;
    }

    @media(max-width:768px){
        .nav-links {
            display:none; position:absolute; top:60px; right:10px;
            background:#004080; padding:10px; border-radius:8px;
            flex-direction:column;
        }
        .nav-links.show { display:flex; }
        .hamburger { display:flex; }
        .filters { flex-direction:column; gap:8px; }
    }
</style>
</head>
<body>
<header class="navbar">
    <div class="logo">🏫 CONQUER HIGH SCHOOL</div>
    <nav>
        <div class="hamburger" onclick="toggleMenu(this)">
            <div></div><div></div><div></div>
        </div>
        <ul class="nav-links" id="navLinks">
            <li><a href="home.php">Home</a></li>
            <li><a href="about.php">About</a></li>
            <li><a href="academics.php">Academics</a></li>
            <li><a href="admission_upload_form.php">Admissions</a></li>
            <li><a href="media_gallery.php">Gallery</a></li>
            <li><a href="public_view_news.php">News</a></li>
            <li><a href="contact.php">Contact</a></li>
            <li><span style="cursor:pointer;" onclick="toggleDark()">🌙</span></li>
        </ul>
    </nav>
</header>

<div class="filters">
    <form method="GET">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search title...">
        <select name="category">
            <option value="all" <?= $category=='all'?'selected':'' ?>>All</option>
            <option value="image" <?= $category=='image'?'selected':'' ?>>Images</option>
            <option value="video" <?= $category=='video'?'selected':'' ?>>Videos</option>
        </select>
        <button type="submit">Filter</button>
    </form>
</div>

<div class="gallery">
    <?php while($row = $results->fetch_assoc()): ?>
        <div class="card" onclick="openLightbox('<?= $row['file_path'] ?>', '<?= $row['file_type'] ?>')">
            <?php if($row['file_type'] == 'image'): ?>
                <img src="<?= $row['file_path'] ?>" alt="<?= htmlspecialchars($row['title']) ?>">
            <?php else: ?>
                <video src="<?= $row['file_path'] ?>"></video>
            <?php endif; ?>
            <h4><?= htmlspecialchars($row['title']) ?></h4>
        </div>
    <?php endwhile; ?>
</div>

<div class="lightbox" id="lightbox" onclick="this.style.display='none'">
    <div class="lightbox-content" onclick="event.stopPropagation()">
        <button class="close-btn" onclick="document.getElementById('lightbox').style.display='none'">Close</button>
        <div id="lightbox-media"></div>
    </div>
</div>

<footer>
  <p>&copy; <?= date("Y") ?> Conquer High School. All rights reserved.</p>
  <p>
    <a href="sitemap_view.php">Sitemap</a> |
    <a href="privacy.php">Privacy Policy</a> |
    <a href="terms.php">Terms of Service</a>
  </p>
</footer>

<div class="bottom-nav">
    <a href="home.php"><i class="fas fa-home"></i><br>Home</a>
    <a href="academics.php"><i class="fas fa-book"></i><br>Academics</a>
    <a href="admission_upload_form.php"><i class="fas fa-user-plus"></i><br>Admissions</a>
    <a href="public_view_news.php"><i class="fas fa-newspaper"></i><br>News</a>
    <a href="contact.php"><i class="fas fa-envelope"></i><br>Contact</a>
</div>

<button onclick="topFunction()" id="topBtn" title="Go to top">↑</button>

<script>
function toggleDark(){document.body.classList.toggle('dark');}
function toggleMenu(el){
    document.getElementById('navLinks').classList.toggle('show');
    el.classList.toggle('active');
}
function openLightbox(path, type){
    const mediaBox = document.getElementById('lightbox-media');
    mediaBox.innerHTML = (type === 'image')
        ? `<img src="${path}" alt="Full Image">`
        : `<video src="${path}" controls autoplay></video>`;
    document.getElementById('lightbox').style.display = 'flex';
}
window.onscroll = function(){
    document.getElementById("topBtn").style.display = (window.scrollY > 200 ? 'block' : 'none');
};
function topFunction(){ window.scrollTo({top:0, behavior:'smooth'}); }
</script>
</body>
</html>
