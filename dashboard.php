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

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');

$sql = "SELECT DISTINCT r.* FROM recipes r 
        LEFT JOIN ingredients i ON r.id = i.recipe_id 
        WHERE r.user_id = ?";
$types = "i";
$params = [$_SESSION['user_id']];

if (!empty($search)) {
    $sql .= " AND (r.title LIKE ? OR i.name LIKE ?)";
    $types .= "ss";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($category)) {
    $sql .= " AND r.category = ?";
    $types .= "s";
    $params[] = $category;
}

$sql .= " ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$all_recipes = $result->fetch_all(MYSQLI_ASSOC);

// For each recipe, fetch the image from the last step (final output photo)
foreach ($all_recipes as &$recipe) {
    $img_stmt = $conn->prepare("SELECT image_path FROM steps WHERE recipe_id = ? AND image_path IS NOT NULL AND image_path != '' ORDER BY step_number DESC LIMIT 1");
    $img_stmt->bind_param("i", $recipe['id']);
    $img_stmt->execute();
    $img_res = $img_stmt->get_result();
    if ($img_row = $img_res->fetch_assoc()) {
        $recipe['final_image'] = $img_row['image_path'];
    } else {
        $recipe['final_image'] = '';
    }
}
unset($recipe);

// Group recipes by category
$grouped_recipes = [];
if (empty($search)) {
    foreach ($all_recipes as $recipe) {
        $cat = !empty($recipe['category']) ? ucfirst(strtolower($recipe['category'])) : 'General';
        $grouped_recipes[$cat][] = $recipe;
    }
} else {
    $grouped_recipes['Search Results'] = $all_recipes;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - My Recipe Book</title>
    <style>
        html, body {
            display: block !important;
            min-height: 100% !important;
            height: auto !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #f8fafc !important;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif !important;
            color: #334155;
        }

        .nav-header {
            background: #ffffff;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 1rem 2.5rem;
        }

        .nav-top-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .logo-section h1 {
            margin: 0;
            font-size: 1.6rem;
            color: #0f172a;
            letter-spacing: -0.5px;
        }

        .logo-section a {
            text-decoration: none;
            color: inherit;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn-add {
            background: #6366f1;
            color: white;
            padding: 0.60rem 1.3rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: background 0.2s;
            white-space: nowrap;
        }

        .btn-add:hover {
            background: #4f46e5;
        }

        .btn-logout {
            background: #f1f5f9;
            color: #ef4444;
            padding: 0.60rem 1.1rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: background 0.2s;
        }

        .btn-logout:hover {
            background: #fee2e2;
        }

        .search-form {
            flex: 1;
            max-width: 500px;
            display: flex;
            gap: 8px;
        }

        .search-input {
            width: 100%;
            padding: 0.60rem 1.2rem;
            border: 1px solid #cbd5e1;
            border-radius: 50px;
            outline: none;
            font-size: 0.95rem;
            transition: all 0.2s;
        }

        .search-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .search-btn {
            padding: 0.60rem 1.4rem;
            background: #6366f1;
            color: white;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            transition: background 0.2s;
        }

        .search-btn:hover {
            background: #4f46e5;
        }

        .hero-banner {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: white;
            padding: 25px 0;
            margin-bottom: 30px;
            box-shadow: inset 0 -10px 20px rgba(0,0,0,0.1);
        }

        .hero-banner-content {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .hero-title {
            font-size: 1.8rem;
            margin: 0;
            font-weight: 700;
            white-space: nowrap;
        }

        .categories-ribbon {
            display: flex;
            align-items: center;
            gap: 8px;
            overflow-x: auto;
            max-width: 100%;
            padding-bottom: 4px;
        }

        .category-pill {
            padding: 6px 16px;
            background: rgba(255, 255, 255, 0.1);
            color: #cbd5e1;
            text-decoration: none;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .category-pill:hover, .category-pill.active {
            background: #6366f1;
            color: #fff;
        }

        .dashboard-wrapper {
            max-width: 1280px;
            margin: 0 auto 50px auto;
            padding: 0 25px;
        }

        .category-section {
            margin-bottom: 40px;
        }

        .category-header {
            font-size: 1.35rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 8px;
        }

        .horizontal-scroll-container {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding-bottom: 15px;
            scroll-behavior: smooth;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f1f5f9;
        }

        .horizontal-scroll-container::-webkit-scrollbar {
            height: 8px;
        }

        .horizontal-scroll-container::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }

        .horizontal-scroll-container::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .recipe-card {
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
            border: 1px solid #e2e8f0;
            min-width: 240px;
            max-width: 240px;
            flex: 0 0 auto;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
        }

        .recipe-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }

        .recipe-image-container {
            width: 100%;
            height: 180px;
            background: #e2e8f0;
            overflow: hidden;
            position: relative;
        }

        .recipe-image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .recipe-card:hover .recipe-image-container img {
            transform: scale(1.05);
        }

        .recipe-card-footer {
            padding: 14px 16px;
            background: #ffffff;
            text-align: center;
        }

        .recipe-title {
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
        }

        .empty-state h3 {
            color: #0f172a;
            margin-bottom: 10px;
            font-size: 1.4rem;
        }

        .empty-state p {
            color: #64748b;
            margin-bottom: 20px;
        }

        .site-footer {
            background: #0f172a;
            color: #94a3b8;
            padding: 50px 20px 25px 20px;
            margin-top: 80px;
            border-top: 1px solid #1e293b;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 40px;
            padding-bottom: 30px;
            border-bottom: 1px solid #1e293b;
        }

        .footer-col h3 {
            color: #ffffff;
            font-size: 1.2rem;
            margin-bottom: 15px;
        }

        .footer-col p {
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            font-size: 0.85rem;
            color: #64748b;
        }
    </style>
</head>
<body>

    <header class="nav-header">
        <div class="nav-top-row">
            <div class="logo-section">
                <h1><a href="dashboard.php">My Recipe Book</a></h1>
            </div>

            <form method="GET" action="dashboard.php" class="search-form">
                <input type="text" name="search" class="search-input" placeholder="Search recipes or ingredients..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-btn">Search</button>
            </form>

            <div class="nav-actions">
                <a href="add_recipe.php" class="btn-add">+ Add Recipe</a>
                <a href="logout.php" class="btn-logout">Log Out</a>
            </div>
        </div>
    </header>

    <div class="hero-banner">
        <div class="hero-banner-content">
            <h2 class="hero-title">Your Recipes</h2>
            
            <div class="categories-ribbon">
                <a href="dashboard.php" class="category-pill <?= empty($category) ? 'active' : '' ?>">All Recipes</a>
                <a href="dashboard.php?category=Breakfast" class="category-pill <?= $category === 'Breakfast' ? 'active' : '' ?>">Breakfast</a>
                <a href="dashboard.php?category=Lunch" class="category-pill <?= $category === 'Lunch' ? 'active' : '' ?>">Lunch</a>
                <a href="dashboard.php?category=Dinner" class="category-pill <?= $category === 'Dinner' ? 'active' : '' ?>">Dinner</a>
                <a href="dashboard.php?category=Dessert" class="category-pill <?= $category === 'Dessert' ? 'active' : '' ?>">Dessert</a>
                <a href="dashboard.php?category=Beverage" class="category-pill <?= $category === 'Beverage' ? 'active' : '' ?>">Beverage</a>
            </div>
        </div>
    </div>

    <div class="dashboard-wrapper">
        <?php if (count($all_recipes) > 0): ?>
            <?php foreach ($grouped_recipes as $cat_name => $recipes_in_cat): ?>
                <div class="category-section">
                    <div class="category-header">
                        <span> <?= htmlspecialchars($cat_name) ?></span>
                    </div>
                    
                    <div class="horizontal-scroll-container">
                        <?php foreach ($recipes_in_cat as $recipe): 
                            $thumb_img = !empty($recipe['final_image']) ? htmlspecialchars($recipe['final_image']) : 'https://images.unsplash.com/photo-1495521821757-a1efb6729352?auto=format&fit=crop&w=600&q=80';
                        ?>
                            <a href="view_recipe.php?id=<?= $recipe['id'] ?>" class="recipe-card">
                                <div class="recipe-image-container">
                                    <img src="<?= $thumb_img ?>" alt="<?= htmlspecialchars($recipe['title']) ?>">
                                </div>
                                <div class="recipe-card-footer">
                                    <h3 class="recipe-title" title="<?= htmlspecialchars($recipe['title']) ?>"><?= htmlspecialchars($recipe['title']) ?></h3>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <h3>No recipes found</h3>
                <p>You haven't added any recipes to your vault yet, or your search query didn't match anything.</p>
                <a href="add_recipe.php" class="btn-add" style="display: inline-block;">Add Your First Recipe</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>