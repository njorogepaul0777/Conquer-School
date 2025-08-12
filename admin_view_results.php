<?php
$conn = new mysqli("localhost", "root", "", "school");

if (!isset($_GET['admission_no'], $_GET['term'], $_GET['year'])) {
    die("Missing required parameters.");
}

$admission_no = $conn->real_escape_string($_GET['admission_no']);
$term = intval($_GET['term']);
$year = intval($_GET['year']);

// Fetch student info
$student_stmt = $conn->prepare("SELECT full_name, class, stream, profile_photo FROM students_admitted WHERE admission_no = ?");
$student_stmt->bind_param("s", $admission_no);
$student_stmt->execute();
$student_info = $student_stmt->get_result()->fetch_assoc();
if (!$student_info) die("Student not found.");

$studentPhoto = !empty($student_info['profile_photo']) ? $student_info['profile_photo'] : 'default_student.png';

// Fetch results
$result_stmt = $conn->prepare("SELECT subject, cat_marks, exam_marks FROM results WHERE admission_no = ? AND term = ? AND year = ? AND subject IN (SELECT name FROM subject WHERE curriculum = '8-4-4')");
$result_stmt->bind_param("sii", $admission_no, $term, $year);
$result_stmt->execute();
$results = $result_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Grading logic
// Grading logic
function getGrade($subject, $mark) {
    // Subjects using lenient scale
    $lenientSubjects = ['mathematics', 'physics', 'chemistry', 'biology', 'science'];

    if (in_array(strtolower($subject), $lenientSubjects)) {
        // Lenient scale: A from 75
        if ($mark >= 75) return 'A';
        elseif ($mark >= 70) return 'A-';
        elseif ($mark >= 65) return 'B+';
        elseif ($mark >= 60) return 'B';
        elseif ($mark >= 55) return 'B-';
        elseif ($mark >= 50) return 'C+';
        elseif ($mark >= 45) return 'C';
        elseif ($mark >= 40) return 'C-';
        elseif ($mark >= 35) return 'D+';
        elseif ($mark >= 30) return 'D';
        elseif ($mark >= 25) return 'D-';
        else return 'E';
    } else {
        // Standard scale: A from 80
        if ($mark >= 80) return 'A';
        elseif ($mark >= 75) return 'A-';
        elseif ($mark >= 70) return 'B+';
        elseif ($mark >= 65) return 'B';
        elseif ($mark >= 60) return 'B-';
        elseif ($mark >= 55) return 'C+';
        elseif ($mark >= 50) return 'C';
        elseif ($mark >= 45) return 'C-';
        elseif ($mark >= 40) return 'D+';
        elseif ($mark >= 35) return 'D';
        elseif ($mark >= 30) return 'D-';
        else return 'E';
    }
}
function gradePoints($grade) {
    return ['A'=>12,'A-'=>11,'B+'=>10,'B'=>9,'B-'=>8,'C+'=>7,'C'=>6,'C-'=>5,'D+'=>4,'D'=>3,'D-'=>2,'E'=>1][$grade] ?? 0;
}
	//grading total
