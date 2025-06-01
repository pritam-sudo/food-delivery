<?php
header('Content-Type: application/json');

// Allow requests only from the same origin
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// Database configuration
$db_config = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',
    'name' => 'food_delivery'
];

// Function to execute SQL file
function executeSQLFile($conn, $filename) {
    try {
        $sql = file_get_contents($filename);
        if ($sql === false) {
            throw new Exception("Could not read SQL file: " . $filename);
        }

        // Split SQL by semicolon
        $queries = array_filter(
            array_map('trim', 
                explode(';', $sql)
            )
        );

        // Execute each query
        foreach ($queries as $query) {
            if (!empty($query)) {
                if (!$conn->query($query)) {
                    throw new Exception("Error executing query: " . $conn->error);
                }
            }
        }
        return true;
    } catch (Exception $e) {
        throw new Exception("Error in file " . basename($filename) . ": " . $e->getMessage());
    }
}

try {
    // Create connection
    $conn = new mysqli($db_config['host'], $db_config['user'], $db_config['pass']);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Create database if not exists
    $sql = "CREATE DATABASE IF NOT EXISTS " . $db_config['name'];
    if (!$conn->query($sql)) {
        throw new Exception("Error creating database: " . $conn->error);
    }

    // Select the database
    if (!$conn->select_db($db_config['name'])) {
        throw new Exception("Error selecting database: " . $conn->error);
    }

    // SQL files to import
    $sql_files = [
        __DIR__ . '/admin/db.sql',
        __DIR__ . '/admin/restaurant_admin.sql',
        __DIR__ . '/delivery_db.sql'
    ];

    $imported_files = [];
    foreach ($sql_files as $file) {
        if (file_exists($file)) {
            executeSQLFile($conn, $file);
            $imported_files[] = "Successfully imported: " . basename($file);
        } else {
            throw new Exception("SQL file not found: " . basename($file));
        }
    }

    // Close connection
    $conn->close();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Database setup completed successfully!',
        'details' => $imported_files
    ]);

} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database setup failed',
        'error' => $e->getMessage()
    ]);
}
?>
