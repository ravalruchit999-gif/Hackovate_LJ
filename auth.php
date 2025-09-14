<?php
// auth.php - Authentication class

require_once __DIR__ . '/config.php';

class Auth {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function register($username, $email, $password, $full_name, $phone = '', $city = 'bangalore') {
        try {
            // Check if username or email already exists
            if ($this->userExists($username, $email)) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }
            
            // Validate input
            if (!validateEmail($email)) {
                return ['success' => false, 'message' => 'Invalid email format'];
            }
            
            if (!validatePassword($password)) {
                return ['success' => false, 'message' => 'Password must be at least 6 characters with uppercase, lowercase, and number'];
            }
            
            if (!empty($phone) && !validatePhone($phone)) {
                return ['success' => false, 'message' => 'Invalid phone number format'];
            }
            
            // Hash password
            $hashed_password = hashPassword($password);
            
            // Insert user
            $query = "INSERT INTO users (username, email, password, full_name, phone, city, created_at) 
                     VALUES (:username, :email, :password, :full_name, :phone, :city, NOW())";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':city', $city);
            
            if ($stmt->execute()) {
                $user_id = $this->db->lastInsertId();
                logActivity($user_id, 'User registered', "Username: $username, Email: $email");
                return ['success' => true, 'message' => 'Registration successful'];
            } else {
                return ['success' => false, 'message' => 'Registration failed'];
            }
            
        } catch (PDOException $e) {
            handleError("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed due to system error'];
        }
    }
    
    public function login($username, $password, $remember_me = false) {
        try {
            // Get user by username or email
            $query = "SELECT * FROM users WHERE (username = :username OR email = :username) AND is_active = 1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && verifyPassword($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['city'] = $user['city'];
                $_SESSION['login_time'] = time();
                
                // Create session record
                $this->createSession($user['id'], $remember_me);
                
                // Update last login
                $update_query = "UPDATE users SET updated_at = NOW() WHERE id = :user_id";
                $update_stmt = $this->db->prepare($update_query);
                $update_stmt->bindParam(':user_id', $user['id']);
                $update_stmt->execute();
                
                logActivity($user['id'], 'User logged in', "IP: " . $_SERVER['REMOTE_ADDR']);
                
                return ['success' => true, 'message' => 'Login successful', 'user' => $user];
            } else {
                return ['success' => false, 'message' => 'Invalid username or password'];
            }
            
        } catch (PDOException $e) {
            handleError("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Login failed due to system error'];
        }
    }
    
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            logActivity($_SESSION['user_id'], 'User logged out');
            
            // Remove session from database
            $this->removeSession($_SESSION['user_id']);
        }
        
        // Destroy session
        session_destroy();
        
        // Redirect to login page
        header('Location: login.php');
        exit();
    }
    
    public function updateProfile($user_id, $data) {
        try {
            $allowed_fields = ['full_name', 'phone', 'city'];
            $update_fields = [];
            $params = [':user_id' => $user_id];
            
            foreach ($allowed_fields as $field) {
                if (isset($data[$field])) {
                    $update_fields[] = "$field = :$field";
                    $params[":$field"] = $data[$field];
                }
            }
            
            if (empty($update_fields)) {
                return ['success' => false, 'message' => 'No fields to update'];
            }
            
            $query = "UPDATE users SET " . implode(', ', $update_fields) . ", updated_at = NOW() WHERE id = :user_id";
            $stmt = $this->db->prepare($query);
            
            if ($stmt->execute($params)) {
                // Update session data
                if (isset($data['city'])) $_SESSION['city'] = $data['city'];
                
                logActivity($user_id, 'Profile updated');
                return ['success' => true, 'message' => 'Profile updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Profile update failed'];
            }
            
        } catch (PDOException $e) {
            handleError("Profile update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Profile update failed'];
        }
    }
    
    public function changePassword($user_id, $current_password, $new_password) {
        try {
            // Verify current password
            $query = "SELECT password FROM users WHERE id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || !verifyPassword($current_password, $user['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            if (!validatePassword($new_password)) {
                return ['success' => false, 'message' => 'New password must be at least 6 characters with uppercase, lowercase, and number'];
            }
            
            // Update password
            $hashed_password = hashPassword($new_password);
            $update_query = "UPDATE users SET password = :password, updated_at = NOW() WHERE id = :user_id";
            $update_stmt = $this->db->prepare($update_query);
            $update_stmt->bindParam(':password', $hashed_password);
            $update_stmt->bindParam(':user_id', $user_id);
            
            if ($update_stmt->execute()) {
                logActivity($user_id, 'Password changed');
                return ['success' => true, 'message' => 'Password changed successfully'];
            } else {
                return ['success' => false, 'message' => 'Password change failed'];
            }
            
        } catch (PDOException $e) {
            handleError("Password change error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Password change failed'];
        }
    }
    
    private function userExists($username, $email) {
        $query = "SELECT id FROM users WHERE username = :username OR email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    private function createSession($user_id, $remember_me = false) {
        $session_token = generateToken();
        $expires_at = $remember_me ? 
            date('Y-m-d H:i:s', strtotime('+30 days')) : 
            date('Y-m-d H:i:s', strtotime('+1 day'));
        
        $query = "INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) 
                 VALUES (:user_id, :session_token, :ip_address, :user_agent, :expires_at)";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':session_token', $session_token);
        $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR']);
        $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT']);
        $stmt->bindParam(':expires_at', $expires_at);
        
        $stmt->execute();
        
        $_SESSION['session_token'] = $session_token;
    }
    
    private function removeSession($user_id) {
        if (isset($_SESSION['session_token'])) {
            $query = "DELETE FROM user_sessions WHERE user_id = :user_id AND session_token = :session_token";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':session_token', $_SESSION['session_token']);
            $stmt->execute();
        }
    }
    
    public function cleanupExpiredSessions() {
        $query = "DELETE FROM user_sessions WHERE expires_at < NOW()";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
    }
    
    public function getActiveUsers() {
        $query = "SELECT u.*, us.expires_at, us.ip_address 
                 FROM users u 
                 LEFT JOIN user_sessions us ON u.id = us.user_id 
                 WHERE u.is_active = 1 AND us.expires_at > NOW() 
                 ORDER BY u.created_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>