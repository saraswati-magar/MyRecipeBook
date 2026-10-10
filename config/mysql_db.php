<?php
$host = 'localhost';
$db   = 'recipe_app';
$user = 'root';
$pass = ''; // Leave blank if you don't have a password for root

try {
    $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>