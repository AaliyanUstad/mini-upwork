<?php
include_once 'config/database.php';
include_once 'models/Message.php';
include_once 'includes/session.php';

if(!Session::isLoggedIn()){
    exit();
}

$database = new Database();
$db = $database->getConnection();
$message = new Message($db);

$user_id = Session::get('user_id');
$contract_id = $_GET['contract_id'] ?? '';

if(!$contract_id) {
    exit();
}

$messages = $message->getContractMessages($contract_id, $user_id);

if(empty($messages)) {
    echo '<div class="no-messages">
            <i class="fas fa-comments"></i>
            <p>No messages yet. Start the conversation!</p>
          </div>';
} else {
    foreach($messages as $msg) {
        $message_class = $msg['sender_id'] == $user_id ? 'message-sent' : 'message-received';
        echo '<div class="message ' . $message_class . '">
                <div class="message-content">
                    <p>' . nl2br(htmlspecialchars($msg['message_text'])) . '</p>
                    <span class="message-time">' . 
                    date('M j, g:i A', strtotime($msg['sent_at']));
                    
        if($msg['sender_id'] == $user_id && $msg['is_read']) {
            echo '<i class="fas fa-check-double" title="Read"></i>';
        }
        
        echo '</span>
                </div>
                <div class="message-sender">' . 
                htmlspecialchars($msg['first_name'] . ' ' . $msg['last_name']) . 
                '</div>
              </div>';
    }
}
?>