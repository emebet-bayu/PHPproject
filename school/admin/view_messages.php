<?php
require "db.php";
if ($_SESSION["role"] != "admin") {
    header("Location: ../login.php");
    exit();
}

$messages = $conn->query("SELECT m.message, m.timestamp, u.username FROM messages m JOIN users u ON m.from_user_id = u.id WHERE m.to_user_id = {$_SESSION['user_id']} ORDER BY m.timestamp DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Messages</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        .message { border-bottom: 1px solid #ccc; padding: 10px 0; }
        .message strong { color: #007bff; }
        .timestamp { color: #666; font-size: 0.9em; }
        a { display: block; text-align: center; margin-top: 20px; color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Messages from Users</h2>
        <?php if ($messages->num_rows > 0): ?>
            <?php while($msg = $messages->fetch_assoc()): ?>
                <div class="message">
                    <strong>From: <?= htmlspecialchars($msg['username']) ?></strong>
                    <p><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
                    <span class="timestamp"><?= $msg['timestamp'] ?></span>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No messages yet.</p>
        <?php endif; ?>
        <a href="dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>