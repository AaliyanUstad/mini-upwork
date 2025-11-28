<?php
include_once 'config/database.php';
include_once 'models/Review.php';
include_once 'models/Contract.php';
include_once 'includes/session.php';

if(!Session::isLoggedIn()){
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$review = new Review($db);
$contract = new Contract($db);

$user_id = Session::get('user_id');
$contract_id = $_GET['contract_id'] ?? '';

if(!$contract_id) {
    header("Location: my_contracts.php");
    exit();
}

$contract_data = $contract->getContractById($contract_id);

if(!$contract_data || $contract_data['contract_status'] != 'completed' ||
   ($contract_data['client_id'] != $user_id && $contract_data['freelancer_id'] != $user_id)) {
    header("Location: my_contracts.php");
    exit();
}

// Check if user has already reviewed this contract
if($review->hasReviewedContract($contract_id, $user_id)) {
    header("Location: my_contracts.php?message=error:You have already reviewed this contract.");
    exit();
}

$user_type = Session::getUserType();
$reviewee_id = $user_type == 'client' ? $contract_data['freelancer_id'] : $contract_data['client_id'];
$reviewee_name = $user_type == 'client' ? $contract_data['freelancer_name'] : $contract_data['client_name'];

$message = '';
if($_POST){
    $review->contract_id = $contract_id;
    $review->reviewer_id = $user_id;
    $review->reviewee_id = $reviewee_id;
    $review->rating = $_POST['rating'];
    $review->comment = $_POST['comment'];
    $review->type = $user_type == 'client' ? 'client_to_freelancer' : 'freelancer_to_client';

    if($review->create()){
        $message = "success:Review submitted successfully!";
    } else {
        $message = "error:Failed to submit review. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Submit Review - Freelancing Platform</title>
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
        <div class="form-container">
            <div class="form-card">
                <div class="form-header">
                    <h2>Submit Review</h2>
                    <p>Share your experience working on: <?php echo htmlspecialchars($contract_data['job_title']); ?></p>
                </div>
                <div class="form-body">
                    <?php if($message): 
                        list($type, $msg) = explode(':', $message);
                    ?>
                        <div class="alert alert-<?php echo $type; ?>"><?php echo $msg; ?></div>
                        
                        <?php if($type == 'success'): ?>
                            <div class="success-actions">
                                <a href="my_contracts.php" class="btn btn-primary">Back to Contracts</a>
                                <a href="profile.php" class="btn btn-outline">View Profile</a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if(!$message || $type == 'error'): ?>
                    <div class="review-info">
                        <div class="review-party">
                            <h3>You're reviewing:</h3>
                            <div class="party-card">
                                <div class="party-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="party-details">
                                    <h4><?php echo htmlspecialchars($reviewee_name); ?></h4>
                                    <p><?php echo $user_type == 'client' ? 'Freelancer' : 'Client'; ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="contract-details">
                            <h3>Project Details:</h3>
                            <div class="details-grid">
                                <div class="detail-item">
                                    <strong>Project:</strong>
                                    <span><?php echo htmlspecialchars($contract_data['job_title']); ?></span>
                                </div>
                                <div class="detail-item">
                                    <strong>Amount:</strong>
                                    <span>$<?php echo number_format($contract_data['contract_amount'], 2); ?></span>
                                </div>
                                <div class="detail-item">
                                    <strong>Completed:</strong>
                                    <span><?php echo date('M j, Y', strtotime($contract_data['end_date'])); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form method="POST">
                        <div class="form-group">
                            <label>Rating</label>
                            <div class="rating-input">
                                <div class="stars">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                        <label for="star<?php echo $i; ?>">
                                            <i class="fas fa-star"></i>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                                <div class="rating-labels">
                                    <span>Poor</span>
                                    <span>Excellent</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Your Review</label>
                            <textarea name="comment" rows="6" placeholder="Share your experience working with <?php echo htmlspecialchars($reviewee_name); ?>. What went well? Any areas for improvement?" required></textarea>
                            <small>Be honest and constructive. Your review helps others make better decisions.</small>
                        </div>
                        
                        <div class="review-tips">
                            <h4>Review Tips:</h4>
                            <ul>
                                <li>Be specific about what you liked or didn't like</li>
                                <li>Focus on the work quality and communication</li>
                                <li>Keep it professional and respectful</li>
                                <li>Mention if you would work together again</li>
                            </ul>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-full">
                            <i class="fas fa-star"></i>Submit Review
                        </button>
                    </form>
                    <?php endif; ?>
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

.review-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-bottom: 2.5rem;
    padding: 2rem;
    background: rgba(30, 41, 59, 0.8);
    border-radius: 12px;
    border: 1px solid var(--border);
}

.review-party h3, .contract-details h3 {
    margin: 0 0 1.5rem 0;
    color: var(--light);
    font-size: 1.2rem;
    font-weight: 600;
}

.party-card {
    display: flex;
    align-items: center;
    gap: 1.2rem;
    padding: 1.5rem;
    background: var(--dark-surface);
    border-radius: 12px;
    border: 1px solid var(--border);
    transition: all 0.3s ease;
}

.party-card:hover {
    border-color: var(--primary);
    transform: translateY(-2px);
}

.party-avatar {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--light);
    font-size: 1.5rem;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
}

.party-details h4 {
    margin: 0 0 0.5rem 0;
    color: var(--light);
    font-size: 1.1rem;
}

.party-details p {
    margin: 0;
    color: var(--gray);
    font-size: 0.9rem;
    background: rgba(16, 185, 129, 0.1);
    padding: 0.3rem 0.8rem;
    border-radius: 12px;
    border: 1px solid rgba(16, 185, 129, 0.3);
    display: inline-block;
}

.details-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: var(--dark-surface);
    border-radius: 8px;
    border: 1px solid var(--border);
    transition: all 0.3s ease;
}

