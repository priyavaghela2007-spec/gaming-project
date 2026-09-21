<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'config/database.php';

$stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch();
$initialBalance = $currentUser ? number_format($currentUser['balance'], 2) : '0.00';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Aviator Game</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="game-container">
        <h2>Aviator Game</h2>
        <div class="top-bar">
            <span>Player: <b><?php echo htmlspecialchars($_SESSION['username']); ?></b></span>
            <span>Balance: <b>$<span id="balanceDisplay"><?php echo $initialBalance; ?></span></b></span>
            <a href="logout.php">Logout</a>
        </div>
        
        <h1 id="multiplierDisplay">1.00x</h1>
        <canvas id="gameCanvas" width="480" height="220"></canvas>
        
        <div id="gameMessage"></div>

        <div class="controls-row">
            <input type="number" id="betAmount" value="10" min="1">
            <button id="actionBtn" data-status="idle">Place Bet</button>
        </div>
    </div>
    <script src="assets/js/game.js"></script>
</body>
</html>