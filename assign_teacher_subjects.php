<?php
/*-------------------------------------------------
  CONFIG & DB
-------------------------------------------------*/
session_start();
$conn = new mysqli("localhost", "root", "", "school");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

/*-------------------------------------------------
  HANDLE FORM SUBMISSION (ADD ASSIGNMENTS)
-------------------------------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['teacher_id'])) {
    $teacher_id  = $_POST['teacher_id'];
    $subject_ids = $_POST['subject_ids'] ?? [];
    $classes     = $_POST['classes']      ?? [];
    $streams     = $_POST['streams']      ?? [];

    $added = 0;
    for ($i = 0; $i < count($subject_ids); $i++) {
        $subject_id = intval($subject_ids[$i]);
        $class      = $classes[$i];
        $stream     = $streams[$i];

        /* duplicate check */
        $chk = $conn->prepare(
            "SELECT id FROM teacher_subjects
             WHERE teacher_id=? AND subject_id=? AND class=? AND stream=?"
        );
        $chk->bind_param("iiss", $teacher_id, $subject_id, $class, $stream);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) continue;   // already exists

        /* insert */
        $ins = $conn->prepare(
            "INSERT INTO teacher_subjects (teacher_id, subject_id, class, stream)
             VALUES (?,?,?,?)"
        );
        $ins->bind_param("iiss", $teacher_id, $subject_id, $class, $stream);
        if ($ins->execute()) $added++;
    }
    $_SESSION['msg'] = $added
        ? "✅ $added new assignment(s) added."
        : "⚠️ No new assignments (duplicates skipped).";
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

/*-------------------------------------------------
  FILTERS (FOR LIST VIEW)
-------------------------------------------------*/
$fClass   = $_GET['filter_class']   ?? '';
$fStream  = $_GET['filter_stream']  ?? '';

$where = [];
$params = [];
$types  = '';
if ($fClass  !== '') { $where[] = 'ts.class = ?';  $params[]=$fClass;  $types.='s'; }
if ($fStream !== '') { $where[] = 'ts.stream = ?'; $params[]=$fStream; $types.='s'; }

$sql = "SELECT ts.id, t.full_name, s.name AS subject,
               ts.class, ts.stream
        FROM teacher_subjects ts
        JOIN teachers t ON t.id = ts.teacher_id
        JOIN subject  s ON s.id = ts.subject_id".
        (count($where) ? ' WHERE '.implode(' AND ',$where) : '').
        " ORDER BY t.full_name, ts.class, ts.stream, subject";

$st  = $conn->prepare($sql);
if ($types) $st->bind_param($types, ...$params);
$st->execute();
$assignments = $st->get_result();

/*-------------------------------------------------
  FETCH DROPDOWNS
-------------------------------------------------*/
$teachers = $conn->query("SELECT id, full_name FROM teachers ORDER BY full_name");
$subjects = $conn->query("SELECT id, name FROM subject WHERE is_active=1 ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Teacher ⇄ Subject/Class/Stream Mapping</title>
<style>
 body{font-family:Arial;margin:20px;}
 h2{color:#0b3d91}
 label{display:inline-block;width:110px;font-weight:bold;margin-bottom:4px}
 select{padding:5px;margin:4px 0}
 form.assign,form.filter{background:#f7f9fc;padding:15px;border-radius:8px;width:680px;max-width:100%}
 table{border-collapse:collapse;width:100%;margin-top:20px}
 th,td{border:1px solid #ccc;padding:8px;text-align:left}
 th{background:#e9eef6}
 .msg{margin:15px 0;padding:10px;border-left:5px solid #4caf50;background:#eef7ee}
 .subject-block{border:1px dashed #ccc;padding:10px;margin:8px 0;position:relative}
 .remove-btn{position:absolute;right:5px;top:5px;background:#e74c3c;color:#fff;border:none;border-radius:4px;cursor:pointer;padding:2px 6px}
 .add-btn{margin:8px 0;padding:6px 10px}
</style>
<script>
function addBlock(){
    const tpl=document.querySelector('.subject-block');
    const clone=tpl.cloneNode(true);
    clone.querySelectorAll('select').forEach(sel=>sel.selectedIndex=0);
    document.getElementById('blocks').appendChild(clone);
}
function removeBlock(btn){
    const blocks=document.querySelectorAll('.subject-block');
    if(blocks.length>1) btn.parentElement.remove();
}
</script>
</head>
<body>

<h2>Assign Teacher to Subject + Class + Stream</h2>

<?php if(isset($_SESSION['msg'])): ?>
  <div class="msg"><?= $_SESSION['msg']; unset($_SESSION['msg']); ?></div>
<?php endif; ?>

<form class="assign" method="post">
  <!-- TEACHER -->
  <label>Teacher:</label>
  <select name="teacher_id" required>
    <option value="">-- select --</option>
    <?php while($t=$teachers->fetch_assoc()): ?>
      <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['full_name']) ?></option>
    <?php endwhile; ?>
  </select><br><br>

  <!-- REPEATABLE BLOCKS -->
  <div id="blocks">
    <div class="subject-block">
      <label>Subject:</label>
      <select name="subject_ids[]" required>
        <option value="">-- select --</option>
        <?php $subjects->data_seek(0); while($s=$subjects->fetch_assoc()): ?>
          <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
        <?php endwhile; ?>
      </select><br>

      <label>Class:</label>
      <select name="classes[]" required>
        <option value="Form 1">Form 1</option>
        <option value="Form 2">Form 2</option>
        <option value="Form 3">Form 3</option>
        <option value="Form 4">Form 4</option>
      </select><br>

      <label>Stream:</label>
      <select name="streams[]" required>
        <option value="North">North</option><option value="South">South</option>
        <option value="East">East</option><option value="West">West</option>
      </select>
      <button type="button" class="remove-btn" onclick="removeBlock(this)">×</button>
    </div>
  </div>

  <button type="button" class="add-btn" onclick="addBlock()">＋ Add another combination</button><br>
  <input type="submit" value="Save Assignment(s)">
</form>

<!-- FILTER -->
<form class="filter" method="get">
  <label>Class:</label>
  <select name="filter_class">
     <option value="">All</option>
     <?php foreach(['Form 1','Form 2','Form 3','Form 4'] as $c): ?>
       <option <?= $fClass==$c?'selected':'' ?>><?= $c ?></option>
     <?php endforeach; ?>
  </select>
  <label>Stream:</label>
  <select name="filter_stream">
    <option value="">All</option>
    <?php foreach(['North','South','East','West'] as $st): ?>
      <option <?= $fStream==$st?'selected':'' ?>><?= $st ?></option>
    <?php endforeach; ?>
  </select>
  <input type="submit" value="Apply filter">
</form>

<!-- TABLE -->
<table>
 <tr><th>Teacher</th><th>Subject</th><th>Class</th><th>Stream</th></tr>
 <?php while($row=$assignments->fetch_assoc()): ?>
   <tr>
     <td><?= htmlspecialchars($row['full_name'])  ?></td>
     <td><?= htmlspecialchars($row['subject'])    ?></td>
     <td><?= htmlspecialchars($row['class'])      ?></td>
     <td><?= htmlspecialchars($row['stream'])     ?></td>
   </tr>
 <?php endwhile; ?>
</table>

</body>
</html>
