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

$user_data = $user->getUserById(Session::get('user_id'));
$message = '';

if ($_POST) {
    $user->id = Session::get('user_id');
    $user->first_name = $_POST['first_name'];
    $user->last_name = $_POST['last_name'];
    $user->company_name = $_POST['company_name'] ?? '';
    $user->bio = $_POST['bio'] ?? '';
    $user->skills = $_POST['skills'] ?? '';
    $user->hourly_rate = $_POST['hourly_rate'] ?? 0;
    $user->portfolio_url = $_POST['portfolio_url'] ?? '';

    if ($user->updateProfile()) {
        $message = "Profile updated successfully!";
        $user_data = $user->getUserById(Session::get('user_id'));
    } else {
        $message = "Profile update failed!";
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Profile - Freelancing Platform</title>
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
                <a class="nav-link"
                    href="<?php echo Session::getUserType() == 'client' ? 'client_dashboard.php' : 'freelancer_dashboard.php'; ?>">
                    <i class="fas fa-tachometer-alt"></i>Dashboard
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="profile-container">
            <div class="profile-card">
                <div class="profile-header">
                    <h2>Edit Profile</h2>
                </div>
                <div class="profile-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name</label>
                                <input type="text" name="first_name" value="<?php echo $user_data['first_name']; ?>"
                                    required>
                            </div>
                            <div class="form-group">
                                <label>Last Name</label>
                                <input type="text" name="last_name" value="<?php echo $user_data['last_name']; ?>"
                                    required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" value="<?php echo $user_data['email']; ?>" disabled>
                            <small>Email cannot be changed</small>
                        </div>

                        <?php if (Session::getUserType() == 'client'): ?>
                            <div class="form-group">
                                <label>Company Name</label>
                                <input type="text" name="company_name" value="<?php echo $user_data['company_name']; ?>">
                            </div>
                        <?php endif; ?>

                        <?php if (Session::getUserType() == 'freelancer'): ?>
                            <div class="form-group">
                                <label>Skills (comma separated)</label>
                                <input type="text" name="skills" value="<?php echo $user_data['skills']; ?>"
                                    placeholder="PHP, JavaScript, Web Design">
                            </div>

                            <div class="form-group">
                                <label>Hourly Rate ($)</label>
                                <input type="number" name="hourly_rate" step="0.01"
                                    value="<?php echo $user_data['hourly_rate']; ?>">
                            </div>

                            <div class="form-group">
                                <label>Portfolio URL</label>
                                <input type="url" name="portfolio_url" value="<?php echo $user_data['portfolio_url']; ?>">
                            </div>
                        <?php endif; ?>

                        <div class="form-group">
                            <label>Bio</label>
                            <textarea name="bio" rows="4"><?php echo $user_data['bio']; ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        .profile-container {
            padding: 2rem 0;
            margin-top: 60px;
        }

        .profile-card {
            background: var(--dark-card);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid var(--border);
            position: relative;
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

        .profile-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: var(--light);
            padding: 2.5rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
        }

        .profile-header h2 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }

        .profile-body {
            padding: 2.5rem;
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: var(--light);
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
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
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
            background: var(--dark-bg);
        }

        .form-group input:disabled {
            background: rgba(30, 41, 59, 0.5);
            color: var(--gray);
            cursor: not-allowed;
            opacity: 0.7;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
            line-height: 1.5;
        }

        .form-group small {
            color: var(--gray);
            font-size: 0.875rem;
            margin-top: 0.5rem;
            display: block;
            font-style: italic;
        }

        /* Special styling for freelancer-specific fields */
        .form-group input[name="skills"] {
            background: var(--dark-surface);
            border: 2px solid var(--border);
        }

        .form-group input[name="skills"]:focus {
            border-color: var(--primary);
            background: var(--dark-bg);
        }

        .form-group input[name="hourly_rate"] {
            background: var(--dark-surface);
            border: 2px solid var(--border);
        }

        .form-group input[name="hourly_rate"]:focus {
            border-color: var(--primary);
            background: var(--dark-bg);
        }

        .form-group input[name="portfolio_url"] {
            background: var(--dark-surface);
            border: 2px solid var(--border);
        }

        .form-group input[name="portfolio_url"]:focus {
            border-color: var(--primary);
            background: var(--dark-bg);
        }

        .btn-primary {
            padding: 1rem 2rem;
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

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--secondary) 100%);
        }

        /* Navigation styles */
        .nav-link {
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: var(--primary-light);
            background: rgba(16, 185, 129, 0.1);
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

        .profile-card {
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
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .profile-body {
                padding: 2rem 1.5rem;
            }

            .profile-header {
                padding: 2rem 1.5rem;
            }

            .profile-header h2 {
                font-size: 1.7rem;
            }

            .profile-container {
                padding: 1rem 0;
                margin-top: 60px;
            }
        }

        @media (max-width: 480px) {
            .profile-body {
                padding: 1.5rem 1rem;
            }

            .profile-header {
                padding: 1.5rem 1rem;
            }

            .profile-header h2 {
                font-size: 1.5rem;
            }

            .form-group input,
            .form-group textarea,
            .form-group select {
                padding: 0.9rem 1rem;
            }

            .btn-primary {
                padding: 0.9rem 1.5rem;
                font-size: 1rem;
                width: 100%;
            }
        }

        /* Focus states for accessibility */
        .form-group input:focus-visible,
        .form-group textarea:focus-visible,
        .form-group select:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        /* Loading state for submit button */
        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none !important;
        }

        .btn-primary:disabled:hover {
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

        /* Company name field styling for clients */
        .form-group input[name="company_name"] {
            background: var(--dark-surface);
            border: 2px solid var(--border);
        }

        .form-group input[name="company_name"]:focus {
            border-color: var(--primary);
            background: var(--dark-bg);
        }

        /* Bio textarea specific styling */
        .form-group textarea[name="bio"] {
            background: var(--dark-surface);
            border: 2px solid var(--border);
            min-height: 140px;
        }

        .form-group textarea[name="bio"]:focus {
            border-color: var(--primary);
            background: var(--dark-bg);
        }

        /* User type specific section styling */
        .form-group:has(input[name="company_name"]) {
            border-left: 3px solid var(--primary);
            padding-left: 1rem;
            margin-left: -1rem;
        }

        .form-group:has(input[name="skills"]),
        .form-group:has(input[name="hourly_rate"]),
        .form-group:has(input[name="portfolio_url"]) {
            border-left: 3px solid var(--accent);
            padding-left: 1rem;
            margin-left: -1rem;
        }
    </style>
</body>

</html>