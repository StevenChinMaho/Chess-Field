<?php
header("Content-Type: text/event-stream");
header("Cache-Control: no-cache");
header("Connection: keep-alive");

require_once('../includes/config.php');

// 關閉 Session 寫入鎖，避免 SSE 卡住其他請求
session_write_close();

$room_code = $_GET['room_code'] ?? '';
if (!$room_code) exit;

// 取得 game_id
$stmt = $pdo->prepare("SELECT game_id FROM rooms WHERE room_code = ?");
$stmt->execute([$room_code]);
$game_id = $stmt->fetchColumn();

if (!$game_id) {
    echo "event: error\ndata: Room not found\n\n";
    flush();
    exit;
}

$last_timestamp = 0;
if (isset($_SERVER['HTTP_LAST_EVENT_ID']))
    $last_timestamp = intval($_SERVER['HTTP_LAST_EVENT_ID']);

// 設定超時防止 PHP process 永遠卡著 (例如 30秒重連一次)
$start_time = time();

while (true) {
    if ((time() - $start_time) > 29) die();

    // 檢查是否有更新
    // 我們檢查 last_update 的 timestamp
    $stmt = $pdo->prepare("SELECT chessboard, turn, status, UNIX_TIMESTAMP(last_update) as ts FROM games WHERE game_id = ?");
    $stmt->execute([$game_id]);
    $game = $stmt->fetch();

    if ($game) {
        $db_ts = $game['ts'];

        // 如果資料庫的時間比我們上次知道的時間新，就發送數據
        if ($db_ts > $last_timestamp) {
            $last_timestamp = $db_ts;
            
            $payload = [
                'board' => $game['chessboard'], // 64字元字串
                'turn' => $game['turn'] ?? 'w', // w 或 b
                'status' => $game['status']
            ];

            echo "id: " . $db_ts . "\n";
            echo "event: update\n";
            echo "data: " . json_encode($payload) . "\n\n";
            
            ob_flush();
            flush();
        }
    }

    // 每 0.5 秒檢查一次
    usleep(100000);
}
?>