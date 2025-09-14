<?php
// config.php - Database configuration and common functions

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'smart_bus_system');
define('DB_USER', 'root');
define('DB_PASS', '');

// Application configuration
define('APP_NAME', 'Smart Bus Management System');
define('APP_URL', 'http://localhost/dashboard/team techys');
define('SESSION_TIMEOUT', 3600); // 1 hour

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database connection class
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                                $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// Authentication functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit();
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM users WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Utility functions
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function redirectWithMessage($url, $message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: $url");
    exit();
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

// Validation functions
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    return preg_match('/^[0-9]{10}$/', $phone);
}

function validatePassword($password) {
    // At least 6 characters, one uppercase, one lowercase, one number
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{6,}$/', $password);
}

// City and route helper functions
function getCities() {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT * FROM cities WHERE is_active = 1 ORDER BY name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRoutesByCity($city_id) {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT r.*, c.name as city_name FROM routes r 
              JOIN cities c ON r.city_id = c.id 
              WHERE r.city_id = :city_id AND r.is_active = 1 
              ORDER BY r.route_code";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':city_id', $city_id);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Logging function
function logActivity($user_id, $activity, $details = '') {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "INSERT INTO system_alerts (alert_type, message, created_by, created_at) 
              VALUES ('info', :message, :user_id, NOW())";
    $stmt = $db->prepare($query);
    $message = "User Activity: $activity. $details";
    $stmt->bindParam(':message', $message);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
}

// Error handling
function handleError($error_message) {
    error_log($error_message);
    $_SESSION['flash_message'] = 'An error occurred. Please try again.';
    $_SESSION['flash_type'] = 'error';
}

// Time zone setting
date_default_timezone_set('Asia/Kolkata');
?>