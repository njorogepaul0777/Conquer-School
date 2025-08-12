<?php
session_start();
$conn = new mysqli("localhost", "root", "", "school");

if (!isset($_SESSION['admission_no'])) {
    die("Access denied.");
}

$admission_no = $_SESSION['admission_no'];

// Get available terms and years for the student
$termList = $conn->query("SELECT DISTINCT term FROM results WHERE admission_no = '$admission_no'");
$yearList = $conn->query("SELECT DISTINCT year FROM results WHERE admission_no = '$admission_no'");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Select Results</title>
	<style>
	<style>
        body {
            font-family: Arial;
            background: #f0f0f0;
            padding: 30px;
        }
        form {
            background: white;
            max-width: 400px;
			height: auto;
            margin: auto;
            padding: 70px;
            border-radius: 10px;
            box-shadow: 0 0 10px #ccc;
        }
        select{
            width: 90%;
            padding: 10px;
            margin: 10px 10px;
        }
		button {
            width: 80%;
            padding: 10px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
	        background-color: rgb(18, 187, 169);
            color: white;
            cursor: pointer;
			display: flex;
			justify-content: center;
            
        }
		a{
			width: 75%;
			background: #3b82f6;
			color: white;
			padding: 10px;
			justify-content: center;
			text-decoration: none;
			display: flex;
			margin-top: 20px;
			border-radius: 5px;
		}
        h2 {
            text-align: center;
        }
        .error {
            color: red;
            text-align: center;
        }
    </style>
	</style>
</head>
<body>
    <h2>View Your Results</h2>

    <form method="GET" action="student_results.php">
	<input type="hidden" name="admission_no" value="<?= htmlspecialchars($admission_no) ?>">
        <label>Term:</label>
        <select name="term" required>
            <option value="">-- Select Term --</option>
            <?php while ($row = $termList->fetch_assoc()): ?>
                <option value="<?= $row['term'] ?>"><?= $row['term'] ?></option>
            <?php endwhile; ?>
        </select>

        <label>Year:</label>
        <select name="year" required>
            <option value="">-- Select Year --</option>
            <?php while ($row = $yearList->fetch_assoc()): ?>
                <option value="<?= $row['year'] ?>"><?= $row['year'] ?></option>
            <?php endwhile; ?>
        </select>

        <button type="submit">View Results</button>
        <a href="student_dashboard.php">Back To Dashboard</a>
    </form>
</body>
</html>

