<?php
require "../db.php";
if ($_SESSION["role"] != "teacher") {
    header("Location: ../../login.php");
    exit();
}

$msg = "";
$errors = [];

// Fetch students
$students = $conn->query("SELECT id, full_name FROM students");

// Fetch subjects
$subjects = $conn->query("SELECT id, name FROM subjects");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST["student_id"];
    $subject_id = $_POST["subject_id"];
    $score = trim($_POST["score"]);
    $status = trim($_POST["status"]);

    if (empty($student_id)) {
        $errors['student'] = "Please select a student.";
    }
    if (empty($subject_id)) {
        $errors['subject'] = "Please select a subject.";
    }
    if ($score === '') {
        $errors['score'] = "Score is required.";
    } elseif (!is_numeric($score) || $score < 0 || $score > 100) {
        $errors['score'] = "Score must be a number between 0 and 100.";
    }
    if (empty($status)) {
        $errors['status'] = "Status is required.";
    }

    if (empty($errors)) {
        $grade = $score . '/' . $status;
        $stmt = $conn->prepare(
            "INSERT INTO grades(student_id, subject_id, grade)
             VALUES(?,?,?)"
        );
        $stmt->bind_param("iis", $student_id, $subject_id, $grade);
        $stmt->execute();

        $msg = "Grade saved successfully";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        ul { list-style: none; padding: 0; text-align: center; margin-bottom: 20px; }
        ul li { display: inline; margin: 0 10px; }
        ul li a { color: #007bff; text-decoration: none; }
        ul li a:hover { text-decoration: underline; }
        form { margin-top: 20px; }
        select, input { margin: 10px 0; padding: 10px; border: 1px solid #ccc; border-radius: 4px; width: 100%; box-sizing: border-box; }
        button { padding: 10px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; width: 100%; }
        button:hover { background: #218838; }
        .success { color: green; text-align: center; }
        .error { color: red; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Teacher Dashboard</h2>
        <nav>
            <a href="../../index.php">Home</a>
            <a href="../../aboutus.php">About Us</a>
            <a href="../../contact.php">Contact</a>
        </nav>
        <ul>
            <li><a href="view_grades.php">View Grades</a></li>
       
            <li><a href="../logout.php">Logout</a></li>
        </ul>

        <h3>Add Student Grade</h3>
        <?php if ($msg): ?>
            <p class="success"><?= $msg ?></p>
        <?php endif; ?>

        <form method="post">
            <select name="student_id">
                <option value="">Select Student</option>
                <?php while($s = $students->fetch_assoc()): ?>
                    <option value="<?= $s['id'] ?>" <?= (isset($_POST['student_id']) && $_POST['student_id'] == $s['id']) ? 'selected' : '' ?>><?= $s['full_name'] ?></option>
                <?php endwhile; ?>
            </select>
            <?php if (isset($errors['student'])): ?>
                <p class="error"><?= $errors['student'] ?></p>
            <?php endif; ?>

            <select name="subject_id">
                <option value="">Select Subject</option>
                <?php while($sub = $subjects->fetch_assoc()): ?>
                    <option value="<?= $sub['id'] ?>" <?= (isset($_POST['subject_id']) && $_POST['subject_id'] == $sub['id']) ? 'selected' : '' ?>><?= $sub['name'] ?></option>
                <?php endwhile; ?>
            </select>
            <?php if (isset($errors['subject'])): ?>
                <p class="error"><?= $errors['subject'] ?></p>
            <?php endif; ?>

            <input type="number" name="score" min="0" max="100" placeholder="Score (0-100)" value="<?= isset($_POST['score']) ? htmlspecialchars($_POST['score']) : '' ?>">
            <?php if (isset($errors['score'])): ?>
                <p class="error"><?= $errors['score'] ?></p>
            <?php endif; ?>

            <select name="status">
                <option value="">Select Status</option>
                <option value="pass">Pass</option>
                <option value="fail">Fail</option>
            </select>
            <?php if (isset($errors['status'])): ?>
                <p class="error"><?= $errors['status'] ?></p>
            <?php endif; ?>

            <button type="submit">Save Grade</button>
        </form>
    </div>
</body>
</html>
