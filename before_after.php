<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
requireAdmin();

$current_user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();

// Handle adding new optimization data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_optimization'])) {
    // In a real system, you would store this in a dedicated optimization_tracking table
    // For now, we'll simulate by adding to system_alerts with a special type
    $stmt = $db->prepare("INSERT INTO system_alerts (alert_type, message, created_at, is_resolved) VALUES ('info', :message, NOW(), 1)");
    $optimization_record = json_encode([
        'type' => 'optimization_milestone',
        'date' => $_POST['milestone_date'],
        'avg_delay' => $_POST['avg_delay'],
        'avg_occupancy' => $_POST['avg_occupancy'],
        'fuel_efficiency' => $_POST['fuel_efficiency'],
        'customer_satisfaction' => $_POST['customer_satisfaction'],
        'on_time_performance' => $_POST['on_time_performance'],
        'maintenance_cost' => $_POST['maintenance_cost'],
        'description' => $_POST['description']
    ]);
    $stmt->execute([':message' => 'Optimization Milestone: ' . $optimization_record]);
}

// Get current system performance (simulated "after" data)
$current_performance = [
    'avg_delay' => 8.7,
    'avg_occupancy' => 78.3,
    'fuel_efficiency' => 7.8,
    'customer_satisfaction' => 4.1,
    'on_time_performance' => 87.5,
    'maintenance_cost' => 45000,
    'total_routes' => 9,
    'active_buses' => 0
];

// Get real-time data from database
$stats_query = "
    SELECT 
        AVG(CASE WHEN status = 'delayed' THEN 15 ELSE 0 END) as avg_delay_current,
        AVG((current_passengers/capacity)*100) as avg_occupancy_current,
        AVG(speed_kmh) as avg_speed_current,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_buses_current,
        COUNT(CASE WHEN status = 'delayed' THEN 1 END) as delayed_buses_current,
        COUNT(*) as total_buses_current
    FROM buses
";
$real_stats = $db->query($stats_query)->fetch(PDO::FETCH_ASSOC);

// Calculate on-time performance
$on_time_performance = $real_stats['total_buses_current'] > 0 ? 
    (($real_stats['active_buses_current'] / $real_stats['total_buses_current']) * 100) : 0;

// Update current performance with real data
$current_performance['avg_delay'] = $real_stats['avg_delay_current'] ?: 8.7;
$current_performance['avg_occupancy'] = $real_stats['avg_occupancy_current'] ?: 78.3;
$current_performance['on_time_performance'] = $on_time_performance;
$current_performance['active_buses'] = $real_stats['active_buses_current'];

// Baseline data (before optimization) - you can modify these based on your historical data
$baseline_performance = [
    'avg_delay' => 15.2,
    'avg_occupancy' => 68.5,
    'fuel_efficiency' => 6.2,
    'customer_satisfaction' => 3.2,
    'on_time_performance' => 72.3,
    'maintenance_cost' => 62000,
    'total_routes' => 9,
    'active_buses' => 18
];

// Historical optimization milestones (you can expand this)
$optimization_milestones = [
    [
        'date' => '2025-01-15',
        'title' => 'Route Optimization Implementation',
        'description' => 'Implemented AI-based route optimization algorithm',
        'improvements' => [
            'avg_delay' => -3.2,
            'fuel_efficiency' => 0.8,
            'on_time_performance' => 8.5
        ]
    ],
    [
        'date' => '2025-02-28',
        'title' => 'Smart Scheduling System',
        'description' => 'Deployed predictive scheduling based on passenger demand',
        'improvements' => [
            'avg_occupancy' => 6.8,
            'customer_satisfaction' => 0.5,
            'maintenance_cost' => -8000
        ]
    ],
    [
        'date' => '2025-06-10',
        'title' => 'Real-time Tracking Integration',
        'description' => 'Integrated GPS tracking and real-time passenger updates',
        'improvements' => [
            'on_time_performance' => 6.7,
            'customer_satisfaction' => 0.4,
            'avg_delay' => -3.3
        ]
    ]
];

// Calculate improvement percentages
function calculateImprovement($before, $after, $reverse = false) {
    if ($before == 0) return 0;
    $improvement = (($after - $before) / abs($before)) * 100;
    return $reverse ? -$improvement : $improvement;
}

