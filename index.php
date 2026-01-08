<?php 
require_once("includes/config.php");
require_once("includes/asset-versions.php");

$player_name = "";
$last_room_code = "";
$identity = $_COOKIE['identity'] ?? null;

// 接收錯誤返回訊息
$error_message = $_GET['error'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM `players` WHERE `player_identity` = :identity");
$stmt->execute(['identity' => $identity]);
$row = $stmt->fetch();

if (!$row) {
    $identity = bin2hex(random_bytes(32));
    setcookie("identity", $identity, time() + 315360000, "/", "chess.mofumofu.ddns.net", true, true);
    $stmt = $pdo->prepare("INSERT INTO `players`(`player_identity`) VALUE (:identity)");
    $stmt->execute(['identity' => $identity ]);
} else {
    $player_name = $row['player_name'];
    $last_room_code = $row['last_room_code'];
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chess Field Homepage</title>
    <link rel="stylesheet" href="css/index-style.css?v=<?php echo $asset_versions["index-style.css"]; ?>">
    <script src="js/index.js?v=<?php echo $asset_versions["index.js"]; ?>" defer></script>
</head>
<body>
    <input hidden id="error-msg" value="<?php echo htmlspecialchars($error_message); ?>">
    <div class="homepage">
        <h1>Chess Field</h1>

        <form method="post" action="set-room.php" id="roomForm">
            <div class="input-box">
                <label for="player-name">玩家名字: </label>
                <input type="text" id="player-name" name="player-name" value="<?php echo htmlspecialchars($player_name); ?>" placeholder="請輸入您的名字">
            </div>

            <div class="input-box">
                <label for="room_code">房號: </label>
                <input type="text" id="room_code" name="room_code" value="<?php echo htmlspecialchars($last_room_code); ?>" placeholder="請輸入房號">
            </div>

            <button id="start-game-btn" class="start-button" disabled>開始遊戲</button>
        </form>
    </div>
</body>
</html>