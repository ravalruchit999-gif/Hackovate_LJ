<?php
// setup.php - One-time setup script to create admin users with proper password hashing

require_once __DIR__ . '/config.php';

// Check if this is a fresh installation
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Database connection failed! Please check your config.php settings.");
}

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Smart Bus System - Setup</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            color: #333;
        }
        .setup-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            max-width: 600px;
            width: 90%;
        }
        h1 {
            color: #667eea;
            text-align: center;
            margin-bottom: 30px;
        }
        .step {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        .warning {
            background: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
        }
        .credentials {
            background: #e3f2fd;
            border: 2px solid #2196f3;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .credentials h3 {
            color: #1976d2;
            margin-bottom: 15px;
        }
        .credential-item {
            background: white;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            margin: 10px 5px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        pre {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class='setup-container'>";

echo "<h1>🚌 Smart Bus System Setup</h1>";

try {
    // Step 1: Check database connection
    echo "<div class='step success'>
            <h3>✅ Step 1: Database Connection</h3>
            <p>Successfully connected to database: <strong>" . DB_NAME . "</strong></p>
          </div>";

    // Step 2: Check if tables exist
    $tables_query = "SHOW TABLES LIKE 'users'";
    $tables_result = $db->query($tables_query);
    
    if ($tables_result->rowCount() == 0) {
        echo "<div class='step error'>
                <h3>❌ Step 2: Database Tables</h3>
                <p>The 'users' table doesn't exist. Please run the database schema first:</p>
                <pre>1. Open phpMyAdmin or MySQL command line
2. Create database 'smart_bus_system' if not exists
3. Run the SQL commands from the database_schema.sql file</pre>
              </div>";
        die("</div></body></html>");
    } else {
        echo "<div class='step success'>
                <h3>✅ Step 2: Database Tables</h3>
                <p>Required tables exist in the database.</p>
              </div>";
    }

    // Step 3: Check existing admin user
    $admin_query = "SELECT * FROM users WHERE username = 'admin' OR role = 'admin'";
    $admin_stmt = $db->prepare($admin_query);
    $admin_stmt->execute();
    $existing_admin = $admin_stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_admin) {
        echo "<div class='step warning'>
                <h3>⚠️ Step 3: Existing Admin Found</h3>
                <p>Admin user already exists: <strong>{$existing_admin['username']}</strong></p>
                <p>Email: <strong>{$existing_admin['email']}</strong></p>
                <p>Created: <strong>{$existing_admin['created_at']}</strong></p>
              </div>";
    }

    // Step 4: Create/Update admin users with proper password hashing
    echo "<div class='step'>
            <h3>🔧 Step 4: Creating Admin Users</h3>";

    // Delete existing users to recreate with proper passwords
    $delete_query = "DELETE FROM users WHERE username IN ('admin', 'operator1')";
    $db->exec($delete_query);

    // Create admin user
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $admin_insert = "INSERT INTO users (username, email, password, full_name, role, city, created_at) 
                     VALUES ('admin', 'admin@smartbus.com', :password, 'System Administrator', 'admin', 'bangalore', NOW())";
    $admin_stmt = $db->prepare($admin_insert);
    $admin_stmt->bindParam(':password', $admin_password);
    
    if ($admin_stmt->execute()) {
        echo "<p>✅ Admin user created successfully</p>";
    } else {
        echo "<p>❌ Failed to create admin user</p>";
    }

    // Create operator user
    $operator_password = password_hash('operator123', PASSWORD_DEFAULT);
    $operator_insert = "INSERT INTO users (username, email, password, full_name, role, city, created_at) 
                        VALUES ('operator1', 'operator@smartbus.com', :password, 'Bus Operator', 'operator', 'bangalore', NOW())";
    $operator_stmt = $db->prepare($operator_insert);
    $operator_stmt->bindParam(':password', $operator_password);
    
    if ($operator_stmt->execute()) {
        echo "<p>✅ Operator user created successfully</p>";
    } else {
        echo "<p>❌ Failed to create operator user</p>";
    }

    echo "</div>";

    // Step 5: Verify password hashing
    $verify_query = "SELECT username, password FROM users WHERE username IN ('admin', 'operator1')";
    $verify_stmt = $db->prepare($verify_query);
    $verify_stmt->execute();
    $users = $verify_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<div class='step success'>
            <h3>✅ Step 5: Password Verification</h3>";
    
    foreach ($users as $user) {
        $password_test = ($user['username'] === 'admin') ? 'admin123' : 'operator123';
        if (password_verify($password_test, $user['password'])) {
            echo "<p>✅ {$user['username']}: Password hash verified</p>";
        } else {
            echo "<p>❌ {$user['username']}: Password hash verification failed</p>";
        }
    }
    echo "</div>";

    // Step 6: Generate sample data
    echo "<div class='step'>
            <h3>🔧 Step 6: Sample Data Generation</h3>";

    // Check if we need to generate sample buses
    $bus_count_query = "SELECT COUNT(*) as count FROM buses";
    $bus_count_stmt = $db->prepare($bus_count_query);
    $bus_count_stmt->execute();
    $bus_count = $bus_count_stmt->fetch(PDO::FETCH_ASSOC)['count'];

    if ($bus_count < 5) {
        // Generate sample buses for each city
        $cities = ['bangalore' => 1, 'delhi' => 2, 'pune' => 3];
        
        foreach ($cities as $city_code => $city_id) {
            // Get routes for this city
            $routes_query = "SELECT * FROM routes WHERE city_id = :city_id LIMIT 3";
            $routes_stmt = $db->prepare($routes_query);
            $routes_stmt->bindParam(':city_id', $city_id);
            $routes_stmt->execute();
            $routes = $routes_stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($routes)) {
                for ($i = 1; $i <= 8; $i++) {
                    $bus_number = strtoupper($city_code) . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);
                    $route = $routes[array_rand($routes)];
                    
                    // Get city coordinates
                    $city_query = "SELECT latitude, longitude FROM cities WHERE id = :city_id";
                    $city_stmt = $db->prepare($city_query);
                    $city_stmt->bindParam(':city_id', $city_id);
                    $city_stmt->execute();
                    $city_coords = $city_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $lat = $city_coords['latitude'] + (mt_rand(-50, 50) / 1000);
                    $lng = $city_coords['longitude'] + (mt_rand(-50, 50) / 1000);
                    $passengers = mt_rand(5, 45);
                    $capacity = 50;
                    $speed = mt_rand(15, 45);
                    $statuses = ['active', 'active', 'active', 'delayed'];
                    $status = $statuses[array_rand($statuses)];
                    
                    $bus_insert = "INSERT INTO buses (bus_number, route_id, capacity, current_passengers, 
                                                    current_latitude, current_longitude, speed_kmh, status, 
                                                    driver_name, driver_phone, last_updated) 
                                   VALUES (:bus_number, :route_id, :capacity, :current_passengers, 
                                          :latitude, :longitude, :speed, :status, 
                                          :driver_name, :driver_phone, NOW())";
                    
                    $bus_stmt = $db->prepare($bus_insert);
                    $bus_stmt->bindParam(':bus_number', $bus_number);
                    $bus_stmt->bindParam(':route_id', $route['id']);
                    $bus_stmt->bindParam(':capacity', $capacity);
                    $bus_stmt->bindParam(':current_passengers', $passengers);
                    $bus_stmt->bindParam(':latitude', $lat);
                    $bus_stmt->bindParam(':longitude', $lng);
                    $bus_stmt->bindParam(':speed', $speed);
                    $bus_stmt->bindParam(':status', $status);
                    
                    $driver_name = "Driver " . $i . " (" . ucfirst($city_code) . ")";
                    $driver_phone = "9" . str_pad(mt_rand(100000000, 999999999), 9, '0', STR_PAD_LEFT);
                    $bus_stmt->bindParam(':driver_name', $driver_name);
                    $bus_stmt->bindParam(':driver_phone', $driver_phone);
                    
                    $bus_stmt->execute();
                }
                echo "<p>✅ Generated 8 sample buses for " . ucfirst($city_code) . "</p>";
            }
        }
    } else {
        echo "<p>ℹ️ Sample buses already exist in database ($bus_count buses found)</p>";
    }
    echo "</div>";

    // Success message and credentials
    echo "<div class='credentials'>
            <h3>🎉 Setup Complete!</h3>
            <p>Your Smart Bus Management System is ready to use.</p>
            
            <div class='credential-item'>
                <strong>🛡️ Admin Access:</strong><br>
                Username: <code>admin</code><br>
                Password: <code>admin123</code><br>
                URL: <code>" . APP_URL . "admin_dashboard.php</code>
            </div>
            
            <div class='credential-item'>
                <strong>👨‍💼 Operator Access:</strong><br>
                Username: <code>operator1</code><br>
                Password: <code>operator123</code><br>
                URL: <code>" . APP_URL . "/dashboard.php</code>
            </div>
            
            <div class='credential-item'>
                <strong>👥 Passenger Access:</strong><br>
                Register new accounts via: <code>" . APP_URL . "/register.php</code>
            </div>
          </div>";

    echo "<div style='text-align: center; margin-top: 30px;'>
            <a href='login.php' class='btn'>🚀 Go to Login Page</a>
            <a href='admin_dashboard.php' class='btn'>🛡️ Admin Dashboard</a>
          </div>";

    echo "<div class='step warning'>
            <h3>🔒 Security Note</h3>
            <p><strong>Important:</strong> Delete this setup.php file after completing the setup for security reasons.</p>
            <p>You can also change the default passwords after your first login.</p>
          </div>";

} catch (Exception $e) {
    echo "<div class='step error'>
            <h3>❌ Setup Error</h3>
            <p>An error occurred during setup: " . htmlspecialchars($e->getMessage()) . "</p>
            <p>Please check your database configuration and try again.</p>
          </div>";
}

echo "</div></body></html>";
?>