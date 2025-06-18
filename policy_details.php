<?php
session_start(); // Start the session

// Ensure policyID is provided
$policyID = isset($_GET['policyID']) ? $_GET['policyID'] : die("Policy ID not provided.");

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}
$user_id = $_SESSION['user_id'];

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "insurasync";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch policy details using policyID
$sql = "SELECT * FROM policies WHERE policyID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $policyID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
} else {
    die("Policy not found.");
}

// Fetch user profile
$sql_user = "SELECT cnic, gender, age, monthly_income, area, city FROM users WHERE user_id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_result = $stmt_user->get_result();

$is_profile_complete = true;
$incomplete_fields = [];

if ($user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
    $fields = ['cnic', 'gender', 'age', 'monthly_income', 'area', 'city'];
    foreach ($fields as $field) {
        if (empty($user[$field])) {
            $is_profile_complete = false;
            $incomplete_fields[] = $field;
        }
    }
} else {
    die("User not found.");
}

$stmt->close();
$stmt_user->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Policy Details</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
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
        header {
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
        .policy-container {
            max-width: 500px;
            margin: 20px auto;
            padding: 30px;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
            flex-grow: 1;
        }
        .policy-container h1 { color: #333; }
        .policy-details p { font-size: 18px; color: #555; }
        .policy-description { font-size: 18px; color: #555; line-height: 1.5; }
        .purchase-btn-container { margin-top: 30px; }
        .purchase-btn {
            padding: 15px 30px;
            background-color: #000;
            color: white;
            font-size: 18px;
            text-align: center;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        .purchase-btn:hover { background-color: #a1bdc5; }
        footer {
            background-color: #000;
            color: white;
            text-align: center;
            padding: 10px 0;
            position: sticky;
            bottom: 0;
            width: 100%;
            margin-top: auto;
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
            text-align: left;
            overflow: hidden;
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
    </style>
</head>
<body>
<header>
    <h1>InsuraSync</h1>
    <nav>
        <a href="user_dashboard.php">Home</a>
        <a href="about.php">About Us</a>
        <a href="contact.php">Contact Us</a>
    </nav>
    <div class="search-profile">
        <span class="icon">🔔</span>
        <span class="icon profile-icon">👤</span>
        <div class="dropdown">
            <div><a href="profile.php" style="text-decoration: none; color: inherit;">My Profile</a></div>
            <div><a href="landing_page1.php" style="text-decoration: none; color: inherit;">Logout</a></div>
        </div>
    </div>
</header>

<div class="policy-container">
    <h1><?php echo $row["policy_name"]; ?></h1>
    <div class="policy-details">
        <p><strong>Coverage Type:</strong> <?php echo $row["coverage_type"]; ?></p>
        <p><strong>Coverage Amount:</strong> $<?php echo $row["coverage_amount"]; ?></p>
        <p><strong>Deductible:</strong> $<?php echo $row["deductible"]; ?></p>
        <p><strong>Premium:</strong> $<?php echo $row["premium"]; ?></p>
        <p><strong>Policy Term:</strong> <?php echo $row["policy_term"]; ?> years</p>
        <p><strong>Additional Features:</strong> <?php echo $row["additional_features"]; ?></p>
        <p><strong>Target Scenario:</strong> <?php echo $row["target_scenario"]; ?></p>
    </div>
    <div class="policy-description">
        <strong>Description:</strong>
        <p><?php echo $row["description"]; ?></p>
    </div>
    <div class="purchase-btn-container">
        <?php if ($is_profile_complete): ?>
            <a href="purchase_policy.php?policyID=<?php echo $policyID; ?>" class="purchase-btn">Purchase Policy</a>
        <?php else: ?>
            <p class="incomplete-message">To proceed with registering this policy, please ensure that your profile is complete.</p>
        <?php endif; ?>
    </div>
</div>
<footer>
    <p>&copy; 2024 InsuraSync Company. All rights reserved.</p>
</footer>
</body>
</html>
