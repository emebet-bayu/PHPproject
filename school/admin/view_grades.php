<?php
require "db.php";
if ($_SESSION["role"] != "admin") {
    header("Location: ../login.php");
    exit();
}

// Fetch all grades (try including optional column; fallback if column missing)
$query = "SELECT grades.id AS grade_id, students.full_name, subjects.name AS subject, grades.grade, classes.class_name, grades.optional
    FROM grades
    JOIN students ON grades.student_id = students.id
    JOIN subjects ON grades.subject_id = subjects.id
    JOIN classes ON students.class_id = classes.id
    ORDER BY classes.class_name, students.full_name, subjects.name";

$result = $conn->query($query);
$errorMsg = "";
$has_optional = false;
if ($result === false) {
    // Try without optional column (for older DBs)
    $fallback_query = "SELECT grades.id AS grade_id, students.full_name, subjects.name AS subject, grades.grade, classes.class_name
        FROM grades
        JOIN students ON grades.student_id = students.id
        JOIN subjects ON grades.subject_id = subjects.id
        JOIN classes ON students.class_id = classes.id
        ORDER BY classes.class_name, students.full_name, subjects.name";
    $result = $conn->query($fallback_query);
    if ($result === false) {
        $errorMsg = "Error fetching grades: " . $conn->error;
        $result = null; // signal error
    }
} else {
    // detect if 'optional' exists in result fields
    foreach ($result->fetch_fields() as $f) {
        if ($f->name === 'optional') { $has_optional = true; break; }
    }
}
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
    </style>
</head>
<body>
    <div class="container">
        <h2>View All Grades</h2>
        <!-- <p style="text-align: center;"><a href="teacher/add_grade.php">Add Grade</a></p> -->
        <table>
            <tr>
                <th>Student Name</th>
                <th>Class</th>
                <th>Subject</th>
                <th>Grade</th>
                <?php if (isset($has_optional) && $has_optional): ?>
                    <th>Optional</th>
                <?php endif; ?>
                <th>Actions</th>
            </tr>
            <?php if ($result === null): ?>
                <tr><td colspan="<?php echo ($has_optional ? 6 : 5); ?>" style="color:red; text-align:center;"><?php echo htmlspecialchars($errorMsg); ?></td></tr>
            <?php else: ?>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['class_name']) ?></td>
                    <td><?= htmlspecialchars($row['subject']) ?></td>
                    <td><?= htmlspecialchars($row['grade']) ?></td>
                    <?php if (isset($has_optional) && $has_optional): ?>
                        <td><?= $row['optional'] ? 'Yes' : 'No' ?></td>
                    <?php endif; ?>
                    <td>
                        <a href="teacher/edit_grade.php?id=<?= htmlspecialchars($row['grade_id']) ?>">Edit</a> |
                        <a href="teacher/delete_grade.php?id=<?= htmlspecialchars($row['grade_id']) ?>" onclick="return confirm('Are you sure you want to delete this grade?')">Delete</a>
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