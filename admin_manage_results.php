<?php
// Start the session
session_start();

// Redirect to login if admin is not logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// Connect to the database
$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Get filter values from GET request
$class        = $_GET['class'] ?? '';
$stream       = $_GET['stream'] ?? '';
$term         = $_GET['term'] ?? '';
$year         = $_GET['year'] ?? '';
$admission_no = $_GET['admission_no'] ?? '';

// Build base query for results
$query = "
    SELECT DISTINCT
        r.admission_no, r.term, r.year,
        s.full_name, s.class, s.stream
    FROM results r
    JOIN students_admitted s ON r.admission_no = s.admission_no
    WHERE 1 = 1
";

// Apply filters
if ($class !== '')         $query .= " AND s.class = '" . $conn->real_escape_string($class) . "'";
if ($stream !== '')        $query .= " AND s.stream = '" . $conn->real_escape_string($stream) . "'";
if ($term !== '')          $query .= " AND r.term = " . intval($term);
if ($year !== '')          $query .= " AND r.year = " . intval($year);
if ($admission_no !== '')  $query .= " AND r.admission_no = '" . $conn->real_escape_string($admission_no) . "'";

// Order results
$query .= " ORDER BY r.year DESC, r.term DESC, s.class ASC";

// Execute query
$results = $conn->query($query);

