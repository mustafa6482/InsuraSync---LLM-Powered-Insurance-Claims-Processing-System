<?php
// Start session to access user ID
session_start();

// Check if the user is logged in
if (isset($_SESSION['user_id'])) {
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

    // Query to fetch the user's claims - now including staff_notes
    $sql = "SELECT claim_id, policy_id, accident_date, accident_description, claim_amount, claim_status, filing_date, documents, staff_notes 
        FROM claims 
        WHERE user_id = '$user_id' 
        ORDER BY claim_id DESC";

    $result = $conn->query($sql);
    
    // Store claims data in an array for later use
    $claims = array();
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $claim_id = $row['claim_id'];
            $policy_id = $row['policy_id'];
            
            // Fetch policy name for each claim
            $policy_name_sql = "SELECT policy_name FROM policies WHERE policyID = '$policy_id'";
            $policy_result = $conn->query($policy_name_sql);
            
            if ($policy_result->num_rows > 0) {
                $policy_row = $policy_result->fetch_assoc();
                $row['policy_name'] = $policy_row['policy_name'];
            } else {
                $row['policy_name'] = 'Unknown';
            }
            
            // Add to claims array
            $claims[] = $row;
        }
    }

    // Close the connection
    $conn->close();
} else {
    // Redirect to the dashboard if the user is not logged in
    header("Location: user_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Status</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* General Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            position: relative;
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

        /* Container */
        .container {
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-width: 1200px;
            margin: 50px auto;
            text-align: center;
        }

        table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
            background-color: white;
            color: black;
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #000;
            color: white;
        }

        tr {
            cursor: pointer;
        }

        tr:hover {
            background-color: #f1f1f1;
            transform: scale(1.02);
        }

        /* Expandable reason section */
        .expandable {
            display: none;
            padding-top: 10px;
            font-size: 14px;
            color: #555;
        }

        /* Arrow indicator */
        .arrow {
            display: inline-block;
            width: 10px;
            height: 10px;
            margin-left: 10px;
            border-right: 2px solid #333;
            border-bottom: 2px solid #333;
            transform: rotate(45deg);
            transition: transform 0.3s ease;
        }

        .arrow-up {
            transform: rotate(-135deg);
        }

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
    <script>
        function toggleReason(claimId) {
            var reasonElement = document.getElementById("reason-" + claimId);
            var arrow = document.getElementById("arrow-" + claimId);
            if (reasonElement.style.display === "block") {
                reasonElement.style.display = "none";
                arrow.classList.remove('arrow-up');
            } else {
                reasonElement.style.display = "block";
                arrow.classList.add('arrow-up');
            }
        }
    </script>
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

<div class="container">
    <h2>Your Claims</h2>

    <?php if (!empty($claims)): ?>
        <!-- Claims Table -->
        <table>
            <tr>
                <th>Claim ID</th>
                <th>Policy Name</th>
                <th>Accident Date</th>
                <th>Accident Description</th>
                <th>Claim Amount</th>
                <th>Claim Status</th>
                <th>Filing Date</th>
                <!-- <th>Documents</th> -->
            </tr>
            <?php foreach ($claims as $row): ?>
                <tr onclick="toggleReason(<?php echo $row['claim_id']; ?>)">
                    <td><?php echo htmlspecialchars($row['claim_id']); ?></td>
                    <td><?php echo htmlspecialchars($row['policy_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['accident_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['accident_description']); ?></td>
                    <td><?php echo htmlspecialchars($row['claim_amount']); ?></td>
                    <td><?php echo htmlspecialchars($row['claim_status']); ?> <span id="arrow-<?php echo $row['claim_id']; ?>" class="arrow"></span></td>
                    <td><?php echo htmlspecialchars($row['filing_date']); ?></td>
                    <!-- <td><a href="<?php echo htmlspecialchars($row['documents']); ?>" target="_blank">View Documents</a></td> -->
                </tr>
                <tr class="expandable" id="reason-<?php echo $row['claim_id']; ?>">
                    <td colspan="8">
                        <?php
                            $status = $row['claim_status'];
                            if ($status == 'rejected') {
                                echo "Reason: " . htmlspecialchars($row['staff_notes'] ?? 'No reason provided.');
                            } elseif ($status == 'approved') {
                                echo "Claim approved. " . htmlspecialchars($row['staff_notes'] ?? '');
                            } elseif ($status == 'pending') {
                                echo "Status: Pending. " . htmlspecialchars($row['staff_notes'] ?? 'Await further instructions.');
                            } else {
                                echo htmlspecialchars($row['staff_notes'] ?? 'No additional information available.');
                            }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>No claims found.</p>
    <?php endif; ?>
</div>

<footer>
    <p>&copy; 2024 InsuraSync. All rights reserved.</p>
</footer>

</body>
</html>