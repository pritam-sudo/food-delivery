<?php
session_start();
require_once 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM delivery_persons WHERE id = ?");
        $stmt->execute([$delete_id]);
        header('Location: view_delivery_persons.php?success=1');
        exit();
    } catch (PDOException $e) {
        $error = "Error deleting delivery person: " . $e->getMessage();
    }
}

// Fetch all delivery persons
try {
    $stmt = $pdo->query("SELECT * FROM delivery_persons ORDER BY created_at DESC");
    $delivery_persons = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error fetching delivery persons: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Persons - Admin Dashboard</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://unpkg.com/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #337ab7;
            --hover-color: #23527c;
            --bg-color: #f5f5f5;
            --text-color: #333;
            --white-color: #fff;
            --grey-color: #888;
            --grey-color-1: #f8f9fa;
            --grey-color-2: #666;
            --black-color: #000;
            --green-color: #28a745;
            --yellow-color: #ffc107;
            --red-color: #dc3545;
            --box-shadow: 0 2px 4px rgba(0,0,0,0.1);
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
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-btn {
            background: var(--grey-color-1);
            color: var(--text-color);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .back-btn:hover {
            background: var(--grey-color);
        }

        .message {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            text-align: center;
        }

        .message.success {
            background: var(--green-color);
            color: var(--white-color);
        }

        .delivery-list {
            background: var(--white-color);
            border-radius: 1rem;
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
        }

        .delivery-list table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .delivery-list th,
        .delivery-list td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--grey-color);
        }

        .delivery-list th {
            background: var(--grey-color-1);
            font-weight: 600;
        }

        .delivery-list tr:hover {
            background: var(--grey-color-1);
        }

        .delivery-list .actions {
            display: flex;
            gap: 0.5rem;
        }

        .delivery-list .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .delivery-list .delete-btn {
            background: var(--red-color);
            color: var(--white-color);
        }

        .delivery-list .delete-btn:hover {
            background: #c82333;
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--grey-color-2);
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="header">
            <h2><i class='bx bx-user'></i> Delivery Persons</h2>
            <a href="index.php" class="back-btn">
                <i class='bx bx-arrow-back'></i> Back to Dashboard
            </a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="message success">Delivery person deleted successfully</div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="delivery-list">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($delivery_persons)): ?>
                        <tr>
                            <td colspan="6" class="empty-state">
                                <i class='bx bx-user-x' style="font-size: 2rem; color: var(--grey-color-2);"></i>
                                <p>No delivery persons found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($delivery_persons as $person): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($person['id']); ?></td>
                                <td><?php echo htmlspecialchars($person['name']); ?></td>
                                <td><?php echo htmlspecialchars($person['email']); ?></td>
                                <td><?php echo htmlspecialchars($person['phone']); ?></td>
                                <td><?php echo date('M d, Y H:i', strtotime($person['created_at'])); ?></td>
                                <td class="actions">
                                    <form method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this delivery person?')">
                                        <input type="hidden" name="delete_id" value="<?php echo $person['id']; ?>">
                                        <button type="submit" class="btn delete-btn">
                                            <i class='bx bx-trash'></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
