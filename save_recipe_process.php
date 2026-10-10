<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/mysql_db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipe_id = isset($_POST['id']) ? trim($_POST['id']) : "";
    $title = trim($_POST['title'] ?? '');
    $category = $_POST['category'] ?? '';
    $prep_time = trim($_POST['prep_time'] ?? '');
    $base_servings = (int)($_POST['servings'] ?? 1);

    $ingredients = $_POST['ingredient'] ?? [];
    $has_ingredients = is_array($ingredients) && count($ingredients) > 0;

    if (!empty($title) && !empty($category) && $has_ingredients) {
        try {
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            if (!empty($recipe_id)) {
                // Update recipe details using PDO execute array
                $stmt = $conn->prepare("UPDATE recipes SET title = ?, category = ?, servings = ?, prep_time = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$title, $category, $base_servings, $prep_time, $recipe_id, $_SESSION['user_id']]);
            
                // Clear old ingredients and steps to replace with updated ones
                $conn->prepare("DELETE FROM ingredients WHERE recipe_id = ?")->execute([$recipe_id]);
                $conn->prepare("DELETE FROM steps WHERE recipe_id = ?")->execute([$recipe_id]);

            } else {
                // Insert new recipe
                $stmt = $conn->prepare("INSERT INTO recipes (user_id, title, servings, category, prep_time, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$_SESSION['user_id'], $title, $base_servings, $category, $prep_time]);
                
                $recipe_id = $conn->lastInsertId();
            }
         // 1. Insert Ingredients
        $amounts_whole = $_POST['amount_whole'] ?? [];
        $amounts_fraction = $_POST['amount_fraction'] ?? [];
        $units = $_POST['unit'] ?? [];

        $ing_stmt = $conn->prepare("INSERT INTO ingredients (recipe_id, quantity, unit, name) VALUES (?, ?, ?, ?)");

        for ($i = 0; $i < count($ingredients); $i++) {
            if (!empty(trim($ingredients[$i]))) {
                $whole = trim($amounts_whole[$i] ?? '0');
                $fraction = trim($amounts_fraction[$i] ?? '');

                // Build the combined quantity string cleanly
                $qty_parts = [];
                if ($whole !== '' && $whole !== '0') {
                    $qty_parts[] = $whole;
                }
                if ($fraction !== '' && $fraction !== '0') {
                    $qty_parts[] = $fraction;
                }
                $qty_display = !empty($qty_parts) ? implode(' ', $qty_parts) : '0';
                
                $unit = trim($units[$i] ?? '');
                $ing_name = trim($ingredients[$i]);

                $ing_stmt->execute([$recipe_id, $qty_display, $unit, $ing_name]);
            }
        }

           // 2. Insert Cooking Steps & Handle Step Image Uploads
            $steps = $_POST['step_description'] ?? [];
            $existing_images = $_POST['existing_step_image'] ?? [];
            $upload_dir = 'uploads/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            if (!empty($steps) && is_array($steps)) {
                $step_stmt = $conn->prepare("INSERT INTO steps (recipe_id, step_number, description, image_path) VALUES (?, ?, ?, ?)");
    
        foreach ($steps as $index => $desc) {
            if (!empty(trim($desc))) {
                $step_num = $index + 1;
            // Default to the previously saved image path if it exists
             $image_path = !empty($existing_images[$index]) ? $existing_images[$index] : null;

            // Check if a new file was uploaded for this step
            if (isset($_FILES['step_image']['name'][$index]) && $_FILES['step_image']['error'][$index] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES['step_image']['tmp_name'][$index];
                $original_name = basename($_FILES['step_image']['name'][$index]);
                $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
                if (in_array($file_ext, $allowed_extensions)) {
                    $new_filename = 'recipe_' . $recipe_id . '_step_' . $step_num . '_' . time() . '.' . $file_ext;
                    $destination = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($tmp_name, $destination)) {
                        $image_path = $destination; // Overwrite with the new uploaded image
                    }
                }
            }

            $step_stmt->execute([$recipe_id, $step_num, trim($desc), $image_path]);
        }
    }
}     
            header("Location: dashboard.php");
            exit();
            
        } catch (PDOException $e) {
            header("Location: add_recipe.php?error=" . urlencode("Database Error: " . $e->getMessage()));
            exit();
        }
    } else {
        header("Location: add_recipe.php?error=" . urlencode("Please fill in all required fields and add at least one ingredient."));
        exit();
    }
}