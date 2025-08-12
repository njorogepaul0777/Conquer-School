<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Filters
$class = $_GET['class'] ?? '';
$stream = $_GET['stream'] ?? '';
$term = $_GET['term'] ?? '';
$year = $_GET['year'] ?? '';

if (!$class || !$term || !$year) {
    die("Please select Class, Term, and Year to view results.");
}

// Grade conversion
function gradeToPoints($grade) {
    $scale = ["A"=>12,"A-"=>11,"B+"=>10,"B"=>9,"B-"=>8,"C+"=>7,"C"=>6,"C-"=>5,"D+"=>4,"D"=>3,"D-"=>2,"E"=>1];
    return $scale[$grade] ?? 0;
}

function totalPointsToGrade($points) {
    if ($points >= 84) return 'A';
    elseif ($points >= 78) return 'A-';
    elseif ($points >= 72) return 'B+';
    elseif ($points >= 66) return 'B';
    elseif ($points >= 60) return 'B-';
    elseif ($points >= 54) return 'C+';
    elseif ($points >= 48) return 'C';
    elseif ($points >= 42) return 'C-';
    elseif ($points >= 36) return 'D+';
    elseif ($points >= 30) return 'D';
    elseif ($points >= 24) return 'D-';
    else return 'E';
}

function getGrade($subject, $total) {
    $lenientSubjects = ['Mathematics', 'Biology', 'Physics', 'Chemistry', 'Science'];
    $subject = strtolower($subject);
    $lenient = array_map('strtolower', $lenientSubjects);

    if (in_array($subject, $lenient)) {
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
        if ($total >= 80) return 'A';
        elseif ($total >= 75) return 'A-';
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

// Subject categories
$categories = [];
$catResult = $conn->query("SELECT name, category FROM subject WHERE curriculum = '8-4-4'");
while ($row = $catResult->fetch_assoc()) {
    $categories[$row['name']] = $row['category'];
}

// Subjects
$subjectResult = $conn->query("
    SELECT DISTINCT subject FROM results r 
    JOIN students_admitted s ON r.admission_no = s.admission_no
    WHERE s.class = '$class' AND r.term = '$term' AND r.year = '$year'
    " . ($stream ? "AND s.stream = '$stream'" : "") . " 
    ORDER BY subject ASC
");
$subjects = [];
while ($row = $subjectResult->fetch_assoc()) {
    $subjects[] = $row['subject'];
}

// Students
$studentsResult = $conn->query("
    SELECT admission_no, full_name FROM students_admitted 
    WHERE class = '$class' " . ($stream ? "AND stream = '$stream'" : "") . " 
    ORDER BY full_name
");
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= "$class $stream - Term $term, $year Results" ?></title>
   <style>
    body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
    table { width: 100%; border-collapse: collapse; background: white; margin-top: 20px; }
    th, td {
        border: 1px solid #ccc;
        padding: 6px;
        text-align: center;
        font-size: 11px;
        word-wrap: break-word;
    }
    th { background-color: #003366; color: white; }
    .print-btn {
        background: green; color: white; padding: 10px 20px; border: none;
        border-radius: 4px; cursor: pointer; margin-bottom: 20px;
    }
    .highlighted {
        background: #d4f8d4; font-weight: bold;
    }

    .school-header {
        display: flex;
        align-items: center;
        gap: 15px;
        border-bottom: 2px solid #003366;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    .school-logo {
        width: 90px;
        height: auto;
    }
    .school-info {
        flex-grow: 1;
        text-align: center;
    }
    .school-info h1 {
        margin: 0;
        font-size: 24px;
        color: #003366;
    }
    .school-info p {
        margin: 3px 0;
        font-size: 13px;
    }
    .school-info h2 {
        margin-top: 10px;
        font-size: 18px;
        color: #003366;
    }

    /* Make printing work well */
    @media print {
        .print-btn {
            display: none;
        }
        body {
            margin: 0;
            padding: 0;
            font-size: 10px;
        }
        table {
            font-size: 9px;
            width: 100%;
            zoom: 80%; /* Shrinks entire table */
        }
        th, td {
            padding: 4px;
        }
    }

    </style>
</head>
<body>

<div class="school-header">
    <img src="school_logo.png" alt="School Logo" class="school-logo">
    <div class="school-info">
        <h1>CONQUER HIGH SCHOOL</h1>
        <p>P.O. Box 12345, Nairobi, Kenya</p>
        <p>Tel: +254 712 345678 | Email: info@conquerhigh.ac.ke</p>
        <h2><?= htmlspecialchars("$class $stream - Term $term, $year Results") ?></h2>
    </div>
</div>

<button onclick="window.print()" class="print-btn">🖨️ Print Class Results</button>

<table>
<thead>
    <tr>
        <th rowspan="2">Adm No</th>
        <th rowspan="2">Full Name</th>
        <?php foreach ($subjects as $subject): ?>
            <th colspan="4"><?= htmlspecialchars($subject) ?></th>
        <?php endforeach; ?>
        <th rowspan="2">Best 7 Points</th>
        <th rowspan="2">Grade</th>
    </tr>
    <tr>
        <?php foreach ($subjects as $subject): ?>
            <th>CAT</th><th>Exam</th><th>Total</th><th>Grade</th>
        <?php endforeach; ?>
    </tr>
</thead>
<tbody>
<?php while ($student = $studentsResult->fetch_assoc()): ?>
<tr>
    <td><?= $student['admission_no'] ?></td>
    <td><?= $student['full_name'] ?></td>
    <?php
    $marks = [];
    foreach ($subjects as $subj) {
        $res = $conn->query("
            SELECT cat_marks, exam_marks, total_marks 
            FROM results 
            WHERE admission_no = '{$student['admission_no']}' AND subject = '$subj'
            AND term = '$term' AND year = '$year' LIMIT 1
        ");
        if ($res->num_rows > 0) {
            $r = $res->fetch_assoc();
            $grade = getGrade($subj, $r['total_marks']);
            $marks[$subj] = [
                'points' => gradeToPoints($grade),
                'category' => $categories[$subj] ?? 'other',
                'subject' => $subj,
                'cat' => $r['cat_marks'],
                'exam' => $r['exam_marks'],
                'total' => $r['total_marks'],
                'grade' => $grade
            ];
        } else {
            $marks[$subj] = null;
        }
    }

    // Select 7 best subjects
    $selected = [];
    $usedSubjects = [];

    if (isset($marks['Mathematics'])) {
        $selected[] = $marks['Mathematics'];
        $usedSubjects[] = 'Mathematics';
    }

    foreach (['English', 'Kiswahili'] as $lang) {
        if (isset($marks[$lang])) {
            $selected[] = $marks[$lang];
            $usedSubjects[] = $lang;
        }
    }

    $sciences = array_filter($marks, fn($m) =>
        $m && $m['category'] === 'science' &&
        strtolower($m['subject']) !== 'mathematics' &&
        !in_array($m['subject'], $usedSubjects)
    );
    usort($sciences, fn($a, $b) => $b['points'] <=> $a['points']);
    foreach (array_slice($sciences, 0, 2) as $sci) {
        $selected[] = $sci;
        $usedSubjects[] = $sci['subject'];
    }

    $humanities = array_filter($marks, fn($m) =>
        $m && $m['category'] === 'humanities' && !in_array($m['subject'], $usedSubjects)
    );
    usort($humanities, fn($a, $b) => $b['points'] <=> $a['points']);
    if (!empty($humanities)) {
        $selected[] = $humanities[0];
        $usedSubjects[] = $humanities[0]['subject'];
    }

    $others = array_filter($marks, fn($m) =>
        $m && in_array($m['category'], ['technology', 'business', 'personal_dev']) &&
        !in_array($m['subject'], $usedSubjects)
    );
    usort($others, fn($a, $b) => $b['points'] <=> $a['points']);
    if (!empty($others)) {
        $selected[] = $others[0];
        $usedSubjects[] = $others[0]['subject'];
    }

    $highlightedSubjects = array_column($selected, 'subject');

    foreach ($subjects as $subj) {
        $m = $marks[$subj];
        $cls = (in_array($subj, $highlightedSubjects)) ? "class='highlighted'" : "";
        if ($m) {
            echo "<td $cls>" . round($m['cat']) . "</td>";
            echo "<td $cls>" . round($m['exam']) . "</td>";
            echo "<td $cls>" . round($m['total']) . "</td>";
            echo "<td $cls>{$m['grade']}</td>";
        } else {
            echo "<td $cls>--</td><td $cls>--</td><td $cls>--</td><td $cls>--</td>";
        }
    }

    $best7Points = array_sum(array_column($selected, 'points'));
    $grade = totalPointsToGrade($best7Points);
    ?>
    <td><strong><?= $best7Points ?></strong></td>
    <td><strong><?= $grade ?></strong></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

</body>
</html>