function getTotalGrade($points) {
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
  
function getRemarks($gpa) {
    if ($gpa >= 11) return "Excellent";
    elseif ($gpa >= 9) return "Very Good";
    elseif ($gpa >= 7) return "Good";
    elseif ($gpa >= 5) return "Fair";
    else return "Needs Improvement";
}

// Subject categories
$subject_stmt = $conn->prepare("SELECT name, category FROM subject WHERE curriculum = '8-4-4' AND is_active = 1");
$subject_stmt->execute();
$subjects = $subject_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$subjectCategories = [
    'science' => [], 'humanities' => [],
    'language' => ['English', 'Kiswahili'],
    'technology' => [], 'business' => [], 'personal_dev' => []
];
foreach ($subjects as $subject) {
    if (isset($subjectCategories[$subject['category']])) {
        $subjectCategories[$subject['category']][] = $subject['name'];
    }
}

// Enrich results
$enriched = [];
foreach ($results as $res) {
    $cat = ($res['cat_marks'] !== null) ? intval(($res['cat_marks'] / 50) * 30) : 0;
    $exam = ($res['exam_marks'] !== null) ? intval(($res['exam_marks'] / 100) * 70) : 0;
    $total = $cat + $exam;
    $grade = getGrade($res['subject'], $total);
    $points = gradePoints($grade);
    $enriched[$res['subject']] = [
        'subject' => $res['subject'], 'cat' => $cat,
        'exam' => $exam, 'total' => $total,
        'grade' => $grade, 'points' => $points
    ];
}

// Grouping and selection logic
$groupedSubjects = ['science'=>[], 'humanities'=>[], 'language'=>[], 'technology'=>[], 'business'=>[], 'personal_dev'=>[]];
$mathSubject = null;
foreach ($enriched as $e) {
    if (strtolower($e['subject']) === 'mathematics') $mathSubject = $e;
    else foreach ($subjectCategories as $cat => $subs) {
        if (in_array($e['subject'], $subs)) $groupedSubjects[$cat][] = $e;
    }
}
foreach ($groupedSubjects as &$g) usort($g, fn($a, $b) => $b['points'] <=> $a['points']);

$bestSubjects = [];
if ($mathSubject) $bestSubjects[] = $mathSubject;
$bestSubjects = array_merge($bestSubjects, array_slice($groupedSubjects['science'], 0, 2));
foreach (['English', 'Kiswahili'] as $lang) {
    foreach ($groupedSubjects['language'] as $key => $sub) {
        if ($sub['subject'] === $lang) {
            $bestSubjects[] = $sub;
            unset($groupedSubjects['language'][$key]);
        }
    }
}
if (!empty($groupedSubjects['humanities'])) $bestSubjects[] = array_shift($groupedSubjects['humanities']);
$other = array_merge($groupedSubjects['technology'], $groupedSubjects['business'], $groupedSubjects['personal_dev']);
if (!empty($other)) {
    usort($other, fn($a, $b) => $b['points'] <=> $a['points']);
    $bestSubjects[] = $other[0];
}

$bestSubjectsNames = array_map(fn($s) => $s['subject'], $bestSubjects);
$totalPoints = array_sum(array_column($bestSubjects, 'points'));
$totalMarks = array_sum(array_column($bestSubjects, 'total'));
$gpa = round($totalPoints / count($bestSubjects), 2);
?>



<!DOCTYPE html>
<html>
<head>
    <title>Result Slip - Conquer High School</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f8fb; margin: 0; padding: 0; }
        .top-nav {
            background-color: #003366; color: white;
            padding: 12px 20px;
            display: flex; justify-content: space-between; align-items: center;
            position: fixed; top: 0; width: 100%; z-index: 1000;
        }
        .top-nav .left {
            display: flex; align-items: center;
        }
        .top-nav img { height: 40px; margin-right: 10px; }
        .top-nav .right a {
            color: #FFD700; text-decoration: none; font-size: 14px;
            margin-left: 10px;
        }
        .slip-container {
            max-width: 850px; margin: 100px auto 60px auto;
            background: #fff; padding: 30px;
            border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            position: relative;
        }
        h1, h2 { text-align: center; color: #003366; margin: 10px 0; }
        .info p { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
        th, td { padding: 8px; border: 1px solid #ccc; text-align: center; }
        .footer-sign { display: flex; justify-content: space-between; margin-top: 30px; }
        .footer-sign div {
            width: 45%; border: 1px dashed #999;
            padding: 15px; text-align: center; font-style: italic;
        }
        .buttons { text-align: center; margin: 20px 0; }
        .buttons button {
            background-color: #004080; color: white; padding: 10px 20px;
            border: none; border-radius: 5px; cursor: pointer; margin: 5px;
        }
        .buttons button:hover { background: #0066cc; }
        .school-logo {
            position: absolute; top: 10px; right: 20px; height: 70px;
        }
        .student-photo {
            position: absolute; top: 10px; left: 20px;
            height: 70px; width: 70px; border-radius: 50%;
            object-fit: cover; border: 2px solid #ccc;
        }
        footer {
            text-align: center; background: #002244;
            color: #ccc; padding: 15px 0; margin-top: 40px; font-size: 13px;
        }
        .dropped { color: #999; font-style: italic; background: #f9f9f9; }
        @media print {
            .buttons, .top-nav, footer { display: none; }
        }
    </style>
</head>
<body>

<!-- Top Navigation Bar -->
<div class="top-nav">
    <div class="left">
        <img src="school_logo.png" alt="School Logo">
        <span style="font-size: 18px; font-weight: bold;">Conquer High School</span>
    </div>
    <div class="right">
        <?= htmlspecialchars($student_info['full_name']) ?> |
        <a href="admin_dashboard.php">Admin Panel</a>
    </div>
</div>

<div class="slip-container">
    <img src="school_logo.png" class="school-logo" alt="School Logo">
    <img src="<?= htmlspecialchars($studentPhoto) ?>" class="student-photo" alt="Student Photo">

    <h1>Conquer High School</h1>
    <h2>Official Student Result Slip</h2>

    <div class="info">
        <p><strong>Name:</strong> <?= htmlspecialchars($student_info['full_name']) ?></p>
        <p><strong>Admission No:</strong> <?= htmlspecialchars($admission_no) ?></p>
        <p><strong>Class:</strong> <?= htmlspecialchars($student_info['class']) ?></p>
        <p><strong>Stream:</strong> <?= htmlspecialchars($student_info['stream']) ?></p>
        <p><strong>Term:</strong> <?= $term ?></p>
        <p><strong>Year:</strong> <?= $year ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Subject</th><th>CAT (30%)</th><th>Exam (70%)</th>
                <th>Total</th><th>Grade</th><th>Points</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($bestSubjects as $subject): ?>
            <tr>
                <td><?= htmlspecialchars($subject['subject']) ?></td>
                <td><?= $subject['cat'] ?></td>
                <td><?= $subject['exam'] ?></td>
                <td><?= $subject['total'] ?></td>
                <td><?= $subject['grade'] ?></td>
                <td><?= $subject['points'] ?></td>
            </tr>
        <?php endforeach; ?>
        <?php foreach ($enriched as $subject):
            if (!in_array($subject['subject'], $bestSubjectsNames)): ?>
            <tr class="dropped" title="Dropped Subject">
                <td><?= htmlspecialchars($subject['subject']) ?></td>
                <td><?= $subject['cat'] ?></td>
                <td><?= $subject['exam'] ?></td>
                <td><?= $subject['total'] ?></td>
                <td><?= $subject['grade'] ?></td>
                <td>0</td>
            </tr>
        <?php endif; endforeach; ?>
        <tr><th colspan="3">Total Marks</th><td><?= $totalMarks ?></td><th>Total Points</th><td><?= $totalPoints ?></td></tr>
        <tr><th colspan="3">GPA</th><td><?= $gpa ?></td><th>Grade</th><td><?= getTotalGrade($totalPoints) ?></td></tr>
        <tr><th colspan="5">Remarks</th><td><?= getRemarks($gpa) ?></td></tr>
        </tbody>
    </table>

    <div class="footer-sign">
        <div>Class Teacher's Signature<br><br>___________________</div>
        <div>Headteacher's Signature<br><br>___________________</div>
    </div>

    <div class="buttons">
        <button onclick="window.print()">Print Result</button>
        <button onclick="window.history.back()">Back</button>
    </div>
</div>

<footer>
    &copy; <?= date('Y') ?> Conquer High School. All rights reserved.
</footer>

</body>
</html>
