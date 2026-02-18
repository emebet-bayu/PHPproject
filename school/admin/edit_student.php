<?php
require "db.php";
if ($_SESSION["role"] != "admin") {
    header("Location: ../login.php");
    exit();
}

$id = $_GET['id'] ?? 0;
$msg = "";
$errors = [];

// Fetch classes
$classes = $conn->query("SELECT id, class_name FROM classes");

// Fetch student data
$stmt = $conn->prepare("SELECT * FROM students WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    die("Student not found.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST["fullname"] ?? '');
    $gender = $_POST["gender"] ?? '';
    $class_id = $_POST["class_id"] ?? '';
    $roll = trim($_POST["roll"] ?? '');

    if (empty($fullname)) {
        $errors['fullname'] = "Full name is required.";
    }
    if (empty($gender)) {
        $errors['gender'] = "Gender is required.";
    }
    if (empty($class_id)) {
        $errors['class_id'] = "Class is required.";
    }

    if (empty($errors)) {
        // Check roll uniqueness within class (exclude current student)
        if ($roll !== '') {
            $rstmt = $conn->prepare("SELECT id FROM students WHERE class_id = ? AND roll = ? AND id != ? LIMIT 1");
            $rstmt->bind_param("isi", $class_id, $roll, $id);
            $rstmt->execute();
            $rres = $rstmt->get_result();
            if ($rres && $rres->num_rows > 0) {
                $errors['roll'] = "This roll number is already assigned in the selected class.";
            }
        }

        if (empty($errors)) {
            $stmt = $conn->prepare("UPDATE students SET full_name=?, gender=?, class_id=?, roll=? WHERE id=?");
            $stmt->bind_param("ssisi", $fullname, $gender, $class_id, $roll, $id);
            $stmt->execute();
            $msg = "Student updated successfully";
            // Refresh data
            $stmt = $conn->prepare("SELECT * FROM students WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $student = $stmt->get_result()->fetch_assoc();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Student</title>
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
        <h2>Edit Student</h2>
        <?php if ($msg): ?>
            <p class="success"><?= $msg ?></p>
        <?php endif; ?>

        <form method="post">
            <input type="text" name="fullname" placeholder="Full Name" value="<?= htmlspecialchars($student['full_name']) ?>">
            <?php if (isset($errors['fullname'])): ?>
                <p class="error"><?= $errors['fullname'] ?></p>
            <?php endif; ?>

            <input type="text" name="roll" placeholder="Roll Number" value="<?= htmlspecialchars($student['roll'] ?? '') ?>">
            <?php if (isset($errors['roll'])): ?>
                <p class="error"><?= $errors['roll'] ?></p>
            <?php endif; ?>

            <select name="gender">
                <option value="">Select Gender</option>
                <option value="male" <?= $student['gender'] == 'male' ? 'selected' : '' ?>>Male</option>
                <option value="female" <?= $student['gender'] == 'female' ? 'selected' : '' ?>>Female</option>
            </select>
            <?php if (isset($errors['gender'])): ?>
                <p class="error"><?= $errors['gender'] ?></p>
            <?php endif; ?>

            <select name="class_id">
                <option value="">Select Class</option>
                <?php while($c = $classes->fetch_assoc()): ?>
                    <option value="<?= $c['id'] ?>" <?= $student['class_id'] == $c['id'] ? 'selected' : '' ?>><?= $c['class_name'] ?></option>
                <?php endwhile; ?>
            </select>
            <?php if (isset($errors['class_id'])): ?>
                <p class="error"><?= $errors['class_id'] ?></p>
            <?php endif; ?>

            <button type="submit">Update Student</button>
        </form>

        <div class="back">
            <a href="view_students.php">Back to Students</a>
        </div>
    </div>
</body>
</html>