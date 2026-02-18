<?php
require "admin/db.php";

// Alter grades table to add optional column
$sql = "ALTER TABLE grades ADD COLUMN optional BOOLEAN DEFAULT FALSE";
if ($conn->query($sql) === TRUE) {
    echo "Column optional added successfully.";
} else {
    echo "Error adding column: " . $conn->error;
}

$conn->close();
?>