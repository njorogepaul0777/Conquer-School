<?php
session_start();
if (!isset($_SESSION['teacher_logged_in'])) {
    header("Location: teacher_login.php");
    exit();
}

$teacher_name = $_SESSION['teacher_name'];

$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function getGrade($subject, $total) {
    $sciences = ['Math', 'Physics', 'Chemistry', 'Biology'];
    if (in_array($subject, $sciences)) {
        if ($total >= 75) return 'A';
        elseif ($total >= 70) return 'A-';
        elseif ($total >= 65) return 'B+';
        elseif ($total >= 60) return 'B';
        elseif ($total >= 55) return 'B-';
        elseif ($total >= 50) return 'C+';
        elseif ($total >= 45) return 'C';
        elseif ($total >= 40) return 'C-';
        elseif ($total >= 35) return 'D+';
        elseif ($total >= 30) return 'D';
        elseif ($total >= 25) return 'D-';
        else return 'E';
    } else {
        if ($total >= 90) return 'A';
        elseif ($total >= 80) return 'A-';
        elseif ($total >= 70) return 'B+';
        elseif ($total >= 65) return 'B';
        elseif ($total >= 60) return 'B-';
        elseif ($total >= 55) return 'C+';
        elseif ($total >= 50) return 'C';
        elseif ($total >= 45) return 'C-';
        elseif ($total >= 40) return 'D+';
        elseif ($total >= 35) return 'D';
        elseif ($total >= 30) return 'D-';
        else return 'E';
    }
}

function calculateTotal($cat, $exam) {
    $cat_scaled = ($cat !== null && $cat !== '') ? ($cat / 50) * 30 : 0;
    $exam_scaled = ($exam !== null && $exam !== '') ? ($exam / 100) * 70 : 0;
    return $cat_scaled + $exam_scaled;
}

$subjects = [];
$result = $conn->query("SELECT name FROM subject WHERE curriculum = '8-4-4' ORDER BY name ASC");
while ($row = $result->fetch_assoc()) {
    $subjects[] = $row['name'];
}

