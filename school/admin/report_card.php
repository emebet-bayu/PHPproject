<?php
require "db.php";
if ($_SESSION["role"] != "admin") {
    header("Location: ../login.php");
    exit();
}

$student_id = $_GET['id'] ?? 0;

if (!$student_id) {
    die("Student ID required.");
}

// Get student info
$stmt = $conn->prepare("SELECT students.full_name, students.roll, classes.class_name FROM students JOIN classes ON students.class_id = classes.id WHERE students.id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    die("Student not found.");
}

// Get grades
$grades = $conn->query("SELECT subjects.name, grades.grade FROM grades JOIN subjects ON grades.subject_id = subjects.id WHERE grades.student_id = $student_id");

// Calculate average
$total = 0;
$count = 0;
$grade_list = [];
while ($row = $grades->fetch_assoc()) {
    $grade_list[] = $row;
    // Convert grade to number if possible
    $g = $row['grade'];
    if (is_numeric($g)) {
        $total += $g;
        $count++;
    } elseif (strtolower($g) == 'pass') {
        $total += 100;
        $count++;
    } elseif (strtolower($g) == 'fail') {
        $total += 0;
        $count++;
    } elseif (preg_match('/^([A-J])(\+|-)?$/i', $g, $matches)) {
        $letter = strtoupper($matches[1]);
        $modifier = $matches[2] ?? '';
        $base = ord($letter) - ord('A') + 1; // A=1, B=2, ..., J=10
        $score = 100 - ($base - 1) * 10; // A=100, B=90, etc.
        if ($modifier == '+') $score += 5;
        elseif ($modifier == '-') $score -= 5;
        $total += $score;
        $count++;
    }
}

$average = $count > 0 ? $total / $count : 0;
$pass_fail = $average >= 50 ? 'Pass' : 'Fail';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Card</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .card { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .summary { margin-top: 20px; font-weight: bold; }
        .pass { color: green; }
        .fail { color: red; }
        .print { text-align: center; margin-top: 20px; }
        .print button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .print button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Report Card</h2>
        <p><strong>Student Name:</strong> <?= htmlspecialchars($student['full_name']) ?></p>
        <p><strong>Roll Number:</strong> <?= htmlspecialchars($student['roll'] ?? '') ?></p>
        <p><strong>Class:</strong> <?= htmlspecialchars($student['class_name']) ?></p>

        <table>
            <tr>
                <th>Subject</th>
                <th>Grade</th>
            </tr>
            <?php foreach ($grade_list as $g): ?>
            <tr>
                <td><?= htmlspecialchars($g['name']) ?></td>
                <td><?= htmlspecialchars($g['grade']) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <div class="summary">
            <p>Average Score: <?= number_format($average, 2) ?>/100</p>
            <p class="<?= strtolower($pass_fail) ?>">Result: <?= $pass_fail ?></p>
        </div>

        <div class="print">
            <button onclick="window.print()">Print Report Card</button>
        </div>
    </div>
</body>
</html>