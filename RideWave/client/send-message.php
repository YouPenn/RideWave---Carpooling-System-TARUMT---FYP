<?php
require '../config/connectdb.php';
require '../vendor/autoload.php'; // Ensure Pusher is autoloaded

// Pusher credentials
$app_id = "1911725";
$key = "762f3ab70aeaf5fc60c5"; // Replace with your key
$secret = "273b9f09e95fc137e7ed"; // Replace with your secret
$cluster = "ap1"; // Replace with your cluster

$pusher = new Pusher\Pusher($key, $secret, $app_id, ['cluster' => $cluster]);

$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    $sender = $data['sender'];
    $sender_email = $data['sender_email'];
    $receiver_email = $data['receiver_email'];
    $message = $data['message'];

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO messages (sender_email, receiver_email, message) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $sender_email, $receiver_email, $message);
    $stmt->execute();

    // Trigger Pusher Event with sender_email and receiver_email included
    $pusher->trigger('chat-channel', 'new-message', [
        'userID'        => $receiver_email,
        'sender'        => $sender, // 'admin' or 'user'
        'message'       => $message,
        'sender_email'  => $sender_email,
        'receiver_email'=> $receiver_email
    ]);

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid data format']);
}
