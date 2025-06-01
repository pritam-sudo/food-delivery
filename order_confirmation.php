<?php
session_start();
require_once 'admin/config.php';

// Get order ID from URL
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Verify order belongs to current user
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT COUNT(*) as order_count FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result['order_count'] == 0) {
    header('Location: dishes.php');
    exit();
}

// Fetch order details with status history
$stmt = $pdo->prepare("
    SELECT 
        o.*, 
        d.title, 
        d.price, 
        r.name as restaurant_name, 
        u.name as customer_name, 
        u.phone as customer_phone, 
        u.address as customer_address,
        GROUP_CONCAT(oh.status ORDER BY oh.updated_at SEPARATOR ' → ') as status_history
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    LEFT JOIN dishes d ON oi.dish_id = d.id 
    LEFT JOIN restaurants r ON d.restaurant_id = r.id 
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN order_history oh ON o.id = oh.order_id
    WHERE o.id = ?
    GROUP BY o.id");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: dishes.php');
    exit();
}

// Update order status to confirmed
if ($order['status'] === 'pending') {
    $stmt = $pdo->prepare("UPDATE orders SET status = 'confirmed' WHERE id = ?");
    $stmt->execute([$order_id]);

    // Record status change in history
    $stmt = $pdo->prepare("INSERT INTO order_history (order_id, status, notes, updated_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$order_id, 'confirmed', 'Order confirmed by customer', 'system']);

    // Set success message for profile page
    $_SESSION['success'] = 'Your order has been confirmed successfully!';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed - FDelivery</title>
    <link rel="stylesheet" href="./css/styles.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: var(--white-color);
            border-radius: 1rem;
            box-shadow: var(--box-shadow);
        }
        .confirmation-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .confirmation-header h2 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        .order-details {
            margin-bottom: 2rem;
        }
        .order-details h3 {
            margin-bottom: 1rem;
            color: var(--black-color);
        }
        .order-item {
            padding: 1rem;
            background: var(--grey-color-1);
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .order-item .title {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .order-summary {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1rem;
            margin-top: 2rem;
        }
        .order-summary div {
            padding: 1rem;
            background: var(--grey-color-1);
            border-radius: 0.5rem;
        }
        .order-summary h4 {
            margin-bottom: 0.5rem;
            color: var(--black-color);
        }
        .order-actions {
            text-align: center;
            margin-top: 2rem;
        }
        .order-actions button {
            background: var(--primary-color);
            color: var(--white-color);
            padding: 0.8rem 2rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .order-actions button:hover {
            background-color: #e3342f;
        }
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
        }
        .confirmed {
            background: var(--green-color);
            color: var(--white-color);
        }
        .pending {
            background: var(--yellow-color);
            color: var(--black-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="confirmation-container">
            <div class="confirmation-header">
                <h2>Order Confirmed!</h2>
                <p>Your order has been placed successfully.</p>
            </div>

            <div class="order-details">
                <div class="order-details">
                    <h3>Order Details</h3>
                    <div class="order-item">
                        <div class="title"><?php echo htmlspecialchars($order['title']); ?></div>
                        <div class="quantity">Quantity: <?php echo $order['quantity']; ?></div>
                        <div class="price">Price: $<?php echo number_format($order['price'], 2); ?></div>
                        <div class="total">Total: $<?php echo number_format($order['total_amount'], 2); ?></div>
                    </div>

                    <div class="order-summary">
                        <div>
                            <h4>Order Information</h4>
                            <p>Order ID: <?php echo $order_id; ?></p>
                            <p>Restaurant: <?php echo htmlspecialchars($order['restaurant_name']); ?></p>
                            <p>Status: <span class="status-badge <?php echo htmlspecialchars($order['status']); ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span></p>
                            <p>Status History: <?php echo htmlspecialchars($order['status_history']); ?></p>
                        </div>
                        <div>
                            <h4>Delivery Information</h4>
                            <p>Name: <?php echo htmlspecialchars($order['customer_name']); ?></p>
                            <p>Phone: <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                            <p>Address: <?php echo htmlspecialchars($order['customer_address']); ?></p>
                        </div>
                        <div>
                            <h4>Order Actions</h4>
                            <?php if ($order['status'] === 'pending'): ?>
                                <p>Your order is being processed.</p>
                            <?php else: ?>
                                <p>Order confirmed successfully!</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="order-actions">
                        <button onclick="window.print()" class="btn">Print Receipt</button>
                        <a href="order_history.php?order_id=<?php echo $order_id; ?>" class="btn">View Order History</a>
                        <a href="dishes.php" class="btn">Continue Order</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
