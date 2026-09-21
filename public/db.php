<?php

$database_host = '127.0.0.1';
$database_name = 'loginsystem';
$database_user = 'jamlick'; // Corrected from variable collision ($database_host)
$database_password = '20590770';

try {
    $pdo = new PDO("mysql:host=$database_host;dbname=$database_name;charset=utf8mb4", $database_user, $database_password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    error_log("Database Connection Failed: " . $e->getMessage());
    die("A system connectivity issue occurred. Please check back later.");
}
