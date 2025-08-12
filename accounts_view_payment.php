<?php
session_start();
$conn = new mysqli("localhost", "root", "", "school");

// AJAX student fetch
if (isset($_GET['ajax']) && $_GET['ajax'] == 'student_info' && isset($_GET['admission_no'])) {
    $adm = $conn->real_escape_string($_GET['admission_no']);
    $res = $conn->query("SELECT * FROM students_admitted WHERE admission_no = '$adm'");
    header('Content-Type: application/json');
    echo json_encode($res->num_rows > 0 ? $res->fetch_assoc() : null);
    exit;
}

$history = [];
$totals = ['total_fee' => 0, 'total_paid' => 0, 'total_balance' => 0];
$message = "";
$admission_no = "";

$student_details = [
    'full_name' => '',
    'class' => '',
    'stream' => '',
    'gender' => '',
    'parent_contact' => '',
    'profile_photo' => ''
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $admission_no = $conn->real_escape_string($_POST['admission_no']);

    // Fetch student details
    $sres = $conn->query("SELECT * FROM students_admitted WHERE admission_no = '$admission_no'");
    if ($sres->num_rows > 0) {
        $student_details = $sres->fetch_assoc();
    } else {
        $message = "<div class='error'>❌ Student not found.</div>";
    }

    $result = $conn->query("
        SELECT DISTINCT class, term, year 
        FROM fee_payments 
        WHERE admission_no = '$admission_no'
        ORDER BY year ASC, term ASC
    ");

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $class = $row['class'];
            $term = $row['term'];
            $year = $row['year'];

            $fee_res = $conn->query("SELECT amount FROM fee_structure WHERE class = '$class' AND term = $term AND year = $year LIMIT 1");
            $fee_required = ($fee_res->num_rows > 0) ? $fee_res->fetch_assoc()['amount'] : 0;

            $paid_res = $conn->query("SELECT SUM(amount_paid) AS total_paid FROM fee_payments WHERE admission_no = '$admission_no' AND class = '$class' AND term = $term AND year = $year");
            $paid_amount = ($paid_res->num_rows > 0) ? $paid_res->fetch_assoc()['total_paid'] : 0;

            $balance = $fee_required - $paid_amount;

            $history[] = [
                'year' => $year,
                'term' => $term,
                'class' => $class,
                'fee_required' => $fee_required,
                'amount_paid' => $paid_amount,
                'balance' => $balance
            ];

            $totals['total_fee'] += $fee_required;
            $totals['total_paid'] += $paid_amount;
            $totals['total_balance'] += $balance;
        }
    } else {
        $message = "<div class='error'>❌ No payment history found.</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Accounts | Student Fee History</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; background: #f4f4f4; }

        .sidebar {
            width: 240px;
            background: #002b5c;
            color: white;
            padding-top: 20px;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            overflow-y: auto;
            transition: 0.3s ease;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 22px;
            font-weight: bold;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            transition: background 0.2s;
            font-size: 15px;
        }

        .sidebar a:hover {
            background-color: #014a99;
        }

        .main {
            margin-left: 220px;
            padding: 30px;
        }

        .topbar {
            background: white;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 5px #ccc;
        }

        .topbar h1 {
            margin: 0;
            font-size: 20px;
            color: #003366;
        }

        .logout {
            background: #cc0000;
            color: white;
            padding: 8px 12px;
            border-radius: 5px;
            text-decoration: none;
        }

        form {
            max-width: 600px;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 8px rgba(0,0,0,0.1);
        }

        label { font-weight: bold; display: block; margin-top: 10px; }
        input, button {
            width: 100%; padding: 10px; margin-top: 10px;
            border-radius: 5px; border: 1px solid #ccc;
        }

        button {
            background: #007bff; color: white;
            font-size: 16px; cursor: pointer;
        }

        .error {
            background: #f8d7da; color: #721c24;
            padding: 10px; margin-top: 10px;
            border-radius: 5px; text-align: center;
        }

        .student-card {
            display: flex; align-items: center;
            gap: 20px; margin-top: 20px;
            border: 1px solid #ccc; padding: 15px;
            background: #fdfdfd; border-radius: 10px;
        }

        .student-card img {
            width: 100px; height: 100px;
            object-fit: cover; border-radius: 50%;
            border: 2px solid #007bff;
        }

        .table-section {
            margin-top: 40px;
            max-width: 100%;
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 8px rgba(0,0,0,0.1);
        }

        table {
            width: 100%; border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px; border-bottom: 1px solid #ddd; text-align: center;
        }

        th {
            background-color: #28a745; color: white;
        }

        .summary {
            font-weight: bold;
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }

        @media (max-width: 768px) {
            .sidebar { width: 100%; height: auto; position: relative; }
            .main { margin-left: 0; }
        }
    </style>
    <script>
        function fetchStudentDetails() {
            const adm = document.getElementById('admission_no').value.trim();
            if (adm.length < 3) return;

            fetch("?ajax=student_info&admission_no=" + encodeURIComponent(adm))
                .then(res => res.json())
                .then(data => {
                    if (data) {
                        document.getElementById("full_name").value = data.full_name;
                        document.getElementById("class").value = data.class;
                        document.getElementById("stream").value = data.stream;
                        document.getElementById("gender").value = data.gender;
                        document.getElementById("parent_contact").value = data.parent_contact;
                        document.getElementById("profile_photo").src = data.profile_photo !== "" ? data.profile_photo : "default-avatar.png";
                        document.getElementById("info_card").style.display = "flex";
                    } else {
                        document.getElementById("info_card").style.display = "none";
                    }
                });
        }
    </script>
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


<div class="main">
    <div class="topbar">
        <h1>📑 View Student Fee History</h1>
        <a href="admin_logout.php" class="logout">Logout</a>
    </div>

    <form method="POST">
        <?php if (!empty($message)) echo $message; ?>
        <label>Admission Number:</label>
        <input type="text" name="admission_no" id="admission_no" onkeyup="fetchStudentDetails()" value="<?= htmlspecialchars($admission_no) ?>" required placeholder="e.g. ADM00123">

        <div id="info_card" class="student-card" style="<?= $student_details['full_name'] ? 'display:flex;' : 'display:none;' ?>">
            <img id="profile_photo" src="<?= $student_details['profile_photo'] ?: 'default-avatar.png' ?>" alt="Profile">
            <div style="flex:1;">
                <label>Full Name:</label>
                <input type="text" id="full_name" value="<?= $student_details['full_name'] ?>" readonly>

                <label>Class:</label>
                <input type="text" id="class" value="<?= $student_details['class'] ?>" readonly>

                <label>Stream:</label>
                <input type="text" id="stream" value="<?= $student_details['stream'] ?>" readonly>

                <label>Gender:</label>
                <input type="text" id="gender" value="<?= $student_details['gender'] ?>" readonly>

                <label>Parent Contact:</label>
                <input type="text" id="parent_contact" value="<?= $student_details['parent_contact'] ?>" readonly>
            </div>
        </div>

        <button type="submit">📊 View Full History</button>
    </form>

    <?php if (!empty($history)): ?>
        <div class="table-section">
            <h3>📄 Fee Payment History for <?= htmlspecialchars($admission_no) ?></h3>
            <table>
                <tr>
                    <th>Year</th>
                    <th>Term</th>
                    <th>Class</th>
                    <th>Fee Required</th>
                    <th>Amount Paid</th>
                    <th>Balance</th>
                </tr>
                <?php foreach ($history as $entry): ?>
                    <tr>
                        <td><?= $entry['year'] ?></td>
                        <td>Term <?= $entry['term'] ?></td>
                        <td><?= $entry['class'] ?></td>
                        <td>KES <?= number_format($entry['fee_required'], 2) ?></td>
                        <td>KES <?= number_format($entry['amount_paid'], 2) ?></td>
                        <td>KES <?= number_format($entry['balance'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>

            <div class="summary">
                TOTAL REQUIRED: KES <?= number_format($totals['total_fee'], 2) ?> &nbsp; | &nbsp;
                TOTAL PAID: KES <?= number_format($totals['total_paid'], 2) ?> &nbsp; | &nbsp;
                BALANCE: KES <?= number_format($totals['total_balance'], 2) ?>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
