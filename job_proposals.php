<?php
include_once 'config/database.php';
include_once 'models/Job.php';
include_once 'models/Proposal.php';
include_once 'includes/session.php';

if (!Session::isLoggedIn() || Session::getUserType() != 'client') {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$job = new Job($db);
$proposal = new Proposal($db);

if (!isset($_GET['id'])) {
    header("Location: my_jobs.php");
    exit();
}

$job_id = $_GET['id'];
$job_data = $job->getJobById($job_id);

// Check if the job exists and belongs to the logged-in client
if (!$job_data || $job_data['client_id'] != Session::get('user_id')) {
    header("Location: my_jobs.php");
    exit();
}

$proposals = $proposal->getJobProposals($job_id);

if (isset($_GET['action']) && isset($_GET['proposal_id'])) {
    $proposal_id = $_GET['proposal_id'];
    $action = $_GET['action'];

    // Verify the proposal belongs to the client's job
    $proposal_data = $proposal->getProposalById($proposal_id);
    if (!$proposal_data || $proposal_data['client_id'] != Session::get('user_id')) {
        header("Location: job_proposals.php?id=" . $job_id);
        exit();
    }

    if ($action == 'accept') {
        if ($proposal->createContract($proposal_id)) {
            $message = "success:Proposal accepted! Contract created successfully.";
        } else {
            $message = "error:Failed to accept proposal.";
        }
    } elseif ($action == 'reject') {
        if ($proposal->updateStatus($proposal_id, 'rejected')) {
            $message = "success:Proposal rejected.";
        } else {
            $message = "error:Failed to reject proposal.";
        }
    }

    header("Location: job_proposals.php?id=" . $job_id . "&message=" . urlencode($message));
    exit();
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Job Proposals - Freelancing Platform</title>
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
                <a class="nav-link" href="client_dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>Dashboard
                </a>
                <a class="nav-link" href="my_jobs.php">
                    <i class="fas fa-briefcase"></i>My Jobs
                </a>
                <a class="nav-link" href="post_job.php">
                    <i class="fas fa-plus"></i>Post Job
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
                    <h1>Proposals for: <?php echo htmlspecialchars($job_data['title']); ?></h1>
                    <p>Review and manage freelancer proposals</p>
                </div>
                <a href="my_jobs.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i>Back to Jobs
                </a>
            </div>

            <?php if (isset($_GET['message'])):
                list($type, $msg) = explode(':', $_GET['message'], 2);
                ?>
                <div class="alert alert-<?php echo $type; ?>"><?php echo $msg; ?></div>
            <?php endif; ?>

            <div class="job-info">
                <h3>Job Details</h3>
                <div class="job-details">
                    <p><strong>Budget:</strong> $<?php echo number_format($job_data['budget'], 2); ?>
                        (<?php echo $job_data['budget_type']; ?>)</p>
                    <p><strong>Status:</strong> <span
                            class="status-<?php echo $job_data['status']; ?>"><?php echo ucfirst($job_data['status']); ?></span>
                    </p>
                    <p><strong>Posted:</strong> <?php echo date('M j, Y', strtotime($job_data['created_at'])); ?></p>
                </div>
            </div>

            <div class="proposals-section">
                <h2>Proposals (<?php echo count($proposals); ?>)</h2>

                <div class="proposals-list">
                    <?php if (!empty($proposals)): ?>
                        <?php foreach ($proposals as $prop): ?>
                            <div class="proposal-card">
                                <div class="proposal-header">
                                    <div class="freelancer-info">
                                        <h3><?php echo htmlspecialchars($prop['first_name'] . ' ' . $prop['last_name']); ?></h3>
                                        <span class="proposal-status status-<?php echo $prop['status']; ?>">
                                            <?php echo ucfirst($prop['status']); ?>
                                        </span>
                                    </div>
                                    <div class="proposal-bid">
                                        <strong>$<?php echo number_format($prop['bid_amount'], 2); ?></strong>
                                        <?php if ($prop['estimated_hours']): ?>
                                            <span>(<?php echo $prop['estimated_hours']; ?> hours)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="proposal-content">
                                    <div class="cover-letter">
                                        <h4>Cover Letter:</h4>
                                        <p><?php echo nl2br(htmlspecialchars($prop['cover_letter'])); ?></p>
                                    </div>

                                    <?php if ($prop['skills']): ?>
                                        <div class="freelancer-skills">
                                            <h4>Skills:</h4>
                                            <div class="skills-list">
                                                <?php
                                                $skills = explode(',', $prop['skills']);
                                                foreach ($skills as $skill): ?>
                                                    <span class="skill-tag"><?php echo trim($skill); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="proposal-meta">
                                        <div class="hourly-rate">
                                            <strong>Hourly Rate:</strong>
                                            $<?php echo number_format($prop['hourly_rate'], 2); ?>/hr
                                        </div>
                                        <div class="submission-date">
                                            Submitted: <?php echo date('M j, Y g:i A', strtotime($prop['submitted_at'])); ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="proposal-actions">
                                    <?php if ($prop['status'] == 'pending' && $job_data['status'] == 'open'): ?>
                                        <a href="job_proposals.php?id=<?php echo $job_id; ?>&action=accept&proposal_id=<?php echo $prop['id']; ?>"
                                            class="btn btn-success"
                                            onclick="return confirm('Accept this proposal and hire this freelancer?')">
                                            <i class="fas fa-check"></i>Accept Proposal
                                        </a>
                                        <a href="job_proposals.php?id=<?php echo $job_id; ?>&action=reject&proposal_id=<?php echo $prop['id']; ?>"
                                            class="btn btn-danger" onclick="return confirm('Reject this proposal?')">
                                            <i class="fas fa-times"></i>Reject
                                        </a>
                                    <?php elseif ($prop['status'] == 'accepted'): ?>
                                        <span class="badge-success">Hired</span>
                                    <?php elseif ($prop['status'] == 'rejected'): ?>
                                        <span class="badge-danger">Rejected</span>
                                    <?php endif; ?>

                                    <a href="view_freelancer.php?id=<?php echo $prop['freelancer_id']; ?>"
                                        class="btn btn-outline">
                                        <i class="fas fa-user"></i>View Profile
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-paper-plane"></i>
                            <h3>No proposals yet</h3>
                            <p>No freelancers have applied to this job yet.</p>
                        </div>
                    <?php endif; ?>
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

        .job-info {
            background: var(--dark-card);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }

        .job-info::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .job-info h3 {
            margin: 0 0 1.5rem 0;
            color: var(--light);
            font-size: 1.4rem;
        }

        .job-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .job-details p {
            margin: 0;
            color: var(--gray);
            font-size: 1rem;
        }

        .job-details strong {
            color: var(--light);
            font-weight: 600;
        }

        .proposals-section h2 {
            margin-bottom: 1.5rem;
            color: var(--light);
            font-size: 1.8rem;
        }

        .proposals-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .proposal-card {
            background: var(--dark-card);
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid var(--border);
            position: relative;
        }

        .proposal-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .proposal-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            border-color: var(--primary);
        }

        .proposal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            background: rgba(30, 41, 59, 0.8);
            border-bottom: 1px solid var(--border);
        }

        .freelancer-info h3 {
            margin: 0 0 0.5rem 0;
            color: var(--light);
            font-size: 1.3rem;
        }

        .proposal-bid strong {
            font-size: 1.5rem;
            color: var(--primary);
            text-shadow: 0 0 10px rgba(16, 185, 129, 0.3);
        }

        .proposal-bid span {
            color: var(--gray);
            font-size: 0.9rem;
            display: block;
            text-align: right;
        }

        .proposal-content {
            padding: 1.5rem;
        }

        .cover-letter h4,
        .freelancer-skills h4 {
            margin: 0 0 0.8rem 0;
            color: var(--light);
            font-size: 1.1rem;
        }

        .cover-letter p {
            color: var(--gray);
            line-height: 1.6;
            margin-bottom: 1.5rem;
            background: rgba(30, 41, 59, 0.5);
            padding: 1rem;
            border-radius: 8px;
            border-left: 3px solid var(--primary);
        }

        .freelancer-skills {
            margin-bottom: 1.5rem;
        }

        .skills-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .skill-tag {
            background: rgba(16, 185, 129, 0.1);
            color: var(--primary-light);
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            border: 1px solid rgba(16, 185, 129, 0.3);
            transition: all 0.3s ease;
        }

        .skill-tag:hover {
            background: rgba(16, 185, 129, 0.2);
            transform: translateY(-1px);
        }

        .proposal-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }

        .hourly-rate {
            color: var(--light);
            font-weight: 600;
            background: rgba(16, 185, 129, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .submission-date {
            color: var(--gray);
            font-size: 0.9rem;
            background: rgba(148, 163, 184, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }

        .proposal-actions {
            padding: 1.5rem;
            background: rgba(30, 41, 59, 0.8);
            border-top: 1px solid var(--border);
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .btn-success {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--light);
            border: none;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-success:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--secondary) 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
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

        .badge-success,
        .badge-danger {
            padding: 0.6rem 1.2rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.2);
            color: var(--primary-light);
            border: 1px solid rgba(16, 185, 129, 0.4);
        }

        .badge-danger {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.4);
        }

        .no-data {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--dark-card);
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
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
            margin: 0;
            font-size: 1rem;
        }

        /* Alert styles */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            border: 1px solid;
            font-weight: 500;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--primary-light);
            border-color: rgba(16, 185, 129, 0.3);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #fca5a5;
            border-color: rgba(239, 68, 68, 0.3);
        }

        /* Status styles */
        .status-pending {
            color: #f59e0b;
            background: rgba(245, 158, 11, 0.1);
            padding: 0.3rem 0.8rem;
            border-radius: 12px;
            border: 1px solid rgba(245, 158, 11, 0.3);
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-accepted {
            color: var(--primary-light);
            background: rgba(16, 185, 129, 0.1);
            padding: 0.3rem 0.8rem;
            border-radius: 12px;
            border: 1px solid rgba(16, 185, 129, 0.3);
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-rejected {
            color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
            padding: 0.3rem 0.8rem;
            border-radius: 12px;
            border: 1px solid rgba(239, 68, 68, 0.3);
            font-size: 0.8rem;
            font-weight: 600;
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

        /* Animation for proposal cards */
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

        .proposal-card {
            animation: fadeInUp 0.5s ease-out;
        }

        .proposals-list .proposal-card:nth-child(1) {
            animation-delay: 0.1s;
        }

        .proposals-list .proposal-card:nth-child(2) {
            animation-delay: 0.2s;
        }

        .proposals-list .proposal-card:nth-child(3) {
            animation-delay: 0.3s;
        }

        .proposals-list .proposal-card:nth-child(4) {
            animation-delay: 0.4s;
        }

        .proposals-list .proposal-card:nth-child(5) {
            animation-delay: 0.5s;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                gap: 1rem;
            }

            .proposal-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .proposal-meta {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .proposal-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .proposal-actions .btn {
                text-align: center;
                width: 100%;
            }

            .job-details {
                grid-template-columns: 1fr;
            }

            .header-content h1 {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 480px) {
            .page-container {
                padding: 1rem 0;
                margin-top: 60px;
            }

            .job-info,
            .proposal-card {
                padding: 1.2rem;
            }

            .proposal-content {
                padding: 1rem;
            }

            .cover-letter p {
                padding: 0.8rem;
            }
        }

        /* Button hover effects */
        .btn-outline:hover {
            background: var(--primary);
            color: var(--light);
            transform: translateY(-2px);
        }
    </style>
</body>

</html>