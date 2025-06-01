<?php
session_start();
require_once '../admin/config.php';

// Check if restaurant admin is logged in
if (!isset($_SESSION['restaurant_admin_id'])) {
    header('Location: login.php');
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'];
$admin_name = $_SESSION['restaurant_admin_name'];
$error = '';

// Fetch restaurant details
try {
    $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE id = ?");
    $stmt->execute([$restaurant_id]);
    $restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching restaurant: " . $e->getMessage();
}

// Fetch restaurant's dishes
try {
    $stmt = $pdo->prepare("SELECT * FROM dishes WHERE restaurant_id = ? ORDER BY created_at DESC");
    $stmt->execute([$restaurant_id]);
    $dishes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching dishes: " . $e->getMessage();
}

// Fetch restaurant's orders
try {
    $stmt = $pdo->prepare("
        SELECT o.*, d.title as dish_title, d.price, oi.quantity,
               u.name as customer_name, u.email as customer_email,
               u.phone as customer_phone, u.address as customer_address
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN dishes d ON oi.dish_id = d.id
        JOIN users u ON o.user_id = u.id
        WHERE d.restaurant_id = ?
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$restaurant_id]);
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching orders: " . $e->getMessage();
}

// Calculate statistics
$total_orders = count($recent_orders);
$total_revenue = array_sum(array_map(function($order) {
    return $order['price'] * $order['quantity'];
}, $recent_orders));
$total_dishes = count($dishes);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Admin Dashboard - FDelivery</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: var(--white-color);
            padding: 1.5rem;
            border-radius: 1rem;
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
        .section {
            background: var(--white-color);
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: var(--box-shadow);
            margin-bottom: 2rem;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--grey-color);
        }
        .table th {
            background: var(--grey-color-1);
            font-weight: 600;
        }
        .status {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            display: inline-block;
        }
        .pending { background: var(--yellow-color); }
        .delivered { background: var(--green-color); color: var(--white-color); }
        .cancelled { background: var(--primary-color); color: var(--white-color); }
        .action-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            margin-right: 0.5rem;
            background: var(--primary-color);
            color: var(--white-color);
            transition: background-color 0.3s ease;
        }
        .action-btn:hover {
            background: var(--hover-color);
        }
        .action-buttons {
            display: flex;
            gap: 1rem;
        }
        .edit-btn {
            background: var(--yellow-color);
            color: var(--black-color);
        }
        .delete-btn {
            background: var(--primary-color);
            color: var(--white-color);
        }
        .dish-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 0.5rem;
        }
        .logout-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            background: var(--grey-color-1);
            color: var(--black-color);
            text-decoration: none;
        }
        .table td small {
            color: var(--grey-color-2);
            display: block;
            line-height: 1.5;
        }
        .table td small i {
            margin-right: 5px;
            font-size: 14px;
        }
        .table td strong {
            color: var(--black-color);
            font-size: 1.1em;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div>
                <h2><?php echo htmlspecialchars($restaurant['name']); ?> Dashboard</h2>
                <p>Welcome back, <?php echo htmlspecialchars($admin_name); ?>!</p>
            </div>
            
            <div>
            <a href="view_orders.php" class="btn">View All Orders</a>
                <a href="add_dish.php" class="btn">Add New Dish</a>
                <a href="logout.php" class="btn">Logout</a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-card">
                <h3>Total Orders</h3>
                <p><?php echo $total_orders; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Revenue</h3>
                <p>$<?php echo number_format($total_revenue, 2); ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Dishes</h3>
                <p><?php echo $total_dishes; ?></p>
            </div>
        </div>

        <div class="section">
            <h3>Recent Orders</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer Details</th>
                        <th>Dish</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_orders as $order): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                                <small>
                                    <i class="bx bx-envelope"></i> <?php echo htmlspecialchars($order['customer_email']); ?><br>
                                    <i class="bx bx-phone"></i> <?php echo htmlspecialchars($order['customer_phone']); ?><br>
                                    <i class="bx bx-map"></i> <?php echo htmlspecialchars($order['customer_address']); ?>
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
        </div>

        <div class="section">
            <h3>Restaurant Dishes</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Title</th>
                        <th>Price</th>
                        <th>Category</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dishes as $dish): ?>
                        <tr>
                            <td>
                                <img src="<?php echo htmlspecialchars($dish['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($dish['title']); ?>" 
                                     class="dish-image">
                            </td>
                            <td><?php echo htmlspecialchars($dish['title']); ?></td>
                            <td>$<?php echo number_format($dish['price'], 2); ?></td>
                            <td><?php echo htmlspecialchars($dish['category']); ?></td>
                            <td>
                                <a href="edit_dish.php?id=<?php echo $dish['id']; ?>" 
                                   class="action-btn edit-btn">Edit</a>
                                <a href="delete_dish.php?id=<?php echo $dish['id']; ?>" 
                                   class="action-btn delete-btn" 
                                   onclick="return confirm('Are you sure you want to delete this dish?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
