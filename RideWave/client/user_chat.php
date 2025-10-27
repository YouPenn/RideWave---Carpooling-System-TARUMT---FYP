<?php
include_once('../config/config.php');
include_once('remember_check.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in
if (!isset($_SESSION["loggedin"])) {
    header("Location: login.php");
    exit();
}

$loggedInUserID = $_SESSION["userID"]; // Logged-in user's ID

// Set timezone to Malaysia and cleanup old messages
$today = new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur')); // Set timezone to Malaysia
$dayOfWeek = $today->format('N'); // 1 = Monday, 7 = Sunday
$hour = $today->format('H');
$minute = $today->format('i');

// 1) Set `delDate` for messages that have `NULL` in `delDate`
$mysqli->query("UPDATE messages SET delDate = CURDATE() WHERE delDate IS NULL");

// 2) Delete messages older than 7 days from their `delDate`
$mysqli->query("DELETE FROM messages WHERE CURDATE() > DATE_ADD(delDate, INTERVAL 7 DAY)");


// For any new message that doesn't have delDate, set it now
$mysqli->query("UPDATE messages SET delDate = CURDATE() WHERE delDate IS NULL");

$navUserID = $_SESSION["userID"];

// Fetch user data from the database (For NAV)
$navsql = "SELECT username, userImg FROM user WHERE userID = ?";
if ($stmt = $mysqli->prepare($navsql)) {
    $stmt->bind_param("i", $navUserID);
    $stmt->execute();
    $stmt->bind_result($navUsername, $navUserImg);
    $stmt->fetch();
    $stmt->close();
} else {
    $navUsername = "Guest";
    $navUserImg = null;
}

// Use default avatar if user image is not set or empty
$navUserImgPath = $navUserImg ? $navUserImg : "../image/user_avatar.png";

// Fetch driver ID from the driver table using $navUserID
$navDriverId = null; // Default value if the user is not a driver
$driverSql = "SELECT driverID FROM driver WHERE userID = ?";
if ($stmt = $mysqli->prepare($driverSql)) {
    $stmt->bind_param("i", $navUserID);
    $stmt->execute();
    $stmt->bind_result($navDriverId);
    $stmt->fetch();
    $stmt->close();
}

// Fetch logged-in user's details
$queryLoggedInUser = "SELECT username, email FROM user WHERE userID = ?";
$stmt = $mysqli->prepare($queryLoggedInUser);
$stmt->bind_param("i", $loggedInUserID);
$stmt->execute();
$loggedInUserResult = $stmt->get_result();
$loggedInUser = $loggedInUserResult->fetch_assoc();
$stmt->close();

// Fetch other users (exclude logged-in user)
$queryOtherUsers = "SELECT userID, username, email FROM user WHERE userID != ?";
$stmt = $mysqli->prepare($queryOtherUsers);
$stmt->bind_param("i", $loggedInUserID);
$stmt->execute();
$otherUsersResult = $stmt->get_result();
$otherUsers = [];
while ($row = $otherUsersResult->fetch_assoc()) {
    $otherUsers[] = $row;
}
$stmt->close();

// Fetch admins
$queryAdmins = "SELECT adminID, username, email FROM admin";
$resultAdmins = $mysqli->query($queryAdmins);
$admins = [];
while ($row = $resultAdmins->fetch_assoc()) {
    $admins[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="assets/" data-template="vertical-menu-template-free">
<head>
    <?php include_once('header.php'); ?>
    <title>User Chat</title>
    <style>
        .chat-header .chat-about { margin-bottom: 10px; }
        .chat-header .chat-email { font-size: 0.9rem; color: gray; }
        .chat-box { height: calc(100vh - 150px); overflow-y: auto; }
        #chatMessages { flex-grow: 1; overflow-y: auto; }
        .message-bubble-right {
            background-color: #007bff; color: white; padding: 10px;
            border-radius: 10px; display: inline-block; max-width: 70%;
            margin-left: auto; text-align: left; word-wrap: break-word;
        }
        .message-bubble-left {
            background-color: #f1f1f1; color: black; padding: 10px;
            border-radius: 10px; display: inline-block; max-width: 70%;
            margin-right: auto; text-align: left; word-wrap: break-word;
        }
        .message-container { display: flex; margin: 5px 0; }
        .message-container.user { justify-content: flex-end; }
        .message-container.admin { justify-content: flex-start; }
        .card-footer { display: flex; align-items: center; padding: 10px; }
        .card-footer input#messageInput { flex-grow: 1; font-size: 0.9rem; height: 36px; }
        .card-footer button#sendMessage { margin-left: 10px; }
        .chat-list li { display: flex; align-items: center; margin-bottom: 10px; cursor: pointer; }
        .chat-list .about { flex-grow: 1; overflow: hidden; }
        .chat-list .name, .chat-list .status {
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .chat-list .name { font-weight: bold; }
        .chat-list .status { color: gray; }
    </style>
</head>
<body>
    <?php include_once('nav.php'); ?>
    
<div class="content-wrapper">
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">User /</span> Chat</h4>

        <div class="row">
            <!-- User List -->
            
            <!-- Chat Box -->
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header">
                        <div class="chat-header">
                            <div class="chat-about">
                                <h6 id="chatUserName">Select a User or Admin</h6>
                                <span id="chatUserEmail" class="chat-email">Email will appear here</span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body chat-box" id="chatBox">
                        <div id="chatMessages"></div>
                    </div>
                    <div class="card-footer">
                        <input type="text" id="messageInput" class="form-control" placeholder="Type a message">
                        <button id="sendMessage" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send</button>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h5>User List</h5>
                        <input type="text" id="searchUser" class="form-control mb-2" placeholder="Search Users...">
                    </div>
                    <div class="card-body">
                        <ul class="chat-list list-unstyled" id="userList">
                            <?php foreach ($otherUsers as $user): ?>
                                <li class="clearfix" onclick="selectParticipant('<?php echo $loggedInUser['email']; ?>', '<?php echo htmlspecialchars($user['email']); ?>', '<?php echo htmlspecialchars($user['username']); ?>')">
                                    <div class="about">
                                        <div class="name"><?php echo htmlspecialchars($user['username']); ?></div>
                                        <div class="status"><?php echo htmlspecialchars($user['email']); ?></div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Admin List -->
            <div class="col-md-3 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h5>Admin List</h5>
                        <input type="text" id="searchAdmin" class="form-control mb-2" placeholder="Search Admins...">
                    </div>
                    <div class="card-body">
                        <ul class="chat-list list-unstyled" id="adminList">
                            <?php foreach ($admins as $admin): ?>
                                <li class="clearfix" onclick="selectParticipant('<?php echo $loggedInUser['email']; ?>', '<?php echo htmlspecialchars($admin['email']); ?>', '<?php echo htmlspecialchars($admin['username'] . ' (Admin)'); ?>')">
                                    <div class="about">
                                        <div class="name"><?php echo htmlspecialchars($admin['username']); ?> (Admin)</div>
                                        <div class="status"><?php echo htmlspecialchars($admin['email']); ?></div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>


        </div>
    </div>
</div>

    <?php include_once('footer.php'); ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://js.pusher.com/7.2/pusher.min.js"></script>
<script>
    const loggedInUserEmail = "<?php echo $loggedInUser['email']; ?>";
    let currentSenderEmail = loggedInUserEmail;
    let currentReceiverEmail = null;
    let refreshInterval = null;

    const pusher = new Pusher('762f3ab70aeaf5fc60c5', {
        cluster: 'ap1',
        encrypted: true,
    });

    const channel = pusher.subscribe('chat-channel');
    channel.bind('new-message', function (data) {
        const { userID, sender, message, sender_email } = data;
        if (sender_email === loggedInUserEmail) return; // Skip if the message is from the logged-in user
        if (currentReceiverEmail === userID) {
            appendMessageToChat(message, sender === 'user' ? 'user' : 'admin');
        }
    });

    function selectParticipant(senderEmail, receiverEmail, receiverName) {
        currentSenderEmail = senderEmail;
        currentReceiverEmail = receiverEmail;
        document.getElementById('chatUserName').innerText = receiverName;
        document.getElementById('chatUserEmail').innerText = receiverEmail;
        loadConversation(senderEmail, receiverEmail);
        if (refreshInterval) clearInterval(refreshInterval);
        refreshInterval = setInterval(() => {
            loadConversation(currentSenderEmail, currentReceiverEmail);
        }, 5000);
    }

    function loadConversation(senderEmail, receiverEmail) {
        fetch(`load-messages.php?sender_email=${encodeURIComponent(senderEmail)}&receiver_email=${encodeURIComponent(receiverEmail)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderMessages(data.messages, senderEmail);
                } else {
                    renderMessages([], senderEmail);
                }
            })
            .catch(err => console.error('Error loading messages:', err));
    }

    function renderMessages(messages, senderEmail) {
        const chatMessages = document.getElementById('chatMessages');
        chatMessages.innerHTML = '';
        messages.forEach(({ message, sender_email }) => {
            const senderType = (sender_email === senderEmail) ? 'user' : 'admin';
            appendMessageToChat(message, senderType);
        });
    }

    function appendMessageToChat(message, senderType) {
        const chatMessages = document.getElementById('chatMessages');
        const messageContainer = document.createElement('div');
        messageContainer.className = senderType === 'user' ? 'message-container user' : 'message-container admin';
        const messageBubble = document.createElement('div');
        messageBubble.className = senderType === 'user' ? 'message-bubble-left' : 'message-bubble-right';
        messageBubble.innerText = message;
        messageContainer.appendChild(messageBubble);
        chatMessages.appendChild(messageContainer);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function sendMessage() {
        const messageInput = document.getElementById('messageInput');
        const message = messageInput.value.trim();
        if (!currentReceiverEmail) {
            Swal.fire({
                icon: 'warning',
                title: 'No participant selected!',
                text: 'Please select an user or admin first.',
            });
            return;
        }
        if (message) {
            appendMessageToChat(message, 'user');
            messageInput.value = '';
            fetch('send-message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    sender: 'user',
                    sender_email: currentSenderEmail,
                    receiver_email: currentReceiverEmail,
                    message: message
                }),
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('Failed to send message:', data.error);
                }
            })
            .catch(error => console.error('Error sending message:', error));
        }
    }

    document.getElementById('sendMessage').addEventListener('click', sendMessage);
    document.getElementById('messageInput').addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            sendMessage();
        }
    });

function filterList(inputId, listId) {
    const searchValue = document.getElementById(inputId).value.toLowerCase();
    const items = document.querySelectorAll(`#${listId} li`);
    items.forEach(item => {
        const name = item.querySelector('.name').innerText.toLowerCase();
        const email = item.querySelector('.status').innerText.toLowerCase();
        if (name.includes(searchValue) || email.includes(searchValue)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

document.getElementById('searchUser').addEventListener('input', function () {
    filterList('searchUser', 'userList');
});

document.getElementById('searchAdmin').addEventListener('input', function () {
    filterList('searchAdmin', 'adminList');
});


    document.addEventListener('DOMContentLoaded', function() {
        window.onload = function() {
            Swal.fire({
                icon: 'info',
                title: 'Temporary Message Notice',
                text: 'These messages are temporary and will be deleted after one week.'
            });
        }
    });
    
    
</script>


</body>
</html>
