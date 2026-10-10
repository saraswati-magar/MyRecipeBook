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

// Fetch recipe ensuring it belongs to the logged-in user (Private check)
$stmt = $conn->prepare("SELECT * FROM recipes WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $recipe_id, $_SESSION['user_id']);
$stmt->execute();
$recipe = $stmt->get_result()->fetch_assoc();

if (!$recipe) {
    header("Location: dashboard.php");
    exit();
}

// Fetch ingredients
$ing_stmt = $conn->prepare("SELECT * FROM ingredients WHERE recipe_id = ?");
$ing_stmt->bind_param("i", $recipe_id);
$ing_stmt->execute();
$ingredients = $ing_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch steps
$step_stmt = $conn->prepare("SELECT * FROM steps WHERE recipe_id = ? ORDER BY step_number ASC");
$step_stmt->bind_param("i", $recipe_id);
$step_stmt->execute();
$steps = $step_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get the final step image for the right-side hero display
$hero_image = 'https://images.unsplash.com/photo-1495521821757-a1efb6729352?auto=format&fit=crop&w=800&q=80';
if (!empty($steps)) {
    $last_step = end($steps);
    if (!empty($last_step['image_path'])) {
        $hero_image = $last_step['image_path'];
    }
}

$base_servings = max(1, intval($recipe['servings'] ?? 4));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($recipe['title']) ?> - My Recipe Book</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            background: #f8fafc;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #334155;
        }

        .nav-header {
            background: #ffffff;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            position: sticky;
            top: 0;
            z-index: 1000;
            padding: 1rem 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-section h1 {
            margin: 0;
            font-size: 1.6rem;
            color: #0f172a;
        }

        .logo-section a {
            text-decoration: none;
            color: inherit;
        }

        .btn-back {
            background: #f1f5f9;
            color: #334155;
            padding: 0.60rem 1.2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: background 0.2s;
        }

        .btn-back:hover {
            background: #e2e8f0;
        }

        .recipe-container {
            max-width: 1000px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            padding: 40px;
        }

        .recipe-header-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 40px;
            align-items: center;
            margin-bottom: 40px;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 35px;
        }

        .recipe-info-col {
            display: flex;
            flex-direction: column;
        }

        .recipe-image-col {
            width: 100%;
        }

        .recipe-hero-img {
            width: 100%;
            height: 320px;
            object-fit: cover;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
            background: #e2e8f0;
        }

        .badge {
            background: #e0e7ff;
            color: #4f46e5;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            display: inline-block;
            margin-bottom: 12px;
            text-transform: uppercase;
            align-self: flex-start;
        }

        .recipe-title {
            font-size: 2.5rem;
            color: #0f172a;
            margin: 0;
            font-weight: 800;
            line-height: 1.2;
        }

        .section-title {
            font-size: 1.3rem;
            color: #0f172a;
            margin: 35px 0 15px 0;
            font-weight: 700;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 8px;
        }

        .recipe-text {
            font-size: 1.05rem;
            line-height: 1.7;
            color: #475569;
            margin: 0;
            white-space: pre-line;
        }

        .calculator-box {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            padding: 20px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
        }

        .calc-input {
            width: 70px;
            padding: 8px;
            text-align: center;
            font-size: 1rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-weight: bold;
        }

        .ingredients-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .ingredients-list li {
            padding: 12px 16px;
            background: #f8fafc;
            margin-bottom: 8px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            font-size: 1.05rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .step-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .step-header {
            font-weight: 700;
            color: #6366f1;
            margin-bottom: 8px;
        }

        .step-img {
            max-width: 100%;
            height: 250px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 12px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 40px;
            padding-top: 25px;
            border-top: 1px solid #f1f5f9;
        }

        .btn-edit-recipe {
            background: #6366f1;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s;
        }

        .btn-edit-recipe:hover {
            background: #4f46e5;
        }

        .btn-delete-recipe {
            background: #fef2f2;
            color: #ef4444;
            border: 1px solid #fecaca;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s;
        }

        .btn-delete-recipe:hover {
            background: #fee2e2;
        }

        @media (max-width: 768px) {
            .recipe-header-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <header class="nav-header">
        <div class="logo-section">
            <h1><a href="dashboard.php">🍳 My Recipe Book</a></h1>
        </div>
        <a href="dashboard.php" class="btn-back">&larr; Back to Dashboard</a>
    </header>

    <div class="recipe-container">
        <!-- Top Header Grid: Title & Category on Left, Final Output Picture on Right -->
        <div class="recipe-header-grid">
            <div class="recipe-info-col">
                <span class="badge"><?= htmlspecialchars($recipe['category'] ?? 'General') ?></span>
                <h1 class="recipe-title"><?= htmlspecialchars($recipe['title']) ?></h1>
            </div>
            
            <div class="recipe-image-col">
                <img src="<?= htmlspecialchars($hero_image) ?>" alt="<?= htmlspecialchars($recipe['title']) ?>" class="recipe-hero-img">
            </div>
        </div>

        <!-- Cooking Steps -->
        <h3 class="section-title" style="margin-top: 0;">Cooking Steps</h3>
        <?php if (!empty($steps)): ?>
            <?php foreach ($steps as $step): ?>
                <div class="step-card">
                    <div class="step-header">Step <?= $step['step_number'] ?></div>
                    <p class="recipe-text" style="margin-bottom: 0;"><?= htmlspecialchars($step['description']) ?></p>
                    <?php if (!empty($step['image_path'])): ?>
                        <br><img src="<?= htmlspecialchars($step['image_path']) ?>" alt="Step <?= $step['step_number'] ?>" class="step-img">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="recipe-text">No instructions provided.</p>
        <?php endif; ?>

        <!-- Ingredients & Calculator -->
        <h3 class="section-title">Ingredients & Serving Calculator</h3>
        
        <div class="calculator-box">
            <div>
                <strong>Serving Size Calculator</strong>
                <div style="font-size: 0.85rem; color: #64748b;">Adjust servings to instantly scale ingredient quantities.</div>
            </div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <label for="servingsInput">Servings:</label>
                <input type="number" id="servingsInput" class="calc-input" value="<?= $base_servings ?>" min="1" max="100">
            </div>
        </div>

        <ul class="ingredients-list">
            <?php foreach ($ingredients as $ing): ?>
                <li class="ingredient-item">
                    <span class="ingredient-qty" data-base="<?= htmlspecialchars($ing['quantity']) ?>"><?= htmlspecialchars($ing['quantity']) ?></span>
                    <span class="ingredient-unit"><?= htmlspecialchars($ing['unit']) ?></span>
                    <span class="ingredient-name"><?= htmlspecialchars($ing['name']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="action-buttons">
            <a href="add_recipe.php?id=<?= $recipe['id'] ?>" class="btn-edit-recipe">Edit Recipe</a>
            <a href="delete_recipe.php?id=<?= $recipe['id'] ?>" class="btn-delete-recipe" onclick="return confirm('Are you sure you want to delete this recipe? This action cannot be undone.');">Delete Recipe</a>
        </div>
    </div>

   <!-- Live Scaler Script -->
    <script>
        function parseQuantity(qtyStr) {
            if (!qtyStr) return 0;
            qtyStr = qtyStr.trim();
            
            if (qtyStr.includes('/')) {
                const parts = qtyStr.split(' ');
                let total = 0;
                for (let part of parts) {
                    if (part.includes('/')) {
                        const [num, den] = part.split('/').map(Number);
                        if (den) total += num / den;
                    } else {
                        total += parseFloat(part) || 0;
                    }
                }
                return total;
            }
            return parseFloat(qtyStr) || 0;
        }

        function decimalToFraction(decimal) {
            if (isNaN(decimal)) return '';
            let whole = Math.floor(decimal);
            let remainder = decimal - whole;
            
            if (remainder < 0.05) {
                return whole === 0 ? '0' : whole.toString();
            }
            if (remainder > 0.95) {
                return (whole + 1).toString();
            }
            
            // Common culinary fractions
            const fractions = [
                { val: 1/8, str: '1/8' },
                { val: 1/4, str: '1/4' },
                { val: 1/3, str: '1/3' },
                { val: 3/8, str: '3/8' },
                { val: 1/2, str: '1/2' },
                { val: 5/8, str: '5/8' },
                { val: 2/3, str: '2/3' },
                { val: 3/4, str: '3/4' },
                { val: 7/8, str: '7/8' }
            ];
            
            let closest = fractions[0];
            let minDiff = Math.abs(remainder - closest.val);
            
            for (let i = 1; i < fractions.length; i++) {
                let diff = Math.abs(remainder - fractions[i].val);
                if (diff < minDiff) {
                    minDiff = diff;
                    closest = fractions[i];
                }
            }
            
            if (minDiff < 0.06) {
                if (whole === 0) return closest.str;
                return whole + ' ' + closest.str;
            }
            
            // Fallback to rounded decimal if no standard fraction matches closely
            return decimal.toFixed(2).replace(/\.?0+$/, '');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const baseServings = <?= $base_servings ?>;
            const servingsInput = document.getElementById('servingsInput');
            const qtyElements = document.querySelectorAll('.ingredient-qty');

            servingsInput.addEventListener('input', function() {
                let currentServings = parseInt(this.value);
                if (isNaN(currentServings) || currentServings < 1) {
                    currentServings = 1;
                }

                const ratio = currentServings / baseServings;

                qtyElements.forEach(el => {
                    let baseVal = parseQuantity(el.getAttribute('data-base'));
                    if (!isNaN(baseVal)) {
                        let scaled = baseVal * ratio;
                        el.textContent = decimalToFraction(scaled);
                    }
                });
            });
        });
    </script>
</body>
</html>