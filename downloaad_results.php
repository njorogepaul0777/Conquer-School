<?php
require('lib/fpdf/fpdf.php'); // adjust path if needed

session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    die("Unauthorized access.");
}

$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$class = $_POST['class'] ?? '';
$stream = $_POST['stream'] ?? '';
$term = $_POST['term'] ?? '';
$year = $_POST['year'] ?? '';

if (!$class || !$stream || !$term || !$year) {
    die("Missing required filters.");
}

// Fetch all students in selected class/stream/term/year
$query = "
    SELECT r.admission_no, s.full_name, s.class, s.stream, r.term, r.year
    FROM results r
    JOIN students_admitted s ON r.admission_no = s.admission_no
    WHERE s.class = '$class' AND s.stream = '$stream'
      AND r.term = '$term' AND r.year = '$year'
    GROUP BY r.admission_no
    ORDER BY s.full_name ASC
";
$students = $conn->query($query);

// Fetch subject list
$subjectsQuery = $conn->query("SELECT DISTINCT subject FROM results WHERE term='$term' AND year='$year'");
$subjects = [];
while ($subRow = $subjectsQuery->fetch_assoc()) {
    $subjects[] = $subRow['subject'];
}

// Start generating PDF
$pdf = new FPDF();
$pdf->SetFont('Arial', '', 12);

while ($student = $students->fetch_assoc()) {
    $pdf->AddPage();

    // Header
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(190, 10, "CONQUER HIGH SCHOOL", 0, 1, 'C');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(190, 10, "Student Result Slip", 0, 1, 'C');
    $pdf->Ln(5);

    // Student Info
    $pdf->Cell(90, 8, "Name: " . $student['full_name'], 0, 0);
    $pdf->Cell(100, 8, "Admission No: " . $student['admission_no'], 0, 1);
    $pdf->Cell(90, 8, "Class: " . $student['class'], 0, 0);
    $pdf->Cell(100, 8, "Stream: " . $student['stream'], 0, 1);
    $pdf->Cell(90, 8, "Term: " . $student['term'], 0, 0);
    $pdf->Cell(100, 8, "Year: " . $student['year'], 0, 1);
    $pdf->Ln(5);

    // Table Header
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(100, 8, "Subject", 1);
    $pdf->Cell(90, 8, "Marks", 1);
    $pdf->SetFont('Arial', '', 12);

    $total = 0;
    $count = 0;

    foreach ($subjects as $subject) {
        $resultQ = $conn->query("SELECT marks FROM results WHERE admission_no='{$student['admission_no']}' AND subject='$subject' AND term='$term' AND year='$year'");
        $mark = ($resultQ && $row = $resultQ->fetch_assoc()) ? $row['marks'] : '-';
        $pdf->Cell(100, 8, $subject, 1);
        $pdf->Cell(90, 8, $mark, 1, 1);
        if (is_numeric($mark)) {
            $total += $mark;
            $count++;
        }
    }

    // Total
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(100, 8, "Total", 1);
    $pdf->Cell(90, 8, $count ? "$total / " . ($count * 100) : '-', 1, 1);

    // Grade
    $avg = $count ? ($total / $count) : 0;
    $grade = '-';
    if ($avg >= 80) $grade = 'A';
    elseif ($avg >= 70) $grade = 'B';
    elseif ($avg >= 60) $grade = 'C';
    elseif ($avg >= 50) $grade = 'D';
    elseif ($avg > 0) $grade = 'E';

    $pdf->Cell(100, 8, "Average Grade", 1);
    $pdf->Cell(90, 8, $grade, 1, 1);
    $pdf->Ln(10);
}

// Output PDF
$pdf->Output("D", "Class_{$class}_{$stream}_Term{$term}_{$year}_Results.pdf");
exit;
?>
