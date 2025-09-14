<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

// Require admin access
requireAdmin();

$current_user = getCurrentUser();

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Get system statistics
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM users WHERE is_active = 1) as total_users,
        (SELECT COUNT(*) FROM buses WHERE status = 'active') as active_buses,
        (SELECT COUNT(*) FROM routes WHERE is_active = 1) as total_routes,
        (SELECT COUNT(*) FROM cities WHERE is_active = 1) as total_cities,
        (SELECT COUNT(*) FROM system_alerts WHERE is_resolved = 0) as pending_alerts
";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
// Get recent activities
$activities_query = "SELECT sa.*, u.username, u.full_name 
                    FROM system_alerts sa
                    LEFT JOIN users u ON sa.created_by = u.id 
                    ORDER BY sa.created_at DESC 
                    LIMIT 10";
$activities_stmt = $db->prepare($activities_query);
$activities_stmt->execute();
$activities = $activities_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user distribution by city
$city_users_query = "SELECT c.name, COUNT(u.id) as user_count 
                     FROM cities c 
                     LEFT JOIN users u ON c.code = u.city 
                     WHERE c.is_active = 1 
                     GROUP BY c.id, c.name";
$city_users_stmt = $db->prepare($city_users_query);
$city_users_stmt->execute();
$city_users = $city_users_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle logout
if (isset($_GET['logout'])) {
    $auth = new Auth();
    $auth->logout();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .nav-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: #e74c3c;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .admin-badge {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .nav-user {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .nav-links {
            display: flex;
            gap: 15px;
        }
        
        .nav-links a {
            color: #4a5568;
            text-decoration: none;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .nav-links a:hover {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #4a5568;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .logout-btn {
            padding: 8px 16px;
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.4);
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            text-align: center;
        }
        
        .header h1 {
            color: #2c3e50;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 1.2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 5px solid #e74c3c;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        
        .stat-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .card h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .recent-activities {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .activity-item {
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 10px;
            border-left: 4px solid #e74c3c;
            background: rgba(231, 76, 60, 0.05);
            transition: transform 0.2s ease;
        }
        
        .activity-item:hover {
            transform: translateX(5px);
            background: rgba(231, 76, 60, 0.1);
        }
        
        .activity-time {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 5px;
        }
        
        .activity-message {
            color: #2c3e50;
            font-weight: 500;
        }
        
        .activity-user {
            font-size: 0.85rem;
            color: #e74c3c;
            margin-top: 5px;
            font-weight: 600;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 20px;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .action-btn {
            display: block;
            padding: 15px 20px;
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(231, 76, 60, 0.4);
        }
        
        .action-btn.secondary {
            background: linear-gradient(135deg, #34495e, #2c3e50);
        }
        
        .action-btn.success {
            background: linear-gradient(135deg, #27ae60, #229954);
        }
        
        .action-btn.warning {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }
        
        .full-width {
            grid-column: 1 / -1;
        }
        
        .management-sections {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .section-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .section-card h4 {
            color: #2c3e50;
            margin-bottom: 10px;
            padding: 20px;
            font-size: 1.2rem;
            backdrop-filter: blur(10px);
            border-radius: 15px;
            transform: translateY(-3px);
            text-align: center;
        }
        
        .section-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .section-actions a {
            padding: 10px 15px;
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .section-actions a:hover {
            background: rgba(231, 76, 60, 0.2);
            transform: translateX(5px);
        }
        
        .alert-badge {
            background: #e74c3c;
            color: white;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 5px;
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
            
            .nav-container {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .nav-links {
                justify-content: center;
            }
            
            .header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                🛡️ Admin Panel
                <span class="admin-badge">ADMIN</span>
            </div>
            <div class="nav-links">
                <a href="manage_users.php">👥 Users</a>
                <a href="fleet_mang.php">🚌 Buses</a>
                <a href="route_plan.php">🗺️ Routes</a>
                <a href="anylic_report.php">📊 Reports</a>
            </div>
            <div class="nav-user">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($current_user['full_name'], 0, 1)); ?>
                    </div>
                    <div>
                        <div style="font-weight: 600;"><?php echo htmlspecialchars($current_user['full_name']); ?></div>
                        <div style="font-size: 0.8rem; color: #666;">System Administrator</div>
                    </div>
                </div>
                <a href="?logout=1" class="logout-btn">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="header">
            <h1>🚌 Smart Bus Admin Dashboard</h1>
            <p>Complete system management and analytics</p>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🚌</div>
                <div class="stat-value"><?php echo number_format($stats['active_buses']); ?></div>
                <div class="stat-label">Active Buses</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🗺️</div>
                <div class="stat-value"><?php echo number_format($stats['total_routes']); ?></div>
                <div class="stat-label">Total Routes</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🏙️</div>
                <div class="stat-value"><?php echo number_format($stats['total_cities']); ?></div>
                <div class="stat-label">Cities</div>
            </div>
        </div>
        
        
        <div class="dashboard-grid">
            <div class="card">
                <h3>📈 User Distribution by City</h3>
                <div class="chart-container">
                    <canvas id="cityChart"></canvas>
                </div>
            </div>
            
            <div class="card">
                <h3>
                    🔔 Recent System Activities 
                    <?php if($stats['pending_alerts'] > 0): ?>
                        <span class="alert-badge"><?php echo $stats['pending_alerts']; ?></span>
                    <?php endif; ?>
                </h3>
                <div class="recent-activities">
                    <?php foreach($activities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-time">
                                <?php echo date('M j, Y H:i', strtotime($activity['created_at'])); ?>
                            </div>
                            <div class="activity-message">
                                <?php echo htmlspecialchars($activity['message']); ?>
                            </div>
                            <?php if($activity['full_name']): ?>
                                <div class="activity-user">
                                    By: <?php echo htmlspecialchars($activity['full_name']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if(empty($activities)): ?>
                        <div class="activity-item">
                            <div class="activity-message">No recent activities found.</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="card full-width">
            <h3>🛠️ System Management</h3>
            <div class="management-sections">
                <div class="section-card">
                    <h4><a href="manage_users.php"style="text-decoration:none";>👥 User Management</a></h4>
                </div>
                
                <div class="section-card">
                    <h4><a href="fleet_mang.php"style="text-decoration:none";>🚌 Fleet Management</a></h4>
                </div>
                
                <div class="section-card">
                    <h4><a href="route_plan.php"style="text-decoration:none";>🗺️ Route Planning</a></h4>
                </div>
                
                <div class="section-card">
                    <h4><a href="anylic_report.php"style="text-decoration:none";>📊 Analytics & Reports</a></h4>
                </div>
           
                
                <div class="section-card">
                    <h4><a href="temp.php"style="text-decoration:none";>⚙️ System Configuration</a></h4>
                </div>
                
                <div class="section-card">
                    <h4><a href="temp.php"style="text-decoration:none";>🔒 Security & Logs</a></h4>
                </div>
                
            </div>
            </div>
        </div>
    </div>
    
    <script>
        // Initialize city distribution chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('cityChart').getContext('2d');
            
            const cityData = <?php echo json_encode($city_users); ?>;
            const labels = cityData.map(item => item.name);
            const data = cityData.map(item => parseInt(item.user_count));
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: [
                            'rgba(231, 76, 60, 0.8)',
                            'rgba(52, 73, 94, 0.8)',
                            'rgba(39, 174, 96, 0.8)',
                            'rgba(243, 156, 18, 0.8)',
                            'rgba(155, 89, 182, 0.8)',
                            'rgba(26, 188, 156, 0.8)'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        });
        
        // Quick action functions
        function addNewUser() {
            window.location.href = 'users.php?action=add';
        }
        
        function manageBuses() {
            window.location.href = 'buses.php';
        }
        
        function viewReports() {
            window.location.href = 'reports.php';
        }
        
        function systemSettings() {
            window.location.href = 'settings.php';
        }
        
        // Auto-refresh activities every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
        
        // Add interactive effects
        document.querySelectorAll('.stat-card, .activity-item').forEach(element => {
            element.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-3px) scale(1.02)';
            });
            
            element.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    </script>
</body>
</html>