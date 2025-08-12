<?php
$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$faqs = $conn->query("SELECT * FROM faqs ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>FAQs - Conquer High School</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background-color: #f4f4f4; }
        .faq-container { max-width: 800px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .faq-item { border-bottom: 1px solid #ddd; padding: 15px 0; }
        .faq-question { font-weight: bold; cursor: pointer; position: relative; }
        .faq-question::after {
            content: "+";
            position: absolute;
            right: 0;
        }
        .faq-question.active::after {
            content: "-";
        }
        .faq-answer { display: none; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="faq-container">
        <h2>Frequently Asked Questions</h2>
        <?php while ($row = $faqs->fetch_assoc()): ?>
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    <?= htmlspecialchars($row['question']) ?>
                </div>
                <div class="faq-answer">
                    <?= nl2br(htmlspecialchars($row['answer'])) ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <script>
        function toggleFAQ(element) {
            element.classList.toggle('active');
            const answer = element.nextElementSibling;
            answer.style.display = answer.style.display === 'block' ? 'none' : 'block';
        }
    </script>
</body>
</html>
