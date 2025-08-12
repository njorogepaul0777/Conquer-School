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
  * { box-sizing: border-box; margin: 0; padding: 0; }
  html, body {
    font-family: Arial, sans-serif;
    background: #f4f4f4;
    color: #333;
    min-height: 100vh;
  }

  .sidebar {
    width: 220px;
    background: #002244;
    color: white;
    position: fixed;
    top: 0; bottom: 0; left: 0;
    padding-top: 20px;
    overflow-y: auto;
    transition: width 0.3s;
  }

  .sidebar.collapsed {
    width: 70px;
  }

  .sidebar h3 {
    text-align: center;
    margin-bottom: 30px;
    font-size: 20px;
    transition: opacity 0.3s;
  }

  .sidebar.collapsed h3 {
    opacity: 0;
  }

  .sidebar a {
    display: block;
    color: white;
    padding: 12px 20px;
    text-decoration: none;
    transition: background 0.3s, padding 0.3s;
    white-space: nowrap;
  }

  .sidebar.collapsed a {
    padding-left: 10px;
    text-align: center;
  }

  .sidebar a:hover {
    background: #004080;
  }

  .collapse-toggle {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #004080;
    color: white;
    border: none;
    border-radius: 4px;
    padding: 4px 8px;
    cursor: pointer;
  }

  .container {
    margin-left: 240px;
    padding: 30px;
    transition: margin-left 0.3s;
  }

  .sidebar.collapsed ~ .container {
    margin-left: 70px;
  }

  h2 {
    color: #004080;
    margin-bottom: 10px;
  }

  p.subtitle {
    color: #007bff;
    margin-bottom: 20px;
  }

  .news-card {
    background: white;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  }

  .news-card h3 {
    margin: 0 0 8px;
    color: #003366;
  }

  .news-card small {
    color: #777;
    display: block;
    margin-bottom: 10px;
  }

  .news-card p {
    max-height: 60px;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .read-more-btn {
    background: #003366;
    color: white;
    padding: 8px 12px;
    border: none;
    border-radius: 5px;
    margin-top: 10px;
    cursor: pointer;
  }

  .modal {
    display: none;
    position: fixed;
    z-index: 999;
    top: 0; left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    justify-content: center;
    align-items: center;
  }

  .modal-content {
    background: white;
    padding: 20px;
    border-radius: 10px;
    max-width: 600px;
    width: 90%;
    max-height: 80%;
    overflow-y: auto;
    position: relative;
  }

  .close-btn {
    position: absolute;
    top: 10px; right: 10px;
    background: red;
    color: white;
    border: none;
    border-radius: 50%;
    width: 30px; height: 30px;
    font-weight: bold;
    cursor: pointer;
  }

  .dark {
    background: #121212;
    color: #ddd;
  }

  .dark .news-card {
    background: #1e1e1e;
    color: #ddd;
  }

  @media (max-width: 768px) {
    .sidebar {
      position: relative;
      width: 100%;
      height: auto;
    }

    .container {
      margin-left: 0;
      padding: 20px;
    }
  }
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
  <button class="collapse-toggle" onclick="toggleSidebar()" id="collapseBtn">«</button>
  <h3><?= htmlspecialchars($teacher_name) ?></h3>
  <a href="teacher_dashboard.php">🏠 Dashboard</a>
  <a href="teacher_update_profile.php">👤</a>
  <a href="upload_result.php">📤</a>
  <a href="admin_manage_results.php">📄</a>
  <a href="#">✉️</a>
  <a href="announcement.php">📢</a>
  <a href="#">📚</a>
  <a href="#">📅</a>
  <a href="view_attendance.php">📝</a>
  <a href="teacher_logout.php">🚪</a>
</div>

<!-- Main content -->
<div class="container">
  <h2>Latest News & Events</h2>
  <p class="subtitle">Stay updated with the latest happenings at Conquer High School</p>

  <?php if ($news->num_rows > 0): ?>
    <?php while($row = $news->fetch_assoc()): ?>
      <div class="news-card">
        <h3><?= htmlspecialchars($row['title']) ?></h3>
        <small>Posted on <?= date("F j, Y", strtotime($row['created_at'])) ?></small>
        <p><?= nl2br(htmlspecialchars(substr($row['content'], 0, 150))) ?>...</p>
        <button class="read-more-btn"
                data-title="<?= htmlspecialchars($row['title']) ?>"
                data-content="<?= htmlspecialchars(nl2br($row['content'])) ?>">
          Read More
        </button>
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

<script>
  const modal = document.getElementById("newsModal");
  const modalTitle = document.getElementById("modalTitle");
  const modalContent = document.getElementById("modalContent");

  document.querySelectorAll(".read-more-btn").forEach(btn => {
    btn.addEventListener("click", function () {
      modalTitle.textContent = this.getAttribute("data-title");
      modalContent.innerHTML = this.getAttribute("data-content");
      modal.style.display = "flex";
    });
  });

  function closeModal() {
    modal.style.display = "none";
  }

  window.onclick = function (event) {
    if (event.target === modal) {
      closeModal();
    }
  }

  function toggleSidebar() {
    const sidebar = document.getElementById("sidebar");
    sidebar.classList.toggle("collapsed");
    const isCollapsed = sidebar.classList.contains("collapsed");
    document.getElementById("collapseBtn").textContent = isCollapsed ? "»" : "«";
    localStorage.setItem("sidebarCollapsed", isCollapsed);
  }

  window.onload = function () {
    const collapsed = localStorage.getItem("sidebarCollapsed") === "true";
    const sidebar = document.getElementById("sidebar");
    if (collapsed) {
      sidebar.classList.add("collapsed");
      document.getElementById("collapseBtn").textContent = "»";
    }
  };
</script>

</body>
</html>
