<?php
session_start();
if (!isset($_SESSION['staff_role']) || $_SESSION['staff_role'] !== 'accounts') {
    header("Location: ../login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "school");
$message = "";

// ADD breakdown
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_breakdown'])) {
    $class = $_POST['class'];
    $term = $_POST['term'];
    $year = $_POST['year'];
    $component = $_POST['component_name'];
    $percentage = $_POST['percentage'];

    $res = $conn->query("SELECT SUM(percentage) AS total FROM fee_breakdown WHERE class='$class' AND term= $term AND year=$year");
    if ($res === false){
        die ("res failed" . $conn->error);
    }
    $total = $res->fetch_assoc()['total'] ?? 0;
    if ($total + $percentage > 100){
        $message = "<p style='color:red;'>❌ Total Percentage for $class Term $term $year exceeds 100%.</p>";
    } else {
        $stmt = $conn->prepare("INSERT INTO fee_breakdown (class, term, year, component_name, percentage) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("siisd", $class, $term, $year, $component, $percentage);
        $stmt->execute();
        $message = "<p style='color:green;'>✅ Breakdown added successfully.</p>";
    }
}

// DELETE
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM fee_breakdown WHERE id = $id");
    header("Location: manage_fee_breakdowns.php");
    exit();
}

// UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_breakdown'])) {
    $id = $_POST['id'];
    $class = $_POST['class'];
    $term = $_POST['term'];
    $year = $_POST['year'];
    $component = $_POST['component_name'];
    $percentage = $_POST['percentage'];

    $res = $conn->query("SELECT SUM(percentage) AS total FROM fee_breakdown WHERE class='$class' AND term=$term AND year=$year AND id!=$id");
    $total = $res->fetch_assoc()['total'] ?? 0;
    if ($total + $percentage > 100){
        $message = "<p style='color:red;'>❌ Updating to $percentage% will exceed 100% for this set.</p>";
    } else {
        $stmt = $conn->prepare("UPDATE fee_breakdown SET component_name=?, percentage=? WHERE id=?");
        $stmt->bind_param("sdi", $component, $percentage, $id);
        $stmt->execute();
        $message = "<p style='color:green;'>✅ Breakdown updated successfully.</p>";
    }
}

// FETCH all
$all = $conn->query("SELECT * FROM fee_breakdown ORDER BY class, term, year, component_name");

$editing = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $edit_q = $conn->query("SELECT * FROM fee_breakdown WHERE id = $id");
    $editing = $edit_q->fetch_assoc();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Manage Fee Breakdowns</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        
 * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; display: flex; background: #f0f4f8; }

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
            width: calc(100% - 220px);
            background: #f4f4f4;
            min-height: 100vh;
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

        form, table {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px #ccc;
            max-width: 900px;
            margin: auto;
        }

        form label {
            font-weight: bold;
            display: block;
            margin-top: 15px;
        }

        form input, form select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        form button {
            margin-top: 20px;
            background: #007bff;
            color: white;
            padding: 10px;
            border: none;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }

        table {
            margin-top: 40px;
            border-collapse: collapse;
            width: 100%;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
        }

        th {
            background: #007bff;
            color: white;
        }

        .actions a {
            margin-right: 10px;
            text-decoration: none;
            color: #dc3545;
        }

        .message {
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
        }
    </style>
</head>
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
        <h1>💰 Manage Fee Breakdowns</h1>
        <a href="../logout.php" class="logout">Logout</a>
    </div>

    <?= $message ?>

    <form method="POST">
        <?php if ($editing): ?>
            <input type="hidden" name="update_breakdown" value="1">
            <input type="hidden" name="id" value="<?= $editing['id'] ?>">
        <?php else: ?>
            <input type="hidden" name="add_breakdown" value="1">
        <?php endif; ?>

        <label>Class:</label>
        <select name="class" required <?= $editing ? 'disabled' : '' ?>>
            <option value="">-- Select Class --</option>
            <?php foreach (['Form 1','Form 2','Form 3','Form 4'] as $f): ?>
                <option value="<?= $f ?>" <?= ($editing && $editing['class'] == $f) ? 'selected' : '' ?>><?= $f ?></option>
            <?php endforeach; ?>
        </select>

        <label>Term:</label>
        <select name="term" required <?= $editing ? 'disabled' : '' ?>>
            <option value="">-- Select Term --</option>
            <?php for ($t = 1; $t <= 3; $t++): ?>
                <option value="<?= $t ?>" <?= ($editing && $editing['term'] == $t) ? 'selected' : '' ?>>Term <?= $t ?></option>
            <?php endfor; ?>
        </select>

        <label>Year:</label>
        <input type="number" name="year" value="<?= $editing ? $editing['year'] : date('Y') ?>" required <?= $editing ? 'readonly' : '' ?>>

        <label>Component Name:</label>
        <input type="text" name="component_name" value="<?= $editing['component_name'] ?? '' ?>" required>

        <label>Percentage (%):</label>
        <input type="number" step="0.01" name="percentage" value="<?= $editing['percentage'] ?? '' ?>" required>

        <button type="submit"><?= $editing ? 'Update Breakdown' : '➕ Add Breakdown' ?></button>
    </form>

    <table>
        <tr>
            <th>#</th>
            <th>Class</th>
            <th>Term</th>
            <th>Year</th>
            <th>Component</th>
            <th>Percentage</th>
            <th>Actions</th>
        </tr>
        <?php if ($all->num_rows > 0): $i = 1; ?>
            <?php while ($r = $all->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= $r['class'] ?></td>
                    <td><?= $r['term'] ?></td>
                    <td><?= $r['year'] ?></td>
                    <td><?= $r['component_name'] ?></td>
                    <td><?= number_format($r['percentage'], 2) ?>%</td>
                    <td class="actions">
                        <a href="?edit=<?= $r['id'] ?>">✏ Edit</a>
                        <a href="?delete=<?= $r['id'] ?>" onclick="return confirm('Are you sure you want to delete this item?')">🗑 Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7" style="text-align:center;">No breakdowns added yet.</td></tr>
        <?php endif; ?>
    </table>
</div>

</body>
</html>
