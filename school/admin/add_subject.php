<?php
require "db.php";
if ($_SESSION["role"] != "admin") {
    header("Location: ../login.php");
    exit();
}

$msg = "";
$errors = [];

// Fetch classes
$classes = $conn->query("SELECT id, class_name FROM classes");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $class_id = $_POST["class_id"];

    if (empty($name)) {
        $errors['name'] = "Subject name is required.";
    }
    if (empty($class_id)) {
        $errors['class_id'] = "Please select a class.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO subjects(name, class_id) VALUES(?,?)");
        $stmt->bind_param("si", $name, $class_id);
        $stmt->execute();
        $msg = "Subject added successfully";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Subject</title>
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
        <h2>Add Subject</h2>
        <?php if ($msg): ?>
            <p class="success"><?= $msg ?></p>
        <?php endif; ?>

        <form method="post">
            <input type="text" name="name" placeholder="Subject Name" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" required>
            <?php if (isset($errors['name'])): ?>
                <p class="error"><?= $errors['name'] ?></p>
            <?php endif; ?>

            <select name="class_id" required>
                <option value="">Select Class</option>
                <?php while($c = $classes->fetch_assoc()): ?>
                    <option value="<?= $c['id'] ?>" <?= (isset($_POST['class_id']) && $_POST['class_id'] == $c['id']) ? 'selected' : '' ?>><?= $c['class_name'] ?></option>
                <?php endwhile; ?>
            </select>
            <?php if (isset($errors['class_id'])): ?>
                <p class="error"><?= $errors['class_id'] ?></p>
            <?php endif; ?>

            <button type="submit">Add Subject</button>
        </form>

        <div class="back">
            <a href="dashboard.php">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>