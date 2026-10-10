<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/mysql_db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
// 1. Clean up user input (e.g., "  japanese  " -> "Japanese")
    $new_cat = trim($_POST['category_name']);
    $new_cat = ucwords(strtolower($new_cat)); // Capitalize first letter of each word

    if (!empty($new_cat)) {
        try {
            // 2. Check if this category already exists in the database
            $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ?");
            $stmt->execute([$new_cat]);
            $existing = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 3. Only insert if it doesn't exist
            if (empty($existing)) {
                $sql = "INSERT INTO categories (name, created_by) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$new_cat, $_SESSION['user_id']]);
            }
        } catch (Exception $e) {
            // Fail silently or handle error
        }
    }
}

// Redirect back to dashboard
header("Location: dashboard.php");
exit();
?>