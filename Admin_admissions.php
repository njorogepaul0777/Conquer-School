<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "school");

// Count summaries
$pendingCount = $conn->query("SELECT COUNT(*) as count FROM students_admitted WHERE status = 0")->fetch_assoc()['count'];
$approvedCount = $conn->query("SELECT COUNT(*) as count FROM students_admitted WHERE status = 1")->fetch_assoc()['count'];
$rejectedCount = $conn->query("SELECT COUNT(*) as count FROM students_admitted WHERE status = -1")->fetch_assoc()['count'];

// Export CSV
if (isset($_GET['export'])) {
    $status = $_GET['export'];
    $map = ['0' => 'Pending', '1' => 'Approved', '-1' => 'Rejected'];
    $res = $conn->query("SELECT * FROM students_admitted WHERE status = $status");

    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=admissions_{$map[$status]}.csv");

    $output = fopen("php://output", "w");
    fputcsv($output, ['Name', 'Gender', 'Contact', 'Email', 'Parent Name', 'Status']);
    while ($row = $res->fetch_assoc()) {
        fputcsv($output, [
            $row['full_name'], $row['gender'], $row['contact'], $row['email'], $row['parent_name'],
            $map[$status]
        ]);
    }
    fclose($output);
    exit();
}

// Approve
if (isset($_GET['approve'])) {
    $id = $_GET['approve'];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $adm = $_POST['admission_no'];
        $class = $_POST['class'];
        $stream = $_POST['stream'];

        $stmt = $conn->prepare("UPDATE students_admitted SET admission_no=?, class=?, stream=?, status=1 WHERE id=?");
        $stmt->bind_param("sssi", $adm, $class, $stream, $id);
        $stmt->execute();
        echo "<p style='color:green'>✅ Student approved. Redirecting...</p>";
        echo "<script>setTimeout(() => window.location.href = 'admin_admissions.php', 2000);</script>";
        exit();
    }
$stu = $conn->query("SELECT * FROM students_admitted WHERE id=$id")->fetch_assoc();

// View
if (isset($_GET['view'])) {
    $id = $_GET['view'];
    $stu = $conn->query("SELECT * FROM students_admitted WHERE id = $id")->fetch_assoc();
    ?>
    <h2>View Application</h2>
    <p><strong>Name:</strong> <?= $stu['full_name'] ?></p>
    <p><strong>Gender:</strong> <?= $stu['gender'] ?> | <strong>DOB:</strong> <?= $stu['date_of_birth'] ?></p>
    <p><strong>Contact:</strong> <?= $stu['contact'] ?> | <strong>Email:</strong> <?= $stu['email'] ?></p>
    <p><strong>Address:</strong> <?= $stu['address'] ?></p>
    <p><strong>Parent:</strong> <?= $stu['parent_name'] ?> (<?= $stu['parent_contact'] ?>)</p>
    <p><strong>KCPE Result:</strong> <a href="<?= $stu['kcpe_result'] ?>" target="_blank">View</a></p>
    <p><strong>KCPE Cert:</strong> <a href="<?= $stu['kcpe_certificate'] ?>" target="_blank">View</a></p>
    <p><strong>Leaving Cert:</strong> <a href="<?= $stu['leaving_certificate'] ?>" target="_blank">View</a></p>
    <?php if (!empty($stu['other_documents'])): ?>
        <p><strong>Other Doc:</strong> <a href="<?= $stu['other_documents'] ?>" target="_blank">View</a></p>
    <?php endif; ?>
    <a href="admin_admissions.php">← Back</a>
    <?php exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Approve Student - Conquer High School</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
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

		
        .approve-form-container {
            max-width: 500px;
            margin: 60px auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #333;
        }
        label {
            font-weight: bold;
            display: block;
            margin: 15px 0 5px;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px 12px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
        }
        .btn-container {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            font-weight: bold;
            border-radius: 6px;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
        }
        .btn-approve {
            background-color: #28a745;
            color: #fff;
        }
        .btn-cancel {
            background-color: #dc3545;
            color: #fff;
        }
        .message {
            text-align: center;
            margin-top: 15px;
            color: green;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="approve-form-container">
    <h2>Approve: <?= htmlspecialchars($stu['full_name']) ?></h2>
    <form method="POST">
        <label for="admission_no">Admission Number</label>
        <input type="text" name="admission_no" id="admission_no" required>

        <label for="class">Class</label>
        <input type="text" name="class" id="class" required>

        <label for="stream">Stream</label>
        <input type="text" name="stream" id="stream" required>

        <div class="btn-container">
            <button type="submit" class="btn btn-approve">✅ Approve</button>
            <a href="admin_admissions.php" class="btn btn-cancel">❌ Cancel</a>
        </div>
    </form>
</div>

</body>
</html>
<?php exit(); ?>

}
<?php
//Reject
if (isset($_GET['reject'])) {
    $id = $_GET['reject'];
    $conn->query("UPDATE students_admitted SET status = -1 WHERE id=$id");
    echo "<p style='color:red'>❌ Student rejected. Redirecting...</p>";
    echo "<script>setTimeout(() => window.location.href = 'admin_admissions.php', 2000);</script>";
    exit();
}

// View
if (isset($_GET['view'])) {
    $id = $_GET['view'];
    $stu = $conn->query("SELECT * FROM students_admitted WHERE id = $id")->fetch_assoc();
    ?>
    <!DOCTYPE html>
    <html>
    <head><title>View Application</title></head>
    <body>
        <h2>View Application</h2>
        <p><strong>Name:</strong> <?= $stu['full_name'] ?></p>
        <p><strong>Gender:</strong> <?= $stu['gender'] ?> | <strong>DOB:</strong> <?= $stu['date_of_birth'] ?></p>
        <p><strong>Contact:</strong> <?= $stu['contact'] ?> | <strong>Email:</strong> <?= $stu['email'] ?></p>
        <p><strong>Address:</strong> <?= $stu['address'] ?></p>
        <p><strong>Parent:</strong> <?= $stu['parent_name'] ?> (<?= $stu['parent_contact'] ?>)</p>
        <p><strong>KCPE Result:</strong> <a href="<?= $stu['kcpe_result'] ?>" target="_blank">View</a></p>
        <p><strong>KCPE Cert:</strong> <a href="<?= $stu['kcpe_certificate'] ?>" target="_blank">View</a></p>
        <p><strong>Leaving Cert:</strong> <a href="<?= $stu['leaving_certificate'] ?>" target="_blank">View</a></p>
        <?php if (!empty($stu['other_documents'])): ?>
            <p><strong>Other Doc:</strong> <a href="<?= $stu['other_documents'] ?>" target="_blank">View</a></p>
        <?php endif; ?>
        <a href="admin_admissions.php">← Back</a>
    </body>
    </html>
    <?php exit();
}
}

