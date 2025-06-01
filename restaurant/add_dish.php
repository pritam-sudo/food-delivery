<?php
session_start();
require_once '../admin/config.php';

// Check if restaurant admin is logged in
if (!isset($_SESSION['restaurant_admin_id'])) {
    header('Location: login.php');
    exit();
}

$restaurant_id = $_SESSION['restaurant_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    $description = $_POST['description'];
    
    // Handle image upload
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = uniqid() . '.' . $ext;
            $upload_path = '../images/dishes/' . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_url = './images/dishes/' . $new_filename;
            } else {
                $error = "Failed to upload image";
            }
        } else {
            $error = "Invalid image format. Allowed formats: " . implode(', ', $allowed);
        }
    }

    if (empty($error)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO dishes (restaurant_id, title, price, category, description, image_url) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            if ($stmt->execute([$restaurant_id, $title, $price, $category, $description, $image_url])) {
                $success = "Dish added successfully!";
            } else {
                $error = "Error adding dish to database";
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
        .form-header {
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
        }
        .error {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        .success {
            color: green;
            margin-bottom: 1rem;
        }
        
    </style>
</head>
<body>

    <div class="form-container">
        <div class="form-header">
            <h2>Add New Dish</h2>
            <a href="dashboard.php" class="back-btn">
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
                    <option value="">Select a category</option>
                    <option value="Fast Food">Fast Food</option>
                    <option value="Rice Menu">Rice Menu</option>
                    <option value="Desserts">Desserts</option>
                    <option value="Pizza">Pizza</option>
                    <option value="Coffee">Coffee</option>
                </select>
            </div>

            <div class="form-group">
                <label for="image">Dish Image</label>
                <input type="file" id="image" name="image" accept="image/*" required>
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
