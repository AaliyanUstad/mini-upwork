<?php
include_once 'config/database.php';
include_once 'models/Message.php'; // Add this line
include_once 'includes/session.php';

if (!Session::isLoggedIn() || Session::getUserType() != 'client') {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$message = new Message($db); // Now this will work

$user_id = Session::get('user_id');

$jobs_count = 0;
$active_jobs = 0;
$total_spent = 0;

try {
    $query = "SELECT COUNT(*) as jobs_count FROM jobs WHERE client_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $jobs_count = $stmt->fetch(PDO::FETCH_ASSOC)['jobs_count'];

    $query = "SELECT COUNT(*) as active_jobs FROM jobs WHERE client_id = ? AND status = 'open'";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $active_jobs = $stmt->fetch(PDO::FETCH_ASSOC)['active_jobs'];

    $query = "SELECT j.*, COUNT(p.id) as proposals_count 
              FROM jobs j 
              LEFT JOIN proposals p ON j.id = p.job_id 
              WHERE j.client_id = ? 
              GROUP BY j.id 
              ORDER BY j.created_at DESC 
              LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $recent_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
}

$unread_count = $message->getUnreadCount($user_id);
?>
<!DOCTYPE html>
<html>

<head>
    <title>Client Dashboard - Freelancing Platform</title>
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
                <a class="nav-link" href="post_job.php">
                    <i class="fas fa-plus"></i>Post Job
                </a>
                <a class="nav-link" href="client_messages.php">
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
                <h1>Client Dashboard</h1>
                <p>Welcome back, <?php echo Session::get('user_name'); ?>!</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $jobs_count; ?></h3>
                        <p>Total Jobs Posted</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $active_jobs; ?></h3>
                        <p>Active Jobs</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3>0</h3>
                        <p>Hired Freelancers</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-content">
                        <h3>$0</h3>
                        <p>Total Spent</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Recent Jobs</h2>
                    <a href="post_job.php" class="btn btn-primary">Post New Job</a>
                </div>

                <div class="jobs-list">
                    <?php if (!empty($recent_jobs)): ?>
                        <?php foreach ($recent_jobs as $job): ?>
                            <div class="job-item">
                                <div class="job-info">
                                    <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                                    <p class="job-status">Status: <span
                                            class="status-<?php echo $job['status']; ?>"><?php echo ucfirst($job['status']); ?></span>
                                    </p>
                                    <p class="job-proposals">Proposals: <?php echo $job['proposals_count']; ?></p>
                                </div>
                                <div class="job-actions">
                                    <a href="view_job.php?id=<?php echo $job['id']; ?>" class="btn btn-outline">View</a>
                                    <?php if ($job['proposals_count'] > 0): ?>
                                        <a href="job_proposals.php?id=<?php echo $job['id']; ?>" class="btn btn-primary">View
                                            Proposals</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-briefcase"></i>
                            <h3>No jobs posted yet</h3>
                            <p>Start by posting your first job</p>
                            <a href="post_job.php" class="btn btn-primary">Post Your First Job</a>
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

        .jobs-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .job-item {
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

        .job-item:hover {
            border-color: var(--primary);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.1);
            transform: translateX(5px);
        }

        .job-item::before {
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

        .job-item:hover::before {
            opacity: 1;
        }

        .job-info h3 {
            margin: 0 0 0.5rem 0;
            color: var(--light);
            font-size: 1.2rem;
        }

        .job-status,
        .job-proposals {
            margin: 0.25rem 0;
            color: var(--gray);
            font-size: 0.9rem;
        }

        .status-open {
            color: var(--primary-light);
            font-weight: 600;
            background: rgba(16, 185, 129, 0.1);
            padding: 0.2rem 0.8rem;
            border-radius: 12px;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .status-in_progress {
            color: #60a5fa;
            font-weight: 600;
            background: rgba(96, 165, 250, 0.1);
            padding: 0.2rem 0.8rem;
            border-radius: 12px;
            border: 1px solid rgba(96, 165, 250, 0.3);
        }

        .status-completed {
            color: var(--gray);
            font-weight: 600;
            background: rgba(148, 163, 184, 0.1);
            padding: 0.2rem 0.8rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.3);
        }

        .job-actions {
            display: flex;
            gap: 0.8rem;
            flex-shrink: 0;
        }

        .job-actions .btn {
            min-width: 120px;
            justify-content: center;
            font-size: 0.9rem;
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

        /* Animation for job items */
        .job-item {
            animation: fadeInUp 0.5s ease-out;
        }

        .jobs-list .job-item:nth-child(1) {
            animation-delay: 0.1s;
        }

        .jobs-list .job-item:nth-child(2) {
            animation-delay: 0.2s;
        }

        .jobs-list .job-item:nth-child(3) {
            animation-delay: 0.3s;
        }

        .jobs-list .job-item:nth-child(4) {
            animation-delay: 0.4s;
        }

        .jobs-list .job-item:nth-child(5) {
            animation-delay: 0.5s;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .section-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .job-item {
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

            .job-actions {
                width: 100%;
                justify-content: flex-start;
            }

            .job-actions .btn {
                min-width: auto;
                flex: 1;
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

            .job-item {
                padding: 1rem;
            }

            .job-actions {
                flex-direction: column;
                width: 100%;
            }

            .job-actions .btn {
                width: 100%;
                text-align: center;
            }
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
    </style>
</body>

</html>