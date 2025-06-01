<?php
session_start();
require_once 'admin/config.php';

// Check if delivery person is logged in
if (!isset($_SESSION['delivery_person_id'])) {
    header('Location: delivery_person/login.php');
    exit();
}

$delivery_person_name = $_SESSION['delivery_person_name'] ?? 'Delivery Person';

$error = '';
$success = '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];
    $action = $_POST['action'];
    
    try {
        // Map the action to correct status value
        $status = $action === 'confirmed' ? 'confirmed' : 'delivered';
        
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $order_id]);
        $success = "Order status updated successfully";
    } catch (PDOException $e) {
        $error = "Error updating order status: " . $e->getMessage();
    }
}

// Fetch all orders that are either out for delivery or confirmed
try {
    $stmt = $pdo->prepare("
        SELECT o.*, d.title as dish_title, d.price, oi.quantity,
               u.name as customer_name, u.email as customer_email,
               u.phone as customer_phone, u.address as customer_address,
               r.name as restaurant_name
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN dishes d ON oi.dish_id = d.id
        JOIN users u ON o.user_id = u.id
        JOIN restaurants r ON d.restaurant_id = r.id
        WHERE o.status IN ('out_for_delivery', 'confirmed')
        ORDER BY o.status, o.created_at DESC
    ");
    $stmt->execute();
    $all_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Separate orders by status
    $out_for_delivery = array_filter($all_orders, function($order) {
        return $order['status'] === 'out_for_delivery';
    });
    
    $confirmed = array_filter($all_orders, function($order) {
        return $order['status'] === 'confirmed';
    });
} catch (PDOException $e) {
    $error = "Error fetching orders: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Out for Delivery Orders - Food Delivery</title>
    <link rel="stylesheet" href="css/styles.css">
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

        .orders-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
        }

        .section {
            margin-bottom: 3rem;
        }

        .section h2 {
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--grey-color);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 0 10px;
            border-radius: 10px;
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

        .delivered-btn {
            background: var(--primary-color);
            color: var(--white-color);
        }

        .delivered-btn:hover {
            background: var(--hover-color);
        }

        .confirm-btn {
            background: var(--green-color);
            color: var(--white-color);
        }

        .confirm-btn:hover {
            background: #218838;
        }

        .status {
            padding: 0.4rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            min-width: 120px;
            text-align: center;
        }

        .confirmed {
            background: var(--green-color);
            color: var(--white-color);
        }

        .delivered {
            background: var(--primary-color);
            color: var(--white-color);
        }
        .out_for_delivery {
            background: var(--black-color);
            color: var(--white-color);
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

        .message {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .message.error {
            background: var(--red-color);
            color: var(--white-color);
        }

        .message.success {
            background: var(--green-color);
            color: var(--white-color);
        }
    </style>
</head>
<body>
    <div class="orders-container">
    <div class="header">
            <div class="title">
                <h2><i class='bx bx-truck'></i> Delivery Boy</h2>
                <p>Welcome, <?php echo htmlspecialchars($delivery_person_name); ?>!</p>
            </div>
            <div class="actions">
                <a href="accept_order.php" class="btn">Accept Orders</a>
                <a href="delivery_person/logout.php" class="btn">Logout</a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="message error">
                <i class='bx bx-error'></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="message success">
                <i class='bx bx-check-circle'></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- <div class="status-info">
            <p><strong>Status Flow:</strong> Confirm Order → Mark as Delivered</p>
            <p class="note">Note: After confirming an order, it will be marked as 'confirmed' in the system.</p>
        </div> -->

        <?php if (empty($out_for_delivery) && empty($confirmed)): ?>
            <div class="no-orders">
                <p>No orders are currently out for delivery or confirmed.</p>
            </div>
        <?php else: ?>
            <!-- All Order Ready for Delivery -->
            <?php if (!empty($out_for_delivery)): ?>
                <div class="section">
                    <h2>All Order Ready for Delivery</h2>
                    <?php foreach ($out_for_delivery as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div>
                                    <h3>Order #<?php echo $order['id']; ?></h3>
                                    <p>Placed on: <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
                                </div>
                                <span class="status <?php echo htmlspecialchars($order['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                </span>
                            </div>

                            <div class="order-info">
                                <div>
                                    <h4>Restaurant:</h4>
                                    <p><?php echo htmlspecialchars($order['restaurant_name']); ?></p>
                                </div>
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

                            <div class="order-actions">
                                <form method="POST" style="display: inline-block;" class="confirm-form">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <input type="hidden" name="action" value="confirmed">
                                    <button type="submit" class="btn">Accept Order</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

                      <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <h3>Order #<?php echo $order['id']; ?></h3>
                            <p>Placed on: <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
                        </div>
                        <span class="status <?php echo htmlspecialchars($order['status']); ?>">
                            <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                        </span>
                    </div>

                    <div class="order-info">
                        <div>
                            <h4>Restaurant:</h4>
                            <p><?php echo htmlspecialchars($order['restaurant_name']); ?></p>
                        </div>
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

                    <div class="order-actions">
                        <form method="POST" style="display: inline-block;" class="confirm-form">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <input type="hidden" name="action" value="confirmed">
                            <button type="submit" class="confirm-btn">Confirm Order</button>
                        </form>
                        <form method="POST" style="display: inline-block;" class="deliver-form">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <input type="hidden" name="action" value="delivered">
                            <button type="submit" class="delivered-btn">Mark as Delivered</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
