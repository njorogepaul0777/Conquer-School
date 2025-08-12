<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You - Conquer High School</title>
    <meta name="description" content="Thank you for contacting Conquer High School. We have received your message.">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #eef2f7;
            color: #333;
            text-align: center;
            padding: 80px 20px;
            transition: background 0.3s, color 0.3s;
        }
        body.dark {
            background: #121212;
            color: #ddd;
        }
        .navbar {
            position: sticky; top: 0; background: #002244; color: white;
            display: flex; justify-content: space-between; padding: 1rem 2rem;
        }
        .navbar .logo { font-weight: bold; }
        .nav-links {
            list-style: none; display: flex; gap: 20px;
        }
        .nav-links a {
            color: white; text-decoration: none; font-weight: bold;
        }
        .toggle-dark {
            cursor: pointer; font-size: 1.2rem;
        }
        h1 {
            color: #004080;
            margin-top: 50px;
        }
        body.dark h1 { color: orange; }
        p { font-size: 1.2rem; }
        a.btn {
            display: inline-block;
            margin-top: 20px;
            background: #004080;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }
        a.btn:hover { background: #0066cc; }
    </style>
    <meta http-equiv="refresh" content="5;url=home.php">
</head>
<body>

<!-- Sticky Navbar -->
<header class="navbar">
    <div class="logo">🏫 CONQUER HIGH SCHOOL</div>
    <nav>
        <ul class="nav-links">
            <li><a href="home.php">Home</a></li>
            <li><a href="about.php">About</a></li>
            <li><a href="academics.php">Academics</a></li>
            <li><a href="admission_upload_form.php">Admissions</a></li>
            <li><a href="contact.php">Contact</a></li>
            <li><span class="toggle-dark" onclick="toggleDark()">🌙</span></li>
        </ul>
    </nav>
</header>

<h1>🎉 Thank You!</h1>
<p>Your message has been successfully sent. We will get back to you shortly.</p>
<p>You will be redirected to the Home page in 5 seconds.</p>
<a href="home.php" class="btn">Return to Home Now</a>

<script>
// Dark mode toggle
function toggleDark() {
    document.body.classList.toggle('dark');
}
</script>

</body>
</html>
