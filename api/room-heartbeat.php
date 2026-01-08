<?php
require_once('../includes/config.php');

$identity = $_COOKIE['identity'] ?? null;
$room_code = $_POST['room_code'] ?? null;

if ($identity && $room_code) {
    $stmt = $pdo->prepare("SELECT `player_id` FROM `players` WHERE `player_identity` = :identity;");
    $stmt->execute(['identity' => $identity]);
    $player_id = $stmt->fetchColumn();

    if ($player_id) {
        $stmt = $pdo->prepare("UPDATE `rooms` 
                               SET `last_conn` = NOW() 
                               WHERE `room_code` = ? AND (`p1_id` = ? OR `p2_id` = ?);
        ");
        $stmt->execute([$room_code, $player_id, $player_id]);
    }
    
} 

http_response_code(204);
?>