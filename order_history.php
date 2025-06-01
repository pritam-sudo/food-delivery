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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - FDelivery</title>
    <link rel="stylesheet" href="./css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #e3342f;
            --white-color: #ffffff;
            --grey-color: #f8f9fa;
            --grey-color-1: #f1f2f6;
            --black-color: #333333;
            --default-color: #666666;
            --green-color: #28a745;
            --yellow-color: #ffc107;
            --red-color: #dc3545;
            --box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .order-history-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: var(--white-color);
            border-radius: 1rem;
            box-shadow: var(--box-shadow);
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .filter-group {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .filter-group select,
        .filter-group input {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 0.5rem;
            font-size: 1rem;
        }

        .order-card {
            background: var(--grey-color-1);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--box-shadow);
            transition: transform 0.3s ease;
        }

        .order-card:hover {
            transform: translateY(-5px);
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
            min-width: 100px;
            text-align: center;
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
        .out_for_delivery {
            background: var(--black-color);
            color: var(--white-color);
        }

        .order-details {
            margin-top: 1rem;
        }

        .order-item {
            margin-bottom: 1rem;
        }

        .order-item h4 {
            margin: 0 0 0.5rem 0;
            color: var(--black-color);
        }

        .order-item p {
            margin: 0.25rem 0;
            color: var(--default-color);
        }

        .restaurant-info {
            margin: 1rem 0;
            padding: 1rem;
            background: var(--white-color);
            border-radius: 0.5rem;
        }

        .restaurant-info h4 {
            margin: 0 0 0.5rem 0;
            color: var(--black-color);
        }

        .status-history {
            background: var(--white-color);
            padding: 1rem;
            border-radius: 0.5rem;
            margin: 1rem 0;
        }

        .status-history h4 {
            margin: 0 0 0.5rem 0;
            color: var(--black-color);
        }

        .status-history p {
            margin: 0;
            color: var(--default-color);
        }

        .btn-primary {
            background: var(--primary-color);
            color: var(--white-color);
            padding: 0.8rem 2rem;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
            text-decoration: none;
        }

        .btn-primary:hover {
            background-color: #e3342f;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--grey-color);
            border-radius: 1rem;
            margin: 2rem auto;
            max-width: 500px;
        }

        .empty-state h3 {
            color: var(--black-color);
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: var(--default-color);
        }

        @media (max-width: 768px) {
            .filter-group {
                flex-direction: column;
            }

            .order-card {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>

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
            <a href="index.html" class="btn">Home</a>
            <a href="dishes.php" class="btn">Order Food</a>
          </div>

          <!-- Hamburger -->
          <div class="hamburger d-flex">
            <i class="bx bx-menu"></i>
          </div>
        </div>
      </nav>
    <div class="container">
        
        <div class="order-history-container">
        <h2 class="text-center">My Order History</h2>
            <div class="header-actions">
                <div class="filter-group">
                    <select id="statusFilter">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="preparing">Preparing</option>
                        <option value="out_for_delivery">Out for Delivery</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <select id="dateFilter">
                        <option value="">All Dates</option>
                        <option value="7">Last 7 Days</option>
                        <option value="30">Last 30 Days</option>
                        <option value="90">Last 90 Days</option>
                    </select>
                    <input type="text" id="searchInput" placeholder="Search orders...">
                </div>
                <div class="order-actions">
                    <a href="profile.php" class="btn btn-primary">Back to Profile</a>
                </div>
            </div>
            <div class="order-history row justify-content-center">
                <?php if (empty($order_history)): ?>
                    <div class="empty-state">
                        <i class="fas fa-box-open fa-4x mb-3"></i>
                        <h3>No Orders Yet</h3>
                        <p>Start placing orders and track them here!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($order_history as $order): ?>
                        <div class="order-card list-group-item col-md-6 p-2">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">Order #<?php echo htmlspecialchars($order['id']); ?></h5>
                                
                            </div>
                            <p class="mb-1">Placed on <?php echo date('F j, Y H:i', strtotime($order['created_at'])); ?></p>
                            <p class="mb-1">Dish: <?php echo htmlspecialchars($order['dish_title']); ?></p>
                            <p class="mb-1">Quantity: <?php echo htmlspecialchars($order['quantity']); ?></p>
                            <p class="mb-1">Price: $<?php echo number_format($order['dish_price'], 2); ?></p>
                            <p class="mb-1">Total: $<?php echo number_format($order['total_amount'], 2); ?></p>

                            <div class="status-history">
                                <h4>Status:</h4>
                                <span class="status <?php echo htmlspecialchars($order['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                </span>
                            </div>

                            <div class="order-actions">
                                <?php if ($order['tracking_number']): ?>
                                    <p><strong>Tracking:</strong> <?php echo htmlspecialchars($order['tracking_number']); ?></p>
                                <?php endif; ?>
                                <?php if ($order['delivery_notes']): ?>
                                    <p><strong>Notes:</strong> <?php echo htmlspecialchars($order['delivery_notes']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Status filter
            const statusFilter = document.getElementById('statusFilter');
            if (statusFilter) {
                statusFilter.addEventListener('change', function() {
                    filterOrders();
                });
            }

            // Date filter
            const dateFilter = document.getElementById('dateFilter');
            if (dateFilter) {
                dateFilter.addEventListener('change', function() {
                    filterOrders();
                });
            }

            // Search
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    filterOrders();
                });
            }

            function filterOrders() {
                const orders = document.querySelectorAll('.order-card');
                const status = statusFilter ? statusFilter.value : '';
                const days = dateFilter ? dateFilter.value : '';
                const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';

                orders.forEach(order => {
                    const orderStatus = order.querySelector('.status').classList[1];
                    const orderDate = new Date(order.querySelector('.order-date p').textContent);
                    const orderTitle = order.querySelector('.order-item h4').textContent.toLowerCase();

                    let showOrder = true;

                    // Status filter
                    if (status && orderStatus !== status) {
                        showOrder = false;
                    }

                    // Date filter
                    if (days) {
                        const now = new Date();
                        const cutoff = new Date(now.getTime() - (days * 24 * 60 * 60 * 1000));
                        if (orderDate < cutoff) {
                            showOrder = false;
                        }
                    }

                    // Search
                    if (searchTerm && !orderTitle.includes(searchTerm)) {
                        showOrder = false;
                    }

                    order.style.display = showOrder ? 'block' : 'none';
                });
            }
        });
    </script>
</body>
</html>
