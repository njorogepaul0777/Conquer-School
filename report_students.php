<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Filters
$genderFilter = $conn->real_escape_string($_GET['gender'] ?? '');
$formFilter = $conn->real_escape_string($_GET['form'] ?? '');
$startDate = $conn->real_escape_string($_GET['start_date'] ?? '');
$endDate = $conn->real_escape_string($_GET['end_date'] ?? '');
$admissionNoFilter = $conn->real_escape_string($_GET['admission_no'] ?? '');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Base query
$searchQuery = "SELECT * FROM students_admitted WHERE status = 1";
if ($genderFilter !== '') $searchQuery .= " AND gender = '$genderFilter'";
if ($formFilter !== '') $searchQuery .= " AND class = '$formFilter'";
if ($startDate && $endDate) $searchQuery .= " AND created_at BETWEEN '$startDate' AND '$endDate'";
if ($admissionNoFilter !== '') $searchQuery .= " AND admission_no LIKE '%$admissionNoFilter%'";

$totalRows = $conn->query($searchQuery)->num_rows;
$totalPages = ceil($totalRows / $limit);
$searchQuery .= " LIMIT $limit OFFSET $offset";
$studentsResult = $conn->query($searchQuery);

// Chart data
$genderChartData = [];
$chartQuery = "SELECT gender, COUNT(*) as count FROM students_admitted WHERE status = 1";
if ($genderFilter !== '') $chartQuery .= " AND gender = '$genderFilter'";
if ($formFilter !== '') $chartQuery .= " AND class = '$formFilter'";
$genderRes = $conn->query("$chartQuery GROUP BY gender");
while ($row = $genderRes->fetch_assoc()) {
    $genderChartData[$row['gender']] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Total Students Report</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; display: flex; min-height: 100vh; background: #f4f6f8; }

        .sidebar {
            width: 220px;
            background: #002b5c;
            color: white;
            padding-top: 20px;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            overflow-y: auto;
        }

        .sidebar h2 { text-align: center; font-size: 18px; margin-bottom: 20px; }
        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            font-size: 14px;
        }

        .sidebar a:hover, .sidebar a.active { background: #014a99; }

        .main-content {
            margin-left: 220px;
            padding: 20px;
            flex: 1;
        }

        h1 { font-size: 22px; margin-bottom: 10px; }

        .button {
            padding: 10px 14px;
            background: #007bff;
            color: white;
            border: none;
            margin: 5px 5px 10px 0;
            border-radius: 5px;
            cursor: pointer;
        }

        .button:hover { background: #0056b3; }

        .pagination a {
            padding: 6px 10px;
            margin: 0 2px;
            background: #fff;
            border: 1px solid #007bff;
            color: #007bff;
            text-decoration: none;
            border-radius: 3px;
        }

        .pagination a.active, .pagination a:hover {
            background: #007bff;
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ccc;
            text-align: left;
        }

        th {
            background: #007bff;
            color: white;
        }

        tr:nth-child(even) { background: #f2f2f2; }

        .filter-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .filter-form {
            background: white;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 400px;
        }

        .filter-form input, .filter-form select {
            width: 100%;
            padding: 8px;
            margin: 8px 0;
        }

        @media print {
            .sidebar, .button, .pagination, .filter-btn { display: none !important; }
        }
    </style>
    <script>
        function toggleFilters() {
            document.getElementById("filter-overlay").style.display = 'flex';
        }

        function closeFilters() {
            document.getElementById("filter-overlay").style.display = 'none';
        }

        function exportTableToExcel(tableID, filename = '') {
            const dataType = 'application/vnd.ms-excel';
            const tableSelect = document.getElementById(tableID);
            const tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
            const downloadLink = document.createElement("a");
            document.body.appendChild(downloadLink);
            downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
            downloadLink.download = filename || 'report.xls';
            downloadLink.click();
        }

        function exportTableToPDF() {
            const win = window.open('', '', 'height=700,width=900');
            win.document.write('<html><head><title>Students Report</title>');
            win.document.write('<style>table {width: 100%; border-collapse: collapse;} th, td {border: 1px solid #ddd; padding: 8px;}</style></head><body>');
            win.document.write(document.getElementById("studentsTable").outerHTML);
            win.document.write('</body></html>');
            win.document.close();
            win.print();
        }
    </script>
</head>
<body>

<!-- Sidebar -->
<nav class="sidebar">
    <h4 class="text-center">Reports Menu</h4>
    <a href="admin_dashboard.php">Dashboard</a>
    <a href="total_students_report.php">Total Students</a>
    <a href="fees_report.php">Fees Report</a>
    <a href="performance_report.php">Performance Report</a>
    <a href="attendance_report.php">Attendance Report</a>
</nav>

<div class="main-content">
    <h1>🧑‍🎓 Total Students Report</h1>
    <button class="button" onclick="window.print()">🖨️ Print</button>
    <button class="button" onclick="exportTableToExcel('studentsTable', 'students_report')">📊 Excel</button>
    <button class="button" onclick="exportTableToPDF()">📄 PDF</button>
    <button class="button" onclick="toggleFilters()">🔍 Filters</button>

    <table id="studentsTable">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Admission No.</th>
                <th>Gender</th>
                <th>Form</th>
                <th>Admission Date</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($studentsResult->num_rows > 0):
                $i = $offset + 1;
                while ($student = $studentsResult->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($student['full_name']) ?></td>
                        <td><?= htmlspecialchars($student['admission_no']) ?></td>
                        <td><?= htmlspecialchars($student['gender']) ?></td>
                        <td><?= htmlspecialchars($student['class']) ?></td>
                        <td><?= htmlspecialchars($student['created_at']) ?></td>
                    </tr>
                <?php endwhile;
            else: ?>
                <tr><td colspan="6">No records found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>

    <h3>📈 Gender Distribution</h3>
    <canvas id="genderChart" style="max-width: 400px;"></canvas>
    <script>
        new Chart(document.getElementById("genderChart"), {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_keys($genderChartData)) ?>,
                datasets: [{
                    label: 'Gender Distribution',
                    data: <?= json_encode(array_values($genderChartData)) ?>,
                    backgroundColor: ['#36A2EB', '#FF6384']
                }]
            }
        });
    </script>
</div>

<!-- Filter Overlay -->
<div id="filter-overlay" class="filter-overlay" onclick="closeFilters()">
    <form class="filter-form" method="GET" onclick="event.stopPropagation()">
        <h3>Filter Students</h3>
        <label>Gender</label>
        <select name="gender">
            <option value="">All</option>
            <option value="Male" <?= $genderFilter == 'Male' ? 'selected' : '' ?>>Male</option>
            <option value="Female" <?= $genderFilter == 'Female' ? 'selected' : '' ?>>Female</option>
        </select>

        <label>Form</label>
        <select name="form">
            <option value="">All</option>
            <?php foreach (['Form 1', 'Form 2', 'Form 3', 'Form 4'] as $form): ?>
                <option value="<?= $form ?>" <?= $formFilter == $form ? 'selected' : '' ?>><?= $form ?></option>
            <?php endforeach; ?>
        </select>

        <label>Admission No.</label>
        <input type="text" name="admission_no" value="<?= htmlspecialchars($admissionNoFilter) ?>">

        <label>Start Date</label>
        <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>">

        <label>End Date</label>
        <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>">

        <button type="submit" class="button">Apply Filters</button>
        <button type="button" class="button" onclick="closeFilters()">Close</button>
    </form>
</div>

</body>
</html>
