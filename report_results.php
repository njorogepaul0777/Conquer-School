<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Fetch filters
$classes = $conn->query("SELECT DISTINCT class FROM students_admitted");
$streams = $conn->query("SELECT DISTINCT stream FROM students_admitted");
$subjects = $conn->query("SELECT DISTINCT subject FROM results");
$terms = $conn->query("SELECT DISTINCT term FROM results");
$years = $conn->query("SELECT DISTINCT year FROM results");
$genders = $conn->query("SELECT DISTINCT gender FROM students_admitted");

// Read selected filters
$selected_class = $_GET['class'] ?? '';
$selected_stream = $_GET['stream'] ?? '';
$selected_term = $_GET['term'] ?? '';
$selected_year = $_GET['year'] ?? '';
$selected_subject = $_GET['subject'] ?? '';
$selected_gender = $_GET['gender'] ?? '';

$where = "WHERE 1";
if ($selected_class) $where .= " AND sa.class = '$selected_class'";
if ($selected_stream) $where .= " AND sa.stream = '$selected_stream'";
if ($selected_term) $where .= " AND r.term = '$selected_term'";
if ($selected_year) $where .= " AND r.year = '$selected_year'";
if ($selected_subject) $where .= " AND r.subject = '$selected_subject'";
if ($selected_gender) $where .= " AND sa.gender = '$selected_gender'";

// Mean scores per subject
$results = $conn->query("
    SELECT r.subject, ROUND(AVG(r.total_marks),2) AS mean_score, COUNT(DISTINCT r.admission_no) AS student_count
    FROM results r
    JOIN students_admitted sa ON r.admission_no = sa.admission_no
    $where
    GROUP BY r.subject
");
$mean_data = [];
while ($row = $results->fetch_assoc()) $mean_data[] = $row;

// Top scorers with RANK
$top_result = $conn->query("
    SELECT r.subject, r.admission_no, ROUND(r.total_marks) AS top_score, r.grade,
           RANK() OVER (PARTITION BY r.subject ORDER BY r.total_marks DESC) AS rank_position
    FROM results r
    JOIN students_admitted sa ON r.admission_no = sa.admission_no
    $where
    ORDER BY r.subject, rank_position ASC
");

if($top_result === false){
	die ("fjk" . $conn->error);
}

$top_data = [];
while ($row = $top_result->fetch_assoc()) {
    $sub = $row['subject'];
    if (!isset($top_data[$sub])) $top_data[$sub] = [];
    if (count($top_data[$sub]) < 3) $top_data[$sub][] = $row;
}

// Stream comparison
$stream_results = $conn->query("
    SELECT sa.stream, r.subject, ROUND(AVG(r.total_marks),2) AS stream_mean
    FROM results r
    JOIN students_admitted sa ON r.admission_no = sa.admission_no
    $where
    GROUP BY sa.stream, r.subject
");
$stream_data = [];
while ($row = $stream_results->fetch_assoc()) {
    $stream_data[$row['stream']][$row['subject']] = $row['stream_mean'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Academic Results Report</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    html, body {
      margin: 0;
      padding: 0;
      height: 100%;
      font-family: 'Segoe UI', sans-serif;
      background-color: #f8f9fa;
    }

    .d-flex {
      display: flex;
      height: 100vh;
      overflow: hidden;
    }

    #sidebar {
      width: 240px;
      background: #1c2833;
      color: white;
      padding: 20px 15px;
      height: 100vh;
      overflow-y: auto;
    }

    #sidebar h5 {
      font-size: 1.1rem;
      margin-bottom: 20px;
    }

    #sidebar .nav-link {
      color: #ecf0f1;
      padding: 10px 12px;
      border-radius: 6px;
      transition: background 0.3s;
    }

    #sidebar .nav-link:hover {
      background-color: rgba(255,255,255,0.1);
      text-decoration: none;
    }

    .main-content {
      flex-grow: 1;
      overflow-y: auto;
      padding: 25px 40px;
    }

    .report-header {
      background: #fff;
      padding: 20px 25px;
      border-radius: 8px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 4px rgba(0,0,0,0.06);
      margin-bottom: 20px;
    }

    .report-header h1 {
      font-size: 1.5rem;
      margin: 0;
    }

    .report-header small {
      color: #6c757d;
      font-size: 0.9rem;
    }

    .report-header img {
      height: 45px;
    }

    .filters-toggle-btn {
      margin: 20px 0;
    }

    #filterContainer {
      overflow: hidden;
      max-height: 0;
      transform: scaleY(0);
      transform-origin: top;
      transition: all 0.4s ease-in-out;
      opacity: 0;
    }

    #filterContainer.open {
      max-height: 1000px;
      transform: scaleY(1);
      opacity: 1;
    }

    .filter-form {
      background: #fff;
      padding: 20px;
      border: 1px solid #dee2e6;
      border-radius: 8px;
      max-width: 500px;
      margin-bottom: 30px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    }

    .filter-form select, .filter-form button {
      margin-bottom: 12px;
    }

    .section {
      background: #fff;
      border-left: 4px solid #0d6efd;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.05);
      margin-bottom: 30px;
    }

    .section h3, .section h4, .section h5 {
      margin-bottom: 15px;
    }

    .results-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }

    .results-table th, .results-table td {
      border: 1px solid #dee2e6;
      padding: 10px;
      font-size: 0.9rem;
    }

    .results-table th {
      background-color: #f1f3f5;
    }

    .actions {
      margin-bottom: 20px;
    }

    @media print {
      #sidebar, #filterContainer, .filters-toggle-btn, .actions, form, button {
        display: none !important;
      }
      body::before {
        content: "CONQUER HIGH SCHOOL";
        position: fixed;
        top: 40%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-30deg);
        font-size: 4rem;
        color: rgba(0, 0, 0, 0.08);
        z-index: -1;
      }
    }
  </style>
