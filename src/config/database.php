<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Default XAMPP user
define('DB_PASS', '');     // Default XAMPP password
define('DB_NAME', 'armaya_catering');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    // Log error instead of dying in production
    error_log("Database Connection failed: " . $conn->connect_error);
    // Display a user-friendly error message or redirect
    die("Maaf, terdapat masalah sambungan ke pangkalan data. Sila cuba lagi nanti.");
}

// Set charset
if (!$conn->set_charset("utf8mb4")) {
    error_log("Error loading character set utf8mb4: " . $conn->error);
}

// Optional: Function to close connection (call in footer or at script end)
/*
function close_db_connection($conn) {
    if ($conn) {
        $conn->close();
    }
}
*/
?> 