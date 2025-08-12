<?php
// subscribers.php - Admin: list subscribers + send newsletter with optional attachment
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit();
}

// DB connection (adjust if you centralize DB config)
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "school";
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Unsubscribe logic if ?unsubscribe=email@example.com is in URL
if (isset($_GET['unsubscribe'])) {
    $email = trim($_GET['unsubscribe']);
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $conn->prepare("DELETE FROM newsletter_subscribers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            echo "<h2 style='font-family:Arial;text-align:center;color:green;'>You have been unsubscribed successfully.</h2>";
        } else {
            echo "<h2 style='font-family:Arial;text-align:center;color:red;'>Email not found in our list.</h2>";
        }
        $stmt->close();
    } else {
        echo "<h2 style='font-family:Arial;text-align:center;color:red;'>Invalid email address.</h2>";
    }
    $conn->close();
    exit; // Stop rest of page so admin panel isn't shown
}


// ---------- SMTP / PHPMailer config - EDIT THESE ----------
$smtp = [
    'use_smtp' => true,
    'host'     => 'smtp.gmail.com',
    'username' => 'njorogepaul5357@gmail.com',
    'password' => 'mbcg yupb pndi hosd',
    'port'     => 587,
    'secure'   => 'tls',
    'from_email' => 'njorogepaul5357@gmail.com',
    'from_name'  => 'Conquer High School'
];

// ----------------------------------------------------------------

// Try to include PHPMailer (Composer or local)
$havePHPMailer = false;
if ($smtp['use_smtp']) {
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
        $havePHPMailer = class_exists('PHPMailer\PHPMailer\PHPMailer');
    } elseif (file_exists(__DIR__ . '/PHPMailer/src/PHPMailer.php')) {
        require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/PHPMailer/src/SMTP.php';
        require_once __DIR__ . '/PHPMailer/src/Exception.php';
        $havePHPMailer = class_exists('PHPMailer\PHPMailer\PHPMailer');
    }
}

// Fetch subscribers
$subsRes = $conn->query("SELECT id, email, subscribed_at FROM newsletter_subscribers ORDER BY subscribed_at DESC");

