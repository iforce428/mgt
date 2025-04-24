<?php
// Include configuration and functions
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// The PDO connection is already established in config.php
// This file serves as a central point to include both config and functions

// Function to close the database connection
function close_connection() {
    global $pdo;
    $pdo = null;
}

// Register shutdown function to close connection
register_shutdown_function('close_connection'); 