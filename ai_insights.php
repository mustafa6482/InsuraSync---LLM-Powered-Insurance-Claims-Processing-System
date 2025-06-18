<?php
// Database connection and processing code - Move to the top
$host = "localhost";
$username = "root";  // Change as needed
$password = "";  // Change as needed
$database = "insurasync";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables to prevent undefined warnings
$claim_id = 0;
$user_id = 0;
$policy_id = 0;
$vehicle_info = "";
$response = null;
$damaged_parts_raw = "";
$formatted_parts_display = "";
$accident_description = "";
$policy_description = "";
$coverage_analysis = null;

// Retrieve claim_id from URL
if (isset($_GET['claim_id'])) {
    $claim_id = intval($_GET['claim_id']); // Sanitize input

    // Retrieve user_id and accident_description from claims table
    $query = "SELECT user_id, accident_description, damaged_parts FROM claims WHERE claim_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $claim_id);
    $stmt->execute();
    $stmt->bind_result($user_id, $accident_description, $damaged_parts_raw);

    if (!$stmt->fetch()) {
        die("Invalid claim ID or no associated user.");
    }
    $stmt->close();
} else {
    die("No claim ID provided.");
}

// Retrieve policy_id from users table
$query_policy = "SELECT active_policy_id, vehicle_company, vehicle_name, vehicle_model_year FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query_policy);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_policy = $stmt->get_result();
$user_data = $result_policy->fetch_assoc();

if (!$user_data) {
    die("User data not found.");
}

$policy_id = $user_data["active_policy_id"];
$vehicle_info = $user_data["vehicle_company"] . " " . $user_data["vehicle_name"] . " " . $user_data["vehicle_model_year"];

// Retrieve policy description from policies table
$query_policy_desc = "SELECT description FROM policies WHERE policyID = ?";
$stmt = $conn->prepare($query_policy_desc);
$stmt->bind_param("i", $policy_id);
$stmt->execute();
$stmt->bind_result($policy_description);

if (!$stmt->fetch()) {
    die("Policy description not found.");
}
$stmt->close();

// Process damaged parts
$damaged_parts = explode(",", $damaged_parts_raw);

// Format damaged parts with vehicle info
$formatted_parts = array_map(fn($part) => trim($part) . " " . $vehicle_info, $damaged_parts);
$formatted_parts_display = implode(", ", $formatted_parts);

// 1. Send request to Flask API for cost analysis
$data_cost = json_encode(["damaged_parts" => $formatted_parts]);
$flask_cost_url = "http://127.0.0.1:5000/get_parts_cost";  // Adjust if Flask runs elsewhere
$ch_cost = curl_init($flask_cost_url);
curl_setopt($ch_cost, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_cost, CURLOPT_POST, true);
curl_setopt($ch_cost, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch_cost, CURLOPT_POSTFIELDS, $data_cost);
$response = curl_exec($ch_cost);
curl_close($ch_cost);

