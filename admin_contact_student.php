<?php
// Connect to database
$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Handle deletion of student message
if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    $conn->query("DELETE FROM student_messages WHERE id = $deleteId");
    header("Location: admin_contact_student.php");
    exit();
}

// Handle type filtering (Suggestion, Complaint, Compliment)
$typeFilter = isset($_GET['type']) ? $_GET['type'] : '';
$where = $typeFilter ? "WHERE type = '$typeFilter'" : '';
$messages = $conn->query("SELECT * FROM student_messages $where ORDER BY created_at DESC");

// Count total messages of each type
$counts = [
    'Suggestions' => $conn->query("SELECT COUNT(*) as c FROM student_messages WHERE type = 'Suggestion'")->fetch_assoc()['c'],
    'Complaints' => $conn->query("SELECT COUNT(*) as c FROM student_messages WHERE type = 'Complaint'")->fetch_assoc()['c'],
    'Compliments' => $conn->query("SELECT COUNT(*) as c FROM student_messages WHERE type = 'Compliment'")->fetch_assoc()['c']
];
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin - Student Messages</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- External admin CSS -->
    <link rel="stylesheet" href="admin_style.css">
   <style>
/* Reset & base */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f2f4f8;
    color: #333;
    display: flex;
    min-height: 100vh;
}

/* Sidebar */
.sidebar {
    width: 240px;
    background: #002b5c;
    color: white;
    padding: 20px 0;
    flex-shrink: 0;
    overflow-y: auto;
}

.sidebar h2 {
    text-align: center;
    font-size: 22px;
    font-weight: bold;
    margin-bottom: 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    padding-bottom: 10px;
}

.sidebar a {
    display: block;
    color: white;
    text-decoration: none;
    padding: 10px 20px;
    font-size: 15px;
    transition: background 0.2s;
}

.sidebar a:hover {
    background-color: #014a99;
}

.sidebar a.active {
    background-color: #004080;
}

/* Dropdown (details) Styling */
.sidebar details {
    margin: 5px 10px;
}

.sidebar summary {
    font-weight: bold;
    padding: 10px 20px;
    cursor: pointer;
    font-size: 15px;
    color: #fff;
    list-style: none;
}

.sidebar summary::marker {
    display: none;
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

/* Main content */
.main {
    flex: 1;
    padding: 20px;
}

/* Topbar */
.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
    padding: 10px 20px;
    border-bottom: 1px solid #ddd;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    margin-bottom: 20px;
}

.topbar h1 {
    font-size: 20px;
    margin: 0;
    color: #004080;
}

.topbar .logout {
    background: #dc3545;
    color: #fff;
    padding: 8px 12px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: bold;
}

.topbar .logout:hover {
    background: #b52a37;
}

/* Summary cards */
.summary {
    display: flex;
    gap: 20px;
    margin: 20px 0;
}

.card {
    background: white;
    padding: 15px;
    border-radius: 10px;
    flex: 1;
    text-align: center;
    box-shadow: 0 0 5px #aaa;
    font-size: 16px;
}

/* Filter form */
.filter {
    margin: 15px 0;
}

/* Table */
table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    box-shadow: 0 0 5px #ccc;
    border-radius: 5px;
    overflow: hidden;
}

th, td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: left;
    vertical-align: top;
}

th {
    background: #004080;
    color: white;
    text-transform: uppercase;
    font-size: 14px;
}

td {
    background: white;
}

.message {
    white-space: pre-wrap;
}

.btn-delete {
    color: red;
    font-weight: bold;
    text-decoration: none;
}

.btn-delete:hover {
    text-decoration: underline;
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



<!-- Main Content -->
<div class="main">
    <div class="topbar">
        <h1>🎓 Student Messages & Feedback</h1>
        <a href="admin_logout.php" class="logout">Logout</a>
    </div>

    <!-- Summary Cards -->
    <div class="summary">
        <div class="card">Suggestions: <strong><?= $counts['Suggestions'] ?></strong></div>
        <div class="card">Complaints: <strong><?= $counts['Complaints'] ?></strong></div>
        <div class="card">Compliments: <strong><?= $counts['Compliments'] ?></strong></div>
    </div>

    <!-- Filter Form -->
    <div class="filter">
        <form method="GET">
            <label>Filter by Type:</label>
            <select name="type" onchange="this.form.submit()">
                <option value="">-- All --</option>
                <option value="Suggestion" <?= $typeFilter == 'Suggestion' ? 'selected' : '' ?>>Suggestion</option>
                <option value="Complaint" <?= $typeFilter == 'Complaint' ? 'selected' : '' ?>>Complaint</option>
                <option value="Compliment" <?= $typeFilter == 'Compliment' ? 'selected' : '' ?>>Compliment</option>
            </select>
        </form>
    </div>

    <!-- Messages Table -->
    <table>
        <thead>
            <tr>
                <th>Student</th>
                <th>Type</th>
                <th>Subject</th>
                <th>Message</th>
                <th>Sent On</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($messages->num_rows > 0): ?>
            <?php while ($row = $messages->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['admission_no']) ?></td>
                <td><?= htmlspecialchars($row['type']) ?></td>
                <td><?= htmlspecialchars($row['subject']) ?></td>
                <td class="message"><?= nl2br(htmlspecialchars($row['message'])) ?></td>
                <td><?= $row['created_at'] ?></td>
                <td>
                    <a class="btn-delete" href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this message?')">🗑️ Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6">No messages found for selected type.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
