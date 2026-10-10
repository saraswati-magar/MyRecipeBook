<?php
require_once 'config/mysql_db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($email) && !empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            
            if ($stmt->execute([$username, $email, $hashed_password])) {
                $message = "<p style='color: #51cf66;'>Registration successful! <a href='login.php'>Login here</a></p>";
            }
        } catch (PDOException $e) {
            $message = "<p style='color: #ff6b6b;'>Error: Email or Username might already exist.</p>";
        }
    } else {
        $message = "<p style='color: #ff6b6b;'>Please fill in all fields.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - MyRecipeBook</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-body">

<div class="container">
    <div class="card">
        <h2>Create Account</h2>
        <?php echo $message; ?>
        
        <form action="register.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Choose a username" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Create a password" required>
            </div>
            
            <button type="submit" class="glass-btn">Register</button>
        </form>
        <p style="margin-top: 1.5rem;">Already have an account? <a href="login.php">Login</a></p>
    </div>
</div>

</body>
</html>