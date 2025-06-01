<?php
session_start();
require_once 'admin/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get order ID from URL
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Check if order exists and belongs to user
$stmt = $pdo->prepare("
    SELECT o.*, d.title as dish_title 
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    LEFT JOIN dishes d ON oi.dish_id = d.id 
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: profile.php');
    exit();
}

// Check if order can be cancelled (only pending orders can be cancelled)
if ($order['status'] !== 'pending') {
    $_SESSION['error'] = "This order cannot be cancelled as it is already " . $order['status'];
    header('Location: profile.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reason = trim($_POST['reason']);
    
    if (empty($reason)) {
        $error = "Please provide a reason for cancellation";
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE orders 
                SET status = 'cancelled', 
                    cancellation_reason = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND user_id = ?
            ");
            
            if ($stmt->execute([$reason, $order_id, $_SESSION['user_id']])) {
                $_SESSION['success'] = "Order cancelled successfully";
                header('Location: profile.php');
                exit();
            } else {
                $error = "Error cancelling order";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Order - FDelivery</title>
    <link rel="stylesheet" href="./css/styles.css">
    <style>
        .cancel-order-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: var(--white-color);
            border-radius: 1rem;
            box-shadow: var(--box-shadow);
        }
        .cancel-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .cancel-header h2 {
            color: var(--black-color);
            margin-bottom: 0.5rem;
        }
        .order-details {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: var(--grey-color-1);
            border-radius: 0.5rem;
        }
        .order-details h3 {
            margin-bottom: 1rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
        }
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--grey-color);
            border-radius: 0.5rem;
            min-height: 100px;
        }
        .error {
            color: var(--primary-color);
            margin-bottom: 1rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="cancel-order-container">
            <div class="cancel-header">
                <h2>Cancel Order</h2>
                <p>Are you sure you want to cancel this order?</p>
            </div>

            <div class="order-details">
                <h3>Order Details</h3>
                <p><strong>Item:</strong> <?php echo htmlspecialchars($order['dish_title']); ?></p>
                <p><strong>Quantity:</strong> <?php echo $order['quantity']; ?></p>
                <p><strong>Total:</strong> $<?php echo number_format($order['total_amount'], 2); ?></p>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="reason">Reason for Cancellation</label>
                    <textarea id="reason" name="reason" required placeholder="Please provide a reason for cancelling this order"></textarea>
                </div>

                <?php if (isset($error)): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="form-actions">
                    <button type="submit" class="btn">Cancel Order</button>
                    <a href="profile.php" class="btn">Back to Profile</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
