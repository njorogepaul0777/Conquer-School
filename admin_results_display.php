<?php
session_start();
if (!isset($_SESSION['view_data'])) {
    echo "<p style='color:red;text-align:center;'>No result data found. Please go back and load results first.</p>";
    exit();
}

$data = $_SESSION['view_data'];
$class = $data['class'];
$stream = $data['stream'];
$term = $data['term'];
$year = $data['year'];
$subjects = $data['subjects'];
$students = $data['students'];
$results = $data['results'];

function gradeToPoints($grade) {
    $map = [
        'A' => 12, 'A-' => 11, 'B+' => 10, 'B' => 9, 'B-' => 8,
        'C+' => 7, 'C' => 6, 'C-' => 5, 'D+' => 4, 'D' => 3,
        'D-' => 2, 'E' => 1
    ];
    return $map[$grade] ?? 0;
}

function getTotalGrade($totalPoints) {
    if ($totalPoints >= 84) return "A";
    elseif ($totalPoints >= 78) return "A-";
    elseif ($totalPoints >= 72) return "B+";
    elseif ($totalPoints >= 66) return "B";
    elseif ($totalPoints >= 60) return "B-";
    elseif ($totalPoints >= 54) return "C+";
    elseif ($totalPoints >= 48) return "C";
    elseif ($totalPoints >= 42) return "C-";
    elseif ($totalPoints >= 36) return "D+";
    elseif ($totalPoints >= 30) return "D";
    elseif ($totalPoints >= 24) return "D-";
    else return "E";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Results Display - <?= htmlspecialchars(ucfirst($class)) ?> <?= htmlspecialchars($stream) ?></title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f4f4f4; }
        h2, h4 { text-align: center; }
        table { width: 100%; border-collapse: collapse; background: #fff; box-shadow: 0 0 10px #ccc; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; font-size: 13px; }
        th { background: #2c3e50; color: white; }
        .print { margin-bottom: 20px; text-align: right; }
        .print button { background: green; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .print button:hover { background: darkgreen; }
    </style>
</head>
<body>

<h2>CONQUER HIGH SCHOOL Results - <?= htmlspecialchars($class) ?> <?= htmlspecialchars($stream) ?> | <?= htmlspecialchars($term) ?> <?= htmlspecialchars($year) ?></h2>
<div class="print">
    <button onclick="window.print()">🖨️ Print or Save as PDF</button>
</div>
<table>
    <thead>
        <tr>
            <th>Full Name</th>
            <th>Adm No</th>
            <?php foreach ($subjects as $sub): ?>
                <th><?= htmlspecialchars($sub) ?><br><small>CAT / EXAM / TOTAL / GRADE</small></th>
            <?php endforeach; ?>
            <th>Total Points</th>
            <th>Overall Grade</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($students as $stu): ?>
        <tr>
            <td><?= htmlspecialchars($stu['full_name']) ?></td>
            <td><?= htmlspecialchars($stu['admission_no']) ?></td>
            <?php
            $total_points = 0;
            foreach ($subjects as $sub):
                $r = $results[$stu['admission_no']][$sub];
                $grade = $r['grade'];
                $points = ($grade !== '-' && $grade !== '') ? gradeToPoints($grade) : 0;
                $total_points += $points;
            ?>
            <td><?= "{$r['cat_marks']} / {$r['exam_marks']} / {$r['total_marks']} / {$r['grade']}" ?></td>
            <?php endforeach; ?>
            <td><strong><?= $total_points ?></strong></td>
            <td><strong><?= getTotalGrade($total_points) ?></strong></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
