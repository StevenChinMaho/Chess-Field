<?php
require_once("includes/config.php");
require_once("includes/asset-versions.php");

// ===驗證玩家身分===

$player_name = $_POST['player-name'] ?? null;
$room_code = $_POST['room_code'] ?? null;
$identity = $_COOKIE['identity'] ?? null;

$stmt = $pdo->prepare("SELECT * FROM `players` WHERE `player_identity` = :identity");
$stmt->execute(['identity' => $identity]);
$player = $stmt->fetch();

if (!$player || !$player_name || !$room_code) {
    setcookie("identity", "", time() - 3600, "/", "chess.mofumofu.ddns.net", true, true);
    header("Location: index.php");
    die();
} else {
    //註冊玩家名稱
    $stmt = $pdo->prepare("UPDATE `players` 
                           SET `player_name` = :player_name, 
                               `last_room_code` = :last_room_code
                           WHERE `player_identity` = :identity
    ");
    $stmt->execute([
        'player_name' => $player_name, 
        'last_room_code' => $room_code,
        'identity' => $player['player_identity']
    ]);
}
$player_id = $player['player_id'];

// ===創建/加入房間===

$stmt = $pdo->prepare("SELECT r.*, g.status, g.game_id 
                       FROM `rooms` r
                       LEFT JOIN `games` g ON r.game_id = g.game_id
                       WHERE r.room_code = :room_code;");
$stmt->execute(['room_code' => $_POST['room_code']]);
$room = $stmt->fetch();

// 如果房間不存在就先建立
if (!$room) {
    // 創建遊戲
    $stmt = $pdo->prepare("INSERT INTO `games` (`status`) VALUE ('deciding');");
    $stmt->execute();
    $game_id = $pdo->lastInsertId();

    // 創建房間並代入game_id
    $stmt = $pdo->prepare("INSERT INTO `rooms` (`room_code`, `game_id`, `p1_id`) VALUE (:room_code, :game_id, :player_id)");
    $stmt->execute([
        'room_code' => $_POST['room_code'], 
        'game_id' => $game_id,
        'player_id' => $player['player_id']
    ]);

    $is_p1 = true;
} else {
    // 房間存在，檢查過期
    $last_conn = new DateTime($room['last_conn']);
    $now = new DateTime();

    //如果房間已過期就重置
    if ($now->getTimestamp() - $last_conn->getTimestamp() > EXPIRATION_TIME_SECONDS) {
        $stmt = $pdo->prepare("INSERT INTO `games` (`status`) VALUE ('deciding');");
        $stmt->execute();
        $new_game_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("UPDATE `rooms` 
                                SET `p1_id` = NULL,
                                    `p2_id` = NULL,
                                    `game_id` = :game_id,
                                    `last_conn` = NOW()
                                WHERE `room_code` = :room_code;");
        $stmt->execute(['room_code' => $room['room_code'], 'game_id' => $new_game_id]);

        // 更新當前變數狀態
        $room['status'] = 'deciding';
        $room['p1_id'] = NULL;
        $room['p2_id'] = NULL;
    }

    // ===核心跳轉與補位邏輯===

    $is_p1 = ($room['p1_id'] == $player_id);
    $is_p2 = ($room['p2_id'] == $player_id);

    // 如果其中一個是自己
    if ($is_p1 || $is_p2) {
        if ($room['status'] === 'deciding') {
            if ($is_p2) {
                // 異常狀態：deciding階段不該有P2，踢回首頁或轉成觀戰(這裡先踢回)
                header("Location: index.php?error=invalid_state");
                exit;
            }
            // 如果是 P1 且是 deciding -> 停留在本頁 (繼續設定)
        } else {
            // 狀態不是 deciding (waiting/playing/finished) -> 進入遊戲頁面
            header("Location: game.php?" . http_build_query(['room_code' => $room_code]));
            exit;
        }
    } 
    // 我是新來的 (不在房內)
    else {
        if ($room['status'] === 'deciding') {
            // 狀態為決定中
            if (empty($room['p1_id'])) {
                // P1 是空的 -> 我補位成為 P1
                $stmt = $pdo->prepare("UPDATE `rooms` SET `p1_id` = ? WHERE `room_code` = ? AND `p1_id` IS NULL");
                $stmt->execute([$player_id, $room_code]);
                // 成功搶到 P1 -> 停留在本頁
                $is_p1 = true;
            } else {
                // P1 已經有人，且狀態是 deciding -> 禁止進入 (不允許 P2)
                header("Location: index.php?error=room_setup_in_progress");
                exit;
            }
        } 
        elseif ($room['status'] === 'waiting') {
            // 狀態為等待中 (房主設定好了)
            if (empty($room['p2_id'])) {
                // P2 是空的 -> 我補位成為 P2
                $stmt = $pdo->prepare("UPDATE `rooms` SET `p2_id` = ? WHERE `room_code` = ? AND `p2_id` IS NULL");
                $stmt->execute([$player_id, $room_code]);
                
                // 補位成功 -> 跳轉到遊戲頁面
                header("Location: game.php?" . http_build_query(['room_code' => $room_code]));
                exit;
            } else {
                // P2 也滿了 -> 房間額滿
                header("Location: index.php?error=room_full");
                exit;
            }
        } 
        else {
            // 狀態是 playing 或 finished -> 房間已開打或結束
            // 根據需求，這裡可以選擇踢回首頁，或是允許觀戰(若是觀戰則直接導向 game.php)
            // header("Location: index.php?error=game_started");
            header("Location: game.php?" . http_build_query(['room_code' => $room_code]));
            exit;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chess Field Set Room</title>
    <link rel="stylesheet" href="css/set-room-style.css?v=<?php echo $asset_versions["set-room-style.css"]; ?>">
    <script src="js/set-room.js?v=<?php echo $asset_versions["set-room.js"]; ?>" defer></script>
    <script src="js/room-heartbeat.js?v=<?php echo $asset_versions["room-heartbeat.js"]; ?>" defer></script>
</head>

<body>
    <input type="hidden" id="room_code" value="<?php echo htmlspecialchars($room_code); ?>">
    <a href="index.php" class="back-button">返回主頁</a>
    <div class="setup-container">
        <h2>棋局設置</h2>

        <div class="setting-group">
            <label class="setting-label">選擇先後手</label>
            <div class="btn-group" id="side-group">
                <button class="btn" data-value="w"><span class="icon-box icon-white"></span>白方</button>
                <button class="btn" data-value="random"><span class="icon-box icon-random"></span>隨機</button>
                <button class="btn" data-value="b"><span class="icon-box icon-black"></span>黑方</button>
            </div>
        </div>

        <div class="setting-group">
            <label class="setting-label">時間設置 (每方時間)</label>
            <div class="range-container">
                <div class="range-display">
                    <span>起始時間</span>
                    <span id="time-display" class="range-value">10分鐘</span>
                </div>
                <input type="range" id="time-range" min="0" value="0">
            </div>
        </div>

        <div class="setting-group">
            <div class="range-container">
                <div class="range-display">
                    <span>每步加秒</span>
                    <span id="inc-display" class="range-value">0 秒</span>
                </div>
                <input type="range" id="inc-range" min="0" value="0">
            </div>
        </div>

        <div class="setting-group" hidden>
            <label class="setting-label">輔助功能 (合法移動提示)</label>
            <div class="btn-group" id="assist-group">
                <button class="btn selected" data-value="true">開啟</button>
                <button class="btn" data-value="false">關閉</button>
            </div>
            <div id="assist-warning" class="warning-text">⚠️警告：若關閉輔助功能，您可能會因為犯規2次而被判負。</div>
        </div>

        <button class="submit-btn" id="start-btn">繼續</button>
    </div>
</body>
</html>
