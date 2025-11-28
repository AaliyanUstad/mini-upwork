<?php
include_once 'config/database.php';
include_once 'models/Message.php';
include_once 'models/Contract.php';
include_once 'includes/session.php';

if (!Session::isLoggedIn() || Session::getUserType() != 'freelancer') {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$message = new Message($db);
$contract = new Contract($db);

$freelancer_id = Session::get('user_id');

// Get all contracts for this freelancer with message info
$query = "SELECT c.*, j.title as job_title, 
                 client.first_name as c_first_name, 
                 client.last_name as c_last_name,
                 client.company_name as c_company_name,
                 (SELECT COUNT(*) FROM messages m WHERE m.contract_id = c.id AND m.receiver_id = ? AND m.is_read = 0) as unread_count,
                 (SELECT MAX(sent_at) FROM messages m WHERE m.contract_id = c.id) as last_message_time
          FROM contracts c 
          JOIN jobs j ON c.job_id = j.id 
          JOIN users client ON c.client_id = client.id 
          WHERE c.freelancer_id = ? 
          ORDER BY last_message_time DESC";

$stmt = $db->prepare($query);
$stmt->execute([$freelancer_id, $freelancer_id]);
$contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unread_count = $message->getUnreadCount($freelancer_id);
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
                <a class="nav-link" href="freelancer_dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>Dashboard
                </a>
                <a class="nav-link" href="browse_jobs.php">
                    <i class="fas fa-search"></i>Browse Jobs
                </a>
                <a class="nav-link" href="my_proposals.php">
                    <i class="fas fa-paper-plane"></i>My Proposals
                </a>
                <a class="nav-link active" href="freelancer_messages.php">
                    <i class="fas fa-comments"></i>Messages
                    <?php if ($unread_count > 0): ?>
                        <span class="notification-badge"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
                </a>
                <a class="nav-link" href="my_contracts.php">
                    <i class="fas fa-file-contract"></i>Contracts
                </a>
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
                <h1>Messages</h1>
                <p>Communicate with your clients</p>
            </div>

            <div class="messages-overview">
                <div class="conversations-list">
                    <h2>Active Conversations</h2>

                    <?php if (!empty($contracts)): ?>
                        <div class="conversations-grid">
                            <?php foreach ($contracts as $contract_item): ?>
                                <div class="conversation-card">
                                    <div class="conversation-header">
                                        <h3><?php echo htmlspecialchars($contract_item['job_title']); ?></h3>
                                        <?php if ($contract_item['unread_count'] > 0): ?>
                                            <span class="unread-badge"><?php echo $contract_item['unread_count']; ?></span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="conversation-info">
                                        <div class="client-info">
                                            <i class="fas fa-user"></i>
                                            <?php echo htmlspecialchars($contract_item['c_company_name'] ?: $contract_item['c_first_name'] . ' ' . $contract_item['c_last_name']); ?>
                                        </div>
                                        <div class="contract-status">
                                            <span class="status-<?php echo $contract_item['contract_status']; ?>">
                                                <?php echo ucfirst($contract_item['contract_status']); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="conversation-meta">
                                        <div class="contract-amount">
                                            $<?php echo number_format($contract_item['contract_amount'], 2); ?>
                                        </div>
                                        <?php if ($contract_item['last_message_time']): ?>
                                            <div class="last-message">
                                                Last activity:
                                                <?php echo date('M j, g:i A', strtotime($contract_item['last_message_time'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="conversation-actions">
                                        <a href="messages.php?contract_id=<?php echo $contract_item['id']; ?>"
                                            class="btn btn-primary">
                                            <i class="fas fa-comments"></i>Open Chat
                                        </a>
                                        <a href="view_contract.php?id=<?php echo $contract_item['id']; ?>"
                                            class="btn btn-outline">
                                            <i class="fas fa-eye"></i>View Contract
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-conversations">
                            <i class="fas fa-comments"></i>
                            <h3>No active conversations</h3>
                            <p>You'll see messages here once clients hire you for projects.</p>
                            <a href="browse_jobs.php" class="btn btn-primary">Browse Jobs</a>
                            <a href="my_proposals.php" class="btn btn-outline">View My Proposals</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Same CSS as client_messages.php -->
    <style>
        .page-container {
            padding: 2rem 0;
            margin-top: 60px;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-header p {
            color: var(--gray);
            font-size: 1.1rem;
        }

        .conversations-list h2 {
            margin-bottom: 1.5rem;
            color: var(--light);
            font-size: 1.8rem;
        }

        .conversations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 1.5rem;
        }

        .conversation-card {
            background: var(--dark-card);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }

        .conversation-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .conversation-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            border-color: var(--primary);
        }

        .conversation-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }

        .conversation-header h3 {
            margin: 0;
            color: var(--light);
            font-size: 1.3rem;
            flex: 1;
            line-height: 1.4;
        }

        .unread-badge {
            background: var(--accent);
            color: white;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(34, 197, 94, 0.4);
            flex-shrink: 0;
        }

        .conversation-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .client-info {
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
        }

        .client-info i {
            color: var(--primary);
        }

        .contract-status {
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-active {
            color: var(--primary-light);
            background: rgba(16, 185, 129, 0.1);
            padding: 0.3rem 0.8rem;
            border-radius: 12px;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .status-completed {
            color: var(--gray);
            background: rgba(148, 163, 184, 0.1);
            padding: 0.3rem 0.8rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.3);
        }

        .status-pending {
            color: #f59e0b;
            background: rgba(245, 158, 11, 0.1);
            padding: 0.3rem 0.8rem;
            border-radius: 12px;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .status-in_progress {
            color: #60a5fa;
            background: rgba(96, 165, 250, 0.1);
            padding: 0.3rem 0.8rem;
            border-radius: 12px;
            border: 1px solid rgba(96, 165, 250, 0.3);
        }

        .conversation-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .contract-amount {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--primary);
            text-shadow: 0 0 10px rgba(16, 185, 129, 0.3);
        }

        .last-message {
            color: var(--gray);
            font-size: 0.85rem;
        }

        .conversation-actions {
            display: flex;
            gap: 0.75rem;
        }

        .conversation-actions .btn {
            flex: 1;
            text-align: center;
            padding: 0.6rem 1rem;
            font-size: 0.9rem;
            justify-content: center;
            min-width: 120px;
        }

        .no-conversations {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--dark-card);
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            border: 2px dashed var(--border);
            grid-column: 1 / -1;
        }

        .no-conversations i {
            font-size: 4rem;
            color: var(--gray);
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        .no-conversations h3 {
            margin-bottom: 1rem;
            color: var(--light);
            font-size: 1.5rem;
        }

        .no-conversations p {
            color: var(--gray);
            margin-bottom: 2rem;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
            font-size: 1rem;
            line-height: 1.6;
        }

        .no-conversations .btn {
            margin: 0 0.5rem;
        }

        .notification-badge {
            background: var(--accent);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
            margin-left: 0.25rem;
            box-shadow: 0 2px 8px rgba(34, 197, 94, 0.4);
        }

        /* Navigation active state */
        .nav-link.active {
            color: var(--primary-light);
            background: rgba(16, 185, 129, 0.1);
            border-left: 3px solid var(--primary);
        }

        /* Animation for conversation cards */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .conversation-card {
            animation: fadeInUp 0.5s ease-out;
        }

        .conversations-grid .conversation-card:nth-child(1) {
            animation-delay: 0.1s;
        }

        .conversations-grid .conversation-card:nth-child(2) {
            animation-delay: 0.2s;
        }

        .conversations-grid .conversation-card:nth-child(3) {
            animation-delay: 0.3s;
        }

        .conversations-grid .conversation-card:nth-child(4) {
            animation-delay: 0.4s;
        }

        .conversations-grid .conversation-card:nth-child(5) {
            animation-delay: 0.5s;
        }

        /* Hover effects for buttons */
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--secondary) 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: var(--light);
            transform: translateY(-2px);
        }

        /* Responsive Design */
        @media (max-width: 968px) {
            .conversations-grid {
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .conversations-grid {
                grid-template-columns: 1fr;
            }

            .conversation-header {
                flex-direction: column;
                gap: 0.5rem;
                align-items: flex-start;
            }

            .conversation-info {
                flex-direction: column;
                gap: 0.8rem;
                align-items: flex-start;
            }

            .conversation-meta {
                flex-direction: column;
                gap: 0.8rem;
                align-items: flex-start;
            }

            .conversation-actions {
                flex-direction: column;
            }

            .conversation-actions .btn {
                min-width: auto;
                width: 100%;
            }

            .no-conversations .btn {
                display: block;
                margin: 0.5rem 0;
                width: 100%;
                max-width: 200px;
                margin-left: auto;
                margin-right: auto;
            }
        }

        @media (max-width: 480px) {
            .page-container {
                padding: 1rem 0;
                margin-top: 60px;
            }

            .conversation-card {
                padding: 1.2rem;
            }

            .conversation-header h3 {
                font-size: 1.1rem;
            }

            .contract-amount {
                font-size: 1.1rem;
            }

            .page-header h1 {
                font-size: 2rem;
            }
        }

        /* Additional status styles */
        .status-cancelled {
            color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
            padding: 0.3rem 0.8rem;
            border-radius: 12px;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .status-disputed {
            color: #f59e0b;
            background: rgba(245, 158, 11, 0.1);
            padding: 0.3rem 0.8rem;
            border-radius: 12px;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .status-on_hold {
            color: #6b7280;
            background: rgba(107, 114, 128, 0.1);
            padding: 0.3rem 0.8rem;
            border-radius: 12px;
            border: 1px solid rgba(107, 114, 128, 0.3);
        }

        /* Loading state for cards */
        .conversation-card.loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .conversation-card.loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.05), transparent);
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0% {
                transform: translateX(-100%);
            }

            100% {
                transform: translateX(100%);
            }
        }

        /* Freelancer specific styles */
        .client-info {
            font-weight: 500;
        }

        .contract-amount {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</body>

</html>