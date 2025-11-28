<?php
include_once 'config/database.php';
include_once 'models/Job.php';
include_once 'models/Proposal.php';
include_once 'includes/session.php';

if (!Session::isLoggedIn() || Session::getUserType() != 'freelancer') {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$job = new Job($db);
$proposal = new Proposal($db);

if (!isset($_GET['job_id'])) {
    header("Location: browse_jobs.php");
    exit();
}

$job_id = $_GET['job_id'];
$job_data = $job->getJobById($job_id);

if (!$job_data || $job_data['status'] != 'open') {
    header("Location: browse_jobs.php");
    exit();
}

$has_applied = false;
$check_query = "SELECT id FROM proposals WHERE job_id = ? AND freelancer_id = ?";
$check_stmt = $db->prepare($check_query);
$check_stmt->execute([$job_id, Session::get('user_id')]);
$has_applied = $check_stmt->rowCount() > 0;

if ($has_applied) {
    header("Location: view_job.php?id=" . $job_id);
    exit();
}

$message = '';
if ($_POST) {
    $proposal->job_id = $job_id;
    $proposal->freelancer_id = Session::get('user_id');
    $proposal->cover_letter = $_POST['cover_letter'];
    $proposal->bid_amount = $_POST['bid_amount'];
    $proposal->estimated_hours = $_POST['estimated_hours'] ?? null;

    if ($proposal->create()) {
        $message = "success:Proposal submitted successfully!";
    } else {
        $message = "error:Failed to submit proposal. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Submit Proposal - Freelancing Platform</title>
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
                    <h1>Submit Proposal</h1>
                    <p>Apply for: <?php echo htmlspecialchars($job_data['title']); ?></p>
                </div>
                <a href="view_job.php?id=<?php echo $job_id; ?>" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i>Back to Job
                </a>
            </div>

            <div class="proposal-container">
                <div class="job-summary">
                    <div class="summary-card">
                        <h3>Job Summary</h3>
                        <div class="summary-content">
                            <div class="summary-item">
                                <strong>Client:</strong>
                                <span><?php echo htmlspecialchars($job_data['company_name'] ?: $job_data['first_name'] . ' ' . $job_data['last_name']); ?></span>
                            </div>
                            <div class="summary-item">
                                <strong>Budget:</strong>
                                <span>$<?php echo number_format($job_data['budget'], 2); ?>
                                    (<?php echo $job_data['budget_type']; ?>)</span>
                            </div>
                            <div class="summary-item">
                                <strong>Posted:</strong>
                                <span><?php echo date('M j, Y', strtotime($job_data['created_at'])); ?></span>
                            </div>
                            <?php if ($job_data['skills_required']): ?>
                                <div class="summary-item">
                                    <strong>Skills Required:</strong>
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
                    </div>
                </div>

                <div class="proposal-form">
                    <div class="form-card">
                        <div class="form-header">
                            <h2>Your Proposal</h2>
                            <p>Convince the client why you're the right fit</p>
                        </div>
                        <div class="form-body">
                            <?php if ($message):
                                list($type, $msg) = explode(':', $message);
                                ?>
                                <div class="alert alert-<?php echo $type; ?>"><?php echo $msg; ?></div>

                                <?php if ($type == 'success'): ?>
                                    <div class="success-actions">
                                        <a href="my_proposals.php" class="btn btn-primary">View My Proposals</a>
                                        <a href="browse_jobs.php" class="btn btn-outline">Browse More Jobs</a>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php if (!$message || $type == 'error'): ?>
                                <form method="POST">
                                    <div class="form-group">
                                        <label>Your Bid Amount ($)</label>
                                        <input type="number" name="bid_amount" step="0.01" min="1"
                                            value="<?php echo $job_data['budget'] * 0.9; ?>" required>
                                        <small>Suggested: $<?php echo number_format($job_data['budget'], 2); ?></small>
                                    </div>

                                    <?php if ($job_data['budget_type'] == 'hourly'): ?>
                                        <div class="form-group">
                                            <label>Estimated Hours</label>
                                            <input type="number" name="estimated_hours" min="1"
                                                placeholder="How many hours do you estimate?">
                                        </div>
                                    <?php endif; ?>

                                    <div class="form-group">
                                        <label>Cover Letter</label>
                                        <textarea name="cover_letter" rows="8"
                                            placeholder="Introduce yourself and explain why you're perfect for this job. Include your relevant experience and how you plan to approach the project."
                                            required></textarea>
                                        <small>Tip: Be specific about your skills and experience relevant to this
                                            job.</small>
                                    </div>

                                    <div class="form-tips">
                                        <h4>Proposal Tips:</h4>
                                        <ul>
                                            <li>Address the client by name if possible</li>
                                            <li>Highlight your relevant experience</li>
                                            <li>Explain your approach to the project</li>
                                            <li>Mention your availability</li>
                                            <li>Keep it professional but friendly</li>
                                        </ul>
                                    </div>

                                    <button type="submit" class="btn btn-primary btn-full">
                                        <i class="fas fa-paper-plane"></i>Submit Proposal
                                    </button>
                                </form>
                            <?php endif; ?>
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
            margin: 0;
            font-size: 1.1rem;
        }

        .proposal-container {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 2.5rem;
        }

        .job-summary {
            position: sticky;
            top: 80px;
            height: fit-content;
        }

        .summary-card {
            background: var(--dark-card);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }

        .summary-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .summary-card h3 {
            margin: 0 0 1.5rem 0;
            color: var(--light);
            font-size: 1.4rem;
            font-weight: 600;
        }

        .summary-content {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .summary-item {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .summary-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .summary-item strong {
            color: var(--light);
            font-size: 0.95rem;
            font-weight: 600;
        }

        .summary-item span {
            color: var(--gray);
            font-size: 0.95rem;
        }

        .skills-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .skill-tag {
            background: rgba(16, 185, 129, 0.15);
            color: var(--primary-light);
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            border: 1px solid rgba(16, 185, 129, 0.3);
            transition: all 0.3s ease;
        }

        .skill-tag:hover {
            background: rgba(16, 185, 129, 0.25);
            transform: translateY(-1px);
        }

        .proposal-form {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .form-card {
            background: var(--dark-card);
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            border: 1px solid var(--border);
            position: relative;
        }

        .form-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .form-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--light);
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .form-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
        }

        .form-header h2 {
            margin: 0 0 0.5rem 0;
            font-size: 1.7rem;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }

        .form-header p {
            margin: 0;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        .form-body {
            padding: 2.5rem;
        }

        .form-group {
            margin-bottom: 2rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: var(--light);
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 1rem 1.2rem;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--dark-surface);
            color: var(--light);
            font-family: inherit;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: var(--gray);
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
            background: var(--dark-bg);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 220px;
            line-height: 1.6;
        }

        .form-group small {
            color: var(--gray);
            font-size: 0.875rem;
            margin-top: 0.5rem;
            display: block;
            font-style: italic;
        }

        .form-tips {
            background: rgba(30, 41, 59, 0.8);
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            border: 1px solid var(--border);
            border-left: 4px solid var(--primary);
        }

        .form-tips h4 {
            margin: 0 0 1rem 0;
            color: var(--light);
            font-size: 1.1rem;
        }

        .form-tips ul {
            margin: 0;
            padding-left: 1.5rem;
            color: var(--gray);
        }

        .form-tips li {
            margin-bottom: 0.5rem;
            line-height: 1.5;
        }

        .btn-full {
            width: 100%;
            padding: 1.2rem;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border: none;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-full::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-full:hover::before {
            left: 100%;
        }

        .btn-full:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--secondary) 100%);
        }

        .success-actions {
            text-align: center;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .alert {
            padding: 1.2rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            border: 1px solid;
            font-weight: 500;
            animation: slideInDown 0.5s ease-out;
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

        /* Animations */
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

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

        .summary-card,
        .form-card {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Form validation styles */
        .form-group input:invalid:not(:focus):not(:placeholder-shown),
        .form-group textarea:invalid:not(:focus):not(:placeholder-shown) {
            border-color: #ef4444;
            background: rgba(239, 68, 68, 0.05);
        }

        .form-group input:valid:not(:focus):not(:placeholder-shown),
        .form-group textarea:valid:not(:focus):not(:placeholder-shown) {
            border-color: var(--primary);
            background: rgba(16, 185, 129, 0.05);
        }

        /* Responsive Design */
        @media (max-width: 968px) {
            .proposal-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .job-summary {
                position: static;
            }
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                gap: 1rem;
            }

            .form-body {
                padding: 2rem 1.5rem;
            }

            .form-header {
                padding: 1.5rem;
            }

            .summary-card {
                padding: 1.5rem;
            }

            .page-header h1 {
                font-size: 1.8rem;
            }

            .success-actions {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .page-container {
                padding: 1rem 0;
                margin-top: 60px;
            }

            .form-body {
                padding: 1.5rem 1rem;
            }

            .form-header {
                padding: 1.2rem 1rem;
            }

            .summary-card {
                padding: 1.2rem;
            }

            .form-group input,
            .form-group textarea {
                padding: 0.9rem 1rem;
            }

            .btn-full {
                padding: 1rem;
                font-size: 1rem;
            }
        }

        /* Focus states for accessibility */
        .form-group input:focus-visible,
        .form-group textarea:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        /* Loading state for submit button */
        .btn-full:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none !important;
        }

        .btn-full:disabled:hover {
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        /* Custom scrollbar for textarea */
        .form-group textarea::-webkit-scrollbar {
            width: 6px;
        }

        .form-group textarea::-webkit-scrollbar-track {
            background: var(--dark-surface);
            border-radius: 3px;
        }

        .form-group textarea::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 3px;
        }

        .form-group textarea::-webkit-scrollbar-thumb:hover {
            background: var(--primary-light);
        }

        /* Bid amount field styling */
        .form-group input[name="bid_amount"] {
            background: var(--dark-surface);
            border: 2px solid var(--border);
            font-weight: 600;
        }

        .form-group input[name="bid_amount"]:focus {
            border-color: var(--primary);
            background: var(--dark-bg);
        }

        /* Estimated hours field styling */
        .form-group input[name="estimated_hours"] {
            background: var(--dark-surface);
            border: 2px solid var(--border);
        }

        .form-group input[name="estimated_hours"]:focus {
            border-color: var(--primary);
            background: var(--dark-bg);
        }

        /* Cover letter textarea specific styling */
        .form-group textarea[name="cover_letter"] {
            background: var(--dark-surface);
            border: 2px solid var(--border);
            min-height: 240px;
        }

        .form-group textarea[name="cover_letter"]:focus {
            border-color: var(--primary);
            background: var(--dark-bg);
        }
    </style>
</body>

</html>