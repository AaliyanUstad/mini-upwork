<?php
include_once 'config/database.php';
include_once 'models/Proposal.php';
include_once 'includes/session.php';

if(!Session::isLoggedIn() || Session::getUserType() != 'freelancer'){
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$proposal = new Proposal($db);

$freelancer_id = Session::get('user_id');

$query = "SELECT p.*, j.title, j.budget, j.budget_type, j.status as job_status,
                 u.first_name, u.last_name, u.company_name
          FROM proposals p 
          JOIN jobs j ON p.job_id = j.id 
          JOIN users u ON j.client_id = u.id 
          WHERE p.freelancer_id = ? 
          ORDER BY p.submitted_at DESC";

$stmt = $db->prepare($query);
$stmt->execute([$freelancer_id]);
$proposals = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats = [
    'total' => count($proposals),
    'pending' => 0,
    'accepted' => 0,
    'rejected' => 0
];

foreach($proposals as $prop) {
    $stats[$prop['status']]++;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Proposals - Freelancing Platform</title>
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
                <a class="nav-link active" href="my_proposals.php">
                    <i class="fas fa-paper-plane"></i>My Proposals
                </a>
                <a class="nav-link" href="my_contracts.php">
                    <i class="fas fa-file-contract"></i>My Contracts
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
                <h1>My Proposals</h1>
                <p>Track your job applications and responses</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p>Total Proposals</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['pending']; ?></h3>
                        <p>Pending</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['accepted']; ?></h3>
                        <p>Accepted</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-times"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['rejected']; ?></h3>
                        <p>Rejected</p>
                    </div>
                </div>
            </div>

            <div class="proposals-section">
                <div class="section-header">
                    <h2>Recent Proposals</h2>
                    <a href="browse_jobs.php" class="btn btn-primary">Browse Jobs</a>
                </div>
                
                <div class="proposals-list">
                    <?php if(!empty($proposals)): ?>
                        <?php foreach($proposals as $prop): ?>
                            <div class="proposal-card">
                                <div class="proposal-header">
                                    <div class="proposal-info">
                                        <h3><?php echo htmlspecialchars($prop['title']); ?></h3>
                                        <div class="client-info">
                                            <i class="fas fa-user"></i>
                                            <?php echo htmlspecialchars($prop['company_name'] ?: $prop['first_name'] . ' ' . $prop['last_name']); ?>
                                        </div>
                                    </div>
                                    <div class="proposal-status">
                                        <span class="status-badge status-<?php echo $prop['status']; ?>">
                                            <?php echo ucfirst($prop['status']); ?>
                                        </span>
                                        <span class="job-status">Job: <?php echo ucfirst($prop['job_status']); ?></span>
                                    </div>
                                </div>
                                
                                <div class="proposal-content">
                                    <div class="proposal-details">
                                        <div class="detail-item">
                                            <strong>Your Bid:</strong>
                                            <span>$<?php echo number_format($prop['bid_amount'], 2); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <strong>Client Budget:</strong>
                                            <span>$<?php echo number_format($prop['budget'], 2); ?> (<?php echo $prop['budget_type']; ?>)</span>
                                        </div>
                                        <?php if($prop['estimated_hours']): ?>
                                        <div class="detail-item">
                                            <strong>Estimated Hours:</strong>
                                            <span><?php echo $prop['estimated_hours']; ?> hours</span>
                                        </div>
                                        <?php endif; ?>
                                        <div class="detail-item">
                                            <strong>Submitted:</strong>
                                            <span><?php echo date('M j, Y g:i A', strtotime($prop['submitted_at'])); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="cover-letter-preview">
                                        <strong>Cover Letter:</strong>
                                        <p><?php echo strlen($prop['cover_letter']) > 200 ? 
                                            substr(htmlspecialchars($prop['cover_letter']), 0, 200) . '...' : 
                                            htmlspecialchars($prop['cover_letter']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="proposal-actions">
                                    <a href="view_job.php?id=<?php echo $prop['job_id']; ?>" class="btn btn-outline">
                                        <i class="fas fa-eye"></i>View Job
                                    </a>
                                    <?php if($prop['status'] == 'accepted'): ?>
                                        <a href="my_contracts.php" class="btn btn-success">
                                            <i class="fas fa-file-contract"></i>View Contract
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-paper-plane"></i>
                            <h3>No proposals yet</h3>
                            <p>Start by browsing available jobs and submitting your first proposal</p>
                            <a href="browse_jobs.php" class="btn btn-primary">Browse Jobs</a>
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

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.stat-card {
    background: var(--dark-card);
    padding: 1.5rem;
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
    display: flex;
    align-items: center;
    gap: 1rem;
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
    box-shadow: 0 15px 35px rgba(0,0,0,0.3);
    border-color: var(--primary);
}

.stat-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 5px 15px rgba(16, 185, 129, 0.3);
    flex-shrink: 0;
}

.stat-icon i {
    font-size: 1.5rem;
    color: var(--light);
}

.stat-content h3 {
    font-size: 2.2rem;
    margin-bottom: 0.25rem;
    color: var(--primary);
    text-shadow: 0 0 10px rgba(16, 185, 129, 0.3);
}

.stat-content p {
    color: var(--gray);
    margin: 0;
    font-size: 0.95rem;
    font-weight: 500;
}

.proposals-section {
    background: var(--dark-card);
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
    padding: 2rem;
    border: 1px solid var(--border);
    position: relative;
    overflow: hidden;
}

.proposals-section::before {
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
    gap: 1.5rem;
}

.proposal-card {
    background: var(--dark-surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
}

.proposal-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--primary), var(--secondary));
    opacity: 0;
    transition: opacity 0.3s ease;
}

