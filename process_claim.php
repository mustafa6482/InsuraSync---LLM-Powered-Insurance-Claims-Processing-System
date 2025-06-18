<?php
session_start();

// Check if staff is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Check if claim ID is provided in URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: view_claims_staff.php");
    exit();
}

$claim_id = $_GET['id'];

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "insurasync";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch claim details
$sql = "SELECT * FROM claims WHERE claim_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $claim_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Claim not found
    header("Location: view_claims_staff.php");
    exit();
}

$claim = $result->fetch_assoc();

// Fetch user data
$user_sql = "SELECT * FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $claim['user_id']);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

// Fetch policy data
$policy_sql = "SELECT * FROM policies WHERE policyID = ?";
$policy_stmt = $conn->prepare($policy_sql);
$policy_stmt->bind_param("i", $claim['policy_id']);
$policy_stmt->execute();
$policy_result = $policy_stmt->get_result();
$policy = $policy_result->fetch_assoc();

// Count previous claims by this user
$count_claims_sql = "SELECT COUNT(*) as claim_count FROM claims WHERE user_id = ?";
$count_claims_stmt = $conn->prepare($count_claims_sql);
$count_claims_stmt->bind_param("i", $claim['user_id']);
$count_claims_stmt->execute();
$count_result = $count_claims_stmt->get_result();
$claim_count_data = $count_result->fetch_assoc();
$previous_claims_count = $claim_count_data['claim_count'];

// Calculate fraud score
$fraud_score = 0;
$fraud_factors = [];

// Check CNIC discrepancy
$cnic_discrepancy = false;
if (isset($user['cnic']) && isset($claim['cnic']) && $user['cnic'] != $claim['cnic']) {
    $cnic_discrepancy = true;
    $fraud_score += 35;
    $fraud_factors[] = "CNIC mismatch";
}

// Check vehicle registration number discrepancy
$vehicle_reg_discrepancy = false;
if (isset($user['vehicle_reg']) && isset($claim['vehicle_reg_num']) && $user['vehicle_reg'] != $claim['vehicle_reg_num']) {
    $vehicle_reg_discrepancy = true;
    $fraud_score += 35;
    $fraud_factors[] = "Vehicle registration number mismatch";
}

// Check claim frequency
if ($previous_claims_count > 2) {
    $fraud_score += min(30, ($previous_claims_count - 2) * 10);
    $fraud_factors[] = "High claim frequency";
}

