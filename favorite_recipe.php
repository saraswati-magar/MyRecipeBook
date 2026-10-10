<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/mysql_db.php';

if (isset($_GET['id'])) {
    $recipe_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    // 1. Check if already favorited to avoid duplicates
    $check_stmt = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND recipe_id = ?");
    $check_stmt->execute([$user_id, $recipe_id]);
    
    if (!$check_stmt->fetch()) {
        // 2. Insert into favorites
        $stmt = $conn->prepare("INSERT INTO favorites (user_id, recipe_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $recipe_id]);
    }
}

// Redirect back to wherever they came from
header("Location: dashboard.php");
exit();
?>