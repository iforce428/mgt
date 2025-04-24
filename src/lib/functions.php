<?php

// General helper functions for the application

/**
 * Redirects to a specific page.
 * 
 * @param string $url The URL to redirect to.
 * @return void
 */
function redirect(string $url): void {
    header("Location: " . $url);
    exit;
}

/**
 * Escapes HTML special characters for safe output.
 * 
 * @param string|null $string The string to escape.
 * @return string The escaped string.
 */
function escape(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Require staff login
 * Redirects to login page if user is not logged in or is not staff/admin
 */
function require_staff_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }

    // Check if user is staff or admin
    $stmt = $GLOBALS['conn']->prepare("SELECT role FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user || !in_array($user['role'], ['Staff', 'Admin'])) {
        header('Location: /index.php');
        exit;
    }
}

// Add more helper functions as needed (e.g., date formatting, validation helpers)

?> 