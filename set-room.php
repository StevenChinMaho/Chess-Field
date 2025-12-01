<?php
require_once("includes/config.php");
require_once("includes/asset-versions.php");

// $_POST[""]

?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chess Field Set Room</title>
    <link rel="stylesheet" href="css/set-room-style.css?v=<?php echo $asset_versions["set-room-style.css"]; ?>">
    <script src="js/set-room-style.js?v=<?php echo $asset_versions["set-room-style.js"]; ?>" defer></script>
</head>

<body>
    <div class="setup-container">
        <h2>棋局設置</h2>

        <div class="setting-group">
            <label class="setting-label">選擇先後手</label>
            <div class="btn-group" id="side-group">
                <button class="btn" data-value="white"><span class="icon-box icon-white"></span>白方</button>
                <button class="btn" data-value="random"><span class="icon-box icon-random"></span>隨機</button>
                <button class="btn" data-value="black"><span class="icon-box icon-black"></span>黑方</button>
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

        <div class="setting-group">
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