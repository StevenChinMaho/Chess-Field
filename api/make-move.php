<?php
require_once('../includes/config.php');

header('Content-Type: application/json');

$identity = $_COOKIE['identity'] ?? null;
$room_code = $_POST['room_code'] ?? null;
$board_str = $_POST['board'] ?? null; // 64字元字串
$move_text = $_POST['move_text'] ?? ''; // e.g. "e2e4"

if (!$identity || !$room_code || !$board_str) {
    echo json_encode(['status' => 'error', 'message' => '缺少參數']);
    exit;
}

// 1. 驗證身分與房間
$sql = "SELECT r.*, g.game_id, g.turn, g.status, p.player_id 
        FROM rooms r 
        JOIN players p ON p.player_identity = :identity
        JOIN games g ON r.game_id = g.game_id
        WHERE r.room_code = :code";
$stmt = $pdo->prepare($sql);
$stmt->execute(['identity' => $identity, 'code' => $room_code]);
$data = $stmt->fetch();

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => '房間或玩家無效']);
    exit;
}

// 2. 判斷玩家顏色
$my_color = '';
if ($data['p1_id'] == $data['player_id']) $my_color = 'w';
else if ($data['p2_id'] == $data['player_id']) $my_color = 'b';
else {
    echo json_encode(['status' => 'error', 'message' => '觀戰者不可移動']);
    exit;
}

// 3. 檢查是否輪到該玩家
// 資料庫 turn 初始可能是 NULL (剛開始)，如果是 NULL 則預設 'w'
$current_turn = $data['turn'] ?? 'w';

if ($current_turn !== $my_color) {
    echo json_encode(['status' => 'error', 'message' => '不是你的回合']);
    exit;
}

// 4. 更新資料庫
try {
    $pdo->beginTransaction();

    // 下一回合顏色
    $next_turn = ($current_turn === 'w') ? 'b' : 'w';

    // 更新 Games 表 (棋盤狀態、回合、最後更新時間)
    // 注意：如果是剛開始 playing，這裡也會把 status 確保為 playing
    $stmt = $pdo->prepare("UPDATE games SET chessboard = :board, turn = :next, status = 'playing', last_update = NOW() WHERE game_id = :gid");
    $stmt->execute([
        'board' => $board_str,
        'next' => $next_turn,
        'gid' => $data['game_id']
    ]);

    // 寫入 Moves 歷史紀錄
    $stmt = $pdo->prepare("INSERT INTO moves (game_id, chessboard, move_text) VALUES (:gid, :board, :txt)");
    $stmt->execute([
        'gid' => $data['game_id'],
        'board' => $board_str,
        'txt' => $move_text
    ]);

    $pdo->commit();
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>