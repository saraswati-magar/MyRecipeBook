<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/mysql_db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $base_servings = intval($_POST['base_servings']);
    $category = trim($_POST['category']);
    $ingredients = trim($_POST['ingredients']);
    $instructions = trim($_POST['instructions']);
    
    // Capture visibility from the form, default to private if not set
    $is_public = (isset($_POST['visibility']) && $_POST['visibility'] === 'public');

    // 4. Insert Ingredients using the new arrays
if (!empty($_POST['name'])) {
    try {
           // 1. Save the main recipe info first
           $stmt = $conn->prepare("INSERT INTO recipes (title, servings, category, is_public, user_id) VALUES (?, ?, ?, ?, ?)");
           $stmt->execute([$title, $base_servings, $category, $is_public, $_SESSION['user_id']]);
           
           // 2. Get the ID of the recipe that is just created
            $recipe_id = $conn->lastInsertId();

            // 3. The loop will work because $recipe_id is defined!
            $stmt = $conn->prepare("INSERT INTO ingredients (recipe_id, quantity, unit, name) VALUES (?, ?, ?, ?)");
            foreach ($_POST['name'] as $index => $name) {
                $qty = $_POST['qty'][$index];
                $unit = $_POST['unit'][$index];
                if (!empty($name)) {
                    $stmt->execute([$recipe_id, $qty, $unit, $name]);
                }
            }
            
            header("Location: dashboard.php");
            exit();

        } catch (Exception $e) {
            die("Error saving recipe: " . $e->getMessage());
        }
    } else {
        die("Title and Ingredients cannot be blank.");
    }
     else {
        header("Location: dashboard.php");
        exit();
    }