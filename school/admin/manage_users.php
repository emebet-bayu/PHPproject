<?php
require "db.php";
if ($_SESSION["role"] != "admin") {
    header("Location: ../login.php");
    exit();
}

$msg = "";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user'])) {
    $user_id = $_POST['delete_user_id'];
    
    // Get the role to handle cascading deletes
    $stmt = $conn->prepare("SELECT role FROM users WHERE id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    
    if ($user_data) {
        $role = $user_data['role'];
        
        // Delete messages first
        $delete_stmt = $conn->prepare("DELETE FROM messages WHERE from_user_id=? OR to_user_id=?");
        $delete_stmt->bind_param("ii", $user_id, $user_id);
        $delete_stmt->execute();
        
        if ($role == 'student') {
            // Get student id
            $stmt2 = $conn->prepare("SELECT id FROM students WHERE user_id=?");
            $stmt2->bind_param("i", $user_id);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            if ($student = $result2->fetch_assoc()) {
                $student_id = $student['id'];
                // Delete grades
                $delete_stmt = $conn->prepare("DELETE FROM grades WHERE student_id=?");
                $delete_stmt->bind_param("i", $student_id);
                $delete_stmt->execute();
            }
            // Delete student
            $delete_stmt = $conn->prepare("DELETE FROM students WHERE user_id=?");
            $delete_stmt->bind_param("i", $user_id);
            $delete_stmt->execute();
        } elseif ($role == 'teacher') {
            // Delete teacher
            $delete_stmt = $conn->prepare("DELETE FROM teachers WHERE user_id=?");
            $delete_stmt->bind_param("i", $user_id);
            $delete_stmt->execute();
        }
        // Delete from users table
        $delete_stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $delete_stmt->bind_param("i", $user_id);
        $delete_stmt->execute();
        
        $msg = "User deleted successfully.";
        // Refresh users list
        $users = $conn->query("SELECT u.id, u.username, u.role, s.full_name as student_name, t.full_name as teacher_name FROM users u LEFT JOIN students s ON u.id = s.user_id LEFT JOIN teachers t ON u.id = t.user_id ORDER BY u.id");
    }
}

$users = $conn->query("SELECT u.id, u.username, u.role, s.full_name as student_name, t.full_name as teacher_name FROM users u LEFT JOIN students s ON u.id = s.user_id LEFT JOIN teachers t ON u.id = t.user_id ORDER BY u.id");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['new_role'];

    if (empty($new_role)) {
        $errors['role'] = "Role is required.";
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE users SET role=? WHERE id=?");
        $stmt->bind_param("si", $new_role, $user_id);
        $stmt->execute();

        $msg = "Role updated successfully.";
        // Refresh users
        $users = $conn->query("SELECT u.id, u.username, u.role, s.full_name as student_name, t.full_name as teacher_name FROM users u LEFT JOIN students s ON u.id = s.user_id LEFT JOIN teachers t ON u.id = t.user_id ORDER BY u.id");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        nav { text-align: center; margin-bottom: 20px; }
        nav a { margin: 0 10px; color: #007bff; text-decoration: none; }
        nav a:hover { text-decoration: underline; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f2f2f2; }
        form { display: inline; }
        select { padding: 5px; }
        button { padding: 5px 10px; background: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background: #0056b3; }
        .success { color: green; text-align: center; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Manage Users</h2>
        <nav>
            <a href="../index.php">Home</a>
            <a href="../aboutus.php">About Us</a>
            <a href="../contact.php">Contact</a>
        </nav>
        <?php if ($msg): ?>
            <p class="success"><?= $msg ?></p>
        <?php endif; ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Role</th>
                <th>Full Name</th>
                <th>Actions</th>
            </tr>
            <?php while($user = $users->fetch_assoc()): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['username']) ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <select name="new_role">
                                <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="teacher" <?= $user['role'] == 'teacher' ? 'selected' : '' ?>>Teacher</option>
                                <option value="student" <?= $user['role'] == 'student' ? 'selected' : '' ?>>Student</option>
                            </select>
                            <button type="submit" name="update_role">Update</button>
                        </form>
                    </td>
                    <td><?= htmlspecialchars($user['student_name'] ?: $user['teacher_name'] ?: 'N/A') ?></td>
                    <td>
                        <form method="post" onsubmit="return confirm('Are you sure you want to delete this user?');">
                            <input type="hidden" name="delete_user_id" value="<?= $user['id'] ?>">
                            <button type="submit" name="delete_user" style="background: #dc3545; color: white; border: none; padding: 5px 10px; cursor: pointer;">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
        <p style="text-align: center; margin-top: 20px;"><a href="dashboard.php">Back to Dashboard</a></p>
    </div>
</body>
</html>