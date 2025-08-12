<?php
// manage_timetable.php
session_start();
$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Fetch subjects and teachers
$subjects = $conn->query("SELECT id, name FROM subject WHERE is_active = 1 ORDER BY name");
$teachers = $conn->query("SELECT id, full_name FROM teachers ORDER BY full_name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class = $_POST['class'];
    $stream = $_POST['stream'];

    foreach ($_POST['timetable'] as $day => $periods) {
        foreach ($periods as $period => $details) {
            $subject_id = $conn->real_escape_string($details['subject']);
            $teacher_id = $conn->real_escape_string($details['teacher']);

            if ($subject_id && $teacher_id) {
                $check = $conn->query("SELECT id FROM timetable WHERE class='$class' AND stream='$stream' AND day_of_week='$day' AND period=$period");
                if ($check->num_rows > 0) {
                    $conn->query("UPDATE timetable SET subject_id=$subject_id, teacher_id=$teacher_id WHERE class='$class' AND stream='$stream' AND day_of_week='$day' AND period=$period");
                } else {
                    $conn->query("INSERT INTO timetable (class, stream, day_of_week, period, subject_id, teacher_id) VALUES ('$class', '$stream', '$day', $period, $subject_id, $teacher_id)");
                }
            }
        }
    }
    echo "<script>alert('Timetable updated successfully!'); window.location.href='manage_timetable.php';</script>";
    exit;
}

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$periods = range(1, 8);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Timetable</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f4f4f4; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; background: white; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: center; }
        select { width: 100%; padding: 5px; }
        .form-control { margin-bottom: 10px; padding: 10px; width: 200px; }
        button { padding: 10px 20px; background: green; color: white; border: none; cursor: pointer; }
        button:hover { background: darkgreen; }
    </style>
</head>
<body>
    <h2>🗓️ Manage Timetable</h2>
    <form method="POST">
        <label>Class:</label>
        <input type="text" name="class" required class="form-control">

        <label>Stream:</label>
        <input type="text" name="stream" required class="form-control">

        <table>
            <thead>
                <tr>
                    <th>Day / Period</th>
                    <?php foreach ($periods as $p) echo "<th>Period $p</th>"; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($days as $day): ?>
                    <tr>
                        <td><strong><?= $day ?></strong></td>
                        <?php foreach ($periods as $p): ?>
                            <td>
                                <label>Subject:<br>
                                    <select name="timetable[<?= $day ?>][<?= $p ?>][subject]">
                                        <option value="">-- Select --</option>
                                        <?php
                                        mysqli_data_seek($subjects, 0);
                                        while ($sub = $subjects->fetch_assoc()): ?>
                                            <option value="<?= $sub['id'] ?>"><?= $sub['name'] ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </label>
                                <label>Teacher:<br>
                                    <select name="timetable[<?= $day ?>][<?= $p ?>][teacher]">
                                        <option value="">-- Select --</option>
                                        <?php
                                        mysqli_data_seek($teachers, 0);
                                        while ($t = $teachers->fetch_assoc()): ?>
                                            <option value="<?= $t['id'] ?>"><?= $t['full_name'] ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </label>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <br>
        <button type="submit">💾 Save Timetable</button>
    </form>
</body>
</html>