// Search
$where = "WHERE status = 0";
$searchTerm = '';
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $searchTerm = $conn->real_escape_string($_GET['search']);
    $where = "WHERE status = 0 AND (
        full_name LIKE '%$searchTerm%' OR
        email LIKE '%$searchTerm%' OR
        contact LIKE '%$searchTerm%' OR
        gender LIKE '%$searchTerm%')";
}
$students = $conn->query("SELECT * FROM students_admitted $where ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Admissions - Conquer High School</title>
	    <link rel="stylesheet" href="admin_style.css">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        <?php include 'admin_style.css'; ?>
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



    <div class="main-content">
        <div class="topbar">
            <h1>🎓 Student Admissions Management</h1>
            <a href="admin_logout.php" class="logout">Logout</a>
        </div>

        <div class="cards">
            <div class="card"><h3>Approved Admissions</h3><p><?= $approvedCount ?></p></div>
            <div class="card"><h3>Pending Admissions</h3><p><?= $pendingCount ?></p></div>
            <div class="card"><h3>Rejected Applications</h3><p><?= $rejectedCount ?></p></div>
        </div>

        <form method="GET" class="search" style="margin-top:20px;">
            <input type="text" name="search" placeholder="Search name, email, contact..." value="<?= htmlspecialchars($searchTerm) ?>">
            <button type="submit">Search</button>
        </form>

        <div style="margin: 15px 0;">
            <a href="?export=1" class="btn export">📥 Export Approved</a>
            <a href="?export=0" class="btn export">📥 Export Pending</a>
            <a href="?export=-1" class="btn export">📥 Export Rejected</a>
        </div>

        <?php if ($students->num_rows > 0): ?>
        <table>
            <tr>
                <th>Name</th>
                <th>Gender</th>
                <th>Contact</th>
                <th>Email</th>
                <th>Parent</th>
                <th>Actions</th>
            </tr>
            <?php while ($row = $students->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['full_name']) ?></td>
                <td><?= $row['gender'] ?></td>
                <td><?= $row['contact'] ?></td>
                <td><?= $row['email'] ?></td>
                <td><?= $row['parent_name'] ?></td>
                <td>
                    <a href="?view=<?= $row['id'] ?>" class="btn view">View</a>
                    <a href="?approve=<?= $row['id'] ?>" class="btn approve">Approve</a>
                    <a href="?reject=<?= $row['id'] ?>" class="btn reject" onclick="return confirm('Reject this application?')">Reject</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php else: ?>
        <p>No applications found.</p>
        <?php endif; ?>
    </div>
</body>
</html>
