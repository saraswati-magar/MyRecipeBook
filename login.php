<?php
session_start();
require_once 'config/mysql_db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $id = $user['id'];
            $username = $user['username'];
            $hashed_password = $user['password'];

            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $username;
                
                header("location: dashboard.php");
                exit;
            } else {
                $message = "<p style='color: #ff6b6b;'>Invalid password.</p>";
            }
        } else {
            $message = "<p style='color: #ff6b6b;'>No account found with that email.</p>";
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
    <title>Login - MyRecipeBook</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-body">

<div class="container">
    <div class="card">
        <h2>Welcome Back</h2>
        <?php echo $message; ?>
        
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            
            <button type="submit" class="glass-btn">Login</button>
        </form>
        <p style="margin-top: 1.5rem;">Don't have an account? <a href="register.php">Register</a></p>
    </div>
</div>

</body>
</html>