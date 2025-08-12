<?php
session_start();
$conn = new mysqli("localhost", "root", "", "school");

if (!isset($_SESSION['admission_no'])) {
    echo "Access denied. Please log in.";
    exit();
}

$admission_no = $_SESSION['admission_no'];
$student = $conn->query("SELECT full_name, class, stream, profile_photo FROM students_admitted WHERE admission_no = '$admission_no'")->fetch_assoc();
$full_name = $student['full_name'];
$class = $student['class'];
$stream = $student['stream'];
$profile_photo = $student['profile_photo'] ?: 'default_student.png';

$term = $year = "";
$results = [];
$total = 0;
$count = 0;

// Fetch term/year options
$term_years = $conn->query("SELECT DISTINCT term, year FROM cbc_assessment WHERE admission_no = '$admission_no' ORDER BY year DESC, term");
$options = [];
while ($row = $term_years->fetch_assoc()) {
    $options[] = $row;
}

// Handle form
if (isset($_GET['term_year'])) {
    list($term, $year) = explode('|', $_GET['term_year']);
    $stmt = $conn->prepare("SELECT name, mark, level, comment FROM cbc_assessment WHERE admission_no = ? AND term = ? AND year = ? ORDER BY subject_name");
    $stmt->bind_param("sss", $admission_no, $term, $year);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $results[] = $r;
        $total += $r['mark'];
        $count++;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>CBC Results - Conquer High School</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .header, .student-info, .form-box, table {
            background: #fff; padding: 20px; border-radius: 6px;
            box-shadow: 0 0 8px rgba(0,0,0,0.05); margin-bottom: 20px;
        }
        .header { text-align: center; }
        .header img { width: 80px; height: 80px; }
        .student-info { display: flex; justify-content: space-between; align-items: center; }
        .student-photo img { width: 100px; height: 100px; border-radius: 8px; }
        .details { flex-grow: 1; padding-left: 20px; }
        .form-box select, .form-box button {
            padding: 10px; font-size: 16px; margin-top: 10px;
            border-radius: 5px; border: 1px solid #ccc;
            width: 200px;
        }
        .form-box button {
            background-color: #007bff; color: white;
            font-weight: bold; cursor: pointer;
        }
        .form-box button:hover { background-color: #0056b3; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f0f0f0; }
        .print-btn {
            background: #28a745; color: white;
            padding: 8px 15px; border: none; border-radius: 5px;
            float: right; cursor: pointer;
        }
        .print-btn:hover { background: #218838; }
        .signatures td { padding: 20px; text-align: center; }
        @media print {
            .form-box, .print-btn { display: none; }
        }
    </style>
</head>
<body>

<div class="header">
    <img src="uploads/school_logo.png" alt="School Logo">
    <h2>Conquer High School</h2>
    <p>P.O. Box 123, Nairobi | Tel: 0700 123456 | Email: info@conquerhigh.ac.ke</p>
</div>

<div class="student-info">
    <div class="student-photo">
        <img src="uploads/<?= htmlspecialchars($profile_photo) ?>" alt="Student Photo">
    </div>
    <div class="details">
        <p><strong>Name:</strong> <?= htmlspecialchars($full_name) ?></p>
        <p><strong>Admission No:</strong> <?= htmlspecialchars($admission_no) ?></p>
        <p><strong>Class:</strong> <?= htmlspecialchars($class) ?> <?= htmlspecialchars($stream) ?></p>
    </div>
</div>

<div class="form-box">
    <form method="GET">
        <label>Select Term & Year:</label><br>
        <select name="term_year" required>
            <option value="">-- Select Term & Year --</option>
            <?php foreach ($options as $o): 
                $v = $o['term'] . '|' . $o['year'];
                $label = $o['term'] . ' ' . $o['year'];
                $selected = ($term . '|' . $year == $v) ? 'selected' : '';
                echo "<option value='$v' $selected>$label</option>";
            endforeach; ?>
        </select><br>
        <button type="submit">View Results</button>
    </form>
</div>

<?php if ($term && $year): ?>
    <?php if ($count > 0): ?>
        <button class="print-btn" onclick="window.print()">🖨 Print</button>
        <h3>CBC Results for <?= htmlspecialchars($term) ?> <?= htmlspecialchars($year) ?></h3>
        <table>
            <tr><th>Subject</th><th>Mark</th><th>Level</th><th>Comment</th></tr>
            <?php foreach ($results as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['subject_name']) ?></td>
                    <td><?= $r['mark'] ?></td>
                    <td><?= htmlspecialchars($r['level']) ?></td>
                    <td><?= htmlspecialchars($r['comment']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <br>
        <p><strong>Total:</strong> <?= $total ?> |
           <strong>Average:</strong> <?= round($total / $count, 2) ?> |
           <strong>Remark:</strong>
           <?php
               $avg = $total / $count;
               echo ($avg >= 80) ? "Excellent" : (($avg >= 60) ? "Good" : (($avg >= 40) ? "Fair" : "Needs Improvement"));
           ?>
        </p>

        <!-- Signature area -->
        <div class="signatures">
            <table width="100%">
                <tr>
                    <td>_<br><strong>Class Teacher</strong></td>
                    <td>_<br><strong>Principal</strong></td>
                </tr>
                <tr><td colspan="2">Date: _____________</td></tr>
            </table>
        </div>
    <?php else: ?>
        <p style="color: red;">No results found for selected term and year.</p>
    <?php endif; ?>
<?php endif; ?>

</body>
</html>