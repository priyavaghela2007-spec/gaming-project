<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

$stmt = $pdo->query("SELECT * FROM rounds ORDER BY id DESC LIMIT 1");
$round = $stmt->fetch();

if (!$round) {
    $pdo->query("INSERT INTO rounds (status, multiplier, crash_point) VALUES ('waiting', 1.00, 1.50)");
    $round = $pdo->query("SELECT * FROM rounds ORDER BY id DESC LIMIT 1")->fetch();
}

$roundId = $round['id'];
$status = $round['status'];
$crashPoint = floatval($round['crash_point']);

$timeStmt = $pdo->prepare("SELECT TIMESTAMPDIFF(SECOND, created_at, NOW()) as elapsed FROM rounds WHERE id = ?");
$timeStmt->execute([$roundId]);
$elapsed = intval($timeStmt->fetch()['elapsed']);

if ($status === 'waiting') {
    if ($elapsed >= 5) {
        $randomCrash = round(rand(120, 400) / 100, 2);
        $update = $pdo->prepare("UPDATE rounds SET status = 'running', multiplier = 1.00, crash_point = ?, created_at = NOW() WHERE id = ?");
        $update->execute([$randomCrash, $roundId]);
        
        $stmt = $pdo->prepare("SELECT * FROM rounds WHERE id = ?");
        $stmt->execute([$roundId]);
        $round = $stmt->fetch();
    }
} elseif ($status === 'running') {
    $timeStmt->execute([$roundId]);
    $elapsed = intval($timeStmt->fetch()['elapsed']);
    $newMultiplier = round(1.00 + ($elapsed * 0.2), 2);

    if ($newMultiplier >= $crashPoint) {
        $newMultiplier = $crashPoint;
        $update = $pdo->prepare("UPDATE rounds SET status = 'crashed', multiplier = ?, created_at = NOW() WHERE id = ?");
        $update->execute([$newMultiplier, $roundId]);

        $betUpdate = $pdo->prepare("UPDATE bets SET status = 'lost' WHERE round_id = ? AND status = 'active'");
        $betUpdate->execute([$roundId]);

        $stmt = $pdo->prepare("SELECT * FROM rounds WHERE id = ?");
        $stmt->execute([$roundId]);
        $round = $stmt->fetch();
    } else {
        $update = $pdo->prepare("UPDATE rounds SET multiplier = ? WHERE id = ?");
        $update->execute([$newMultiplier, $roundId]);
        $round['multiplier'] = $newMultiplier;
    }
} elseif ($status === 'crashed') {
    if ($elapsed >= 5) {
        $pdo->query("INSERT INTO rounds (status, multiplier, crash_point) VALUES ('waiting', 1.00, 1.00)");
        $round = $pdo->query("SELECT * FROM rounds ORDER BY id DESC LIMIT 1")->fetch();
    }
}

$balance = 0.00;
if (isset($_SESSION['user_id'])) {
    $userStmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $user = $userStmt->fetch();
    if ($user) {
        $balance = floatval($user['balance']);
    }
}

$response = $round;
$response['balance'] = $balance;

echo json_encode($response);
?>