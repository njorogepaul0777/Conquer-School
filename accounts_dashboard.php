<?php
session_start();
$conn = new mysqli("localhost", "root", "", "school");

// Restrict access to 'accounts' staff only
if (!isset($_SESSION['staff_role']) || $_SESSION['staff_role'] !== 'accounts') {
    header("Location: ../login.php");
    exit();
}
//query to fetch information
$totalStudents = $conn->query("SELECT COUNT(*) AS total FROM students_admitted")->fetch_assoc()['total'] ?? 0;
$totalPaid = $conn->query("SELECT SUM(amount_paid) AS total FROM fee_payments")->fetch_assoc()['total'] ?? 0;
$totalExpected = $conn->query("SELECT SUM(amount) AS total FROM fee_structure")->fetch_assoc()['total'] ?? 0;
$pending = $totalExpected - $totalPaid;
$paymentCountQuery = $conn->query("SELECT COUNT(*) AS count FROM fee_payments");
$totalPayments = $paymentCountQuery ? $paymentCountQuery->fetch_assoc()['count'] ?? 0 : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Accounts Dashboard - Conquer High School</title>
    <style>
        :root {
            --primary: #2c3e50;
            --accent: #3498db;
            --accent-dark: #2980b9;
            --success: #27ae60;
            --warning: #f39c12;
            --light-bg: #f4f6f8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            background: var(--light-bg);
        }

        /* Sidebar */
        .sidebar {
            width: 240px;
            background: var(--primary);
            color: white;
            height: 100vh;
            padding: 20px 0;
            position: fixed;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .sidebar h2 {
            text-align: center;
            font-size: 22px;
            margin-bottom: 30px;
        }

        .sidebar nav a {
            display: block;
            padding: 14px 30px;
            color: white;
            text-decoration: none;
            font-size: 15px;
            transition: 0.3s;
        }

        .sidebar nav a:hover,
        .sidebar nav a.active {
            background: var(--accent-dark);
        }

        .profile {
            text-align: center;
            padding: 15px 0;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .profile small {
            font-size: 13px;
            color: #bdc3c7;
        }

        .main {
            margin-left: 240px;
            padding: 30px;
            width: calc(100% - 240px);
        }

        header {
            background: var(--accent);
            color: white;
            padding: 20px 30px;
            border-radius: 12px;
            font-size: 20px;
            font-weight: bold;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.2s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card h2 {
            font-size: 15px;
            color: #555;
            margin-bottom: 8px;
        }

        .card p {
            font-size: 22px;
            color: var(--success);
            font-weight: bold;
        }

        .nav-links {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .nav-links a {
            padding: 12px 20px;
            background: var(--accent);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            transition: 0.3s;
            box-shadow: 0 1px 4px rgba(0,0,0,0.1);
        }

        .nav-links a:hover {
            background: var(--accent-dark);
        }

        @media screen and (max-width: 768px) {
            .sidebar {
                display: none;
            }

            .main {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }

            header {
                font-size: 18px;
                padding: 15px;
            }

            .summary {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
    <div>
        <h2>💼 Accounts Pannel</h2>
        <nav>
            <a href="accounts_dashboard.php" class="active">📊 Dashboard</a>
            <a href="accounts_add_fee_structure.php">💵 Fee Structure</a>
            <a href="accounts_add_payment.php">➕ Record Payment</a>
            <a href="manage_fee_breakdowns.php">⚙️ Fee Breakdowns</a>
            <a href="accounts_view_payment.php">💳 Payment History</a>
        </nav>
    </div>
    <div class="profile">
        <strong><?= $_SESSION['staff_username'] ?? 'Accounts User' ?></strong><br>
        <small>Role: Accounts</small><br><br>
        <a href="teacher_logout.php" style="color: var(--warning); text-decoration: none;">🚪 Logout</a>
    </div>
</aside>

<!-- Main Content -->
<div class="main">
    <header>Accounts Dashboard - Conquer High School</header>

    <div class="summary">
        <div class="card">
            <h2>👨‍🎓 Total Students</h2>
            <p><?= number_format($totalStudents) ?></p>
        </div>
        <div class="card">
            <h2>💰 Total Collected</h2>
            <p>KES <?= number_format($totalPaid) ?></p>
        </div>
        <div class="card">
            <h2>📦 Total Expected</h2>
            <p>KES <?= number_format($totalExpected) ?></p>
        </div>
        <div class="card">
            <h2>⏳ Pending Balance</h2>
            <p>KES <?= number_format($pending) ?></p>
        </div>
        <div class="card">
            <h2>🧾 Total Transactions</h2>
            <p><?= $totalPayments ?></p>
        </div>
    </div>

    <div class="nav-links">
            <a href="accounts_add_fee_structure.php">💵 Fee Structure</a>
            <a href="accounts_add_payment.php">➕ Record Payment</a>
            <a href="manage_fee_breakdowns.php">⚙️ Fee Breakdowns</a>
            <a href="accounts_view_payment.php">💳 Payment History</a>
    </div>
</div>

</body>
</html>
