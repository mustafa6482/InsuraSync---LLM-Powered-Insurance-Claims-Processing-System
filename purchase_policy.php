<?php
// Enable error reporting to debug any issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isset($_GET['policyID'])) {
    $policyID = $_GET['policyID'];
    // echo "Debug: policyID received from URL: " . htmlspecialchars($policyID) . "<br>";
} else {
    die("Policy ID not provided.");
}


// Step 2: Get user_id from session (assuming it's already set)
session_start();
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    die("User not logged in.");
}

// Step 3: Connect to the database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "insurasync"; // Your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Step 4: Fetch the policy details based on policy_name
$sql = "SELECT * FROM policies WHERE policyID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $policyID); // Bind the policy name parameter
$stmt->execute();
$result = $stmt->get_result();

// Step 5: Check if the policy was found
if ($result->num_rows > 0) {
    $policy = $result->fetch_assoc();
} else {
    die("Policy not found.");
}

// Step 6: Process the billing details form if submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get billing details from the form
    $billing_name = $_POST['billing_name'];
    $credit_card = $_POST['credit_card'];
    $expiration_date = $_POST['expiration_date'];
    $cvv = $_POST['cvv'];

    // Step 7: Basic validation for billing details
    if (empty($billing_name) || empty($credit_card) || empty($expiration_date) || empty($cvv)) {
        $error_message = "Please fill in all billing details.";
    } else {
        // Step 8: Update the user's active_policy field with the policy name
        $update_sql = "UPDATE users SET active_policy_id = ? WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $policyID, $user_id);
        $update_stmt->execute();

        if ($update_stmt->affected_rows > 0) {
            // Transaction completed successfully
            $success_message = "Transaction successful! Your active policy is now: " . htmlspecialchars($policy['policy_name']);
        }
         else {
            $error_message = "There was an issue updating your active policy.";
        }
    }
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Policy</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 0;
            margin: 0;
        }
        body::before {
            content: '';
            position: fixed; /* Covers the entire screen */
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('bg.jpg'); /* Replace with your image URL */
            background-size: cover;
            background-repeat: no-repeat;
            background-attachment: fixed;
            filter: brightness(50%); /* Dims the background */
            z-index: -1; /* Places it behind all content */
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
            color: #a1bdc5; /* Change to a new color */
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
        .content {
            flex: 1; /* Take up available space */
            padding-bottom: 80px; /* Give space for the footer */
        }
        /* New Styles */
        /* New Styles */
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background-color: #000; /* Optional: Set a background to match your design */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Optional: Add shadow for a subtle effect */
            margin: 20px 50px; /* Adjust spacing around the container */
            border-radius: 8px; /* Optional: Add rounded corners */
        }


        .header-container h2 {
            margin: 0;
            text-align: center;
            flex: 1;
            font-size: 22px;
            
            color: #fff;
        }
        .form-container {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            width: 50%;
            text-align: center;
        }
        .form-container h1 {
            color: #333;
        }
        .form-container input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        .form-container button {
            padding: 15px 30px;
            background-color: #000;
            color: white;
            font-size: 18px;
            text-align: center;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .form-container button:hover {
            background-color: #555;
        }
        .error-message {
            color: red;
            font-weight: bold;
        }
        .success-message {
            color: green;
            font-weight: bold;
        }
        footer {
            background-color: #000;
            color: white;
            text-align: center;
            padding: 10px 0;
            position: fixed;
            bottom: 0;
            width: 100%;
            left: 0;
            right: 0;
        }
        .policy-details {
            margin-bottom: 20px;
        }
        .policy-details p {
            font-size: 18px;
            color: #555;
            margin: 10px 0;
        }
    </style>
</head>
<body>

<!-- Main Menu Bar -->
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

<div class="header-container">
<h2>Purchase Policy: <?php echo $policy['policy_name']; ?></h2>
    
</div>
    


<div class="form-container">
    <!-- Show error or success messages -->
    <?php if (isset($error_message)): ?>
        <p class="error-message"><?php echo $error_message; ?></p>
    <?php elseif (isset($success_message)): ?>
        <p class="success-message"><?php echo $success_message; ?></p>
    <?php endif; ?>

    <!-- Policy Details -->
    <!-- <div class="policy-details">
        <p><strong>Policy Name:</strong> <?php echo $policy['policy_name']; ?></p>
        <p><strong>Coverage Type:</strong> <?php echo $policy['coverage_type']; ?></p>
        <p><strong>Coverage Amount:</strong> $<?php echo $policy['coverage_amount']; ?></p>
        <p><strong>Deductible:</strong> $<?php echo $policy['deductible']; ?></p>
        <p><strong>Premium:</strong> $<?php echo $policy['premium']; ?></p>
        <p><strong>Policy Term:</strong> <?php echo $policy['policy_term']; ?> years</p>
        <p><strong>Additional Features:</strong> <?php echo $policy['additional_features']; ?></p>
        <p><strong>Target Scenario:</strong> <?php echo $policy['target_scenario']; ?></p>
    </div> -->

    <h2>Enter Billing Details</h2>
    <form action="purchase_policy.php?policyID=<?php echo urlencode($policyID); ?>" method="POST">
    <input type="text" name="billing_name" placeholder="Billing Name" required>
        <input type="text" name="credit_card" placeholder="Credit Card Number" required>
        <input type="text" name="expiration_date" placeholder="Expiration Date (MM/YY)" required>
        <input type="text" name="cvv" placeholder="CVV" required>
        <button type="submit">Complete Transaction</button>
    </form>
</div>

<footer>
    <p>&copy; 2024 InsuraSync Company. All rights reserved.</p>
</footer>

</body>
</html>
