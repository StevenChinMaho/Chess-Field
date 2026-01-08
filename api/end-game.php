<?php
require_once("../includes/config.php");
header('Content-Type: application/json');

$room_code = $_POST['room_code'] ?? '';
$identity = $_COOKIE['identity'] ?? '';
$outcome = $_POST['outcome'] ?? null;

$sql = "SELECT r.game_id, r.p1_id, r.p2_id, p.player_id, g.p1_side
        FROM rooms r
        JOIN players p ON p.player_identity = :idn
        JOIN games g ON r.game_id = g.game_id
        WHERE r.room_code = :code";
$stmt = $pdo->prepare($sql);
$stmt->execute(['idn' => $identity, 'code' => $room_code]);
$info = $stmt->fetch();

if (!$info) {
    echo json_encode(['status' => 'error']);
    exit;
}

if (!$outcome) {
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM moves WHERE game_id = ?");
    $stmt_count->execute([$info['game_id']]);
    $move_count = $stmt_count->fetchColumn();

    if ($move_count < 2) {
        $outcome = 'aborted';
    } else {
        $my_color = ($info['player_id'] == $info['p1_id']) ? $info['p1_side'] : ($info['p1_side'] == 'w' ? 'b' : 'w');
        $outcome = ($my_color == 'w') ? 'b' : 'w';
    }
}

$stmt = $pdo->prepare("UPDATE games SET status = 'finished', outcome = :outcome WHERE game_id = :gid");
$stmt->execute(['outcome' => $outcome, 'gid' => $info['game_id']]);

echo json_encode(['status' => 'success']);
?>