.proposal-card:hover {
    border-color: var(--primary);
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    transform: translateY(-3px);
}

.proposal-card:hover::before {
    opacity: 1;
}

.proposal-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 1.5rem;
    background: rgba(30, 41, 59, 0.8);
    border-bottom: 1px solid var(--border);
}

.proposal-info h3 {
    margin: 0 0 0.5rem 0;
    color: var(--light);
    font-size: 1.3rem;
    line-height: 1.4;
    flex: 1;
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

.proposal-status {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.5rem;
}

.status-badge {
    padding: 0.5rem 1.2rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: capitalize;
    border: 1px solid;
}

.status-pending {
    background: rgba(245, 158, 11, 0.15);
    color: #fcd34d;
    border-color: rgba(245, 158, 11, 0.4);
}

.status-accepted {
    background: rgba(16, 185, 129, 0.15);
    color: var(--primary-light);
    border-color: rgba(16, 185, 129, 0.4);
}

.status-rejected {
    background: rgba(239, 68, 68, 0.15);
    color: #fca5a5;
    border-color: rgba(239, 68, 68, 0.4);
}

.status-under_review {
    background: rgba(139, 92, 246, 0.15);
    color: #a78bfa;
    border-color: rgba(139, 92, 246, 0.4);
}

.job-status {
    color: var(--gray);
    font-size: 0.8rem;
    background: rgba(148, 163, 184, 0.1);
    padding: 0.3rem 0.8rem;
    border-radius: 12px;
    border: 1px solid var(--border);
}

.proposal-content {
    padding: 1.5rem;
}

.proposal-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    background: rgba(30, 41, 59, 0.5);
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid var(--border);
}

.detail-item strong {
    color: var(--light);
    font-size: 0.9rem;
    font-weight: 600;
}

.detail-item span {
    color: var(--gray);
    font-size: 0.9rem;
}

.cover-letter-preview strong {
    color: var(--light);
    font-size: 1rem;
    display: block;
    margin-bottom: 0.8rem;
    font-weight: 600;
}

.cover-letter-preview p {
    color: var(--gray);
    line-height: 1.6;
    margin: 0;
    font-size: 0.95rem;
    background: rgba(30, 41, 59, 0.5);
    padding: 1rem;
    border-radius: 8px;
    border-left: 3px solid var(--primary);
}

.proposal-actions {
    padding: 1.5rem;
    background: rgba(30, 41, 59, 0.8);
    border-top: 1px solid var(--border);
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.proposal-actions .btn {
    min-width: 140px;
    justify-content: center;
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
    background: var(--dark-surface);
    border-radius: 12px;
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

/* Navigation active state */
.nav-link.active {
    color: var(--primary-light);
    background: rgba(16, 185, 129, 0.1);
    border-left: 3px solid var(--primary);
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

.stat-card:nth-child(1) { animation-delay: 0.1s; }
.stat-card:nth-child(2) { animation-delay: 0.2s; }
.stat-card:nth-child(3) { animation-delay: 0.3s; }
.stat-card:nth-child(4) { animation-delay: 0.4s; }

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

.proposals-list .proposal-card:nth-child(1) { animation-delay: 0.1s; }
.proposals-list .proposal-card:nth-child(2) { animation-delay: 0.2s; }
.proposals-list .proposal-card:nth-child(3) { animation-delay: 0.3s; }
.proposals-list .proposal-card:nth-child(4) { animation-delay: 0.4s; }
.proposals-list .proposal-card:nth-child(5) { animation-delay: 0.5s; }

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
    .proposal-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }

    .proposal-status {
        flex-direction: row;
        align-items: center;
        width: 100%;
        justify-content: space-between;
    }

    .proposal-details {
        grid-template-columns: 1fr;
    }

    .section-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }

    .stat-card {
        flex-direction: column;
        text-align: center;
        padding: 1.2rem;
    }

    .stat-icon {
        width: 50px;
        height: 50px;
    }

    .stat-icon i {
        font-size: 1.2rem;
    }

    .stat-content h3 {
        font-size: 1.8rem;
    }

    .page-header h1 {
        font-size: 2rem;
    }

    .proposal-actions {
        flex-direction: column;
    }

    .proposal-actions .btn {
        min-width: auto;
        width: 100%;
    }
}

@media (max-width: 480px) {
    .page-container {
        padding: 1rem 0;
        margin-top: 60px;
    }

    .proposals-section {
        padding: 1.5rem;
    }

    .proposal-header,
    .proposal-content,
    .proposal-actions {
        padding: 1rem;
    }

    .detail-item {
        padding: 0.8rem;
    }

    .stats-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
}

/* Additional job status styles */
.job-status-open {
    color: var(--primary-light);
    background: rgba(16, 185, 129, 0.1);
    border-color: rgba(16, 185, 129, 0.3);
}

.job-status-closed {
    color: var(--gray);
    background: rgba(148, 163, 184, 0.1);
    border-color: rgba(148, 163, 184, 0.3);
}

.job-status-in_progress {
    color: #93c5fd;
    background: rgba(96, 165, 250, 0.1);
    border-color: rgba(96, 165, 250, 0.3);
}

/* Highlight accepted proposals */
.proposal-card.status-accepted {
    border-color: var(--primary);
}

.proposal-card.status-accepted::before {
    opacity: 1;
    background: linear-gradient(90deg, var(--primary), var(--accent));
}
    </style>
</body>
</html>