$students = [];
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['load'])) {
    $class = $_POST['class'];
    $stream = $_POST['stream'];
    $term = $_POST['term'];
    $year = $_POST['year'];

    $stmt = $conn->prepare("SELECT admission_no, full_name FROM students_admitted WHERE class = ? AND stream = ? ORDER BY LENGTH(admission_no), admission_no");
    $stmt->bind_param("ss", $class, $stream);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_results'])) {
    $term = $_POST['term'];
    $year = $_POST['year'];
    $class = $_POST['class'];
    $stream = $_POST['stream'];
    $uploaded_by = 'teacher';
    $marks = $_POST['marks'];

    foreach ($marks as $adm => $subjects_data) {
        foreach ($subjects_data as $sub => $scores) {
            $cat = isset($scores['cat']) && $scores['cat'] !== '' ? floatval($scores['cat']) : null;
            $exam = isset($scores['exam']) && $scores['exam'] !== '' ? floatval($scores['exam']) : null;

            if ($cat !== null || $exam !== null) {
                $total = calculateTotal($cat, $exam);
                $grade = getGrade($sub, $total);

                $check = $conn->prepare("SELECT id FROM results WHERE admission_no = ? AND subject = ? AND term = ? AND year = ?");
                $check->bind_param("sssi", $adm, $sub, $term, $year);
                $check->execute();
                $res = $check->get_result();

                if ($res->num_rows > 0) {
                    $update = $conn->prepare("UPDATE results SET cat_marks = ?, exam_marks = ?, total_marks = ?, grade = ?, uploaded_by = ? WHERE admission_no = ? AND subject = ? AND term = ? AND year = ?");
                    $update->bind_param("dddsssssi", $cat, $exam, $total, $grade, $uploaded_by, $adm, $sub, $term, $year);
                    $update->execute();
                } else {
                    $insert = $conn->prepare("INSERT INTO results (admission_no, subject, cat_marks, exam_marks, total_marks, grade, term, year, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $insert->bind_param("ssdddssis", $adm, $sub, $cat, $exam, $total, $grade, $term, $year, $uploaded_by);
                    $insert->execute();
                }
            }
        }
    }

    $message = "<p style='color:green;'>✅ Results uploaded/updated successfully!</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher - Upload 8-4-4 Results</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            background: #f0f4f8;
            min-height: 100vh;
        }
        .sidebar {
            width: 240px;
            background: #003366;
            color: white;
            padding-top: 20px;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            overflow-y: auto;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 22px;
            font-weight: bold;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            transition: background 0.2s;
            font-size: 15px;
        }

        .sidebar a:hover {
            background-color: #014a99;
        }
		@media (max-width: 768px) {
            .sidebar, .main-content {
                width: 100%;
                position: static;
            }
            .main-content {
                margin-left: 0;
            }
        }

        .main-content {
            margin-left: 220px;
            padding: 20px;
            width: calc(100% - 220px);
        }
        .topbar {
            background: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
        }
        .topbar h1 { font-size: 20px; color: #003366; }
        .logout {
            background: #ff4d4d;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
        }
        .logout:hover { background: #cc0000; }

        form, .results-table {
            background: white;
            padding: 20px;
            margin-top: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px #ccc;
            overflow-x: auto;
        }

        label {
            font-weight: bold;
            margin-right: 10px;
        }

        input, select, button {
            padding: 7px;
            margin: 8px 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        button {
            background: green;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover { background: darkgreen; }

        table {
            width: max-content;
            min-width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 6px 10px;
            text-align: center;
        }

        th {
            background: #003366;
            color: white;
        }

        @media (max-width: 768px) {
            .sidebar, .main-content {
                width: 100%;
                position: static;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
   <div class="sidebar">
  <h2>Teacher Panel</h2>
  <a href="teacher_dashboard.php">🏠 Dashboard</a>
  <a href="teacher_profile.php"><i class="fas fa-user"></i> Profile</a>
  <a href="upload_results.php"><i class="fas fa-file-upload"></i> Upload Results</a>
  <a href="view_results.php"><i class="fas fa-eye"></i> View Results</a>
  <a href="teacher_messages.php"><i class="fas fa-envelope"></i> Messages</a>
  <a href="view_announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a>
  <a href="teacher_resources.php"><i class="fas fa-book-open"></i> Resources</a>
  <a href="timetable.php"><i class="fas fa-calendar-alt"></i> Timetable</a>
  <a href="attendance.php"><i class="fas fa-user-check"></i> Attendance</a>
  <a href="logout.php" onclick="return confirm('Logout?')"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
<!-- Main Content -->
<div class="main-content">
    <div class="topbar">
        <h1>📊 Upload Results - 8-4-4 Curriculum</h1>
        <a href="teacher_logout.php" class="logout">Logout</a>
    </div>

    <?= $message ?>

    <!-- Load student form -->
    <form method="POST">
        <label>Class:</label>
        <input type="text" name="class" required>
        <label>Stream:</label>
        <input type="text" name="stream" required>
        <label>Term:</label>
        <select name="term" required>
            <option value="">-- Term --</option>
            <option>Term 1</option>
            <option>Term 2</option>
            <option>Term 3</option>
        </select>
        <label>Year:</label>
        <input type="number" name="year" value="<?= date('Y') ?>" required>
        <button type="submit" name="load">Load Students</button>
    </form>

    <!-- Results Table -->
    <?php if (!empty($students)): ?>
    <form method="POST" class="results-table">
        <input type="hidden" name="term" value="<?= htmlspecialchars($term) ?>">
        <input type="hidden" name="year" value="<?= htmlspecialchars($year) ?>">
        <input type="hidden" name="class" value="<?= htmlspecialchars($class) ?>">
        <input type="hidden" name="stream" value="<?= htmlspecialchars($stream) ?>">

        <table>
            <tr>
                <th>Full Name</th>
                <th>Adm No</th>
                <?php foreach ($subjects as $sub): ?>
                    <th colspan="2"><?= htmlspecialchars($sub) ?><br><small>CAT / Main</small></th>
                <?php endforeach; ?>
            </tr>
            <?php foreach ($students as $stu): ?>
            <tr>
                <td><?= htmlspecialchars($stu['full_name']) ?></td>
                <td><?= htmlspecialchars($stu['admission_no']) ?></td>
                <?php foreach ($subjects as $sub):
                    $preload = $conn->prepare("SELECT cat_marks, exam_marks FROM results WHERE admission_no = ? AND subject = ? AND term = ? AND year = ?");
                    $preload->bind_param("sssi", $stu['admission_no'], $sub, $term, $year);
                    $preload->execute();
                    $res = $preload->get_result();
                    $cat = $exam = '';
                    if ($res->num_rows > 0) {
                        $existing = $res->fetch_assoc();
                        $cat = ($existing['cat_marks'] !== null) ? intval($existing['cat_marks']) : '';
                        $exam = ($existing['exam_marks'] !== null) ? intval($existing['exam_marks']) : '';
                    }
                ?>
                    <td><input type="number" name="marks[<?= $stu['admission_no'] ?>][<?= $sub ?>][cat]" value="<?= $cat ?>" min="0" max="50"></td>
                    <td><input type="number" name="marks[<?= $stu['admission_no'] ?>][<?= $sub ?>][exam]" value="<?= $exam ?>" min="0" max="100"></td>
                <?php endforeach; ?>
            </tr>
            <?php endforeach; ?>
        </table>

        <br>
        <button type="submit" name="submit_results">✅ Submit All Results</button>
    </form>
    <?php endif; ?>
</div>

</body>
</html>
