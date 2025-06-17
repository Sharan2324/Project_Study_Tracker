<?php
// db_config.php - Centralized Database Connection using PDO

// Database credentials
$host = "localhost";
$user = "root";
$password = ""; // IMPORTANT: Use your actual database password here
$db = "study_tracker";

// Attempt to establish PDO connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // Throw exceptions on errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,   // Fetch results as associative arrays by default
        PDO::ATTR_EMULATE_PREPARES => false,                // Disable emulation for security and performance
    ]);
} catch (PDOException $e) {
    // Log the connection error securely
    error_log("Database connection failed in db_config.php: " . $e->getMessage());

    // Display a user-friendly message without exposing database details
    die("<h1>Service Unavailable</h1><p>Our services are currently experiencing technical difficulties. Please try again later.</p>");
}
?>
