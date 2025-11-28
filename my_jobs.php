<?php
include_once 'config/database.php';
include_once 'models/Job.php';
include_once 'models/Proposal.php';
include_once 'models/Message.php'; // Add this line
include_once 'includes/session.php';

if (!Session::isLoggedIn() || Session::getUserType() != 'client') {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$job = new Job($db);
$proposal = new Proposal($db);
$message = new Message($db); // Add this line

$client_id = Session::get('user_id');
$jobs = $job->getClientJobs($client_id);

$unread_count = $message->getUnreadCount($client_id); // Add this line

if (isset($_GET['action']) && isset($_GET['id'])) {
    $job_id = $_GET['id'];
    $action = $_GET['action'];

    if ($action == 'complete') {
        if ($job->updateStatus($job_id, 'completed')) {
            $message = "success:Job marked as completed!";
        } else {
            $message = "error:Failed to update job status.";
        }
    } elseif ($action == 'cancel') {
        if ($job->updateStatus($job_id, 'cancelled')) {
            $message = "success:Job cancelled!";
        } else {
            $message = "error:Failed to cancel job.";
        }
    }

    header("Location: my_jobs.php?message=" . urlencode($message));
    exit();
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>My Jobs - Freelancing Platform</title>
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

    <!-- Rest of the my_jobs.php content remains the same -->
    <!-- ... -->

    <div class="container">
        <div class="page-container">
            <div class="page-header">
                <h1>My Jobs</h1>
                <p>Manage your posted jobs and view proposals</p>
            </div>

            <?php if (isset($_GET['message'])):
                list($type, $msg) = explode(':', $_GET['message'], 2);
                ?>
                <div class="alert alert-<?php echo $type; ?>"><?php echo $msg; ?></div>
            <?php endif; ?>

            <div class="jobs-list">
                <?php while ($row = $jobs->fetch(PDO::FETCH_ASSOC)):
                    $proposals = $proposal->getJobProposals($row['id']);
                    $proposals_count = count($proposals);
                    ?>
                    <div class="job-card">
                        <div class="job-header">
                            <h3><?php echo htmlspecialchars($row['title']); ?></h3>
                            <span class="job-status status-<?php echo $row['status']; ?>">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </div>

                        <div class="job-content">
                            <p class="job-description">
                                <?php echo strlen($row['description']) > 200 ?
                                    substr(htmlspecialchars($row['description']), 0, 200) . '...' :
                                    htmlspecialchars($row['description']); ?>
                            </p>

                            <div class="job-meta">
                                <div class="job-budget">
                                    <strong>$<?php echo number_format($row['budget'], 2); ?></strong>
                                    <span><?php echo ucfirst($row['budget_type']); ?></span>
                                </div>
                                <div class="job-proposals">
                                    <i class="fas fa-paper-plane"></i>
                                    <?php echo $proposals_count; ?> proposal(s)
                                </div>
                                <div class="job-date">
                                    Posted: <?php echo date('M j, Y', strtotime($row['created_at'])); ?>
                                </div>
                            </div>

                            <?php if ($row['skills_required']): ?>
                                <div class="job-skills">
                                    <strong>Skills:</strong>
                                    <?php
                                    $skills = explode(',', $row['skills_required']);
                                    foreach ($skills as $skill): ?>
                                        <span class="skill-tag"><?php echo trim($skill); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="job-actions">
                            <a href="view_job.php?id=<?php echo $row['id']; ?>" class="btn btn-outline">
                                <i class="fas fa-eye"></i>View Details
                            </a>
                            <a href="job_proposals.php?id=<?php echo $row['id']; ?>" class="btn btn-outline">
                                <i class="fas fa-paper-plane"></i>Proposals (<?php echo $proposals_count; ?>)
                            </a>

                            <?php if ($row['status'] == 'open' || $row['status'] == 'in_progress'): ?>
                                <?php if ($row['status'] == 'open'): ?>
                                    <a href="my_jobs.php?action=cancel&id=<?php echo $row['id']; ?>" class="btn btn-danger"
                                        onclick="return confirm('Are you sure you want to cancel this job?')">
                                        <i class="fas fa-times"></i>Cancel
                                    </a>
                                <?php else: ?>
                                    <a href="my_jobs.php?action=complete&id=<?php echo $row['id']; ?>" class="btn btn-success"
                                        onclick="return confirm('Mark this job as completed?')">
                                        <i class="fas fa-check"></i>Complete
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>

                <?php if ($jobs->rowCount() == 0): ?>
                    <div class="no-data">
                        <i class="fas fa-briefcase"></i>
                        <h3>No jobs posted yet</h3>
                        <p>Start by posting your first job to find freelancers</p>
                        <a href="post_job.php" class="btn btn-primary">Post Your First Job</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

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

.jobs-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.job-card {
    background: var(--dark-card);
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
    overflow: hidden;
    transition: all 0.3s ease;
    border: 1px solid var(--border);
    position: relative;
}

.job-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary), var(--secondary));
}