// Handle form submission: send to all subscribers
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_all'])) {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    $errors = [];
    if ($subject === '' || $message === '') {
        $errors[] = "Subject and message are required.";
    }

    // Attachment handling (optional)
    $attachmentPath = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
        $fileErr = $_FILES['attachment']['error'];
        if ($fileErr !== UPLOAD_ERR_OK) {
            $errors[] = "Attachment upload error (code: $fileErr).";
        } else {
            $allowedExt = ['pdf','doc','docx','jpg','jpeg','png'];
            $maxSize = 5 * 1024 * 1024; // 5 MB
            $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt)) {
                $errors[] = "Attachment type not allowed. Allowed: " . implode(', ', $allowedExt);
            } elseif ($_FILES['attachment']['size'] > $maxSize) {
                $errors[] = "Attachment too large (max 5MB).";
            } else {
                $uploadDir = __DIR__ . '/uploads/newsletters/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES['attachment']['name']));
                $dest = $uploadDir . $safeName;
                if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $dest)) {
                    $errors[] = "Failed to save the attachment.";
                } else {
                    $attachmentPath = $dest;
                }
            }
        }
    }

    // If no errors, proceed to fetch emails and send
    if (empty($errors)) {
        // Fetch emails
        $emails = [];
        $res = $conn->query("SELECT email FROM newsletter_subscribers");
        if ($res) {
            while ($r = $res->fetch_assoc()) $emails[] = $r['email'];
        }

        if (empty($emails)) {
            $errors[] = "No subscribers found.";
        } else {
            $sent = 0;
            $failed = 0;
            $failedList = [];

            if ($smtp['use_smtp'] && $havePHPMailer) {
                // PHPMailer + SMTP
                foreach ($emails as $to) {
                    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                    try {
                        // SMTP settings
                        $mail->isSMTP();
                        $mail->Host = $smtp['host'];
                        $mail->SMTPAuth = true;
                        $mail->Username = $smtp['username'];
                        $mail->Password = $smtp['password'];
                        if (!empty($smtp['secure'])) $mail->SMTPSecure = $smtp['secure'];
                        $mail->Port = $smtp['port'];

                        // From & to
                        $mail->setFrom($smtp['from_email'], $smtp['from_name']);
                        $mail->addAddress($to);

                        // Content
                        $mail->isHTML(true);
                        // Simple branded template (customize)
                       $unsubscribeLink = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?unsubscribe=" . urlencode($to);

$htmlBody = "<div style='font-family:Segoe UI,Arial;color:#333'>
                <h3 style='color:#004080;margin-bottom:6px;'>" . htmlspecialchars($smtp['from_name']) . "</h3>
                <div style='padding:6px 0;'>" . nl2br(htmlspecialchars($message)) . "</div>
                <hr style='border:none;border-top:1px solid #eee;margin:10px 0;'>
                <small style='color:#666'>If you no longer wish to receive these emails, <a href='$unsubscribeLink'>click here to unsubscribe</a>.</small>
            </div>";

                      
                        $mail->Subject = $subject;
                        $mail->Body = $htmlBody;
                        $mail->AltBody = strip_tags($message);

                        // Attachment
                        if ($attachmentPath) $mail->addAttachment($attachmentPath, basename($attachmentPath));

                        $mail->send();
                        $sent++;
                    } catch (Exception $e) {
                        $failed++;
                        $failedList[] = $to;
                        // You can log $mail->ErrorInfo to a file if needed
                    }
                    // tiny delay to reduce SMTP throttling
                    usleep(150000); // 0.15s
                }
            } else {
                // Fallback to mail() — note: attachments are not supported easily here
                $headers = "From: {$smtp['from_name']} <{$smtp['from_email']}>\r\n";
                $headers .= "MIME-Version: 1.0\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                $body = "<html><body>" . nl2br(htmlspecialchars($message)) . "</body></html>";
                foreach ($emails as $to) {
                    $ok = mail($to, $subject, $body, $headers);
                    if ($ok) $sent++; else { $failed++; $failedList[] = $to; }
                    usleep(100000);
                }
            }

            // Log broadcast
            $stmt = $conn->prepare("INSERT INTO sent_emails (subject, body, attachment_path, recipients_count, failed_count) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssii", $subject, $message, $attachmentPath, $sent, $failed);
            $stmt->execute();
            $stmt->close();

            // Prepare notice
            $notice = "Sent: $sent. Failed: $failed.";
            if ($failed > 0) $notice .= " (showing up to 10 failures) " . implode(', ', array_slice($failedList, 0, 10));
        }
    }

    if (!empty($errors)) {
        $notice = '<span style="color:red;">' . implode('<br>', $errors) . '</span>';
        if (!empty($attachmentPath) && file_exists($attachmentPath)) @unlink($attachmentPath);
    }
}

