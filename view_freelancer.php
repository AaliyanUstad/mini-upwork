<?php
include_once 'config/database.php';
include_once 'models/User.php';
include_once 'includes/session.php';

if (!Session::isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

if (!isset($_GET['id'])) {
    header("Location: " . (Session::getUserType() == 'client' ? 'my_jobs.php' : 'browse_jobs.php'));
    exit();
}

$freelancer_id = $_GET['id'];
$freelancer_data = $user->getUserById($freelancer_id);

if (!$freelancer_data || $freelancer_data['user_type'] != 'freelancer') {
    header("Location: " . (Session::getUserType() == 'client' ? 'my_jobs.php' : 'browse_jobs.php'));
    exit();
}
?>
<!DOCTYPE html>
<html>

<head>
    <title><?php echo htmlspecialchars($freelancer_data['first_name'] . ' ' . $freelancer_data['last_name']); ?> -
        Freelancing Platform</title>
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
                    <h1><?php echo htmlspecialchars($freelancer_data['first_name'] . ' ' . $freelancer_data['last_name']); ?>
                    </h1>
                    <p>Freelancer Profile</p>
                </div>
                <a href="javascript:history.back()" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i>Back
                </a>
            </div>

            <div class="profile-container">
                <div class="profile-sidebar">
                    <div class="profile-card">
                        <div class="profile-avatar">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <div class="profile-info">
                            <h2><?php echo htmlspecialchars($freelancer_data['first_name'] . ' ' . $freelancer_data['last_name']); ?>
                            </h2>
                            <p class="profile-title">Freelancer</p>

                            <?php if ($freelancer_data['hourly_rate']): ?>
                                <div class="hourly-rate">
                                    <strong>$<?php echo number_format($freelancer_data['hourly_rate'], 2); ?>/hr</strong>
                                </div>
                            <?php endif; ?>

                            <?php if ($freelancer_data['portfolio_url']): ?>
                                <div class="portfolio-link">
                                    <a href="<?php echo htmlspecialchars($freelancer_data['portfolio_url']); ?>"
                                        target="_blank" class="btn btn-outline btn-full">
                                        <i class="fas fa-external-link-alt"></i>View Portfolio
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="profile-main">
                    <?php if ($freelancer_data['bio']): ?>
                        <div class="profile-section">
                            <h3>About Me</h3>
                            <div class="bio-content">
                                <?php echo nl2br(htmlspecialchars($freelancer_data['bio'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($freelancer_data['skills']): ?>
                        <div class="profile-section">
                            <h3>Skills & Expertise</h3>
                            <div class="skills-list">
                                <?php
                                $skills = explode(',', $freelancer_data['skills']);
                                foreach ($skills as $skill): ?>
                                    <span class="skill-tag"><?php echo trim($skill); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="profile-section">
                        <h3>Contact Information</h3>
                        <div class="contact-info">
                            <div class="contact-item">
                                <strong>Email:</strong>
                                <span><?php echo htmlspecialchars($freelancer_data['email']); ?></span>
                            </div>
                            <div class="contact-item">
                                <strong>Member Since:</strong>
                                <span><?php echo date('M j, Y', strtotime($freelancer_data['created_at'])); ?></span>
                            </div>
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

        .profile-container {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 2.5rem;
        }

        .profile-sidebar {
            position: sticky;
            top: 80px;
            height: fit-content;
        }

        .profile-card {
            background: var(--dark-card);
            border-radius: 15px;
            padding: 2.5rem 2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            text-align: center;
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }

        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .profile-avatar {
            font-size: 4.5rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .profile-info h2 {
            margin: 0 0 0.5rem 0;
            color: var(--light);
            font-size: 1.6rem;
            font-weight: 700;
        }

        .profile-title {
            color: var(--gray);
            margin: 0 0 1.5rem 0;
            font-size: 1rem;
            background: rgba(16, 185, 129, 0.1);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            border: 1px solid rgba(16, 185, 129, 0.3);
            display: inline-block;
        }

        .hourly-rate {
            font-size: 1.5rem;
            color: var(--primary);
            font-weight: bold;
            margin-bottom: 2rem;
            text-shadow: 0 0 10px rgba(16, 185, 129, 0.3);
            background: rgba(16, 185, 129, 0.1);
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .btn-full {
            width: 100%;
            padding: 1rem;
            font-weight: 600;
            border-radius: 12px;
            justify-content: center;
        }

        .profile-main {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .profile-section {
            background: var(--dark-card);
            border-radius: 15px;
            padding: 2.5rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }

        .profile-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .profile-section h3 {
            margin: 0 0 1.5rem 0;
            color: var(--light);
            font-size: 1.4rem;
            font-weight: 600;
        }

        .bio-content {
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

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }

        .contact-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            background: rgba(30, 41, 59, 0.5);
            border-radius: 10px;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
        }

        .contact-item:hover {
            border-color: var(--primary);
            transform: translateX(5px);
        }

        .contact-item strong {
            color: var(--light);
            font-weight: 600;
            font-size: 0.95rem;
        }

        .contact-item span {
            color: var(--gray);
            font-size: 0.95rem;
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

        /* Portfolio link specific styling */
        .portfolio-link .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            transition: all 0.3s ease;
        }

        .portfolio-link .btn-outline:hover {
            background: var(--primary);
            color: var(--light);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
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

        .profile-card,
        .profile-section {
            animation: fadeInUp 0.6s ease-out;
        }

        .profile-card {
            animation-delay: 0.1s;
        }

        .profile-section:nth-child(1) {
            animation-delay: 0.2s;
        }

        .profile-section:nth-child(2) {
            animation-delay: 0.3s;
        }

        .profile-section:nth-child(3) {
            animation-delay: 0.4s;
        }

        /* Responsive Design */
        @media (max-width: 968px) {
            .profile-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .profile-sidebar {
                position: static;
            }

            .profile-card {
                max-width: 400px;
                margin: 0 auto;
            }
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                gap: 1rem;
            }

            .profile-section {
                padding: 2rem 1.5rem;
            }

            .profile-card {
                padding: 2rem 1.5rem;
            }

            .contact-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
                text-align: left;
            }

            .header-content h1 {
                font-size: 1.8rem;
            }

            .profile-info h2 {
                font-size: 1.4rem;
            }
        }

        @media (max-width: 480px) {
            .page-container {
                padding: 1rem 0;
                margin-top: 60px;
            }

            .profile-section {
                padding: 1.5rem 1rem;
            }

            .profile-card {
                padding: 1.5rem 1rem;
            }

            .profile-avatar {
                font-size: 3.5rem;
            }

            .skills-list {
                gap: 0.5rem;
            }

            .skill-tag {
                padding: 0.5rem 1rem;
                font-size: 0.85rem;
            }

            .bio-content {
                padding: 1rem;
                font-size: 1rem;
            }
        }

        /* Additional styling for empty states */
        .profile-section:empty {
            display: none;
        }

        /* Enhanced avatar animation */
        .profile-avatar {
            transition: transform 0.3s ease;
        }

        .profile-card:hover .profile-avatar {
            transform: scale(1.1);
        }

        /* Focus states for accessibility */
        .btn-outline:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        /* Loading state for external links */
        .portfolio-link a:active {
            transform: translateY(0);
        }

        /* Custom scrollbar for bio content */
        .bio-content {
            max-height: 300px;
            overflow-y: auto;
        }

        .bio-content::-webkit-scrollbar {
            width: 6px;
        }

        .bio-content::-webkit-scrollbar-track {
            background: var(--dark-surface);
            border-radius: 3px;
        }

        .bio-content::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 3px;
        }

        .bio-content::-webkit-scrollbar-thumb:hover {
            background: var(--primary-light);
        }

        /* Stats section styling (if added later) */
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: rgba(30, 41, 59, 0.5);
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary);
            display: block;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--gray);
            margin-top: 0.25rem;
        }
    </style>
</body>

</html>