<?php
require "db.php";
if ($_SESSION['role'] != 'admin') {
    header('Location: ../login.php'); exit();
}

// Find duplicates by class and roll
$sql = "SELECT class_id, roll, COUNT(*) as cnt GROUP_CONCAT(id SEPARATOR ',') as ids
        FROM students
        WHERE roll IS NOT NULL AND roll != ''
        GROUP BY class_id, roll
        HAVING COUNT(*) > 1";

// MySQL doesn't allow GROUP_CONCAT in SELECT without GROUP BY in this exact form depending on version; do a safer query
$res = $conn->query("SELECT class_id, roll, COUNT(*) as cnt FROM students WHERE roll IS NOT NULL AND roll != '' GROUP BY class_id, roll HAVING COUNT(*) > 1");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Duplicate Rolls</title>
<style>body{font-family:Arial; padding:20px;} table{border-collapse:collapse; width:100%;} th,td{border:1px solid #ddd; padding:8px;} th{background:#f8f8f8}</style>
</head>
<body>
<h2>Duplicate Roll Numbers (per class)</h2>
<?php if ($res && $res->num_rows > 0): ?>
<table>
<tr><th>Class</th><th>Roll</th><th>Count</th><th>Students</th></tr>
<?php while($r = $res->fetch_assoc()):
    $class_name = '';
    $cid = $r['class_id'];
    $c = $conn->prepare("SELECT class_name FROM classes WHERE id = ? LIMIT 1");
    $c->bind_param("i", $cid); $c->execute(); $cres = $c->get_result(); if ($cres && $cres->num_rows) $class_name = $cres->fetch_assoc()['class_name'];
    $roll = $r['roll'];
    $students = $conn->query("SELECT id, full_name FROM students WHERE class_id = " . (int)$cid . " AND roll = '" . $conn->real_escape_string($roll) . "'");
?>
<tr>
    <td><?= htmlspecialchars($class_name) ?></td>
    <td><?= htmlspecialchars($roll) ?></td>
    <td><?= htmlspecialchars($r['cnt']) ?></td>
    <td>
        <?php while($s = $students->fetch_assoc()): ?>
            <?= htmlspecialchars($s['full_name']) ?> (<a href="edit_student.php?id=<?= $s['id'] ?>">Edit</a>)<br>
        <?php endwhile; ?>
    </td>
</tr>
<?php endwhile; ?>
</table>
<?php else: ?>
<p>No duplicate rolls found.</p>
<?php endif; ?>
<p><a href="dashboard.php">Back to Dashboard</a></p>
</body>
</html>