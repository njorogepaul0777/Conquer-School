<?php
session_start();

if(!isset($_SESSION['admin_id'])){
	header("Location: admin_login.php");
	exit();
}
$admin_name = $_SESSION['admin_username'];
?>
<!DOCTYPE html>
<html utf="8">
<head>
<title>Admin dashboard</title>
<style>
body{
	font-family: 'segoe UI', sans-serif;
	background-color: #f4f4f4;
	padding: 20px;
	
}

.container{
	background: #fff;
	padding: 200px;
	max-width: 100px;
	margin: auto;
	border-radius: 8px;
	box-shadow: 0 2px 6px rgba(0,0,0,0.1);
	
}
h2{
	color: #333;
	
}
.nav-links a{
	display: inline-block;
	margin: 10px 15px;
	text-decoration: none;
	color: #007BFF;
	font-weight: bold;
	
}
.logout{
	float: right;
	color: red;
	text-decoration: none;
	
}
</style>

</head>
<body>
<div class="container">
<a href="loguot.php" class="logout">Logout</a>
<h2>Welcome Admin:<?php echo htmlspecialchars($admin_name); ?></h2>
<div class="nav-links">
<a href="Upload_result.php">Upload Results</a>
<a href="admin_contact.php">View conatcts</a>
<a href="admin_contact_student.php">View students conatcts</a>
<a href="admin_upload_media.php">Upload Media</a>
<a href="upload_news.php">upload News & Events</a>

</div>
</div>
</body>
</html>