<?php
require "db.php";
if ($_SESSION["role"] != "admin") {
    header("Location: ../login.php");
    exit();
}

$msg = "";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $class_name = trim($_POST["class_name"]);

    if (empty($class_name)) {
        $errors['class_name'] = "Class name is required.";
    } elseif (!in_array($class_name, ['9', '10', '11', '12', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'])) {
        $errors['class_name'] = "Only grades 9-12 and classes A-J are allowed.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO classes(class_name) VALUES(?)");
        $stmt->bind_param("s", $class_name);
        $stmt->execute();
        $msg = "Class added successfully";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Class</title>
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
        <h2>Add Class</h2>
        <?php if ($msg): ?>
            <p class="success"><?= $msg ?></p>
        <?php endif; ?>

        <form method="post">
            <input type="text" name="class_name" placeholder="Grade (9-12) or Class (A-J)" value="<?= isset($_POST['class_name']) ? htmlspecialchars($_POST['class_name']) : '' ?>" required>
            <?php if (isset($errors['class_name'])): ?>
                <p class="error"><?= $errors['class_name'] ?></p>
            <?php endif; ?>

            <button type="submit">Add Class</button>
        </form>

        <div class="back">
            <a href="dashboard.php">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>