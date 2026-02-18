<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "school_management";


$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    // echo "Database created successfully or already exists.<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db($dbname);

// SQL to create tables
$tables = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin','teacher','student') NOT NULL
    )",

    "CREATE TABLE IF NOT EXISTS classes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        class_name VARCHAR(20) NOT NULL
    )",

    "CREATE TABLE IF NOT EXISTS students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        gender ENUM('male','female') NOT NULL,
        class_id INT NOT NULL,
        roll VARCHAR(10) DEFAULT NULL,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY(class_id) REFERENCES classes(id) ON DELETE CASCADE
    )",

    "CREATE TABLE IF NOT EXISTS teachers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        subject_id INT DEFAULT NULL,
        FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    "CREATE TABLE IF NOT EXISTS subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        class_id INT NOT NULL,
        FOREIGN KEY(class_id) REFERENCES classes(id) ON DELETE CASCADE
    )",

    "CREATE TABLE IF NOT EXISTS grades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        subject_id INT NOT NULL,
        grade VARCHAR(5) DEFAULT NULL,
        FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY(subject_id) REFERENCES subjects(id) ON DELETE CASCADE
    )",

    "CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        from_user_id INT NOT NULL,
        to_user_id INT NOT NULL,
        message TEXT NOT NULL,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY(from_user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY(to_user_id) REFERENCES users(id) ON DELETE CASCADE
    )"
];

// Execute table creation
foreach ($tables as $sql) {
    if ($conn->query($sql) === TRUE) {
        // Table created successfully
    } else {
        echo "Error creating table: " . $conn->error . "<br>";
    }
}

// Start session (only if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to get user role
function getUserRole() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

// Function to redirect based on role
function redirectBasedOnRole() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }

    $role = getUserRole();
    switch ($role) {
        case 'admin':
            header("Location: dashboard.php");
            break;
        case 'teacher':
            header("Location: teacher/dashboard.php");
            break;
        case 'student':
            header("Location: student/dashboard.php");
            break;
        default:
            header("Location: ../login.php");
            break;
    }
    exit();
}

// Function to sanitize input
function sanitizeInput($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($data));
}

// Function to hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Function to verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

?>
