<?php
// api/buses.php - Bus management API endpoints

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
// Set JSON response header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Require login for all API calls
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$request = $_GET['request'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGet($db, $request);
            break;
        case 'POST':
            handlePost($db, $request);
            break;
        case 'PUT':
            handlePut($db, $request);
            break;
        case 'DELETE':
            handleDelete($db, $request);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error', 'message' => $e->getMessage()]);
}

function handleGet($db, $request) {
    switch ($request) {
        case 'all':
            getAllBuses($db);
            break;
        case 'active':
            getActiveBuses($db);
            break;
        case 'by-route':
            getBusesByRoute($db, $_GET['route_id'] ?? null);
            break;
        case 'by-city':
            getBusesByCity($db, $_GET['city_code'] ?? null);
            break;
        case 'live-tracking':
            getLiveTracking($db);
            break;
        case 'stats':
            getBusStats($db);
            break;
        default:
            if (is_numeric($request)) {
                getBusById($db, $request);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid request']);
            }
    }
}

function handlePost($db, $request) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($request) {
        case 'add':
            addBus($db, $input);
            break;
        case 'update-location':
            updateBusLocation($db, $input);
            break;
        case 'update-passenger-count':
            updatePassengerCount($db, $input);
            break;
        case 'generate-sample':
            generateSampleBuses($db, $input);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request']);
    }
}

function handlePut($db, $request) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (is_numeric($request)) {
        updateBus($db, $request, $input);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid bus ID']);
    }
}

function handleDelete($db, $request) {
    // Only admins can delete buses
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        return;
    }
    
    if (is_numeric($request)) {
        deleteBus($db, $request);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid bus ID']);
    }
}

function getAllBuses($db) {
    $query = "SELECT b.*, r.route_name, r.route_code, c.name as city_name 
              FROM buses b 
              LEFT JOIN routes r ON b.route_id = r.id 
              LEFT JOIN cities c ON r.city_id = c.id 
              ORDER BY b.bus_number";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $buses]);
}

function getActiveBuses($db) {
    $query = "SELECT b.*, r.route_name, r.route_code, c.name as city_name 
              FROM buses b 
              LEFT JOIN routes r ON b.route_id = r.id 
              LEFT JOIN cities c ON r.city_id = c.id 
              WHERE b.status = 'active' 
              ORDER BY b.last_updated DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $buses]);
}

function getBusesByRoute($db, $route_id) {
    if (!$route_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Route ID required']);
        return;
    }
    
    $query = "SELECT b.*, r.route_name, r.route_code 
              FROM buses b 
              LEFT JOIN routes r ON b.route_id = r.id 
              WHERE b.route_id = :route_id 
              ORDER BY b.bus_number";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':route_id', $route_id);
    $stmt->execute();
    $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $buses]);
}

function getBusesByCity($db, $city_code) {
    if (!$city_code) {
        http_response_code(400);
        echo json_encode(['error' => 'City code required']);
        return;
    }
    
    $query = "SELECT b.*, r.route_name, r.route_code, c.name as city_name 
              FROM buses b 
              LEFT JOIN routes r ON b.route_id = r.id 
              LEFT JOIN cities c ON r.city_id = c.id 
              WHERE c.code = :city_code 
              ORDER BY b.bus_number";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':city_code', $city_code);
    $stmt->execute();
    $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $buses]);
}

function getLiveTracking($db) {
    $query = "SELECT b.id, b.bus_number, b.current_latitude as lat, b.current_longitude as lng,
                     b.current_passengers, b.capacity, b.status, b.speed_kmh,
                     r.route_name, r.route_code, b.last_updated
              FROM buses b 
              LEFT JOIN routes r ON b.route_id = r.id 
              WHERE b.current_latitude IS NOT NULL 
              AND b.current_longitude IS NOT NULL 
              AND b.status = 'active'
              ORDER BY b.last_updated DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $buses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $buses]);
}

function getBusStats($db) {
    $stats_query = "
        SELECT 
            COUNT(*) as total_buses,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_buses,
            COUNT(CASE WHEN status = 'maintenance' THEN 1 END) as maintenance_buses,
            COUNT(CASE WHEN status = 'delayed' THEN 1 END) as delayed_buses,
            AVG(current_passengers) as avg_passengers,
            SUM(current_passengers) as total_passengers,
            AVG(capacity) as avg_capacity
        FROM buses
    ";
    
    $stmt = $db->prepare($stats_query);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate utilization percentage
    $stats['utilization_percentage'] = $stats['avg_capacity'] > 0 ? 
        round(($stats['avg_passengers'] / $stats['avg_capacity']) * 100, 2) : 0;
    
    echo json_encode(['success' => true, 'data' => $stats]);
}

