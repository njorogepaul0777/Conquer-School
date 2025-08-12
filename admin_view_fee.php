<?php
session_start();
$conn = new mysqli("localhost", "root", "", "school");

// Restrict access to accounts role
if (!isset($_SESSION['staff_role']) || $_SESSION['staff_role'] !== 'accounts') {
    header("Location: ../login.php");
    exit();
}

// Handle deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM fee_structure WHERE id = $id");
    header("Location: admin_add_fee_structure.php");
    exit();
}

// Handle update
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id = intval($_POST['edit_id']);
    $class = $_POST['class'];
    $term = $_POST['term'];
    $year = $_POST['year'];
    $amount = $_POST['amount'];

    $stmt = $conn->prepare("UPDATE fee_structure SET class=?, term=?, year=?, amount=? WHERE id=?");
    $stmt->bind_param("siidi", $class, $term, $year, $amount, $id);

    if ($stmt->execute()) {
        $message = "<div class='success'>✅ Fee structure updated successfully!</div>";
    } else {
        $message = "<div class='error'>❌ Update failed: " . $stmt->error . "</div>";
    }
}

// Fetch fee structures
$result = $conn->query("SELECT * FROM fee_structure ORDER BY year DESC, term ASC, class ASC");
$edit_mode = isset($_GET['edit']) ? intval($_GET['edit']) : null;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Accounts - Manage Fee Structure</title>
    <link rel="stylesheet" href="admin_style.css">
    <style>
        .main { padding: 30px; }

        .success, .error {
            max-width: 600px;
            margin: 10px auto;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 0 8px #ccc;
        }
        th, td {
            padding: 12px;
            text-align: center;
            border: 1px solid #ddd;
        }
        th { background-color: #004080; color: white; }

        input, select {
            width: 100%;
            padding: 6px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .actions a, .actions button {
            margin: 2px;
            padding: 6px 10px;
            font-size: 14px;
            border-radius: 4px;
            text-decoration: none;
            cursor: pointer;
        }

        .edit-btn { background: #007bff; color: white; border: none; }
        .delete-btn { background: #dc3545; color: white; border: none; }
        .save-btn { background: #28a745; color: white; border: none; }

        .sidebar {
            width: 200px;
            background: #2c3e50;
            padding: 20px;
            position: fixed;
            height: 100%;
            color: white;
        }

        .sidebar h2 {
            font-size: 20px;
            margin-bottom: 20px;
        }

        .sidebar a {
            display: block;
            padding: 10px;
            margin: 8px 0;
            color: white;
            text-decoration: none;
            background: #34495e;
            border-radius: 4px;
        }

        .sidebar a:hover {
            background: #1abc9c;
        }

        .main {
            margin-left: 220px;
            padding: 20px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logout {
            background: #e74c3c;
            color: white;
            padding: 8px 14px;
            border-radius: 4px;
            text-decoration: none;
        }

        .logout:hover {
            background: #c0392b;
        }
    </style>

    <script>
        function confirmDelete(id) {
            if (confirm("Are you sure you want to delete this fee structure?")) {
                window.location.href = "admin_add_fee_structure.php?delete=" + id;
            }
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
<!-- Main -->
<div class="main">
    <div class="topbar">
        <h1>💵 Manage Fee Structures</h1>
        <a href="admin_logout.php" class="logout">Logout</a>
    </div>

    <?= $message ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Class</th>
                <th>Term</th>
                <th>Year</th>
                <th>Amount (KES)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <?php if ($edit_mode === $row['id']) { ?>
                    <form method="POST">
                        <td><?= $row['id']; ?><input type="hidden" name="edit_id" value="<?= $row['id']; ?>"></td>
                        <td>
                            <select name="class" required>
                                <?php foreach (['Form 1', 'Form 2', 'Form 3', 'Form 4'] as $c): ?>
                                    <option <?= $row['class'] === $c ? 'selected' : '' ?>><?= $c ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <select name="term" required>
                                <option value="1" <?= $row['term'] == 1 ? 'selected' : '' ?>>1</option>
                                <option value="2" <?= $row['term'] == 2 ? 'selected' : '' ?>>2</option>
                                <option value="3" <?= $row['term'] == 3 ? 'selected' : '' ?>>3</option>
                            </select>
                        </td>
                        <td><input type="number" name="year" value="<?= $row['year'] ?>" required></td>
                        <td><input type="number" step="0.01" name="amount" value="<?= $row['amount'] ?>" required></td>
                        <td class="actions">
                            <button type="submit" class="save-btn">Save</button>
                            <a href="admin_add_fee_structure.php" class="delete-btn">Cancel</a>
                        </td>
                    </form>
                <?php } else { ?>
                    <td><?= $row['id']; ?></td>
                    <td><?= $row['class']; ?></td>
                    <td><?= $row['term']; ?></td>
                    <td><?= $row['year']; ?></td>
                    <td><?= number_format($row['amount'], 2); ?></td>
                    <td class="actions">
                        <a href="?edit=<?= $row['id'] ?>" class="edit-btn">Edit</a>
                        <button onclick="confirmDelete(<?= $row['id'] ?>)" class="delete-btn">Delete</button>
                    </td>
                <?php } ?>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

</body>
</html>
