<?php
session_start(); // Start the session

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user ID is set in session
if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}

$user_id = $_SESSION['user_id'];
// echo "<h2>Welcome, User ID: $user_id</h2>";
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "insurasync"; // Your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch policies from the database
$sql = "SELECT policyID, policy_name, coverage_type FROM policies";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Policies</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
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
            flex: 1;
            padding-bottom: 80px;
        }
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            background-color: #000;
            margin: 20px 50px;
            border-radius: 8px;
        }
        .header-container h2 {
            margin: 0;
            text-align: left;
            font-size: 22px;
            color: #fff;
            flex: 1;
        }
        .personalized-btn {
            padding: 15px 30px;
            background-color: #FFF;
            color: black;
            font-size: 14px;
            text-align: center;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            margin-left: 20px;
        }
        .personalized-btn:hover {
            background-color: #000;
            color: white;
        }
        .card-container {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            justify-content: center;
            padding-top: 30px;
        }
        .card {
            background-color: black;
            color: #fff;
            border: 1px solid #444;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            height: 250px;
            width: 350px;
        }
        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.4);
        }
        .card h3 {
            font-size: 20px;
            margin-bottom: 15px;
            color: #fff;
        }
        .card a {
            text-decoration: none;
            color: #fff;
        }
        .card a:hover {
            color: #ccc;
        }
        footer {
            background-color: #000;
            color: white;
            text-align: center;
            padding: 10px 0;
            position: sticky;
            bottom: 0;
            width: 100%;
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

<div class="header-container">
    <h2>Dive into InsuraSync's Policies</h2>
    <a href="personalized_policy.php" class="personalized-btn">Get Personalized Policy</a>
</div>

<div class="content">
    <div class="card-container">
        <?php
        $query = "SELECT policyID, policy_name, description, target_scenario FROM policies";
        $result = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<div class='card'>";
                echo "<h3>" . htmlspecialchars($row['policy_name']) . "</h3>";
                echo "<p class='target-scenario'>Target: " . htmlspecialchars($row['target_scenario']) . "</p>";
                echo "<a href='policy_details.php?policyID=" . urlencode($row['policyID']) . "' class='learn-more-btn' style='
                padding: 15px 30px; 
                background-color: #FFF; 
                color: black; 
                font-size: 14px; 
                text-align: center; 
                border: none; 
                border-radius: 5px; 
                cursor: pointer; 
                text-decoration: none; 
                display: inline-block; 
                margin-top: 10px; 
                transition: background-color 0.3s, color 0.3s;'
                onmouseover='this.style.backgroundColor=\"#000\"; this.style.color=\"#FFF\"' 
                onmouseout='this.style.backgroundColor=\"#FFF\"; this.style.color=\"#000\"'>Learn More</a>";

echo "</div>";

            }
        } else {
            echo "<p>No policies found.</p>";
        }
        mysqli_close($conn);
        ?>
    </div>
</div>

<footer>
    <p>&copy; 2024 InsuraSync. All rights reserved.</p>
</footer>

</body>
</html>