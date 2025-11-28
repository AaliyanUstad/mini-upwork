<?php
include_once 'config/database.php';
include_once 'includes/session.php';

if(!Session::isLoggedIn()){
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$user_id = Session::get('user_id');
$user_type = Session::getUserType();

if($user_type == 'freelancer') {
    $query = "SELECT c.*, j.title, j.description, u.first_name, u.last_name, u.company_name
              FROM contracts c 
              JOIN jobs j ON c.job_id = j.id 
              JOIN users u ON c.client_id = u.id 
              WHERE c.freelancer_id = ? 
              ORDER BY c.created_at DESC";
} else {
    $query = "SELECT c.*, j.title, j.description, u.first_name, u.last_name, u.skills
              FROM contracts c 
              JOIN jobs j ON c.job_id = j.id 
              JOIN users u ON c.freelancer_id = u.id 
              WHERE c.client_id = ? 
              ORDER BY c.created_at DESC";
}

$stmt = $db->prepare($query);
$stmt->execute([$user_id]);
$contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats = [
    'total' => count($contracts),
    'active' => 0,
    'completed' => 0,
    'cancelled' => 0
];

foreach($contracts as $contract) {
    $stats[$contract['contract_status']]++;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Contracts - Freelancing Platform</title>
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
                <?php if($user_type == 'freelancer'): ?>
                    <a class="nav-link" href="freelancer_dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>Dashboard
                    </a>
                    <a class="nav-link" href="browse_jobs.php">
                        <i class="fas fa-search"></i>Browse Jobs
                    </a>
                    <a class="nav-link" href="my_proposals.php">
                        <i class="fas fa-paper-plane"></i>My Proposals
                    </a>
                    <a class="nav-link active" href="my_contracts.php">
                        <i class="fas fa-file-contract"></i>My Contracts
                    </a>
                <?php else: ?>
                    <a class="nav-link" href="client_dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>Dashboard
                    </a>
                    <a class="nav-link" href="my_jobs.php">
                        <i class="fas fa-briefcase"></i>My Jobs
                    </a>
                    <a class="nav-link active" href="my_contracts.php">
                        <i class="fas fa-file-contract"></i>My Contracts
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
                <h1>My Contracts</h1>
                <p>Manage your active projects and agreements</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['total']; ?></h3>
                        <p>Total Contracts</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-play-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['active']; ?></h3>
                        <p>Active</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['completed']; ?></h3>
                        <p>Completed</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $stats['cancelled']; ?></h3>
                        <p>Cancelled</p>
                    </div>
                </div>
            </div>

            <div class="contracts-section">
                <div class="section-header">
                    <h2>All Contracts</h2>
                </div>
                
                <div class="contracts-list">
                    <?php if(!empty($contracts)): ?>
                        <?php foreach($contracts as $contract): ?>
                            <div class="contract-card">
                                <div class="contract-header">
                                    <div class="contract-info">
                                        <h3><?php echo htmlspecialchars($contract['title']); ?></h3>
                                        <div class="contract-party">
                                            <i class="fas fa-user"></i>
                                            <?php if($user_type == 'freelancer'): ?>
                                                Client: <?php echo htmlspecialchars($contract['company_name'] ?: $contract['first_name'] . ' ' . $contract['last_name']); ?>
                                            <?php else: ?>
                                                Freelancer: <?php echo htmlspecialchars($contract['first_name'] . ' ' . $contract['last_name']); ?>
                                                <?php if($contract['skills']): ?>
                                                    <span class="skills">(<?php echo htmlspecialchars($contract['skills']); ?>)</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="contract-status">
                                        <span class="status-badge status-<?php echo $contract['contract_status']; ?>">
                                            <?php echo ucfirst($contract['contract_status']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="contract-content">
                                    <div class="contract-details">
                                        <div class="detail-grid">
                                            <div class="detail-item">
                                                <strong>Contract Amount:</strong>
                                                <span>$<?php echo number_format($contract['contract_amount'], 2); ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <strong>Start Date:</strong>
                                                <span><?php echo date('M j, Y', strtotime($contract['start_date'])); ?></span>
                                            </div>
                                            <?php if($contract['end_date']): ?>
                                            <div class="detail-item">
                                                <strong>End Date:</strong>
                                                <span><?php echo date('M j, Y', strtotime($contract['end_date'])); ?></span>
                                            </div>
                                            <?php endif; ?>
                                            <div class="detail-item">
                                                <strong>Created:</strong>
                                                <span><?php echo date('M j, Y', strtotime($contract['created_at'])); ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="project-description">
                                            <strong>Project Description:</strong>
                                            <p><?php echo htmlspecialchars($contract['description']); ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="contract-actions">
                                    <a href="view_contract.php?id=<?php echo $contract['id']; ?>" class="btn btn-outline">
                                        <i class="fas fa-eye"></i>View Details
                                    </a>
                                    <?php if($contract['contract_status'] == 'active'): ?>
                                        <a href="messages.php?contract_id=<?php echo $contract['id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-comments"></i>Message
                                        </a>
                                        <?php if($user_type == 'client'): ?>
                                            <a href="complete_contract.php?id=<?php echo $contract['id']; ?>" 
                                               class="btn btn-success"
                                               onclick="return confirm('Mark this contract as completed?')">
                                                <i class="fas fa-check"></i>Complete
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-file-contract"></i>
                            <h3>No contracts yet</h3>
                            <p>
                                <?php if($user_type == 'freelancer'): ?>
                                    You haven't been hired for any projects yet. Start by submitting proposals to jobs.
                                <?php else: ?>
                                    You haven't hired any freelancers yet. Start by posting a job and accepting proposals.
                                <?php endif; ?>
                            </p>
                            <a href="<?php echo $user_type == 'freelancer' ? 'browse_jobs.php' : 'post_job.php'; ?>" class="btn btn-primary">
                                <?php echo $user_type == 'freelancer' ? 'Browse Jobs' : 'Post a Job'; ?>
                            </a>
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

.contracts-section {
    background: var(--dark-card);
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
    padding: 2rem;
    border: 1px solid var(--border);
    position: relative;
    overflow: hidden;
}

.contracts-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary), var(--secondary));
}