// Refresh subscriber list after send (counts may be same but ensure fresh)
$subsRes = $conn->query("SELECT id, email, subscribed_at FROM newsletter_subscribers ORDER BY subscribed_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Subscribers & Send Newsletter - Admin</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
/* Simple admin styles — adapt to your theme */
body { font-family: Arial, sans-serif; background:#f4f6f8; margin:0; display:flex; }
.sidebar {
    width:240px; background:#002b5c; color:#fff; height:100vh; padding:20px; position:fixed; overflow-y:auto;
}
.sidebar h2 { margin:0 0 18px 0; font-size:18px; text-align:center; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:10px;}
.sidebar a { color:#fff; display:block; text-decoration:none; padding:8px 6px; border-radius:4px; }
.sidebar a:hover { background:#014a99; }

.main { margin-left:260px; padding:22px; flex:1; }
.box { background:#fff; padding:16px; border-radius:8px; box-shadow:0 6px 18px rgba(0,0,0,0.06); margin-bottom:18px; }
h1 { margin:0 0 12px 0; color:#003366; text-align:center; }
label { display:block; margin-top:10px; font-weight:600; }
input[type="text"], textarea, input[type="file"] { width:100%; padding:8px; margin-top:6px; border:1px solid #dcdcdc; border-radius:6px; }
textarea { min-height:130px; }
.btn { background:#007bff; color:#fff; padding:10px 14px; border:none; border-radius:6px; cursor:pointer; margin-top:12px; }
.btn.secondary { background:#28a745; }
.notice { margin-bottom:12px; font-weight:600; }
.table { width:100%; border-collapse:collapse; margin-top:12px; }
.table th, .table td { border:1px solid #e6e6e6; padding:8px; text-align:left; }
.table th { background:#004080; color:#fff; }
.small { font-size:0.9em; color:#666; margin-top:6px; }
.export-btn { background:orange; color:#fff; padding:8px 12px; border-radius:6px; border:none; cursor:pointer; }
@media (max-width:800px) { .sidebar { position:relative; width:100%; height:auto } .main { margin-left:0; } }
</style>
</head>
<body>

<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php">🏠 Dashboard</a>
    <details><summary>🎓 Admissions</summary><a href="admin_admissions.php">• Manage Admissions</a><a href="admin_contact_student.php">• Student Messages</a></details>
    <details><summary>📚 Academics</summary><a href="admin_manage_results.php">• Manage Results</a><a href="admin_subjects.php">• Subjects</a><a href="admin_upload_syllabus.php">• Upload Syllabus</a><a href="admin_upload_teacher.php">• Teachers</a></details>
    <details><summary>🖼️ Media & News</summary><a href="admin_upload_media.php">• Media Gallery</a><a href="upload_news.php">• News & Events</a></details>
    <details><summary>👥 Interaction</summary><a href="admin_contact.php">• Contact Messages</a><a href="admin_faqs.php">• FAQs</a><a href="admin_manage_testimonials.php">• Testimonials</a><a href="subscribers.php">• Subscribers</a></details>
    <a href="admin_manage_staffs.php">👩‍🏫 Manage Staff</a>
    <a href="admin_logout.php">🚪 Logout</a>
</div>

<div class="main">
    <h1>Newsletter Subscribers & Broadcast</h1>

    <?php if (!empty($notice)): ?>
        <div class="notice box"><?= $notice ?></div>
    <?php endif; ?>

    <!-- Send form -->
    <div class="box">
        <h3>Send Email / Document to All Subscribers</h3>
        <form method="post" enctype="multipart/form-data" onsubmit="return confirm('Send this message to ALL subscribers?');">
            <label>Subject</label>
            <input type="text" name="subject" required value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>">

            <label>Message (plain text — HTML will be converted)</label>
            <textarea name="message" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>

            <label>Attachment (optional, PDF/DOC/DOCX/JPG/PNG, max 5MB)</label>
            <input type="file" name="attachment" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">

            <button type="submit" name="send_all" class="btn">Send to All Subscribers</button>
            <div class="small">Tip: For reliable delivery with attachments, configure SMTP in this file and install PHPMailer.</div>
        </form>
    </div>

    <!-- Export button -->
    <div class="box" style="display:flex;justify-content:flex-start;align-items:center;">
        <form method="post" action="export_subscribers.php">
            <button type="submit" class="export-btn">Export Subscribers as CSV</button>
        </form>
    </div>

    <!-- Subscribers table -->
    <div class="box">
        <h3>Subscriber List (most recent first)</h3>
        <table class="table">
            <thead>
                <tr><th>#</th><th>Email</th><th>Subscribed At</th></tr>
            </thead>
            <tbody>
            <?php
            $i = 1;
            if ($subsRes && $subsRes->num_rows > 0):
                while ($row = $subsRes->fetch_assoc()):
            ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= date("M d, Y H:i", strtotime($row['subscribed_at'])) ?></td>
                </tr>
            <?php
                endwhile;
            else:
            ?>
                <tr><td colspan="3">No subscribers found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>

<?php $conn->close(); ?>