</head>
<body>
<div class="d-flex">
  <!-- Sidebar -->
  <div id="sidebar">
    <h5>📊 Reports</h5>
    <ul class="nav flex-column">
      <li class="nav-item"><a href="report_results.php" class="nav-link">Academic Results</a></li>
      <li class="nav-item"><a href="report_fees.php" class="nav-link">Fees Report</a></li>
      <li class="nav-item"><a href="report_admissions.php" class="nav-link">Admissions Report</a></li>
      <li class="nav-item"><a href="report_single_student.php" class="nav-link">Student Report</a></li>
      <li class="nav-item"><a href="index.php" class="nav-link">🏠 Dashboard</a></li>
    </ul>
  </div>

  <!-- Main content -->
  <div class="main-content">
    <!-- Header -->
    <div class="report-header">
      <div>
        <h1>CONQUER HIGH SCHOOL</h1>
        <small>Academic Performance Report</small>
      </div>
      <div class="text-end">
        <img src="school_logo.png" alt="School Logo">
        <div id="report-date" style="font-size: 0.75rem; color: #555;"></div>
      </div>
    </div>

    <!-- Filter Toggle -->
    <button class="btn btn-primary filters-toggle-btn" onclick="toggleFilters()">🔍 Show Filters</button>

    <!-- Filter Form -->
    <div id="filterContainer">
      <form method="GET" class="filter-form">
        <?php function renderOptions($res, $field, $selected) {
          $res->data_seek(0);
          while ($row = $res->fetch_assoc()) {
              $val = htmlspecialchars($row[$field]);
              $sel = ($val == $selected) ? "selected" : "";
              echo "<option value=\"$val\" $sel>$val</option>";
          }
        } ?>
        <select name="class" class="form-select"><option value="">Class</option><?php renderOptions($classes, 'class', $selected_class); ?></select>
        <select name="stream" class="form-select"><option value="">Stream</option><?php renderOptions($streams, 'stream', $selected_stream); ?></select>
        <select name="term" class="form-select"><option value="">Term</option><?php renderOptions($terms, 'term', $selected_term); ?></select>
        <select name="year" class="form-select"><option value="">Year</option><?php renderOptions($years, 'year', $selected_year); ?></select>
        <select name="subject" class="form-select"><option value="">Subject</option><?php renderOptions($subjects, 'subject', $selected_subject); ?></select>
        <select name="gender" class="form-select"><option value="">Gender</option><?php renderOptions($genders, 'gender', $selected_gender); ?></select>
        <button type="submit" class="btn btn-success">Apply Filters</button>
      </form>
    </div>

    <!-- Applied Filters -->
    <div class="section">
      <h4>Applied Filters</h4>
      <p>
        <strong>Class:</strong> <?= $selected_class ?: 'All' ?> |
        <strong>Stream:</strong> <?= $selected_stream ?: 'All' ?> |
        <strong>Term:</strong> <?= $selected_term ?: 'All' ?> |
        <strong>Year:</strong> <?= $selected_year ?: 'All' ?> |
        <strong>Subject:</strong> <?= $selected_subject ?: 'All' ?> |
        <strong>Gender:</strong> <?= $selected_gender ?: 'All' ?>
      </p>
    </div>

    <!-- Print Button -->
    <div class="actions">
      <button class="btn btn-secondary" onclick="window.print()">🖨️ Print / Save as PDF</button>
    </div>

    <!-- Mean Score Table -->
    <div class="section">
      <h3>Mean Scores Per Subject</h3>
      <table class="results-table">
        <thead><tr><th>Subject</th><th>Mean Score</th><th>Total Students</th></tr></thead>
        <tbody>
        <?php foreach ($mean_data as $row): ?>
          <tr>
            <td><?= $row['subject'] ?></td>
            <td><?= $row['mean_score'] ?></td>
            <td><?= $row['student_count'] ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <canvas id="meanChart" style="margin-top:20px;" height="100"></canvas>
    </div>

    <!-- Top Scorers -->
    <div class="section">
      <h3>Top 3 Scorers Per Subject</h3>
      <?php foreach ($top_data as $subject => $list): ?>
        <h5><?= $subject ?></h5>
        <table class="results-table">
          <thead><tr><th>Position</th><th>Admission No</th><th>Full Name</th><th>Score</th><th>Grade</th></tr></thead>
          <tbody>
          <?php foreach ($list as $s): ?>
            <tr>
              <td><?= $s['rank_position'] ?></td>
              <td><?= $s['admission_no'] ?></td>
              <td><?= $s['full_name'] ?></td>
              <td><?= $s['top_score'] ?></td>
              <td><?= $s['grade'] ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endforeach; ?>
    </div>

    <!-- Stream Comparison -->
    <?php if (!empty($stream_data)): ?>
      <div class="section">
        <h3>Stream Mean Comparison</h3>
        <table class="results-table">
          <thead>
            <tr><th>Stream</th>
              <?php $subjectsList = array_keys(reset($stream_data)); foreach ($subjectsList as $s) echo "<th>$s</th>"; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($stream_data as $stream => $scores): ?>
              <tr>
                <td><?= $stream ?></td>
                <?php foreach ($subjectsList as $s): ?>
                  <td><?= $scores[$s] ?? '-' ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- JS -->
<script>
  function toggleFilters() {
    const container = document.getElementById('filterContainer');
    container.classList.toggle('open');
    document.querySelector('.filters-toggle-btn').textContent =
      container.classList.contains('open') ? '❌ Hide Filters' : '🔍 Show Filters';
  }

  new Chart(document.getElementById('meanChart'), {
    type: 'bar',
    data: {
      labels: <?= json_encode(array_column($mean_data, 'subject')) ?>,
      datasets: [{
        label: 'Mean Score',
        data: <?= json_encode(array_column($mean_data, 'mean_score')) ?>,
        backgroundColor: 'rgba(13, 110, 253, 0.6)',
        borderColor: '#0d6efd',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      plugins: {
        title: {
          display: true,
          text: 'Mean Score per Subject'
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          max: 100
        }
      }
    }
  });

  document.addEventListener('DOMContentLoaded', () => {
    const dateElement = document.getElementById('report-date');
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    dateElement.textContent = 'Date: ' + new Date().toLocaleDateString(undefined, options);
  });
</script>
</body>
</html>
