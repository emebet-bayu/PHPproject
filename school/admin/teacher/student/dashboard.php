<?php
require "../../../admin/db.php";
if ($_SESSION["role"] != "student") {
    header("Location: ../../../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <img src="../download.jfif" alt="Dashboard Image" style="display:block; margin: 20px auto; max-width:100%; height:auto;">

    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #333; }
        ul { list-style: none; padding: 0; text-align: center; }
        ul li { display: inline; margin: 0 10px; }
        ul li a { color: #007bff; text-decoration: none; }
        ul li a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Student Dashboard</h2>
        <nav>
            <a href="../../../index.php">Home</a>
            <a href="../../../aboutus.php">About Us</a>
            <a href="../../../contact.php">Contact</a>
        </nav>
        <ul>
            <li><a href="view_grades.php">View Grades</a></li>
            <li><a href="profile.php">My Profile</a></li>
            <li><a href="ask_teacher.php">Ask Teacher</a></li>
            <li><a href="../../../admin/logout.php">Logout</a></li>
        </ul>
    </div>
</body>
</html>
