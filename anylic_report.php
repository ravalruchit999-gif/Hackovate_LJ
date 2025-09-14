<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
requireAdmin();

$current_user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();

// Handle Export Data
if (isset($_GET['export'])) {
    $export_type = $_GET['export'];
    $filename = '';
    $data = [];
    
    switch ($export_type) {
        case 'ridership':
            $filename = 'ridership_report_' . date('Y-m-d') . '.csv';
            $stmt = $db->prepare("
                SELECT b.bus_number, r.route_name, b.current_passengers, b.capacity, 
                       ROUND((b.current_passengers/b.capacity)*100, 2) as occupancy_rate,
                       b.status, c.name as city
                FROM buses b 
                LEFT JOIN routes r ON b.route_id = r.id 
                LEFT JOIN cities c ON r.city_id = c.id
                ORDER BY c.name, r.route_name
            ");
            break;
        case 'performance':
            $filename = 'performance_report_' . date('Y-m-d') . '.csv';
            $stmt = $db->prepare("
                SELECT r.route_name, c.name as city, 
                       COUNT(b.id) as total_buses,
                       AVG(b.current_passengers) as avg_passengers,
                       AVG(b.speed_kmh) as avg_speed,
                       SUM(CASE WHEN b.status = 'delayed' THEN 1 ELSE 0 END) as delayed_buses
                FROM routes r 
                LEFT JOIN cities c ON r.city_id = c.id
                LEFT JOIN buses b ON r.id = b.route_id
                GROUP BY r.id, r.route_name, c.name
                ORDER BY c.name, r.route_name
            ");
            break;
        case 'alerts':
            $filename = 'system_alerts_' . date('Y-m-d') . '.csv';
            $stmt = $db->prepare("
                SELECT alert_type, message, created_at, is_resolved,
                       CASE WHEN bus_id IS NOT NULL THEN 
                           (SELECT bus_number FROM buses WHERE id = system_alerts.bus_id) 
                       ELSE 'N/A' END as bus_number,
                       CASE WHEN route_id IS NOT NULL THEN 
                           (SELECT route_name FROM routes WHERE id = system_alerts.route_id) 
                       ELSE 'N/A' END as route_name
                FROM system_alerts 
                ORDER BY created_at DESC
            ");
            break;
        default:
            exit('Invalid export type');
    }
    
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    if (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    fclose($output);
    exit;
}

// Get overall statistics
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM buses) as total_buses,
        (SELECT COUNT(*) FROM routes) as total_routes,
        (SELECT COUNT(*) FROM cities) as total_cities,
        (SELECT COUNT(*) FROM users WHERE role = 'passenger') as total_passengers,
        (SELECT COUNT(*) FROM buses WHERE status = 'active') as active_buses,
        (SELECT COUNT(*) FROM buses WHERE status = 'delayed') as delayed_buses,
        (SELECT AVG(current_passengers) FROM buses) as avg_occupancy,
        (SELECT AVG(speed_kmh) FROM buses WHERE status = 'active') as avg_speed
";
$stats = $db->query($stats_query)->fetch(PDO::FETCH_ASSOC);

// Get ridership data by city
$ridership_query = "
    SELECT c.name as city, 
           COUNT(b.id) as total_buses,
           SUM(b.current_passengers) as total_passengers,
           SUM(b.capacity) as total_capacity,
           ROUND((SUM(b.current_passengers)/SUM(b.capacity))*100, 2) as occupancy_rate
    FROM cities c 
    LEFT JOIN routes r ON c.id = r.city_id 
    LEFT JOIN buses b ON r.id = b.route_id 
    GROUP BY c.id, c.name
    ORDER BY occupancy_rate DESC
";
$ridership_data = $db->query($ridership_query)->fetchAll(PDO::FETCH_ASSOC);

// Get performance data by route
$performance_query = "
    SELECT r.route_name, c.name as city, r.distance_km, r.estimated_duration,
           COUNT(b.id) as buses_assigned,
           AVG(b.current_passengers) as avg_passengers,
           AVG(b.speed_kmh) as avg_speed,
           SUM(CASE WHEN b.status = 'delayed' THEN 1 ELSE 0 END) as delayed_count,
           SUM(CASE WHEN b.status = 'active' THEN 1 ELSE 0 END) as active_count
    FROM routes r 
    LEFT JOIN cities c ON r.city_id = c.id
    LEFT JOIN buses b ON r.id = b.route_id
    WHERE r.is_active = 1
    GROUP BY r.id, r.route_name, c.name, r.distance_km, r.estimated_duration
    ORDER BY c.name, avg_passengers DESC
";
$performance_data = $db->query($performance_query)->fetchAll(PDO::FETCH_ASSOC);

// Get recent alerts
$alerts_query = "
    SELECT sa.*, 
           CASE WHEN sa.bus_id IS NOT NULL THEN b.bus_number ELSE 'N/A' END as bus_number,
           CASE WHEN sa.route_id IS NOT NULL THEN r.route_name ELSE 'N/A' END as route_name
    FROM system_alerts sa
    LEFT JOIN buses b ON sa.bus_id = b.id
    LEFT JOIN routes r ON sa.route_id = r.id
    ORDER BY sa.created_at DESC
    LIMIT 10
";
$recent_alerts = $db->query($alerts_query)->fetchAll(PDO::FETCH_ASSOC);

// Simulate before/after optimization data (you can modify this based on your actual optimization tracking)
$optimization_data = [
    'before' => [
        'avg_delay' => 15.2,
        'avg_occupancy' => 68.5,
        'fuel_efficiency' => 6.2,
        'customer_satisfaction' => 3.2
    ],
    'after' => [
        'avg_delay' => 8.7,
        'avg_occupancy' => 78.3,
        'fuel_efficiency' => 7.8,
        'customer_satisfaction' => 4.1
    ]
];

include 'admin_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics & Reports - <?php echo APP_NAME; ?></title>
    <style>
        .analytics-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: center; border-left: 4px solid #007bff; }
        .stat-number { font-size: 2em; font-weight: bold; color: #007bff; }
        .stat-label { color: #666; margin-top: 5px; }
        .section { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .section h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .export-buttons { margin-bottom: 20px; }
        .export-btn { background: #28a745; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; margin-right: 10px; }
        .export-btn:hover { background: #218838; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; color:black; }
        th { background-color: #f8f9fa; font-weight: bold; }
        tr:hover { background-color: #f5f5f5; }
        .status-active { color: #28a745; font-weight: bold; }
        .status-delayed { color: #dc3545; font-weight: bold; }
        .status-inactive { color: #6c757d; }
        .alert-info { background: #d1ecf1; border-left: 4px solid #17a2b8; }
        .alert-warning { background: #fff3cd; border-left: 4px solid #ffc107; }
        .alert-error { background: #f8d7da; border-left: 4px solid #dc3545; }
        .alert-success { background: #d4edda; border-left: 4px solid #28a745; }
        .comparison-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .comparison-card { padding: 15px; border-radius: 6px; }
        .before-card { background: #fff3cd; border: 1px solid #ffc107; }
        .after-card { background: #d4edda; border: 1px solid #28a745; }
        .metric { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .improvement { color: #28a745; font-weight: bold; }
        .degradation { color: #dc3545; font-weight: bold; }
        .progress-bar { width: 100%; height: 20px; background: #e9ecef; border-radius: 10px; overflow: hidden; }
        .progress-fill { height: 100%; background: #007bff; transition: width 0.3s ease; }
        .tab-buttons { margin-bottom: 20px; }
        .tab-btn { background: #f8f9fa; border: 1px solid #ddd; padding: 10px 20px; cursor: pointer; margin-right: 5px; }
        .tab-btn.active { background: #007bff; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .color{color:black;}
    </style>
</head>
<body>
    <div class="analytics-container">
        <h1>📊 Analytics & Reports</h1>

        <!-- Export Buttons -->
        <div class="export-buttons">
            <a href="?export=ridership" class="export-btn">📋 Export Ridership Data</a>
            <a href="?export=performance" class="export-btn">📈 Export Performance Data</a>
            <a href="?export=alerts" class="export-btn">🚨 Export System Alerts</a>
        </div>

        <!-- Overall Statistics -->
        <div class="section">
            <h2>📊 System Overview</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_buses']; ?></div>
                    <div class="stat-label">Total Buses</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['active_buses']; ?></div>
                    <div class="stat-label">Active Buses</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['delayed_buses']; ?></div>
                    <div class="stat-label">Delayed Buses</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['avg_occupancy'], 1); ?></div>
                    <div class="stat-label">Avg Passengers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['avg_speed'], 1); ?></div>
                    <div class="stat-label">Avg Speed (km/h)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_routes']; ?></div>
                    <div class="stat-label">Total Routes</div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tab-buttons">
            <button class="tab-btn active" onclick="showTab('ridership')">📈 Ridership Reports</button>
            <button class="tab-btn" onclick="showTab('performance')">⚡ Performance Analytics</button>
            <button class="tab-btn" onclick="showTab('optimization')">🔄 Before/After Optimization</button>
            <button class="tab-btn" onclick="showTab('alerts')">🚨 System Alerts</button>
        </div>

        <!-- Ridership Reports Tab -->
        <div id="ridership" class="tab-content active">
            <div class="section">
                <h2>👥 Ridership Reports by City</h2>
                <table>
                    <tr class="color">
                        <th>City</th>
                        <th>Total Buses</th>
                        <th>Current Passengers</th>
                        <th>Total Capacity</th>
                        <th>Occupancy Rate</th>
                        <th>Utilization</th>
                    </tr>
                    <?php foreach ($ridership_data as $city): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($city['city']); ?></td>
                        <td><?php echo $city['total_buses']; ?></td>
                        <td><?php echo $city['total_passengers']; ?></td>
                        <td><?php echo $city['total_capacity']; ?></td>
                        <td><?php echo $city['occupancy_rate']; ?>%</td>
                        <td>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $city['occupancy_rate']; ?>%"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <!-- Performance Analytics Tab -->
        <div id="performance" class="tab-content">
            <div class="section">
                <h2>⚡ Performance Analytics by Route</h2>
                <table>
                    <tr>
                        <th;>Route</th>
                        <th>City</th>
                        <th>Buses Assigned</th>
                        <th>Avg Passengers</th>
                        <th>Avg Speed</th>
                        <th>Active</th>
                        <th>Delayed</th>
                        <th>Efficiency</th>
                    </tr>
                    <?php foreach ($performance_data as $route): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($route['route_name']); ?></td>
                        <td><?php echo htmlspecialchars($route['city']); ?></td>
                        <td><?php echo $route['buses_assigned']; ?></td>
                        <td><?php echo number_format($route['avg_passengers'], 1); ?></td>
                        <td><?php echo number_format($route['avg_speed'], 1); ?> km/h</td>
                        <td class="status-active"><?php echo $route['active_count']; ?></td>
                        <td class="status-delayed"><?php echo $route['delayed_count']; ?></td>
                        <td>
                            <?php 
                            $efficiency = $route['delayed_count'] == 0 ? 100 : 
                                         (($route['active_count'] / ($route['active_count'] + $route['delayed_count'])) * 100);
                            echo number_format($efficiency, 1) . '%';
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <!-- Before/After Optimization Tab -->
        <div id="optimization" class="tab-content">
            <div class="section">
                <h2>🔄 Before/After Optimization Comparison</h2>
                <div class="comparison-grid">
                    <div class="comparison-card before-card">
                        <h3>📉 Before Optimization</h3>
                        <div class="metric">
                            <span>Average Delay:</span>
                            <span><?php echo $optimization_data['before']['avg_delay']; ?> minutes</span>
                        </div>
                        <div class="metric">
                            <span>Average Occupancy:</span>
                            <span><?php echo $optimization_data['before']['avg_occupancy']; ?>%</span>
                        </div>
                        <div class="metric">
                            <span>Fuel Efficiency:</span>
                            <span><?php echo $optimization_data['before']['fuel_efficiency']; ?> km/l</span>
                        </div>
                        <div class="metric">
                            <span>Customer Satisfaction:</span>
                            <span><?php echo $optimization_data['before']['customer_satisfaction']; ?>/5.0</span>
                        </div>
                    </div>
                    <div class="comparison-card after-card">
                        <h3>📈 After Optimization</h3>
                        <div class="metric">
                            <span>Average Delay:</span>
                            <span><?php echo $optimization_data['after']['avg_delay']; ?> minutes</span>
                        </div>
                        <div class="metric">
                            <span>Average Occupancy:</span>
                            <span><?php echo $optimization_data['after']['avg_occupancy']; ?>%</span>
                        </div>
                        <div class="metric">
                            <span>Fuel Efficiency:</span>
                            <span><?php echo $optimization_data['after']['fuel_efficiency']; ?> km/l</span>
                        </div>
                        <div class="metric">
                            <span>Customer Satisfaction:</span>
                            <span><?php echo $optimization_data['after']['customer_satisfaction']; ?>/5.0</span>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <h3>📊 Improvement Metrics</h3>
                    <div class="metric">
                        <span>Delay Reduction:</span>
                        <span class="improvement">
                            <?php echo number_format((($optimization_data['before']['avg_delay'] - $optimization_data['after']['avg_delay']) / $optimization_data['before']['avg_delay']) * 100, 1); ?>% improvement
                        </span>
                    </div>
                    <div class="metric">
                        <span>Occupancy Increase:</span>
                        <span class="improvement">
                            <?php echo number_format((($optimization_data['after']['avg_occupancy'] - $optimization_data['before']['avg_occupancy']) / $optimization_data['before']['avg_occupancy']) * 100, 1); ?>% improvement
                        </span>
                    </div>
                    <div class="metric">
                        <span>Fuel Efficiency Gain:</span>
                        <span class="improvement">
                            <?php echo number_format((($optimization_data['after']['fuel_efficiency'] - $optimization_data['before']['fuel_efficiency']) / $optimization_data['before']['fuel_efficiency']) * 100, 1); ?>% improvement
                        </span>
                    </div>
                    <div class="metric">
                        <span>Satisfaction Improvement:</span>
                        <span class="improvement">
                            <?php echo number_format((($optimization_data['after']['customer_satisfaction'] - $optimization_data['before']['customer_satisfaction']) / $optimization_data['before']['customer_satisfaction']) * 100, 1); ?>% improvement
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Alerts Tab -->
        <div id="alerts" class="tab-content">
            <div class="section">
                <h2>🚨 Recent System Alerts</h2>
                <table>
                    <tr>
                        <th>Type</th>
                        <th>Message</th>
                        <th>Bus</th>
                        <th>Route</th>
                        <th>Created</th>
                        <th>Status</th>
                    </tr>
                    <?php foreach ($recent_alerts as $alert): ?>
                    <tr class="alert-<?php echo $alert['alert_type']; ?>">
                        <td>
                            <?php 
                            $icons = ['info' => 'ℹ️', 'warning' => '⚠️', 'error' => '❌', 'success' => '✅'];
                            echo $icons[$alert['alert_type']] . ' ' . ucfirst($alert['alert_type']); 
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($alert['message']); ?></td>
                        <td><?php echo htmlspecialchars($alert['bus_number']); ?></td>
                        <td><?php echo htmlspecialchars($alert['route_name']); ?></td>
                        <td><?php echo date('M j, Y H:i', strtotime($alert['created_at'])); ?></td>
                        <td>
                            <?php if ($alert['is_resolved']): ?>
                                <span class="status-active">✅ Resolved</span>
                            <?php else: ?>
                                <span class="status-delayed">⏳ Pending</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            var contents = document.getElementsByClassName('tab-content');
            for (var i = 0; i < contents.length; i++) {
                contents[i].classList.remove('active');
            }
            
            // Remove active class from all buttons
            var buttons = document.getElementsByClassName('tab-btn');
            for (var i = 0; i < buttons.length; i++) {
                buttons[i].classList.remove('active');
            }
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }

        // Add some dynamic updates (you can extend this)
        setInterval(function() {
            // You can add AJAX calls here to update data dynamically
            console.log('Data refresh check...');
        }, 30000); // Check every 30 seconds
    </script>
</body>
</html>

<?php include 'admin_footer.php'; ?>