<?php
session_start();
require_once 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

$error = '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Prepare the base query
$query = "SELECT u.*, 
          COUNT(DISTINCT o.id) as total_orders,
          SUM(o.total_amount) as total_spent,
          MAX(o.created_at) as last_order_date
          FROM users u
          LEFT JOIN orders o ON u.id = o.user_id";

// Add search condition if search term exists
if ($search) {
    $query .= " WHERE u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?";
}

// Add filter conditions
switch ($filter) {
    case 'recent':
        $query .= $search ? " AND" : " WHERE";
        $query .= " u.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
    case 'active':
        $query .= $search ? " AND" : " WHERE";
        $query .= " o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
    case 'inactive':
        $query .= $search ? " AND" : " WHERE";
        $query .= " (o.created_at IS NULL OR o.created_at < DATE_SUB(NOW(), INTERVAL 90 DAY))";
        break;
}

$query .= " GROUP BY u.id ORDER BY u.created_at DESC";

try {
    $stmt = $pdo->prepare($query);
    
    if ($search) {
        $searchParam = "%$search%";
        $stmt->execute([$searchParam, $searchParam, $searchParam]);
    } else {
        $stmt->execute();
    }
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Users - FDelivery Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 0 10px;
            border-radius: 10px;
        }
        .search-filter {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .search-filter input,
        .search-filter select {
            padding: 0.5rem;
            border: 1px solid var(--grey-color);
            border-radius: 0.5rem;
        }
        .users-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--white-color);
            box-shadow: var(--box-shadow);
            border-radius: 0.5rem;
            overflow: hidden;
        }
        .users-table th,
        .users-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--grey-color);
        }
        .users-table th {
            background: var(--grey-color-1);
            font-weight: 600;
        }
        .user-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: var(--white-color);
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: var(--box-shadow);
        }
        .stat-card h3 {
            margin: 0 0 0.5rem 0;
            color: var(--grey-color-2);
        }
        .stat-card p {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        .back-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            background: var(--grey-color-1);
            color: var(--black-color);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .view-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            background: var(--primary-color);
            color: var(--white-color);
            text-decoration: none;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="header">
            <div>
                <a href="index.php" class="back-btn">
                    <i class="bx bx-arrow-back"></i> Back to Dashboard
                </a>
            </div>
            <h2>Customer Management</h2>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="user-stats">
            <div class="stat-card">
                <h3>Total Customers</h3>
                <p><?php echo count($users); ?></p>
            </div>
            <div class="stat-card">
                <h3>Active Customers</h3>
                <p><?php 
                    echo count(array_filter($users, function($user) {
                        return !empty($user['last_order_date']) && 
                               strtotime($user['last_order_date']) >= strtotime('-30 days');
                    }));
                ?></p>
            </div>
            <div class="stat-card">
                <h3>New This Month</h3>
                <p><?php 
                    echo count(array_filter($users, function($user) {
                        return strtotime($user['created_at']) >= strtotime('-30 days');
                    }));
                ?></p>
            </div>
        </div>

        <div class="search-filter">
            <input type="text" 
                   placeholder="Search by name, email or phone" 
                   value="<?php echo htmlspecialchars($search); ?>"
                   onchange="window.location.href='?search='+this.value+'&filter=<?php echo $filter; ?>'">
            
            <select onchange="window.location.href='?search=<?php echo urlencode($search); ?>&filter='+this.value">
                <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>All Customers</option>
                <option value="recent" <?php echo $filter == 'recent' ? 'selected' : ''; ?>>New Customers (30 days)</option>
                <option value="active" <?php echo $filter == 'active' ? 'selected' : ''; ?>>Active Customers</option>
                <option value="inactive" <?php echo $filter == 'inactive' ? 'selected' : ''; ?>>Inactive Customers</option>
            </select>
        </div>

        <table class="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Total Orders</th>
                    <th>Total Spent</th>
                    <th>Last Order</th>
                    <th>Joined Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td>#<?php echo htmlspecialchars($user['id']); ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone']); ?></td>
                        <td><?php echo $user['total_orders']; ?></td>
                        <td>$<?php echo number_format($user['total_spent'] ?? 0, 2); ?></td>
                        <td>
                            <?php 
                                echo $user['last_order_date'] 
                                    ? date('M j, Y', strtotime($user['last_order_date']))
                                    : 'Never';
                            ?>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <a href="view_user_details.php?id=<?php echo $user['id']; ?>" 
                               class="view-btn">View Details</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
