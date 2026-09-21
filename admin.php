<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch();

if (!$currentUser || $currentUser['is_admin'] != 1) {
    header('Location: index.php');
    exit;
}

$secureAdminPassword = 'supersecretpassword123';
$error = '';

if (isset($_POST['admin_pass'])) {
    if ($_POST['admin_pass'] === $secureAdminPassword) {
        $_SESSION['admin_authenticated'] = true;
    } else {
        $error = 'Incorrect admin password.';
    }
}

if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Security Check</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <h2>Admin Authentication</h2>
        <p>Enter secure password to continue</p>
        <?php if($error): ?><p style="color: var(--accent-red); font-weight: bold;"><?php echo $error; ?></p><?php endif; ?>
        <form method="POST">
            <input type="password" name="admin_pass" placeholder="Admin Password" required>
            <button type="submit">Verify & Access</button>
        </form>
        <p style="margin-top: 20px;"><a href="index.php">← Back to Game</a></p>
    </div>
</body>
</html>
<?php 
    exit;
endif;

$statsStmt = $pdo->query("SELECT COUNT(*) as total_users, SUM(balance) as total_balance FROM users");
$stats = $statsStmt->fetch();
$totalUsers = $stats['total_users'] ?? 0;
$totalBalance = $stats['total_balance'] ?? 0.00;

$usersStmt = $pdo->query("SELECT id, username, balance, is_admin FROM users ORDER BY id DESC");
$users = $usersStmt->fetchAll();

$roundsStmt = $pdo->query("SELECT * FROM rounds ORDER BY id DESC LIMIT 10");
$rounds = $roundsStmt->fetchAll();

$betsStmt = $pdo->query("
    SELECT bets.*, users.username, rounds.status as round_status 
    FROM bets 
    JOIN users ON bets.user_id = users.id 
    JOIN rounds ON bets.round_id = rounds.id 
    ORDER BY bets.id DESC LIMIT 15
");
$bets = $betsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Aviator Game</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .admin-container { max-width: 900px; margin: 30px auto; background: var(--bg-card); padding: 30px; border-radius: 16px; text-align: left; border: 1px solid var(--border-color); }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; margin-bottom: 30px; }
        th, td { border: 1px solid var(--border-color); padding: 10px 14px; text-align: center; font-size: 14px; }
        th { background: #0f172a; color: var(--accent-gold); }
        tr:nth-child(even) { background: rgba(255,255,255,0.02); }
        h3 { border-bottom: 2px solid var(--accent-gold); padding-bottom: 8px; color: var(--accent-gold); margin-top: 35px; }
        .stats-grid { display: flex; gap: 20px; margin: 20px 0; }
        .stat-card { background: var(--bg-main); border: 1px solid var(--border-color); padding: 20px; border-radius: 12px; flex: 1; text-align: center; }
        .stat-card h4 { margin: 0; color: var(--text-muted); font-size: 14px; }
        .stat-card p { margin: 8px 0 0; font-size: 26px; font-weight: bold; color: var(--accent-green); }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="top-bar">
            <h2>Aviator Admin Dashboard</h2>
            <div>
                <a href="index.php" style="margin-right: 15px;">Back to Game</a>
                <a href="logout.php" style="color: var(--accent-red);">Logout</a>
            </div>
        </div>
        <hr style="border: 0; border-top: 1px solid var(--border-color); margin: 20px 0;">

        <div class="stats-grid">
            <div class="stat-card">
                <h4>Total Users</h4>
                <p><?php echo $totalUsers; ?></p>
            </div>
            <div class="stat-card">
                <h4>Total User Balances</h4>
                <p>$<?php echo number_format($totalBalance, 2); ?></p>
            </div>
        </div>

        <h3>Registered Users & Names</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Balance ($)</th>
                    <th>Role</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td>$<?php echo number_format($user['balance'], 2); ?></td>
                    <td style="color: <?php echo $user['is_admin'] == 1 ? 'var(--accent-red)' : 'var(--accent-green)'; ?>">
                        <b><?php echo $user['is_admin'] == 1 ? 'ADMIN' : 'USER'; ?></b>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Recent Game Rounds (Last 10)</h3>
        <table>
            <thead>
                <tr>
                    <th>Round ID</th>
                    <th>Status</th>
                    <th>Multiplier</th>
                    <th>Crash Point</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rounds as $round): ?>
                <tr>
                    <td><?php echo $round['id']; ?></td>
                    <td style="color: <?php echo $round['status'] === 'crashed' ? 'var(--accent-red)' : ($round['status'] === 'running' ? 'var(--accent-green)' : 'var(--accent-gold)'); ?>">
                        <b><?php echo strtoupper($round['status']); ?></b>
                    </td>
                    <td><?php echo number_format($round['multiplier'], 2); ?>x</td>
                    <td><?php echo number_format($round['crash_point'], 2); ?>x</td>
                    <td><?php echo $round['created_at']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3>Recent User Bets</h3>
        <table>
            <thead>
                <tr>
                    <th>Bet ID</th>
                    <th>User</th>
                    <th>Round ID</th>
                    <th>Amount ($)</th>
                    <th>Multiplier</th>
                    <th>Payout ($)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bets as $bet): ?>
                <tr>
                    <td><?php echo $bet['id']; ?></td>
                    <td><?php echo htmlspecialchars($bet['username']); ?></td>
                    <td><?php echo $bet['round_id']; ?></td>
                    <td>$<?php echo number_format($bet['amount'], 2); ?></td>
                    <td><?php echo $bet['multiplier'] > 0 ? number_format($bet['multiplier'], 2) . 'x' : '-'; ?></td>
                    <td>$<?php echo number_format($bet['payout'], 2); ?></td>
                    <td style="color: <?php echo $bet['status'] === 'cashed_out' ? 'var(--accent-green)' : ($bet['status'] === 'lost' ? 'var(--accent-red)' : 'var(--accent-gold)'); ?>">
                        <b><?php echo strtoupper($bet['status']); ?></b>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>