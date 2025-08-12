<!DOCTYPE html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Conquer High School</title>
<style>
*{
margin: 0;
padding: 0;
box-sizing: border-box;
}
body{
font-family: Arial, sans-serif;
line-height: 1.6;
background: #f5f5f5;
}
header{
background: linear-gradient(to right, #002147, #005f73);
color: white;
padding: 20px 40px;
text-align: center;
}
nav{
background: #003153;
display: flex;
justify-content: center;
gap: 30px;
padding: 10px;
}
nav a{
color: white;
text-decoration: none;
padding: 8px 12px;
font-weight: bold;
}
nav a:hover{
color: #ffcc00;
background: $004d80;
border-radius: 5px;
}
.dropdown{
position: relative;
display: inline-block;
}
.dropdown-content{
display: none;
position: absolute;
background-color: #003153;
min-width:180px;
box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
z-index: 1;
flex-direction: column;
}
.dropdown-content a{
padding: 12px 16px;
text-decoration: none;
display: block;
color: white;
}
.dropdown-content a:hover{
background-color: #004d80;
}
.dropdown:hover .dropdown-content {
display: flex;
}
.hero{
background: url('pubpage.png') no-repeat center center/cover;
height: 400px;
display: flex;
align-items: center;
justify-content: center;
color: white;
text-align: center;
text-shadow: 2px 2px 4px rgba(0,0,0,0.7);
text-align: center;
padding: 0 20px;
}
.hero h1{
font-size: 3em;
}
section{
padding: 40px 20px;
max-width: 1000px;
margin: auto;
background: white;
margin-top: 20px;
box-shadow: 0 0 10px rgba(0,0,0,.1);
border-radius: 10px;
}
footer{
background: #002147;
color: white;
text-align: center;
padding: 20px;
margin-top: 40px;
}
</style>
<head>
<body>
<header>
<h1>Conquer High School</h1>
<p>"Empowering Minds, Shapping Future"<p>
</header>
<nav>
<a href="about.php">About Us</a>
<a href="public_view_news.php">News & Events</a>
<a href="media_gallery.php">Media Gallery</a>
<a href="admission_upload_form.php">Admissions</a>
<a href="contact.php">Contact Us</a>
<div class="dropdown">
<a href="" class="dropbtn">	Portal </a>
<div class="dropdown-content">
<a href="login.php">Student Login</a>
<a href="teacher-login.php">Teacher Login</a>
<a href="admin_login.php">Admin Login</a>
</div>

</div>
</nav>
<div class="hero">
<h1>Welcome to Conquer High School</h1>
</div>
<section id="about">
<h2>About Us</h2>
<p>Conquer High School is commited to providing a nurturing and empowering learning environment. we focus on holistic education that prepares stdudents for global stage while perservung local values and cultural identity.</p>

</section> 
<section id="news">
<h2>News & Events</h2>
<p> For all schools' news and Events</p>
</section>
<section id="gallery">
<h2>Media Gallery</h2>
<p>for Photos and videos of sschool all activities </p>
</section>
<section id="admission">
<h2>Admissions</h2>
<p>Admission for the new academic year is ongoing. Apply through our online admission form or visit our administration office</p>
</section>
<section id="Contact">
<h2>Contact Us</h2>
<p> Location: Kiambu county, Kenya<br>
Phone: +254 700000000<br>
Email: info@conquerhigh.ac.ke<p>
</section>
<footer>
<p>&copy; <?= date('Y') ?>Conquer High School. All rights reserved.</p>
</footer>
<body>
<html>
 
