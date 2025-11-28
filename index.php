<?php
include_once 'config/database.php';
include_once 'includes/session.php';

$database = new Database();
$db = $database->getConnection();

$featured_jobs = [];
try {
    $query = "SELECT j.*, u.first_name, u.last_name, u.company_name 
              FROM jobs j 
              JOIN users u ON j.client_id = u.id 
              WHERE j.status = 'open' 
              ORDER BY j.created_at DESC 
              LIMIT 6";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $featured_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $exception) {
}

$freelancer_stats = [];
try {
    $query = "SELECT COUNT(*) as total_freelancers FROM users WHERE user_type = 'freelancer'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $freelancer_stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $exception) {
}

$job_stats = [];
try {
    $query = "SELECT COUNT(*) as total_jobs, 
                     COUNT(CASE WHEN status = 'open' THEN 1 END) as open_jobs 
              FROM jobs";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $job_stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $exception) {
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreelanceHub - Find Talent & Work</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-handshake"></i>
                FreelanceHub
            </a>
            <button class="navbar-toggle" type="button">
                <span class="navbar-toggle-icon"></span>
            </button>
            <div class="navbar-menu" id="navbarMenu">
                <a class="nav-link" href="#how-it-works">How It Works</a>
                <a class="nav-link" href="#featured-jobs">Find Work</a>
                <a class="nav-link" href="#why-choose">Why Choose Us</a>
                <?php if(Session::isLoggedIn()): ?>
                    <a class="nav-link" href="<?php echo Session::getUserType() == 'client' ? 'client_dashboard.php' : 'freelancer_dashboard.php'; ?>">
                        <i class="fas fa-tachometer-alt"></i>Dashboard
                    </a>
                    <a class="nav-link" href="profile.php">
                        <i class="fas fa-user"></i>Profile
                    </a>
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-sign-out-alt"></i>Logout
                    </a>
                <?php else: ?>
                    <a class="nav-btn secondary" href="login.php">Login</a>
                    <a class="nav-btn primary" href="register.php">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-text">
                    <h1>Find the Perfect Freelance Services for Your Business</h1>
                    <p>Connect with skilled freelancers from around the world. Get your projects done faster and more efficiently.</p>
                    <div class="hero-buttons">
                        <?php if(!Session::isLoggedIn()): ?>
                            <a href="register.php?user_type=client" class="btn btn-light">
                                <i class="fas fa-briefcase"></i>Hire Talent
                            </a>
                            <a href="register.php?user_type=freelancer" class="btn btn-outline">
                                <i class="fas fa-code"></i>Find Work
                            </a>
                        <?php else: ?>
                            <a href="<?php echo Session::getUserType() == 'client' ? 'post_job.php' : 'browse_jobs.php'; ?>" class="btn btn-light">
                                <i class="fas fa-rocket"></i>
                                <?php echo Session::getUserType() == 'client' ? 'Post a Job' : 'Browse Jobs'; ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="hero-image">
                    <img src="https://cdn.pixabay.com/photo/2018/03/10/12/00/paper-3213924_1280.jpg" 
                         alt="Freelance Collaboration">
                </div>
            </div>
        </div>
    </section>

    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $job_stats['total_jobs'] ?? '1000'; ?>+</div>
                    <p>Projects Posted</p>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $freelancer_stats['total_freelancers'] ?? '500'; ?>+</div>
                    <p>Skilled Freelancers</p>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $job_stats['open_jobs'] ?? '150'; ?>+</div>
                    <p>Open Jobs</p>
                </div>
                <div class="stat-item">
                    <div class="stat-number">98%</div>
                    <p>Client Satisfaction</p>
                </div>
            </div>
        </div>
    </section>

    <section id="how-it-works" class="section">
        <div class="container">
            <div class="section-header">
                <h2>How It Works</h2>
                <p>Simple steps to get your project done</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h4>1. Create Account</h4>
                    <p>Sign up as a client or freelancer in just a few minutes.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <h4>2. Post or Find Work</h4>
                    <p>Clients post projects, freelancers find perfect opportunities.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h4>3. Collaborate & Pay</h4>
                    <p>Work together securely and release payment upon completion.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="featured-jobs" class="section bg-light">
        <div class="container">
            <div class="section-header">
                <h2>Featured Jobs</h2>
                <p>Latest opportunities from our clients</p>
            </div>
            
            <div class="jobs-grid">
                <?php if(!empty($featured_jobs)): ?>
                    <?php foreach($featured_jobs as $job): ?>
                        <div class="job-card">
                            <div class="job-content">
                                <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                                <p class="job-description">
                                    <?php echo strlen($job['description']) > 100 ? 
                                        substr(htmlspecialchars($job['description']), 0, 100) . '...' : 
                                        htmlspecialchars($job['description']); ?>
                                </p>
                                <div class="job-skills">
                                    <?php if($job['skills_required']): ?>
                                        <?php 
                                        $skills = explode(',', $job['skills_required']);
                                        foreach(array_slice($skills, 0, 3) as $skill): ?>
                                            <span class="skill-tag"><?php echo trim($skill); ?></span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="job-meta">
                                    <div class="job-price">
                                        <strong>$<?php echo number_format($job['budget'], 2); ?></strong>
                                        <span><?php echo ucfirst($job['budget_type']); ?></span>
                                    </div>
                                    <div class="job-date">
                                        <?php echo date('M j, Y', strtotime($job['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="job-footer">
                                <?php if(Session::isLoggedIn() && Session::getUserType() == 'freelancer'): ?>
                                    <a href="view_job.php?id=<?php echo $job['id']; ?>" class="btn btn-primary">Apply Now</a>
                                <?php elseif(!Session::isLoggedIn()): ?>
                                    <a href="login.php" class="btn btn-primary">Login to Apply</a>
                                <?php endif; ?>
                                <div class="job-client">
                                    By: <?php echo htmlspecialchars($job['company_name'] ?: $job['first_name'] . ' ' . $job['last_name']); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-jobs">
                        <i class="fas fa-briefcase"></i>
                        <h4>No jobs available at the moment</h4>
                        <p>Check back later for new opportunities</p>
                        <?php if(Session::isLoggedIn() && Session::getUserType() == 'client'): ?>
                            <a href="post_job.php" class="btn btn-primary">Post First Job</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section id="why-choose" class="section">
        <div class="container">
            <div class="section-header">
                <h2>Why Choose FreelanceHub?</h2>
                <p>The best platform for freelancers and clients</p>
            </div>
            <div class="benefits-grid">
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="benefit-content">
                        <h4>Secure Payments</h4>
                        <p>Your payments are protected with our secure escrow system.</p>
                    </div>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="benefit-content">
                        <h4>Quality Talent</h4>
                        <p>Access to verified freelancers with proven track records.</p>
                    </div>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="benefit-content">
                        <h4>24/7 Support</h4>
                        <p>Get help whenever you need it with our dedicated support team.</p>
                    </div>
                </div>
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="benefit-content">
                        <h4>Growth Opportunities</h4>
                        <p>Build long-term relationships and grow your business.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="cta">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Get Started?</h2>
                <p>Join thousands of clients and freelancers already growing their business with us.</p>
                <?php if(!Session::isLoggedIn()): ?>
                    <a href="register.php" class="btn btn-light">Sign Up Free</a>
                    <a href="login.php" class="btn btn-outline">Learn More</a>
                <?php else: ?>
                    <a href="<?php echo Session::getUserType() == 'client' ? 'post_job.php' : 'browse_jobs.php'; ?>" 
                       class="btn btn-light">
                        Get Started Now
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h5><i class="fas fa-handshake"></i>FreelanceHub</h5>
                    <p>Connecting talented freelancers with amazing clients worldwide.</p>
                </div>
                <div class="footer-section">
                    <h6>Quick Links</h6>
                    <a href="#how-it-works">How It Works</a>
                    <a href="#featured-jobs">Find Work</a>
                    <a href="#why-choose">Why Choose Us</a>
                </div>
                <div class="footer-section">
                    <h6>Contact</h6>
                    <p><i class="fas fa-envelope"></i>support@freelancehub.com</p>
                    <p><i class="fas fa-phone"></i>+1 (555) 123-4567</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 FreelanceHub. All rights reserved.</p>
            </div>
        </div>
    </footer>

</body>
</html>