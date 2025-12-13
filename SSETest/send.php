<?php
require_once('../includes/config.php');

$user = $_POST['user'] ?? '';
$message = $_POST['message'] ?? '';

$stmt = $pdo->prepare("INSERT INTO messages (user, message) VALUES (:user, :message)");
$stmt->execute([
    ':user' => $user,
    ':message' => $message
]);

echo "OK";
?>
