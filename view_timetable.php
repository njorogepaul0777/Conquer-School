<?php
$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Get distinct classes and streams
$classes = $conn->query("SELECT DISTINCT class FROM students_admitted");
$streams = $conn->query("SELECT DISTINCT stream FROM students_admitted");

// Initialize values
$class = $stream = "";
$timetable = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $class = $_POST['class'];
    $stream = $_POST['stream'];

    // Fetch all timetable entries for that class + stream
    $sql = "SELECT t.day, t.period, s.name, te.full_name AS teacher_name
            FROM timetable t
            JOIN subjects s ON t.subject_id = s.id
            JOIN teachers te ON t.teacher_id = te.id
            WHERE t.class = '$class' AND t.stream = '$stream'";
    $result = $conn->query($sql);

    // Format timetable into nested array: $timetable[day][period]
    while ($row = $result->fetch_assoc()) {
        $day = $row['day'];
        $period = $row['period'];
        $subject = $row['name'];
        $teacher = $row['teacher_name'];
        $timetable[$day][$period] = "$subject<br><small>($teacher)</small>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Timetable | High School</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f7f7f7; }
        form { margin-bottom: 20px; background: #fff; padding: 20px; border-radius: 10px; width: 450px; margin: auto; }
        select, button { width: 100%; padding: 10px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; }
        th, td { border: 1px solid #ccc; padding: 10px; text-align: center; vertical-align: middle; }
        th { background: #f0f0f0; }
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

<h2 style="text-align:center;">View Class Timetable</h2>

<form method="POST">
    <label>Select Class:</label>
    <select name="class" required>
        <option value="">Select Class</option>
        <?php while ($row = $classes->fetch_assoc()): ?>
            <option value="<?= $row['class'] ?>" <?= $class == $row['class'] ? 'selected' : '' ?>>
                <?= $row['class'] ?>
            </option>
        <?php endwhile; ?>
    </select>

    <label>Select Stream:</label>
    <select name="stream" required>
        <option value="">Select Stream</option>
        <?php $streams->data_seek(0); while ($row = $streams->fetch_assoc()): ?>
            <option value="<?= $row['stream'] ?>" <?= $stream == $row['stream'] ? 'selected' : '' ?>>
                <?= $row['stream'] ?>
            </option>
        <?php endwhile; ?>
    </select>

    <button type="submit">View Timetable</button>
</form>

<?php if (!empty($timetable)): ?>
    <h3 style="text-align:center;">Timetable for <?= htmlspecialchars($class) ?> - <?= htmlspecialchars($stream) ?></h3>
    <table>
        <tr>
            <th>Period</th>
            <?php foreach (["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"] as $day): ?>
                <th><?= $day ?></th>
            <?php endforeach; ?>
        </tr>
        <?php for ($period = 1; $period <= 8; $period++): ?>
            <tr>
                <td><strong>Period <?= $period ?></strong></td>
                <?php foreach (["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"] as $day): ?>
                    <td>
                        <?= isset($timetable[$day][$period]) ? $timetable[$day][$period] : '-' ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endfor; ?>
    </table>
<?php elseif ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
    <p style="text-align:center; color:red;">No timetable data found for this class and stream.</p>
<?php endif; ?>

</body>
</html>
