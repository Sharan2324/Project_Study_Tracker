<?php
$host = "localhost";
$user = "root";
$password = ""; 
$db = "study_tracker";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,   
        PDO::ATTR_EMULATE_PREPARES => false,                
    ]);
} catch (PDOException $e) {
    
    error_log("Database connection failed in db_config.php: " . $e->getMessage());

        die("<h1>Service Unavailable</h1><p>Our services are currently experiencing technical difficulties. Please try again later.</p>");
}
?>
