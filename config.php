<?php
// Database Configuration Constants
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'user_database');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create database connection
try {
    $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn) {
        throw new Exception("Koneksi gagal: " . mysqli_connect_error());
    }
    
    // Optional: Set character set
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die($e->getMessage());
}

// Connection function for reuse
function connectDB() {
    try {
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$conn) {
            throw new Exception("Koneksi gagal: " . mysqli_connect_error());
        }
        return $conn;
    } catch (Exception $e) {
        die($e->getMessage());
    }
}

// Create initial database connection
try {
    $conn = connectDB();
    // Set character set
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die($e->getMessage());
}
?>