<?php
include_once 'config/database.php';
include_once 'includes/session.php';

if(!Session::isLoggedIn() || Session::getUserType() != 'client'){
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

if(!isset($_GET['id'])) {
    header("Location: my_contracts.php");
    exit();
}

$contract_id = $_GET['id'];

// Verify the contract belongs to the client
$query = "SELECT c.* FROM contracts c WHERE c.id = ? AND c.client_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$contract_id, Session::get('user_id')]);
$contract = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$contract) {
    header("Location: my_contracts.php");
    exit();
}

// Update contract status to completed
$update_query = "UPDATE contracts SET contract_status = 'completed', end_date = NOW() WHERE id = ?";
$update_stmt = $db->prepare($update_query);

if($update_stmt->execute([$contract_id])) {
    // Also update the job status to completed
    $job_query = "UPDATE jobs SET status = 'completed', updated_at = NOW() WHERE id = ?";
    $job_stmt = $db->prepare($job_query);
    $job_stmt->execute([$contract['job_id']]);
    
    header("Location: my_contracts.php?message=success:Contract marked as completed!");
} else {
    header("Location: my_contracts.php?message=error:Failed to complete contract.");
}
exit();
?>