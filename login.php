<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = $_SESSION['role'];
    if ($role === 'admin') {
        header('Location: admin_dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
    exit();
}

$error_message = '';
$success_message = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']);
    
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password';
    } else {
        $auth = new Auth();
        $result = $auth->login($username, $password, $remember_me);
        
        if ($result['success']) {
            $role = $result['user']['role'];
            if ($role === 'admin') {
                header('Location: admin_dashboard.php');
            } else {
                header('Location: dashboard.php');
            }
            exit();
        } else {
            $error_message = $result['message'];
        }
    }
}

// Get flash messages
$flash = getFlashMessage();
if ($flash) {
    if ($flash['type'] === 'success') {
        $success_message = $flash['message'];
    } else {
        $error_message = $flash['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            width: 100%;
            max-width: 450px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #667eea;
            font-size: 2.2rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .login-header p {
            color: #666;
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 600;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-2px);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 25px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            transform: scale(1.2);
        }
        
        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-error {
            background-color: #fee;
            border: 1px solid #fcc;
            color: #c33;
        }
        
        .alert-success {
            background-color: #efe;
            border: 1px solid #cfc;
            color: #363;
        }
        
        .register-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .register-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        .demo-credentials {
            background: rgba(102, 126, 234, 0.1);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        
        .demo-credentials h4 {
            color: #667eea;
            margin-bottom: 8px;
        }
        
        .demo-credentials p {
            font-size: 0.9rem;
            color: #555;
            margin: 2px 0;
        }
        
        @media (max-width: 480px) {
            .login-container {
                margin: 20px;
                padding: 30px 25px;
            }
            
            .login-header h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🚌 Smart Bus</h1>
            <p>Sign in to manage your bus system</p>
        </div>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
       
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" id="remember_me" name="remember_me">
                <label for="remember_me">Remember me for 30 days</label>
            </div>
            
            <button type="submit" class="login-btn">Sign In</button>
        </form>
        
        <div class="register-link">
            <p>Don't have an account? <a href="register.php">Sign up here</a></p>
        </div>
    </div>
    
    <script>
        // Add some interactive effects
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>