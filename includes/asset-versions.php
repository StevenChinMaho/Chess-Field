<?php 
$asset_versions = [
    "index-style.css" => filemtime("css/index-style.css"),
    "set-room-style.css" => filemtime("css/set-room-style.css"),
    "game.js" => filemtime("js/game.js"),
    "index.js" => filemtime("js/index.js"),
    "set-room.js" => filemtime("js/set-room.js"),
    "room-heartbeat.js" => filemtime("js/room-heartbeat.js")
];
?>