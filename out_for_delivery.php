<?php
require_once '../admin/config.php';

// Fetch all orders that are out for delivery
try {
    $stmt = $pdo->prepare("
        SELECT o.*, d.title as dish_title, d.price, oi.quantity,
               u.name as customer_name, u.email as customer_email,
               u.phone as customer_phone, u.address as customer_address
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN dishes d ON oi.dish_id = d.id
        JOIN users u ON o.user_id = u.id
        WHERE o.status = 'out_for_delivery'
        ORDER BY o.created_at DESC
    ");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching orders: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Out for Delivery Orders - Delivery Person</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #337ab7;
            --hover-color: #23527c;
            --bg-color: #f5f5f5;
            --text-color: #333;
            --white-color: #fff;
            --grey-color: #888;
            --grey-color-1: #f8f9fa;
            --grey-color-2: #666;
            --black-color: #000;
            --green-color: #28a745;
            --yellow-color: #ffc107;
            --red-color: #dc3545;
            --box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .delivery-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
        }

        .delivery-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
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

        .order-actions {
            display: flex;
            gap: 1rem;
        }

        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .accept-btn {
            background: var(--green-color);
            color: var(--white-color);
        }

        .accept-btn:hover {
            background: #218838;
        }

        .delivered-btn {
            background: var(--primary-color);
            color: var(--white-color);
        }

        .delivered-btn:hover {
            background: var(--hover-color);
        }

        .status {
            padding: 0.4rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            min-width: 120px;
            text-align: center;
            background: var(--yellow-color);
            color: var(--black-color);
        }

        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem;
            border-bottom: 1px solid var(--grey-color);
        }

        .back-btn {
            background: var(--grey-color-1);
            color: var(--black-color);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .back-btn:hover {
            background: var(--grey-color);
        }
    </style>
</head>
<body>
    <div class="delivery-container">
        <div class="delivery-header">
            <h2>Out for Delivery Orders</h2>
            <a href="dashboard.php" class="back-btn">Back to Dashboard</a>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <div class="no-orders">
                <p>No orders are currently out for delivery.</p>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <h3>Order #<?php echo $order['id']; ?></h3>
                            <p>Placed on: <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
                        </div>
                        <span class="status">
                            <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                        </span>
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
                            <div class="order-item">
                                <span>Dish:</span>
                                <span><?php echo htmlspecialchars($order['dish_title']); ?></span>
                            </div>
                            <div class="order-item">
                                <span>Quantity:</span>
                                <span><?php echo $order['quantity']; ?></span>
                            </div>
                            <div class="order-item">
                                <span>Price:</span>
                                <span>$<?php echo number_format($order['price'], 2); ?></span>
                            </div>
                            <div class="order-item">
                                <span>Total:</span>
                                <span>$<?php echo number_format($order['price'] * $order['quantity'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
