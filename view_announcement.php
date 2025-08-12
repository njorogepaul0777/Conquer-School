<?php
session_start();
if (!isset($_SESSION['teacher_logged_in'])) {
    header("Location: teacher_login.php");
    exit();
}

$teacher_name = $_SESSION['teacher_name'];

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
    .header{
		color: white;
		background-color: #002244;
		width: 100%;
		display: block;
		position: relative;
		text-align:center; padding:20px;
		
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
        .container {
            margin-left: 240px;
            padding: 30px;
        }
		.container {
            margin-left: 240px;
            padding: 30px;
        }
        .container h2,p {
            color: #007bff;
        }
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

<!-- Header -->
<div class="container">


    <h2>Latest News & Events</h2>
    <p>Stay updated with the latest happenings at Conquer High School</p>

<!-- News Cards Container -->
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
