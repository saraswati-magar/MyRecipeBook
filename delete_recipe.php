<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$host = 'localhost';
$db   = 'recipe_app';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$recipe_id = intval($_GET['id'] ?? 0);

if ($recipe_id > 0) {
    // 1. Verify the recipe belongs to the logged-in user for security
    $stmt = $conn->prepare("SELECT id FROM recipes WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $recipe_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // 2. Delete related steps images from the server folder if they exist
        $img_stmt = $conn->prepare("SELECT image_path FROM steps WHERE recipe_id = ?");
        $img_stmt->bind_param("i", $recipe_id);
        $img_stmt->execute();
        $img_res = $img_stmt->get_result();
        while ($row = $img_res->fetch_assoc()) {
            if (!empty($row['image_path']) && file_exists($row['image_path'])) {
                @unlink($row['image_path']);
            }
        }

        // 3. Delete from database tables (ingredients, steps, and recipe)
        $conn->prepare("DELETE FROM ingredients WHERE recipe_id = ?")->execute([$recipe_id] ?? [$recipe_id]); // or standard bind_param
        
        // Using mysqli standard prepare/bind for safety:
        $del_ing = $conn->prepare("DELETE FROM ingredients WHERE recipe_id = ?");
        $del_ing->bind_param("i", $recipe_id);
        $del_ing->execute();

        $del_steps = $conn->prepare("DELETE FROM steps WHERE recipe_id = ?");
        $del_steps->bind_param("i", $recipe_id);
        $del_steps->execute();

        $del_recipe = $conn->prepare("DELETE FROM recipes WHERE id = ? AND user_id = ?");
        $del_recipe->bind_param("ii", $recipe_id, $_SESSION['user_id']);
        $del_recipe->execute();
    }
}

header("Location: dashboard.php");
exit();
?>