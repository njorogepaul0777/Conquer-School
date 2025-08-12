<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$status = $_GET['status'] ?? '';
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$applied_class = $_GET['applied_class'] ?? '';

$where = "WHERE 1";
if ($status) $where .= " AND status = '" . $conn->real_escape_string($status) . "'";
if ($applied_class) $where .= " AND applied_class = '" . $conn->real_escape_string($applied_class) . "'";
if ($from_date && $to_date) $where .= " AND created_at BETWEEN '$from_date' AND '$to_date'";

$query = $conn->query("SELECT * FROM students_admitted $where ORDER BY created_at DESC");

$summary = [];
foreach (["Pending", "Approved", "Rejected"] as $s) {
    $res = $conn->query("SELECT COUNT(*) AS count FROM students_admitted WHERE status = '$s'");
    $summary[$s] = $res->fetch_assoc()['count'];
}
$summary['Total'] = array_sum($summary);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admissions Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
        }

        .layout {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }

        .sidebar {
            background-color: #343a40;
            color: white;
            height: 100vh;
            padding-top: 20px;
            position: sticky;
            top: 0;
        }

        .sidebar h4 {
            text-align: center;
            margin-bottom: 20px;
        }

        .sidebar a {
            display: block;
            padding: 12px 20px;
            color: white;
            text-decoration: none;
        }

        .sidebar a:hover {
            background-color: #495057;
        }

        .main-content {
            padding: 30px;
        }

        .card {
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.05);
        }

        .form-select, .form-control, .btn {
            box-shadow: none !important;
        }

        @media print {
            .sidebar, .btn, .form-select, .form-control, form {
                display: none !important;
            }
            .main-content {
                padding: 0;
            }
        }
    </style>
</head>
<body>
<div class="layout">
    <!-- Sidebar -->
    <div class="sidebar">
        <h4>Admin Panel</h4>
        <a href="dashboard.php">Dashboard</a>
        <a href="admissions.php">Admissions</a>
        <a href="report_admissions.php">Reports</a>
        <a href="logout.php">Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h2 class="mb-4">Admissions Report</h2>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <?php foreach ($summary as $key => $value): ?>
            <div class="col-md-3 mb-3">
                <div class="card text-white <?= $key == 'Approved' ? 'bg-success' : ($key == 'Rejected' ? 'bg-danger' : ($key == 'Pending' ? 'bg-warning text-dark' : 'bg-primary')) ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= $key ?></h5>
                        <p class="card-text fs-4"><?= $value ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Filters -->
        <form method="get" class="row g-3 mb-4">
            <div class="col-md-3">
                <label>Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option <?= $status == 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option <?= $status == 'Approved' ? 'selected' : '' ?>>Approved</option>
                    <option <?= $status == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-3">
                <label>Applied Class</label>
                <input type="text" name="applied_class" class="form-control" value="<?= htmlspecialchars($applied_class) ?>">
            </div>
            <div class="col-md-3">
                <label>From Date</label>
                <input type="date" name="from_date" class="form-control" value="<?= $from_date ?>">
            </div>
            <div class="col-md-3">
                <label>To Date</label>
                <input type="date" name="to_date" class="form-control" value="<?= $to_date ?>">
            </div>

            <div class="col-md-2">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
            <div class="col-md-2">
                <label>&nbsp;</label>
                <a href="export_admissions_excel.php?<?= http_build_query($_GET) ?>" class="btn btn-success w-100">Export Excel</a>
            </div>
            <div class="col-md-2">
                <label>&nbsp;</label>
                <a href="export_admissions_pdf.php?<?= http_build_query($_GET) ?>" class="btn btn-danger w-100">Export PDF</a>
            </div>
            <div class="col-md-2">
                <label>&nbsp;</label>
                <button type="button" onclick="window.print()" class="btn btn-secondary w-100">Print</button>
            </div>
        </form>

        <!-- Data Table -->
        <div class="table-responsive">
            <table id="admissionsTable" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Applied Class</th>
                        <th>Status</th>
                        <th>Application Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; while ($row = $query->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                        <td><?= htmlspecialchars($row['applied_class']) ?></td>
                        <td><span class="badge bg-<?= $row['status'] == 'Approved' ? 'success' : ($row['status'] == 'Rejected' ? 'danger' : 'warning text-dark') ?>"><?= $row['status'] ?></span></td>
                        <td><?= $row['created_at'] ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function () {
        $('#admissionsTable').DataTable();
    });
</script>
</body>
</html>
