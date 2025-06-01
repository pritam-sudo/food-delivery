<?php
session_start();
require_once 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

// Fetch all restaurants
$restaurants = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM restaurants ORDER BY name");
    $restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching restaurants: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    $restaurant_id = $_POST['restaurant_id'];
    
    // Insert dish into database
    $stmt = $pdo->prepare("INSERT INTO dishes (restaurant_id, title, price, category, description) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$restaurant_id, $title, $price, $category, $description])) {
        $success = "Dish added successfully!";
    } else {
        $error = "Error adding dish to database: " . implode("", $stmt->errorInfo());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Dish - FDelivery</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <style>
        .form-container {
            max-width: 600px;
            margin: 2rem auto;
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
        .form-group select,
        .form-group textarea {
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
        .admin-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        .back-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            background: var(--grey-color-1);
            color: var(--black-color);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-left: auto;
        }
            
        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>

    <div class="form-container">
    <div class="form-header">
            <h2>Add New Dish</h2>
            <a href="index.php" class="back-btn">
                <i class="bx bx-arrow-back"></i> Back to Dashboard
            </a>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title">Dish Title</label>
                <input type="text" id="title" name="title" required>
            </div>

            <div class="form-group">
                <label for="price">Price</label>
                <input type="number" id="price" name="price" step="0.01" required>
            </div>

            <div class="form-group">
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    <option value="Fast Food">Fast Food</option>
                    <option value="Rice Menu">Rice Menu</option>
                    <option value="Desserts">Desserts</option>
                    <option value="Pizza">Pizza</option>
                    <option value="Coffee">Coffee</option>
                </select>
            </div>

            <div class="form-group">
                <label for="restaurant_id">Restaurant</label>
                <select id="restaurant_id" name="restaurant_id" required>
                    <option value="">Select a restaurant</option>
                    <?php foreach ($restaurants as $restaurant): ?>
                        <option value="<?php echo htmlspecialchars($restaurant['id']); ?>">
                            <?php echo htmlspecialchars($restaurant['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"></textarea>
            </div>

            <button type="submit" class="btn" style="width: 100%;">Add Dish</button>
        </form>
    </div>
</body>
</html> 