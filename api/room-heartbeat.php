<?php
require_once('../includes/config.php');

$identity = $_COOKIE['identity'] ?? null;
$room_code = $_POST['room_code'] ?? null;

// error_log("[DEBUG] " . $identity . ", " . $room_code);

if ($identity && $room_code) {
    $stmt = $pdo->prepare("SELECT `player_id` FROM `players` WHERE `player_identity` = :identity;");
    $stmt->execute(['identity' => $identity]);
    $player_id = $stmt->fetchColumn();

    // error_log("[DEBUG] player_id: " . $player_id);

    if ($player_id) {
        $stmt = $pdo->prepare("UPDATE `rooms` 
                               SET `last_conn` = NOW() 
                               WHERE `room_code` = ? AND (`p1_id` = ? OR `p2_id` = ?);
        ");
        $stmt->execute([$room_code, $player_id, $player_id]);
        // error_log($stmt->rowCount());
    }
    
} 

http_response_code(204);
?>