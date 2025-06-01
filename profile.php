<?php
session_start();
require_once 'admin/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch user details
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch order history with detailed information and status history
$stmt = $pdo->prepare("
    SELECT 
        o.*, 
        d.title as dish_title, 
        d.price as dish_price,
        r.name as restaurant_name,
        r.phone as restaurant_phone,
        r.address as restaurant_address,
        GROUP_CONCAT(oh.status ORDER BY oh.updated_at SEPARATOR ' → ') as status_history
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    LEFT JOIN dishes d ON oi.dish_id = d.id 
    LEFT JOIN restaurants r ON d.restaurant_id = r.id 
    LEFT JOIN order_history oh ON o.id = oh.order_id
    WHERE o.user_id = ? 
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id]);
$order_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent orders (last 5)
$stmt = $pdo->prepare("
    SELECT 
        o.*, 
        d.title as dish_title, 
        d.price as dish_price,
        r.name as restaurant_name,
        r.phone as restaurant_phone,
        r.address as restaurant_address,
        GROUP_CONCAT(oh.status ORDER BY oh.updated_at SEPARATOR ' → ') as status_history
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    LEFT JOIN dishes d ON oi.dish_id = d.id 
    LEFT JOIN restaurants r ON d.restaurant_id = r.id 
    LEFT JOIN order_history oh ON o.id = oh.order_id
    WHERE o.user_id = ? 
    GROUP BY o.id
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Display success/error messages
$success = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';

// Clear session messages
unset($_SESSION['success']);
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const showHistoryBtn = document.getElementById('showHistoryBtn');
            const orderHistory = document.getElementById('orderHistory');
            const recentOrders = document.getElementById('recentOrders');

            if (showHistoryBtn && orderHistory && recentOrders) {
                showHistoryBtn.addEventListener('click', function() {
                    orderHistory.style.display = orderHistory.style.display === 'none' ? 'block' : 'none';
                    recentOrders.style.display = recentOrders.style.display === 'none' ? 'block' : 'none';
                });
            }
        });
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - FDelivery</title>
    <link rel="stylesheet" href="./css/styles.css">
    <style>
        .btn-primary {
            background: var(--primary-color);
            color: var(--white-color);
            padding: 0.8rem 2rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-bottom: 1rem;
        }
        .btn-primary:hover {
            background-color: #e3342f;
        }
        .profile-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: var(--white-color);
            border-radius: 1rem;
            box-shadow: var(--box-shadow);
        }
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--grey-color);
        }
        .profile-header .avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1.5rem;
        }
        .profile-header .avatar span {
            font-size: 2rem;
            color: var(--white-color);
        }
        .profile-header .info {
            flex: 1;
        }
        .profile-header h2 {
            margin: 0 0 0.5rem 0;
            color: var(--black-color);
        }
        .profile-header p {
            margin: 0;
            color: var(--default-color);
        }
        .profile-tabs {
            display: flex;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--grey-color);
        }
        .profile-tabs button {
            padding: 1rem 2rem;
            margin-right: 1rem;
            border: none;
            background: none;
            cursor: pointer;
            color: var(--default-color);
        }
        .profile-tabs button.active {
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
        }
        .profile-section {
            display: none;
        }
        .profile-section.active {
            display: block;
        }
        .order-card {
            background: var(--grey-color-1);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--box-shadow);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .order-id {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .status {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
        }
        .pending {
            background: var(--yellow-color);
            color: var(--black-color);
        }
        .delivered {
            background: var(--green-color);
            color: var(--white-color);
        }
        .cancelled {
            background: var(--red-color);
            color: var(--white-color);
        }
        .order-details {
            display: grid;
            gap: 1.5rem;
        }
        .order-item {
            background: var(--white-color);
            padding: 1rem;
            border-radius: 0.5rem;
            box-shadow: var(--box-shadow);
        }
        .restaurant-info {
            background: var(--white-color);
            padding: 1rem;
            border-radius: 0.5rem;
            box-shadow: var(--box-shadow);
        }
        .order-status {
            background: var(--white-color);
            padding: 1rem;
            border-radius: 0.5rem;
            box-shadow: var(--box-shadow);
        }
        .order-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-danger {
            background: var(--primary-color);
            color: var(--white-color);
        }
        .btn-danger:hover {
            background: #e3342f;
        }
        .error {
            color: var(--primary-color);
            margin-bottom: 1rem;
            text-align: center;
        }
        .success {
            color: green;
            margin-bottom: 1rem;
            text-align: center;
        }
        .orders-grid {
            display: grid;
            gap: 1.5rem;
        }
    </style>
</head>
<body>
      <!--=============== Header ===============-->
      <header class="header">
      <nav class="navbar">
        <div class="row d-flex container">
          <a href="" class="logo d-flex">
            <img src="./images/logo.png" alt="" />
          </a>

          <ul class="nav-list d-flex">
            <a href="index.html">Home</a>
            <a href="">About</a>
            <a href="dishes.html">Shop</a>
            <a href="">Recipes</a>
            <a href="">Contact</a>
            <span class="close d-flex"><i class="bx bx-x"></i></span>
          </ul>

          <div class="auth-links d-flex">
            <!--  -->
          </div>

          <div class="col d-flex">
            
            <div class="cart-icon d-flex">
              <i class="bx bx-shopping-bag"></i>
              <span>0</span>
            </div>
            <a href="profile.php" class="btn">Profile</a>
            <a href="dishes.php" class="btn">Order Food</a>
          </div>

          <!-- Hamburger -->
          <div class="hamburger d-flex">
            <i class="bx bx-menu"></i>
          </div>
        </div>
      </nav>

      
    <div class="container">
        <div class="profile-container">
            <div class="profile-header">
                <div class="avatar">
                    <span><?php echo strtoupper(substr($user['name'], 0, 1)); ?></span>
                </div>
                <div class="info">
                    <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
            </div>

            <div class="profile-tabs">
                <button class="active" onclick="showSection('profile-info')">Profile Info</button>
                <button onclick="showSection('order-history')">Order History</button>
            </div>

            <div id="profile-info" class="profile-section active">
                <h3>Profile Information</h3>
                <div class="profile-info">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($user['address']); ?></p>
                    <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                </div>
            </div>

            <div id="order-history" class="profile-section">
                <h3>Order History</h3>
                <div class="recent-orders">
                    <h4>Recent Orders</h4>
                    <?php if (empty($recent_orders)): ?>
                        <p>No orders yet</p>
                    <?php else: ?>
                        <?php foreach ($recent_orders as $order): ?>
                            <div class="order-item">
                                <div class="details">
                                    <h5><?php echo htmlspecialchars($order['dish_title']); ?></h5>
                                    <p>Restaurant: <?php echo htmlspecialchars($order['restaurant_name']); ?></p>
                                    <p>Quantity: <?php echo $order['quantity']; ?></p>
                                    <p>Total: $<?php echo number_format($order['total_amount'], 2); ?></p>
                                </div>
                                <div class="status <?php echo htmlspecialchars($order['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="profile-section" id="recentOrders">
                    <h4>Recent Orders</h4>
                    <?php if (empty($recent_orders)): ?>
                        <p>No recent orders</p>
                    <?php else: ?>
                        <div class="orders-grid">
                            <?php foreach ($recent_orders as $order): ?>
                                <div class="order-card">
                                    <div class="order-header">
                                        <div class="order-id">
                                            <h3>Order #<?php echo $order['id']; ?></h3>
                                            <span class="status <?php echo htmlspecialchars($order['status']); ?>">
                                                <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                            </span>
                                        </div>
                                        <div class="order-date">
                                            <p><?php echo date('F j, Y H:i', strtotime($order['created_at'])); ?></p>
                                        </div>
                                    </div>

                                    <div class="order-details">
                                        <div class="order-item">
                                            <h4><?php echo htmlspecialchars($order['dish_title']); ?></h4>
                                            <p>Quantity: <?php echo $order['quantity']; ?></p>
                                            <p>Price: $<?php echo number_format($order['dish_price'], 2); ?></p>
                                            <p>Total: $<?php echo number_format($order['total_amount'], 2); ?></p>
                                        </div>

                                        <div class="restaurant-info">
                                            <h4>Restaurant</h4>
                                            <p><?php echo htmlspecialchars($order['restaurant_name']); ?></p>
                                            <p><?php echo htmlspecialchars($order['restaurant_phone']); ?></p>
                                            <p><?php echo htmlspecialchars($order['restaurant_address']); ?></p>
                                        </div>

                                        <div class="order-status">
                                            <h4>Status History</h4>
                                            <p><?php echo htmlspecialchars($order['status_history']); ?></p>
                                        </div>

                                        <div class="order-actions">
                                            <?php if ($order['status'] === 'pending'): ?>
                                                <a href="cancel_order.php?order_id=<?php echo $order['id']; ?>" class="btn btn-danger">Cancel</a>
                                            <?php endif; ?>
                                            <?php if ($order['tracking_number']): ?>
                                                <p>Tracking: <?php echo htmlspecialchars($order['tracking_number']); ?></p>
                                            <?php endif; ?>
                                            <?php if ($order['delivery_notes']): ?>
                                                <p>Notes: <?php echo htmlspecialchars($order['delivery_notes']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="profile-section" id="orderHistory">
                    <button id="showHistoryBtn" class="btn btn-primary">Show Full Order History</button>
                    <div class="all-orders" style="display: none;">
                        <h4>All Orders</h4>
                        <?php if (empty($order_history)): ?>
                            <p>No orders yet</p>
                        <?php else: ?>
                            <div class="orders-grid">
                                <?php foreach ($order_history as $order): ?>
                                    <div class="order-card">
                                        <div class="order-header">
                                            <div class="order-id">
                                                <h3>Order #<?php echo $order['id']; ?></h3>
                                                <span class="status <?php echo htmlspecialchars($order['status']); ?>">
                                                    <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                                </span>
                                            </div>
                                            <div class="order-date">
                                                <p><?php echo date('F j, Y H:i', strtotime($order['created_at'])); ?></p>
                                            </div>
                                        </div>

                                        <div class="order-details">
                                            <div class="order-item">
                                                <h4><?php echo htmlspecialchars($order['dish_title']); ?></h4>
                                                <p>Quantity: <?php echo $order['quantity']; ?></p>
                                                <p>Price: $<?php echo number_format($order['dish_price'], 2); ?></p>
                                                <p>Total: $<?php echo number_format($order['total_amount'], 2); ?></p>
                                            </div>

                                            <div class="restaurant-info">
                                                <h4>Restaurant</h4>
                                                <p><?php echo htmlspecialchars($order['restaurant_name']); ?></p>
                                                <p><?php echo htmlspecialchars($order['restaurant_phone']); ?></p>
                                                <p><?php echo htmlspecialchars($order['restaurant_address']); ?></p>
                                            </div>

                                            <div class="order-status">
                                                <h4>Status History</h4>
                                                <p><?php echo htmlspecialchars($order['status_history']); ?></p>
                                            </div>

                                            <div class="order-actions">
                                                <?php if ($order['status'] === 'pending'): ?>
                                                    <a href="cancel_order.php?order_id=<?php echo $order['id']; ?>" class="btn btn-danger">Cancel</a>
                                                <?php endif; ?>
                                                <?php if ($order['tracking_number']): ?>
                                                    <p>Tracking: <?php echo htmlspecialchars($order['tracking_number']); ?></p>
                                                <?php endif; ?>
                                                <?php if ($order['delivery_notes']): ?>
                                                    <p>Notes: <?php echo htmlspecialchars($order['delivery_notes']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                   
                </div>
            </div>
        </div>

    
        <a href="logout.php" class="btn">Logout</a>
        <a href="order_history.php" class="btn">View Order History</a>
    </div>
    
        

    <script>
        function showSection(sectionId) {
            // Remove active class from all tabs
            document.querySelectorAll('.profile-tabs button').forEach(button => {
                button.classList.remove('active');
            });
            
            // Add active class to clicked tab
            document.querySelector(`[onclick="showSection('${sectionId}')"]').classList.add('active');
            
            // Hide all sections
            document.querySelectorAll('.profile-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(sectionId).classList.add('active');
        }
    </script>
</body>
</html>
