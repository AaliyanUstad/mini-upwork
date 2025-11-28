<?php
include_once 'config/database.php';
include_once 'models/Message.php'; // Add this line
include_once 'includes/session.php';

if (!Session::isLoggedIn() || Session::getUserType() != 'freelancer') {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$message = new Message($db); // Add this line

$user_id = Session::get('user_id');

$proposals_count = 0;
$active_proposals = 0;
$earnings = 0;

try {
    $query = "SELECT COUNT(*) as proposals_count FROM proposals WHERE freelancer_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $proposals_count = $stmt->fetch(PDO::FETCH_ASSOC)['proposals_count'];

    $query = "SELECT COUNT(*) as active_proposals FROM proposals WHERE freelancer_id = ? AND status = 'pending'";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $active_proposals = $stmt->fetch(PDO::FETCH_ASSOC)['active_proposals'];

    $query = "SELECT p.*, j.title, j.budget, j.budget_type 
              FROM proposals p 
              JOIN jobs j ON p.job_id = j.id 
              WHERE p.freelancer_id = ? 
              ORDER BY p.submitted_at DESC 
              LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $recent_proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
}

$unread_count = $message->getUnreadCount($user_id); // Add this line
?>
<!DOCTYPE html>
<html>

<head>
    <title>Freelancer Dashboard - Freelancing Platform</title>
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
                <a class="nav-link" href="browse_jobs.php">
                    <i class="fas fa-search"></i>Browse Jobs
                </a>
                <a class="nav-link" href="my_proposals.php">
                    <i class="fas fa-paper-plane"></i>My Proposals
                </a>
                <a class="nav-link" href="my_contracts.php">
                    <i class="fas fa-file-contract"></i>My Contracts
                </a>
                <a class="nav-link" href="freelancer_messages.php">
                    <i class="fas fa-comments"></i>Messages
                    <?php if ($unread_count > 0): ?>
                        <span class="notification-badge"><?php echo $unread_count; ?></span>
                    <?php endif; ?>
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
        <div class="dashboard">
            <div class="dashboard-header">
                <h1>Freelancer Dashboard</h1>
                <p>Welcome back, <?php echo Session::get('user_name'); ?>!</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $proposals_count; ?></h3>
                        <p>Total Proposals</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $active_proposals; ?></h3>
                        <p>Active Proposals</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div class="stat-content">
                        <h3>0</h3>
                        <p>Active Contracts</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-content">
                        <h3>$0</h3>
                        <p>Total Earnings</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Recent Proposals</h2>
                    <a href="browse_jobs.php" class="btn btn-primary">Browse Jobs</a>
                </div>

                <div class="proposals-list">
                    <?php if (!empty($recent_proposals)): ?>
                        <?php foreach ($recent_proposals as $proposal): ?>
                            <div class="proposal-item">
                                <div class="proposal-info">
                                    <h3><?php echo htmlspecialchars($proposal['title']); ?></h3>
                                    <p class="proposal-status">Status: <span
                                            class="status-<?php echo $proposal['status']; ?>"><?php echo ucfirst($proposal['status']); ?></span>
                                    </p>
                                    <p class="proposal-bid">Bid: $<?php echo number_format($proposal['bid_amount'], 2); ?>
                                        (<?php echo $proposal['budget_type']; ?>)</p>
                                </div>
                                <div class="proposal-actions">
                                    <span
                                        class="proposal-date"><?php echo date('M j, Y', strtotime($proposal['submitted_at'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-paper-plane"></i>
                            <h3>No proposals sent yet</h3>
                            <p>Start by browsing available jobs</p>
                            <a href="browse_jobs.php" class="btn btn-primary">Browse Jobs</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <style>
        .dashboard {
            padding: 2rem 0;
            margin-top: 60px;
        }

        .dashboard-header {
            margin-bottom: 3rem;
        }

        .dashboard-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .dashboard-header p {
            color: var(--gray);
            font-size: 1.2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: var(--dark-card);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 1.5rem;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            border-color: var(--primary);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
            flex-shrink: 0;
        }

        .stat-icon i {
            font-size: 1.8rem;
            color: var(--light);
        }

        .stat-content h3 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            color: var(--primary);
            text-shadow: 0 0 10px rgba(16, 185, 129, 0.3);
        }

        .stat-content p {
            color: var(--gray);
            margin: 0;
            font-size: 1rem;
            font-weight: 500;
        }

        .dashboard-section {
            background: var(--dark-card);
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            padding: 2rem;
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }

        .dashboard-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
        }

        .section-header h2 {
            margin: 0;
            color: var(--light);
            font-size: 1.8rem;
        }

        .proposals-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .proposal-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border: 1px solid var(--border);
            border-radius: 10px;
            transition: all 0.3s ease;
            background: var(--dark-surface);
            position: relative;
        }

        .proposal-item:hover {
            border-color: var(--primary);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.1);
            transform: translateX(5px);
        }

        .proposal-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(180deg, var(--primary), var(--secondary));
            border-radius: 4px 0 0 4px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .proposal-item:hover::before {
            opacity: 1;
        }

        .proposal-info h3 {
            margin: 0 0 0.5rem 0;
            color: var(--light);
            font-size: 1.2rem;
        }

        .proposal-status,
        .proposal-bid {
            margin: 0.25rem 0;
            color: var(--gray);
            font-size: 0.9rem;
        }

        .status-pending {
            color: #f59e0b;
            font-weight: 600;
            background: rgba(245, 158, 11, 0.1);
            padding: 0.2rem 0.8rem;
            border-radius: 12px;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .status-accepted {
            color: var(--primary-light);
            font-weight: 600;
            background: rgba(16, 185, 129, 0.1);
            padding: 0.2rem 0.8rem;
            border-radius: 12px;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .status-rejected {
            color: #ef4444;
            font-weight: 600;
            background: rgba(239, 68, 68, 0.1);
            padding: 0.2rem 0.8rem;
            border-radius: 12px;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .status-under_review {
            color: #8b5cf6;
            font-weight: 600;
            background: rgba(139, 92, 246, 0.1);
            padding: 0.2rem 0.8rem;
            border-radius: 12px;
            border: 1px solid rgba(139, 92, 246, 0.3);
        }

        .proposal-bid {
            font-weight: 600;
            color: var(--primary-light);
        }

        .proposal-date {
            color: var(--gray);
            font-size: 0.9rem;
            background: rgba(148, 163, 184, 0.1);
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        .no-data {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--dark-surface);
            border-radius: 10px;
            border: 2px dashed var(--border);
        }

        .no-data i {
            font-size: 4rem;
            color: var(--gray);
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        .no-data h3 {
            margin-bottom: 1rem;
            color: var(--light);
            font-size: 1.5rem;
        }

        .no-data p {
            color: var(--gray);
            margin-bottom: 2rem;
            font-size: 1rem;
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

        /* Navigation active states */
        .nav-link {
            position: relative;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: var(--primary-light);
            background: rgba(16, 185, 129, 0.1);
        }

        /* Animation for stats cards */
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-card {
            animation: slideInUp 0.6s ease-out;
        }

        .stat-card:nth-child(1) {
            animation-delay: 0.1s;
        }

        .stat-card:nth-child(2) {
            animation-delay: 0.2s;
        }

        .stat-card:nth-child(3) {
            animation-delay: 0.3s;
        }

        .stat-card:nth-child(4) {
            animation-delay: 0.4s;
        }

        /* Animation for proposal items */
        .proposal-item {
            animation: fadeInUp 0.5s ease-out;
        }

        .proposals-list .proposal-item:nth-child(1) {
            animation-delay: 0.1s;
        }

        .proposals-list .proposal-item:nth-child(2) {
            animation-delay: 0.2s;
        }

        .proposals-list .proposal-item:nth-child(3) {
            animation-delay: 0.3s;
        }

        .proposals-list .proposal-item:nth-child(4) {
            animation-delay: 0.4s;
        }

        .proposals-list .proposal-item:nth-child(5) {
            animation-delay: 0.5s;
        }

        /* Hover effects for buttons */
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--secondary) 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .section-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .proposal-item {
                flex-direction: column;
                gap: 1.5rem;
                align-items: flex-start;
            }

            .stat-card {
                flex-direction: column;
                text-align: center;
                padding: 1.5rem;
            }

            .stat-icon {
                width: 60px;
                height: 60px;
            }

            .stat-icon i {
                font-size: 1.5rem;
            }

            .stat-content h3 {
                font-size: 2rem;
            }

            .dashboard-header h1 {
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {
            .dashboard {
                padding: 1rem 0;
                margin-top: 60px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .dashboard-section {
                padding: 1.5rem;
            }

            .proposal-item {
                padding: 1rem;
            }

            .proposal-info h3 {
                font-size: 1.1rem;
            }
        }

        /* Additional status styles for completeness */
        .status-completed {
            color: #10b981;
            font-weight: 600;
            background: rgba(16, 185, 129, 0.1);
            padding: 0.2rem 0.8rem;
            border-radius: 12px;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .status-cancelled {
            color: #6b7280;
            font-weight: 600;
            background: rgba(107, 114, 128, 0.1);
            padding: 0.2rem 0.8rem;
            border-radius: 12px;
            border: 1px solid rgba(107, 114, 128, 0.3);
        }
    </style>
</body>

</html>