.detail-item:hover {
    border-color: var(--primary);
}

.detail-item strong {
    color: var(--light);
    font-size: 0.95rem;
    font-weight: 600;
}

.detail-item span {
    color: var(--gray);
    font-size: 0.95rem;
}

.form-group {
    margin-bottom: 2.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 1rem;
    font-weight: 600;
    color: var(--light);
    font-size: 1.1rem;
}

.rating-input {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    align-items: center;
}

.stars {
    display: flex;
    gap: 0.75rem;
    justify-content: center;
}

.stars input {
    display: none;
}

.stars label {
    font-size: 2.5rem;
    color: #374151;
    cursor: pointer;
    transition: all 0.3s ease;
    margin: 0;
    position: relative;
}

.stars label:hover,
.stars input:checked ~ label {
    color: #fbbf24;
    transform: scale(1.1);
}

.stars label:hover ~ label {
    color: #fbbf24;
}

.rating-labels {
    display: flex;
    justify-content: space-between;
    width: 100%;
    max-width: 300px;
    color: var(--gray);
    font-size: 0.9rem;
    font-weight: 500;
}

.form-group textarea {
    width: 100%;
    padding: 1.2rem;
    border: 2px solid var(--border);
    border-radius: 12px;
    font-size: 1rem;
    resize: vertical;
    min-height: 140px;
    line-height: 1.6;
    transition: all 0.3s ease;
    background: var(--dark-surface);
    color: var(--light);
    font-family: inherit;
}

.form-group textarea::placeholder {
    color: var(--gray);
}

.form-group textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    background: var(--dark-bg);
}

.form-group small {
    color: var(--gray);
    font-size: 0.875rem;
    margin-top: 0.75rem;
    display: block;
    font-style: italic;
    line-height: 1.4;
}

.review-tips {
    background: rgba(30, 41, 59, 0.8);
    padding: 2rem;
    border-radius: 12px;
    margin-bottom: 2.5rem;
    border: 1px solid var(--border);
    border-left: 4px solid var(--primary);
}

.review-tips h4 {
    margin: 0 0 1.2rem 0;
    color: var(--light);
    font-size: 1.1rem;
    font-weight: 600;
}

.review-tips ul {
    margin: 0;
    padding-left: 1.5rem;
    color: var(--gray);
}

.review-tips li {
    margin-bottom: 0.75rem;
    line-height: 1.5;
    position: relative;
}

.review-tips li::before {
    content: '💡';
    position: absolute;
    left: -1.5rem;
    color: var(--primary);
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

.success-actions {
    text-align: center;
    padding: 2rem;
    display: flex;
    gap: 1.5rem;
    justify-content: center;
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

.form-card {
    animation: fadeInUp 0.6s ease-out;
}

/* Star rating animation */
@keyframes starPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); }
}

.stars input:checked + label {
    animation: starPulse 0.5s ease;
}

/* Responsive Design */
@media (max-width: 768px) {
    .review-info {
        grid-template-columns: 1fr;
        gap: 1.5rem;
        padding: 1.5rem;
    }

    .form-body {
        padding: 2rem 1.5rem;
    }

    .form-header {
        padding: 2rem 1.5rem;
    }

    .success-actions {
        flex-direction: column;
        gap: 1rem;
    }

    .stars {
        gap: 0.5rem;
    }

    .stars label {
        font-size: 2rem;
    }

    .form-header h2 {
        font-size: 1.7rem;
    }
}

@media (max-width: 480px) {
    .form-container {
        padding: 1rem 0;
        margin-top: 60px;
    }

    .form-body {
        padding: 1.5rem 1rem;
    }

    .form-header {
        padding: 1.5rem 1rem;
    }

    .party-card {
        padding: 1rem;
        flex-direction: column;
        text-align: center;
    }

    .detail-item {
        flex-direction: column;
        gap: 0.5rem;
        align-items: flex-start;
    }

    .stars label {
        font-size: 1.8rem;
    }

    .form-header h2 {
        font-size: 1.5rem;
    }
}

/* Focus states for accessibility */
.form-group textarea:focus-visible {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
}

.stars input:focus + label {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
    border-radius: 4px;
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

/* Enhanced star rating hover effects */
.stars:hover label {
    color: #fbbf24;
}

.stars label:hover ~ label {
    color: #374151;
}
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const stars = document.querySelectorAll('.stars input');
        stars.forEach(star => {
            star.addEventListener('change', function() {
                const rating = this.value;
            });
        });
    });
    </script>
</body>
</html>