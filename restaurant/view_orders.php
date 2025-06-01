<?php
session_start();
require_once '../admin/config.php';

// Check if restaurant admin is logged in
if (!isset($_SESSION['restaurant_admin_id'])) {
    header('Location: login.php');
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'];
$error = '';

// Fetch all orders for the restaurant
try {
    $stmt = $pdo->prepare("
        SELECT o.*, d.title as dish_title, d.price, oi.quantity,
               u.name as customer_name, u.email as customer_email,
               u.phone as customer_phone, u.address as customer_address,
               CASE 
                   WHEN o.status = 'picking' THEN 'Picking up your order'
                   ELSE o.status
               END as display_status
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

// Handle order status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $order_id]);
        header('Location: view_orders.php');
        exit();
    } catch (PDOException $e) {
        $error = "Error updating order status: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Orders - Restaurant Admin</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <style>
        .orders-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
        }
        .order-card {
            background: var(--white-color);
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .order-info {
            display: flex;
            gap: 1rem;
        }
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 1rem;
            border-bottom: 1px solid var(--grey-color);
        }
        .status-select {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            border: 1px solid var(--grey-color);
        }
        .action-group {
            display: flex;
            gap: 0.5rem;
        }
        .back-btn {
            background: var(--grey-color-1);
            color: var(--black-color);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
        }
        .status {
            padding: 0.4rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            min-width: 100px;
            text-align: center;
        }

        .pending {
            background: var(--black-color);
            color: var(--white-color);
        }

        .delivered {
            background: var(--black-color);
            color: var(--white-color);
        }

        .cancelled {
            background: var(--black-color);
            color: var(--white-color);
        }
        .out_for_delivery {
            background: var(--black-color);
            color: var(--white-color);
        }
    </style>
</head>
<body>
    <div class="orders-container">
        <div class="order-header">
            <h2>Order Management</h2>
            <a href="dashboard.php" class="back-btn">Back to Dashboard</a>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php foreach ($orders as $order): ?>
            <div class="order-card">
                <div class="order-header">
                    <div>
                        <h3>Order #<?php echo $order['id']; ?></h3>
                        <p>Placed on: <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
                    </div>
                    <div class="action-group">
                        <form method="POST" style="display: inline-block;">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <select name="status" class="status-select" onchange="this.form.submit()">
                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $order['status'] === 'confirmed' ? 'selected' : ''; ?>>send to delivery boy</option>
                                <option value="out_for_delivery" <?php echo $order['status'] === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                                <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </form>
                    </div>
                </div>

                <div class="order-info">
                    <div>
                        <h4>Customer Info:</h4>
                        <p>Name: <?php echo htmlspecialchars($order['customer_name']); ?></p>
                        <p>Email: <?php echo htmlspecialchars($order['customer_email']); ?></p>
                        <p>Phone: <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                        <p>Address: <?php echo htmlspecialchars($order['customer_address']); ?></p>
                    </div>
                    <div>
                        <h4>Order Details:</h4>
                        <p>Dish: <?php echo htmlspecialchars($order['dish_title']); ?></p>
                        <p>Quantity: <?php echo $order['quantity']; ?></p>
                        <p>Price: $<?php echo number_format($order['price'], 2); ?></p>
                        <p>Total: $<?php echo number_format($order['price'] * $order['quantity'], 2); ?></p>
                    </div>
                </div>
                <div> <h1> -</h1></div>
                <h4>Order Details:
                        <span class="status <?php echo htmlspecialchars($order['status']); ?>">
                <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
            </span></h4>
            </div>
            
        <?php endforeach; ?>

        <?php if (empty($orders)): ?>
            <div class="no-orders">
                <p>No orders found.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
