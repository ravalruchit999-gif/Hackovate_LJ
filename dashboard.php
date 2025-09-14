<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

// Require login
requireLogin();

$current_user = getCurrentUser();
$user_city = $current_user['city'];

// Get user's city routes
$database = new Database();
$db = $database->getConnection();

// Get city info
$city_query = "SELECT * FROM cities WHERE code = :city_code";
$city_stmt = $db->prepare($city_query);
$city_stmt->bindParam(':city_code', $user_city);
$city_stmt->execute();
$city_info = $city_stmt->fetch(PDO::FETCH_ASSOC);

// Get routes for user's city
$routes_query = "SELECT * FROM routes WHERE city_id = :city_id AND is_active = 1";
$routes_stmt = $db->prepare($routes_query);
$routes_stmt->bindParam(':city_id', $city_info['id']);
$routes_stmt->execute();
$routes = $routes_stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />
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
            color: #333;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea, #764ba2);
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
            color: #fff;
        }
        
        .nav-user {
            display: flex;
            align-items: center;
            gap: 20px;
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
            background: Black;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .logout-btn {
            padding: 8px 16px;
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .welcome-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            text-align: center;
        }
        
        .welcome-section h1 {
            color: #667eea;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .welcome-section p {
            color: #666;
            font-size: 1.2rem;
        }
        
        .city-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            margin-top: 10px;
        }
        
        .dashboard {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .card h3 {
            color: #4a5568;
            margin-bottom: 15px;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .controls {
           
            padding: 15px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            background: #fff;
            min-width: 240px;
            text-align: center;
            margin-bottom: 25px;
            margin-top  : 25px;
        }
        
        .control-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
            padding: 15px 20px;
        }
        
        .control-group label {
            font-weight: 600;
            color: #fff;
            font-size: 0.9rem;
        }
        
        select, button {
            padding: 8px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        button {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 600;
            padding: 20px;
            margin-top: 10px;
            transition: transform 0.2s ease, box-shadow 0.3s ease;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        button.stop {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
        }
        
        .status-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.9);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .status-item {
            text-align: center;
        }
        
        .status-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: #667eea;
        }
        
        .status-label {
            font-size: 0.9rem;
            color: #666;
            margin-top: 5px;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 15px;
        }
        
        #map {
            height: 400px;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .alerts {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .alert {
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 8px;
            border-left: 4px solid;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .alert.warning {
            background-color: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        
        .alert.info {
            background-color: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
        
        .alert.success {
            background-color: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        
        .full-width {
            grid-column: 1 / -1;
        }
        
        .metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .metric-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.7));
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .metric-value {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .metric-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .routes-list {
            display: grid;
            gap: 10px;
            margin-top: 15px;
        }
        
        .route-item {
            background: rgba(102, 126, 234, 0.1);
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        
        .route-code {
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .route-name {
            color: #4a5568;
            margin-bottom: 5px;
        }
        
        .route-details {
            font-size: 0.9rem;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .dashboard {
                grid-template-columns: 1fr;
            }
            
            .controls {
                flex-direction: column;
            }
            
            .status-bar {
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-container {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .welcome-section h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">🚌 Smart Bus System</div>
            <div class="nav-user">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($current_user['full_name'], 0, 1)); ?>
                    </div>
                    <div>
                        <div style="font-weight: 600;color: #fff;"><?php echo htmlspecialchars($current_user['full_name']); ?></div>
                        <div style="font-size: 0.8rem; color: #fff;"><?php echo ucfirst($current_user['role']); ?></div>
                    </div>
                </div>
                
                <a href="?logout=1" class="logout-btn">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-section">
            <h1>Welcome back, <?php echo htmlspecialchars($current_user['full_name']); ?>!</h1>
            <p>Monitor and manage bus operations in your city</p>
            <div class="city-badge">📍 <?php echo htmlspecialchars($city_info['name']); ?></div>
        </div>
        
        <div class="controls">
            <div class="control-group">
                <label for="routeSelect">Select Route:</label>
                <select id="routeSelect">
                    <option value="all">All Routes</option>
                    <?php foreach ($routes as $route): ?>
                        <option value="<?php echo $route['id']; ?>">
                            <?php echo htmlspecialchars($route['route_code'] . ' - ' . $route['route_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button id="startSimulation">Start Real-time Simulation</button>
            <button id="stopSimulation" class="stop" style="display: none;">Stop Simulation</button>
            <button id="optimizeSchedule">Optimize Schedule</button>
        </div>
        
        <div class="status-bar">
            <div class="metric-card">
                <div class="status-value" id="activeBuses">24</div>
                <div class="status-label">Active Buses</div>
            </div>
            <div class="metric-card">
                <div class="status-value" id="totalPassengers">1,247</div>
                <div class="status-label">Total Passengers</div>
            </div>
            <div class="metric-card">
                <div class="status-value" id="avgWaitTime">8.5</div>
                <div class="status-label">Avg Wait Time (min)</div>
            </div>
            <div class="metric-card">
                <div class="status-value" id="onTimePerf">78%</div>
                <div class="status-label">On-time Performance</div>
            </div>
        </div>
        
        <div class="metrics">
            <div class="metric-card">
                <div class="metric-value" id="waitTimeImprovement">-</div>
                <div class="metric-label">Wait Time Reduction</div>
            </div>
            <div class="metric-card">
                <div class="metric-value" id="utilizationImprovement">-</div>
                <div class="metric-label">Bus Utilization</div>
            </div>
            <div class="metric-card">
                <div class="metric-value" id="bunchingReduction">-</div>
                <div class="metric-label">Bunching Reduction</div>
            </div>
            <div class="metric-card">
                <div class="metric-value" id="fuelSavings">-</div>
                <div class="metric-label">Fuel Savings</div>
            </div>
        </div>
        
        <div class="dashboard">
            <div class="card">
                <h3>📍 Live Bus Tracking</h3>
                <div id="map"></div>
            </div>
            
            <div class="card">
                <h3>🔔 System Alerts</h3>
                <div id="alerts" class="alerts">
                    <div class="alert info">System initialized for <?php echo htmlspecialchars($city_info['name']); ?>. Ready for optimization.</div>
                </div>
            </div>
            
            <div class="card">
                <h3>📊 Ridership Prediction vs Actual</h3>
                <div class="chart-container">
                    <canvas id="ridership-chart"></canvas>
                </div>
            </div>
            
            <div class="card">
                <h3>⏱️ Schedule Performance</h3>
                <div class="chart-container">
                    <canvas id="schedule-chart"></canvas>
                </div>
            </div>
            
            <div class="card full-width">
                <h3>🗺️ Available Routes in <?php echo htmlspecialchars($city_info['name']); ?></h3>
                <div class="routes-list">
                    <?php foreach ($routes as $route): ?>
                        <div class="route-item">
                            <div class="route-code"><?php echo htmlspecialchars($route['route_code']); ?></div>
                            <div class="route-name"><?php echo htmlspecialchars($route['route_name']); ?></div>
                            <div class="route-details">
                                📍 <?php echo htmlspecialchars($route['start_point']); ?> ➔ <?php echo htmlspecialchars($route['end_point']); ?> 
                                | 📏 <?php echo htmlspecialchars($route['distance_km']); ?> km 
                                | ⏱️ <?php echo htmlspecialchars($route['estimated_duration']); ?> min
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let simulationInterval = null;
        let isSimulationRunning = false;
        let map = null;
        let busMarkers = [];
        let ridershipChart = null;
        let scheduleChart = null;
        let currentTime = new Date();
        let optimizationActive = false;
        
        // City configuration from PHP
        const cityConfig = {
            name: '<?php echo addslashes($city_info['name']); ?>',
            center: [<?php echo $city_info['latitude']; ?>, <?php echo $city_info['longitude']; ?>],
            routes: <?php echo json_encode($routes); ?>
        };
        
        let buses = [];
        let passengerData = [];
        let alertsData = [];
        
        // Initialize the system
        document.addEventListener('DOMContentLoaded', function() {
            initializeMap();
            generateSampleData();
            setupEventListeners();
            initializeCharts();
            
            // Start with some sample alerts
            addAlert(`System ready for real-time optimization in ${cityConfig.name}`, 'info');
            addAlert('Historical data loaded: 30 days of ticket sales and GPS logs', 'success');
        });
        
        function initializeMap() {
            map = L.map('map').setView(cityConfig.center, 11);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);
        }
        
        function generateSampleData() {
            // Generate sample bus data based on actual routes
            buses = [];
            for (let i = 1; i <= 24; i++) {
                const randomRoute = cityConfig.routes[Math.floor(Math.random() * cityConfig.routes.length)];
                
                buses.push({
                    id: `BUS-${i.toString().padStart(3, '0')}`,
                    route: randomRoute.route_name,
                    route_code: randomRoute.route_code,
                    lat: cityConfig.center[0] + (Math.random() - 0.5) * 0.2,
                    lng: cityConfig.center[1] + (Math.random() - 0.5) * 0.2,
                    passengers: Math.floor(Math.random() * 45) + 5,
                    capacity: 50,
                    status: Math.random() > 0.8 ? 'delayed' : 'on-time',
                    delay: Math.random() > 0.8 ? Math.floor(Math.random() * 15) + 1 : 0,
                    speed: 15 + Math.random() * 25,
                    lastStop: `Stop-${Math.floor(Math.random() * 20) + 1}`
                });
            }
            
            // Generate passenger prediction data
            passengerData = [];
            for (let i = 0; i < 24; i++) {
                const hour = i;
                const basePassengers = getBasePassengerCount(hour);
                passengerData.push({
                    hour: hour,
                    predicted: Math.floor(basePassengers * (0.8 + Math.random() * 0.4)),
                    actual: Math.floor(basePassengers * (0.9 + Math.random() * 0.2))
                });
            }
        }
        
        function getBasePassengerCount(hour) {
            // Simulate realistic passenger patterns for Indian cities
            if (hour >= 7 && hour <= 9) return 800 + Math.random() * 400; // Morning rush
            if (hour >= 17 && hour <= 19) return 700 + Math.random() * 350; // Evening rush
            if (hour >= 10 && hour <= 16) return 300 + Math.random() * 200; // Mid-day
            return 50 + Math.random() * 100; // Off-peak
        }
        
        function setupEventListeners() {
            document.getElementById('startSimulation').addEventListener('click', startSimulation);
            document.getElementById('stopSimulation').addEventListener('click', stopSimulation);
            document.getElementById('optimizeSchedule').addEventListener('click', optimizeSchedule);
        }
        
        // Rest of the JavaScript functionality remains the same as the original
        // ... (continuing with the same functions as in the original HTML file)
        
        function startSimulation() {
            if (isSimulationRunning) return;
            
            isSimulationRunning = true;
            document.getElementById('startSimulation').style.display = 'none';
            document.getElementById('stopSimulation').style.display = 'inline-block';
            
            addAlert('Real-time simulation started', 'success');
            
            simulationInterval = setInterval(() => {
                updateBusPositions();
                updatePassengerCounts();
                updateMetrics();
                updateMap();
                updateCharts();
                
                // Random events
                if (Math.random() > 0.95) {
                    simulateRandomEvent();
                }
            }, 2000);
        }
        
        function stopSimulation() {
            if (!isSimulationRunning) return;
            
            isSimulationRunning = false;
            clearInterval(simulationInterval);
            
            document.getElementById('startSimulation').style.display = 'inline-block';
            document.getElementById('stopSimulation').style.display = 'none';
            
            addAlert('Simulation stopped', 'info');
        }
        
        function optimizeSchedule() {
            optimizationActive = true;
            addAlert('Starting ML-based schedule optimization...', 'info');
            
            setTimeout(() => {
                // Simulate optimization results
                buses.forEach(bus => {
                    if (Math.random() > 0.3) {
                        if (bus.status === 'delayed') {
                            bus.delay = Math.max(0, bus.delay - Math.floor(Math.random() * 5));
                            if (bus.delay <= 2) bus.status = 'on-time';
                        }
                        
                        if (bus.passengers < 15) {
                            bus.passengers += Math.floor(Math.random() * 10);
                        }
                    }
                });
                
                updateMetrics();
                addAlert(`Schedule optimization complete for ${cityConfig.name}! Reduced average delay by 35%`, 'success');
                
                // Update improvement metrics
                document.getElementById('waitTimeImprovement').textContent = '35%';
                document.getElementById('waitTimeImprovement').style.color = '#28a745';
                
                document.getElementById('utilizationImprovement').textContent = '+22%';
                document.getElementById('utilizationImprovement').style.color = '#28a745';
                
                document.getElementById('bunchingReduction').textContent = '45%';
                document.getElementById('bunchingReduction').style.color = '#28a745';
                
                document.getElementById('fuelSavings').textContent = '18%';
                document.getElementById('fuelSavings').style.color = '#28a745';
                
            }, 3000);
        }
        
        function updateBusPositions() {
            buses.forEach(bus => {
                bus.lat += (Math.random() - 0.5) * 0.001;
                bus.lng += (Math.random() - 0.5) * 0.001;
                
                const change = Math.floor((Math.random() - 0.5) * 8);
                bus.passengers = Math.max(0, Math.min(bus.capacity, bus.passengers + change));
            });
        }
        
        function updatePassengerCounts() {
            const currentHour = new Date().getHours();
            const currentData = passengerData[currentHour];
            if (currentData) {
                currentData.actual += Math.floor((Math.random() - 0.5) * 20);
                currentData.actual = Math.max(0, currentData.actual);
            }
        }
        
        function updateMetrics() {
            const totalPassengers = buses.reduce((sum, bus) => sum + bus.passengers, 0);
            const activeBuses = buses.filter(bus => bus.passengers > 0).length;
            const delayedBuses = buses.filter(bus => bus.status === 'delayed').length;
            const onTimePerformance = Math.round(((buses.length - delayedBuses) / buses.length) * 100);
            const avgWaitTime = buses.reduce((sum, bus) => sum + bus.delay, 0) / buses.length;
            
            document.getElementById('activeBuses').textContent = activeBuses;
            document.getElementById('totalPassengers').textContent = totalPassengers.toLocaleString();
            document.getElementById('avgWaitTime').textContent = avgWaitTime.toFixed(1);
            document.getElementById('onTimePerf').textContent = `${onTimePerformance}%`;
        }
        
        function simulateRandomEvent() {
            const events = [
                `Traffic congestion detected on ${cityConfig.routes[0]?.route_code || 'Route-1'}. Rerouting buses...`,
                'High demand surge detected. Dispatching additional buses.',
                'Bus breakdown reported. Sending replacement vehicle.',
                'Weather alert: Heavy rain expected. Adjusting schedules.',
                'Festival crowd detected. Increasing frequency on major routes.',
                'Peak hour extended due to IT sector shifts.'
            ];
            
            const eventTypes = ['warning', 'info', 'warning', 'warning', 'info', 'info'];
            const randomIndex = Math.floor(Math.random() * events.length);
            
            addAlert(events[randomIndex], eventTypes[randomIndex]);
        }
        
        function addAlert(message, type) {
            const alertsContainer = document.getElementById('alerts');
            const alert = document.createElement('div');
            alert.className = `alert ${type}`;
            alert.innerHTML = `
                <strong>${new Date().toLocaleTimeString()}</strong><br>
                ${message}
            `;
            
            alertsContainer.insertBefore(alert, alertsContainer.firstChild);
            
            while (alertsContainer.children.length > 10) {
                alertsContainer.removeChild(alertsContainer.lastChild);
            }
        }
        
        function updateMap() {
            busMarkers.forEach(marker => map.removeLayer(marker));
            busMarkers = [];
            
            buses.forEach(bus => {
                const color = bus.status === 'delayed' ? 'red' : 'green';
                const marker = L.circleMarker([bus.lat, bus.lng], {
                    color: color,
                    fillColor: color,
                    fillOpacity: 0.7,
                    radius: 8
                }).addTo(map);
                
                marker.bindPopup(`
                    <strong>${bus.id}</strong><br>
                    Route: ${bus.route_code}<br>
                    Passengers: ${bus.passengers}/${bus.capacity}<br>
                    Status: ${bus.status}<br>
                    ${bus.delay > 0 ? `Delay: ${bus.delay} min` : ''}
                `);
                
                busMarkers.push(marker);
            });
        }
        
        function initializeCharts() {
            // Ridership chart
            const ridershipCtx = document.getElementById('ridership-chart').getContext('2d');
            ridershipChart = new Chart(ridershipCtx, {
                type: 'line',
                data: {
                    labels: passengerData.map(d => `${d.hour}:00`),
                    datasets: [{
                        label: 'Predicted Ridership',
                        data: passengerData.map(d => d.predicted),
                        borderColor: 'rgb(102, 126, 234)',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4
                    }, {
                        label: 'Actual Ridership',
                        data: passengerData.map(d => d.actual),
                        borderColor: 'rgb(118, 75, 162)',
                        backgroundColor: 'rgba(118, 75, 162, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'top' }
                    },
                    scales: { y: { beginAtZero: true } }
                }
            });
            
            // Schedule performance chart
            const scheduleCtx = document.getElementById('schedule-chart').getContext('2d');
            scheduleChart = new Chart(scheduleCtx, {
                type: 'doughnut',
                data: {
                    labels: ['On Time', 'Delayed', 'Early'],
                    datasets: [{
                        data: [
                            buses.filter(b => b.status === 'on-time').length,
                            buses.filter(b => b.status === 'delayed').length,
                            buses.filter(b => b.status === 'early').length
                        ],
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.8)',
                            'rgba(220, 53, 69, 0.8)',
                            'rgba(255, 193, 7, 0.8)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        }
        
        function updateCharts() {
            if (ridershipChart) {
                ridershipChart.data.datasets[1].data = passengerData.map(d => d.actual);
                ridershipChart.update('none');
            }
            
            if (scheduleChart) {
                scheduleChart.data.datasets[0].data = [
                    buses.filter(b => b.status === 'on-time').length,
                    buses.filter(b => b.status === 'delayed').length,
                    buses.filter(b => b.status === 'early').length
                ];
                scheduleChart.update('none');
            }
        }
    </script>
</body>
</html>