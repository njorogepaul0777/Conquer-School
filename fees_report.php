<?php
session_start();
$conn = new mysqli("localhost", "root", "", "school");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Filters
$classFilter = $_GET['class'] ?? '';
$fromDate = $_GET['from'] ?? '';
$toDate = $_GET['to'] ?? '';

// Summary queries
$totalStudents = $conn->query("SELECT COUNT(*) AS total FROM students_admitted")->fetch_assoc()['total'] ?? 0;
$totalPaid = $conn->query("SELECT SUM(amount_paid) AS total FROM fee_payments")->fetch_assoc()['total'] ?? 0;
$totalExpected = $conn->query("SELECT SUM(amount) AS total FROM fee_structure")->fetch_assoc()['total'] ?? 0;
$pending = $totalExpected - $totalPaid;
$totalPayments = $conn->query("SELECT COUNT(*) AS count FROM fee_payments")->fetch_assoc()['count'] ?? 0;

// Filters for WHERE clause
$where = "WHERE 1";
if (!empty($classFilter)) {
    $where .= " AND sa.class = '" . $conn->real_escape_string($classFilter) . "'";
}
if (!empty($fromDate) && !empty($toDate)) {
    $where .= " AND fp.payment_date BETWEEN '" . $conn->real_escape_string($fromDate) . "' AND '" . $conn->real_escape_string($toDate) . "'";
}

// Data fetch
$payments = $conn->query("
    SELECT fp.id, sa.full_name, sa.class, fp.amount_paid, fp.payment_date 
    FROM fee_payments fp 
    JOIN students_admitted sa ON fp.admission_no = sa.id 
    $where
    ORDER BY fp.payment_date DESC
");

$genderChartData = $conn->query("SELECT gender, COUNT(*) as count FROM students_admitted GROUP BY gender");
$genderLabels = []; $genderCounts = [];
while ($row = $genderChartData->fetch_assoc()) {
    $genderLabels[] = $row['gender'];
    $genderCounts[] = $row['count'];
}
$classList = $conn->query("SELECT DISTINCT class FROM students_admitted ORDER BY class ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fees Report - Conquer High School</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            background: #f4f6f8;
        }

        .sidebar {
            width: 240px;
            background: #2c3e50;
            color: white;
            height: 100vh;
            position: fixed;
        }

        .sidebar h2 {
            text-align: center;
            padding: 20px;
            margin: 0;
            font-size: 22px;
        }

        .sidebar nav a {
            display: block;
            padding: 15px 30px;
            color: white;
            text-decoration: none;
        }

        .sidebar nav a:hover,
        .sidebar nav a.active {
            background: #2980b9;
        }

        .main {
            margin-left: 240px;
            padding: 30px;
            flex: 1;
        }

        header {
            background: #3498db;
            color: white;
            padding: 20px;
            border-radius: 10px;
            font-size: 20px;
            margin-bottom: 20px;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.1);
            text-align: center;
        }

        .card h2 {
            font-size: 15px;
            color: #555;
        }

        .card p {
            font-size: 22px;
            font-weight: bold;
            color: #27ae60;
        }

        .filter-form {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: center;
        }

        .filter-form label {
            font-weight: bold;
            display: flex;
            flex-direction: column;
            font-size: 14px;
        }

        .filter-form select,
        .filter-form input[type="date"] {
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 14px;
            margin-top: 5px;
        }

        .filter-form button {
            padding: 10px 15px;
            background: #3498db;
            border: none;
            color: white;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            height: fit-content;
            margin-top: 22px;
        }

        .export-buttons {
            margin-bottom: 20px;
        }

        .export-buttons button {
            padding: 10px 15px;
            margin-right: 10px;
            border: none;
            border-radius: 6px;
            background: #3498db;
            color: white;
            cursor: pointer;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 1px 6px rgba(0,0,0,0.1);
        }

        table th, table td {
            padding: 12px;
            border: 1px solid #ccc;
            text-align: left;
        }

        table th {
            background: #ecf0f1;
        }

        canvas {
            max-width: 400px;
            margin: 30px 0;
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <h2>🧑‍💼 Admin Panel</h2>
    <nav>
        <a href="admin_dashboard.php">🏠 Dashboard</a>
        <a href="report_fees.php" class="active">📋 Fees Report</a>
        <a href="report_students.php">👨‍🎓 Students</a>
        <a href="report_results.php">📝 Results</a>
        <a href="report_attendance.php">📅 Attendance</a>
        <a href="report_teachers.php">👩‍🏫 Teachers</a>
    </nav>
</aside>

<div class="main">
    <header>💰 Fees Report</header>

    <form class="filter-form" method="get">
        <label>
            Filter by Class
            <select name="class">
                <option value="">-- All --</option>
                <?php while($c = $classList->fetch_assoc()): ?>
                    <option value="<?= $c['class'] ?>" <?= $classFilter == $c['class'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['class']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </label>

        <label>
            From Date
            <input type="date" name="from" value="<?= htmlspecialchars($fromDate) ?>">
        </label>

        <label>
            To Date
            <input type="date" name="to" value="<?= htmlspecialchars($toDate) ?>">
        </label>

        <button type="submit">Apply Filters</button>
    </form>

    <div class="summary">
        <div class="card"><h2>Total Students</h2><p><?= number_format($totalStudents) ?></p></div>
        <div class="card"><h2>Total Collected</h2><p>KES <?= number_format($totalPaid) ?></p></div>
        <div class="card"><h2>Total Expected</h2><p>KES <?= number_format($totalExpected) ?></p></div>
        <div class="card"><h2>Pending Balance</h2><p>KES <?= number_format($pending) ?></p></div>
        <div class="card"><h2>Payments</h2><p><?= $totalPayments ?></p></div>
    </div>

    <div class="export-buttons">
        <button onclick="exportTableToExcel('feesTable')">Export to Excel</button>
        <button onclick="printTable()">Print</button>
    </div>

    <canvas id="genderChart"></canvas>

    <table id="feesTable">
        <thead>
            <tr>
                <th>#</th>
                <th>Student Name</th>
                <th>Class</th>
                <th>Amount Paid</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; while ($row = $payments->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['class']) ?></td>
                    <td>KES <?= number_format($row['amount_paid']) ?></td>
                    <td><?= htmlspecialchars($row['payment_date']) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
function exportTableToExcel(tableID) {
    let wb = XLSX.utils.table_to_book(document.getElementById(tableID), { sheet: "Sheet 1" });
    XLSX.writeFile(wb, "fees_report.xlsx");
}

function printTable() {
    const content = document.getElementById("feesTable").outerHTML;
    const style = '<style>table{width:100%;border-collapse:collapse}th,td{border:1px solid #ccc;padding:10px;text-align:left}</style>';
    const win = window.open('', '', 'height=700,width=900');
    win.document.write('<html><head><title>Print Report</title>' + style + '</head><body>');
    win.document.write(content);
    win.document.write('</body></html>');
    win.document.close();
    win.print();
}

// Gender Chart
new Chart(document.getElementById('genderChart'), {
    type: 'pie',
    data: {
        labels: <?= json_encode($genderLabels) ?>,
        datasets: [{
            label: 'Gender Distribution',
            data: <?= json_encode($genderCounts) ?>,
            backgroundColor: ['#3498db', '#e74c3c', '#9b59b6']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' },
            title: { display: true, text: 'Gender Distribution of Students' }
        }
    }
});
</script>
</body>
</html>
