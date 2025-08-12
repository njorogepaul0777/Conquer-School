<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "school");

// Handle form submission
$message = "";
$current_year = date("Y");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class = $_POST['class'];
    $term = $_POST['term'];
    $year = $_POST['year'];
    $amount = $_POST['amount'];

    // Prevent duplicate entry
    $check = $conn->prepare("SELECT * FROM fee_structure WHERE class = ? AND term = ? AND year = ?");
    if ($check === false) {
        die("Check failed: " . $conn->error);
    }
    $check->bind_param("sii", $class, $term, $year);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $message = "<div class='error'>Fee structure for $class, Term $term, $year already exists.</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO fee_structure (class, term, year, amount) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siid", $class, $term, $year, $amount);
        if ($stmt->execute()) {
            $message = "<div class='success'>✅ Fee Structure Added Successfully!</div>";
        } else {
            $message = "<div class='error'>❌ Error: " . $stmt->error . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Accounts-Add Fee Structure</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f1f1f1;
            padding: 40px;
        }

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


        h2 {
            text-align: center;
            color: #333;
            margin-left: 220px;
        }

        form {
            background: #fff;
            padding: 25px 30px;
            max-width: 500px;
            margin: 20px auto  auto;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        input, select, button {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 16px;
        }

        button {
            background: #28a745;
            color: white;
            font-weight: bold;
            margin-top: 20px;
            cursor: pointer;
            border: none;
        }

        button:hover {
            background: #218838;
        }

        .button-link {
            display: inline-block;
            margin-top: 10px;
            text-align: center;
            background: #007bff;
            color: white;
            padding: 10px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }

        .button-link:hover {
            background: #0056b3;
        }

        .success, .error {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            max-width: 500px;
            text-align: center;
            margin-left: auto;
            margin-right: auto;
        }

        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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

<h2>Add Fee Structure</h2>

<?php if (!empty($message)) echo $message; ?>

<form method="POST">
    <label>Class:</label>
    <select name="class" required>
        <option value="">---Select Class---</option>
        <option>Form 1</option>
        <option>Form 2</option>
        <option>Form 3</option>
        <option>Form 4</option>
    </select>

    <label>Term:</label>
    <select name="term" required>
        <option value="">---Select Term---</option>
        <option value="1">Term 1</option>
        <option value="2">Term 2</option>
        <option value="3">Term 3</option>
    </select>

    <label>Year:</label>
    <input type="number" name="year" value="<?= $current_year ?>" required>

    <label>Amount (KES):</label>
    <input type="number" name="amount" step="0.01" required>

    <button type="submit">Add Fee Structure</button>
    <a href="admin_view_fee.php" class="button-link">📋 View Fee Structure</a>
</form>

</body>
</html>
