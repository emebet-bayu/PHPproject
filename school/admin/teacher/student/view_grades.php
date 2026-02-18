<?php
require "../../../admin/db.php";

$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['student','teacher','admin'])) {
    header("Location: ../../../login.php");
    exit();
}

$student = null;

if ($role === 'student') {
    $user_id = $_SESSION['user_id'] ?? 0;
    if ($user_id) {
        // Fetch the logged-in student's record
        $stmt = $conn->prepare("SELECT students.id, students.full_name, students.roll, classes.class_name FROM students LEFT JOIN classes ON students.class_id = classes.id WHERE students.user_id = ? LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $student = $res->fetch_assoc();
        if (!$student) {
            echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>My Grades</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); text-align: center; }
        .error { color: red; font-size: 18px; }
        .back { margin-top: 20px; }
        .back a { color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
    <div class='container'>
        <p class='error'>Student record not found. Please contact admin.</p>
        <div class='back'>
            <a href='dashbord.php'>Back to Dashboard</a>
        </div>
    </div>
</body>
</html>";
            exit();
        }
    } else {
        echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>My Grades</title>
</head>
<body>
    <div style='text-align:center;padding:20px;'>Not logged in.</div>
</body>
</html>";
        exit();
    }
} else {
    // teacher or admin must pass a student_id
    $sid = intval($_GET['student_id'] ?? $_GET['id'] ?? 0);
    if (!$sid) {
        echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Student Grades</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); text-align: center; }
        .error { color: red; font-size: 18px; }
        .back { margin-top: 20px; }
        .back a { color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
    <div class='container'>
        <p class='error'>No student selected. Please use the search and click "View Grades" for a student.</p>
        <div class='back'>
            <a href='../../dashboard.php'>Back to Dashboard</a>
        </div>
    </div>
</body>
</html>";
        exit();
    }

    $stmt = $conn->prepare("SELECT students.id, students.full_name, students.roll, classes.class_name FROM students LEFT JOIN classes ON students.class_id = classes.id WHERE students.id = ? LIMIT 1");
    $stmt->bind_param("i", $sid);
    $stmt->execute();
    $res = $stmt->get_result();
    $student = $res->fetch_assoc();
    if (!$student) {
        echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Student Grades</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); text-align: center; }
        .error { color: red; font-size: 18px; }
        .back { margin-top: 20px; }
        .back a { color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
    <div class='container'>
        <p class='error'>Student record not found.</p>
        <div class='back'>
            <a href='../../dashboard.php'>Back to Dashboard</a>
        </div>
    </div>
</body>
</html>";
        exit();
    }
}

$student_id = $student['id'];
$student_name = htmlspecialchars($student['full_name']);
$student_roll = htmlspecialchars($student['roll'] ?? '');
$class_name = htmlspecialchars($student['class_name'] ?? 'Unassigned');

// Fetch grades (group duplicates and show counts)
$sql = "
SELECT subjects.name AS subject, grades.grade, COUNT(*) AS occurrences
FROM grades
JOIN subjects ON grades.subject_id = subjects.id
WHERE grades.student_id = ?
GROUP BY subjects.name, grades.grade
ORDER BY subjects.name
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Grades</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 700px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .back { text-align: center; margin-top: 20px; }
        .back a { color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($role === 'student'): ?>
            <h2>My Grades</h2>
        <?php else: ?>
            <h2>Grades for <?= $student_name ?> — <?= $class_name ?> <?= $student_roll ? "(Roll: $student_roll)" : '' ?></h2>
        <?php endif; ?>

        <p style="text-align:right;"><a href="../../../login.php">Logout</a></p>

        <?php if ($result && $result->num_rows > 0): ?>
            <table>
                <tr>
                    <th>Subject</th>
                    <th>Score / Status</th>
                </tr>
                <?php while($g = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($g['subject']) ?></td>
                        <td><?= htmlspecialchars($g['grade']) ?><?php if(isset($g['occurrences']) && $g['occurrences'] > 1): ?> <em>(<?= $g['occurrences'] ?> duplicates)</em><?php endif; ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
            <p style="color:green; text-align:center;">Grades found for this student.</p>
        <?php else: ?>
            <p style="text-align:center;">No grades found for this student.</p>
        <?php endif; ?>

        <div class="back">
            <?php if ($role === 'student'): ?>
                <a href="dashbord.php">Back to Dashboard</a>
            <?php elseif ($role === 'teacher'): ?>
                <a href="../../dashboard.php">Back to Teacher Dashboard</a>
            <?php else: ?>
                <a href="../dashboard.php">Back to Admin Dashboard</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
