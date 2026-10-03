<?php
// Disable exceptions so PHP relies on traditional if/else checks
mysqli_report(MYSQLI_REPORT_OFF);

// Connect to the database
$conn = new mysqli('localhost', 'root', '', 'app_db');

// Check the connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
} else {
    //echo "Connected successfully!"; // You can remove this line in production
}
?>