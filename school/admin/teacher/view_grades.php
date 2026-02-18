<?php
require "../db.php";
if ($_SESSION["role"] != "teacher") {
    header("Location: ../../login.php");
    exit();
}

// Fetch all grades (teachers can view all, or perhaps only their subjects, but for simplicity all)
$result = $conn->query("
    SELECT grades.id, students.full_name, subjects.name AS subject, grades.grade, classes.class_name
    FROM grades
    JOIN students ON grades.student_id = students.id
    JOIN subjects ON grades.subject_id = subjects.id
    JOIN classes ON students.class_id = classes.id
    ORDER BY classes.class_name, students.full_name, subjects.name
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Grades</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .back { text-align: center; margin-top: 20px; }
        .back a { color: #007bff; text-decoration: none; }
        .actions a { margin-right: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>View Grades</h2>
        <table>
            <tr>
                
                <th>Student Name</th>
                <th>Class</th>
                <th>Subject</th>
                <th>Score / Status</th>
                <th>Actions</th>
            </tr>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['full_name']) ?></td>
                <td><?= htmlspecialchars($row['class_name']) ?></td>
                <td><?= htmlspecialchars($row['subject']) ?></td>
                <td><?= htmlspecialchars($row['grade']) ?></td>
                <td class="actions">
                    <a href="edit_grade.php?id=<?= $row['id'] ?>">Edit</a>
                    <a href="delete_grade.php?id=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
        <div class="back">
            <a href="dashboard.php">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>