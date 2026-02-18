<?php
require "../db.php";
if ($_SESSION["role"] != "teacher") {
    header("Location: ../../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard</title>
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
        <h2>Teacher Dashboard</h2>
        <ul>
            <li><a href="add_grade.php">Add Grades</a></li>
            <li><a href="view_grades.php">View Grades</a></li>
            <li><a href="view_messages.php"></a></li>
            <li><a href="../logout.php">Logout</a></li>
        </ul>
    </div>
</body>
</html>