// Dropdown options
$classOptions  = $conn->query("SELECT DISTINCT class FROM students_admitted ORDER BY class ASC");
$streamOptions = $conn->query("SELECT DISTINCT stream FROM students_admitted ORDER BY stream ASC");
$termOptions   = $conn->query("SELECT DISTINCT term FROM results ORDER BY term ASC");
$yearOptions   = $conn->query("SELECT DISTINCT year FROM results ORDER BY year DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Student Results - Admin Panel</title>
    <style>
        /* ===== Base Layout ===== */
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            display: flex;
            min-height: 100vh;
        }

        /* ===== Sidebar ===== */
        .sidebar {
            width: 240px;
            background-color: #002244;
            color: white;
            padding: 20px;
            flex-shrink: 0;
        }
        .sidebar h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .sidebar a, .sidebar summary {
            display: block;
            color: white;
            padding: 10px;
            text-decoration: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .sidebar a:hover, .sidebar summary:hover {
            background-color: #004080;
        }
        details {
            margin-bottom: 10px;
        }
        details a {
            padding-left: 20px;
            font-size: 14px;
        }

        /* ===== Rotating Arrows for <details> ===== */
        details summary {
            position: relative;
            padding-right: 35px;
            list-style: none;
            cursor: pointer;
        }
        details summary::-webkit-details-marker {
            display: none;
        }
        details summary::marker {
            content: "";
        }
        details summary::after {
            content: "▼";
            position: absolute;
            right: 15px;
            font-size: 12px;
            transition: transform 0.2s ease;
        }
        details[open] summary::after {
            transform: rotate(180deg);
        }

        /* ===== Main Content ===== */
        .main-content {
            flex: 1;
            padding: 20px;
        }
        h1 {
            color: #003366;
            text-align: center;
            margin-bottom: 20px;
        }

        /* ===== Buttons ===== */
        .btn {
            background-color: #0066cc;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            background-color: #004080;
        }

        /* ===== Table ===== */
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 6px;
            overflow: hidden;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }
        th {
            background-color: #003366;
            color: white;
        }
        tr:hover td {
            background: #f1f1f1;
        }

        /* ===== Filter Modal Overlay ===== */
        #filterOverlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            justify-content: center;
            align-items: flex-start;
            padding-top: 60px;
        }
        .filter-modal {
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 320px;
            box-shadow: 0 0 15px rgba(0,0,0,0.3);
        }
        .filter-form {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .filter-form select,
        .filter-form input,
        .filter-form button {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
            font-size: 14px;
        }
        .filter-form button[type="submit"] {
            background-color: #003366;
            color: white;
            cursor: pointer;
        }
        .filter-form button[type="submit"]:hover {
            background-color: #004080;
        }
    </style>
</head>
<body>

<!-- ===== Sidebar Navigation ===== -->
<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php">🏠 Dashboard</a>

    <details>
        <summary>🎓 Admissions</summary>
        <a href="admin_admissions.php">• Manage Admissions</a>
        <a href="admin_contact_student.php">• Student Messages</a>
    </details>

    <details>
        <summary>📚 Academics</summary>
        <a href="admin_manage_results.php">• Manage Results</a>
        <a href="admin_subjects.php">• Subjects</a>
        <a href="admin_upload_syllabus.php">• Upload Syllabus</a>
        <a href="admin_upload_teacher.php">• Teachers</a>
    </details>

    <details>
        <summary>🖼️ Media & News</summary>
        <a href="admin_upload_media.php">• Media Gallery</a>
        <a href="upload_news.php">• News & Events</a>
    </details>

    <details>
        <summary>👥 Interaction</summary>
        <a href="admin_contact.php">• Contact Messages</a>
        <a href="admin_faqs.php">• FAQs</a>
        <a href="admin_manage_testimonials.php">• Testimonials</a>
        <a href="subscribers.php">• Subscribers</a>
    </details>

    <a href="admin_manage_staffs.php">👩‍🏫 Manage Staff</a>
    <a href="admin_logout.php">🚪 Logout</a>
</div>

<!-- ===== Main Content ===== -->
<div class="main-content">
    <h1>Manage Student Results</h1>

    <!-- Filter Button -->
    <button id="toggleFilterBtn" class="btn" style="margin-bottom: 20px;">🔍 Filter Results</button>

    <!-- Filter Modal -->
    <div id="filterOverlay">
        <div class="filter-modal">
            <form method="GET" class="filter-form">
                <h3>Filter Results</h3>

                <!-- Class dropdown -->
                <select name="class">
                    <option value="">-- Class --</option>
                    <?php while ($row = $classOptions->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($row['class']) ?>" <?= $class == $row['class'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['class']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <!-- Stream dropdown -->
                <select name="stream">
                    <option value="">-- Stream --</option>
                    <?php while ($row = $streamOptions->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($row['stream']) ?>" <?= $stream == $row['stream'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['stream']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <!-- Term dropdown -->
                <select name="term">
                    <option value="">-- Term --</option>
                    <?php while ($row = $termOptions->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($row['term']) ?>" <?= $term == $row['term'] ? 'selected' : '' ?>>
                            Term <?= htmlspecialchars($row['term']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <!-- Year dropdown -->
                <select name="year">
                    <option value="">-- Year --</option>
                    <?php while ($row = $yearOptions->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($row['year']) ?>" <?= $year == $row['year'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['year']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <!-- Admission Number input -->
                <input type="text" name="admission_no" placeholder="Admission No" value="<?= htmlspecialchars($admission_no) ?>">

                <!-- Buttons -->
                <div style="display: flex; justify-content: space-between;">
                    <button type="submit">Apply Filter</button>
                    <button type="button" id="closeFilterBtn" class="btn" style="background: gray;">Close</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Table -->
    <table>
        <thead>
            <tr>
                <th>Admission No</th>
                <th>Full Name</th>
                <th>Class</th>
                <th>Stream</th>
                <th>Term</th>
                <th>Year</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($results && $results->num_rows > 0): ?>
                <?php while ($row = $results->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['admission_no']) ?></td>
                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                        <td><?= htmlspecialchars($row['class']) ?></td>
                        <td><?= htmlspecialchars($row['stream']) ?></td>
                        <td><?= htmlspecialchars($row['term']) ?></td>
                        <td><?= htmlspecialchars($row['year']) ?></td>
                        <td>
                            <a href="admin_view_results.php?admission_no=<?= urlencode($row['admission_no']) ?>&term=<?= $row['term'] ?>&year=<?= $row['year'] ?>" class="btn">
                                View Result Slip
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7">No results found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Download full class results if all filters are applied -->
    <?php if ($class && $stream && $term && $year): ?>
        <a href="download_results.php?class=<?= urlencode($class) ?>&stream=<?= urlencode($stream) ?>&term=<?= $term ?>&year=<?= $year ?>" class="btn" target="_blank">
            View & Print Full Class Results
        </a>
    <?php endif; ?>
</div>

<!-- ===== JavaScript for modal toggle ===== -->
<script>
    const overlay = document.getElementById('filterOverlay');
    const toggleBtn = document.getElementById('toggleFilterBtn');
    const closeBtn = document.getElementById('closeFilterBtn');

    toggleBtn.onclick = () => overlay.style.display = 'flex';
    closeBtn.onclick = () => overlay.style.display = 'none';
    window.onclick = e => { if (e.target === overlay) overlay.style.display = 'none'; };
</script>

</body>
</html>

<?php
$conn->close();
?>
