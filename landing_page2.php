<?php
// Enable error reporting to debug any issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Step 1: Connect to the database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "insurasync";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Step 2: Fetch policies from the database
$sql = "SELECT policy_name, coverage_type, target_scenario FROM policies";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Policy Cards</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-image: url('bg.jpg'); /* Background image */
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: #fff;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        /* Sticky Navigation Bar */
        header {
            background-color: #000;
            color: white;
            padding: 20px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 10;
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

        .auth-buttons button {
            padding: 8px 15px;
            margin-left: 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        .login-btn {
            background-color: transparent;
            color: #fff;
            border: 1px solid #fff;
        }

        .signup-btn {
            background-color: #007bff;
            color: #fff;
            border: none;
        }

        /* Card Section */
        .card-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); /* Less cards per row */
            gap: 30px;
            padding: 30px;
            max-width: 100%;
            margin: 0 auto;
            box-sizing: border-box;
            flex-grow: 1;
            justify-items: center;
        }

        .card {
            background-color: black; /* Darker card background */
            color: #fff; /* White text */
            border: 1px solid #444;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.3); /* Soft shadow */
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 250px; /* Fix card height */
            width: 350px; /* Fix card width */
            min-width: 300px; /* Ensure cards don’t get too small */
            max-width: 350px; /* Limit max width */
        }

        .card:hover {
            transform: translateY(-10px); /* Slight lift on hover */
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.4); /* Stronger shadow on hover */
        }

        .card h3 {
            font-size: 20px;
            margin-bottom: 15px;
            color: #fff;
        }

        .card p {
            font-size: 16px;
            margin-bottom: 15px;
            color: #ccc;
        }

        .card .target-scenario {
            font-size: 14px;
            margin-bottom: 20px;
            color: #aaa;
        }

        .learn-more-btn {
            padding: 12px 25px;
            background-color: #fff; /* White background for the button */
            color: #000; /* Black text */
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .learn-more-btn:hover {
            background-color: #000;
            color: #fff;
        }

        /* Make the cards responsive */
        @media (max-width: 768px) {
            .card-container {
                grid-template-columns: 1fr 1fr; /* 2 cards per row on medium screens */
            }

            .card {
                height: 320px; /* Adjust height for medium screens */
            }
        }

        @media (max-width: 480px) {
            .card-container {
                grid-template-columns: 1fr; /* 1 card per row on small screens */
            }

            .card {
                height: 320px; /* Adjust height for small screens */
            }
        }
    </style>
</head>
<body>
    <!-- Sticky Navigation Bar -->
    <header id="navbar">
        <h1>InsuraSync</h1>
        <nav>
            <a href="landing_page1.php" class="nav-link">Home</a>
            <a href="#" class="nav-link">About</a>
            <a href="#" class="nav-link">Contact</a>
        </nav>
        <div class="auth-buttons">
    <a href="login.php"><button class="login-btn">Login</button></a>
    <a href="signup.php"><button class="signup-btn">Sign Up</button></a>
</div>
    </header>

    <div class="card-container">
        <?php
        // Step 3: Check if there are any policies
        if ($result->num_rows > 0) {
            // Step 4: Loop through the results and create a card for each policy
            while ($row = $result->fetch_assoc()) {
                echo '<div class="card">';
                echo '<h3>' . htmlspecialchars($row["policy_name"]) . '</h3>';
                echo '<p>' . htmlspecialchars($row["coverage_type"]) . '</p>';
                echo '<p class="target-scenario">Target Scenario: ' . htmlspecialchars($row["target_scenario"]) . '</p>';
                // Button redirects to login.php
                echo '<a href="login.php">';
                echo '<button class="learn-more-btn">Learn More</button>';
                echo '</a>';
                echo '</div>';
            }
        } else {
            echo "<p>No policies found.</p>";
        }

        // Close the database connection
        $conn->close();
        ?>
    </div>

    <script>
        // Add the fade effect when scrolling
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.style.opacity = '0.7'; // Fade effect
            } else {
                navbar.style.opacity = '1'; // Fully visible
            }
        });
    </script>
</body>
</html>
