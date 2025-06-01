<?php
session_start();
require_once 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

// Handle dish deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM dishes WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: index.php');
    exit();
}

// Fetch all dishes
try {
    $stmt = $pdo->query("SELECT * FROM dishes ORDER BY created_at DESC");
    $dishes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching dishes: " . $e->getMessage();
}

// Fetch recent orders
try {
    $stmt = $pdo->query("
        SELECT o.*, d.title as dish_title, r.name as restaurant_name,
               u.name as customer_name
        FROM orders o 
        LEFT JOIN order_items oi ON o.id = oi.order_id 
        LEFT JOIN dishes d ON oi.dish_id = d.id 
        LEFT JOIN restaurants r ON d.restaurant_id = r.id 
        LEFT JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching orders: " . $e->getMessage();
}

// Calculate statistics
$total_dishes = count($dishes);
$total_orders = $pdo->query("SELECT COUNT(*) as count FROM orders")->fetch(PDO::FETCH_ASSOC)['count'];
$total_revenue = $pdo->query("SELECT SUM(total_amount) as total FROM orders")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - FDelivery</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #337ab7;
            --secondary-color: #2196F3;
            --success-color: #4CAF50;
            --warning-color: #FFC107;
            --danger-color: #F44336;
            --white-color: #ffffff;
            --grey-color: #e0e0e0;
            --grey-color-1: #f5f5f5;
            --grey-color-2: #666666;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px rgba(0,0,0,0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        .admin-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
        }

        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid var(--grey-color);
        }

        .admin-header h2 {
            font-size: 2rem;
            color: var(--primary-color);
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .add-dish-btn {
            background: var(--primary-color);
            color: var(--white-color);
        }

        .add-dish-btn:hover {
            background: #23527c;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .stat {
            background: var(--white-color);
            padding: 1.5rem;
            border-radius: 0.75rem;
            box-shadow: var(--shadow-md);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat:hover {
            transform: translateY(-5px);
        }

        .stat i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .stat h3 {
            color: var(--grey-color-2);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .stat p {
            font-size: 2rem;
            font-weight: 600;
            margin: 0;
        }

        .stat.total-dishes i { color: var(--primary-color); }
        .stat.total-orders i { color: var(--secondary-color); }
        .stat.total-revenue i { color: var(--success-color); }

        .table-container {
            background: var(--white-color);
            border-radius: 0.75rem;
            box-shadow: var(--shadow-md);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .table-header {
            padding: 1.5rem;
            background: var(--primary-color);
            color: var(--white-color);
            border-radius: 0.75rem 0.75rem 0 0;
        }

        .table-header h3 {
            margin: 0;
            font-size: 1.25rem;
        }

        .dishes-table,
        .recent-orders table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .dishes-table th,
        .dishes-table td,
        .recent-orders th,
        .recent-orders td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--grey-color);
        }

        .dishes-table th,
        .recent-orders th {
            background: var(--grey-color-1);
            font-weight: 500;
        }

        .dish-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 0.5rem;
            margin-right: 1rem;
        }

        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            transform: translateY(-2px);
        }

        .edit-btn {
            background: var(--warning-color);
            color: var(--black-color);
        }

        .edit-btn:hover {
            background: #FFA000;
        }

        .delete-btn {
            background: var(--danger-color);
            color: var(--white-color);
        }

        .delete-btn:hover {
            background: #D32F2F;
        }

        .status {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .pending {
            background: var(--warning-color);
            color: var(--black-color);
        }

        .delivered {
            background: var(--success-color);
            color: var(--white-color);
        }

        .cancelled {
            background: var(--danger-color);
            color: var(--white-color);
        }

        .text-muted {
            color: var(--grey-color-2);
            font-style: italic;
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-wrap: wrap;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .table-header h3 {
                font-size: 1.1rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1><i class='bx bx-dashboard'></i> Admin Dashboard</h1>
            <div class="action-buttons">
                <a href="add_dish.php" class="btn">
                    <i class='bx bx-plus'></i> Add New Dish
                </a>
                <a href="view_users.php" class="btn">
                    <i class='bx bx-user'></i> Manage Users
                </a>
                <a href="view_delivery_persons.php" class="btn">
                    <i class='bx bx-user'></i> Delivery Persons
                </a>
                <a href="restaurants.php" class="btn">
                    <i class='bx bx-store'></i> Manage Restaurants
                </a>
               
                <a href="restaurant_orders.php" class="btn">
                    <i class='bx bx-cart'></i> View Orders
                </a>
            </div>
        </div>

        <div class="dashboard">
            <div class="stats">
                <div class="stat total-dishes">
                    <i class='bx bx-dish'></i>
                    <h3>Total Dishes</h3>
                    <p><?php echo $total_dishes; ?></p>
                </div>
                <div class="stat total-orders">
                    <i class='bx bx-cart'></i>
                    <h3>Total Orders</h3>
                    <p><?php echo $total_orders; ?></p>
                </div>
                <div class="stat total-revenue">
                    <i class='bx bx-money'></i>
                    <h3>Total Revenue</h3>
                    <p>$<?php echo number_format($total_revenue, 2); ?></p>
                </div>
            </div>
            <div class="table-container">
                <div class="table-header">
                    <h3><i class='bx bx-history'></i> Recent Orders</h3>
                </div>
                <div class="recent-orders">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Dish</th>
                                <th>Restaurant</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($order['dish_title']); ?></td>
                                    <td><?php echo htmlspecialchars($order['restaurant_name']); ?></td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status <?php echo strtolower($order['status']); ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="table-container">
                <div class="table-header">
                    <h3><i class='bx bx-list-ul'></i> Dishes List</h3>
                </div>
                <table class="dishes-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Category</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dishes as $dish): ?>
                            <tr>
                                <td>
                                    <img src="../<?php echo htmlspecialchars($dish['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($dish['title']); ?>" 
                                         class="dish-image">
                                </td>
                                <td><?php echo htmlspecialchars($dish['title']); ?></td>
                                <td>$<?php echo number_format($dish['price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($dish['category']); ?></td>
                                <td>
                                    <a href="edit_dish.php?id=<?php echo $dish['id']; ?>" 
                                       class="action-btn edit-btn">
                                        <i class='bx bx-edit'></i> Edit
                                    </a>
                                    <a href="?delete=<?php echo $dish['id']; ?>" 
                                       class="action-btn delete-btn" 
                                       onclick="return confirm('Are you sure you want to delete this dish?')">
                                        <i class='bx bx-trash'></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>


        </div>
    </div>
</body>
</html>