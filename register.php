<?php
include_once 'config/database.php';
include_once 'models/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$message = '';
if ($_POST) {
    $user->email = $_POST['email'];
    $user->password = $_POST['password'];
    $user->user_type = $_POST['user_type'];
    $user->first_name = $_POST['first_name'];
    $user->last_name = $_POST['last_name'];
    $user->company_name = $_POST['company_name'] ?? '';

    if ($user->emailExists()) {
        $message = "Email already exists!";
    } else {
        if ($user->register()) {
            header("Location: login.php?message=Registration successful");
            exit();
        } else {
            $message = "Registration failed!";
        }
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Register - Freelancing Platform</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-handshake"></i>
                FreelanceHub
            </a>
        </div>
    </nav>

    <div class="container">
        <div class="form-container">
            <div class="form-card">
                <div class="form-header">
                    <h3>Register</h3>
                </div>
                <div class="form-body">
                    <?php if ($message): ?>
                        <div class="alert alert-error"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" name="last_name" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label>User Type</label>
                            <select name="user_type" required>
                                <option value="client">Client</option>
                                <option value="freelancer">Freelancer</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Company Name (Optional)</label>
                            <input type="text" name="company_name">
                        </div>
                        <button type="submit" class="btn btn-primary btn-full">Register</button>
                    </form>
                    <div class="form-footer">
                        <a href="login.php">Already have an account? Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Register Page Specific Styles */
body {
    background: linear-gradient(135deg, var(--dark-bg) 0%, var(--dark-surface) 100%);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.navbar {
    background: rgba(15, 23, 42, 0.95);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--border);
}

.form-container {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem 0;
    min-height: calc(100vh - 80px);
}

.form-card {
    background: var(--dark-card);
    border-radius: 20px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
    overflow: hidden;
    max-width: 500px;
    width: 100%;
    border: 1px solid var(--border);
    position: relative;
    animation: slideUp 0.8s ease-out;
}

.form-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--primary), var(--secondary), var(--accent));
    background-size: 200% 100%;
    animation: gradientShift 3s ease infinite;
}

.form-header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: var(--light);
    padding: 3rem 2rem;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.form-header::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: float 6s ease-in-out infinite;
}

.form-header h3 {
    margin: 0;
    font-size: 2.2rem;
    font-weight: 700;
    position: relative;
    z-index: 1;
}

.form-body {
    padding: 3rem 2.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
    position: relative;
}

.form-group label {
    display: block;
    margin-bottom: 0.75rem;
    font-weight: 600;
    color: var(--light);
    font-size: 0.95rem;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 1rem 1.2rem;
    border: 2px solid var(--border);
    border-radius: 12px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: var(--dark-surface);
    color: var(--light);
    font-family: inherit;
}

.form-group input::placeholder,
.form-group select::placeholder {
    color: var(--gray);
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    background: var(--dark-bg);
    transform: translateY(-1px);
}

.form-group input:invalid:not(:focus):not(:placeholder-shown),
.form-group select:invalid:not(:focus) {
    border-color: #ef4444;
    background: rgba(239, 68, 68, 0.05);
}

.form-group input:valid:not(:focus):not(:placeholder-shown),
.form-group select:valid:not(:focus) {
    border-color: var(--primary);
    background: rgba(16, 185, 129, 0.05);
}

.form-group select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-size: 1.2rem;
    cursor: pointer;
}

/* User type specific styling */
.form-group select[name="user_type"] {
    background: var(--dark-surface);
    border: 2px solid var(--border);
    font-weight: 500;
}

.form-group select[name="user_type"]:focus {
    border-color: var(--primary);
    background: var(--dark-bg);
}

/* Company name field styling */
.form-group input[name="company_name"] {
    background: var(--dark-surface);
    border: 2px solid var(--border);
}

.form-group input[name="company_name"]:focus {
    border-color: var(--primary);
    background: var(--dark-bg);
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
    margin-top: 1rem;
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

.btn-full:active {
    transform: translateY(0);
}

.form-footer {
    text-align: center;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid var(--border);
}

.form-footer a {
    color: var(--primary-light);
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    position: relative;
}

.form-footer a::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 2px;
    background: var(--primary);
    transition: width 0.3s ease;
}