// 2. Send request to Flask API for coverage analysis
$data_coverage = json_encode([
    "accident_description" => $accident_description,
    "policy_text" => $policy_description
]);
$flask_coverage_url = "http://127.0.0.1:5001/check_claim";  // Adjust if Flask runs elsewhere
$ch_coverage = curl_init($flask_coverage_url);
curl_setopt($ch_coverage, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_coverage, CURLOPT_POST, true);
curl_setopt($ch_coverage, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
curl_setopt($ch_coverage, CURLOPT_POSTFIELDS, $data_coverage);
$coverage_response = curl_exec($ch_coverage);
curl_close($ch_coverage);

if ($coverage_response) {
    $coverage_data = json_decode($coverage_response, true);
    $coverage_analysis = $coverage_data["result"] ?? "Unable to determine coverage";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Insights - Staff</title>
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

        .insight-section {
            margin-bottom: 30px;
        }

        .insight-section h3 {
            border-bottom: 1px solid #444;
            padding-bottom: 10px;
            color: #60a5fa;
        }

        .parts-list {
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .parts-list ul {
            list-style-type: none;
            padding-left: 0;
        }

        .parts-list li {
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .parts-list li:last-child {
            border-bottom: none;
        }

        .cost-summary {
            background-color: rgba(96, 165, 250, 0.1);
            border-left: 4px solid #60a5fa;
            padding: 15px;
            border-radius: 0 4px 4px 0;
            margin-top: 20px;
        }

        .missing-prices {
            background-color: rgba(255, 193, 7, 0.1);
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 0 4px 4px 0;
            margin-top: 20px;
        }

        .vehicle-info {
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .coverage-info {
            padding: 15px;
            border-radius: 0 4px 4px 0;
            margin-top: 20px;
        }

        .coverage-covered {
            background-color: rgba(40, 167, 69, 0.1);
            border-left: 4px solid #28a745;
        }

        .coverage-not-covered {
            background-color: rgba(220, 53, 69, 0.1);
            border-left: 4px solid #dc3545;
        }

        .coverage-unclear {
            background-color: rgba(255, 193, 7, 0.1);
            border-left: 4px solid #ffc107;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-weight: bold;
            display: inline-block;
            text-decoration: none;
            margin-top: 20px;
        }

        .btn-primary {
            background-color: #0d6efd;
            color: white;
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

        .debug-info {
            background-color: rgba(100, 100, 100, 0.2);
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }

        .detail-label {
            font-weight: bold;
            width: 150px;
            color: #aaa;
        }

        .detail-value {
            flex: 1;
        }

        .code-block {
            background-color: rgba(0, 0, 0, 0.3);
            border-radius: 4px;
            padding: 15px;
            font-family: monospace;
            white-space: pre-wrap;
            max-height: 200px;
            overflow-y: auto;
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
            <h2>AI Insights for Claim #<?php echo $claim_id; ?></h2>
            
            <div class="insight-section">
                <h3>Claim Information</h3>
                <div class="debug-info">
                    <div class="detail-row">
                        <div class="detail-label">Claim ID:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($claim_id); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">User ID:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($user_id); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Policy ID:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($policy_id); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="insight-section">
                <h3>Vehicle Information</h3>
                <div class="vehicle-info">
                    <div class="detail-row">
                        <div class="detail-label">Vehicle:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($vehicle_info); ?></div>
                    </div>
                </div>
            </div>

            <div class="insight-section">
                <h3>Accident Description</h3>
                <div class="vehicle-info">
                    <div class="code-block"><?php echo htmlspecialchars($accident_description); ?></div>
                </div>
            </div>

            <div class="insight-section">
                <h3>Policy Description</h3>
                <div class="vehicle-info">
                    <div class="code-block"><?php echo htmlspecialchars($policy_description); ?></div>
                </div>
            </div>
            
            <div class="insight-section">
                <h3>Coverage Analysis</h3>
                <?php 
                    $coverage_class = 'coverage-unclear';
                    if ($coverage_analysis) {
                        if (stripos($coverage_analysis, '**Covered**') !== false) {
                            $coverage_class = 'coverage-covered';
                        } elseif (stripos($coverage_analysis, '**Not Covered**') !== false) {
                            $coverage_class = 'coverage-not-covered';
                        }
                    }
                ?>
                <div class="coverage-info <?php echo $coverage_class; ?>">
                    <?php echo nl2br(htmlspecialchars($coverage_analysis ?? 'Coverage analysis not available')); ?>
                </div>
            </div>

            <div class="insight-section">
                <h3>Damaged Parts</h3>
                <div class="vehicle-info">
                    <div class="detail-row">
                        <div class="detail-label">Raw Parts:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($damaged_parts_raw); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Formatted Parts:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($formatted_parts_display); ?></div>
                    </div>
                </div>
            </div>

            <div class="insight-section">
                <h3>Parts Cost Analysis</h3>
                
                <?php if (isset($response) && $response): ?>
                    <?php $response_data = json_decode($response, true); ?>
                    
                    <div class="parts-list">
                        <h4>Claimed Damaged Parts and Prices:</h4>
                        <ul>
                            <?php foreach ($response_data["claimed_parts"] as $part): ?>
                                <li><?php echo htmlspecialchars($part); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <div class="cost-summary">
                            <h4>Total Repair Cost: <?php echo htmlspecialchars($response_data["total_cost"]); ?></h4>
                        </div>
                        
                        <?php if (!empty($response_data["missing_prices"])): ?>
                            <div class="missing-prices">
                                <h4>Parts found without listed prices:</h4>
                                <ul>
                                    <?php foreach ($response_data["missing_prices"] as $missing): ?>
                                        <li><?php echo htmlspecialchars($missing); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="alert" style="background-color: #dc3545;">
                        Failed to retrieve data from the Flask API.
                    </div>
                <?php endif; ?>
            </div>
            
            <a href="process_claim.php?claim_id=<?php echo $claim_id; ?>" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Claim
            </a>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 InsuraSync. All rights reserved.</p>
    </footer>
</body>
</html>

<?php
$conn->close();
?>