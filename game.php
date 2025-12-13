<?php
require_once("includes/config.php");
require_once("includes/asset-versions.php");
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game - Chess Field</title>
    <link rel="stylesheet" href="css/game-style.css">
</head>
<body>
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