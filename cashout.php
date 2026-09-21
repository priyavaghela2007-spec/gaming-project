<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];

$stmt = $pdo->query("SELECT * FROM rounds WHERE status = 'running' ORDER BY id DESC LIMIT 1");
$round = $stmt->fetch();

if (!$round) {
    echo json_encode(['success' => false, 'message' => 'Round is not running']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM bets WHERE user_id = ? AND round_id = ? AND status = 'active'");
$stmt->execute([$userId, $round['id']]);
$bet = $stmt->fetch();

if (!$bet) {
    echo json_encode(['success' => false, 'message' => 'No active bet found']);
    exit;
}

$currentMultiplier = floatval($round['multiplier']);
$payout = $bet['amount'] * $currentMultiplier;

$pdo->beginTransaction();
try {
    $updateBet = $pdo->prepare("UPDATE bets SET status = 'cashed_out', multiplier = ?, payout = ? WHERE id = ?");
    $updateBet->execute([$currentMultiplier, $payout, $bet['id']]);

    $updateBalance = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
    $updateBalance->execute([$payout, $userId]);

    $pdo->commit();
    echo json_encode(['success' => true, 'payout' => $payout, 'multiplier' => $currentMultiplier]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Cashout transaction failed']);
}
?>