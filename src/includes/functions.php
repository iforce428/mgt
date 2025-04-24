<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Global variable for login status
$is_logged_in = isset($_SESSION['user_id']);

/**
 * Helper functions for the application
 */

/**
 * Get current page name
 * 
 * @return string The current page filename
 */
function get_current_page() {
    return basename($_SERVER['PHP_SELF']);
}

/**
 * Initialize page variables
 * Sets up common variables needed across pages
 * 
 * @return array Array of page variables
 */
function init_page_variables() {
    $vars = array(
        'page_title' => 'Armaya Enterprise',
        'current_page' => get_current_page(),
        'is_logged_in' => false,
        'user_role' => null,
        'user_name' => null
    );

    // Check if user is logged in
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        $vars['is_logged_in'] = true;
        $vars['user_role'] = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
        $vars['user_name'] = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : null;
    }

    // Set page-specific titles
    switch ($vars['current_page']) {
        case 'index.php':
            $vars['page_title'] = 'Armaya Enterprise - Catering Services';
            break;
        case 'menu.php':
            $vars['page_title'] = 'Menu - Armaya Enterprise';
            break;
        case 'login.php':
            $vars['page_title'] = 'Log Masuk - Armaya Enterprise';
            break;
        // Add more pages as needed
    }

    return $vars;
}

/**
 * Get the public URL for a path
 * @param string $path The path to append to the base URL
 * @return string The full public URL
 */
function public_url($path = '') {
    // Remove leading slash if present
    $path = ltrim($path, '/');
    
    // Get the base URL from server variables
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    
    // Get the directory path
    $dir = dirname(dirname($_SERVER['SCRIPT_NAME']));
    
    // Remove any double slashes
    $dir = rtrim($dir, '/');
    
    // Build the base URL
    $baseUrl = $protocol . '://' . $host . $dir;
    
    // Return the full URL
    return $baseUrl . '/' . $path;
}

/**
 * Escape string to prevent XSS attacks
 * 
 * @param string $str The string to escape
 * @return string The escaped string
 */
function escape($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Format date to a readable format
 * 
 * @param string $date The date to format (MySQL format)
 * @param string $format The format to use (default: 'd-m-Y H:i')
 * @return string The formatted date
 */
function format_date($date, $format = 'd-m-Y H:i') {
    return date($format, strtotime($date));
}

/**
 * Format price with RM symbol
 * 
 * @param float $price The price to format
 * @return string The formatted price
 */
function format_price($price) {
    return 'RM ' . number_format($price, 2);
}

/**
 * Get current page URL
 * 
 * @return string The current page URL
 */
function current_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Check if a user is logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get user role if logged in
 * 
 * @return string|null User role or null if not logged in
 */
function get_user_role() {
    return isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
}

/**
 * Redirect to a specific page with optional flash message
 * 
 * @param string $path The path to redirect to
 * @param array|null $with_message Optional flash message array with 'type' and 'message' keys
 * @return void
 */
function redirect($path, $with_message = null) {
    if ($with_message) {
        set_flash_message($with_message['type'], $with_message['message']);
    }
    header("Location: " . $path);
    exit();
}

/**
 * Generate a random string
 * 
 * @param int $length The length of the string
 * @return string The random string
 */
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

/**
 * Display flash message
 * @param string $type The type of message (success, error, warning, info)
 * @param string $message The message to display
 */
function set_flash_message($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 * @return array|null The flash message array or null if none exists
 */
function get_flash_message() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Sanitize user input
 * @param string $data The data to sanitize
 * @return string The sanitized data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Validate Malaysian phone number format
 * @param string $phone The phone number to validate
 * @return bool Whether the phone number is valid
 */
function validate_phone($phone) {
    // Malaysian phone number format
    return preg_match('/^(\+?6?01)[0-46-9]-*[0-9]{7,8}$/', $phone);
}

/**
 * Check if username already exists
 * @param PDO $pdo The database connection
 * @param string $username The username to check
 * @return bool Whether the username exists
 */
function username_exists($pdo, $username) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Check if phone number already exists
 * @param PDO $pdo The database connection
 * @param string $phone The phone number to check
 * @return bool Whether the phone number exists
 */
function phone_exists($pdo, $phone) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Get user data by ID
 * @param int $user_id The user ID
 * @return array|false The user data or false if not found
 */
function get_user_by_id($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Initialize page variables at the end of the file
$page_vars = init_page_variables();
extract($page_vars);

// Input Handling
function validate_my_phone($phone) {
    // Remove any non-digit characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if the number starts with '60' (country code) or '0'
    if (strlen($phone) >= 10 && strlen($phone) <= 12) {
        if (substr($phone, 0, 2) === '60') {
            return true;
        } elseif ($phone[0] === '0') {
            return true;
        }
    }
    return false;
}

// Database Utilities
function is_username_taken($username) {
    global $pdo;
    return username_exists($pdo, $username);
}

function is_phone_taken($phone) {
    global $pdo;
    return phone_exists($pdo, $phone);
}

function get_user_by_username($username) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Authentication
function login_user($user_id) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['last_activity'] = time();
}

function logout_user() {
    session_destroy();
    redirect('/mgt/login.php');
}

function check_auth() {
    if (!is_logged_in()) {
        redirect('/mgt/login.php', ['type' => 'error', 'message' => 'Please log in to continue.']);
    }
}

// Session timeout after 30 minutes of inactivity
function check_session_timeout() {
    $timeout = 30 * 60; // 30 minutes
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        logout_user();
        redirect('/mgt/login.php', ['type' => 'warning', 'message' => 'Session expired. Please log in again.']);
    }
    $_SESSION['last_activity'] = time();
}

// CSRF Protection
function generate_csrf_token() {
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    return $token;
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if the current user is a staff member and redirect if not
 * @return void
 */
function require_staff_login() {
    if (!is_logged_in()) {
        redirect('/mgt/public/login.php', ['type' => 'error', 'message' => 'Please log in to access the staff area.']);
    }
    
    // Check if user is staff or admin
    $user_role = get_user_role();
    if ($user_role !== 'Staff' && $user_role !== 'Admin') {
        redirect('/mgt/public/index.php', ['type' => 'error', 'message' => 'You do not have permission to access this area.']);
    }
}
?> 