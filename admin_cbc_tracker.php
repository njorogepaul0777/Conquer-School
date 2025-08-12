<?php
session_start();
$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) die("Connection Failed: " . $conn->connect_error);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save'])) {
    $class = $_POST['class'];
    $stream = $_POST['stream'];
    $term = $_POST['term'];
    $year = $_POST['year'];
    $subject = $_POST['subject'];

    foreach ($_POST['marks'] as $admission_no => $exams) {
        $exam1 = is_numeric($exams['exam1']) ? intval($exams['exam1']) : null;
        $exam2 = is_numeric($exams['exam2']) ? intval($exams['exam2']) : null;
        $exam3 = is_numeric($exams['exam3']) ? intval($exams['exam3']) : null;

        // Assign levels
        function level($score) {
            if ($score === null) return null;
            if ($score >= 80) return 'Excellent';
            if ($score >= 60) return 'Good';
            if ($score >= 40) return 'Average';
            return 'Needs Improvement';
        }

        $level1 = level($exam1);
        $level2 = level($exam2);
        $level3 = level($exam3);

        // Check if result exists
        $check = $conn->query("SELECT * FROM cbc_tracker_results WHERE admission_no='$admission_no' AND subject='$subject' AND term='$term' AND year='$year'");
        if ($check->num_rows > 0) {
            // Update
            $conn->query("UPDATE cbc_tracker_results SET exam1=".($exam1 ?? 'NULL').", exam2=".($exam2 ?? 'NULL').", exam3=".($exam3 ?? 'NULL').",
                level1=".($level1 ? "'$level1'" : "NULL").", level2=".($level2 ? "'$level2'" : "NULL").", level3=".($level3 ? "'$level3'" : "NULL")."
                WHERE admission_no='$admission_no' AND subject='$subject' AND term='$term' AND year='$year'");
        } else {
            // Insert
            $conn->query("INSERT INTO cbc_tracker_results (admission_no, subject, term, year, exam1, exam2, exam3, level1, level2, level3)
                VALUES ('$admission_no', '$subject', '$term', '$year', ".($exam1 ?? 'NULL').", ".($exam2 ?? 'NULL').", ".($exam3 ?? 'NULL').",
                ".($level1 ? "'$level1'" : "NULL").", ".($level2 ? "'$level2'" : "NULL").", ".($level3 ? "'$level3'" : "NULL").")");
        }
    }

    echo "<div class='alert alert-success text-center'>CBC Tracker saved successfully!</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>CBC Tracker Upload</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">

<h3 class="mb-4">CBC Tracker Upload Page</h3>

<!-- Selection Form -->
<form method="GET" class="row g-3 mb-4">
    <?php
    $classes = $conn->query("SELECT DISTINCT class FROM students_admitted ORDER BY class");
    $streams = $conn->query("SELECT DISTINCT stream FROM students_admitted ORDER BY stream");
    $subjects = $conn->query("SELECT name FROM subject WHERE curriculum='CBC' ORDER BY name");
    ?>

    <div class="col-md-2">
        <label>Class:</label>
        <select name="class" class="form-control" required>
            <option value="">Select</option>
            <?php while($r = $classes->fetch_assoc()): ?>
                <option <?= ($_GET['class'] ?? '') == $r['class'] ? 'selected' : '' ?> value="<?= $r['class'] ?>"><?= $r['class'] ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label>Stream:</label>
        <select name="stream" class="form-control" required>
            <option value="">Select</option>
            <?php while($r = $streams->fetch_assoc()): ?>
                <option <?= ($_GET['stream'] ?? '') == $r['stream'] ? 'selected' : '' ?> value="<?= $r['stream'] ?>"><?= $r['stream'] ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label>Term:</label>
        <select name="term" class="form-control" required>
            <option value="">Select</option>
            <?php foreach(['Term 1','Term 2','Term 3'] as $t): ?>
                <option <?= ($_GET['term'] ?? '') == $t ? 'selected' : '' ?> value="<?= $t ?>"><?= $t ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <label>Year:</label>
        <input type="number" name="year" value="<?= $_GET['year'] ?? date('Y') ?>" class="form-control" required>
    </div>
    <div class="col-md-3">
        <label>Subject (CBC):</label>
        <select name="subject" class="form-control" required>
            <option value="">Select</option>
            <?php while($r = $subjects->fetch_assoc()): ?>
                <option <?= ($_GET['subject'] ?? '') == $r['name'] ? 'selected' : '' ?> value="<?= $r['name'] ?>"><?= $r['name'] ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="col-md-1 mt-4">
        <button type="submit" class="btn btn-primary w-100">Load</button>
    </div>
</form>

<?php
if (isset($_GET['class'], $_GET['stream'], $_GET['term'], $_GET['year'], $_GET['subject'])):
    $class = $_GET['class'];
    $stream = $_GET['stream'];
    $term = $_GET['term'];
    $year = $_GET['year'];
    $subject = $_GET['subject'];

    $students = $conn->query("SELECT admission_no, full_name FROM students_admitted WHERE class='$class' AND stream='$stream' ORDER BY full_name ASC");
    if ($students->num_rows == 0) {
        echo "<div class='alert alert-warning'>No students found in this class and stream.</div>";
    } else {
?>

<form method="POST">
    <input type="hidden" name="class" value="<?= $class ?>">
    <input type="hidden" name="stream" value="<?= $stream ?>">
    <input type="hidden" name="term" value="<?= $term ?>">
    <input type="hidden" name="year" value="<?= $year ?>">
    <input type="hidden" name="subject" value="<?= $subject ?>">

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Adm No</th>
                <th>Student Name</th>
                <th>Exam 1</th>
                <th>Exam 2</th>
                <th>Exam 3</th>
            </tr>
        </thead>
        <tbody>
            <?php while($s = $students->fetch_assoc()):
                $adm = $s['admission_no'];
                $existing = $conn->query("SELECT * FROM cbc_tracker_results WHERE admission_no='$adm' AND subject='$subject' AND term='$term' AND year='$year'")->fetch_assoc();
            ?>
            <tr>
                <td><?= $adm ?></td>
                <td><?= $s['full_name'] ?></td>
                <td><input type="number" name="marks[<?= $adm ?>][exam1]" value="<?= $existing['exam1'] ?? '' ?>" class="form-control" min="0" max="100"></td>
                <td><input type="number" name="marks[<?= $adm ?>][exam2]" value="<?= $existing['exam2'] ?? '' ?>" class="form-control" min="0" max="100"></td>
                <td><input type="number" name="marks[<?= $adm ?>][exam3]" value="<?= $existing['exam3'] ?? '' ?>" class="form-control" min="0" max="100"></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <button type="submit" name="save" class="btn btn-success">Save CBC Tracker</button>
</form>

<?php } endif; ?>

</body>
</html>
