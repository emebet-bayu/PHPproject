<?php
require "db.php";
if ($_SESSION["role"] != "admin") {
    header("Location: ../login.php");
    exit();
}

$id = $_GET['id'] ?? 0;

if ($id) {
    // Get user_id
    $stmt = $conn->prepare("SELECT user_id FROM students WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $user_id = $student['user_id'];

    // Delete grades first
    $stmt = $conn->prepare("DELETE FROM grades WHERE student_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // Delete student
    $stmt = $conn->prepare("DELETE FROM students WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    // Delete user
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

header("Location: view_students.php");
exit();
?>