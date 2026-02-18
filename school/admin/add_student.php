<?php
require "db.php";
if ($_SESSION["role"] != "admin") {
    header("Location: ../login.php");
    exit();
}

$msg = "";
$errors = [];

// Fetch classes
$classes = $conn->query("SELECT id, class_name FROM classes WHERE class_name IN ('9','10','11','12','A','B','C','D','E','F','G','H','I','J')");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"] ?? '');
    $password = $_POST["password"] ?? '';
    $fullname = trim($_POST["fullname"] ?? '');
    $gender = $_POST["gender"] ?? '';
    $class_id = $_POST["class_id"] ?? '';
    $roll = trim($_POST["roll"] ?? '');

    if (empty($username)) {
        $errors['username'] = "Username is required.";
    }
    if (empty($password)) {
        $errors['password'] = "Password is required.";
    } elseif (strlen($password) < 6 || !preg_match('/\d/', $password)) {
        $errors['password'] = "Password must be at least 6 characters and include at least one number.";
    }
    if (empty($fullname)) {
        $errors['fullname'] = "Full name is required.";
    }
    if (empty($gender)) {
        $errors['gender'] = "Please select a gender.";
    }
    if (empty($class_id)) {
        $errors['class_id'] = "Please select a class.";
    } else {
        if (!ctype_digit($class_id)) {
            $errors['class_id'] = "Invalid class selection.";
        } else {
            $cstmt = $conn->prepare("SELECT id FROM classes WHERE id=?");
            $cstmt->bind_param("i", $class_id);
            $cstmt->execute();
            if ($cstmt->get_result()->num_rows == 0) {
                $errors['class_id'] = "Selected class not found.";
            }
        }
    }
    if (!empty($roll) && !ctype_digit($roll)) {
        $errors['roll'] = "Roll must be a number.";
    }

    if (empty($errors)) {
        // Check if username exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows == 0) {
            // Check roll uniqueness within class
            if ($roll !== '') {
                $rstmt = $conn->prepare("SELECT id FROM students WHERE class_id = ? AND roll = ? LIMIT 1");
                $rstmt->bind_param("is", $class_id, $roll);
                $rstmt->execute();
                $rres = $rstmt->get_result();
                if ($rres && $rres->num_rows > 0) {
                    $errors['roll'] = "This roll number is already assigned in the selected class.";
                }
            }

            if (empty($errors)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // insert into users
                $stmt = $conn->prepare("INSERT INTO users(username,password,role) VALUES(?,?,'student')");
                $stmt->bind_param("ss", $username, $hashed_password);
                $stmt->execute();
                $user_id = $stmt->insert_id;

                // insert into students
                $stmt2 = $conn->prepare(
                    "INSERT INTO students(user_id,full_name,gender,class_id,roll) VALUES(?,?,?,?,?)"
                );
                $stmt2->bind_param("issis", $user_id, $fullname, $gender, $class_id, $roll);
                $stmt2->execute();

                $msg = "Student added successfully";
            }
        } else {
            $errors['username'] = "Username already exists.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Student</title>
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
        <h2>Add Student</h2>
        <?php if ($msg): ?>
            <p class="success"><?= $msg ?></p>
        <?php endif; ?>

        <form method="post">
            <input type="text" name="username" placeholder="Username" required value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
            <?php if (isset($errors['username'])): ?>
                <p class="error"><?= $errors['username'] ?></p>
            <?php endif; ?>

            <input type="password" name="password" placeholder="Password" required pattern="(?=.*\d).{6,}" title="At least 6 characters and include at least one number">
            <?php if (isset($errors['password'])): ?>
                <p class="error"><?= $errors['password'] ?></p>
            <?php endif; ?>

            <input type="text" name="fullname" placeholder="Full Name" required value="<?= isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : '' ?>">
            <?php if (isset($errors['fullname'])): ?>
                <p class="error"><?= $errors['fullname'] ?></p>
            <?php endif; ?>

            <input type="number" name="roll" placeholder="Roll Number" min="1" value="<?= isset($_POST['roll']) ? htmlspecialchars($_POST['roll']) : '' ?>">
            <?php if (isset($errors['roll'])): ?>
                <p class="error"><?= $errors['roll'] ?></p>
            <?php endif; ?>

            <select name="gender" required>
                <option value="" disabled <?= !isset($_POST['gender']) ? 'selected' : '' ?>>Select Gender</option>
                <option value="male" <?= (isset($_POST['gender']) && $_POST['gender'] == 'male') ? 'selected' : '' ?>>Male</option>
                <option value="female" <?= (isset($_POST['gender']) && $_POST['gender'] == 'female') ? 'selected' : '' ?>>Female</option>
            </select>
            <?php if (isset($errors['gender'])): ?>
                <p class="error"><?= $errors['gender'] ?></p>
            <?php endif; ?>

            <select name="class_id" required>
                <option value="">Select Class</option>
                <?php $classes->data_seek(0); while($c = $classes->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($c['id']) ?>" <?= (isset($_POST['class_id']) && $_POST['class_id'] == $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['class_name']) ?></option>
                <?php endwhile; ?>
            </select>
            <?php if (isset($errors['class_id'])): ?>
                <p class="error"><?= $errors['class_id'] ?></p>
            <?php endif; ?>

            <button type="submit">Save Student</button>
        </form>

        <div class="back">
            <a href="dashboard.php">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>