.job-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.3);
    border-color: var(--primary);
}

.job-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    background: rgba(30, 41, 59, 0.8);
    border-bottom: 1px solid var(--border);
}

.job-header h3 {
    margin: 0;
    color: var(--light);
    font-size: 1.3rem;
    line-height: 1.4;
    flex: 1;
}

.job-status {
    padding: 0.5rem 1.2rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: capitalize;
    border: 1px solid;
}

.status-open {
    background: rgba(16, 185, 129, 0.15);
    color: var(--primary-light);
    border-color: rgba(16, 185, 129, 0.4);
}

.status-in_progress {
    background: rgba(96, 165, 250, 0.15);
    color: #93c5fd;
    border-color: rgba(96, 165, 250, 0.4);
}

.status-completed {
    background: rgba(148, 163, 184, 0.15);
    color: var(--gray);
    border-color: rgba(148, 163, 184, 0.4);
}

.status-cancelled {
    background: rgba(239, 68, 68, 0.15);
    color: #fca5a5;
    border-color: rgba(239, 68, 68, 0.4);
}

.job-content {
    padding: 1.5rem;
}

.job-description {
    color: var(--gray);
    line-height: 1.6;
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
}

.job-meta {
    display: flex;
    gap: 2rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.job-budget strong {
    font-size: 1.3rem;
    color: var(--primary);
    text-shadow: 0 0 10px rgba(16, 185, 129, 0.3);
}

.job-budget span {
    color: var(--gray);
    margin-left: 0.5rem;
    font-size: 0.9rem;
}

.job-proposals,
.job-date {
    color: var(--gray);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
}

.job-proposals i {
    color: var(--primary);
}

.job-skills {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border);
}

.job-skills strong {
    color: var(--light);
    margin-right: 0.5rem;
    font-size: 0.95rem;
    display: block;
    margin-bottom: 0.5rem;
}

.skill-tag {
    background: rgba(16, 185, 129, 0.1);
    color: var(--primary-light);
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    margin-right: 0.5rem;
    margin-bottom: 0.5rem;
    display: inline-block;
    border: 1px solid rgba(16, 185, 129, 0.3);
    transition: all 0.3s ease;
}

.skill-tag:hover {
    background: rgba(16, 185, 129, 0.2);
    transform: translateY(-1px);
}

.job-actions {
    padding: 1.5rem;
    background: rgba(30, 41, 59, 0.8);
    border-top: 1px solid var(--border);
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.job-actions .btn {
    min-width: 140px;
    justify-content: center;
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

.no-data {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--dark-card);
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
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
    line-height: 1.6;
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

/* Navigation styles */
.nav-link.active {
    color: var(--primary-light);
    background: rgba(16, 185, 129, 0.1);
    border-left: 3px solid var(--primary);
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

/* Animation for job cards */
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

.job-card {
    animation: fadeInUp 0.5s ease-out;
}

.jobs-list .job-card:nth-child(1) { animation-delay: 0.1s; }
.jobs-list .job-card:nth-child(2) { animation-delay: 0.2s; }
.jobs-list .job-card:nth-child(3) { animation-delay: 0.3s; }
.jobs-list .job-card:nth-child(4) { animation-delay: 0.4s; }
.jobs-list .job-card:nth-child(5) { animation-delay: 0.5s; }

/* Button hover effects */
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
@media (max-width: 768px) {
    .job-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }

    .job-meta {
        flex-direction: column;
        gap: 1rem;
    }

    .job-actions {
        flex-direction: column;
    }

    .job-actions .btn {
        min-width: auto;
        width: 100%;
        text-align: center;
    }

    .page-header h1 {
        font-size: 2rem;
    }
}

@media (max-width: 480px) {
    .page-container {
        padding: 1rem 0;
        margin-top: 60px;
    }

    .job-header,
    .job-content,
    .job-actions {
        padding: 1rem;
    }

    .job-meta {
        gap: 0.8rem;
    }

    .job-budget strong {
        font-size: 1.1rem;
    }
}

/* Additional status styles */
.status-draft {
    background: rgba(107, 114, 128, 0.15);
    color: #d1d5db;
    border-color: rgba(107, 114, 128, 0.4);
}

.status-pending {
    background: rgba(245, 158, 11, 0.15);
    color: #fcd34d;
    border-color: rgba(245, 158, 11, 0.4);
}
    </style>
</body>

</html>