.section-header {
    margin-bottom: 2rem;
    position: relative;
    z-index: 1;
}

.section-header h2 {
    margin: 0;
    color: var(--light);
    font-size: 1.8rem;
}

.contracts-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.contract-card {
    background: var(--dark-surface);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
}

.contract-card::before {
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

.contract-card:hover {
    border-color: var(--primary);
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    transform: translateY(-3px);
}

.contract-card:hover::before {
    opacity: 1;
}

.contract-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 1.5rem;
    background: rgba(30, 41, 59, 0.8);
    border-bottom: 1px solid var(--border);
}

.contract-info h3 {
    margin: 0 0 0.5rem 0;
    color: var(--light);
    font-size: 1.3rem;
    line-height: 1.4;
}

.contract-party {
    color: var(--gray);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
}

.contract-party i {
    color: var(--primary);
}

.skills {
    color: var(--primary-light);
    font-style: italic;
    font-size: 0.85rem;
}

.contract-status {
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
}

.status-active {
    background: rgba(16, 185, 129, 0.15);
    color: var(--primary-light);
    border: 1px solid rgba(16, 185, 129, 0.4);
}

.status-completed {
    background: rgba(148, 163, 184, 0.15);
    color: var(--gray);
    border: 1px solid rgba(148, 163, 184, 0.4);
}

.status-cancelled {
    background: rgba(239, 68, 68, 0.15);
    color: #fca5a5;
    border: 1px solid rgba(239, 68, 68, 0.4);
}

.status-pending {
    background: rgba(245, 158, 11, 0.15);
    color: #fcd34d;
    border: 1px solid rgba(245, 158, 11, 0.4);
}

.contract-content {
    padding: 1.5rem;
}

.contract-details {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
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

.project-description strong {
    color: var(--light);
    font-size: 1rem;
    display: block;
    margin-bottom: 0.8rem;
    font-weight: 600;
}

.project-description p {
    color: var(--gray);
    line-height: 1.6;
    margin: 0;
    font-size: 0.95rem;
    background: rgba(30, 41, 59, 0.5);
    padding: 1rem;
    border-radius: 8px;
    border-left: 3px solid var(--primary);
}

.contract-actions {
    padding: 1.5rem;
    background: rgba(30, 41, 59, 0.8);
    border-top: 1px solid var(--border);
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.contract-actions .btn {
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
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
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

/* Animation for contract cards */
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

.contract-card {
    animation: fadeInUp 0.5s ease-out;
}

.contracts-list .contract-card:nth-child(1) { animation-delay: 0.1s; }
.contracts-list .contract-card:nth-child(2) { animation-delay: 0.2s; }
.contracts-list .contract-card:nth-child(3) { animation-delay: 0.3s; }
.contracts-list .contract-card:nth-child(4) { animation-delay: 0.4s; }
.contracts-list .contract-card:nth-child(5) { animation-delay: 0.5s; }

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
    .contract-header {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }

    .contract-status {
        flex-direction: row;
        align-items: center;
        width: 100%;
        justify-content: space-between;
    }

    .detail-grid {
        grid-template-columns: 1fr;
    }

    .contract-actions {
        flex-direction: column;
    }

    .contract-actions .btn {
        min-width: auto;
        width: 100%;
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
}

@media (max-width: 480px) {
    .page-container {
        padding: 1rem 0;
        margin-top: 60px;
    }

    .contracts-section {
        padding: 1.5rem;
    }

    .contract-header,
    .contract-content,
    .contract-actions {
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

/* Additional status styles */
.status-in_progress {
    background: rgba(96, 165, 250, 0.15);
    color: #93c5fd;
    border: 1px solid rgba(96, 165, 250, 0.4);
}

.status-disputed {
    background: rgba(245, 158, 11, 0.15);
    color: #fcd34d;
    border: 1px solid rgba(245, 158, 11, 0.4);
}

.status-on_hold {
    background: rgba(107, 114, 128, 0.15);
    color: #d1d5db;
    border: 1px solid rgba(107, 114, 128, 0.4);
}
    </style>
</body>
</html>