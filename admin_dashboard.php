<?php
// Start the session
session_start();

// Redirect to login if admin is not authenticated
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Connect to the database
$conn = new mysqli("localhost", "root", "", "school");

// Fetch data for dashboard summary cards
$pendingCount = $conn->query("SELECT COUNT(*) as count FROM students_admitted WHERE status = 0")->fetch_assoc()['count'];
$approvedCount = $conn->query("SELECT COUNT(*) as count FROM students_admitted WHERE status = 1")->fetch_assoc()['count'];
$contactCount = $conn->query("SELECT COUNT(*) as count FROM contact_messages")->fetch_assoc()['count'];
$galleryCount = $conn->query("SELECT COUNT(*) as count FROM media_gallery")->fetch_assoc()['count'];
$newsCount = $conn->query("SELECT COUNT(*) as count FROM news_events")->fetch_assoc()['count'];
$resultCount = $conn->query("SELECT COUNT(*) as count FROM results")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Conquer High School</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* General Reset & Base Styles */
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f2f4f8;
            display: flex;
            min-height: 100vh;
            color: #333;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 240px;
            background: #002b5c;
            color: white;
            padding: 20px 0;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            overflow-y: auto;
        }

        .sidebar h2 {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            padding-bottom: 10px;
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            font-size: 15px;
        }

        .sidebar a:hover {
            background-color: #014a99;
        }

        .sidebar a.active {
            background-color: #004080;
        }

        /* Dropdown (Details) Styling in Sidebar */
        .sidebar details {
            margin: 5px 10px;
        }

        .sidebar summary {
            font-weight: bold;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 15px;
            color: #fff;
        }

        .sidebar details[open] summary::after {
            content: " ▲";
            float: right;
        }

        .sidebar summary::after {
            content: " ▼";
            float: right;
        }

        .sidebar details a {
            font-size: 14px;
            padding-left: 30px;
            color: #cbd9ff;
        }

        .sidebar details a:hover {
            color: #fff;
            background: #014a99;
        }

        /* Main content layout */
        .main-content {
            margin-left: 240px;
            padding: 25px;
            flex: 1;
        }

        /* Topbar/Header styling */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 18px 25px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }

        .topbar h1 {
            font-size: 24px;
            color: #002b5c;
            margin: 0;
        }

        .logout {
            background: #d9534f;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
        }

        .logout:hover {
            background: #c9302c;
        }

        /* Cards Section */
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.1);
        }

        .card h3 {
            margin: 0 0 12px;
            font-size: 16px;
            color: #007bff;
        }

        .card p {
            font-size: 22px;
            font-weight: bold;
            color: #333;
            margin: 0;
        }

        .card a {
            display: block;
            margin-top: 15px;
            font-size: 14px;
            color: #007bff;
            text-decoration: none;
        }

        .card a:hover {
            color: #004080;
        }

        /* Responsive behavior */
        @media (max-width: 768px) {
            .main-content { margin-left: 0; }
            .sidebar { position: relative; width: 100%; }
        }
    </style>
</head>
<body>

<!-- Sidebar Navigation -->
<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php" class="active">🏠 Dashboard</a>

    <!-- Admissions section -->
    <details>
        <summary>🎓 Admissions</summary>
        <a href="admin_admissions.php">• Manage Admissions</a>
        <a href="admin_contact_student.php">• Student Messages</a>
    </details>

    <!-- Academics section -->
    <details>
        <summary>📚 Academics</summary>
        <a href="admin_manage_results.php">• Manage Results</a>
        <a href="admin_subjects.php">• Subjects</a>
        <a href="admin_upload_syllabus.php">• Upload Syllabus</a>
        <a href="admin_upload_teacher.php">• Teachers</a>
    </details>

    <!-- Media section -->
    <details>
        <summary>🖼️ Media & News</summary>
        <a href="admin_upload_media.php">• Media Gallery</a>
        <a href="upload_news.php">• News & Events</a>
    </details>

    <!-- Communication section -->
    <details>
        <summary>👥 Interaction</summary>
        <a href="admin_contact.php">• Contact Messages</a>
        <a href="admin_faqs.php">• FAQs</a>
        <a href="admin_manage_testimonials.php">• Testimonials</a>
        <a href="subscribers.php">• Subscribers</a>
    </details>

    <!-- Staff management -->
    <a href="admin_manage_staffs.php">👩‍🏫 Manage Staff</a>
    <a href="admin_logout.php">🚪 Logout</a>
</div>

<!-- Main Dashboard Content -->
<div class="main-content">
    <!-- Top bar -->
    <div class="topbar">
        <h1>Welcome, <?= htmlspecialchars($_SESSION['admin_username']) ?></h1>
        <a href="admin_logout.php" class="logout">Logout</a>
    </div>

    <!-- Welcome banner -->
    <div class="card" style="margin-top: 25px; background: #eaf4ff; border-left: 6px solid #007bff;">
        <h3>👋 Hello <?= htmlspecialchars($_SESSION['admin_username']) ?>,</h3>
        <p>Welcome back to the Admin Dashboard of <strong>Conquer High School</strong>. Use the sidebar to manage admissions, academics, staff, and media. Keep everything organized and up to date.</p>
    </div>

    <!-- Summary cards -->
    <div class="cards">
        <div class="card">
            <h3>📌 Total Students</h3>
            <p><?= $approvedCount ?></p>
            <a href="report_students.php">View Detailed Report</a>
        </div>

        <div class="card">
            <h3>📬 Contact Messages</h3>
            <p><?= $contactCount ?></p>
            <a href="report_contact_messages.php">View Detailed Report</a>
        </div>

        <div class="card">
            <h3>📝 Results Uploaded</h3>
            <p><?= $resultCount ?></p>
            <a href="report_results.php">View Detailed Report</a>
        </div>

        <div class="card">
            <h3>🎓 Pending Admissions</h3>
            <p><?= $pendingCount ?></p>
            <a href="report_admissions.php">View Detailed Report</a>
        </div>

        <div class="card">
            <h3>📰 News Articles</h3>
            <p><?= $newsCount ?></p>
            <a href="report_news_articles.php">View Detailed Report</a>
        </div>

        <div class="card">
            <h3>📷 Media Files</h3>
            <p><?= $galleryCount ?></p>
            <a href="report_media_gallery.php">View Detailed Report</a>
        </div>

        <div class="card">
            <h3>💰 Fees Report</h3>
            <p>[Summary]</p>
            <a href="fees_report.php">View Detailed Report</a>
        </div>
    </div>
</div>

</body>
</html>
