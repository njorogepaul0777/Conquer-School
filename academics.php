<?php
$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$curriculum = isset($_GET['curriculum']) ? $_GET['curriculum'] : 'All';
$subjectQuery = "SELECT * FROM subject";
if ($curriculum != 'All') {
    $subjectQuery .= " WHERE curriculum = '".$conn->real_escape_string($curriculum)."'";
}
$subjectResult = $conn->query($subjectQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Academics - Conquer High School</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Explore the academic programs, subjects, and teachers of Conquer High School.">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    *{margin:0;padding:0;box-sizing:border-box;}
    html, body {font-family: Arial, sans-serif; background: #f4f4f4; color:#333; min-height: 100vh; display: flex; flex-direction: column;}
    body.dark { background:#121212; color:#ddd; }

    .navbar {
        position: sticky; top: 0; background: #004080; color: #fff;
        padding: 10px 20px; z-index:999; display: flex;
        justify-content: space-between; align-items: center;
    }
    .logo { font-size: 20px; font-weight: bold; }
    .nav-links { display:flex; gap:15px; list-style:none; }
    .nav-links a { color:white; text-decoration:none; font-weight: bold; }
    .nav-links a:hover { color: orange; }
    .hamburger { display: none; cursor:pointer; flex-direction: column; gap:5px; }
    .hamburger div { width:25px; height:3px; background:white; }

    h1, h2, h3 { text-align: center; color: #004080; }
    body.dark h1, body.dark h2, body.dark h3 { color: orange; }
    p { max-width: 800px; margin: 10px auto; text-align: center; line-height: 1.6; }

    .tabs { text-align: center; margin: 20px 0; }
    .tabs a {
        padding: 10px 20px; margin: 0 5px; background: #004080;
        color: #fff; border-radius: 5px; text-decoration: none;
    }
    .tabs a.active { background: #ff6600; }

    .subject-card {
        background: #fff; padding: 15px; margin: 20px auto;
        max-width: 700px; box-shadow: 0 0 8px #ccc; border-radius: 8px;
    }
    body.dark .subject-card { background: #1e1e1e; box-shadow:0 0 5px #555; }
    .subject-card h3 { margin-top: 0; }
    .btn {
        display: inline-block; padding: 8px 15px; background: #003366;
        color: white; text-decoration: none; border-radius: 4px; margin-top: 10px;
    }
    .btn:hover { background: #0055aa; }

    .modal {
        display: none; position: fixed; top: 0; left: 0;
        width: 100%; height: 100%; background-color: rgba(0,0,0,0.6);
        justify-content: center; align-items: center; z-index: 1000;
    }
    .modal-content {
        background: white; padding: 20px; border-radius: 8px;
        max-width: 500px; width: 90%; position: relative;
    }
    .modal-content img { max-width: 100%; margin-top: 10px; }
    .close {
        position: absolute; top: 10px; right: 15px; font-size: 22px;
        cursor: pointer; color: #900;
    }
    body.dark .modal-content { background:#1e1e1e; color: white; }

    .info-section {
        background: #fff; padding: 20px; max-width: 900px;
        margin: 20px auto; border-radius: 8px; box-shadow: 0 0 8px #ccc;
    }
    body.dark .info-section { background:#1e1e1e; }

    footer {
        background: #002244; color: white; text-align: center;
        padding: 15px;
    }
    footer a { color:white; text-decoration:none; margin: 0 5px; }
    footer a:hover { text-decoration: underline; }

    .bottom-nav {
        display: flex; justify-content: space-around; align-items: center;
        background: #001d3d; color: white; padding: 10px;
        position: fixed; bottom: 0; left: 0; width: 100%;
        z-index: 999;
    }
    .bottom-nav a {
        color: white; text-decoration: none; text-align: center;
        font-size: 13px;
    }
    .bottom-nav a:hover { color: orange; }

    #topBtn {
        position: fixed; bottom: 70px; right: 20px; display: none;
        background:#004080; color:white; padding:10px 15px;
        border:none; border-radius:50%; cursor:pointer; font-size:16px;
        z-index: 1000;
    }

    @media(max-width:768px){
        .nav-links {
            display:none; position:absolute; top:60px; right:10px;
            background:#004080; padding:10px; border-radius:8px;
            flex-direction:column;
        }
        .nav-links.show { display:flex; }
        .hamburger { display:flex; }
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
            <li><a href="index.php">Home</a></li>
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

<h1>Academics at Conquer High School</h1>

<div class="info-section">
    <h2>Our Academic Philosophy</h2>
    <p>We deliver holistic education, nurturing creativity, critical thinking, and competence for real-world problem-solving in both CBC and 8-4-4 systems.</p>
    <h2>Understanding CBC vs 8-4-4</h2>
    <p><strong>CBC:</strong> Competence-focused, project-based, skill-driven learning approach.</p>
    <p><strong>8-4-4:</strong> Subject-based, examination-focused, knowledge-driven learning approach.</p>
</div>

<div class="info-section">
    <h2>Academic Excellence Awards</h2>
    <p>Top students receive awards in Science, Math, Languages, and Leadership annually to motivate and recognize excellence.</p>
</div>

<div class="info-section">
    <h2>How We Teach</h2>
    <ul style="max-width:700px;margin:auto;list-style:circle;">
        <li>Blended Learning (E-learning + Classroom)</li>
        <li>Lab Sessions & Practical Experiments</li>
        <li>Group Projects & Peer Reviews</li>
        <li>Continuous Assessments & Personalized Feedback</li>
    </ul>
</div>

<div class="tabs">
    <a href="academics.php?curriculum=All" class="<?= ($curriculum == 'All') ? 'active' : '' ?>">All</a>
    <a href="academics.php?curriculum=8-4-4" class="<?= ($curriculum == '8-4-4') ? 'active' : '' ?>">8-4-4</a>
    <a href="academics.php?curriculum=CBC" class="<?= ($curriculum == 'CBC') ? 'active' : '' ?>">CBC</a>
</div>

<?php if ($subjectResult->num_rows > 0): ?>
    <?php while($subject = $subjectResult->fetch_assoc()): ?>
        <div class="subject-card">
            <h3><?= htmlspecialchars($subject['name']) ?> (<?= htmlspecialchars($subject['curriculum']) ?>)</h3>
            <?php
            $sid = $subject['id'];
            $syllabusRes = $conn->query("SELECT * FROM syllabus_files WHERE subject_id = $sid");
            if ($syllabusRes && $syllabusRes->num_rows > 0):
                $syllabus = $syllabusRes->fetch_assoc();
            ?>
                <p><strong>Syllabus:</strong> <a class="btn" href="uploads/<?= htmlspecialchars($syllabus['file_path']) ?>" target="_blank">📥 Download</a></p>
            <?php else: ?>
                <p><em>No syllabus uploaded yet.</em></p>
            <?php endif; ?>

            <?php
            $teacherRes = $conn->query("SELECT * FROM teachers WHERE subject_id = $sid");
            if ($teacherRes && $teacherRes->num_rows > 0):
                $teacher = $teacherRes->fetch_assoc();
            ?>
                <p><strong>Teacher:</strong> <button class="btn" onclick="openProfileModal(<?= $teacher['id'] ?>)">👨‍🏫 View Profile</button></p>

                <div class="modal" id="modal-<?= $teacher['id'] ?>">
                    <div class="modal-content">
                        <span class="close" onclick="closeProfileModal(<?= $teacher['id'] ?>)">&times;</span>
                        <h3><?= htmlspecialchars($teacher['full_name']) ?></h3>
                        <p><strong>Email:</strong> <?= htmlspecialchars($teacher['email']) ?></p>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($teacher['phone']) ?></p>
                        <p><strong>Bio:</strong> <?= nl2br(htmlspecialchars($teacher['bio'])) ?></p>
                        <?php if (!empty($teacher['profile_photo']) && file_exists($teacher['profile_photo'])): ?>
                            <img src="<?= $teacher['profile_photo'] ?>" alt="Profile Photo" style="width:150px;border-radius:8px;margin-top:10px;">
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <p><em>No teacher assigned yet.</em></p>
            <?php endif; ?>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <p style="text-align:center;">No subjects found for this curriculum.</p>
<?php endif; ?>

<footer>
    &copy; <?= date('Y') ?> Conquer High School. All rights reserved.
    <br>
    <a href="sitemap_view.php">Sitemap</a> | <a href="privacy.php">Privacy Policy</a>
</footer>

<div class="bottom-nav">
    <a href="index.php"><i class="fas fa-home"></i><br>Home</a>
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
window.onscroll = function(){scrollFunction()};
function scrollFunction(){
    document.getElementById("topBtn").style.display = (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) ? "block" : "none";
}
function topFunction(){
    document.body.scrollTop = 0;
    document.documentElement.scrollTop = 0;
}
function openProfileModal(id) {
    document.getElementById('modal-' + id).style.display = 'flex';
}
function closeProfileModal(id) {
    document.getElementById('modal-' + id).style.display = 'none';
}
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>
</body>
</html>

