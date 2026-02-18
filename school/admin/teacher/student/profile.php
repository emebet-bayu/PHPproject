<?php
require "../../../admin/db.php";
if ($_SESSION["role"] != "student") {
    header("Location: ../../../login.php");
    exit();
}

$user_id = $_SESSION["user_id"];
$msg = "";
$errors = [];

// Get student data
$stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    die("Student not found.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST["fullname"] ?? '');
    $roll = trim($_POST["roll"] ?? '');

    if (empty($fullname)) {
        $errors['fullname'] = "Full name is required.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE students SET full_name=?, roll=? WHERE user_id=?");
        $stmt->bind_param("ssi", $fullname, $roll, $user_id);
        $stmt->execute();
        $msg = "Profile updated successfully";
        // Refresh data
        $stmt = $conn->prepare("SELECT * FROM students WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        form { display: flex; flex-direction: column; }
        input { margin: 10px 0; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
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
        <h2>My Profile</h2>
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

            <button type="submit">Update Profile</button>
        </form>

        <div class="back">
            <a href="dashbord.php">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>