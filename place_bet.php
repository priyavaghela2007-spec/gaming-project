<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$amount = floatval($data['amount']);
$userId = $_SESSION['user_id'];

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid bet amount']);
    exit;
}

$stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user || floatval($user['balance']) < $amount) {
    echo json_encode(['success' => false, 'message' => 'Insufficient balance']);
    exit;
}

$stmt = $pdo->query("SELECT * FROM rounds WHERE status = 'waiting' ORDER BY id DESC LIMIT 1");
$round = $stmt->fetch();

if (!$round) {
    echo json_encode(['success' => false, 'message' => 'Betting window closed']);
    exit;
}

$pdo->beginTransaction();
try {
    $updateBalance = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
    $updateBalance->execute([$amount, $userId]);

    $insertBet = $pdo->prepare("INSERT INTO bets (user_id, round_id, amount, status) VALUES (?, ?, ?, 'active')");
    $insertBet->execute([$userId, $round['id'], $amount]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Bet placed successfully']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Transaction failed']);
}
?>