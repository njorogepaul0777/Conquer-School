<!DOCTYPE html>
<html>
<head>
    <title>Upload Syllabus</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        /* ==============================
           Global Styles
        ============================== */
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, sans-serif; display: flex; }

        /* ==============================
           Sidebar Styling
        ============================== */
        .sidebar {
            width: 240px;
            background: #002b5c; /* Dark blue background */
            color: white;
            padding-top: 20px;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            overflow-y: auto; /* Scroll if content too long */
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
            background-color: #014a99; /* Slightly lighter blue on hover */
        }

        /* ==============================
           Collapsible Menu Arrow Styling
        ============================== */
        details summary {
            position: relative;
            padding: 12px 20px 12px 20px;
            cursor: pointer;
            font-size: 15px;
            list-style: none; /* Remove default marker */
        }
        details summary::-webkit-details-marker {
            display: none; /* Hide default arrow in Chrome/Safari */
        }
        details summary::after {
            content: "▼"; /* Downward arrow */
            position: absolute;
            right: 15px;
            font-size: 12px;
            transition: transform 0.2s ease; /* Smooth rotation */
        }
        details[open] summary::after {
            transform: rotate(180deg); /* Rotate arrow when open */
        }

        /* Nested links inside collapsible details */
        details a {
            padding-left: 35px;
            font-size: 14px;
        }

        /* ==============================
           Main Content Area
        ============================== */
        .main {
            margin-left: 240px; /* Leave space for sidebar */
            padding: 30px;
            width: calc(100% - 240px);
            background: #f4f4f4;
            min-height: 100vh;
        }

        /* ==============================
           Top Bar
        ============================== */
        .topbar {
            background: white;
            padding: 15px 20px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 5px #ccc; /* Light shadow */
        }

        .topbar h1 {
            margin: 0;
            font-size: 20px;
            color: #003366;
        }

        /* Logout button */
        .logout {
            background: #cc0000;
            color: white;
            padding: 8px 12px;
            border-radius: 5px;
            text-decoration: none;
        }

        /* ==============================
           Upload Form Styling
        ============================== */
        .upload-form {
            background: white;
            padding: 25px;
            border-radius: 10px;
            max-width: 600px;
            margin: auto;
            box-shadow: 0 0 10px #ccc;
        }

        .upload-form h2 {
            text-align: center;
            color: #004080;
            margin-bottom: 20px;
        }

        .upload-form select,
        .upload-form input[type="file"],
        .upload-form button {
            width: 100%;
            padding: 12px;
            margin-top: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        /* Submit button styling */
        .upload-form button {
            background: #004080;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        .upload-form button:hover {
            background: #0066cc;
        }

        /* Message feedback styling */
        .message {
            text-align: center;
            margin-top: 10px;
            font-size: 15px;
        }

        /* ==============================
           Mobile Responsive Styling
        ============================== */
        @media (max-width: 768px) {
            .sidebar { width: 100%; position: static; }
            .main { margin-left: 0; width: 100%; }
        }
    </style>
</head>
<body>

<!-- ==============================
     Sidebar Navigation Menu
============================== -->
<div class="sidebar">
    <h2>Admin Panel</h2>
    <a href="admin_dashboard.php" class="active">🏠 Dashboard</a>

    <details>
        <summary>🎓 Admissions</summary>
        <a href="admin_admissions.php">• Manage Admissions</a>
        <a href="admin_contact_student.php">• Student Messages</a>
    </details>

    <details>
        <summary>📚 Academics</summary>
        <a href="admin_manage_results.php">• Manage Results</a>
        <a href="admin_subjects.php">• Subjects</a>
        <a href="admin_upload_syllabus.php">• Upload Syllabus</a>
        <a href="admin_upload_teacher.php">• Teachers</a>
    </details>

    <details>
        <summary>🖼️ Media & News</summary>
        <a href="admin_upload_media.php">• Media Gallery</a>
        <a href="upload_news.php">• News & Events</a>
    </details>

    <details>
        <summary>👥 Interaction</summary>
        <a href="admin_contact.php">• Contact Messages</a>
        <a href="admin_faqs.php">• FAQs</a>
        <a href="admin_manage_testimonials.php">• Testimonials</a>
        <a href="subscribers.php">• Subscribers</a>
    </details>

    <a href="admin_manage_staffs.php">👩‍🏫 Manage Staff</a>
    <a href="admin_logout.php">🚪 Logout</a>
</div>

<!-- ==============================
     Main Content Area
============================== -->
<div class="main">
    <div class="topbar">
        <h1>📝 Upload Syllabus</h1>
        <a href="admin_logout.php" class="logout">Logout</a>
    </div>

    <div class="upload-form">
        <h2>Upload Subject Syllabus</h2>

        <!-- Display upload success/error message -->
        <?= $message ?>

        <!-- Upload form -->
        <form method="POST" enctype="multipart/form-data">
            <!-- Subject selection -->
            <label for="subject_id">Select Subject:</label>
            <select name="subject_id" id="subject_id" required>
                <option value="">-- Select Subject --</option>
                <?php while ($sub = $subjects->fetch_assoc()): ?>
                    <option value="<?= $sub['id'] ?>">
                        <?= htmlspecialchars($sub['name']) ?> (<?= htmlspecialchars($sub['curriculum']) ?>)
                    </option>
                <?php endwhile; ?>
            </select>

            <!-- File selection -->
            <label for="syllabus_file">Choose File (PDF/DOC/DOCX):</label>
            <input type="file" name="syllabus_file" id="syllabus_file" accept=".pdf,.doc,.docx" required>

            <!-- Submit button -->
            <button type="submit">Upload Syllabus</button>
        </form>
    </div>
</div>

</body>
</html>
