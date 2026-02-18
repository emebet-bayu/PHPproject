<?php
require "db.php";

// Alter users table to add created_at column
$sql = "ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
if ($conn->query($sql) === TRUE) {
    echo "Column created_at added successfully.";
} else {
    echo "Error adding column: " . $conn->error;
}

$conn->close();
?>