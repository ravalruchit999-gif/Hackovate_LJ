<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
requireAdmin();

$current_user = getCurrentUser();
$database = new Database();
$db = $database->getConnection();

// Handle add / delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $stmt = $db->prepare("INSERT INTO users (username,email,password,full_name,role,city,is_active,created_at) 
                              VALUES (:username,:email,:password,:full_name,:role,:city,1,NOW())");
        $stmt->execute([
            ':username' => $_POST['username'],
            ':email' => $_POST['email'],
            ':password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
            ':full_name' => $_POST['full_name'],
            ':role' => $_POST['role'],
            ':city' => $_POST['city']
        ]);
    } elseif (isset($_POST['delete_user'])) {
    $user_id = $_POST['id'];

    // First delete related sessions
    $stmt = $db->prepare("DELETE FROM user_sessions WHERE user_id = :id");
    $stmt->execute([':id' => $user_id]);

    // Then delete related alerts (if any)
    $stmt = $db->prepare("DELETE FROM system_alerts WHERE created_by = :id");
    $stmt->execute([':id' => $user_id]);

    // Finally delete the user
    $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
}
}

// Filtering: all users or only admins
$where = "";
if (isset($_GET['filter']) && $_GET['filter'] === 'admins') {
    $where = "WHERE role = 'admin'";
}

$query = "SELECT * FROM users $where ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
include 'admin_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - <?php echo APP_NAME; ?></title>
</head>
<body>
            <h1><?php echo (isset($_GET['filter']) && $_GET['filter'] === 'admins') ? "Manage Administrators" : "Manage Users"; ?></h1>

        <!-- Add User Form -->
        <form method="POST">
            <h3>Add New User</h3>
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="full_name" placeholder="Full Name" required>
            <input type="password" name="password" placeholder="Password" required>
            <select name="role" required>
                <option value="user">User</option>
                <option value="admin">Administrator</option>
            </select>
            <input type="text" name="city" placeholder="City">
            <button type="submit" name="add_user">➕ Add User</button>
        </form>

        <!-- User Table -->
        <h3>User List</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>City</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo htmlspecialchars($user['id']); ?></td>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td>
                    <?php if ($user['role'] === 'admin'): ?>
                        <span class="badge-admin">Admin</span>
                    <?php else: ?>
                        <span class="badge-user">User</span>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($user['city']); ?></td>
                <td class="actions">
                    <?php if ($user['id'] != $current_user['id']): ?>
                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                            <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                            <button type="submit" name="delete_user">🗑️ Delete</button>
                        </form>
                        
                    <?php else: ?>
                        <em>(You)</em>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
            <tr><td colspan="7">No users found.</td></tr>
            
            <?php endif; ?>
            
        </table>
    </div>
</body>
</html>

<?php include 'admin_footer.php'; ?>