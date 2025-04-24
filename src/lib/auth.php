<?php

// Authentication and session management functions

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Checks if a user is currently logged in.
 * 
 * @return bool True if logged in, false otherwise.
 */
function is_logged_in(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Attempts to log in a user.
 * 
 * @param mysqli $conn Database connection.
 * @param string $username
 * @param string $password
 * @param string $role Expected role ('Admin', 'Staff', or 'Customer').
 * @return array|false User data array on success, false on failure.
 */
function attempt_login(mysqli $conn, string $username, string $password, string $role): array|false {
    // Use prepared statements to prevent SQL injection
    $sql = "SELECT user_id, username, password_hash, full_name, role FROM users WHERE username = ? AND role IN (?, ?)";
    $stmt = $conn->prepare($sql);
    
    // Allow Admin to login via Staff role selection
    $role1 = $role;
    $role2 = ($role === 'Staff') ? 'Admin' : $role; // If attempting Staff login, also allow Admin
    
    $stmt->bind_param("sss", $username, $role1, $role2);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // Verify password
        if (password_verify($password, $user['password_hash'])) {
            $stmt->close();
            return $user; // Login successful
        }
    }
    
    $stmt->close();
    return false; // Login failed
}

/**
 * Logs the current user out.
 * 
 * @return void
 */
function logout(): void {
    // Unset all session variables
    $_SESSION = array();

    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Destroy the session
    session_destroy();

    // Redirect to login page
    redirect('login.php'); // Assumes redirect function is available
}

/**
 * Redirects the user based on their role after login.
 * 
 * @return void
 */
function redirect_based_on_role(): void {
    if (!is_logged_in()) {
        redirect('login.php');
        return;
    }

    $role = $_SESSION['user_role'] ?? null;

    if ($role === 'Admin' || $role === 'Staff') {
        redirect('staff/index.php'); // Redirect Admin/Staff to staff dashboard
    } elseif ($role === 'Customer') {
        redirect('menu.php'); // Redirect Customer to menu page
    } else {
        // Default redirect if role is unknown (should not happen)
        redirect('login.php');
        logout(); // Log out if role is invalid
    }
}

/**
 * Requires the user to be logged in. Redirects to login if not.
 * Optionally requires a specific role.
 * 
 * @param string|null $required_role The role required ('Admin', 'Staff', 'Customer') or null if any logged-in user is okay.
 * @param string $redirect_url The URL to redirect to if check fails (default: login.php).
 * @return void
 */
function require_login(?string $required_role = null, string $redirect_url = 'login.php'): void {
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI']; // Store intended destination
        redirect($redirect_url);
        exit;
    }

    if ($required_role !== null) {
        $user_role = $_SESSION['user_role'] ?? null;
        // Allow Admin access if Staff is required
        $is_authorized = ($user_role === $required_role) || ($required_role === 'Staff' && $user_role === 'Admin');
        
        if (!$is_authorized) {
            // Handle unauthorized access - maybe redirect to a specific page or show error
            // For simplicity, redirecting back to their default area or login
            redirect_based_on_role(); 
            exit;
        }
    }
}

?> 