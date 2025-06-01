<?php
session_start();
require_once 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

$error = '';
$restaurant_id = isset($_GET['restaurant_id']) ? (int)$_GET['restaurant_id'] : 0;

// Fetch restaurant details
$restaurant = null;
if ($restaurant_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE id = ?");
        $stmt->execute([$restaurant_id]);
        $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Error fetching restaurant: " . $e->getMessage();
    }
}

// Fetch all restaurants for the dropdown
try {
    $stmt = $pdo->query("SELECT id, name FROM restaurants ORDER BY name");
    $restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching restaurants: " . $e->getMessage();
}

// Fetch orders for the selected restaurant
$orders = [];
if ($restaurant_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT o.*, d.title as dish_title, d.price, oi.quantity,
                   u.name as customer_name, u.email as customer_email, 
                   u.phone as customer_phone
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            JOIN dishes d ON oi.dish_id = d.id
            JOIN users u ON o.user_id = u.id
            WHERE d.restaurant_id = ?
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$restaurant_id]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Error fetching orders: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Orders - FDelivery</title>
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
        .restaurant-select {
            padding: 0.5rem;
            border-radius: 0.5rem;
            border: 1px solid var(--grey-color);
            min-width: 200px;
        }
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2rem;
            background: var(--white-color);
            box-shadow: var(--box-shadow);
            border-radius: 0.5rem;
            overflow: hidden;
        }
        .orders-table th,
        .orders-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--grey-color);
        }
        .orders-table th {
            background: var(--grey-color-1);
            font-weight: 600;
        }
        .status {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-align: center;
            display: inline-block;
            min-width: 100px;
        }
        .pending { background: var(--yellow-color); }
        .delivered { background: var(--green-color); color: var(--white-color); }
        .cancelled { background: var(--primary-color); color: var(--white-color); }
        .stats {
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
            <div>
                <select class="restaurant-select" onchange="window.location.href='?restaurant_id='+this.value">
                    <option value="">Select Restaurant</option>
                    <?php foreach ($restaurants as $rest): ?>
                        <option value="<?php echo $rest['id']; ?>" <?php echo $restaurant_id == $rest['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($rest['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($restaurant): ?>
            <h2><?php echo htmlspecialchars($restaurant['name']); ?> - Orders</h2>
            
            <div class="stats">
                <div class="stat-card">
                    <h3>Total Orders</h3>
                    <p><?php echo count($orders); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Revenue</h3>
                    <p>$<?php 
                        $revenue = array_sum(array_map(function($order) {
                            return $order['price'] * $order['quantity'];
                        }, $orders));
                        echo number_format($revenue, 2);
                    ?></p>
                </div>
                <div class="stat-card">
                    <h3>Average Order Value</h3>
                    <p>$<?php 
                        $avg = $orders ? $revenue / count($orders) : 0;
                        echo number_format($avg, 2);
                    ?></p>
                </div>
            </div>

            <?php if ($orders): ?>
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Dish</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($order['customer_name']); ?>
                                </td>
                                <td>
                                    <small>
                                        <?php echo htmlspecialchars($order['customer_email']); ?><br>
                                        <?php echo htmlspecialchars($order['customer_phone']); ?>
                                    </small>
                                </td>
                                <td><?php echo htmlspecialchars($order['dish_title']); ?></td>
                                <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                                <td>$<?php echo number_format($order['price'] * $order['quantity'], 2); ?></td>
                                <td>
                                    <span class="status <?php echo strtolower($order['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y H:i', strtotime($order['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No orders found for this restaurant.</p>
            <?php endif; ?>
        <?php else: ?>
            <p>Please select a restaurant to view its orders.</p>
        <?php endif; ?>
    </div>
</body>
</html>
