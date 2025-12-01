<?php 
require_once("includes/config.php");

$userName = "";

if (isset($_COOKIE['identity'])) {
    echo "歡迎回來，" . $_COOKIE['identity'];
} else {
    setcookie("identity", bin2hex(random_bytes(32)), time() + 315360000, "/", "chess.mofumofu.ddns.net", true, true); 
    echo "第一次訪問，已設定 cookie！";

}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chess Field Homepage</title>
    <link rel="stylesheet" href="css/homepage-style.css">
    <script src="js/homepage-style.js" defer></script>
</head>
<body>
    <div class="homepage">
        <h1>Chess Field</h1>

        <form method="post" action="room.php" id="roomForm">
            <div class="input-box">
                <label for="playerName">玩家名字: </label>
                <input type="text" id="playerName" name="playerName" placeholder="請輸入您的名字">
            </div>

            <div class="input-box">
                <label for="roomCode">房號: </label>
                <input type="text" id="roomCode" name="roomCode" placeholder="請輸入房號">
            </div>

            <button id="startGameBtn" class="start-button" disabled>開始遊戲</button>
        </form>
    </div>
</body>
</html>