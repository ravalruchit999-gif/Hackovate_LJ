<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
requireAdmin();

$current_user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add New Route
    if (isset($_POST['add_route'])) {
        $stmt = $db->prepare("INSERT INTO routes (city_id, route_code, route_name, start_point, end_point, distance_km, estimated_duration, is_active, created_at) 
                              VALUES (:city_id, :route_code, :route_name, :start_point, :end_point, :distance_km, :estimated_duration, 1, NOW())");
        $stmt->execute([
            ':city_id' => $_POST['city_id'],
            ':route_code' => $_POST['route_code'],
            ':route_name' => $_POST['route_name'],
            ':start_point' => $_POST['start_point'],
            ':end_point' => $_POST['end_point'],
            ':distance_km' => $_POST['distance_km'],
            ':estimated_duration' => $_POST['estimated_duration']
        ]);
    }
    
    // Add New Bus Stop
    elseif (isset($_POST['add_bus_stop'])) {
        $stmt = $db->prepare("INSERT INTO bus_stops (stop_name, latitude, longitude, city_id, is_active) 
                              VALUES (:stop_name, :latitude, :longitude, :city_id, 1)");
        $stmt->execute([
            ':stop_name' => $_POST['stop_name'],
            ':latitude' => $_POST['latitude'] ?: 0,
            ':longitude' => $_POST['longitude'] ?: 0,
            ':city_id' => $_POST['city_id']
        ]);
    }
    
    // Add Route-Stop Association
    elseif (isset($_POST['add_route_stop'])) {
        $stmt = $db->prepare("INSERT INTO route_stops (route_id, stop_id, stop_sequence, estimated_time_from_start) 
                              VALUES (:route_id, :stop_id, :stop_sequence, :estimated_time_from_start)");
        $stmt->execute([
            ':route_id' => $_POST['route_id'],
            ':stop_id' => $_POST['stop_id'],
            ':stop_sequence' => $_POST['stop_sequence'],
            ':estimated_time_from_start' => $_POST['estimated_time_from_start']
        ]);
    }
    
    // Delete Route
    elseif (isset($_POST['delete_route'])) {
        $route_id = $_POST['id'];
        
        // First delete route-stop associations
        $stmt = $db->prepare("DELETE FROM route_stops WHERE route_id = :id");
        $stmt->execute([':id' => $route_id]);
        
        // Delete bus schedules
        $stmt = $db->prepare("DELETE FROM bus_schedules WHERE route_id = :id");
        $stmt->execute([':id' => $route_id]);
        
        // Update buses assigned to this route
        $stmt = $db->prepare("UPDATE buses SET route_id = NULL WHERE route_id = :id");
        $stmt->execute([':id' => $route_id]);
        
        // Then delete the route
        $stmt = $db->prepare("DELETE FROM routes WHERE id = :id");
        $stmt->execute([':id' => $route_id]);
    }
    
    // Delete Bus Stop
    elseif (isset($_POST['delete_bus_stop'])) {
        $stop_id = $_POST['id'];
        
        // First delete route-stop associations
        $stmt = $db->prepare("DELETE FROM route_stops WHERE stop_id = :id");
        $stmt->execute([':id' => $stop_id]);
        
        // Then delete the bus stop
        $stmt = $db->prepare("DELETE FROM bus_stops WHERE id = :id");
        $stmt->execute([':id' => $stop_id]);
    }
    
    // Toggle Route Status
    elseif (isset($_POST['toggle_route_status'])) {
        $stmt = $db->prepare("UPDATE routes SET is_active = NOT is_active WHERE id = :id");
        $stmt->execute([':id' => $_POST['id']]);
    }
    
    // Toggle Bus Stop Status
    elseif (isset($_POST['toggle_stop_status'])) {
        $stmt = $db->prepare("UPDATE bus_stops SET is_active = NOT is_active WHERE id = :id");
        $stmt->execute([':id' => $_POST['id']]);
    }
}

// Get current view from URL parameter
$view = $_GET['view'] ?? 'routes';

// Fetch data based on current view
$routes = [];
$bus_stops = [];
$cities = [];
$route_optimization = [];

// Always fetch cities for dropdowns
$stmt = $db->prepare("SELECT * FROM cities WHERE is_active = 1 ORDER BY name");
$stmt->execute();
$cities = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($view === 'routes' || $view === 'create_route') {
    // Fetch all routes with city information
    $stmt = $db->prepare("SELECT r.*, c.name as city_name, c.code as city_code,
                         (SELECT COUNT(*) FROM buses WHERE route_id = r.id) as bus_count,
                         (SELECT COUNT(*) FROM route_stops WHERE route_id = r.id) as stop_count
                         FROM routes r 
                         LEFT JOIN cities c ON r.city_id = c.id 
                         ORDER BY r.created_at DESC");
    $stmt->execute();
    $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($view === 'bus_stops') {
    // Fetch all bus stops with city information
    $stmt = $db->prepare("SELECT bs.*, c.name as city_name, c.code as city_code,
                         (SELECT COUNT(*) FROM route_stops WHERE stop_id = bs.id) as route_count
                         FROM bus_stops bs 
                         LEFT JOIN cities c ON bs.city_id = c.id 
                         ORDER BY c.name, bs.stop_name");
    $stmt->execute();
    $bus_stops = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($view === 'optimization') {
    // Fetch route optimization data with performance metrics
    $stmt = $db->prepare("SELECT r.*, c.name as city_name,
                         COUNT(DISTINCT rs.stop_id) as total_stops,
                         COUNT(DISTINCT b.id) as total_buses,
                         AVG(b.current_passengers) as avg_passengers,
                         COUNT(CASE WHEN b.status = 'delayed' THEN 1 END) as delayed_buses,
                         (r.distance_km / NULLIF(r.estimated_duration, 0) * 60) as avg_speed_kmh
                         FROM routes r 
                         LEFT JOIN cities c ON r.city_id = c.id
                         LEFT JOIN route_stops rs ON r.id = rs.route_id 
                         LEFT JOIN buses b ON r.id = b.route_id
                         WHERE r.is_active = 1
                         GROUP BY r.id 
                         ORDER BY r.distance_km DESC");
    $stmt->execute();
    $route_optimization = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch all routes and stops for dropdowns in create route view
$all_routes = [];
$all_stops = [];
if ($view === 'create_route') {
    $stmt = $db->prepare("SELECT r.id, r.route_code, r.route_name, c.name as city_name 
                         FROM routes r 
                         LEFT JOIN cities c ON r.city_id = c.id 
                         ORDER BY c.name, r.route_name");
    $stmt->execute();
    $all_routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("SELECT bs.id, bs.stop_name, c.name as city_name, c.code as city_code
                         FROM bus_stops bs 
                         LEFT JOIN cities c ON bs.city_id = c.id 
                         WHERE bs.is_active = 1
                         ORDER BY c.name, bs.stop_name");
    $stmt->execute();
    $all_stops = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include 'admin_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Route Planning - <?php echo APP_NAME; ?></title>
    <style>
        .nav-tabs {
            display: flex;
            background: #f1f1f1;
            border-radius: 5px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .nav-tab {
            flex: 1;
            padding: 12px 20px;
            background: #f1f1f1;
            border: none;
            cursor: pointer;
            text-decoration: none;
            color: #333;
            text-align: center;
            transition: background 0.3s;
        }
        .nav-tab:hover {
            background: #e1e1e1;
        }
        .nav-tab.active {
            background: #007bff;
            color: white;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        .form-grid.single {
            grid-template-columns: 1fr;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input, .form-group select, .form-group textarea {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .badge-active { background: #28a745; color: white; padding: 4px 8px; border-radius: 12px; }
        .badge-inactive { background: #dc3545; color: white; padding: 4px 8px; border-radius: 12px; }
        .actions { display: flex; gap: 5px; }
        .actions form { margin: 0; }
        .actions button { padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer; }
        .btn-delete { background: #dc3545; color: white; }
        .btn-toggle { background: #ffc107; color: black; }
        .optimization-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .optimization-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        .metric {
            text-align: center;
            padding: 10px;
            background: white;
            border-radius: 5px;
        }
        .metric-value {
            font-size: 20px;
            font-weight: bold;
            color: #007bff;
        }
        .metric-label {
            font-size: 11px;
            color: #6c757d;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        th, td {
            padding: 12px 8px;
            border: 1px solid #dee2e6;
            text-align: left;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .form-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚌 Route Planning Management</h1>

        <!-- Navigation Tabs -->
        <div class="nav-tabs">
            <a href="?view=routes" class="nav-tab <?php echo $view === 'routes' ? 'active' : ''; ?>">
                📋 Manage Routes
            </a>
            <a href="?view=create_route" class="nav-tab <?php echo $view === 'create_route' ? 'active' : ''; ?>">
                ➕ Create New Route
            </a>
            <a href="?view=optimization" class="nav-tab <?php echo $view === 'optimization' ? 'active' : ''; ?>">
                📊 Route Optimization
            </a>
            <a href="?view=bus_stops" class="nav-tab <?php echo $view === 'bus_stops' ? 'active' : ''; ?>">
                🚏 Manage Bus Stops
            </a>
        </div>

        <?php if ($view === 'routes'): ?>
            <!-- Manage Routes View -->
            <h2>📋 Manage Routes</h2>
            
            <!-- Add New Route Form -->
            <div class="form-container">
                <form method="POST">
                    <h3>Add New Route</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>City:</label>
                            <select name="city_id" required>
                                <option value="">Choose a city...</option>
                                <?php foreach ($cities as $city): ?>
                                <option value="<?php echo $city['id']; ?>">
                                    <?php echo htmlspecialchars($city['name'] . ' (' . $city['code'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Route Code:</label>
                            <input type="text" name="route_code" placeholder="e.g., BLR-010" required>
                        </div>
                        <div class="form-group">
                            <label>Route Name:</label>
                            <input type="text" name="route_name" placeholder="e.g., Airport Express" required>
                        </div>
                        <div class="form-group">
                            <label>Start Point:</label>
                            <input type="text" name="start_point" placeholder="Starting location" required>
                        </div>
                        <div class="form-group">
                            <label>End Point:</label>
                            <input type="text" name="end_point" placeholder="End location" required>
                        </div>
                        <div class="form-group">
                            <label>Distance (KM):</label>
                            <input type="number" name="distance_km" step="0.1" placeholder="Distance in kilometers" required>
                        </div>
                    </div>
                    <div class="form-grid single">
                        <div class="form-group">
                            <label>Estimated Duration (minutes):</label>
                            <input type="number" name="estimated_duration" placeholder="Duration in minutes" required>
                        </div>
                    </div>
                    <button type="submit" name="add_route" style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px;">
                        ➕ Add Route
                    </button>
                </form>
            </div>

            <!-- Routes Table -->
            <h3>Route List</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>City</th>
                        <th>Route Code</th>
                        <th>Route Name</th>
                        <th>Start → End</th>
                        <th>Distance</th>
                        <th>Duration</th>
                        <th>Buses</th>
                        <th>Stops</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($routes as $route): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($route['id']); ?></td>
                        <td><?php echo htmlspecialchars($route['city_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($route['route_code']); ?></td>
                        <td><?php echo htmlspecialchars($route['route_name']); ?></td>
                        <td><?php echo htmlspecialchars($route['start_point'] . ' → ' . $route['end_point']); ?></td>
                        <td><?php echo $route['distance_km']; ?> km</td>
                        <td><?php echo $route['estimated_duration']; ?> min</td>
                        <td><?php echo $route['bus_count']; ?></td>
                        <td><?php echo $route['stop_count']; ?></td>
                        <td>
                            <?php if ($route['is_active']): ?>
                                <span class="badge-active">Active</span>
                            <?php else: ?>
                                <span class="badge-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="id" value="<?php echo $route['id']; ?>">
                                <button type="submit" name="toggle_route_status" class="btn-toggle">
                                    <?php echo $route['is_active'] ? '⏸️' : '▶️'; ?>
                                </button>
                            </form>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this route?');">
                                <input type="hidden" name="id" value="<?php echo $route['id']; ?>">
                                <button type="submit" name="delete_route" class="btn-delete">🗑️</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($routes)): ?>
                    <tr><td colspan="11" style="padding: 20px; text-align: center; color: #6c757d;">No routes found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php elseif ($view === 'create_route'): ?>
            <!-- Create New Route View -->
            <h2>➕ Create New Route</h2>
            
            <!-- Route-Stop Association Form -->
            <div class="form-container">
                <form method="POST">
                    <h3>Add Stop to Route</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Select Route:</label>
                            <select name="route_id" required>
                                <option value="">Choose a route...</option>
                                <?php foreach ($all_routes as $route): ?>
                                <option value="<?php echo $route['id']; ?>">
                                    <?php echo htmlspecialchars($route['city_name'] . ' - ' . $route['route_code'] . ' (' . $route['route_name'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Select Bus Stop:</label>
                            <select name="stop_id" required>
                                <option value="">Choose a bus stop...</option>
                                <?php foreach ($all_stops as $stop): ?>
                                <option value="<?php echo $stop['id']; ?>">
                                    <?php echo htmlspecialchars($stop['city_name'] . ' - ' . $stop['stop_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Stop Sequence:</label>
                            <input type="number" name="stop_sequence" placeholder="e.g., 1, 2, 3..." required>
                        </div>
                        <div class="form-group">
                            <label>Time from Start (minutes):</label>
                            <input type="number" name="estimated_time_from_start" placeholder="Minutes from route start" required>
                        </div>
                    </div>
                    <button type="submit" name="add_route_stop" style="background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px;">
                        🚏 Add Stop to Route
                    </button>
                </form>
            </div>

        <?php elseif ($view === 'optimization'): ?>
            <!-- Route Optimization View -->
            <h2>📊 Route Optimization</h2>
            
            <p style="color: #6c757d; margin-bottom: 30px;">
                Analyze and optimize your bus routes for better efficiency and performance.
            </p>

            <?php foreach ($route_optimization as $route): ?>
            <div class="optimization-card">
                <h4><?php echo htmlspecialchars($route['route_code'] . ' - ' . $route['route_name']); ?></h4>
                <p><strong>City:</strong> <?php echo htmlspecialchars($route['city_name']); ?></p>
                <p><?php echo htmlspecialchars($route['start_point'] . ' → ' . $route['end_point']); ?></p>
                
                <div class="optimization-metrics">
                    <div class="metric">
                        <div class="metric-value"><?php echo $route['distance_km']; ?></div>
                        <div class="metric-label">Distance (KM)</div>
                    </div>
                    <div class="metric">
                        <div class="metric-value"><?php echo $route['estimated_duration']; ?></div>
                        <div class="metric-label">Duration (Min)</div>
                    </div>
                    <div class="metric">
                        <div class="metric-value"><?php echo $route['total_stops'] ?: '0'; ?></div>
                        <div class="metric-label">Total Stops</div>
                    </div>
                    <div class="metric">
                        <div class="metric-value"><?php echo $route['total_buses'] ?: '0'; ?></div>
                        <div class="metric-label">Buses</div>
                    </div>
                    <div class="metric">
                        <div class="metric-value"><?php echo $route['avg_passengers'] ? round($route['avg_passengers']) : '0'; ?></div>
                        <div class="metric-label">Avg Passengers</div>
                    </div>
                    <div class="metric">
                        <div class="metric-value"><?php echo $route['delayed_buses'] ?: '0'; ?></div>
                        <div class="metric-label">Delayed Buses</div>
                    </div>
                    <div class="metric">
                        <div class="metric-value"><?php echo $route['avg_speed_kmh'] ? round($route['avg_speed_kmh'], 1) : '0'; ?></div>
                        <div class="metric-label">Avg Speed (KM/H)</div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($route_optimization)): ?>
            <p style="text-align: center; color: #6c757d; padding: 40px;">No active routes available for optimization analysis.</p>
            <?php endif; ?>

        <?php elseif ($view === 'bus_stops'): ?>
            <!-- Manage Bus Stops View -->
            <h2>🚏 Manage Bus Stops</h2>
            
            <!-- Add New Bus Stop Form -->
            <div class="form-container">
                <form method="POST">
                    <h3>Add New Bus Stop</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>City:</label>
                            <select name="city_id" required>
                                <option value="">Choose a city...</option>
                                <?php foreach ($cities as $city): ?>
                                <option value="<?php echo $city['id']; ?>">
                                    <?php echo htmlspecialchars($city['name'] . ' (' . $city['code'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Stop Name:</label>
                            <input type="text" name="stop_name" placeholder="e.g., Central Mall" required>
                        </div>
                        <div class="form-group">
                            <label>Latitude:</label>
                            <input type="number" name="latitude" step="any" placeholder="e.g., 23.0225" required>
                        </div>
                        <div class="form-group">
                            <label>Longitude:</label>
                            <input type="number" name="longitude" step="any" placeholder="e.g., 72.5714" required>
                        </div>
                    </div>
                    <button type="submit" name="add_bus_stop" style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px;">
                        ➕ Add Bus Stop
                    </button>
                </form>
            </div>

            <!-- Bus Stops Table -->
            <h3>Bus Stop List</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>City</th>
                        <th>Stop Name</th>
                        <th>Coordinates</th>
                        <th>Routes</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bus_stops as $stop): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($stop['id']); ?></td>
                        <td><?php echo htmlspecialchars($stop['city_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($stop['stop_name']); ?></td>
                        <td><?php echo $stop['latitude'] . ', ' . $stop['longitude']; ?></td>
                        <td><?php echo $stop['route_count']; ?></td>
                        <td>
                            <?php if ($stop['is_active']): ?>
                                <span class="badge-active">Active</span>
                            <?php else: ?>
                                <span class="badge-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="id" value="<?php echo $stop['id']; ?>">
                                <button type="submit" name="toggle_stop_status" class="btn-toggle">
                                    <?php echo $stop['is_active'] ? '⏸️' : '▶️'; ?>
                                </button>
                            </form>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this bus stop?');">
                                <input type="hidden" name="id" value="<?php echo $stop['id']; ?>">
                                <button type="submit" name="delete_bus_stop" class="btn-delete">🗑️</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($bus_stops)): ?>
                    <tr><td colspan="7" style="padding: 20px; text-align: center; color: #6c757d;">No bus stops found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php endif; ?>
    </div>
</body>
</html>

<?php include 'admin_footer.php'; ?>