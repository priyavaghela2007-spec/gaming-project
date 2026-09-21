<?php
require_once 'config/database.php';
session_start();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        
        // Redirect to Admin Panel if user is admin
        if (isset($user['is_admin']) && $user['is_admin'] == 1) {
            header('Location: admin.php');
            exit;
        }

        header('Location: index.php');
        exit;
    } else {
        $message = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Aviator Game</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <h2>Welcome Back</h2>
        <p>Sign in to continue playing</p>
        <?php if($message): ?><p style="color: var(--accent-red); font-weight: 600;"><?php echo $message; ?></p><?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <p style="margin-top: 20px;">Don't have an account? <a href="register.php">Register here</a></p>
    </div>
</body>
</html>