// Process form submission
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $staff_notes = isset($_POST['staff_notes']) ? $_POST['staff_notes'] : '';
        $settlement_amount = isset($_POST['settlement_amount']) ? $_POST['settlement_amount'] : 0;
        
        // Update claim status based on action
        if ($action == 'approve') {
            $status = 'approved';
            // Use your actual schema columns here
            $update_sql = "UPDATE claims SET claim_status = ?, claim_amount = ?, staff_notes = ? WHERE claim_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("sdsi", $status, $settlement_amount, $staff_notes, $claim_id);
        } else if ($action == 'reject') {
            $status = 'rejected';
            $update_sql = "UPDATE claims SET claim_status = ?, staff_notes = ? WHERE claim_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssi", $status, $staff_notes, $claim_id);
        } else if ($action == 'pending') {
            $status = 'pending';
            $update_sql = "UPDATE claims SET claim_status = ?, staff_notes = ? WHERE claim_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssi", $status, $staff_notes, $claim_id);
        }
        
        if ($update_stmt->execute()) {
            $message = "Claim has been " . ucfirst($status) . " successfully!";
            
            // Refresh claim data
            $stmt->execute();
            $result = $stmt->get_result();
            $claim = $result->fetch_assoc();
        } else {
            $message = "Error updating claim: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Claim - Staff</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-image: url('bg.jpg');
            background-size: cover;
            background-repeat: no-repeat;
            background-attachment: fixed;
            color: #ddd;
            display: flex;
        }

        .sidebar {
            width: 250px;
            background-color: #1a1a2e;
            height: 100vh;
            padding: 20px;
            box-sizing: border-box;
            position: fixed;
            top: 0;
            left: 0;
        }

        .sidebar h2 {
            color: #60a5fa;
            text-align: center;
        }

        .sidebar a {
            display: block;
            padding: 15px;
            color: white;
            text-decoration: none;
            font-size: 16px;
            border-radius: 5px;
            transition: background-color 0.3s;
            margin-bottom: 10px;
        }

        .sidebar a:hover {
            background-color: #0056b3;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
            box-sizing: border-box;
        }

        header {
            background-color: black;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 25px;
            background-color: rgba(25, 25, 50, 0.95);
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            width: 100%;
            box-sizing: border-box;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            background-color: #28a745;
            color: white;
        }

        .claim-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .claim-section {
            margin-bottom: 20px;
        }

        .claim-section h3 {
            border-bottom: 1px solid #444;
            padding-bottom: 10px;
            color: #60a5fa;
        }

        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }

        .detail-label {
            font-weight: bold;
            width: 150px;
            color: #aaa;
            padding-right: 5px;
        }

        .detail-value {
            flex: 1;
        }

        .fraud-section {
            margin: 20px 0;
            padding: 15px;
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            border-left: 4px solid #e63946;
        }

        .fraud-section h3 {
            color: #e63946;
            margin-top: 0;
        }

        .fraud-score {
            font-size: 24px;
            font-weight: bold;
            margin: 15px 0;
            color: #fff;
        }

        .fraud-score-number {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 50%;
            background-color: #e63946;
            margin-right: 10px;
        }

        .fraud-factor {
            margin-bottom: 10px;
            padding: 8px;
            background-color: rgba(230, 57, 70, 0.2);
            border-radius: 4px;
        }

        .fraud-factor i {
            margin-right: 8px;
            color: #e63946;
        }

        .fraud-level-low {
            color: #2ecc71;
        }

        .fraud-level-medium {
            color: #f39c12;
        }

        .fraud-level-high {
            color: #e74c3c;
        }

        .documents-section {
            margin-top: 30px;
        }

        .document-item {
            padding: 10px;
            margin-bottom: 5px;
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .document-item a {
            color: #60a5fa;
            text-decoration: none;
        }

        .document-item a:hover {
            text-decoration: underline;
        }

        .action-form {
            margin-top: 30px;
            padding: 20px;
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            max-width: 100%;

        }

        .action-form .form-control {
            box-sizing: border-box;
            max-width: 100%;
            width: 100%;
        }

        .form-group {
            margin-bottom: 20px;
            max-width: 100%;

        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #aaa;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            background-color: #222;
            border: 1px solid #444;
            border-radius: 4px;
            color: #ddd;
        }

        textarea.form-control {
            height: 100px;
            resize: vertical;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        @media (max-width: 768px) {
    .btn {
        flex: 1 0 auto;
        min-width: 120px;
    }
}

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-weight: bold;
        }

        .btn-approve {
            background-color: #28a745;
            color: white;
        }

        .btn-ai {
            background-color: black;
            color: white;
        }

        .btn-reject {
            background-color: #dc3545;
            color: white;
        }

        .btn-pending {
            background-color: #ffc107;
            color: black;
        }

        .btn:hover {
            opacity: 0.9;
        }

        footer {
            background-color: #1a1a2e;
            color: white;
            text-align: center;
            padding: 10px 0;
            position: fixed;
            bottom: 0;
            left: 250px;
            width: calc(100% - 250px);
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Staff Dashboard</h2>
        <a href="view_claims_staff.php">View Pending Claims</a>
        <a href="completed_claims.php">View Completed Claims</a>
        <a href="staff_login.php">Logout</a>
    </div>

    <div class="main-content">
        <header>
            <h1>InsuraSync - Technical Staff Dashboard</h1>
        </header>

        <div class="container">
            <?php if (!empty($message)): ?>
                <div class="alert">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <h2>Process Claim #<?= htmlspecialchars($claim_id) ?></h2>
            
            <div class="claim-details">
                <div class="claim-section">
                    <h3>Claim Information</h3>
                    <div class="detail-row">
                        <div class="detail-label">Status:</div>
                        <div class="detail-value"><?= htmlspecialchars($claim['claim_status']) ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Filed Date:</div>
                        <div class="detail-value"><?= htmlspecialchars($claim['filing_date']) ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Accident Date:</div>
                        <div class="detail-value"><?= htmlspecialchars($claim['accident_date']) ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Description:</div>
                        <div class="detail-value"><?= htmlspecialchars($claim['accident_description']) ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Entered cnic:</div>
                        <div class="detail-value"><?= htmlspecialchars($claim['cnic']) ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Entered vehicle registration number:</div>
                        <div class="detail-value"><?= htmlspecialchars($claim['vehicle_reg_num']) ?></div>
                    </div>

                    <?php if (!empty($claim['staff_notes'])): ?>
                    <div class="detail-row">
                        <div class="detail-label">Staff Notes:</div>
                        <div class="detail-value"><?= nl2br(htmlspecialchars($claim['staff_notes'])) ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($claim['damaged_parts'])): ?>
                    <div class="detail-row">
                        <div class="detail-label">Damaged Parts:</div>
                        <div class="detail-value"><?= htmlspecialchars($claim['damaged_parts']) ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($claim['claim_amount'])): ?>
                    <div class="detail-row">
                        <div class="detail-label">Claim Amount:</div>
                        <div class="detail-value">$<?= htmlspecialchars(number_format($claim['claim_amount'], 2)) ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Fraud Analysis Section -->
                    <div class="fraud-section">
                        <h3><i class="fas fa-shield-alt"></i> Fraud Risk Analysis</h3>
                        
                        <div class="fraud-score">
                            <span class="fraud-score-number"><?= $fraud_score ?></span>
                            <?php
                            if ($fraud_score < 30) {
                                echo '<span class="fraud-level-low">Low Risk</span>';
                            } elseif ($fraud_score < 70) {
                                echo '<span class="fraud-level-medium">Medium Risk</span>';
                            } else {
                                echo '<span class="fraud-level-high">High Risk</span>';
                            }
                            ?>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Previous Claims:</div>
                            <div class="detail-value"><?= $previous_claims_count ?></div>
                        </div>
                        
                        <?php if ($cnic_discrepancy): ?>
                        <div class="fraud-factor">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <strong>CNIC Mismatch</strong>: The CNIC provided (<?= htmlspecialchars($claim['cnic']) ?>) 
                            doesn't match the user's registered CNIC (<?= htmlspecialchars($user['cnic']) ?>)
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($vehicle_reg_discrepancy): ?>
                        <div class="fraud-factor">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <strong>Vehicle Registration Mismatch</strong>: The vehicle registration number provided 
                            (<?= htmlspecialchars($claim['vehicle_reg_num']) ?>) doesn't match the user's registered vehicle 
                            (<?= htmlspecialchars($user['vehicle_reg']) ?>)
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($previous_claims_count > 3): ?>
                        <div class="fraud-factor">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <strong>High Claim Frequency</strong>: This user has submitted <?= $previous_claims_count ?> claims
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="claim-section">
                    <h3>Policy & Customer Information</h3>
                    <?php if (isset($policy) && $policy): ?>
                        <div class="detail-row">
                            <div class="detail-label">Policy ID:</div>
                            <div class="detail-value"><?= htmlspecialchars($claim['policy_id']) ?></div>
                        </div>
                        <?php foreach($policy as $key => $value): ?>
                            <?php if($key != 'policyID' && !empty($value)): ?>
                            <div class="detail-row">
                                <div class="detail-label"><?= ucfirst(str_replace('_', ' ', $key)); ?>:</div>
                                <div class="detail-value">
                                    <?php if(is_numeric($value) && strpos($key, 'amount') !== false): ?>
                                        $<?= htmlspecialchars(number_format($value, 2)) ?>
                                    <?php else: ?>
                                        <?= htmlspecialchars($value) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No policy information available.</p>
                    <?php endif; ?>
                    
                    <?php if (isset($user) && $user): ?>
                        <div class="detail-row">
                            <div class="detail-label">User ID:</div>
                            <div class="detail-value"><?= htmlspecialchars($claim['user_id']) ?></div>
                        </div>
                        <?php foreach($user as $key => $value): ?>
                            <?php if($key != 'user_id' && $key != 'password' && !empty($value)): ?>
                            <div class="detail-row">
                                <div class="detail-label"><?= ucfirst(str_replace('_', ' ', $key)); ?>:</div>
                                <div class="detail-value"><?= htmlspecialchars($value) ?></div>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No customer information available.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($claim['documents'])): ?>
            <div class="documents-section">
                <h3>Submitted Documents</h3>
                <div class="document-item">
                    <span>Claim Documents</span>
                    <?php if (file_exists('uploads/' . $claim['documents'])): ?>
                    <a href="view_document.php?file=<?= urlencode($claim['documents']) ?>" target="_blank">
                        <i class="fas fa-file-alt"></i> View Document
                    </a>
                    <?php else: ?>
                    <span>File not found</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="action-form">
                <h3>Process This Claim</h3>
                <form method="post" action="">
                    <?php if ($claim['claim_status'] != 'approved'): ?>
                    <div class="form-group">
                        <label for="settlement_amount">Settlement Amount (if approving):</label>
                        <input type="number" name="settlement_amount" id="settlement_amount" class="form-control" step="0.01" min="0" 
                            value="<?= isset($claim['claim_amount']) ? $claim['claim_amount'] : '' ?>">
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="staff_notes">Staff Notes:</label>
                        <textarea name="staff_notes" id="staff_notes" class="form-control"><?= isset($claim['staff_notes']) ? htmlspecialchars($claim['staff_notes']) : '' ?></textarea>
                    </div>
                    
                    <div class="btn-group">
                        <a href="ai_insights.php?claim_id=<?= $claim_id ?>" class="btn btn-ai">Get AI Insights</a>
                        <!-- <button type="submit" name="action" value="approve" class="btn btn-ai">Get AI Insights</button> -->
                        <?php if ($claim['claim_status'] != 'approved'): ?>
                            <button type="submit" name="action" value="approve" class="btn btn-approve">Approve Claim</button>
                        <?php endif; ?>
                        
                        <?php if ($claim['claim_status'] != 'rejected'): ?>
                            <button type="submit" name="action" value="reject" class="btn btn-reject">Reject Claim</button>
                        <?php endif; ?>
                        
                        <?php if ($claim['claim_status'] != 'pending'): ?>
                            <button type="submit" name="action" value="pending" class="btn btn-pending">Mark as Pending</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 InsuraSync. All rights reserved.</p>
    </footer>
</body>
</html>

<?php
$stmt->close();
if (isset($user_stmt)) $user_stmt->close();
if (isset($policy_stmt)) $policy_stmt->close();
if (isset($count_claims_stmt)) $count_claims_stmt->close();
$conn->close();
?>