<?php
include_once 'config/database.php';
include_once 'models/Job.php';
include_once 'includes/session.php';

if (!Session::isLoggedIn() || Session::getUserType() != 'freelancer') {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$job = new Job($db);

$search = $_GET['search'] ?? '';
$min_budget = $_GET['min_budget'] ?? '';
$max_budget = $_GET['max_budget'] ?? '';
$budget_type = $_GET['budget_type'] ?? '';

$query = "SELECT j.*, u.first_name, u.last_name, u.company_name 
          FROM jobs j 
          JOIN users u ON j.client_id = u.id 
          WHERE j.status = 'open'";

$params = [];

if (!empty($search)) {
    $query .= " AND (j.title LIKE ? OR j.description LIKE ? OR j.skills_required LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if (!empty($min_budget)) {
    $query .= " AND j.budget >= ?";
    $params[] = $min_budget;
}

if (!empty($max_budget)) {
    $query .= " AND j.budget <= ?";
    $params[] = $max_budget;
}

if (!empty($budget_type)) {
    $query .= " AND j.budget_type = ?";
    $params[] = $budget_type;
}

$query .= " ORDER BY j.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$has_applied = [];
foreach ($jobs as $job_item) {
    $check_query = "SELECT id FROM proposals WHERE job_id = ? AND freelancer_id = ?";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([$job_item['id'], Session::get('user_id')]);
    $has_applied[$job_item['id']] = $check_stmt->rowCount() > 0;
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Browse Jobs - Freelancing Platform</title>
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
                <a class="nav-link active" href="browse_jobs.php">
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
                <h1>Browse Jobs</h1>
                <p>Find your next freelance opportunity</p>
            </div>

            <div class="jobs-container">
                <div class="filters-sidebar">
                    <div class="filters-card">
                        <h3>Filters</h3>
                        <form method="GET" class="filters-form">
                            <div class="form-group">
                                <label>Search Keywords</label>
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                                    placeholder="Job title, skills...">
                            </div>

                            <div class="form-group">
                                <label>Budget Type</label>
                                <select name="budget_type">
                                    <option value="">All Types</option>
                                    <option value="fixed" <?php echo $budget_type == 'fixed' ? 'selected' : ''; ?>>Fixed
                                        Price</option>
                                    <option value="hourly" <?php echo $budget_type == 'hourly' ? 'selected' : ''; ?>>
                                        Hourly</option>
                                </select>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Min Budget ($)</label>
                                    <input type="number" name="min_budget"
                                        value="<?php echo htmlspecialchars($min_budget); ?>" placeholder="0">
                                </div>
                                <div class="form-group">
                                    <label>Max Budget ($)</label>
                                    <input type="number" name="max_budget"
                                        value="<?php echo htmlspecialchars($max_budget); ?>" placeholder="10000">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-full">
                                <i class="fas fa-filter"></i>Apply Filters
                            </button>
                            <a href="browse_jobs.php" class="btn btn-outline btn-full">Clear Filters</a>
                        </form>
                    </div>
                </div>

                <div class="jobs-main">
                    <div class="jobs-header">
                        <h2>Available Jobs (<?php echo count($jobs); ?>)</h2>
                        <div class="sort-options">
                            <span>Sort by:</span>
                            <select onchange="location = this.value;">
                                <option value="browse_jobs.php?sort=newest">Newest First</option>
                                <option value="browse_jobs.php?sort=budget_high">Budget: High to Low</option>
                                <option value="browse_jobs.php?sort=budget_low">Budget: Low to High</option>
                            </select>
                        </div>
                    </div>

                    <div class="jobs-list">
                        <?php if (!empty($jobs)): ?>
                            <?php foreach ($jobs as $job_item): ?>
                                <div class="job-card">
                                    <div class="job-header">
                                        <h3><?php echo htmlspecialchars($job_item['title']); ?></h3>
                                        <div class="job-meta">
                                            <span class="budget-type"><?php echo ucfirst($job_item['budget_type']); ?></span>
                                            <span
                                                class="budget-amount">$<?php echo number_format($job_item['budget'], 2); ?></span>
                                        </div>
                                    </div>

                                    <div class="job-content">
                                        <p class="job-description">
                                            <?php echo strlen($job_item['description']) > 150 ?
                                                substr(htmlspecialchars($job_item['description']), 0, 150) . '...' :
                                                htmlspecialchars($job_item['description']); ?>
                                        </p>

                                        <?php if ($job_item['skills_required']): ?>
                                            <div class="job-skills">
                                                <?php
                                                $skills = explode(',', $job_item['skills_required']);
                                                foreach (array_slice($skills, 0, 3) as $skill): ?>
                                                    <span class="skill-tag"><?php echo trim($skill); ?></span>
                                                <?php endforeach; ?>
                                                <?php if (count($skills) > 3): ?>
                                                    <span class="skill-more">+<?php echo count($skills) - 3; ?> more</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="job-footer">
                                            <div class="client-info">
                                                <i class="fas fa-user"></i>
                                                <?php echo htmlspecialchars($job_item['company_name'] ?: $job_item['first_name'] . ' ' . $job_item['last_name']); ?>
                                            </div>
                                            <div class="job-date">
                                                <?php echo date('M j, Y', strtotime($job_item['created_at'])); ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="job-actions">
                                        <a href="view_job.php?id=<?php echo $job_item['id']; ?>" class="btn btn-outline">
                                            <i class="fas fa-eye"></i>View Details
                                        </a>
                                        <?php if ($has_applied[$job_item['id']]): ?>
                                            <button class="btn btn-success" disabled>
                                                <i class="fas fa-check"></i>Applied
                                            </button>
                                        <?php else: ?>
                                            <a href="submit_proposal.php?job_id=<?php echo $job_item['id']; ?>"
                                                class="btn btn-primary">
                                                <i class="fas fa-paper-plane"></i>Apply Now
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-data">
                                <i class="fas fa-search"></i>
                                <h3>No jobs found</h3>
                                <p>Try adjusting your search criteria or check back later for new opportunities.</p>
                                <a href="browse_jobs.php" class="btn btn-primary">Clear Filters</a>
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

        .jobs-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
        }

        .filters-sidebar {
            position: sticky;
            top: 80px;
            height: fit-content;
        }

        .filters-card {
            background: var(--dark-card);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border);
        }

        .filters-card h3 {
            margin: 0 0 1.5rem 0;
            color: var(--light);
            font-size: 1.3rem;
        }

        .filters-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--light);
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 0.9rem;
            background: var(--dark-surface);
            color: var(--light);
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }

        .btn-full {
            width: 100%;
            justify-content: center;
        }

        .jobs-main {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .jobs-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            background: var(--dark-card);
            border-radius: 10px;
            padding: 1.5rem;
            border: 1px solid var(--border);
        }

        .jobs-header h2 {
            margin: 0;
            color: var(--light);
            font-size: 1.5rem;
        }

        .sort-options {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray);
        }

        .sort-options select {
            padding: 0.6rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--dark-surface);
            color: var(--light);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .sort-options select:focus {
            border-color: var(--primary);
            outline: none;
        }

        .jobs-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .job-card {
            background: var(--dark-card);
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid var(--border);
            position: relative;
        }

        .job-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            border-color: var(--primary);
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

        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 1.5rem;
            background: rgba(30, 41, 59, 0.8);
            border-bottom: 1px solid var(--border);
        }

        .job-header h3 {
            margin: 0;
            color: var(--light);
            font-size: 1.3rem;
            flex: 1;
            line-height: 1.4;
        }

        .job-meta {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.5rem;
        }

        .budget-type {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--light);
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }

        .budget-amount {
            font-size: 1.4rem;
            font-weight: bold;
            color: var(--primary);
            text-shadow: 0 0 10px rgba(16, 185, 129, 0.3);
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

        .job-skills {
            margin-bottom: 1.5rem;
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

        .skill-more {
            color: var(--gray);
            font-size: 0.8rem;
            font-style: italic;
        }

        .job-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }

        .client-info {
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .client-info i {
            color: var(--primary);
        }

        .job-date {
            color: var(--gray);
            font-size: 0.9rem;
        }

        .job-actions {
            padding: 1.5rem;
            background: rgba(30, 41, 59, 0.8);
            border-top: 1px solid var(--border);
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .job-actions .btn {
            min-width: 140px;
            justify-content: center;
        }

        .btn-success {
            background: var(--primary);
            color: var(--light);
            border: none;
            opacity: 0.8;
            cursor: not-allowed;
        }

        .btn-success:hover {
            transform: none;
            box-shadow: none;
        }

        .no-data {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--dark-card);
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            border: 2px dashed var(--border);
            grid-column: 1 / -1;
        }

        .no-data i {
            font-size: 3rem;
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

        /* Active state for navigation */
        .nav-link.active {
            color: var(--primary-light);
            background: rgba(16, 185, 129, 0.1);
            border-left: 3px solid var(--primary);
        }

        /* Form enhancements */
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type="number"] {
            -moz-appearance: textfield;
        }

        /* Loading states */
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        /* Responsive Design */
        @media (max-width: 968px) {
            .jobs-container {
                grid-template-columns: 1fr;
            }

            .filters-sidebar {
                position: static;
            }

            .job-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .job-meta {
                flex-direction: row;
                align-items: center;
                width: 100%;
                justify-content: space-between;
            }
        }

        @media (max-width: 768px) {
            .jobs-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .job-actions {
                flex-direction: column;
            }

            .job-actions .btn {
                min-width: auto;
                width: 100%;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .job-header h3 {
                font-size: 1.1rem;
            }

            .budget-amount {
                font-size: 1.2rem;
            }
        }

        @media (max-width: 480px) {
            .page-container {
                padding: 1rem 0;
                margin-top: 60px;
            }

            .jobs-header {
                padding: 1rem;
            }

            .job-header,
            .job-content,
            .job-actions {
                padding: 1rem;
            }

            .filters-card {
                padding: 1rem;
            }
        }

        /* Animation for new job cards */
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

        /* Stagger animation for multiple cards */
        .jobs-list .job-card:nth-child(1) {
            animation-delay: 0.1s;
        }

        .jobs-list .job-card:nth-child(2) {
            animation-delay: 0.2s;
        }

        .jobs-list .job-card:nth-child(3) {
            animation-delay: 0.3s;
        }

        .jobs-list .job-card:nth-child(4) {
            animation-delay: 0.4s;
        }

        .jobs-list .job-card:nth-child(5) {
            animation-delay: 0.5s;
        }
    </style>
</body>

</html>