function getBusById($db, $bus_id) {
    $query = "SELECT b.*, r.route_name, r.route_code, c.name as city_name 
              FROM buses b 
              LEFT JOIN routes r ON b.route_id = r.id 
              LEFT JOIN cities c ON r.city_id = c.id 
              WHERE b.id = :bus_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':bus_id', $bus_id);
    $stmt->execute();
    $bus = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($bus) {
        echo json_encode(['success' => true, 'data' => $bus]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Bus not found']);
    }
}

function addBus($db, $data) {
    // Only admins can add buses
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        return;
    }
    
    $required_fields = ['bus_number', 'route_id', 'capacity'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Field '$field' is required"]);
            return;
        }
    }
    
    // Check if bus number already exists
    $check_query = "SELECT id FROM buses WHERE bus_number = :bus_number";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':bus_number', $data['bus_number']);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Bus number already exists']);
        return;
    }
    
    $query = "INSERT INTO buses (bus_number, route_id, capacity, current_passengers, 
                                status, driver_name, driver_phone) 
              VALUES (:bus_number, :route_id, :capacity, :current_passengers, 
                      :status, :driver_name, :driver_phone)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':bus_number', $data['bus_number']);
    $stmt->bindParam(':route_id', $data['route_id']);
    $stmt->bindParam(':capacity', $data['capacity']);
    $stmt->bindParam(':current_passengers', $data['current_passengers'] ?? 0);
    $stmt->bindParam(':status', $data['status'] ?? 'active');
    $stmt->bindParam(':driver_name', $data['driver_name'] ?? '');
    $stmt->bindParam(':driver_phone', $data['driver_phone'] ?? '');
    
    if ($stmt->execute()) {
        $bus_id = $db->lastInsertId();
        logActivity($_SESSION['user_id'], 'Bus added', "Bus number: {$data['bus_number']}");
        echo json_encode(['success' => true, 'data' => ['id' => $bus_id, 'message' => 'Bus added successfully']]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add bus']);
    }
}

function updateBus($db, $bus_id, $data) {
    // Only admins can update buses
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        return;
    }
    
    $allowed_fields = ['bus_number', 'route_id', 'capacity', 'current_passengers', 
                      'status', 'driver_name', 'driver_phone'];
    $update_fields = [];
    $params = [':id' => $bus_id];
    
    foreach ($allowed_fields as $field) {
        if (isset($data[$field])) {
            $update_fields[] = "$field = :$field";
            $params[":$field"] = $data[$field];
        }
    }
    
    if (empty($update_fields)) {
        http_response_code(400);
        echo json_encode(['error' => 'No valid fields to update']);
        return;
    }
    
    $query = "UPDATE buses SET " . implode(', ', $update_fields) . ", last_updated = NOW() WHERE id = :id";
    $stmt = $db->prepare($query);
    
    if ($stmt->execute($params)) {
        logActivity($_SESSION['user_id'], 'Bus updated', "Bus ID: $bus_id");
        echo json_encode(['success' => true, 'message' => 'Bus updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update bus']);
    }
}

function updateBusLocation($db, $data) {
    $required_fields = ['bus_id', 'latitude', 'longitude'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Field '$field' is required"]);
            return;
        }
    }
    
    $query = "UPDATE buses SET current_latitude = :latitude, current_longitude = :longitude, 
                               speed_kmh = :speed, last_updated = NOW() 
              WHERE id = :bus_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':latitude', $data['latitude']);
    $stmt->bindParam(':longitude', $data['longitude']);
    $stmt->bindParam(':speed', $data['speed'] ?? 0);
    $stmt->bindParam(':bus_id', $data['bus_id']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Bus location updated']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update bus location']);
    }
}

function updatePassengerCount($db, $data) {
    $required_fields = ['bus_id', 'passenger_count'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Field '$field' is required"]);
            return;
        }
    }
    
    $query = "UPDATE buses SET current_passengers = :passenger_count, last_updated = NOW() 
              WHERE id = :bus_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':passenger_count', $data['passenger_count']);
    $stmt->bindParam(':bus_id', $data['bus_id']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Passenger count updated']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update passenger count']);
    }
}

