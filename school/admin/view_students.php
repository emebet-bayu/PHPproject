<?php
require "db.php";
if ($_SESSION["role"] != "admin") {
    header("Location: ../login.php");
    exit();
}

// Fetch students with class (include all students and allow missing/Unassigned classes)
$result = $conn->query("
    SELECT students.id, students.full_name, students.roll, students.gender, classes.class_name
    FROM students
    LEFT JOIN classes ON students.class_id = classes.id
    ORDER BY classes.class_name, students.full_name
");

if ($result === false) {
    $errorMsg = "Error fetching students: " . $conn->error;
} else {
    $errorMsg = '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Students in Grades 9-12 and Classes A-J</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
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
        <h2>View Students in Grades 9-12 and Classes A-J</h2>
        <table>
            <tr>
                <th>Full Name</th>
                <th>Roll</th>
                <th>Gender</th>
                <th>Class</th>
                <th>Actions</th>
            </tr>
            <?php if ($errorMsg): ?>
                <tr><td colspan="5" style="color:red; text-align:center;"><?php echo htmlspecialchars($errorMsg); ?></td></tr>
            <?php else: ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['roll'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['gender']) ?></td>
                    <td><?= htmlspecialchars($row['class_name'] ?? 'Unassigned') ?></td>
                    <td class="actions">
                        <a href="edit_student.php?id=<?= $row['id'] ?>">Edit</a> |
                        <a href="delete_student.php?id=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')">Delete</a> |
                        <a href="report_card.php?id=<?= $row['id'] ?>">Report Card</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php endif; ?>
        </table>
        <div class="back">
            <a href="dashboard.php">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>