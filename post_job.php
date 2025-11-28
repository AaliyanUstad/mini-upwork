<?php
include_once 'config/database.php';
include_once 'models/Job.php';
include_once 'includes/session.php';

if(!Session::isLoggedIn() || Session::getUserType() != 'client'){
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$job = new Job($db);

$message = '';
if($_POST){
    $job->client_id = Session::get('user_id');
    $job->title = $_POST['title'];
    $job->description = $_POST['description'];
    $job->budget = $_POST['budget'];
    $job->budget_type = $_POST['budget_type'];
    $job->skills_required = $_POST['skills_required'];

    if($job->create()){
        $message = "success:Job posted successfully!";
    } else {
        $message = "error:Failed to post job. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Post a Job - Freelancing Platform</title>
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
        <div class="form-container">
            <div class="form-card">
                <div class="form-header">
                    <h2>Post a New Job</h2>
                    <p>Fill in the details to find the perfect freelancer</p>
                </div>
                <div class="form-body">
                    <?php if($message): 
                        list($type, $msg) = explode(':', $message, 2);
                    ?>
                        <div class="alert alert-<?php echo $type; ?>"><?php echo $msg; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label>Job Title</label>
                            <input type="text" name="title" placeholder="e.g., Website Developer Needed" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Job Description</label>
                            <textarea name="description" rows="6" placeholder="Describe the project in detail..." required></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Budget Type</label>
                                <select name="budget_type" required>
                                    <option value="fixed">Fixed Price</option>
                                    <option value="hourly">Hourly Rate</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Budget Amount ($)</label>
                                <input type="number" name="budget" step="0.01" min="1" placeholder="e.g., 500" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Skills Required (comma separated)</label>
                            <input type="text" name="skills_required" placeholder="e.g., PHP, JavaScript, Web Design">
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-full">Post Job</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
    .form-container {
    padding: 2rem 0;
    margin-top: 60px;
}

.form-card {
    background: var(--dark-card);
    border-radius: 15px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.3);
    overflow: hidden;
    max-width: 800px;
    margin: 0 auto;
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
    padding: 2.5rem 2rem;
    text-align: center;
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
    background: radial-gradient(circle at 30% 20%, rgba(255,255,255,0.1) 0%, transparent 50%);
}

.form-header h2 {
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
    font-weight: 700;
    position: relative;
    z-index: 1;
}

.form-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 1.1rem;
    position: relative;
    z-index: 1;
}

.form-body {
    padding: 2.5rem;
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

.form-group textarea {
    resize: vertical;
    min-height: 140px;
    line-height: 1.5;
}

.form-group select {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-size: 1.2rem;
}

.btn-full {
    width: 100%;
    padding: 1.2rem;
    font-size: 1.1rem;
    font-weight: 600;
    border-radius: 12px;
    margin-top: 1rem;
    justify-content: center;
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
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
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

.alert {
    padding: 1.2rem 1.5rem;
    border-radius: 10px;
    margin-bottom: 2rem;
    border: 1px solid;
    font-weight: 500;
    position: relative;
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
@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .form-body {
        padding: 2rem 1.5rem;
    }

    .form-header {
        padding: 2rem 1.5rem;
    }

    .form-header h2 {
        font-size: 1.7rem;
    }

    .form-container {
        padding: 1rem 0;
        margin-top: 60px;
    }
}

@media (max-width: 480px) {
    .form-body {
        padding: 1.5rem 1rem;
    }

    .form-header {
        padding: 1.5rem 1rem;
    }

    .form-header h2 {
        font-size: 1.5rem;
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
        padding: 0.9rem 1rem;
    }

    .btn-full {
        padding: 1rem;
        font-size: 1rem;
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

/* Skills input styling */
.form-group input[name="skills_required"] {
    background: var(--dark-surface);
    border: 2px solid var(--border);
}

.form-group input[name="skills_required"]:focus {
    border-color: var(--primary);
    background: var(--dark-bg);
}
    </style>
</body>
</html>