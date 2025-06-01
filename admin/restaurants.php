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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = $_POST['name'];
                $address = $_POST['address'];
                $phone = $_POST['phone'];
                $email = $_POST['email'];
                $description = $_POST['description'];
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO restaurants (name, address, phone, email, description) VALUES (?, ?, ?, ?, ?)");
                    if ($stmt->execute([$name, $address, $phone, $email, $description])) {
                        $success = "Restaurant added successfully!";
                    } else {
                        $error = "Error adding restaurant: " . implode("", $stmt->errorInfo());
                    }
                } catch (PDOException $e) {
                    $error = "Database error: " . $e->getMessage();
                }
                break;
            
            case 'delete':
                $id = $_POST['id'];
                try {
                    $stmt = $pdo->prepare("DELETE FROM restaurants WHERE id = ?");
                    if ($stmt->execute([$id])) {
                        $success = "Restaurant deleted successfully!";
                    } else {
                        $error = "Error deleting restaurant: " . implode("", $stmt->errorInfo());
                    }
                } catch (PDOException $e) {
                    $error = "Database error: " . $e->getMessage();
                }
                break;
        }
    }
}

// Fetch all restaurants
try {
    $stmt = $pdo->query("SELECT * FROM restaurants ORDER BY name");
    $restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching restaurants: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Restaurants - FDelivery</title>
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

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 0 10px;
            border-radius: 10px;
        }

        .header h2 {
            font-size: 2rem;
            color: var(--primary-color);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            background: var(--white-color);
            color: var(--primary-color);
            text-decoration: none;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .form-container {
            background: var(--white-color);
            border-radius: 1rem;
            box-shadow: var(--shadow-md);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .form-header h3 {
            color: var(--primary-color);
            font-size: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--grey-color-2);
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 1px solid var(--grey-color);
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(51, 122, 183, 0.1);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
        }

        .submit-btn {
            background: var(--primary-color);
            color: var(--white-color);
            padding: 1rem 2rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            background: #23527c;
        }

        .table-container {
            background: var(--white-color);
            border-radius: 1rem;
            box-shadow: var(--shadow-md);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .table-header {
            padding: 1.5rem;
            background: var(--primary-color);
            color: var(--white-color);
            border-radius: 1rem 1rem 0 0;
        }

        .table-header h3 {
            margin: 0;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .restaurants-table {
            width: 100%;
            border-collapse: collapse;
        }

        .restaurants-table th,
        .restaurants-table td {
            padding: 1.25rem;
            text-align: left;
            border-bottom: 1px solid var(--grey-color);
        }

        .restaurants-table th {
            background: var(--grey-color-1);
            font-weight: 500;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
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

        .message {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .error {
            background: var(--danger-color);
            color: var(--white-color);
        }

        .success {
            background: var(--success-color);
            color: var(--white-color);
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 1rem;
            }

            .form-container,
            .table-container {
                margin: 1rem auto;
            }

            .action-buttons {
                flex-wrap: wrap;
            }

            .submit-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="admin-container">
        <div class="header">
            <h2><i class='bx bx-building'></i> Verify Restaurants</h2>
            <a href="http://localhost/food-delivery-website/restaurant/register.php" class="btn">
           Add New Restaurants
            </a>
            <a href="index.php" class="back-btn">
                <i class='bx bx-arrow-back'></i> Back to Dashboard
            </a>
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

        <div class="form-container">
            <div class="form-header">
                <h3><i class='bx bx-plus'></i> Verify Restaurant</h3>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="name">Restaurant Name</label>
                    <input type="text" id="name" name="name" required placeholder="Enter restaurant name">
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" required placeholder="Enter restaurant address">
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" required placeholder="Enter phone number">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required placeholder="Enter email address">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" required placeholder="Enter restaurant description"></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="submit-btn">
                        <i class='bx bx-save'></i> Verify Restaurant
                    </button>
                </div>
            </form>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h3><i class='bx bx-list-ul'></i> Restaurants List</h3>
            </div>
            <table class="restaurants-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($restaurants as $restaurant): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($restaurant['name']); ?></td>
                            <td><?php echo htmlspecialchars($restaurant['address']); ?></td>
                            <td><?php echo htmlspecialchars($restaurant['phone']); ?></td>
                            <td><?php echo htmlspecialchars($restaurant['email']); ?></td>
                            <td class="action-buttons">
                                <a href="edit_restaurant.php?id=<?php echo $restaurant['id']; ?>" class="action-btn edit-btn">
                                    <i class='bx bx-edit'></i> Edit
                                </a>
                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this restaurant?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $restaurant['id']; ?>">
                                    <button type="submit" class="action-btn delete-btn">
                                        <i class='bx bx-trash'></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

    <script>
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this restaurant? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
