<?php
// Connect to database
$conn = new mysqli("localhost", "root", "", "school");
$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and collect form inputs
    $admission_no = strtoupper(trim($_POST['admission_no']));
    $class = $_POST['class'];
    $term = $_POST['term'];
    $year = $_POST['year'];
    $amount_paid = $_POST['amount_paid'];
    $payment_date = $_POST['payment_date'];
    $payment_mode = $_POST['payment_mode'];
    $reference_no = $_POST['reference_no'];
    $confirmed_by = $_POST['confirmed_by'];
    $remarks = $_POST['remarks'];

    // Prepare and execute SQL insert
    $stmt = $conn->prepare("INSERT INTO fee_payments 
        (admission_no, class, term, year, amount_paid, payment_date, payment_mode, reference_no, confirmed_by, remarks)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->bind_param("ssiidsssss", 
        $admission_no, $class, $term, $year, $amount_paid,
        $payment_date, $payment_mode, $reference_no, $confirmed_by, $remarks
    );

    if ($stmt->execute()) {
        $last_id = $conn->insert_id;
        $message = "
            <div class='success'>
                ✅ Payment recorded successfully.<br><br>
                <a href='print_receipt.php?payment_id=$last_id' target='_blank'>
                    <button type='button' class='print-button'>🧾 Print Receipt</button>
                </a>
            </div>
        ";
    } else {
        $message = "<div class='error'>❌ Error: " . $stmt->error . "</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Accounts - Record Student Payment</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
        }

        /* Sidebar */
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



        /* Main Content */
        .main {
            margin-left: 240px;
            padding: 40px 20px;
        }

        form {
            max-width: 700px;
            background: white;
            margin: auto;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: white;
        }

        label {
            font-weight: bold;
            display: block;
            margin-top: 15px;
        }

        input, select, textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 15px;
        }

        button, .print-button {
            margin-top: 20px;
            width: 100%;
            background: #28a745;
            color: white;
            font-size: 16px;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .print-button {
            background: #007bff;
        }

        .success, .error {
            text-align: center;
            margin-bottom: 15px;
            padding: 12px;
            border-radius: 5px;
        }

        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<!-- Sidebar -->
<aside class="sidebar">
    <div>
        <h2>💼 Accounts Pannel</h2>
        <nav>
            <a href="accounts_dashboard.php" class="active">📊 Dashboard</a>
            <a href="admin_add_fee_structure.php">💵 Fee Structure</a>
            <a href="admin_add_payment.php">➕ Record Payment</a>
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

<!-- Main Content Area -->
<div class="main">
    <form method="POST">
        <h2>Record Student Payment</h2>

        <!-- Display success or error message -->
        <?php if (!empty($message)) echo $message; ?>

        <!-- Form Inputs -->
        <label>Admission Number:</label>
        <input type="text" name="admission_no" required>

        <label>Class:</label>
        <select name="class" required>
            <option value="">--Select Class--</option>
            <option>Form 1</option>
            <option>Form 2</option>
            <option>Form 3</option>
            <option>Form 4</option>
        </select>

        <label>Term:</label>
        <select name="term" required>
            <option value="">--Select Term--</option>
            <option value="1">Term 1</option>
            <option value="2">Term 2</option>
            <option value="3">Term 3</option>
        </select>

        <label>Year:</label>
        <input type="number" name="year" value="<?= date("Y"); ?>" required>

        <label>Amount Paid (KES):</label>
        <input type="number" step="0.01" name="amount_paid" required>

        <label>Payment Date:</label>
        <input type="date" name="payment_date" value="<?= date('Y-m-d'); ?>" required>

        <label>Payment Mode:</label>
        <select name="payment_mode" required>
            <option value="">--Select Mode--</option>
            <option value="M-Pesa">M-Pesa</option>
            <option value="Bank Deposit">Bank Deposit</option>
            <option value="Bank Transfer">Bank Transfer</option>
            <option value="Cheque">Cheque</option>
        </select>

        <label>Reference Number:</label>
        <input type="text" name="reference_no" required>

        <label>Confirmed By (Admin/Clerk):</label>
        <input type="text" name="confirmed_by" placeholder="e.g., Admin John" required>

        <label>Remarks:</label>
        <textarea name="remarks" rows="3" placeholder="Optional notes..."></textarea>

        <!-- Submit Button -->
        <button type="submit">💾 Record Payment</button>
    </form>
</div>

</body>
</html>
