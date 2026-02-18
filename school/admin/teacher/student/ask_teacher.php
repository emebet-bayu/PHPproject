<?php
require "../../../admin/db.php";
if ($_SESSION["role"] != "student") {
    header("Location: ../../../login.php");
    exit();
}

$msg = "";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $message = trim($_POST["message"]);

    if (empty($message)) {
        $errors['message'] = "Message is required.";
    }

    if (empty($errors)) {
        // Assume sending to a teacher, for simplicity, send to user with role teacher
        $teacher_result = $conn->query("SELECT user_id FROM teachers LIMIT 1");
        if ($teacher_result->num_rows > 0) {
            $teacher = $teacher_result->fetch_assoc();
            $to_user_id = $teacher['user_id'];
            $from_user_id = $_SESSION['user_id'];

            $stmt = $conn->prepare("INSERT INTO messages(from_user_id, to_user_id, message) VALUES(?, ?, ?)");
            $stmt->bind_param("iis", $from_user_id, $to_user_id, $message);
            $stmt->execute();

            $msg = "Message sent successfully.";
        } else {
            $errors['general'] = "No teacher found.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ask Teacher</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        form { display: flex; flex-direction: column; }
        textarea { padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; }
        button { padding: 10px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .error { color: red; }
        .success { color: green; }
        a { display: block; text-align: center; margin-top: 20px; color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Ask Teacher</h2>
        <?php if (!empty($msg)) echo "<p class='success'>$msg</p>"; ?>
        <?php if (!empty($errors['general'])) echo "<p class='error'>{$errors['general']}</p>"; ?>
        <form method="post">
            <textarea name="message" rows="5" placeholder="Enter your question about unknown words..."></textarea>
            <?php if (!empty($errors['message'])) echo "<p class='error'>{$errors['message']}</p>"; ?>
            <button type="submit">Send Message</button>
        </form>
        <a href="dashbord.php">Back to Dashboard</a>
    </div>
</body>
</html>