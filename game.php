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
        WHERE r.room_code = :code";
$stmt = $pdo->prepare($sql);
$stmt->execute(['idn' => $identity, 'code' => $room_code]);
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
}

// 3. 取得初始棋盤 (如果剛進來是 reload，需要恢復盤面)
$sql_game = "SELECT g.chessboard, g.turn FROM rooms r JOIN games g ON r.game_id = g.game_id WHERE r.room_code = ?";
$stmt_g = $pdo->prepare($sql_game);
$stmt_g->execute([$room_code]);
$game_data = $stmt_g->fetch();
$initial_board = $game_data['chessboard'] ?? 'rnbqkbnrpppppppp................................PPPPPPPPRNBQKBNR';
$initial_turn = $game_data['turn'] ?? 'w';
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game - Chess Field</title>
    <link rel="stylesheet" href="css/game-style.css">
    <script>
        // 將 PHP 變數傳給 JS
        const GAME_CONFIG = {
            roomCode: "<?php echo htmlspecialchars($room_code); ?>",
            mySide: "<?php echo $my_side; ?>", // 'w', 'b', or 'spectator'
            initialBoard: "<?php echo $initial_board; ?>",
            initialTurn: "<?php echo $initial_turn; ?>"
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
                <div id="status" class="status">White's Turn</div>
                <button id="btn-reset" class="btn-gray">Restart</button>
            </div>
        </div>
    </div>

    <script src="js/game.js?v=<?php echo $asset_versions['game.js']; ?>" defer></script>
</body>
</html>