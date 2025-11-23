<?php
// send.php - 接收客戶端送出的訊息
if (!empty($_POST["msg"])) {
    $msg = strip_tags($_POST["msg"]);
    file_put_contents("chat.log", $msg . PHP_EOL, FILE_APPEND);
}
