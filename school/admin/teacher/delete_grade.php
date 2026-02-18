<?php
require "../db.php";
if ($_SESSION["role"] != "teacher" && $_SESSION["role"] != "admin") {
    header("Location: ../../login.php");
    exit();
}

$id = $_GET['id'] ?? 0;

if ($id) {
    $stmt = $conn->prepare("DELETE FROM grades WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

// Redirect to appropriate grades view based on role
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: ../view_grades.php");
} else {
    header("Location: view_grades.php");
}
exit();
?>