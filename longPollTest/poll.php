<?php
// poll.php - 長輪詢接收訊息
session_write_close(); // 避免 session 鎖住
header("Content-Type: application/json");

$lastId = isset($_GET["lastId"]) ? (int)$_GET["lastId"] : 0;
$timeout = 20; // 最長等待秒數
$start = time();

while (true) {
    // 簡單用檔案模擬訊息佇列
    $messages = file("chat.log", FILE_IGNORE_NEW_LINES);
    $count = count($messages);

    // 如果有新訊息就回傳
    if ($count > $lastId) {
        $newMsg = $messages[$count - 1];
        echo json_encode(["id" => $count, "message" => $newMsg]);
        flush();
        exit;
    }

    // 超時回傳空結果
    if ((time() - $start) > $timeout) {
        echo json_encode(["id" => $lastId, "message" => null]);
        flush();
        exit;
    }

    usleep(500000); // 每 0.5 秒檢查一次
}