function generateSampleBuses($db, $data) {
    // Only admins can generate sample data
    if (!isAdmin()) {
        http_response_code(403);
        echo json_encode(['error' => 'Admin access required']);
        return;
    }
    
    $city_code = $data['city_code'] ?? 'bangalore';
    $count = min($data['count'] ?? 10, 50); // Limit to 50 buses max
    
    // Get city info
    $city_query = "SELECT * FROM cities WHERE code = :city_code";
    $city_stmt = $db->prepare($city_query);
    $city_stmt->bindParam(':city_code', $city_code);
    $city_stmt->execute();
    $city = $city_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$city) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid city code']);
        return;
    }
    
    // Get routes for the city
    $routes_query = "SELECT * FROM routes WHERE city_id = :city_id AND is_active = 1";
    $routes_stmt = $db->prepare($routes_query);
    $routes_stmt->bindParam(':city_id', $city['id']);
    $routes_stmt->execute();
    $routes = $routes_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($routes)) {
        http_response_code(400);
        echo json_encode(['error' => 'No active routes found for this city']);
        return;
    }
    
    $generated = 0;
    $errors = [];
    
    for ($i = 1; $i <= $count; $i++) {
        $bus_number = strtoupper($city_code) . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);
        $route = $routes[array_rand($routes)];
        
        // Check if bus already exists
        $check_query = "SELECT id FROM buses WHERE bus_number = :bus_number";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':bus_number', $bus_number);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $errors[] = "Bus $bus_number already exists";
            continue;
        }
        
        // Generate random location within city bounds
        $lat = $city['latitude'] + (mt_rand(-100, 100) / 1000);
        $lng = $city['longitude'] + (mt_rand(-100, 100) / 1000);
        $passengers = mt_rand(0, 45);
        $capacity = 50;
        $speed = mt_rand(10, 50);
        $statuses = ['active', 'active', 'active', 'delayed']; // 75% active, 25% delayed
        $status = $statuses[array_rand($statuses)];
        
        $insert_query = "INSERT INTO buses (bus_number, route_id, capacity, current_passengers, 
                                          current_latitude, current_longitude, speed_kmh, status, 
                                          driver_name, driver_phone, last_updated) 
                        VALUES (:bus_number, :route_id, :capacity, :current_passengers, 
                               :latitude, :longitude, :speed, :status, 
                               :driver_name, :driver_phone, NOW())";
        
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->bindParam(':bus_number', $bus_number);
        $insert_stmt->bindParam(':route_id', $route['id']);
        $insert_stmt->bindParam(':capacity', $capacity);
        $insert_stmt->bindParam(':current_passengers', $passengers);
        $insert_stmt->bindParam(':latitude', $lat);
        $insert_stmt->bindParam(':longitude', $lng);
        $insert_stmt->bindParam(':speed', $speed);
        $insert_stmt->bindParam(':status', $status);
        
        $driver_name = "Driver " . $i;
        $driver_phone = "9" . str_pad(mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT);
        $insert_stmt->bindParam(':driver_name', $driver_name);
        $insert_stmt->bindParam(':driver_phone', $driver_phone);
        
        if ($insert_stmt->execute()) {
            $generated++;
        } else {
            $errors[] = "Failed to create bus $bus_number";
        }
    }
    
    logActivity($_SESSION['user_id'], 'Sample buses generated', "Generated $generated buses for $city_code");
    
    $response = [
        'success' => true,
        'message' => "Generated $generated sample buses",
        'generated_count' => $generated,
        'requested_count' => $count
    ];
    
    if (!empty($errors)) {
        $response['errors'] = $errors;
    }
    
    echo json_encode($response);
}

function deleteBus($db, $bus_id) {
    // Check if bus exists
    $check_query = "SELECT bus_number FROM buses WHERE id = :bus_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':bus_id', $bus_id);
    $check_stmt->execute();
    $bus = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$bus) {
        http_response_code(404);
        echo json_encode(['error' => 'Bus not found']);
        return;
    }
    
    // Delete the bus
    $query = "DELETE FROM buses WHERE id = :bus_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':bus_id', $bus_id);
    
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], 'Bus deleted', "Bus: {$bus['bus_number']}");
        echo json_encode(['success' => true, 'message' => 'Bus deleted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete bus']);
    }
}

// Utility function to validate coordinates
function validateCoordinates($lat, $lng) {
    return is_numeric($lat) && is_numeric($lng) && 
           $lat >= -90 && $lat <= 90 && 
           $lng >= -180 && $lng <= 180;
}

// Utility function to calculate distance between two points
function calculateDistance($lat1, $lng1, $lat2, $lng2) {
    $earth_radius = 6371; // Earth's radius in kilometers
    
    $lat1 = deg2rad($lat1);
    $lng1 = deg2rad($lng1);
    $lat2 = deg2rad($lat2);
    $lng2 = deg2rad($lng2);
    
    $delta_lat = $lat2 - $lat1;
    $delta_lng = $lng2 - $lng1;
    
    $a = sin($delta_lat/2) * sin($delta_lat/2) + 
         cos($lat1) * cos($lat2) * sin($delta_lng/2) * sin($delta_lng/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earth_radius * $c;
}
?>