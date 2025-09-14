<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

// Handle Add / Update / Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_bus'])) {
        $stmt = $db->prepare("INSERT INTO buses (bus_number, route_id, capacity, status, driver_name, driver_phone) 
                              VALUES (:bus_number, :route_id, :capacity, :status, :driver_name, :driver_phone)");
        $stmt->execute([
            ':bus_number'   => $_POST['bus_number'],
            ':route_id'     => $_POST['route_id'],
            ':capacity'     => $_POST['capacity'],
            ':status'       => $_POST['status'],
            ':driver_name'  => $_POST['driver_name'],
            ':driver_phone' => $_POST['driver_phone']
        ]);
        logActivity($_SESSION['user_id'], "Bus Added", "Bus {$_POST['bus_number']} added.");
    } elseif (isset($_POST['delete_bus'])) {
        $stmt = $db->prepare("DELETE FROM buses WHERE id=:id");
        $stmt->execute([':id' => $_POST['id']]);
        logActivity($_SESSION['user_id'], "Bus Deleted", "Bus ID {$_POST['id']} deleted.");
    } elseif (isset($_POST['update_status'])) {
        $stmt = $db->prepare("UPDATE buses SET status=:status WHERE id=:id");
        $stmt->execute([':status' => $_POST['status'], ':id' => $_POST['id']]);
        logActivity($_SESSION['user_id'], "Bus Status Updated", "Bus ID {$_POST['id']} status changed.");
    }
}

// Fetch all buses
$query = "SELECT b.*, r.route_name, c.name as city_name 
          FROM buses b
          LEFT JOIN routes r ON b.route_id = r.id
          LEFT JOIN cities c ON r.city_id = c.id
          ORDER BY b.bus_number";
$stmt = $db->prepare($query);
$stmt->execute();
$buses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch routes for dropdown
$routes_stmt = $db->prepare("SELECT r.id, r.route_name, c.name as city_name FROM routes r 
                             JOIN cities c ON r.city_id=c.id WHERE r.is_active=1");
$routes_stmt->execute();
$routes = $routes_stmt->fetchAll(PDO::FETCH_ASSOC);

include 'admin_header.php';
?>

<div class="container">
    <h1>🚌 Fleet Management</h1>
    <p>Manage buses, add new ones, and update maintenance schedules.</p>

    <!-- Add Bus Form -->
    <div class="card">
        <h3>➕ Add New Bus</h3>
        <form method="POST">
            <div class="form-row">
                <input type="text" name="bus_number" placeholder="Bus Number" required>
                <select name="route_id" required>
                    <option value="">Select Route</option>
                    <?php foreach ($routes as $route): ?>
                        <option value="<?= $route['id'] ?>"><?= htmlspecialchars($route['route_name']) ?> (<?= htmlspecialchars($route['city_name']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="capacity" placeholder="Capacity" required>
            </div>
            <div class="form-row">
                <input type="text" name="driver_name" placeholder="Driver Name">
                <input type="text" name="driver_phone" placeholder="Driver Phone">
                <select name="status">
                    <option value="active">Active</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="delayed">Delayed</option>
                </select>
            </div>
            <button type="submit" name="add_bus" class="btn btn-success">Add Bus</button>
        </form>
    </div>

    <!-- Bus List -->
    <div class="card">
        <h3>📋 All Buses</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Bus Number</th>
                    <th>Route</th>
                    <th>City</th>
                    <th>Capacity</th>
                    <th>Status</th>
                    <th>Driver</th>
                    <th>Last Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($buses as $bus): ?>
                    <tr>
                        <td><?= $bus['id'] ?></td>
                        <td><?= htmlspecialchars($bus['bus_number']) ?></td>
                        <td><?= htmlspecialchars($bus['route_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($bus['city_name'] ?? '-') ?></td>
                        <td><?= $bus['capacity'] ?></td>
                        <td>
                            <form method="POST" style="display:inline-block;">
                                <input type="hidden" name="id" value="<?= $bus['id'] ?>">
                                <select name="status" onchange="this.form.submit()">
                                    <option value="active" <?= $bus['status']=='active'?'selected':'' ?>>Active</option>
                                    <option value="maintenance" <?= $bus['status']=='maintenance'?'selected':'' ?>>Maintenance</option>
                                    <option value="delayed" <?= $bus['status']=='delayed'?'selected':'' ?>>Delayed</option>
                                </select>
                                <input type="hidden" name="update_status" value="1">
                            </form>
                        </td>
                        <td><?= htmlspecialchars($bus['driver_name']) ?><br><?= htmlspecialchars($bus['driver_phone']) ?></td>
                        <td><?= $bus['last_updated'] ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Delete this bus?')">
                                <input type="hidden" name="id" value="<?= $bus['id'] ?>">
                                <button type="submit" name="delete_bus" class="btn btn-danger">❌ Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($buses)): ?>
                    <tr><td colspan="9">No buses found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'admin_footer.php'; ?>
