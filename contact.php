<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

// DB connection
$conn = new mysqli("localhost", "root", "", "school");
$message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $conn->real_escape_string($_POST["name"]);
    $email = $conn->real_escape_string($_POST["email"]);
    $subject = $conn->real_escape_string($_POST["subject"]);
    $msg = $conn->real_escape_string($_POST["message"]);

    // Save to DB
    $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $subject, $msg);

    if ($stmt->execute()) {
        $mail = new PHPMailer(true);
        try {
            // Email setup
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'njorogepaul5357@gmail.com'; // Sender Gmail
            $mail->Password = 'mbcg yupb pndi hosd'; // App password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('njorogepaul5357@gmail.com', 'Conquer High School');
            $mail->addAddress('conquerschool@gmail.com'); // School email
            $mail->addReplyTo($email, $name);

            $mail->isHTML(true);
            $mail->Subject = "New Contact Message: $subject";
            $mail->Body = "
                <h3>Message from: $name</h3>
                <p><strong>Email:</strong> $email</p>
                <p><strong>Subject:</strong> $subject</p>
                <p><strong>Message:</strong><br>$msg</p>
            ";

            $mail->send();
            header("Location: thank_you.php");
            exit();
        } catch (Exception $e) {
            $message = "✅ Saved to database, but email failed: {$mail->ErrorInfo}";
        }
    } else {
        $message = "❌ Something went wrong. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Conquer High School</title>
    <meta name="description" content="Contact Conquer High School for inquiries, admissions, and other information.">
    <meta name="keywords" content="Conquer High School, Contact, Admissions, Kenya">
    <meta name="author" content="Conquer High School">

    <meta property="og:title" content="Contact Us - Conquer High School">
    <meta property="og:description" content="Get in touch with Conquer High School for all your educational needs.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="http://localhost/school/contact.php">
    <meta property="og:image" content="pubpage.png">

    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        html, body {
            height: 100%;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #eef2f7;
            color: #333;
            display: flex;
            flex-direction: column;
        }
        body.dark { background: #121212; color: #ddd; }
        .navbar {
            background: #002244;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 999;
        }
        .logo { font-size: 1.5rem; font-weight: bold; }
        .nav-links { list-style: none; display: flex; gap: 20px; }
        .nav-links a {
            color: white; text-decoration: none; font-weight: bold;
            transition: color 0.3s;
        }
        .nav-links a:hover { color: orange; }
        .toggle-dark { cursor: pointer; padding: 5px; font-size: 1.2rem; }

        .container {
            max-width: 1000px;
            margin: 40px auto;
            display: flex;
            gap: 30px;
            padding: 0 20px;
            flex: 1;
        }
        .info, .form-section {
            flex: 1;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px #ccc;
            min-width: 300px;
        }
        body.dark .info, body.dark .form-section {
            background: #1e1e1e;
        }
        h2, h3 { text-align: center; color: #003366; }
        body.dark h2, body.dark h3 { color: orange; }

        input, textarea {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        button {
            padding: 12px;
            background: #003366;
            color: white;
            border: none;
            width: 100%;
            margin-top: 15px;
            border-radius: 6px;
            cursor: pointer;
        }
        button:hover { background: #0055aa; }

        .message {
            color: red;
            text-align: center;
            margin-bottom: 10px;
        }
        footer {
            background: #002244;
            color: white;
            text-align: center;
            padding: 20px;
        }
        .bottom-nav {
            background: #001d3d;
            padding: 10px;
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        .bottom-nav a {
            color: white;
            text-decoration: none;
        }
        .bottom-nav a:hover { text-decoration: underline; }

        @media(max-width:768px){
            .nav-links {
                display: none;
                flex-direction: column;
                background: #002244;
                position: absolute;
                right: 0;
                top: 60px;
                width: 200px;
                padding: 10px;
            }
            .nav-links.show { display: flex; }
            .hamburger {
                display: flex;
                flex-direction: column;
                gap: 5px;
                cursor: pointer;
            }
            .hamburger div {
                width: 25px;
                height: 3px;
                background: white;
            }
            .bottom-nav {
                position: fixed;
                bottom: 0;
                left: 0;
                width: 100%;
                z-index: 999;
            }
        }
    </style>
</head>
<body>

<header class="navbar">
    <div class="logo">🏫 CONQUER HIGH SCHOOL</div>
    <nav>
        <div class="hamburger" onclick="toggleMenu(this)">
            <div></div><div></div><div></div>
        </div>
        <ul class="nav-links" id="navLinks">
            <li><a href="index.php">Home</a></li>
            <li><a href="about.php">About</a></li>
            <li><a href="academics.php">Academics</a></li>
            <li><a href="admission_upload_form.php">Admissions</a></li>
            <li><a href="media_gallery.php">Gallery</a></li>
            <li><a href="public_view_news.php">News</a></li>
            <li><a href="contact.php">Contact</a></li>
            <li><span class="toggle-dark" onclick="toggleDark()">🌙</span></li>
        </ul>
    </nav>
</header>

<h2>Contact Conquer High School</h2>

<div class="container">
    <div class="info">
        <h3>📍 School Contact Information</h3>
        <p><strong>School Name:</strong> Conquer High School</p>
        <p><strong>Address:</strong> P.O. Box 123, Kiambu, Kenya</p>
        <p><strong>Phone:</strong> +254 712 345 678 / +254 798 123 456</p>
        <p><strong>Email:</strong> info@conquerhigh.ac.ke</p>
        <p><strong>Website:</strong> www.conquerhigh.ac.ke</p>
        <p><strong>Working Hours:</strong> Mon - Fri, 8:00 AM - 5:00 PM</p>
    </div>

    <div class="form-section">
        <?php if ($message) echo "<p class='message'>$message</p>"; ?>
        <form method="POST">
            <input type="text" name="name" placeholder="Your Full Name" required>
            <input type="email" name="email" placeholder="Your Email Address" required>
            <input type="text" name="subject" placeholder="Subject" required>
            <textarea name="message" placeholder="Your Message..." rows="5" required></textarea>
            <button type="submit">Send Message</button>
        </form>
    </div>
</div>

<footer>
    <p>&copy; <?= date("Y") ?> Conquer High School. All rights reserved.</p>
    <p><a href="sitemap_view.php" target="_blank">Sitemap</a> | <a href="privacy.php">Privacy Policy</a></p>
</footer>

<div class="bottom-nav">
    <a href="index.php">Home</a>
    <a href="about.php">About</a>
    <a href="academics.php">Academics</a>
    <a href="admission_upload_form.php">Admissions</a>
    <a href="media_gallery.php">Gallery</a>
    <a href="public_view_news.php">News</a>
    <a href="contact.php">Contact</a>
</div>

<script>
function toggleDark() {
    document.body.classList.toggle('dark');
}
function toggleMenu(el) {
    const nav = document.getElementById('navLinks');
    nav.classList.toggle('show');
    el.classList.toggle('active');
}
</script>

</body>
</html>

