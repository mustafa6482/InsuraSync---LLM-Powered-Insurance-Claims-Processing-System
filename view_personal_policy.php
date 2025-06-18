<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}

// Get the user ID from the session
$user_id = $_SESSION['user_id'];

// Database credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "insurasync";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the active policy ID
$policyID_sql = "SELECT active_policy_id FROM users WHERE user_id = ?";
$stmt = $conn->prepare($policyID_sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$policyID_result = $stmt->get_result();

// Fetch the policy ID
if ($policyID_row = $policyID_result->fetch_assoc()) {
    $policyID = $policyID_row['active_policy_id'];
} else {
    die("No active policy found for this user.");
}

// Fetch policy details
$sql = "SELECT * FROM policies WHERE policyID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $policyID);
$stmt->execute();
$policy_result = $stmt->get_result();

if ($policy_result->num_rows > 0) {
    $policy = $policy_result->fetch_assoc();
} else {
    die("Policy not found.");
}

// Close the database connection
$stmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Policy Details</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* General Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('bg.jpg');
            background-size: cover;
            background-repeat: no-repeat;
            background-attachment: fixed;
            filter: brightness(50%);
            z-index: -1;
        }

        /* Header */
        header {
            /* background-color: rgba(0, 0, 0, 0.8); */
            background-color: #000;
            color: white;
            padding: 20px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h1 {
            margin: 0;
            font-size: 24px;
        }

        nav {
            display: flex;
            gap: 15px;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-size: 15px;
        }

        nav a:hover {
            color: #a1bdc5;
        }

        /* Profile and Search Icons */
        .search-profile {
            display: flex;
            align-items: center;
            position: relative;
        }

        .icon {
            font-size: 24px;
            margin-left: 15px;
            cursor: pointer;
        }

        .dropdown {
            display: none;
            position: absolute;
            top: 35px;
            right: 0;
            background: #fff;
            color: #333;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 150px;
            z-index: 10;
        }

        .dropdown div {
            padding: 10px 15px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .dropdown div:hover {
            background: #f1f1f1;
        }

        .profile-icon:hover + .dropdown,
        .dropdown:hover {
            display: block;
        }

        /* Container for Policy Details */
        .policy-container {
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 50px auto;
        }

        .policy-container h2 {
            margin-bottom: 20px;
        }

        .policy-details p {
            font-size: 16px;
            margin: 5px 0;
        }

        .policy-details label {
            font-weight: bold;
            color: #4CAF50;
        }

        /* Footer */
        footer {
            color: white;
            text-align: center;
            padding: 10px 0;
            position: fixed;
            bottom: 0;
            width: 100%;
            background-color: rgba(0, 0, 0, 0.8);
        }
    </style>
</head>
<body>

<header>
    <h1>InsuraSync</h1>
    <nav>
        <a href="user_dashboard.php">Home</a>
        <a href="#">About Us</a>
        <a href="#">Contact Us</a>
    </nav>
    <div class="search-profile">
    <span class="icon">🔔</span>
    <span class="icon profile-icon">👤</span>
    <div class="dropdown">
        <div>
            <a href="profile.php" style="text-decoration: none; color: inherit;">My Profile</a>
        </div>
        <div>
        <a href="landing_page1.php" style="text-decoration: none; color: inherit;">Logout</a>    
        </div>    
    </div>
</div>

</header>
<div class="policy-container">
    <h2><?php echo htmlspecialchars($policy['policy_name']); ?></h2>
    <div class="policy-details">
        <p><?php echo htmlspecialchars($policy['description']); ?></p><br>
        <p><label>Coverage Type:</label> <?php echo htmlspecialchars($policy['coverage_type']); ?></p>
        <p><label>Coverage Amount:</label> $<?php echo htmlspecialchars($policy['coverage_amount']); ?></p>
        <p><label>Deductible:</label> $<?php echo htmlspecialchars($policy['deductible']); ?></p>
        <p><label>Premium:</label> $<?php echo htmlspecialchars($policy['premium']); ?></p>
        <p><label>Policy Term:</label> <?php echo htmlspecialchars($policy['policy_term']); ?> years</p>
        <p><label>Additional Features:</label> <?php echo htmlspecialchars($policy['additional_features']); ?></p>
        <p><label>Target Scenario:</label> <?php echo htmlspecialchars($policy['target_scenario']); ?></p>
    </div>
</div>

<footer>
    <p>&copy; 2024 InsuraSync. All rights reserved.</p>
</footer>

</body>
</html>
