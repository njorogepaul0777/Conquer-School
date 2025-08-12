<?php
// Connect to database
$conn = new mysqli("localhost", "root", "", "school");

// Ensure payment ID is provided
if (!isset($_GET['payment_id'])) {
    die("❌ Payment ID not provided.");
}

$payment_id = intval($_GET['payment_id']);

// Fetch payment using prepared statement
$stmt = $conn->prepare("SELECT * FROM fee_payments WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("❌ Payment not found.");
}

$row = $result->fetch_assoc();
$admission_no = $row['admission_no'];
$class = $row['class'];
$term = $row['term'];
$year = $row['year'];

// Get student info
$student_q = $conn->prepare("SELECT full_name, profile_photo FROM students_admitted WHERE admission_no = ? LIMIT 1");
$student_q->bind_param("s", $admission_no);
$student_q->execute();
$student_result = $student_q->get_result();
$student = ($student_result->num_rows > 0) ? $student_result->fetch_assoc() : ['full_name' => 'Unknown', 'profile_photo' => 'profile_photo'];

// Get fee structure and payments
$fee_structures = $conn->query("SELECT * FROM fee_structure WHERE class = '$class'");
$payments = $conn->query("SELECT * FROM fee_payments WHERE admission_no = '$admission_no' AND class = '$class'");

// Helper function
function termYearKey($term, $year) {
    return $year . '-' . str_pad($term, 2, '0', STR_PAD_LEFT);
}

// Organize fee structure by term-year
$fees_map = [];
while ($f = $fee_structures->fetch_assoc()) {
    $key = termYearKey($f['term'], $f['year']);
    $fees_map[$key] = $f['amount'];
}

// Organize payments by term-year
$payments_map = [];
while ($p = $payments->fetch_assoc()) {
    $key = termYearKey($p['term'], $p['year']);
    if (!isset($payments_map[$key])) $payments_map[$key] = 0;
    $payments_map[$key] += $p['amount_paid'];
}

// Calculate carry forward
$all_keys = array_unique(array_merge(array_keys($fees_map), array_keys($payments_map)));
sort($all_keys);

$carry_forward = 0;
$current_key = termYearKey($term, $year);
foreach ($all_keys as $key) {
    if ($key == $current_key) break;
    $paid = $payments_map[$key] ?? 0;
    $expected = $fees_map[$key] ?? 0;
    $carry_forward += ($paid - $expected);
}

// Calculate current values
$total_paid = $payments_map[$current_key] ?? 0;
$total_fee = $fees_map[$current_key] ?? 0;
$adjusted_fee = max(0, $total_fee - $carry_forward);
$balance = $adjusted_fee - $total_paid;

// Allocate paid amount to components
$allocated = [];
$breakdowns = $conn->query("SELECT component_name, percentage FROM fee_breakdown WHERE class = '$class' AND term = $term AND year = $year");
while ($b = $breakdowns->fetch_assoc()) {
    $component = $b['component_name'];
    $portion = ($b['percentage'] / 100) * $total_paid;
    $allocated[$component] = $portion;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Print Receipt</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f4f4; margin: 0; }
        .receipt { max-width: 900px; margin: 40px auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }

        .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #ccc; padding-bottom: 10px; }
        .header img { height: 80px; }
        .school-info { text-align: center; flex-grow: 1; }
        .school-info h2 { margin: 0; color: #007bff; }
        .school-info p { margin: 2px 0; font-size: 14px; }

        .student-section { display: flex; margin-top: 30px; }
        .student-photo { width: 120px; height: 120px; border-radius: 10px; object-fit: cover; border: 2px solid #007bff; }
        .student-details { margin-left: 20px; }
        .student-details p { margin: 5px 0; font-size: 16px; }

        table { width: 100%; margin-top: 30px; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid #ccc; text-align: left; }
        th { background: #007bff; color: white; }

        .summary { background: #f8f9fa; margin-top: 20px; padding: 15px; font-size: 16px; border-radius: 5px; }
        .footer { text-align: center; margin-top: 30px; font-style: italic; color: #333; }

        .print-btn { text-align: center; margin-top: 30px; }
        .print-btn button { background: #28a745; color: white; padding: 12px 20px; font-size: 16px; border: none; border-radius: 6px; cursor: pointer; }

        @media print { .print-btn { display: none; } }
    </style>
</head>
<body>

<div class="receipt">
    <div class="header">
        <img src="school_logo.png" alt="School Logo">
        <div class="school-info">
            <h2>Conquer High School</h2>
            <p>P.O. Box 12345 - 00100, Nairobi</p>
            <p>Tel: +254 712 345 678 | Email: info@conquerhigh.ac.ke</p>
        </div>
    </div>

    <div class="student-section">
        <img src="<?= htmlspecialchars($student['profile_photo']) ?>" class="student-photo" alt="Student Photo">
        <div class="student-details">
            <p><strong>Student Name:</strong> <?= htmlspecialchars($student['full_name']) ?></p>
            <p><strong>Admission No:</strong> <?= htmlspecialchars($admission_no) ?></p>
            <p><strong>Class:</strong> <?= htmlspecialchars($class) ?> | <strong>Term:</strong> <?= $term ?> | <strong>Year:</strong> <?= $year ?></p>
        </div>
    </div>

    <h3 style="margin-top:30px; color:#007bff;">💰 Fee Allocation Summary</h3>
    <table>
        <tr>
            <th>Component</th>
            <th>Expected (KES)</th>
            <th>Allocated (KES)</th>
            <th>Balance</th>
        </tr>
        <?php
        $breakdowns = $conn->query("SELECT component_name, percentage FROM fee_breakdown WHERE class = '$class' AND term = $term AND year = $year");
        while ($b = $breakdowns->fetch_assoc()):
            $component = $b['component_name'];
            $percent = $b['percentage'];
            $expected = ($percent / 100) * $total_fee;
            $paid = $allocated[$component] ?? 0;
            $diff = $expected - $paid;
        ?>
        <tr>
            <td><?= htmlspecialchars($component) ?></td>
            <td>KES <?= number_format($expected, 2) ?></td>
            <td>KES <?= number_format($paid, 2) ?></td>
            <td style="color: <?= $diff < 0 ? 'green' : 'red' ?>;">
                <?= number_format($diff, 2) ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>

    <div class="summary">
        <p><strong>Original Term Fee:</strong> KES <?= number_format($total_fee, 2) ?></p>
        <p><strong>Carry Forward:</strong> 
            <span style="color:<?= $carry_forward >= 0 ? 'green' : 'red' ?>;">
                <?= ($carry_forward >= 0 ? "+KES " : "-KES ") . number_format(abs($carry_forward), 2) ?>
            </span>
        </p>
        <p><strong>Adjusted Fee for This Term:</strong> KES <?= number_format($adjusted_fee, 2) ?></p>
        <p><strong>Paid This Term:</strong> KES <?= number_format($total_paid, 2) ?></p>
        <p><strong>Balance:</strong>
            <span style="color:<?= $balance < 0 ? 'green' : 'red' ?>;">
                <?= $balance < 0 ? "Overpaid by KES " . number_format(abs($balance), 2) : "KES " . number_format($balance, 2) ?>
            </span>
        </p>
    </div>

    <div class="footer">
        <p>Generated on: <?= date('Y-m-d H:i') ?></p>
        <p>Thank you for your payment!</p>
    </div>

    <div class="print-btn">
        <button onclick="window.print()">🖨 Print Receipt</button>
    </div>
</div>

</body>
</html>
