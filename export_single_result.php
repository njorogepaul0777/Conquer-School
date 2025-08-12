<?php
require('fpdf/fpdf.php'); // Ensure you have FPDF in the same directory or include path

session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    die("Unauthorized access.");
}

if (!isset($_POST['student'], $_POST['class'], $_POST['term'], $_POST['year'])) {
    die("Missing data.");
}

$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$admission_no = $_POST['student'];
$class = $_POST['class'];
$term = $_POST['term'];
$year = $_POST['year'];

// Fetch student info
$studentQuery = $conn->prepare("SELECT full_name, class, stream FROM students_admitted WHERE admission_no = ?");
$studentQuery->bind_param("s", $admission_no);
$studentQuery->execute();
$studentResult = $studentQuery->get_result();
$student = $studentResult->fetch_assoc();

if (!$student) {
    die("Student not found.");
}

// Get subject list
$subjects = [];
$res = $conn->query("SELECT name FROM subject WHERE curriculum = '8-4-4' ORDER BY name");
while ($row = $res->fetch_assoc()) {
    $subjects[] = $row['name'];
}

// Grade to points mapping
function gradeToPoints($grade) {
    $map = [
        'A' => 12, 'A-' => 11, 'B+' => 10, 'B' => 9, 'B-' => 8,
        'C+' => 7, 'C' => 6, 'C-' => 5, 'D+' => 4, 'D' => 3,
        'D-' => 2, 'E' => 1
    ];
    return $map[$grade] ?? 0;
}

function getTotalGrade($points) {
    if ($points >= 84) return "A";
    elseif ($points >= 78) return "A-";
    elseif ($points >= 72) return "B+";
    elseif ($points >= 66) return "B";
    elseif ($points >= 60) return "B-";
    elseif ($points >= 54) return "C+";
    elseif ($points >= 48) return "C";
    elseif ($points >= 42) return "C-";
    elseif ($points >= 36) return "D+";
    elseif ($points >= 30) return "D";
    elseif ($points >= 24) return "D-";
    else return "E";
}

// Fetch results
$results = [];
$totalPoints = 0;

foreach ($subjects as $subject) {
    $stmt = $conn->prepare("SELECT cat_marks, exam_marks, total_marks, grade FROM results WHERE admission_no = ? AND subject = ? AND term = ? AND year = ?");
    $stmt->bind_param("sssi", $admission_no, $subject, $term, $year);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $results[$subject] = $row;
        $points = gradeToPoints($row['grade']);
        $totalPoints += $points;
    } else {
        $results[$subject] = ['cat_marks' => '-', 'exam_marks' => '-', 'total_marks' => '-', 'grade' => '-'];
    }
}

$overallGrade = getTotalGrade($totalPoints);

// -------------------- Generate PDF --------------------
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

$pdf->Cell(0, 10, 'CONQUER HIGH SCHOOL', 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, "Student Result Slip - $term $year", 0, 1, 'C');
$pdf->Ln(5);

$pdf->SetFont('Arial', '', 11);
$pdf->Cell(100, 8, "Name: " . $student['full_name'], 0, 0);
$pdf->Cell(50, 8, "ADM No: " . $admission_no, 0, 1);
$pdf->Cell(100, 8, "Class: " . $student['class'], 0, 0);
$pdf->Cell(50, 8, "Stream: " . $student['stream'], 0, 1);
$pdf->Ln(5);

// Table Header
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(200, 220, 255);
$pdf->Cell(60, 8, 'Subject', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'CAT', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'EXAM', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'TOTAL', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'GRADE', 1, 1, 'C', true);

// Table Body
$pdf->SetFont('Arial', '', 10);
foreach ($subjects as $subject) {
    $r = $results[$subject];
    $pdf->Cell(60, 8, $subject, 1);
    $pdf->Cell(25, 8, $r['cat_marks'], 1, 0, 'C');
    $pdf->Cell(25, 8, $r['exam_marks'], 1, 0, 'C');
    $pdf->Cell(25, 8, $r['total_marks'], 1, 0, 'C');
    $pdf->Cell(25, 8, $r['grade'], 1, 1, 'C');
}

// Total and Grade
$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(80, 8, 'Total Points: ' . $totalPoints, 0, 1);
$pdf->Cell(80, 8, 'Overall Grade: ' . $overallGrade, 0, 1);

$pdf->Output("I", "{$student['full_name']}_Result_{$term}_{$year}.pdf");
exit;
?>
