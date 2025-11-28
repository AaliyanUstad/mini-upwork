<?php
include_once 'config/database.php';
include_once 'models/Job.php';
include_once 'models/Proposal.php';
include_once 'includes/session.php';

if (!Session::isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$job = new Job($db);
$proposal = new Proposal($db);

if (!isset($_GET['id'])) {
    header("Location: " . (Session::getUserType() == 'client' ? 'my_jobs.php' : 'browse_jobs.php'));
    exit();
}

$job_id = $_GET['id'];
$job_data = $job->getJobById($job_id);

if (!$job_data) {
    header("Location: " . (Session::getUserType() == 'client' ? 'my_jobs.php' : 'browse_jobs.php'));
    exit();
}

$has_applied = false;
if (Session::getUserType() == 'freelancer') {
    $query = "SELECT id FROM proposals WHERE job_id = ? AND freelancer_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$job_id, Session::get('user_id')]);
    $has_applied = $stmt->rowCount() > 0;
}
?>
<!DOCTYPE html>
<html>

<head>
    <title><?php echo htmlspecialchars($job_data['title']); ?> - Freelancing Platform</title>
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
                <?php if (Session::getUserType() == 'client'): ?>
                    <a class="nav-link" href="client_dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>Dashboard
                    </a>
                    <a class="nav-link" href="my_jobs.php">
                        <i class="fas fa-briefcase"></i>My Jobs
                    </a>
                <?php else: ?>
                    <a class="nav-link" href="freelancer_dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>Dashboard
                    </a>
                    <a class="nav-link" href="browse_jobs.php">
                        <i class="fas fa-search"></i>Browse Jobs
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
                    <h1><?php echo htmlspecialchars($job_data['title']); ?></h1>
                    <p>Posted by:
                        <?php echo htmlspecialchars($job_data['company_name'] ?: $job_data['first_name'] . ' ' . $job_data['last_name']); ?>
                    </p>
                </div>
                <a href="<?php echo Session::getUserType() == 'client' ? 'my_jobs.php' : 'browse_jobs.php'; ?>"
                    class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i>Back
                </a>
            </div>

            <div class="job-detail-container">
                <div class="job-main">
                    <div class="job-section">
                        <h2>Job Description</h2>
                        <div class="job-description">
                            <?php echo nl2br(htmlspecialchars($job_data['description'])); ?>
                        </div>
                    </div>

                    <?php if ($job_data['skills_required']): ?>
                        <div class="job-section">
                            <h2>Skills Required</h2>
                            <div class="skills-list">
                                <?php
                                $skills = explode(',', $job_data['skills_required']);
                                foreach ($skills as $skill): ?>
                                    <span class="skill-tag"><?php echo trim($skill); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="job-sidebar">
                    <div class="job-info-card">
                        <h3>Job Details</h3>
                        <div class="info-list">
                            <div class="info-item">
                                <strong>Budget:</strong>
                                <span>$<?php echo number_format($job_data['budget'], 2); ?>
                                    (<?php echo $job_data['budget_type']; ?>)</span>
                            </div>
                            <div class="info-item">
                                <strong>Status:</strong>
                                <span
                                    class="status-<?php echo $job_data['status']; ?>"><?php echo ucfirst($job_data['status']); ?></span>
                            </div>
                            <div class="info-item">
                                <strong>Posted:</strong>
                                <span><?php echo date('M j, Y g:i A', strtotime($job_data['created_at'])); ?></span>
                            </div>
                            <div class="info-item">
                                <strong>Last Updated:</strong>
                                <span><?php echo date('M j, Y g:i A', strtotime($job_data['updated_at'])); ?></span>
                            </div>
                        </div>

                        <?php if (Session::getUserType() == 'freelancer'): ?>
                            <div class="action-buttons">
                                <?php if ($job_data['status'] == 'open'): ?>
                                    <?php if ($has_applied): ?>
                                        <button class="btn btn-success btn-full" disabled>
                                            <i class="fas fa-check"></i>Already Applied
                                        </button>
                                    <?php else: ?>
                                        <a href="submit_proposal.php?job_id=<?php echo $job_id; ?>"
                                            class="btn btn-primary btn-full">
                                            <i class="fas fa-paper-plane"></i>Submit Proposal
                                        </a>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-full" disabled>
                                        <i class="fas fa-lock"></i>Job Not Available
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php elseif (Session::getUserType() == 'client' && $job_data['client_id'] == Session::get('user_id')): ?>
                            <div class="action-buttons">
                                <a href="job_proposals.php?id=<?php echo $job_id; ?>" class="btn btn-primary btn-full">
                                    <i class="fas fa-paper-plane"></i>View Proposals
                                </a>
                                <?php if ($job_data['status'] == 'open'): ?>
                                    <a href="my_jobs.php?action=cancel&id=<?php echo $job_id; ?>"
                                        class="btn btn-danger btn-full"
                                        onclick="return confirm('Are you sure you want to cancel this job?')">
                                        <i class="fas fa-times"></i>Cancel Job
                                    </a>
                                <?php elseif ($job_data['status'] == 'in_progress'): ?>
                                    <a href="my_jobs.php?action=complete&id=<?php echo $job_id; ?>"
                                        class="btn btn-success btn-full"
                                        onclick="return confirm('Mark this job as completed?')">
                                        <i class="fas fa-check"></i>Mark Complete
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
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
            margin: 0;
            font-size: 1.1rem;
        }

        .job-detail-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2.5rem;
        }

        .job-main {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .job-section {
            background: var(--dark-card);
            border-radius: 15px;
            padding: 2.5rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }

        .job-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .job-section h2 {
            margin: 0 0 1.5rem 0;
            color: var(--light);
            font-size: 1.5rem;
            font-weight: 600;
        }

        .job-description {
            color: var(--gray);
            line-height: 1.7;
            font-size: 1.05rem;
            background: rgba(30, 41, 59, 0.5);
            padding: 1.5rem;
            border-radius: 10px;
            border-left: 3px solid var(--primary);
        }

        .skills-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .skill-tag {
            background: rgba(16, 185, 129, 0.15);
            color: var(--primary-light);
            padding: 0.6rem 1.2rem;
            border-radius: 20px;
            font-size: 0.9rem;
            border: 1px solid rgba(16, 185, 129, 0.3);
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .skill-tag:hover {
            background: rgba(16, 185, 129, 0.25);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
        }

        .job-sidebar {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .job-info-card {
            background: var(--dark-card);
            border-radius: 15px;
            padding: 2.5rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border);
            position: sticky;
            top: 80px;
            position: relative;
            overflow: hidden;
        }

        .job-info-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .job-info-card h3 {
            margin: 0 0 1.5rem 0;
            color: var(--light);
            font-size: 1.4rem;
            font-weight: 600;
        }

        .info-list {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
            margin-bottom: 2rem;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: rgba(30, 41, 59, 0.5);
            border-radius: 8px;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
        }

        .info-item:hover {
            border-color: var(--primary);
            transform: translateX(5px);
        }

        .info-item strong {
            color: var(--light);
            font-weight: 600;
            font-size: 0.95rem;
        }

        .info-item span {
            color: var(--gray);
            font-size: 0.95rem;
        }

        .status-open {
            color: var(--primary-light);
            background: rgba(16, 185, 129, 0.1);
            padding: 0.3rem 0.8rem;
            border-radius: 12px;
            border: 1px solid rgba(16, 185, 129, 0.3);
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-in_progress {
            color: #93c5fd;
            background: rgba(96, 165, 250, 0.1);
            padding: 0.3rem 0.8rem;
            border-radius: 12px;
            border: 1px solid rgba(96, 165, 250, 0.3);
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

        .status-cancelled {
            color: #fca5a5;
            background: rgba(239, 68, 68, 0.1);
            padding: 0.3rem 0.8rem;
            border-radius: 12px;
            border: 1px solid rgba(239, 68, 68, 0.3);
            font-size: 0.8rem;
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .btn-full {
            width: 100%;
            padding: 1rem;
            font-weight: 600;
            border-radius: 12px;
            justify-content: center;
            text-align: center;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--light);
            border: none;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-success:hover:not(:disabled) {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--secondary) 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }

        .btn-secondary {
            background: rgba(148, 163, 184, 0.2);
            color: var(--gray);
            border: 1px solid rgba(148, 163, 184, 0.3);
        }

        .btn-secondary:hover:not(:disabled) {
            background: rgba(148, 163, 184, 0.3);
            transform: translateY(-2px);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: var(--light);
            border: none;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }

        .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        .btn:disabled:hover {
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        /* Navigation styles */
        .nav-link {
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: var(--primary-light);
            background: rgba(16, 185, 129, 0.1);
        }

        /* Button hover effects */
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

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .job-section,
        .job-info-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .job-section:nth-child(1) {
            animation-delay: 0.1s;
        }

        .job-section:nth-child(2) {
            animation-delay: 0.2s;
        }

        .job-info-card {
            animation-delay: 0.3s;
        }

        /* Responsive Design */
        @media (max-width: 968px) {
            .job-detail-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .job-sidebar {
                order: -1;
            }

            .job-info-card {
                position: static;
            }
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                gap: 1rem;
            }

            .job-section {
                padding: 2rem 1.5rem;
            }

            .job-info-card {
                padding: 2rem 1.5rem;
            }

            .info-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
                text-align: left;
            }

            .header-content h1 {
                font-size: 1.8rem;
            }

            .job-section h2 {
                font-size: 1.3rem;
            }
        }

        @media (max-width: 480px) {
            .page-container {
                padding: 1rem 0;
                margin-top: 60px;
            }

            .job-section {
                padding: 1.5rem 1rem;
            }

            .job-info-card {
                padding: 1.5rem 1rem;
            }

            .job-description {
                padding: 1rem;
                font-size: 1rem;
            }

            .skills-list {
                gap: 0.5rem;
            }

            .skill-tag {
                padding: 0.5rem 1rem;
                font-size: 0.85rem;
            }
        }

        /* Focus states for accessibility */
        .btn:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        /* Budget amount highlight */
        .info-item:first-child span {
            color: var(--primary);
            font-weight: 600;
            font-size: 1.1rem;
        }

        /* Client-specific action buttons */
        .action-buttons .btn-danger {
            margin-top: 0.5rem;
        }

        /* Freelancer-specific states */
        .btn-success:disabled {
            background: rgba(16, 185, 129, 0.3);
            color: rgba(255, 255, 255, 0.7);
        }

        /* Custom scrollbar for job description */
        .job-description {
            max-height: 400px;
            overflow-y: auto;
        }

        .job-description::-webkit-scrollbar {
            width: 6px;
        }

        .job-description::-webkit-scrollbar-track {
            background: var(--dark-surface);
            border-radius: 3px;
        }

        .job-description::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 3px;
        }

        .job-description::-webkit-scrollbar-thumb:hover {
            background: var(--primary-light);
        }

        /* Additional status styles */
        .status-draft {
            color: #d1d5db;
            background: rgba(107, 114, 128, 0.1);
            padding: 0.3rem 0.8rem;
            border-radius: 12px;
            border: 1px solid rgba(107, 114, 128, 0.3);
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-pending {
            color: #fcd34d;
            background: rgba(245, 158, 11, 0.1);
            padding: 0.3rem 0.8rem;
            border-radius: 12px;
            border: 1px solid rgba(245, 158, 11, 0.3);
            font-size: 0.8rem;
            font-weight: 600;
        }
    </style>
</body>

</html>