<?php
require '../config/connectdb.php';

$sender_email = $_GET['sender_email'] ?? '';
$receiver_email = $_GET['receiver_email'] ?? '';

if (empty($sender_email) || empty($receiver_email)) {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

$stmt = $conn->prepare("
    SELECT sender_email, receiver_email, message, timestamp
    FROM messages
    WHERE (sender_email = ? AND receiver_email = ?)
       OR (sender_email = ? AND receiver_email = ?)
    ORDER BY timestamp ASC
");
$stmt->bind_param("ssss", $sender_email, $receiver_email, $receiver_email, $sender_email);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

echo json_encode(['success' => true, 'messages' => $messages]);
