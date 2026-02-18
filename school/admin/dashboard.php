<?php
require "db.php";
if ($_SESSION["role"] != "admin") {
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        ul { list-style: none; padding: 0; }
        ul li { margin: 10px 0; }
        ul li a { display: block; padding: 10px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; text-align: center; }
        ul li a:hover { background: #0056b3; }
        .logout { text-align: center; margin-top: 20px; }
        .logout a { color: #dc3545; text-decoration: none; }
    </style>
</head>
<body>
    
    <div class="container">
        <h2>Admin Dashboard</h2>
        <nav>
            <a href="../index.php">Home</a>
            <a href="../aboutus.php">About Us</a>
            <a href="../contact.php">Contact</a>
         
        </nav>
        <img src="../download.jfif" alt="Dashboard Image" style="display:block; margin: 20px auto; max-width:100%; height:auto;">

        <div style="text-align:center; margin-top:10px;">
            <a href="../login.php#register">Create Account</a> |
            <a href="../login.php#forgot">Forgot Password</a>
        </div>
        <ul>
            <li><a href="add_student.php">Add Student</a></li>
            <li><a href="add_class.php">Add Class</a></li>
            <li><a href="add_subject.php">Add Subject</a></li>
            <li><a href="view_students.php">View Students</a></li>
            <li><a href="view_grades.php">View Grades</a></li>
            <li><a href="manage_users.php">Manage Users</a></li>
        </ul>
        <div class="logout">
            <a href="logout.php">Logout</a>
        </div>
    </div>
</body>
</html>
