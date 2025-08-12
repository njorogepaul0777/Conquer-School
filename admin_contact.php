<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// Flash message handler
$flash = "";
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

// Delete message
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM contact_messages WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $_SESSION['flash'] = "✅ Message deleted.";
    header("Location: admin_contact.php");
    exit();
}

// Handle email reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_submit'])) {
    $msg_id = intval($_POST['msg_id']);
    $reply_message = $_POST['reply_message'];
    $user_email = $_POST['user_email'];

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'njorogepaul5357@gmail.com';
        $mail->Password = 'mbcg yupb pndi hosd'; // Gmail App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('njorogepaul5357@gmail.com', 'Conquer High School');
        $mail->addAddress($user_email);
        $mail->isHTML(false);
        $mail->Subject = 'Reply from Conquer High School';
        $mail->Body = $reply_message;

        $mail->send();

        $stmt = $conn->prepare("UPDATE contact_messages SET reply = ?, status = 'Replied' WHERE id = ?");
        $stmt->bind_param("si", $reply_message, $msg_id);
        $stmt->execute();

        $_SESSION['flash'] = "✔️ Reply sent and status updated.";
    } catch (Exception $e) {
        $_SESSION['flash'] = "❌ Email failed: {$mail->ErrorInfo}";
    }
    header("Location: admin_contact.php");
    exit();
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;
$result = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$totalResult = $conn->query("SELECT COUNT(*) AS total FROM contact_messages");
$totalMessages = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalMessages / $limit);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin - Contact Messages</title>
    <link rel="stylesheet" href="admin_style.css">
    <style>
	/* General Reset & Base Styles */
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f2f4f8;
            display: flex;
            min-height: 100vh;
            color: #333;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 240px;
            background: #002b5c;
            color: white;
            padding: 20px 0;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            overflow-y: auto;
        }

        .sidebar h2 {
            text-align: center;
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            padding-bottom: 10px;
        }

        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            font-size: 15px;
        }

        .sidebar a:hover {
            background-color: #014a99;
        }

        .sidebar a.active {
            background-color: #004080;
        }

        /* Dropdown (Details) Styling in Sidebar */
        .sidebar details {
            margin: 5px 10px;
        }

        .sidebar summary {
            font-weight: bold;
            padding: 10px 20px;
            cursor: pointer;
            font-size: 15px;
            color: #fff;
        }

        .sidebar details[open] summary::after {
            content: " ▲";
            float: right;
        }

        .sidebar summary::after {
            content: " ▼";
            float: right;
        }

        .sidebar details a {
            font-size: 14px;
            padding-left: 30px;
            color: #cbd9ff;
        }

        .sidebar details a:hover {
            color: #fff;
            background: #014a99;
        }

        /* Main content layout */
        .main-content {
            margin-left: 240px;
            padding: 25px;
            flex: 1;
        }

        /* Topbar/Header styling */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 18px 25px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }

        .topbar h1 {
            font-size: 24px;
            color: #002b5c;
            margin: 0;
        }

        .logout {
            background: #d9534f;
            color: white;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
        }

        .logout:hover {
            background: #c9302c;
        }
        .flash-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #003366;
            color: white;
            padding: 12px 18px;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 9999;
            opacity: 1;
            transition: opacity 1s ease;
            font-size: 16px;
        }
    </style>
</head>
<body>

<!-- Sidebar Navigation -->
<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php" class="active">🏠 Dashboard</a>

    <!-- Admissions section -->
    <details>
        <summary>🎓 Admissions</summary>
        <a href="admin_admissions.php">• Manage Admissions</a>
        <a href="admin_contact_student.php">• Student Messages</a>
    </details>

    <!-- Academics section -->
    <details>
        <summary>📚 Academics</summary>
        <a href="admin_manage_results.php">• Manage Results</a>
        <a href="admin_subjects.php">• Subjects</a>
        <a href="admin_upload_syllabus.php">• Upload Syllabus</a>
        <a href="admin_upload_teacher.php">• Teachers</a>
    </details>

    <!-- Media section -->
    <details>
        <summary>🖼️ Media & News</summary>
        <a href="admin_upload_media.php">• Media Gallery</a>
        <a href="upload_news.php">• News & Events</a>
    </details>

    <!-- Communication section -->
    <details>
        <summary>👥 Interaction</summary>
        <a href="admin_contact.php">• Contact Messages</a>
        <a href="admin_faqs.php">• FAQs</a>
        <a href="admin_manage_testimonials.php">• Testimonials</a>
        <a href="subscribers.php">• Subscribers</a>
    </details>

    <!-- Staff management -->
    <a href="admin_manage_staffs.php">👩‍🏫 Manage Staff</a>
    <a href="admin_logout.php">🚪 Logout</a>
</div>



<!-- Main content -->
<div class="main">
    <div class="topbar">
        <h1>📩 Contact Messages</h1>
        <a href="admin_logout.php" class="logout">Logout</a>
    </div>

    <?php if ($flash): ?>
        <div class="flash-toast" id="flashToast"><?= $flash ?></div>
        <script>
            setTimeout(() => {
                const toast = document.getElementById('flashToast');
                if (toast) toast.style.opacity = '0';
            }, 3000);
        </script>
    <?php endif; ?>

    <table>
        <thead>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Subject</th>
            <th>Message</th>
            <th>Reply</th>
            <th>Status</th>
            <th>Date</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['subject']) ?></td>
                <td><?= nl2br(htmlspecialchars($row['message'])) ?></td>
                <td>
                    <td>
    <form method="POST">
        <textarea name="reply_message" required><?= htmlspecialchars($row['reply'] ?? '') ?></textarea>
        <input type="hidden" name="msg_id" value="<?= $row['id'] ?>">
        <input type="hidden" name="user_email" value="<?= htmlspecialchars($row['email']) ?>">
        <button type="submit" name="reply_submit">Send Reply</button>
    </form>



                </td>
                <td><?= htmlspecialchars($row['status'] ?? 'Pending') ?></td>
                <td><?= htmlspecialchars($row['created_at']) ?></td>
                <td><a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Delete this message?')">🗑️ Delete</a></td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="pagination">
        <?php if($page > 1): ?><a href="?page=<?= $page - 1 ?>">« Prev</a><?php endif; ?>
        <?php for($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?page=<?= $i ?>" <?= ($i == $page) ? 'style="background:#ff6600;"' : '' ?>><?= $i ?></a>
        <?php endfor; ?>
        <?php if($page < $totalPages): ?><a href="?page=<?= $page + 1 ?>">Next »</a><?php endif; ?>
    </div>
</div>

</body>
</html>
