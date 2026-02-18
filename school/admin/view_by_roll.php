<?php
require "db.php";
$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['admin', 'teacher', 'student'])) {
    header("Location: ../login.php");
    exit();
}

$roll = trim($_GET['roll'] ?? '');
$students = [];
$error = '';

// If current user is a student, ignore GET and use their own roll
if ($role === 'student') {
    $user_id = $_SESSION['user_id'] ?? 0;
    if ($user_id) {
        $stmt = $conn->prepare("SELECT roll FROM students WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $rres = $stmt->get_result();
        if ($rres && $rres->num_rows > 0) {
            $rrow = $rres->fetch_assoc();
            $roll = $rrow['roll'] ?? '';
        } else {
            $error = 'Student record not found.';
        }
    } else {
        $error = 'Not logged in.';
    }
}

if ($roll !== '') {
    // Allow alphanumeric rolls (some datasets use words like 'student')
    if (!preg_match('/^[A-Za-z0-9_-]+$/', $roll)) {
        $error = 'Roll must be alphanumeric.';
    } else {
        // Find students by roll (include user_id for fallback lookups)
        $stmt = $conn->prepare("SELECT students.id, students.user_id, students.full_name, students.roll, students.gender, classes.class_name FROM students LEFT JOIN classes ON students.class_id = classes.id WHERE students.roll = ?");
        $stmt->bind_param("s", $roll);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            while ($r = $res->fetch_assoc()) $students[] = $r;
            if (count($students) > 1) {
                $warning = 'Multiple students found with this roll. Consider resolving duplicates or search by class + roll.';
            }
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
} else {
    if ($role !== 'student' && empty($roll)) {
        $error = 'Please provide a roll number.';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Student by Roll</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        form { display:flex; gap:8px; justify-content:center; margin-bottom:16px; }
        input[type="text"] { padding:8px; width:200px; }
        button { padding:8px 12px; }
        table { width:100%; border-collapse: collapse; }
        th, td { padding:8px; border-bottom:1px solid #ddd; text-align:left; }
        .error { color: red; text-align:center; }
        .back { text-align:center; margin-top:12px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>View Student by Roll Number</h2>
        <?php if ($role === 'admin'): ?>
            <p style="text-align:center;"><a href="view_grades.php">View All Grades</a></p>
        <?php elseif ($role === 'teacher'): ?>
            <p style="text-align:center;"><a href="teacher/view_grades.php">View All Grades</a></p>
        <?php endif; ?>
        <form method="get">
            <input type="text" name="roll" placeholder="Enter roll number" value="<?= htmlspecialchars($roll) ?>">
            <button type="submit">Search</button>
        </form>
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <?php if (!empty($warning)): ?>
            <p style="color:orange; text-align:center;"><?= htmlspecialchars($warning) ?></p>
        <?php endif; ?>

        <?php if ($roll !== '' && empty($students) && !$error): ?>
            <p style="text-align:center;">No student found with roll <?= htmlspecialchars($roll) ?>.</p>
        <?php endif; ?>

        <?php foreach ($students as $st): ?>

            <h3><?= htmlspecialchars($st['full_name']) ?> — <?= htmlspecialchars($st['class_name'] ?? 'Unassigned') ?> (Roll: <?= htmlspecialchars($st['roll']) ?>)</h3>
            <?php if (in_array($role, ['admin','teacher'])): ?>
                <p style="text-align:right;"><a href="teacher/student/view_grades.php?student_id=<?= urlencode($st['id']) ?>">Open student page</a></p>
            <?php endif; ?>
            <?php
            // Primary lookup by students.id (group duplicates and show counts)
            $stmt = $conn->prepare("SELECT subjects.name AS subject, grades.grade, COUNT(*) AS occurrences FROM grades JOIN subjects ON grades.subject_id = subjects.id WHERE grades.student_id = ? GROUP BY subjects.name, grades.grade ORDER BY subjects.name");
            $stmt->bind_param("i", $st['id']);
            $stmt->execute();
            $gres = $stmt->get_result();

            // Fallback: if no grades found, try using the student's user_id in case grades were stored with user id by mistake
            $fallback_used = false;
            if (!($gres && $gres->num_rows > 0)) {
                if (!empty($st['user_id'])) {
                    $stmt2 = $conn->prepare("SELECT subjects.name AS subject, grades.grade, COUNT(*) AS occurrences FROM grades JOIN subjects ON grades.subject_id = subjects.id WHERE grades.student_id = ? GROUP BY subjects.name, grades.grade ORDER BY subjects.name");
                    $stmt2->bind_param("i", $st['user_id']);
                    $stmt2->execute();
                    $gres2 = $stmt2->get_result();
                    if ($gres2 && $gres2->num_rows > 0) {
                        $gres = $gres2;
                        $fallback_used = true;
                    }
                }
            }
            ?>
            <?php if ($gres && $gres->num_rows > 0): ?>
                <?php if ($fallback_used): ?>
                    <p style="color:orange;">Note: grades found using a fallback lookup (possible data mismatch). Admin may want to inspect grade records.</p>
                <?php endif; ?>
                <table>
                    <tr><th>Subject</th><th>Score / Status</th></tr>
                    <?php while($g = $gres->fetch_assoc()): ?>
                        <tr><td><?= htmlspecialchars($g['subject']) ?></td><td><?= htmlspecialchars($g['grade']) ?><?php if(isset($g['occurrences']) && $g['occurrences'] > 1): ?> <em>(<?= $g['occurrences'] ?> duplicates)</em><?php endif; ?></td></tr>
                    <?php endwhile; ?>
                </table>
                <p style="color:green; text-align:center;">Grades found for this student.</p>
            <?php else: ?>
                <p style="text-align:center;">No grades found for this student.</p>
            <?php endif; ?>
            <hr>
        <?php endforeach; ?>

        <div class="back"><a href="dashboard.php">Back to Dashboard</a></div>
    </div>
</body>
</html>