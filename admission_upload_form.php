<?php
$conn = new mysqli("localhost", "root", "", "school");
$message = "";

// Move uploaded file function
function moveAndSave($field, $upload_dir){
    if(isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK){
        $filename = time() . "_" . basename($_FILES[$field]['name']);
        $filepath = $upload_dir . $filename;
        if(move_uploaded_file($_FILES[$field]['tmp_name'], $filepath)){
            return $filepath;
        }
    }
    return null;
}
if ($_SERVER["REQUEST_METHOD"] === 'POST'){
    $full_name = $_POST['full_name'];
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    $applied_class = $_POST['applied_class']; // NEW
    $contact = $_POST['contact'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $parent_name = $_POST['parent_name'];
    $parent_contact = $_POST['parent_contact'];

    // File upload directory
    $upload_dir = "uploads/students/";
    if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $kcpe_result = moveAndSave("kcpe_result", $upload_dir);
    $kcpe_cert = moveAndSave("kcpe_certificate", $upload_dir);
    $leaving_cert = moveAndSave("leaving_certificate", $upload_dir);
    $other_doc = moveAndSave("other_documents", $upload_dir);
    $profile_photo = moveAndSave("profile_photo", $upload_dir);

    $stmt = $conn->prepare("INSERT INTO students_admitted 
        (full_name, gender, date_of_birth, applied_class, contact, email, address, parent_name, parent_contact, kcpe_result, kcpe_certificate, leaving_certificate, other_documents, profile_photo) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("ssssssssssssss", $full_name, $gender, $dob, $applied_class, $contact, $email, $address, $parent_name, $parent_contact, $kcpe_result, $kcpe_cert, $leaving_cert, $other_doc, $profile_photo);



    if($stmt->execute()){
        header("Location: thank_you.php");
        exit();
    } else {
        $message = "❌ Error: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Admission - Conquer High School</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    *{box-sizing:border-box;margin:0;padding:0;}
    body {font-family:Arial;background:#eef2f7;color:#333;padding-bottom:80px;}
    .navbar {position:sticky;top:0;background:#002244;color:#fff;padding:10px 20px;display:flex;justify-content:space-between;align-items:center;z-index:999;}
    .navbar a {color:white;margin:0 10px;text-decoration:none;}
    .navbar a:hover {text-decoration:underline;}
    .container {max-width:600px;margin:30px auto;background:#fff;padding:20px;border-radius:8px;box-shadow:0 0 10px #ccc;}
    h2 {text-align:center;color:#004080;margin-bottom:20px;}
    input, select, textarea {width:100%;margin:10px 0;padding:8px;border:1px solid #ccc;border-radius:4px;}
    label {margin-top:10px;display:block;}
    button {padding:10px;background:green;color:white;border:none;border-radius:5px;width:100%;cursor:pointer;}
    button:hover {background:darkgreen;}
    .preview {margin-top:10px;border:1px solid #ccc;padding:5px;background:#f9f9f9;}
    footer {text-align:center;background:#002244;color:white;padding:15px;margin-top:30px;}
    .dark {background:#121212;color:#ddd;}
    .toggle-dark {cursor:pointer;float:right;margin-top:-5px;}
    .preview-img {width:150px; border-radius: 8px; margin-top: 10px;}

    .bottom-nav {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        background: #003366;
        display: flex;
        justify-content: space-around;
        align-items: center;
        padding: 8px 0;
        z-index: 999;
        border-top: 2px solid #002244;
    }
    .bottom-nav a {
        color: white;
        text-align: center;
        text-decoration: none;
        font-size: 13px;
        line-height: 1.2;
    }
    .bottom-nav a:hover { text-decoration: underline; }

    #topBtn {
        position: fixed;
        bottom: 70px;
        right: 15px;
        background: #004080;
        color: white;
        border: none;
        border-radius: 50%;
        font-size: 16px;
        padding: 10px 14px;
        cursor: pointer;
        display: none;
        z-index: 1000;
    }
</style>
</head>
<body>

<!-- Navbar -->
<div class="navbar">
    <div>🏫 Conquer High School</div>
    <div>
        <a href="home.php">Home</a>
        <a href="about.php">About</a>
        <a href="academics.php">Academics</a>
        <a href="admission_upload_form.php">Admissions</a>
        <a href="media_gallery.php">Gallery</a>
        <a href="public_view_news.php">News</a>
        <a href="contact.php">Contact</a>
        <span class="toggle-dark" onclick="toggleDark()">🌙</span>
    </div>
</div>

<!-- Form -->
<div class="container">
    <h2>Student Admission Form</h2>
    <?php if($message) echo "<p style='color:red;text-align:center;'>$message</p>"; ?>

    <form method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
        <input type="text" name="full_name" placeholder="Full Name" required>
        <select name="gender" required>
            <option value="">--Select Gender--</option>
            <option>Male</option>
            <option>Female</option>
        </select>
        <label>Date of Birth</label>
        <input type="date" name="dob" required>
		<input type="text" name="applied_class" placeholder="Applied Class (e.g. Grade 9, Form 1)" required>
        <input type="text" name="contact" placeholder="Phone Number" required>
        <input type="email" name="email" placeholder="Email Address" required>
        <textarea name="address" placeholder="Home Address" required></textarea>
        <input type="text" name="parent_name" placeholder="Parent/Guardian Name" required>
        <input type="text" name="parent_contact" placeholder="Parent/Guardian Contact" required>

        <!-- 📷 Profile Photo Upload -->
        <label>Passport Photo (JPG/PNG)</label>
        <input type="file" name="profile_photo" accept="image/*" onchange="previewImage(this, 'photoPreview')" required>
        <img id="photoPreview" class="preview-img" />

        <!-- 📄 Document Uploads -->
        <label>KCPE Result Slip (PDF)</label>
        <input type="file" name="kcpe_result" accept="application/pdf" onchange="previewPDF(this, 'resultPreview')" required>
        <iframe id="resultPreview" class="preview" width="100%" height="200"></iframe>

        <label>KCPE Certificate (PDF)</label>
        <input type="file" name="kcpe_certificate" accept="application/pdf" onchange="previewPDF(this, 'certPreview')" required>
        <iframe id="certPreview" class="preview" width="100%" height="200"></iframe>

        <label>Leaving Certificate (PDF)</label>
        <input type="file" name="leaving_certificate" accept="application/pdf" onchange="previewPDF(this, 'leavingPreview')" required>
        <iframe id="leavingPreview" class="preview" width="100%" height="200"></iframe>

        <label>Other Documents (Optional, PDF)</label>
        <input type="file" name="other_documents" accept="application/pdf" onchange="previewPDF(this, 'otherPreview')">
        <iframe id="otherPreview" class="preview" width="100%" height="200"></iframe>

        <button type="submit">Submit Admission</button>
    </form>
</div>

<!-- Footer -->
<footer>
    &copy; <?= date('Y') ?> Conquer High School. All Rights Reserved.
    <br><a href="sitemap_view.php" style="color:white;">Sitemap</a> | <a href="privacy.php" style="color:white;">Privacy Policy</a>
</footer>

<!-- Bottom Navigation -->
<div class="bottom-nav">
    <a href="home.php">🏠<br>Home</a>
    <a href="about.php">📖<br>About</a>
    <a href="academics.php">📚<br>Academics</a>
    <a href="admission_upload_form.php">📝<br>Apply</a>
    <a href="public_view_news.php">📰<br>News</a>
    <a href="media_gallery.php">🖼️<br>Gallery</a>
    <a href="contact.php">☎️<br>Contact</a>
</div>

<!-- Back to Top -->
<button onclick="topFunction()" id="topBtn" title="Go to top">↑</button>

<!-- JavaScript -->
<script>
function previewPDF(input, iframeId) {
    const file = input.files[0];
    const iframe = document.getElementById(iframeId);
    if(file && file.type === 'application/pdf') {
        iframe.src = URL.createObjectURL(file);
    } else {
        iframe.src = "";
    }
}
function previewImage(input, imgId) {
    const file = input.files[0];
    const img = document.getElementById(imgId);
    if(file && file.type.startsWith("image/")) {
        img.src = URL.createObjectURL(file);
    } else {
        img.src = "";
    }
}
function toggleDark() {
    document.body.classList.toggle('dark');
}
window.onscroll = function(){scrollFunction()};
function scrollFunction(){
    document.getElementById("topBtn").style.display = 
        (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) ? "block" : "none";
}
function topFunction(){
    document.body.scrollTop = 0;
    document.documentElement.scrollTop = 0;
}
function validateForm() {
    let requiredFields = document.querySelectorAll("input[required], textarea[required], select[required]");
    for(let field of requiredFields){
        if(!field.value.trim()){
            alert("Please fill all required fields.");
            return false;
        }
    }
    return true;
}
</script>
</body>
</html>
