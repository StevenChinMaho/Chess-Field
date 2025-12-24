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
$sql = "SELECT r.*, g.game_id, g.p1_side , g.turn, g.status, p.player_id 
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
if ($data['p1_id'] == $data['player_id']) $my_color = $data['p1_side'];
else if ($data['p2_id'] == $data['player_id']) $my_color = $data['p1_side'] === 'w' ? 'b' : 'w';
else {
    echo json_encode(['status' => 'error', 'message' => '觀戰者不可移動']);
    exit;
}

// 3. 檢查是否輪到該玩家
$current_turn = $data['turn'];

if ($current_turn !== $my_color) {
    echo json_encode(['status' => 'error', 'message' => '不是你的回合']);
    exit;
}

// 4. 更新資料庫
try {
    $pdo->beginTransaction();

    // 取得目前資料庫的棋盤（用來判斷是否為兩格兵）
    $stmt = $pdo->prepare("SELECT chessboard FROM games WHERE game_id = ?");
    $stmt->execute([$data['game_id']]);
    $old_board = $stmt->fetchColumn();

    // 判斷是否有兩格兵移動，並計算 en passant 目標(e.g. 'e3')
    $en_passant = null;
    if ($old_board) {
        $old = str_split($old_board);
        $new = str_split($board_str);
        $from = null; $to = null;
        for ($i = 0; $i < 64; $i++) {
            if ($old[$i] !== $new[$i]) {
                if ($old[$i] !== '.' && $new[$i] === '.' && strtolower($old[$i]) === 'p') {
                    $from = $i;
                }
                if ($new[$i] !== '.' && $old[$i] === '.' && strtolower($new[$i]) === 'p') {
                    $to = $i;
                }
            }
        }
        if ($from !== null && $to !== null) {
            $from_r = intdiv($from, 8);
            $to_r = intdiv($to, 8);
            $from_c = $from % 8;
            $to_c = $to % 8;
            if (abs($from_r - $to_r) === 2 && $from_c === $to_c) {
                $mid_r = ($from_r + $to_r) / 2;
                $file = chr(97 + $to_c);
                $rank = 8 - $mid_r;
                $en_passant = $file . $rank;
            }
        }
    }

    // 下一回合顏色
    $next_turn = ($current_turn === 'w') ? 'b' : 'w';

    // 更新 Games 表 (棋盤狀態、回合、最後更新時間、en_passant_target)
    $stmt = $pdo->prepare("UPDATE games SET chessboard = :board, turn = :next, status = 'playing', last_update = NOW(), en_passant_target = :enpass WHERE game_id = :gid");
    $stmt->execute([
        'board' => $board_str,
        'next' => $next_turn,
        'enpass' => $en_passant,
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