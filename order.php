<?php
session_start();
require_once 'admin/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get dish ID from URL
$dish_id = isset($_GET['dish_id']) ? (int)$_GET['dish_id'] : 0;

// Fetch dish details
$stmt = $pdo->prepare("SELECT d.*, r.name as restaurant_name 
                       FROM dishes d 
                       LEFT JOIN restaurants r ON d.restaurant_id = r.id 
                       WHERE d.id = ?");
$stmt->execute([$dish_id]);
$dish = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dish) {
    header('Location: dishes.php');
    exit();
}

// Fetch user details
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    // Validate input
    if ($quantity < 1) {
        $error = "Quantity must be at least 1";
    } else {
        try {
            // Start transaction
            $pdo->beginTransaction();

            // Insert order
            $stmt = $pdo->prepare("INSERT INTO orders (user_id, dish_id, quantity, total_amount) 
                                  VALUES (?, ?, ?, ?)");
            $total_amount = $dish['price'] * $quantity;
            $stmt->execute([$user_id, $dish_id, $quantity, $total_amount]);
            $order_id = $pdo->lastInsertId();

            // Insert order item
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, dish_id, quantity, price) 
                                  VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $dish_id, $quantity, $dish['price']]);

            // Insert user order record
            $stmt = $pdo->prepare("INSERT INTO user_orders (user_id, order_id) 
                                  VALUES (?, ?)");
            $stmt->execute([$user_id, $order_id]);

            // Commit transaction
            $pdo->commit();

            // Redirect to order confirmation
            header("Location: order_confirmation.php?order_id=$order_id");
            exit();
        } catch (PDOException $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $error = "Error placing order: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Order - FDelivery</title>
    <link rel="stylesheet" href="./css/styles.css">
    <style>
        .order-container {
            max-width: 800px;
            margin: 3rem auto;
            padding: 2rem;
            background: var(--white-color);
            border-radius: 1rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .order-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .order-header h2 {
            font-size: 2rem;
            color: var(--black-color);
            margin-bottom: 0.5rem;
        }

        .order-header p {
            color: var(--grey-color-2);
            font-size: 1.1rem;
        }

        .form-group {
            margin-bottom: 2rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.8rem;
            font-weight: 500;
            color: var(--black-color);
            font-size: 1.1rem;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 1rem;
            border: 2px solid #eef0f7;
            border-radius: 0.8rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: var(--primary-color);
            background: var(--white-color);
            box-shadow: 0 0 0 4px rgba(227, 52, 47, 0.1);
            outline: none;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #a0aec0;
        }

        .error {
            color: var(--primary-color);
            font-size: 0.9rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .error i {
            font-size: 1.1rem;
        }

        .order-summary {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 0.8rem;
            margin-bottom: 2rem;
        }

        .order-summary h3 {
            color: var(--black-color);
            margin-bottom: 1.5rem;
            font-size: 1.2rem;
        }

        .dish-details {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #eef0f7;
        }

        .dish-image {
            width: 120px;
            height: 120px;
            border-radius: 0.8rem;
            object-fit: cover;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .dish-info {
            flex: 1;
        }

        .dish-name {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--black-color);
            margin-bottom: 0.5rem;
        }

        .dish-price {
            font-size: 1.2rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .order-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            color: var(--grey-color-2);
        }

        .detail-item strong {
            color: var(--black-color);
        }

        .order-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 2px solid #eef0f7;
        }

        .order-actions .total {
            font-size: 1.8rem;
            font-weight: 600;
            color: var(--black-color);
        }

        .order-actions .total span {
            color: var(--primary-color);
        }

        .order-actions button {
            background: var(--primary-color);
            color: var(--white-color);
            padding: 1.2rem 3rem;
            border-radius: 1rem;
            font-size: 1.3rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            min-width: 240px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .order-actions button i {
            font-size: 1.5rem;
        }

        .order-actions button:hover {
            background-color: #cf1f1a;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(227, 52, 47, 0.25);
        }

        .payment-methods {
            margin-bottom: 2rem;
        }

        .payment-methods h3 {
            font-size: 1.2rem;
            color: var(--black-color);
            margin-bottom: 1rem;
        }

        .payment-option {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.2rem;
            border: 2px solid #eef0f7;
            border-radius: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }

        .payment-option:hover {
            border-color: var(--primary-color);
            background: #fff;
        }

        .payment-option.selected {
            border-color: var(--primary-color);
            background: #fff;
            box-shadow: 0 4px 12px rgba(227, 52, 47, 0.1);
        }

        .payment-option input[type="radio"] {
            width: 20px;
            height: 20px;
            accent-color: var(--primary-color);
        }

        .payment-option label {
            flex: 1;
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 0;
            cursor: pointer;
        }

        .payment-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            border-radius: 0.5rem;
            color: var(--primary-color);
        }

        .payment-icon i {
            font-size: 1.5rem;
        }

        .payment-description {
            color: var(--grey-color-2);
            font-size: 0.9rem;
            margin-top: 0.3rem;
            margin-left: 2.5rem;
        }

        .user-info {
            background: #fff;
            padding: 1.5rem;
            border-radius: 0.8rem;
            margin-bottom: 2rem;
            border: 2px solid #eef0f7;
        }

        .user-info h3 {
            font-size: 1.2rem;
            color: var(--black-color);
            margin-bottom: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .user-info h3 i {
            color: var(--primary-color);
            font-size: 1.4rem;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .info-item label {
            color: var(--grey-color-2);
            font-size: 0.9rem;
        }

        .info-item span {
            color: var(--black-color);
            font-size: 1.1rem;
            font-weight: 500;
        }

        .info-item.full-width {
            grid-column: 1 / -1;
        }

        @media (max-width: 576px) {
            .dish-details {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }

            .dish-image {
                width: 180px;
                height: 180px;
            }

            .order-details {
                grid-template-columns: 1fr;
            }

            .order-actions {
                flex-direction: column;
                gap: 1.5rem;
                text-align: center;
            }

            .order-actions button {
                width: 100%;
                justify-content: center;
            }

            .info-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="order-container">
            <div class="order-header">
                <h2>Complete Your Order</h2>
                <p>Please provide your delivery details below</p>
            </div>

            <div class="order-summary">
                <h3>Order Summary</h3>
                <div class="dish-details">
                    <img src="<?php echo htmlspecialchars($dish['image_url']); ?>" alt="<?php echo htmlspecialchars($dish['title']); ?>" class="dish-image">
                    <div class="dish-info">
                        <div class="dish-name"><?php echo htmlspecialchars($dish['title']); ?></div>
                        <div class="dish-price">$<?php echo number_format($dish['price'], 2); ?></div>
                        <div class="order-details">
                            <div class="detail-item">
                                <span>Quantity:</span>
                                <strong><?php echo isset($_POST['quantity']) ? intval($_POST['quantity']) : 1; ?></strong>
                            </div>
                            <div class="detail-item">
                                <span>Subtotal:</span>
                                <strong>$<?php echo number_format($dish['price'] * (isset($_POST['quantity']) ? $_POST['quantity'] : 1), 2); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST" action="">
                <div class="user-info">
                    <h3>
                        <i class='bx bx-user-circle'></i>
                        Delivery Information
                    </h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Name</label>
                            <span><?php echo htmlspecialchars($user['name']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Phone</label>
                            <span><?php echo htmlspecialchars($user['phone']); ?></span>
                        </div>
                        <div class="info-item full-width">
                            <label>Delivery Address</label>
                            <span><?php echo htmlspecialchars($user['address']); ?></span>
                        </div>
                    </div>
                </div>

                <div class="payment-methods">
                    <h3>Select Payment Method</h3>
                    
                    <div class="payment-option selected">
                        <input type="radio" id="cod" name="payment_method" value="cod" checked>
                        <div class="payment-icon">
                            <i class='bx bx-money'></i>
                        </div>
                        <div>
                            <label for="cod">Cash on Delivery</label>
                            <div class="payment-description">Pay with cash upon delivery of your order</div>
                        </div>
                    </div>

                    <div class="payment-option" style="opacity: 0.5; pointer-events: none;">
                        <input type="radio" id="card" name="payment_method" value="card" disabled>
                        <div class="payment-icon">
                            <i class='bx bx-credit-card'></i>
                        </div>
                        <div>
                            <label for="card">Card Payment</label>
                            <div class="payment-description">Coming soon - Pay with credit or debit card</div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <select id="quantity" name="quantity" onchange="updateTotal()">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo (isset($_POST['quantity']) && $_POST['quantity'] == $i) ? 'selected' : ''; ?>>
                                <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <?php if ($error): ?>
                    <div class="error">
                        <i class='bx bx-error-circle'></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="order-actions">
                    <div class="total">Total: <span>$<?php echo number_format($dish['price'] * (isset($_POST['quantity']) ? $_POST['quantity'] : 1), 2); ?></span></div>
                    <button type="submit">
                        <i class='bx bx-check-circle'></i>
                        PLACE ORDER
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function updateTotal() {
            const quantity = document.getElementById('quantity').value;
            const price = <?php echo $dish['price']; ?>;
            const total = quantity * price;
            
            document.getElementById('final-total').textContent = total.toFixed(2);
        }

        // Initialize total if quantity was previously selected
        const prevQuantity = <?php echo isset($_POST['quantity']) ? $_POST['quantity'] : 1; ?>;
        document.getElementById('quantity').value = prevQuantity;
        updateTotal();
    </script>
</body>
</html>
