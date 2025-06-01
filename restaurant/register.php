<?php
session_start();
require_once '../admin/config.php';

if (isset($_SESSION['restaurant_admin_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

// Fetch all restaurants
try {
    $stmt = $pdo->query("SELECT id, name FROM restaurants ORDER BY name");
    $restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching restaurants: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email = $_POST['email'];
    $name = $_POST['name'];
    $restaurant_id = $_POST['restaurant_id'];

    // Validate input
    if ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        try {
            // Check if username exists
            $stmt = $pdo->prepare("SELECT id FROM restaurant_admins WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = "Username already exists";
            } else {
                // Check if email exists
                $stmt = $pdo->prepare("SELECT id FROM restaurant_admins WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error = "Email already exists";
                } else {
                    // Check if restaurant already has an admin
                    $stmt = $pdo->prepare("SELECT id FROM restaurant_admins WHERE restaurant_id = ?");
                    $stmt->execute([$restaurant_id]);
                    if ($stmt->fetch()) {
                        $error = "This restaurant already has an admin";
                    } else {
                        // Create new admin
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO restaurant_admins (username, password, email, name, restaurant_id) VALUES (?, ?, ?, ?, ?)");
                        if ($stmt->execute([$username, $hashed_password, $email, $name, $restaurant_id])) {
                            $success = "Registration successful! You can now login.";
                        } else {
                            $error = "Error creating account";
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Admin Registration - FDelivery</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .register-container {
            max-width: 500px;
            margin: 4rem auto;
            padding: 2rem;
            background: var(--white-color);
            border-radius: 1rem;
            box-shadow: var(--box-shadow);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--grey-color);
            border-radius: 0.5rem;
        }
        .error {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        .success {
            color: green;
            margin-bottom: 1rem;
        }
        .login-link {
            text-align: center;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h2>Restaurant Admin Registration</h2>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="restaurant_id">Restaurant</label>
                
                <select id="restaurant_id" name="restaurant_id" required>
                    <option value="">Select a restaurant</option>
                    <?php foreach ($restaurants as $restaurant): ?>
                        <option value="<?php echo $restaurant['id']; ?>">
                            <?php echo htmlspecialchars($restaurant['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit" class="btn" style="width: 100%;">Register</button>
        </form>
        

    </div>
    <p style="text-align: center;">
            <a href="http://localhost/food-delivery-website/admin/index.php" class="btn">Back</a>
        </p>
</body>
</html>
