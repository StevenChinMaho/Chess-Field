<?php
header("Content-Type: text/event-stream");
header("Cache-Control: no-cache");

require_once('../includes/config.php');

$lastId = 0;

while (true) {
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE id > :lastId ORDER BY id ASC");
    $stmt->execute([':lastId' => $lastId]);

    while ($row = $stmt->fetch()) {
        $lastId = $row['id'];
        echo "event: message\n";
        echo "data: " . json_encode($row) . "\n\n";
        ob_flush();
        flush();
    }

    sleep(1);
}
?>