include 'admin_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Optimization Comparison - <?php echo APP_NAME; ?></title>
    <style>
        .optimization-container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .comparison-section { background: white; padding: 25px; margin-bottom: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .comparison-section h2 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 15px; margin-bottom: 20px; }
        
        .metrics-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 25px; margin-bottom: 30px; }
        .metric-card { padding: 20px; border-radius: 10px; text-align: center; position: relative; }
        .before-card { background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%); border: 2px solid #f39c12; }
        .after-card { background: linear-gradient(135deg, #d4edda 0%, #a8e6cf 100%); border: 2px solid #27ae60; }
        .improvement-card { background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border: 2px solid #2196f3; }
        
        .metric-title { font-size: 1.1em; font-weight: bold; margin-bottom: 10px; color: #333; }
        .metric-value { font-size: 2.5em; font-weight: bold; margin-bottom: 5px; }
        .metric-unit { font-size: 0.9em; color: #666; }
        .metric-change { font-size: 1.2em; font-weight: bold; margin-top: 10px; }
        .positive { color: #27ae60; }
        .negative { color: #e74c3c; }
        
        .detailed-comparison { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .comparison-column h3 { text-align: center; margin-bottom: 20px; padding: 10px; border-radius: 8px; }
        .before-column h3 { background: #fff3cd; color: #856404; }
        .after-column h3 { background: #d4edda; color: #155724; }
        
        .metric-row { display: flex; justify-content: space-between; align-items: center; padding: 15px; margin-bottom: 10px; border-radius: 8px; background: #f8f9fa; border-left: 4px solid #007bff; }
        .metric-label { font-weight: 500; color: #333; }
        .metric-data { font-weight: bold; font-size: 1.1em; }
        
        .timeline { margin-top: 30px; }
        .milestone { background: #f8f9fa; padding: 20px; margin-bottom: 15px; border-radius: 8px; border-left: 5px solid #007bff; position: relative; }
        .milestone-date { color: #007bff; font-weight: bold; font-size: 0.9em; }
        .milestone-title { font-size: 1.2em; font-weight: bold; color: #333; margin: 5px 0; }
        .milestone-description { color: #666; margin-bottom: 10px; }
        .milestone-improvements { display: flex; flex-wrap: wrap; gap: 10px; }
        .improvement-badge { background: #e8f5e8; color: #2d5a2d; padding: 5px 10px; border-radius: 15px; font-size: 0.85em; font-weight: 500; }
        
        .charts-section { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-top: 30px; }
        .chart-container { background: #f8f9fa; padding: 20px; border-radius: 10px; }
        .chart-title { text-align: center; font-weight: bold; margin-bottom: 15px; }
        .progress-comparison { margin: 15px 0; }
        .progress-label { display: flex; justify-content: between; margin-bottom: 5px; font-size: 0.9em; }
        .progress-bars { display: flex; gap: 10px; align-items: center; }
        .progress-bar { height: 25px; border-radius: 12px; position: relative; min-width: 100px; }
        .progress-before { background: #ffc107; }
        .progress-after { background: #28a745; }
        .progress-text { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 0.8em; font-weight: bold; color: white; }
        
        .add-milestone-form { background: #f8f9fa; padding: 20px; border-radius: 10px; margin-top: 20px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .form-group textarea { height: 80px; resize: vertical; }
        .submit-btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 1em; }
        .submit-btn:hover { background: #0056b3; }
        
        .summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .summary-card { padding: 20px; border-radius: 10px; text-align: center; }
        .summary-success { background: linear-gradient(135deg, #d4edda, #c3e6cb); border: 2px solid #28a745; }
        .summary-warning { background: linear-gradient(135deg, #fff3cd, #ffeaa7); border: 2px solid #ffc107; }
        .summary-info { background: linear-gradient(135deg, #cce7ff, #b3d9ff); border: 2px solid #007bff; }
    </style>
</head>
<body>
    <div class="optimization-container">
        <h1>🔄 Before/After Optimization Comparison</h1>
        
        <!-- Summary Cards -->
        <div class="summary-cards">
            <div class="summary-card summary-success">
                <h3>✅ Overall Improvement</h3>
                <div class="metric-value positive">+<?php echo number_format(calculateImprovement($baseline_performance['on_time_performance'], $current_performance['on_time_performance']), 1); ?>%</div>
                <div class="metric-unit">Performance Gain</div>
            </div>
            <div class="summary-card summary-info">
                <h3>📊 Optimization Period</h3>
                <div class="metric-value" style="color: #007bff;">8</div>
                <div class="metric-unit">Months Active</div>
            </div>
            <div class="summary-card summary-warning">
                <h3>💰 Cost Savings</h3>
                <div class="metric-value" style="color: #f39c12;">$<?php echo number_format($baseline_performance['maintenance_cost'] - $current_performance['maintenance_cost']); ?></div>
                <div class="metric-unit">Monthly Reduction</div>
            </div>
        </div>

        <!-- Key Metrics Comparison -->
        <div class="comparison-section">
            <h2>📈 Key Performance Indicators</h2>
            
            <!-- Average Delay -->
            <div class="metrics-grid">
                <div class="metric-card before-card">
                    <div class="metric-title">📉 Before Optimization</div>
                    <div class="metric-value" style="color: #f39c12;"><?php echo $baseline_performance['avg_delay']; ?></div>
                    <div class="metric-unit">Average Delay (minutes)</div>
                </div>
                <div class="metric-card after-card">
                    <div class="metric-title">📈 After Optimization</div>
                    <div class="metric-value" style="color: #27ae60;"><?php echo number_format($current_performance['avg_delay'], 1); ?></div>
                    <div class="metric-unit">Average Delay (minutes)</div>
                </div>
                <div class="metric-card improvement-card">
                    <div class="metric-title">🎯 Improvement</div>
                    <div class="metric-value positive"><?php echo number_format(calculateImprovement($baseline_performance['avg_delay'], $current_performance['avg_delay'], true), 1); ?>%</div>
                    <div class="metric-change positive">-<?php echo number_format($baseline_performance['avg_delay'] - $current_performance['avg_delay'], 1); ?> min</div>
                </div>
            </div>

            <!-- Occupancy Rate -->
            <div class="metrics-grid">
                <div class="metric-card before-card">
                    <div class="metric-title">📉 Before Optimization</div>
                    <div class="metric-value" style="color: #f39c12;"><?php echo $baseline_performance['avg_occupancy']; ?>%</div>
                    <div class="metric-unit">Average Occupancy</div>
                </div>
                <div class="metric-card after-card">
                    <div class="metric-title">📈 After Optimization</div>
                    <div class="metric-value" style="color: #27ae60;"><?php echo number_format($current_performance['avg_occupancy'], 1); ?>%</div>
                    <div class="metric-unit">Average Occupancy</div>
                </div>
                <div class="metric-card improvement-card">
                    <div class="metric-title">🎯 Improvement</div>
                    <div class="metric-value positive">+<?php echo number_format(calculateImprovement($baseline_performance['avg_occupancy'], $current_performance['avg_occupancy']), 1); ?>%</div>
                    <div class="metric-change positive">+<?php echo number_format($current_performance['avg_occupancy'] - $baseline_performance['avg_occupancy'], 1); ?>%</div>
                </div>
            </div>

            <!-- On-time Performance -->
            <div class="metrics-grid">
                <div class="metric-card before-card">
                    <div class="metric-title">📉 Before Optimization</div>
                    <div class="metric-value" style="color: #f39c12;"><?php echo $baseline_performance['on_time_performance']; ?>%</div>
                    <div class="metric-unit">On-time Performance</div>
                </div>
                <div class="metric-card after-card">
                    <div class="metric-title">📈 After Optimization</div>
                    <div class="metric-value" style="color: #27ae60;"><?php echo number_format($current_performance['on_time_performance'], 1); ?>%</div>
                    <div class="metric-unit">On-time Performance</div>
                </div>
                <div class="metric-card improvement-card">
                    <div class="metric-title">🎯 Improvement</div>
                    <div class="metric-value positive">+<?php echo number_format(calculateImprovement($baseline_performance['on_time_performance'], $current_performance['on_time_performance']), 1); ?>%</div>
                    <div class="metric-change positive">+<?php echo number_format($current_performance['on_time_performance'] - $baseline_performance['on_time_performance'], 1); ?>%</div>
                </div>
            </div>
        </div>

        <!-- Detailed Comparison -->
        <div class="comparison-section">
            <h2>🔍 Detailed Performance Analysis</h2>
            <div class="detailed-comparison">
                <div class="comparison-column">
                    <h3>📊 Before Optimization</h3>
                    <div class="metric-row">
                        <span class="metric-label">Average Delay:</span>
                        <span class="metric-data"><?php echo $baseline_performance['avg_delay']; ?> minutes</span>
                    </div>
                    <div class="metric-row">
                        <span class="metric-label">Occupancy Rate:</span>
                        <span class="metric-data"><?php echo $baseline_performance['avg_occupancy']; ?>%</span>
                    </div>
                    <div class="metric-row">
                        <span class="metric-label">Fuel Efficiency:</span>
                        <span class="metric-data"><?php echo $baseline_performance['fuel_efficiency']; ?> km/l</span>
                    </div>
                    <div class="metric-row">
                        <span class="metric-label">Customer Satisfaction:</span>
                        <span class="metric-data"><?php echo $baseline_performance['customer_satisfaction']; ?>/5.0</span>
                    </div>
                    <div class="metric-row">
                        <span class="metric-label">On-time Performance:</span>
                        <span class="metric-data"><?php echo $baseline_performance['on_time_performance']; ?>%</span>
                    </div>
                    <div class="metric-row">
                        <span class="metric-label">Monthly Maintenance:</span>
                        <span class="metric-data">$<?php echo number_format($baseline_performance['maintenance_cost']); ?></span>
                    </div>
                </div>

                <div class="comparison-column">
                    <h3>📈 After Optimization</h3>
                    <div class="metric-row">
                        <span class="metric-label">Average Delay:</span>
                        <span class="metric-data positive"><?php echo number_format($current_performance['avg_delay'], 1); ?> minutes</span>
                    </div>
                    <div class="metric-row">
                        <span class="metric-label">Occupancy Rate:</span>
                        <span class="metric-data positive"><?php echo number_format($current_performance['avg_occupancy'], 1); ?>%</span>
                    </div>
                    <div class="metric-row">
                        <span class="metric-label">Fuel Efficiency:</span>
                        <span class="metric-data positive"><?php echo $current_performance['fuel_efficiency']; ?> km/l</span>
                    </div>
                    <div class="metric-row">
                        <span class="metric-label">Customer Satisfaction:</span>
                        <span class="metric-data positive"><?php echo $current_performance['customer_satisfaction']; ?>/5.0</span>
                    </div>
                    <div class="metric-row">
                        <span class="metric-label">On-time Performance:</span>
                        <span class="metric-data positive"><?php echo number_format($current_performance['on_time_performance'], 1); ?>%</span>
                    </div>
                    <div class="metric-row">
                        <span class="metric-label">Monthly Maintenance:</span>
                        <span class="metric-data positive">$<?php echo number_format($current_performance['maintenance_cost']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Visual Progress Comparison -->
        <div class="comparison-section">
            <h2>📊 Visual Performance Comparison</h2>
            <div class="charts-section">
                <div class="chart-container">
                    <div class="chart-title">Performance Metrics Comparison</div>
                    
                    <div class="progress-comparison">
                        <div class="progress-label">
                            <span>Average Delay (Lower is Better)</span>
                        </div>
                        <div class="progress-bars">
                            <div class="progress-bar progress-before" style="width: <?php echo ($baseline_performance['avg_delay'] / 20) * 100; ?>px;">
                                <div class="progress-text"><?php echo $baseline_performance['avg_delay']; ?>m</div>
                            </div>
                            <div class="progress-bar progress-after" style="width: <?php echo ($current_performance['avg_delay'] / 20) * 100; ?>px;">
                                <div class="progress-text"><?php echo number_format($current_performance['avg_delay'], 1); ?>m</div>
                            </div>
                        </div>
                    </div>

                    <div class="progress-comparison">
                        <div class="progress-label">
                            <span>Occupancy Rate</span>
                        </div>
                        <div class="progress-bars">
                            <div class="progress-bar progress-before" style="width: <?php echo $baseline_performance['avg_occupancy']; ?>%;">
                                <div class="progress-text"><?php echo $baseline_performance['avg_occupancy']; ?>%</div>
                            </div>
                            <div class="progress-bar progress-after" style="width: <?php echo $current_performance['avg_occupancy']; ?>%;">
                                <div class="progress-text"><?php echo number_format($current_performance['avg_occupancy'], 1); ?>%</div>
                            </div>
                        </div>
                    </div>

                    <div class="progress-comparison">
                        <div class="progress-label">
                            <span>On-time Performance</span>
                        </div>
                        <div class="progress-bars">
                            <div class="progress-bar progress-before" style="width: <?php echo $baseline_performance['on_time_performance']; ?>%;">
                                <div class="progress-text"><?php echo $baseline_performance['on_time_performance']; ?>%</div>
                            </div>
                            <div class="progress-bar progress-after" style="width: <?php echo $current_performance['on_time_performance']; ?>%;">
                                <div class="progress-text"><?php echo number_format($current_performance['on_time_performance'], 1); ?>%</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="chart-container">
                    <div class="chart-title">Cost & Satisfaction Metrics</div>
                    
                    <div class="progress-comparison">
                        <div class="progress-label">
                            <span>Customer Satisfaction</span>
                        </div>
                        <div class="progress-bars">
                            <div class="progress-bar progress-before" style="width: <?php echo ($baseline_performance['customer_satisfaction'] / 5) * 100; ?>%;">
                                <div class="progress-text"><?php echo $baseline_performance['customer_satisfaction']; ?>/5</div>
                            </div>
                            <div class="progress-bar progress-after" style="width: <?php echo ($current_performance['customer_satisfaction'] / 5) * 100; ?>%;">
                                <div class="progress-text"><?php echo $current_performance['customer_satisfaction']; ?>/5</div>
                            </div>
                        </div>
                    </div>

                    <div class="progress-comparison">
                        <div class="progress-label">
                            <span>Fuel Efficiency</span>
                        </div>
                        <div class="progress-bars">
                            <div class="progress-bar progress-before" style="width: <?php echo ($baseline_performance['fuel_efficiency'] / 10) * 100; ?>%;">
                                <div class="progress-text"><?php echo $baseline_performance['fuel_efficiency']; ?> km/l</div>
                            </div>
                            <div class="progress-bar progress-after" style="width: <?php echo ($current_performance['fuel_efficiency'] / 10) * 100; ?>%;">
                                <div class="progress-text"><?php echo $current_performance['fuel_efficiency']; ?> km/l</div>
                            </div>
                        </div>
                    </div>

                    <div class="progress-comparison">
                        <div class="progress-label">
                            <span>Maintenance Cost (Lower is Better)</span>
                        </div>
                        <div class="progress-bars">
                            <div class="progress-bar progress-before" style="width: <?php echo ($baseline_performance['maintenance_cost'] / 70000) * 200; ?>px;">
                                <div class="progress-text">$<?php echo number_format($baseline_performance['maintenance_cost']); ?></div>
                            </div>
                            <div class="progress-bar progress-after" style="width: <?php echo ($current_performance['maintenance_cost'] / 70000) * 200; ?>px;">
                                <div class="progress-text">$<?php echo number_format($current_performance['maintenance_cost']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Optimization Timeline -->
        <div class="comparison-section">
            <h2>📅 Optimization Timeline</h2>
            <div class="timeline">
                <?php foreach ($optimization_milestones as $milestone): ?>
                <div class="milestone">
                    <div class="milestone-date"><?php echo date('M j, Y', strtotime($milestone['date'])); ?></div>
                    <div class="milestone-title"><?php echo htmlspecialchars($milestone['title']); ?></div>
                    <div class="milestone-description"><?php echo htmlspecialchars($milestone['description']); ?></div>
                    <div class="milestone-improvements">
                        <?php foreach ($milestone['improvements'] as $metric => $improvement): ?>
                            <div class="improvement-badge">
                                <?php echo ucfirst(str_replace('_', ' ', $metric)); ?>: 
                                <?php echo $improvement > 0 ? '+' : ''; ?><?php echo $improvement; ?>
                                <?php echo in_array($metric, ['fuel_efficiency']) ? ' km/l' : (in_array($metric, ['maintenance_cost']) ? '' : '%'); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Add New Milestone -->
        <div class="comparison-section">
            <h2>➕ Add Optimization Milestone</h2>
            <form method="POST" class="add-milestone-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="milestone_date">Date:</label>
                        <input type="date" id="milestone_date" name="milestone_date" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <input type="text" id="description" name="description" placeholder="Brief description of optimization" required>
                    </div>
                    <div class="form-group">
                        <label for="avg_delay">Average Delay (minutes):</label>
                        <input type="number" id="avg_delay" name="avg_delay" step="0.1" placeholder="8.5">
                    </div>
                    <div class="form-group">
                        <label for="avg_occupancy">Occupancy Rate (%):</label>
                        <input type="number" id="avg_occupancy" name="avg_occupancy" step="0.1" placeholder="75.2">
                    </div>
                    <div class="form-group">
                        <label for="fuel_efficiency">Fuel Efficiency (km/l):</label>
                        <input type="number" id="fuel_efficiency" name="fuel_efficiency" step="0.1" placeholder="7.5">
                    </div>
                    <div class="form-group">
                        <label for="customer_satisfaction">Customer Satisfaction (1-5):</label>
                        <input type="number" id="customer_satisfaction" name="customer_satisfaction" step="0.1" min="1" max="5" placeholder="4.2">
                    </div>
                    <div class="form-group">
                        <label for="on_time_performance">On-time Performance (%):</label>
                        <input type="number" id="on_time_performance" name="on_time_performance" step="0.1" placeholder="85.5">
                    </div>
                    <div class="form-group">
                        <label for="maintenance_cost">Monthly Maintenance Cost ($):</label>
                        <input type="number" id="maintenance_cost" name="maintenance_cost" placeholder="45000">
                    </div>
                </div>
                <button type="submit" name="add_optimization" class="submit-btn">📊 Add Optimization Milestone</button>
            </form>
        </div>
    </div>
</body>
</html>

<?php include 'admin_footer.php'; ?>