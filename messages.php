<?php
include_once 'config/database.php';
include_once 'models/Message.php';
include_once 'models/Contract.php';
include_once 'includes/session.php';

if (!Session::isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$message = new Message($db);

$user_id = Session::get('user_id');
$contract_id = $_GET['contract_id'] ?? '';

if (!$contract_id) {
    header("Location: my_contracts.php");
    exit();
}

// Verify user has access to this contract
$contract = new Contract($db);
$contract_data = $contract->getContractById($contract_id);

if (
    !$contract_data ||
    ($contract_data['client_id'] != $user_id && $contract_data['freelancer_id'] != $user_id)
) {
    header("Location: my_contracts.php");
    exit();
}

$other_party = $contract_data['client_id'] == $user_id ?
    ['id' => $contract_data['freelancer_id'], 'name' => $contract_data['freelancer_name']] :
    ['id' => $contract_data['client_id'], 'name' => $contract_data['client_name']];

if ($_POST && isset($_POST['message'])) {
    $message->sender_id = $user_id;
    $message->receiver_id = $other_party['id'];
    $message->contract_id = $contract_id;
    $message->message_text = $_POST['message'];

    if ($message->create()) {
        header("Location: messages.php?contract_id=" . $contract_id);
        exit();
    }
}

$messages = $message->getContractMessages($contract_id, $user_id);
$unread_count = $message->getUnreadCount($user_id);
?>
<!DOCTYPE html>
<html>

<head>
    <title>Messages - Freelancing Platform</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-handshake"></i>
                FreelanceHub
            </a>
            <div class="navbar-menu">
                <?php if (Session::getUserType() == 'freelancer'): ?>
                    <a class="nav-link" href="freelancer_dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>Dashboard
                    </a>
                    <a class="nav-link" href="browse_jobs.php">
                        <i class="fas fa-search"></i>Browse Jobs
                    </a>
                <?php else: ?>
                    <a class="nav-link" href="client_dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>Dashboard
                    </a>
                    <a class="nav-link" href="my_jobs.php">
                        <i class="fas fa-briefcase"></i>My Jobs
                    </a>
                <?php endif; ?>
                <a class="nav-link active" href="my_contracts.php">
                    <i class="fas fa-file-contract"></i>Contracts
                </a>
                <?php if ($unread_count > 0): ?>
                    <a class="nav-link" href="my_contracts.php" style="color: #e53e3e;">
                        <i class="fas fa-bell"></i>Messages (<?php echo $unread_count; ?>)
                    </a>
                <?php endif; ?>
                <a class="nav-link" href="profile.php">
                    <i class="fas fa-user"></i>Profile
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-container">
            <div class="page-header">
                <div class="header-content">
                    <h1>Messages</h1>
                    <p>Project: <?php echo htmlspecialchars($contract_data['job_title']); ?></p>
                    <p>With: <?php echo htmlspecialchars($other_party['name']); ?></p>
                </div>
                <a href="my_contracts.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i>Back to Contracts
                </a>
            </div>

            <div class="messages-container">
                <div class="messages-sidebar">
                    <div class="contract-info">
                        <h3>Contract Details</h3>
                        <div class="info-list">
                            <div class="info-item">
                                <strong>Amount:</strong>
                                <span>$<?php echo number_format($contract_data['contract_amount'], 2); ?></span>
                            </div>
                            <div class="info-item">
                                <strong>Status:</strong>
                                <span class="status-<?php echo $contract_data['contract_status']; ?>">
                                    <?php echo ucfirst($contract_data['contract_status']); ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <strong>Started:</strong>
                                <span><?php echo date('M j, Y', strtotime($contract_data['start_date'])); ?></span>
                            </div>
                            <?php if ($contract_data['contract_status'] == 'completed'): ?>
                                <div class="info-item">
                                    <strong>Completed:</strong>
                                    <span><?php echo date('M j, Y', strtotime($contract_data['end_date'])); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($contract_data['contract_status'] == 'completed' && !$contract_data['has_reviewed']): ?>
                            <div class="review-prompt">
                                <p>Project completed! Would you like to leave a review?</p>
                                <a href="submit_review.php?contract_id=<?php echo $contract_id; ?>"
                                    class="btn btn-primary btn-full">
                                    <i class="fas fa-star"></i>Leave Review
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="messages-main">
                    <div class="messages-window">
                        <div class="messages-list" id="messagesList">
                            <?php if (empty($messages)): ?>
                                <div class="no-messages">
                                    <i class="fas fa-comments"></i>
                                    <p>No messages yet. Start the conversation!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($messages as $msg): ?>
                                    <div
                                        class="message <?php echo $msg['sender_id'] == $user_id ? 'message-sent' : 'message-received'; ?>">
                                        <div class="message-content">
                                            <p><?php echo nl2br(htmlspecialchars($msg['message_text'])); ?></p>
                                            <span class="message-time">
                                                <?php echo date('M j, g:i A', strtotime($msg['sent_at'])); ?>
                                                <?php if ($msg['sender_id'] == $user_id && $msg['is_read']): ?>
                                                    <i class="fas fa-check-double" title="Read"></i>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                        <div class="message-sender">
                                            <?php echo htmlspecialchars($msg['first_name'] . ' ' . $msg['last_name']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="message-input">
                            <form method="POST" class="message-form">
                                <div class="input-group">
                                    <textarea name="message" placeholder="Type your message..." required></textarea>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i>Send
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .page-container {
            padding: 2rem 0;
            margin-top: 60px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 2rem;
            gap: 2rem;
        }

        .header-content h1 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header-content p {
            color: var(--gray);
            margin: 0.25rem 0;
            font-size: 1rem;
        }

        .messages-container {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 2rem;
            height: 70vh;
        }

        .messages-sidebar {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .contract-info {
            background: var(--dark-card);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }

        .contract-info::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .contract-info h3 {
            margin: 0 0 1.5rem 0;
            color: var(--light);
            font-size: 1.3rem;
        }

        .info-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid var(--border);
        }

        .info-item strong {
            color: var(--light);
            font-size: 0.9rem;
            font-weight: 600;
        }

        .info-item span {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .review-prompt {
            background: rgba(16, 185, 129, 0.1);
            padding: 1.2rem;
            border-radius: 10px;
            text-align: center;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .review-prompt p {
            margin: 0 0 1rem 0;
            color: var(--primary-light);
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .btn-full {
            width: 100%;
            justify-content: center;
        }

        .messages-main {
            display: flex;
            flex-direction: column;
        }

        .messages-window {
            background: var(--dark-card);
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            height: 100%;
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }

        .messages-window::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .messages-list {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
            max-height: 500px;
            background: var(--dark-surface);
        }

        .message {
            display: flex;
            flex-direction: column;
            max-width: 70%;
            animation: fadeInUp 0.3s ease-out;
        }

        .message-sent {
            align-self: flex-end;
            align-items: flex-end;
        }

        .message-received {
            align-self: flex-start;
            align-items: flex-start;
        }

        .message-content {
            background: var(--dark-surface);
            padding: 1rem 1.2rem;
            border-radius: 15px;
            position: relative;
            border: 1px solid var(--border);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .message-sent .message-content {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--light);
            border-color: rgba(16, 185, 129, 0.3);
        }

        .message-content p {
            margin: 0 0 0.5rem 0;
            line-height: 1.4;
            font-size: 0.95rem;
        }

        .message-time {
            font-size: 0.75rem;
            opacity: 0.8;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .message-sent .message-time {
            color: rgba(255, 255, 255, 0.8);
        }

        .message-received .message-time {
            color: var(--gray);
        }

        .message-sender {
            font-size: 0.8rem;
            color: var(--gray);
            margin-top: 0.25rem;
            font-weight: 500;
        }

        .message-sent .message-sender {
            display: none;
        }

        .no-messages {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--gray);
        }

        .no-messages i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .no-messages p {
            margin: 0;
            font-size: 1rem;
        }

        .message-input {
            padding: 1.5rem;
            border-top: 1px solid var(--border);
            background: var(--dark-card);
        }

        .message-form {
            display: flex;
            gap: 1rem;
        }

        .input-group {
            display: flex;
            flex: 1;
            gap: 1rem;
        }

        .input-group textarea {
            flex: 1;
            padding: 1rem 1.2rem;
            border: 2px solid var(--border);
            border-radius: 12px;
            resize: none;
            height: 60px;
            font-family: inherit;
            background: var(--dark-surface);
            color: var(--light);
            transition: all 0.3s ease;
        }

        .input-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .input-group textarea::placeholder {
            color: var(--gray);
        }

        .input-group button {
            align-self: flex-end;
            padding: 1rem 1.5rem;
            border-radius: 12px;
        }

        /* Status styles */
        .status-active {
            color: var(--primary-light);
            background: rgba(16, 185, 129, 0.1);
            padding: 0.3rem 0.8rem;
            border-radius: 12px;
            border: 1px solid rgba(16, 185, 129, 0.3);
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-completed {
            color: var(--gray);
            background: rgba(148, 163, 184, 0.1);
            padding: 0.3rem 0.8rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.3);
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-pending {
            color: #f59e0b;
            background: rgba(245, 158, 11, 0.1);
            padding: 0.3rem 0.8rem;
            border-radius: 12px;
            border: 1px solid rgba(245, 158, 11, 0.3);
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Navigation active state */
        .nav-link.active {
            color: var(--primary-light);
            background: rgba(16, 185, 129, 0.1);
            border-left: 3px solid var(--primary);
        }

        /* Animation for messages */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Scrollbar styling for messages */
        .messages-list::-webkit-scrollbar {
            width: 6px;
        }

        .messages-list::-webkit-scrollbar-track {
            background: var(--dark-surface);
        }

        .messages-list::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 3px;
        }

        .messages-list::-webkit-scrollbar-thumb:hover {
            background: var(--primary-light);
        }

        /* Responsive Design */
        @media (max-width: 968px) {
            .messages-container {
                grid-template-columns: 1fr;
                height: auto;
            }

            .messages-sidebar {
                order: 2;
            }

            .messages-window {
                min-height: 500px;
            }
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                gap: 1rem;
            }

            .message {
                max-width: 85%;
            }

            .input-group {
                flex-direction: column;
            }

            .input-group button {
                align-self: stretch;
            }

            .header-content h1 {
                font-size: 1.8rem;
            }

            .messages-list {
                padding: 1rem;
            }

            .message-input {
                padding: 1rem;
            }
        }

        @media (max-width: 480px) {
            .page-container {
                padding: 1rem 0;
                margin-top: 60px;
            }

            .contract-info,
            .messages-window {
                border-radius: 10px;
            }

            .message-content {
                padding: 0.8rem 1rem;
            }

            .message {
                max-width: 90%;
            }
        }

        /* Notification badge in nav */
        .nav-link[style*="color: #e53e3e"] {
            color: var(--accent) !important;
            font-weight: 600;
        }

        .nav-link[style*="color: #e53e3e"]:hover {
            color: var(--primary-light) !important;
        }

        /* Hover effects */
        .btn-outline:hover {
            background: var(--primary);
            color: var(--light);
            transform: translateY(-2px);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--secondary) 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }

        /* Message read indicator */
        .fa-check-double {
            color: var(--accent);
            margin-left: 0.25rem;
        }

        .message-sent .fa-check-double {
            color: rgba(255, 255, 255, 0.8);
        }
    </style>

    <script>
        function scrollToBottom() {
            const messagesList = document.getElementById('messagesList');
            messagesList.scrollTop = messagesList.scrollHeight;
        }

        document.addEventListener('DOMContentLoaded', function () {
            scrollToBottom();

            setInterval(function () {
                fetch('get_messages.php?contract_id=<?php echo $contract_id; ?>')
                    .then(response => response.text())
                    .then(data => {
                        document.getElementById('messagesList').innerHTML = data;
                        scrollToBottom();
                    });
            }, 5000);
        });
    </script>
</body>

</html>