<?php
$conn = new mysqli("localhost", "root", "", "school");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class = $_POST['class'];
    $stream = $_POST['stream'];
    $term = $_POST['term'];
    $year = $_POST['year'];

    $students = $conn->prepare("SELECT admission_no, full_name FROM students_admitted WHERE class = ? AND stream = ? ORDER BY LENGTH(admission_no), admission_no");
    $students->bind_param("ss", $class, $stream);
    $students->execute();
    $student_data = $students->get_result()->fetch_all(MYSQLI_ASSOC);

    $subjects_result = $conn->query("SELECT DISTINCT subject FROM results WHERE term = '$term' AND year = '$year'");
    $subjects = [];
    while ($s = $subjects_result->fetch_assoc()) $subjects[] = $s['subject'];

    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=results_{$class}_{$stream}_{$term}_{$year}.csv");

    $output = fopen("php://output", "w");
    $header = array_merge(['Admission Number', 'Full Name'], $subjects, ['Total', 'Average']);
    fputcsv($output, $header);

    foreach ($student_data as $stu) {
        $row = [$stu['admission_no'], $stu['full_name']];
        $total = 0;
        $count = 0;
        foreach ($subjects as $sub) {
            $stmt = $conn->prepare("SELECT marks FROM results WHERE admission_no = ? AND subject = ? AND term = ? AND year = ?");
            $stmt->bind_param("sssi", $stu['admission_no'], $sub, $term, $year);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $mark = $res['marks'] ?? '';
            $row[] = $mark;
            if ($mark !== '') {
                $total += $mark;
                $count++;
            }
        }
        $avg = $count > 0 ? round($total / $count, 2) : '';
        $row[] = $total;
        $row[] = $avg;
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>Download Stream Results</title>
</head>
<body>
<h2>Download Stream Results</h2>
<form method="POST">
    <label>Class:</label>
    <input type="text" name="class" required>

    <label>Stream:</label>
    <input type="text" name="stream" required>

    <label>Term:</label>
    <select name="term" required>
        <option value="">-- Select Term --</option>
        <option>Term 1</option>
        <option>Term 2</option>
        <option>Term 3</option>
    </select>

    <label>Year:</label>
    <input type="number" name="year" value="<?= date('Y') ?>" required>

    <button type="submit">Download CSV</button>
</form>
</body>
</html>