.form-footer a:hover {
    color: var(--light);
}

.form-footer a:hover::after {
    width: 100%;
}

.alert {
    padding: 1.2rem 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    border: 1px solid;
    font-weight: 500;
    animation: slideInDown 0.5s ease-out;
}

.alert-error {
    background: rgba(239, 68, 68, 0.1);
    color: #fca5a5;
    border-color: rgba(239, 68, 68, 0.3);
}

.alert-success {
    background: rgba(16, 185, 129, 0.1);
    color: var(--primary-light);
    border-color: rgba(16, 185, 129, 0.3);
}

/* Animations */
@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

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

@keyframes gradientShift {
    0% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
    100% {
        background-position: 0% 50%;
    }
}

@keyframes float {
    0%, 100% {
        transform: translateY(0px) rotate(0deg);
    }
    50% {
        transform: translateY(-20px) rotate(180deg);
    }
}

/* Password strength indicator (if added later) */
.password-strength {
    margin-top: 0.5rem;
    height: 4px;
    border-radius: 2px;
    background: var(--border);
    overflow: hidden;
}

.strength-bar {
    height: 100%;
    width: 0%;
    transition: all 0.3s ease;
    border-radius: 2px;
}

.strength-weak { background: #ef4444; width: 33%; }
.strength-medium { background: #f59e0b; width: 66%; }
.strength-strong { background: var(--primary); width: 100%; }

/* Loading state */
.btn-full:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none !important;
}

.btn-full:disabled:hover {
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
}

/* Focus states for accessibility */
.form-group input:focus-visible,
.form-group select:focus-visible {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
}

.btn-full:focus-visible {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
}

.form-footer a:focus-visible {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
    border-radius: 4px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .form-container {
        padding: 1rem;
    }

    .form-card {
        max-width: 100%;
        margin: 0 1rem;
    }

    .form-body {
        padding: 2rem 1.5rem;
    }

    .form-header {
        padding: 2.5rem 1.5rem;
    }

    .form-header h3 {
        font-size: 1.8rem;
    }
}

@media (max-width: 480px) {
    .form-body {
        padding: 1.5rem 1rem;
    }

    .form-header {
        padding: 2rem 1rem;
    }

    .form-group input,
    .form-group select {
        padding: 0.9rem 1rem;
    }

    .btn-full {
        padding: 1rem;
        font-size: 1rem;
    }

    .form-header h3 {
        font-size: 1.6rem;
    }
}

/* Enhanced navbar for register page */
.navbar .container {
    display: flex;
    justify-content: center;
    padding: 1rem 20px;
}

.navbar-brand {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--primary-light);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transition: all 0.3s ease;
}

.navbar-brand:hover {
    color: var(--light);
    transform: scale(1.05);
}

.navbar-brand i {
    font-size: 2rem;
}

/* Decorative elements */
.form-card::after {
    content: '';
    position: absolute;
    bottom: -100px;
    right: -100px;
    width: 200px;
    height: 200px;
    background: radial-gradient(circle, var(--primary) 0%, transparent 70%);
    opacity: 0.1;
    border-radius: 50%;
    animation: pulse 4s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
        opacity: 0.1;
    }
    50% {
        transform: scale(1.1);
        opacity: 0.15;
    }
}

/* Form validation icons */
.form-group input:valid + .validation-icon::after {
    content: '✓';
    color: var(--primary);
}

.form-group input:invalid:not(:focus):not(:placeholder-shown) + .validation-icon::after {
    content: '✗';
    color: #ef4444;
}

.validation-icon::after {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(50%);
    font-weight: bold;
}

/* User type selection styling */
.user-type-info {
    background: rgba(30, 41, 59, 0.5);
    padding: 1rem;
    border-radius: 8px;
    margin-top: 0.5rem;
    border-left: 3px solid var(--primary);
}

.user-type-info p {
    margin: 0;
    color: var(--gray);
    font-size: 0.9rem;
    line-height: 1.4;
}

/* Optional field styling */
.form-group input[optional] {
    border-style: dashed;
}

.form-group label[for="company_name"]::after {
    content: " (Optional)";
    color: var(--gray);
    font-weight: normal;
    font-size: 0.85rem;
}
    </style>
</body>

</html>