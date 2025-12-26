<?php
require_once("includes/config.php");
require_once("includes/asset-versions.php");

// 1. 取得基本資訊
$room_code = $_GET['room_code'] ?? null;
$identity = $_COOKIE['identity'] ?? null;

// 2. 取得玩家資訊
$sql = "SELECT r.p1_id, r.p2_id, p.player_id 
        FROM rooms r 
        JOIN players p ON p.player_identity = :idn 
        WHERE r.room_code = :code AND TIMESTAMPDIFF(SECOND, r.last_conn, NOW()) <= :exp";
$stmt = $pdo->prepare($sql);
$stmt->execute(['idn' => $identity, 'code' => $room_code, 'exp' => EXPIRATION_TIME_SECONDS]);
$info = $stmt->fetch();

$my_side = 'spectator';
// 只有存在的人才能改變狀態
if ($info) {
    // 遊戲設定配置
    $game_settings = [
        'side' => $_POST['side'] ?? '',
        'time_minutes' => $_POST['timeMinutes'] ?? '',
        'increment' => $_POST['increment'] ?? '',
        'assist' => $_POST['assist'] ?? ''
    ];
    
    // 遊戲初始化設定
    $stmt = $pdo->prepare("SELECT g.game_id, g.status FROM rooms r 
                           JOIN games g ON r.game_id = g.game_id 
                           WHERE r.room_code = ?");
    $stmt->execute([$room_code]);
    $game_status = $stmt->fetch();
    
    // 如果是deciding狀態且是玩家一就切換成waiting
    if ($game_status['status'] === 'deciding' && $info['player_id'] === $info['p1_id']) {
        if ($game_settings['side'] != 'w' && $game_settings['side'] != 'b')
            $game_settings['side'] = mt_rand(0, 1) ? 'w' : 'b';
        
        $stmt = $pdo->prepare("UPDATE `games` 
                               SET `status` = 'waiting',
                               `p1_side` = :side
                               WHERE game_id = :game_id");
        $stmt->execute(['side' => $game_settings['side'], 'game_id' => $game_status['game_id']]);
    }
    
    // 如果是waiting狀態且是玩家二就切換成playing
    if ($game_status['status'] === 'waiting' && $info['player_id'] === $info['p2_id']) {
        $stmt = $pdo->prepare("UPDATE `games` 
                               SET `status` = 'playing'
                               WHERE game_id = :game_id");
        $stmt->execute(['game_id' => $game_status['game_id']]);
    }

    $stmt = $pdo->prepare("SELECT p1_side FROM games WHERE game_id = :game_id");
    $stmt->execute(['game_id' => $game_status['game_id']]);
    $p1_side = $stmt->fetchColumn();

    if ($info['p1_id'] == $info['player_id']) $my_side = $p1_side;
    else if ($info['p2_id'] == $info['player_id']) $my_side = $p1_side === 'w' ? 'b' : 'w';
} else {
    // 輸入不合法，導回主畫面
    header("Location: index.php?error=invaild_input");
    exit();
}

// 3. 取得初始棋盤 (如果剛進來是 reload，需要恢復盤面)
$sql_game = "SELECT g.game_id, g.chessboard, g.turn, g.status, g.outcome, g.en_passant_target, g.castling_rights FROM rooms r JOIN games g ON r.game_id = g.game_id WHERE r.room_code = ?";
$stmt_g = $pdo->prepare($sql_game);
$stmt_g->execute([$room_code]);
$game_data = $stmt_g->fetch();
$initial_board = $game_data['chessboard'] ?? 'rnbqkbnrpppppppp................................PPPPPPPPRNBQKBNR';
$initial_turn = $game_data['turn'] ?? 'w';
$initial_en_passant = $game_data['en_passant_target'] ?? null;
$initial_castling = $game_data['castling_rights'] ?? null;
$current_status = $game_data['status'] ?? 'deciding';
$current_outcome = $game_data['outcome'] ?? null;

$stmt_m = $pdo->prepare("SELECT COUNT(*) FROM moves WHERE game_id = ?");
$stmt_m->execute([$game_data['game_id']]);
$current_move_count = $stmt_m->fetchColumn();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game - Chess Field</title>
    <link rel="stylesheet" href="css/game-style.css?v=<?php echo $asset_versions["game-style.css"]; ?>">
    <script>
        // 將 PHP 變數傳給 JS
        const GAME_CONFIG = {
            roomCode: "<?php echo htmlspecialchars($room_code); ?>",
            mySide: "<?php echo $my_side; ?>", // 'w', 'b', or 'spectator'
            initialBoard: "<?php echo $initial_board; ?>",
            initialTurn: "<?php echo $initial_turn; ?>",
            initialEnPassant: "<?php echo $initial_en_passant; ?>",
            initialCastling: "<?php echo $initial_castling; ?>",
            initialMoveCount: <?php echo (int)$current_move_count; ?>,
            gameStatus: "<?php echo $current_status; ?>",
            outcome: "<?php echo $current_outcome; ?>"
        };
    </script>
    <script src="js/room-heartbeat.js?v=<?php echo $asset_versions["room-heartbeat.js"]; ?>" defer></script>
</head>
<body>
    <input type="hidden" id="room_code" value="<?php echo htmlspecialchars($room_code); ?>">
    <div class="game-layout">
        <div class="board-area">
            <div class="player-bar top">
                <span class="name">Opponent</span>
            </div>
            <div id="board" class="board"></div>
            <div class="player-bar btm">
                <span class="name">You</span>
            </div>
        </div>

        <div class="side-bar">
            <div class="moves" id="logs"></div>
            <div class="ctrls">
                <div id="status" class="status">載入中...</div>
                <button id="btn-action" class="btn" style="background-color: #d9534f;">終止遊戲</button>
            </div>
        </div>
    </div>

    <div id="game-modal" class="modal-overlay">
        <div class="modal-content">
            <h2 id="modal-title">對局結束</h2>
            <p id="modal-msg"></p>
            <button onclick="location.href='index.php'" class="modal-btn">返回首頁</button>
        </div>
    </div>

    <script src="js/game.js?v=<?php echo $asset_versions['game.js']; ?>" defer></script>
</body>
</html>