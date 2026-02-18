<?php
require "../db.php";
if ($_SESSION["role"] != "teacher" && $_SESSION["role"] != "admin") {
    header("Location: ../../login.php");
    exit();
}

$id = $_GET['id'] ?? 0;
$msg = "";
$errors = [];

// Fetch students
$students = $conn->query("SELECT id, full_name FROM students");

// Fetch subjects
$subjects = $conn->query("SELECT id, name FROM subjects");

// Fetch grade data
$stmt = $conn->prepare("SELECT * FROM grades WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$grade = $stmt->get_result()->fetch_assoc();

if (!$grade) {
    die("Grade not found.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST["student_id"];
    $subject_id = $_POST["subject_id"];
    $grade_value = trim($_POST["grade"]);

    if (empty($student_id)) {
        $errors['student'] = "Please select a student.";
    }
    if (empty($subject_id)) {
        $errors['subject'] = "Please select a subject.";
    }
    if (empty($grade_value)) {
        $errors['grade'] = "Grade is required.";
    } elseif (!preg_match('/^[A-Ja-j](\+|-)?$|^pass$|^fail$|^[0-9]+(\.[0-9]+)?$/i', $grade_value)) {
        $errors['grade'] = "Invalid grade format. Use A-J, pass, fail, or numbers.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE grades SET student_id=?, subject_id=?, grade=? WHERE id=?");
        $stmt->bind_param("iisi", $student_id, $subject_id, $grade_value, $id);
        $stmt->execute();
        $msg = "Grade updated successfully";
        // Refresh data
        $stmt = $conn->prepare("SELECT * FROM grades WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $grade = $stmt->get_result()->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Grade</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        form { display: flex; flex-direction: column; }
        select, input { margin: 10px 0; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        button { padding: 10px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #218838; }
        .success { color: green; text-align: center; }
        .error { color: red; font-size: 0.9em; }
        .back { text-align: center; margin-top: 20px; }
        .back a { color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Edit Grade</h2>
        <?php if ($msg): ?>
            <p class="success"><?= $msg ?></p>
        <?php endif; ?>

        <form method="post">
            <select name="student_id">
                <option value="">Select Student</option>
                <?php while($s = $students->fetch_assoc()): ?>
                    <option value="<?= $s['id'] ?>" <?= $grade['student_id'] == $s['id'] ? 'selected' : '' ?>><?= $s['full_name'] ?></option>
                <?php endwhile; ?>
            </select>
            <?php if (isset($errors['student'])): ?>
                <p class="error"><?= $errors['student'] ?></p>
            <?php endif; ?>

            <select name="subject_id">
                <option value="">Select Subject</option>
                <?php while($sub = $subjects->fetch_assoc()): ?>
                    <option value="<?= $sub['id'] ?>" <?= $grade['subject_id'] == $sub['id'] ? 'selected' : '' ?>><?= $sub['name'] ?></option>
                <?php endwhile; ?>
            </select>
            <?php if (isset($errors['subject'])): ?>
                <p class="error"><?= $errors['subject'] ?></p>
            <?php endif; ?>

            <input type="text" name="grade" placeholder="Grade (A-J, pass, fail, 85…)" value="<?= htmlspecialchars($grade['grade']) ?>">
            <?php if (isset($errors['grade'])): ?>
                <p class="error"><?= $errors['grade'] ?></p>
            <?php endif; ?>

            <button type="submit">Update Grade</button>
        </form>

        <div class="back">
            <a href="<?= ($_SESSION['role'] === 'admin') ? '../view_grades.php' : 'view_grades.php' ?>">Back to Grades</a>
        </div>
    </div>
</body>
</html>