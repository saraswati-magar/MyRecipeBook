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

$edit_mode = false;
$recipe = ['title' => '', 'category' => '', 'servings' => 1, 'prep_time' => ''];
$ingredients = [];
$steps = [];

if (isset($_GET['id'])) {
    $edit_mode = true;
    $recipe_id = intval($_GET['id']); 
    // Fetch recipe using MySQLi
    $stmt = $conn->prepare("SELECT * FROM recipes WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $recipe_id, $_SESSION['user_id']);
    $stmt->execute();
    $recipe = $stmt->get_result()->fetch_assoc();

    if (!$recipe) {
        header("Location: dashboard.php");
        exit();
    }
    // Fetch ingredients using MySQLi
    $ing_stmt = $conn->prepare("SELECT * FROM ingredients WHERE recipe_id = ?");
    $ing_stmt->bind_param("i", $recipe_id);
    $ing_stmt->execute();
    $ingredients = $ing_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Fetch steps using MySQLi
    $step_stmt = $conn->prepare("SELECT * FROM steps WHERE recipe_id = ? ORDER BY step_number ASC");
    $step_stmt->bind_param("i", $recipe_id);
    $step_stmt->execute();
    $steps = $step_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Recipe - MyRecipeBook</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<div class="form-container">
    <form action="save_recipe_process.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= $edit_mode ? $recipe['id'] : '' ?>">
    
    <div class="form-header-container" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <button type="button" id="preview-btn" class="btn-secondary" style="background-color: #6366f1; color: white; padding: 8px 16px; border-radius: 6px; border: none; cursor: pointer;">
            Preview Recipe
        </button>
        <a href="dashboard.php" id="backBtn" style="background: #f1f5f9; color: #334155; padding: 0.5rem 1rem; border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 0.9rem; border: 1px solid #cbd5e1;">&larr; Back to Dashboard</a>    

        <!-- Recipe Preview Modal -->
        <div id="preview-modal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); overflow-y: auto;">
            <div class="modal-content" style="background-color: #fff; margin: 5% auto; padding: 30px; border-radius: 12px; width: 600px; max-width: 90%; box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
                <span id="close-modal" style="float: right; font-size: 28px; font-weight: bold; cursor: pointer; color: #666;">&times;</span>
                
                <h2 id="prev-title" style="margin-top: 0; color: #1e293b;">Recipe Title</h2>
                <p><strong>Category:</strong> <span id="prev-category">-</span> | <strong>Servings:</strong> <span id="prev-servings">1</span></p>
                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 15px 0;">
                
                <h3 style="color: #334155; font-size: 18px;">Ingredients</h3>
                <ul id="prev-ingredients-list" style="padding-left: 20px; color: #334155;"></ul>

                <h3 style="color: #334155; font-size: 18px;">Cooking Steps</h3>
                <ol id="prev-steps-list" style="padding-left: 20px; color: #334155;"></ol>
            </div>
        </div>
    </div>

    <!-- STEP 1: Basic Information -->
    <div class="form-step active" id="step-1">
        <h2>Step 1: Basic Recipe Details</h2>
        <label class="required">Recipe Title</label>
        <p>What would you like to name your Recipe?</p><br>
        <input type="text" name="title" value="<?= htmlspecialchars($recipe['title']) ?>" placeholder="e.g., Homemade Margherita Pizza" required>
       
        <label class="required">Category (Main Bucket)</label>
        <p>What type of Category is your recipe?</p><br>
        <select name="category" required>
            <option value="" disabled <?= empty($recipe['category']) ? 'selected' : '' ?>>Select a main category...</option>
            <optgroup label="Meal Types & Courses">
                <option value="Breakfast" <?= ($recipe['category'] ?? '') === 'Breakfast' ? 'selected' : '' ?>>Breakfast</option>
                <option value="Brunch" <?= ($recipe['category'] ?? '') === 'Brunch' ? 'selected' : '' ?>>Brunch</option>
                <option value="Lunch" <?= ($recipe['category'] ?? '') === 'Lunch' ? 'selected' : '' ?>>Lunch</option>
                <option value="Dinner" <?= ($recipe['category'] ?? '') === 'Dinner' ? 'selected' : '' ?>>Dinner</option>
                <option value="Snack" <?= ($recipe['category'] ?? '') === 'Snack' ? 'selected' : '' ?>>Snack</option>
                <option value="Appetizer" <?= ($recipe['category'] ?? '') === 'Appetizer' ? 'selected' : '' ?>>Appetizer</option>
                <option value="Side Dish" <?= ($recipe['category'] ?? '') === 'Side Dish' ? 'selected' : '' ?>>Side Dish</option>
                <option value="Soup" <?= ($recipe['category'] ?? '') === 'Soup' ? 'selected' : '' ?>>Soup</option>
                <option value="Salad" <?= ($recipe['category'] ?? '') === 'Salad' ? 'selected' : '' ?>>Salad</option>
                <option value="Main Course" <?= ($recipe['category'] ?? '') === 'Main Course' ? 'selected' : '' ?>>Main Course</option>
                <option value="Dessert" <?= ($recipe['category'] ?? '') === 'Dessert' ? 'selected' : '' ?>>Dessert</option>
                <option value="Baked Goods & Bread" <?= ($recipe['category'] ?? '') === 'Baked Goods & Bread' ? 'selected' : '' ?>>Baked Goods & Bread</option>
                <option value="Condiments & Sauces" <?= ($recipe['category'] ?? '') === 'Condiments & Sauces' ? 'selected' : '' ?>>Condiments & Sauces</option>
                <option value="Beverages (Non-Alcoholic)" <?= ($recipe['category'] ?? '') === 'Beverages (Non-Alcoholic)' ? 'selected' : '' ?>>Beverages (Non-Alcoholic)</option>
                <option value="Cocktails & Drinks (Alcoholic)" <?= ($recipe['category'] ?? '') === 'Cocktails & Drinks (Alcoholic)' ? 'selected' : '' ?>>Cocktails & Drinks (Alcoholic)</option>
            </optgroup>
            
            <optgroup label="Dietary & Lifestyle">
                <option value="Vegetarian" <?= ($recipe['category'] ?? '') === 'Vegetarian' ? 'selected' : '' ?>>Vegetarian</option>
                <option value="Vegan" <?= ($recipe['category'] ?? '') === 'Vegan' ? 'selected' : '' ?>>Vegan</option>
                <option value="Gluten-Free" <?= ($recipe['category'] ?? '') === 'Gluten-Free' ? 'selected' : '' ?>>Gluten-Free</option>
                <option value="Dairy-Free" <?= ($recipe['category'] ?? '') === 'Dairy-Free' ? 'selected' : '' ?>>Dairy-Free</option>
                <option value="Keto" <?= ($recipe['category'] ?? '') === 'Keto' ? 'selected' : '' ?>>Keto</option>
                <option value="Paleo" <?= ($recipe['category'] ?? '') === 'Paleo' ? 'selected' : '' ?>>Paleo</option>
                <option value="Low-Carb" <?= ($recipe['category'] ?? '') === 'Low-Carb' ? 'selected' : '' ?>>Low-Carb</option>
                <option value="High-Protein" <?= ($recipe['category'] ?? '') === 'High-Protein' ? 'selected' : '' ?>>High-Protein</option>
                <option value="Low-Fat" <?= ($recipe['category'] ?? '') === 'Low-Fat' ? 'selected' : '' ?>>Low-Fat</option>
                <option value="Whole30" <?= ($recipe['category'] ?? '') === 'Whole30' ? 'selected' : '' ?>>Whole30</option>
            </optgroup>
        
            <optgroup label="Global Cuisines">
                <option value="Italian" <?= ($recipe['category'] ?? '') === 'Italian' ? 'selected' : '' ?>>Italian</option>
                <option value="Mexican" <?= ($recipe['category'] ?? '') === 'Mexican' ? 'selected' : '' ?>>Mexican</option>
                <option value="Asian" <?= ($recipe['category'] ?? '') === 'Asian' ? 'selected' : '' ?>>Asian</option>
                <option value="American" <?= ($recipe['category'] ?? '') === 'American' ? 'selected' : '' ?>>American</option>
                <option value="Mediterranean" <?= ($recipe['category'] ?? '') === 'Mediterranean' ? 'selected' : '' ?>>Mediterranean</option>
                <option value="Indian" <?= ($recipe['category'] ?? '') === 'Indian' ? 'selected' : '' ?>>Indian</option>
                <option value="Middle Eastern" <?= ($recipe['category'] ?? '') === 'Middle Eastern' ? 'selected' : '' ?>>Middle Eastern</option>
                <option value="French" <?= ($recipe['category'] ?? '') === 'French' ? 'selected' : '' ?>>French</option>
                <option value="Greek" <?= ($recipe['category'] ?? '') === 'Greek' ? 'selected' : '' ?>>Greek</option>
                <option value="Thai" <?= ($recipe['category'] ?? '') === 'Thai' ? 'selected' : '' ?>>Thai</option>
                <option value="Japanese" <?= ($recipe['category'] ?? '') === 'Japanese' ? 'selected' : '' ?>>Japanese</option>
                <option value="Korean" <?= ($recipe['category'] ?? '') === 'Korean' ? 'selected' : '' ?>>Korean</option>
                <option value="Chinese" <?= ($recipe['category'] ?? '') === 'Chinese' ? 'selected' : '' ?>>Chinese</option>
                <option value="Latin American" <?= ($recipe['category'] ?? '') === 'Latin American' ? 'selected' : '' ?>>Latin American</option>
                <option value="African" <?= ($recipe['category'] ?? '') === 'African' ? 'selected' : '' ?>>African</option>
            </optgroup>
            
            <optgroup label="Cooking Styles & Occasions">
                <option value="Quick & Easy (Under 30 mins)" <?= ($recipe['category'] ?? '') === 'Quick & Easy (Under 30 mins)' ? 'selected' : '' ?>>Quick & Easy (Under 30 mins)</option>
                <option value="One-Pot / One-Pan" <?= ($recipe['category'] ?? '') === 'One-Pot / One-Pan' ? 'selected' : '' ?>>One-Pot / One-Pan</option>
                <option value="Air Fryer" <?= ($recipe['category'] ?? '') === 'Air Fryer' ? 'selected' : '' ?>>Air Fryer</option>
                <option value="Slow Cooker / Crockpot" <?= ($recipe['category'] ?? '') === 'Slow Cooker / Crockpot' ? 'selected' : '' ?>>Slow Cooker / Crockpot</option>
                <option value="Instant Pot / Pressure Cooker" <?= ($recipe['category'] ?? '') === 'Instant Pot / Pressure Cooker' ? 'selected' : '' ?>>Instant Pot / Pressure Cooker</option>
                <option value="Grill & BBQ" <?= ($recipe['category'] ?? '') === 'Grill & BBQ' ? 'selected' : '' ?>>Grill & BBQ</option>
                <option value="No-Bake" <?= ($recipe['category'] ?? '') === 'No-Bake' ? 'selected' : '' ?>>No-Bake</option>
                <option value="Meal Prep" <?= ($recipe['category'] ?? '') === 'Meal Prep' ? 'selected' : '' ?>>Meal Prep</option>
                <option value="Holiday & Seasonal" <?= ($recipe['category'] ?? '') === 'Holiday & Seasonal' ? 'selected' : '' ?>>Holiday & Seasonal</option>
                <option value="Comfort Food" <?= ($recipe['category'] ?? '') === 'Comfort Food' ? 'selected' : '' ?>>Comfort Food</option>
            </optgroup>
        </select>

        <label class="required">Prep Time</label>
        <p style="font-size: 0.85rem; color: #666; margin-bottom: 0.5rem;">How much time do you actively spend making the dish, including cooking time on the stove?</p>
        <input type="text" name="prep_time" value="<?= htmlspecialchars($recipe['prep_time'] ?? '') ?>" placeholder="e.g., 30 mins" style="margin-bottom: 1.25rem;">

        <label class="required">Base Serving</label>
        <input type="number" name="servings" value="<?= htmlspecialchars($recipe['servings'] ?? 1) ?>" min="1" required>

        <div class="btn-container">
            <div></div>
            <button type="button" class="btn-next" onclick="nextStep(2)">Next: Ingredients &rarr;</button>
        </div>
    </div>

    <!-- STEP 2: Ingredients -->
    <div class="form-step" id="step-2">
        <h2>Step 2: Ingredients</h2>
        
        <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 25px;">
            <h3 style="font-size: 1.1rem; color: #1e293b; margin-top: 0; margin-bottom: 20px;">Add an Ingredient</h3>
            
            <div style="display: flex; gap: 15px; align-items: flex-start; width: 100%;">
                
                <div style="display: flex; flex-direction: column; flex: 1.2;">
                    <label style="font-size: 0.75rem; font-weight: bold; color: #475569; margin-bottom: 6px; letter-spacing: 0.05em;">AMOUNT</label>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <input type="number" id="input-whole" min="0" placeholder="0" style="width: 100%; height: 52px; padding: 0 14px; border-radius: 6px; border: 1px solid #cbd5e1; box-sizing: border-box;">
                        <select id="input-fraction" style="width: 100%; height: 52px; padding: 0 14px; border-radius: 6px; border: 1px solid #cbd5e1; box-sizing: border-box;">
                            <option value="0">0</option>
                            <option value="1/2">&frac12;</option>
                            <option value="1/3">&frac13;</option>
                            <option value="1/4">&frac14;</option>
                            <option value="1/8">&#8539;</option>
                            <option value="2/3">&frac23;</option>
                            <option value="3/4">&frac34;</option>
                        </select>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; flex: 1;">
                    <label style="font-size: 0.75rem; font-weight: bold; color: #475569; margin-bottom: 6px; letter-spacing: 0.05em;">UNIT</label>
                    <select id="input-unit" style="height: 52px; padding: 0 14px; border-radius: 6px; border: 1px solid #cbd5e1; box-sizing: border-box;">
                        <option value="" disabled selected>Please select...</option>
                        <option value="cup">cup</option>
                        <option value="cups">cups</option>
                        <option value="gram">gram</option>
                        <option value="grams">grams</option>
                        <option value="kilogram">kilogram</option>
                        <option value="kilograms">kilograms</option>
                        <option value="milliliter">milliliter</option>
                        <option value="milliliters">milliliters</option>
                        <option value="liter">liter</option>
                        <option value="liters">liters</option>
                        <option value="fluid ounce">fluid ounce</option>
                        <option value="fluid ounces">fluid ounces</option>
                        <option value="ounce">ounce</option>
                        <option value="ounces">ounces</option>
                        <option value="pound">pound</option>
                        <option value="pounds">pounds</option>
                        <option value="teaspoon">teaspoon</option>
                        <option value="teaspoons">teaspoons</option>
                        <option value="tablespoon">tablespoon</option>
                        <option value="tablespoons">tablespoons</option>
                        <option value="pinch">pinch</option>
                        <option value="dash">dash</option>
                        <option value="drop">drop</option>
                        <option value="piece">piece</option>
                        <option value="slice">slice</option>
                        <option value="clove">clove</option>
                        <option value="sprig">sprig</option>
                        <option value="stalk">stalk</option>
                        <option value="head">head</option>
                        <option value="bunch">bunch</option>
                        <option value="packet">packet</option>
                        <option value="can">can</option>
                        <option value="package">package</option>
                        <option value="whole">whole</option>
                    </select>
                </div>

                <div style="display: flex; flex-direction: column; flex: 2; position: relative;">
                    <label style="font-size: 0.75rem; font-weight: bold; color: #475569; margin-bottom: 6px; letter-spacing: 0.05em;">INGREDIENT <span style="color: #dc2626;">*</span></label>
                    <input type="text" id="input-name" placeholder="Search ingredient, e.g. flour" autocomplete="off" maxlength="55" style="width: 100%; height: 52px; padding: 0 14px; border-radius: 6px; border: 1px solid #cbd5e1; box-sizing: border-box;">
                    <div id="suggestions-box" style="display: none; position: absolute; top: 78px; left: 0; right: 0; background: #fff; border: 1px solid #cbd5e1; border-radius: 6px; max-height: 200px; overflow-y: auto; z-index: 1000; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);"></div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 6px;">
                        <span id="char-count" style="font-size: 0.8rem; color: #64748b;">0/55</span>
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
                <button type="button" onclick="addIngredientToList()" style="background: #3730a3; color: white; border: none; padding: 12px 24px; border-radius: 6px; font-weight: 600; cursor: pointer;">Add to your list</button>
            </div>
        </div>

        <h3 style="margin-bottom: 15px; color: #1e293b;">Ingredient List</h3>
        <div id="ingredient-feedback" style="font-size: 0.85rem; margin-bottom: 8px; min-height: 20px; font-weight: 500;"></div>

        <!-- Ingredient List Container with Synced IDs for Editing -->
        <div id="ingredient-list-container" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 25px;">
            <?php if (!empty($ingredients)): ?>
                <?php foreach ($ingredients as $index => $ing): ?>
                    <?php 
                        $uniqueId = 'db_' .$index; 
                        $qty = htmlspecialchars($ing['quantity'] ?? '');
                        $unit = htmlspecialchars($ing['unit'] ?? '');
                        $name = htmlspecialchars($ing['name'] ?? '');
                    ?>
                    <div class="ingredient-card" data-id="<?= $uniqueId ?>" style="display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 16px 20px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                        <div style="display: flex; gap: 40px; align-items: center;">
                            <span style="font-weight: 500; color: #1e293b; min-width: 90px;"><?= $qty . ' ' .$unit ?></span>
                            <span style="color: #334155;"><?= $name ?></span>
                        </div>
                        <div style="display: flex; gap: 15px; align-items: center; color: #4f46e5; cursor: pointer;">
                            <span title="Edit" onclick="editIngredient('<?= $uniqueId ?>')" style="font-size: 0.9rem; cursor: pointer;">Edit</span>
                            <span title="Delete" onclick="removeIngredient('<?= $uniqueId ?>')" style="color: #dc2626; font-weight: bold; font-size: 1rem;">Remove</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Hidden Inputs Container -->
        <div id="hidden-inputs-container">
            <?php if ($edit_mode && !empty($ingredients)): ?>
                <?php foreach ($ingredients as$index => $ing):$parts = explode(' ', trim($ing['quantity']));$stored_whole = count($parts) > 1 ?$parts[0] : (is_numeric($parts[0]) && strpos($parts[0], '/') === false ? $parts[0] : '0');$stored_fraction = count($parts) > 1 ?$parts[1] : (strpos($parts[0], '/') !== false ? $parts[0] : '0');
                    if ($stored_whole === $stored_fraction)$stored_fraction = '0';
                    $uniqueId = 'db_' .$index;
                ?>
                    <div data-id="<?= $uniqueId ?>">
                        <input type="hidden" name="amount_whole[]" value="<?= htmlspecialchars($stored_whole) ?>">
                        <input type="hidden" name="amount_fraction[]" value="<?= htmlspecialchars($stored_fraction) ?>">
                        <input type="hidden" name="unit[]" value="<?= htmlspecialchars($ing['unit']) ?>">
                        <input type="hidden" name="ingredient[]" value="<?= htmlspecialchars($ing['name']) ?>">
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="btn-container">
            <button type="button" class="btn-prev" onclick="prevStep(1)">&larr; Back</button>
            <button type="button" class="btn-next" onclick="nextStep(3)">Next: Cooking Steps &rarr;</button>
        </div>
    </div>

    <!-- STEP 3: Cooking Steps & Pictures -->
    <div class="form-step" id="step-3">
        <h2>Step 3: Cooking Steps & Photos</h2>
        <div id="step-wrapper">
            <?php if (!empty($steps)): ?>
                <?php foreach ($steps as $index =>$st): ?>
                    <input type="hidden" name="existing_step_image[]" value="<?= htmlspecialchars($st['image_path'] ?? '') ?>">
                    <div class="step-block" style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <label style="margin-bottom: 0; font-weight: bold;">Step <?= $index + 1 ?> Description</label>
                            <?php if ($index > 0): ?>
                                <button type="button" class="btn-remove" style="padding: 4px 10px; font-size: 12px; background: #fee2e2; color: #dc2626; border: none; border-radius: 4px; cursor: pointer;" onclick="confirmDelete(this, '.step-block')">Remove Step</button>
                            <?php endif; ?>
                        </div>
                        <textarea name="step_description[]" placeholder="Explain what to do in this step..." required style="width: 100%; min-height: 80px; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;"><?= htmlspecialchars($st['description']) ?></textarea>
                        <label style="margin-top: 10px; display: block;">Step Photo (Optional)</label>
                        <input type="file" name="step_image[]" accept="image/*">
                        <?php if (!empty($st['image_path'])): ?>
                            <div style="font-size: 0.85rem; color: #64748b; margin-top: 5px;">Current photo: <?= htmlspecialchars($st['image_path']) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="step-block" style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 15px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <label style="margin-bottom: 0; font-weight: bold;">Step Description</label>
                    </div>
                    <textarea name="step_description[]" placeholder="Explain what to do in this step..." required style="width: 100%; min-height: 80px; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;"></textarea>
                    <label style="margin-top: 10px; display: block;">Step Photo (Optional)</label>
                    <input type="file" name="step_image[]" accept="image/*">
                </div>
            <?php endif; ?>
        </div>

        <button type="button" class="btn-add" onclick="addStepRow()" style="background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 10px 16px; border-radius: 6px; font-weight: 600; cursor: pointer; margin-bottom: 20px;">+ Add Another Step</button>

        <div class="btn-container" style="display: flex; justify-content: space-between;">
            <button type="button" class="btn-prev" onclick="prevStep(2)">&larr; Back</button>
            <button type="submit" name="save_recipe" class="btn-submit" style="background: #4f46e5; color: white; border: none; padding: 12px 24px; border-radius: 6px; font-weight: 600; cursor: pointer;">Publish Recipe</button>
        </div>
    </div>

    </form>
</div>

<!-- Modal for feedback message while deleting -->
<div id="customConfirmModal" class="modal-overlay" style="display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); align-items: center; justify-content: center;">
    <div class="modal-content" style="background: #fff; padding: 25px; border-radius: 8px; width: 350px; text-align: center;">
        <p id="modalMessage" style="margin-bottom: 20px; color: #1e293b; font-weight: 500;">Are you sure you want to delete this?</p>
        <div style="display: flex; justify-content: center; gap: 10px;">
            <button type="button" id="modalNoBtn" style="padding: 8px 16px; background: #e2e8f0; border: none; border-radius: 4px; cursor: pointer;">No</button>
            <button type="button" id="modalYesBtn" style="padding: 8px 16px; background: #dc2626; color: white; border: none; border-radius: 4px; cursor: pointer;">Yes</button>
        </div>
    </div>
</div>

<script>
    let currentStep = 1;

    function nextStep(step) {
        let currentStepElement = document.getElementById('step-' + currentStep);
        let requiredFields = currentStepElement.querySelectorAll('[required]');
        
        for (let field of requiredFields) {
            if (!field.value.trim()) {
                field.reportValidity();
                field.focus();
                return;
            }
        }

        let steps = document.querySelectorAll('.form-step');
        steps.forEach(s => s.classList.remove('active'));

        currentStep = step;
        document.getElementById('step-' + currentStep).classList.add('active');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function prevStep(targetStep) {
        document.getElementById(`step-${currentStep}`).classList.remove('active');
        currentStep = targetStep;
        document.getElementById(`step-${currentStep}`).classList.add('active');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    const allowedIngredients = [
        "all-purpose flour", "almond flour", "rice flour", "cornstarch", "baking powder", "baking soda", 
        "brown sugar", "granulated sugar", "powdered sugar", "honey", "maple syrup", "molasses",
        "butter", "ghee", "lard", "canola oil", "vegetable oil", "olive oil", "sesame oil", "coconut oil",
        "chocolate chips", "cocoa powder", "vanilla extract", "yeast", "salt", "kosher salt", "sea salt",
        "eggs", "milk", "whole milk", "heavy cream", "sour cream", "yogurt", "greek yogurt", 
        "mozzarella cheese", "cheddar cheese", "parmesan cheese", "paneer", "feta cheese", "butter milk",
        "rice", "basmati rice", "jasmine rice", "brown rice", "pasta", "spaghetti", "noodles", 
        "bread", "tortilla", "oats", "quinoa", "lentils", "red lentils", "black lentils", 
        "chickpeas", "kidney beans", "black beans", "tofu",
        "onion", "red onion", "garlic", "ginger", "tomato", "potato", "sweet potato", 
        "carrot", "bell pepper", "chili pepper", "green chili", "spinach", "cabbage", 
        "broccoli", "cauliflower", "mushroom", "cucumber", "zucchini", "eggplant", 
        "green peas", "corn", "lettuce", "cilantro", "mint leaves", "scallion",
        "chicken", "chicken breast", "chicken thighs", "beef", "ground beef", "buffalo meat", 
        "pork", "lamb", "fish", "salmon", "shrimp", "eggs",
        "black pepper", "cumin", "coriander powder", "turmeric powder", "paprika", "chili powder", 
        "cayenne pepper", "cinnamon", "cardamom", "nutmeg", "bay leaves", "oregano", 
        "thyme", "rosemary", "basil", "parsley", "garam masala", "curry powder", "soy sauce", 
        "fish sauce", "vinegar", "apple cider vinegar", "rice vinegar", "lime juice", "lemon juice", 
        "unsalted butter", "salted butter", "cream cheese", "ricotta cheese", "blue cheese", "brie cheese",
        "camembert cheese", "gouda cheese", "swiss cheese", "provolone cheese", "mozzarella sticks", "cheese slices", 
        "cheese cubes", "cheese shreds", "cheese sauce", "cheese dip","thyme", "oregano", "basil", 
        "parsley", "dill", "tarragon", "chives", "sage", "marjoram", "bay leaves", "curry leaves", "lemongrass", 
        "saffron", "vanilla beans", "cocoa nibs", "coconut flakes", "almonds", "walnuts", "pecans", "cashews", 
        "pistachios", "hazelnuts", "macadamia nuts", "sunflower seeds", "pumpkin seeds", "chia seeds", "flaxseeds", 
        "hemp seeds", "raisins", "cranberries", "dates", "figs", "apricots", "prunes", "coconut milk", "coconut cream", "almond milk", "soy milk", "oat milk",
        "peanut butter", "almond butter", "cashew butter", "hazelnut butter", "sunflower seed butter", "tahini", 
        "sesame paste", "hummus", "salsa", "guacamole", "ketchup", "mustard", "mayonnaise", "barbecue sauce", 
        "hot sauce", "sriracha", "teriyaki sauce", "hoisin sauce", "oyster sauce", "fish sauce", "soy sauce", 
        "tamari", "miso paste", "curry paste", "chili paste", "sugar", "brown sugar", "powdered sugar", "honey", 
        "maple syrup", "molasses", "agave nectar", "corn syrup", "stevia", "xylitol", "erythritol", 
        "monk fruit sweetener", "water", "sparkling water", "club soda", "coconut water", "green tea", "black tea", 
        "herbal tea", "coffee", "espresso", "cappuccino", "latte", "mocha", "hot chocolate", "milkshake", "smoothie", 
        "juice", "lemonade", "iced tea", "iced coffee", "wine", "red wine", "white wine", "rose wine", 
        "sparkling wine", "champagne", "beer", "lager", "ale", "stout", "porter", "cider", "whiskey", "vodka", 
        "rum", "gin", "tequila", "brandy", "liqueur", "cocktail mixers", "bitters", "vermouth", "absinthe", "schnapps", 
        "amaretto", "triple sec", "cointreau", "grand marnier", "baileys irish cream", "kahlua", "frangelico", "sambuca", 
        "chartreuse", "campari", "aperol", "coarse salt", "sea salt", "kosher salt", "table salt", "black salt", 
        "pink salt", "smoked salt", "peppercorns", "black pepper", "white pepper", "green pepper", "pink pepper", 
        "red pepper flakes", "cayenne pepper", "paprika", "smoked paprika", "chipotle powder",
        "cumin seeds", "coriander seeds", "fennel seeds", "mustard seeds", "caraway seeds", "anise seeds", 
        "nigella seeds", "fenugreek seeds", "sesame seeds", "poppy seeds", "chia seeds", "flaxseeds", "hemp seeds",
        "cinnamon sticks", "ground cinnamon", "nutmeg", "cloves", "allspice", "cardamom pods", "ground cardamom", 
        "star anise", "bay leaves", "dried thyme", "dried rosemary", "dried oregano", "dried basil", "dried parsley", 
        "dried dill", "dried tarragon", "dried sage", "steak", "chicken", "pork", "lamb", "fish", "shrimp", "crab", 
        "lobster", "scallops", "mussels", "clams", "squid", "octopus", "tofu", "tempeh", "seitan", "edamame", 
        "black beans", "kidney beans", "pinto beans", "navy beans", "garbanzo beans", "lentils", "split peas", 
        "green peas", "snow peas", "sugar snap peas", "corn", "sweet corn", "baby corn", "corn on the cob", 
        "potatoes", "sweet potatoes", "yams", "carrots", "parsnips", "turnips", "rutabaga", "beets", "radishes", 
        "daikon", "cabbage", "broccoli", "cauliflower", "brussels sprouts", "kale", "spinach", "arugula", "lettuce",
        "romaine lettuce", "iceberg lettuce", "butter lettuce", "endive", "radicchio", "watercress", "bok choy", 
        "chard", "collard greens", "mustard greens", "dandelion greens",
    ]; 

    const inputName = document.getElementById('input-name');
    const suggestionsBox = document.getElementById('suggestions-box');
    const charCount = document.getElementById('char-count');

    inputName.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        charCount.textContent = this.value.length + '/55';
        suggestionsBox.innerHTML = '';
        
        if (query.length === 0) {
            suggestionsBox.style.display = 'none';
            return;
        }

        const matches = allowedIngredients.filter(item => item.toLowerCase().includes(query));

        if (matches.length > 0) {
            suggestionsBox.style.display = 'block';
            matches.forEach(match => {
                const div = document.createElement('div');
                div.textContent = match;
                div.style.cssText = "padding: 12px 14px; cursor: pointer; border-bottom: 1px solid #f1f5f9; font-size: 0.95rem; color: #334155;";
                
                div.onmouseover = () => div.style.background = "#f8fafc";
                div.onmouseout = () => div.style.background = "#fff";
                
                div.onclick = function() {
                    inputName.value = match;
                    charCount.textContent = match.length + '/55';
                    suggestionsBox.style.display = 'none';
                };
                
                suggestionsBox.appendChild(div);
            });
        } else {
            suggestionsBox.style.display = 'none';
        }
    });

    function addIngredientToList() {
        const whole = document.getElementById('input-whole').value || "0";
        const fractionSelect = document.getElementById('input-fraction');
        const fractionVal = fractionSelect.value;
        const fractionText = fractionVal !== "0" ? fractionSelect.options[fractionSelect.selectedIndex].text : "";
        
        const unitSelect = document.getElementById('input-unit');
        const unitVal = unitSelect.value;
        const ingredientName = document.getElementById('input-name').value.trim();

        if (!ingredientName) {
            alert("Please enter an ingredient name.");
            return;
        }

        if (!allowedIngredients.includes(ingredientName.toLowerCase())) {
            alert("Please select a valid ingredient from the search suggestions.");
            return;
        }  

        let amountDisplay = whole !== "0" ? whole : "";
        if (fractionText) {
            amountDisplay += (amountDisplay ? " " : "") + fractionText;
        }
        if (!amountDisplay) amountDisplay = "0";

        const container = document.getElementById('ingredient-list-container');
        const hiddenContainer = document.getElementById('hidden-inputs-container');
        const uniqueId = 'dyn_' + Date.now();

        const card = document.createElement('div');
        card.className = 'ingredient-card';
        card.setAttribute('data-id', uniqueId);
        card.style.cssText = "display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 16px 20px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.02);";
        
        card.innerHTML = `
            <div style="display: flex; gap: 40px; align-items: center;">
                <span style="font-weight: 500; color: #1e293b; min-width: 90px;">${amountDisplay} ${unitVal}</span>
                <span style="color: #334155;">${ingredientName}</span>
            </div>
            <div style="display: flex; gap: 15px; align-items: center; color: #4f46e5; cursor: pointer;">
                <span title="Edit" onclick="editIngredient('${uniqueId}')" style="font-size: 0.9rem; cursor: pointer;">Edit</span>
                <span title="Delete" onclick="removeIngredient('${uniqueId}')" style="color: #dc2626; font-weight: bold; font-size: 1rem;">Remove</span>
            </div>
        `;

        container.appendChild(card);

        const hiddenGroup = document.createElement('div');
        hiddenGroup.setAttribute('data-id', uniqueId);
        hiddenGroup.innerHTML = `
            <input type="hidden" name="amount_whole[]" value="${whole}">
            <input type="hidden" name="amount_fraction[]" value="${fractionVal}">
            <input type="hidden" name="unit[]" value="${unitVal}">
            <input type="hidden" name="ingredient[]" value="${ingredientName}">
        `;
        hiddenContainer.appendChild(hiddenGroup);

        document.getElementById('input-whole').value = '';
        document.getElementById('input-fraction').value = '0';
        document.getElementById('input-unit').value = '';
        document.getElementById('input-name').value = '';
        document.getElementById('char-count').textContent = '0/55';
    }

    function removeIngredient(id) {
        const confirmed = confirm("Are you sure you want to delete this ingredient?");
        if (!confirmed) return;
        
        deleteIngredientElement(id);

        const feedbackEl = document.getElementById('ingredient-feedback');
        if (feedbackEl) {
            feedbackEl.style.color = "#dc2626";
            feedbackEl.textContent = "Ingredient removed from list.";
            setTimeout(() => { feedbackEl.textContent = ""; }, 1500);
        }
    }

    function deleteIngredientElement(id) {
        const card = document.querySelector(`.ingredient-card[data-id="${id}"]`);
        const hiddenGroup = document.querySelector(`#hidden-inputs-container div[data-id="${id}"]`);
        
        if (card) card.remove();
        if (hiddenGroup) hiddenGroup.remove();
    }

    function editIngredient(id) {
        const hiddenGroup = document.querySelector(`#hidden-inputs-container div[data-id="${id}"]`);
        if (!hiddenGroup) return;

        const wholeVal = hiddenGroup.querySelector('input[name="amount_whole[]"]').value;
        const fractionVal = hiddenGroup.querySelector('input[name="amount_fraction[]"]').value;
        const unitVal = hiddenGroup.querySelector('input[name="unit[]"]').value;
        const ingredientVal = hiddenGroup.querySelector('input[name="ingredient[]"]').value;

        document.getElementById('input-whole').value = wholeVal !== "0" ? wholeVal : '';
        document.getElementById('input-fraction').value = fractionVal;
        document.getElementById('input-unit').value = unitVal;
        document.getElementById('input-name').value = ingredientVal;
        document.getElementById('char-count').textContent = ingredientVal.length + '/55';

        deleteIngredientElement(id);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function addStepRow() {
        const wrapper = document.getElementById('step-wrapper');
        const newBlock = document.createElement('div');
        newBlock.classList.add('step-block');
        newBlock.style.cssText = "background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 15px;";
        newBlock.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                <label style="margin-bottom: 0; font-weight: bold;">Step Description</label>
                <button type="button" class="btn-remove" style="padding: 4px 10px; font-size: 12px; background: #fee2e2; color: #dc2626; border: none; border-radius: 4px; cursor: pointer;" onclick="confirmDelete(this, '.step-block')">Remove Step</button>
            </div>
            <textarea name="step_description[]" placeholder="Explain what to do in this step..." required style="width: 100%; min-height: 80px; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box;"></textarea>
            <label style="margin-top: 10px; display: block;">Step Photo (Optional)</label>
            <input type="file" name="step_image[]" accept="image/*">
        `;
        wrapper.appendChild(newBlock);
    }

    let elementToRemove = null;

    function confirmDelete(button, selector) {
        elementToRemove = button.closest(selector);
        document.getElementById('customConfirmModal').style.display = 'flex';
    }

    document.getElementById('modalNoBtn').addEventListener('click', function() {
        document.getElementById('customConfirmModal').style.display = 'none';
        elementToRemove = null;
    });

    document.getElementById('modalYesBtn').addEventListener('click', function() {
        if (elementToRemove) {
            elementToRemove.remove();
        }
        document.getElementById('customConfirmModal').style.display = 'none';
        elementToRemove = null;
    });

    document.getElementById('preview-btn').addEventListener('click', function() {
        document.getElementById('prev-title').textContent = document.querySelector('[name="title"]').value || 'Untitled Recipe';
        document.getElementById('prev-category').textContent = document.querySelector('[name="category"]').value || 'Uncategorized';
        document.getElementById('prev-servings').textContent = document.querySelector('[name="servings"]').value || '1';

        const ingNames = document.querySelectorAll('input[name="ingredient[]"]');
        const ingListContainer = document.getElementById('prev-ingredients-list');
        ingListContainer.innerHTML = '';

        ingNames.forEach((nameInput) => {
            if (nameInput.value.trim() !== '') {
                const li = document.createElement('li');
                li.textContent = nameInput.value;
                ingListContainer.appendChild(li);
            }
        });

        if (ingListContainer.children.length === 0) {
            ingListContainer.innerHTML = '<li>No ingredients added yet.</li>';
        }

        const stepTextareas = document.querySelectorAll('textarea[name="step_description[]"]');
        const stepListContainer = document.getElementById('prev-steps-list');
        stepListContainer.innerHTML = '';

        stepTextareas.forEach((textarea) => {
            if (textarea.value.trim() !== '') {
                const li = document.createElement('li');
                li.textContent = textarea.value;
                stepListContainer.appendChild(li);
            }
        });

        if (stepListContainer.children.length === 0) {
            stepListContainer.innerHTML = '<li>No steps added yet.</li>';
        }

        document.getElementById('preview-modal').style.display = 'block';
    });

    document.getElementById('close-modal').addEventListener('click', function() {
        document.getElementById('preview-modal').style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        const modal = document.getElementById('preview-modal');
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });

    let isDirty = false;
    document.querySelectorAll('input, textarea, select').forEach(input => {
        input.addEventListener('change', () => { isDirty = true; });
    });

    document.getElementById('backBtn').addEventListener('click', function(e) {
        if (isDirty) {
            if (!confirm("Are you sure you want to leave? Your unsaved changes will be lost.")) {
                e.preventDefault();
            }
        }
    });
</script>
</body>
</html>