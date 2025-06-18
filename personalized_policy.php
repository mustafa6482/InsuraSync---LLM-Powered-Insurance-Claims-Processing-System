<?php
// Start session for storing user information (if needed)
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "insurasync"; // Your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to calculate the score for each policy based on user input
function calculate_score($policy, $user_input) {
    $score = 0;

    // Scoring for coverage type (most important)
    if ($policy['coverage_type'] == $user_input['Coverage Type']) {
        $score += 30;  // High score for exact match
    } else {
        $score -= 15;  // Penalty for mismatch
    }

    // Scoring for additional features (second most important)
    if (strpos($policy['additional_features'], $user_input['Additional Features']) !== false) {
        $score += 25;  // Significant score for matching features
    } else {
        $score -= 10;  // Penalty if the feature doesn't match
    }

    // Scoring for coverage amount, deductible, and premium (equally important)
    if (abs($policy['coverage_amount'] - $user_input['Coverage Amount ($)']) <= 5000) {
        $score += 15; // If within range
    }

    if (abs($policy['deductible'] - $user_input['Deductible ($)']) <= 200) {
        $score += 15; // If deductible is similar
    }

    if ($policy['premium'] <= $user_input['Premium ($)']) {
        $score += 15; // If premium fits within user's budget
    }

    return $score;
}

// Function to calculate cosine similarity between two vectors
function cosine_similarity($vector1, $vector2) {
    $dot_product = 0;
    $magnitude1 = 0;
    $magnitude2 = 0;

    foreach ($vector1 as $key => $value) {
        if (isset($vector2[$key])) {
            $dot_product += $value * $vector2[$key];
        }
    }

    foreach ($vector1 as $value) {
        $magnitude1 += pow($value, 2);
    }

    foreach ($vector2 as $value) {
        $magnitude2 += pow($value, 2);
    }

    $magnitude1 = sqrt($magnitude1);
    $magnitude2 = sqrt($magnitude2);

    return $magnitude1 && $magnitude2 ? $dot_product / ($magnitude1 * $magnitude2) : 0;
}

// Function to calculate similarity score between users
function calculate_similarity($user1, $user2) {
    $similarity = 0;
    $threshold = 11000; // Margin for income comparison

    if (abs($user1['monthly_income'] - $user2['monthly_income']) <= $threshold) {
        $similarity += 1;
    }

    $attributes1 = [
        'is_senior_citizen' => (int) $user1['is_senior_citizen'],
        'city' => $user1['city'],
        'vehicle_name' => $user1['vehicle_name'],
        'vehicle_company' => $user1['vehicle_company'],
    ];

    $attributes2 = [
        'is_senior_citizen' => (int) $user2['is_senior_citizen'],
        'city' => $user2['city'],
        'vehicle_name' => $user2['vehicle_name'],
        'vehicle_company' => $user2['vehicle_company'],
    ];

    $vector1 = [];
    $vector2 = [];
    foreach ($attributes1 as $key => $value) {
        if (is_numeric($value)) {
            $vector1[$key] = $value;
            $vector2[$key] = (int) $attributes2[$key];
        } else {
            $vector1[$key] = $value === $attributes2[$key] ? 1 : 0;
            $vector2[$key] = $value === $attributes2[$key] ? 1 : 0;
        }
    }

    $similarity += cosine_similarity($vector1, $vector2);

    return $similarity;
}

// Check if form is submitted for personalized recommendations
$recommendations = [];
$similar_user_recommendations = [];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get user input from the form
    $user_input = [
        'Coverage Type' => $_POST['coverage_type'],
        'Coverage Amount ($)' => $_POST['coverage_amount'],
        'Premium ($)' => $_POST['premium'],
        'Deductible ($)' => $_POST['deductible'],
        'Policy Term (Years)' => $_POST['policy_term'],
        'Additional Features' => $_POST['additional_features'],
    ];

    // Query to fetch policies from the database
    $sql = "SELECT * FROM policies";
    $result = $conn->query($sql);

    // Array to hold policies and their scores
    $policies_with_scores = [];

    if ($result->num_rows > 0) {
        // Fetch policies from the database and calculate scores
        while ($row = $result->fetch_assoc()) {
            $score = calculate_score($row, $user_input);
            $policies_with_scores[] = [
                'policy_name' => $row['policy_name'],
                'score' => $score,
                'policyID' => $row['policyID'],
                'coverage_type' => $row['coverage_type'],
                'premium' => $row['premium'],
                'coverage_amount' => $row['coverage_amount'],
                'deductible' => $row['deductible'],
                'additional_features' => $row['additional_features']
            ];
        }

        usort($policies_with_scores, function($a, $b) {
            return $b['score'] - $a['score'];
        });

        // Get top recommendations
        $recommendations = array_slice($policies_with_scores, 0, 2);
    }

    // Retrieve similar policies based on similar users (if any)
    // Check if the user is logged in
    if (isset($_SESSION['user_id'])) {
        // Get the user ID from the session
        $user_id = $_SESSION['user_id'];
        
        // Fetch current user details
        $sql_user = "SELECT * FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql_user);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_result = $stmt->get_result();
        
        // Only proceed if we found the current user
        if ($user_result->num_rows > 0) {
            $current_user = $user_result->fetch_assoc();

            // Fetch all users except the current one
            $sql_users = "SELECT * FROM users WHERE user_id != ?";
            $stmt = $conn->prepare($sql_users);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $users_result = $stmt->get_result();

            $similar_users = [];
            while ($other_user = $users_result->fetch_assoc()) {
                // Only compare if the other user has an active policy
                if (!empty($other_user['active_policy_id'])) {
                    $similarity_score = calculate_similarity($current_user, $other_user);
                    if ($similarity_score >= 1.0) {
                        $similar_users[] = [
                            'user_id' => $other_user['user_id'],
                            'similarity_score' => $similarity_score,
                            'active_policy_id' => $other_user['active_policy_id'],
                        ];
                    }
                }
            }

            if (count($similar_users) > 0) {
                $recommended_policies = [];
                foreach ($similar_users as $similar_user) {
                    $policyID = $similar_user['active_policy_id'];
                    if (!empty($policyID) && !in_array($policyID, $recommended_policies)) {
                        $recommended_policies[] = $policyID;
                    }
                }

                if (count($recommended_policies) > 0) {
                    $similar_user_recommendations = [];
                    foreach ($recommended_policies as $policyID) {
                        $policy_sql = "SELECT * FROM policies WHERE policyID = ?";
                        $stmt = $conn->prepare($policy_sql);
                        $stmt->bind_param("s", $policyID);
                        $stmt->execute();
                        $policy_result = $stmt->get_result();
                        if ($policy_result->num_rows > 0) {
                            $policy = $policy_result->fetch_assoc();
                            $similar_user_recommendations[] = [
                                'policy_name' => $policy['policy_name'],
                                'coverage_type' => $policy['coverage_type'],
                                'premium' => $policy['premium'],
                                'coverage_amount' => $policy['coverage_amount'],
                                'deductible' => $policy['deductible'],
                                'policyID' => $policy['policyID'],
                                'additional_features' => $policy['additional_features']
                            ];
                        }
                    }
                }
            }
        }
    } else {
        // User is not logged in, handle accordingly
        // For debugging purposes, we'll store this message to display later
        $login_error = "User is not logged in. Similar user recommendations are not available.";
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Get Personalized Policy</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 0;
            margin: 0;
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

        form {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            background-color: #fff;
            border-bottom: 2px solid #ccc;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        form select,
        form input {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }

        form button {
            padding: 10px 15px;
            background-color: #333;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        form button:hover {
            background-color: #555;
        }

        footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 10px 0;
            margin-top: 30px;
            width: 100%;
        }

        /* Container for recommendations and similar recommendations */
        .container {
            max-width: 1100px; /* Adjust to fit the recommendation cards */
            margin: 50px auto;
            padding: 20px;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 100%;
            box-sizing: border-box;
        }

        .recommendations, .similar-recommendations {
            padding: 20px;
        }

        .cards-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }

        .card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            width: 300px;
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }

        .card h3 {
            margin: 0 0 10px;
            font-size: 20px;
            color: #333;
        }

        .card p {
            margin: 5px 0;
            font-size: 14px;
            color: #555;
        }

        .card .view-details {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 15px;
            background-color: #007BFF;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .card .view-details:hover {
            background-color: #0056b3;
        }

        .debug-info {
            background-color: #ffeeee;
            border: 1px solid #ffcccc;
            padding: 10px;
            margin-top: 20px;
            border-radius: 4px;
            color: #cc0000;
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
            <div><a href="profile.php">My Profile</a></div>
            <div><a href="landing_page1.php">Logout</a></div>
        </div>
    </div>
</header>

<!-- Horizontal User Input Form -->
<form method="POST" action="">
    <select name="coverage_type" required>
        <option value="Comprehensive">Comprehensive</option>
        <option value="Third Party">Third Party</option>
        <option value="Third Party, Fire & more">Third Party, Fire & more</option>
        <option value="Collision Only">Collision Only</option>
        <option value="Third Party Only">Third Party Only</option>
        <option value="Comprehensive + Rideshare">Comprehensive + Rideshare</option>
    </select>

    <input type="number" name="coverage_amount" placeholder="Coverage Amount ($)" required>
    <input type="number" name="premium" placeholder="Premium ($)" required>
    <input type="number" name="deductible" placeholder="Deductible ($)" required>
    <input type="number" name="policy_term" placeholder="Policy Term (Years)" required>
    <select name="additional_features" required>
    <option value="">Select Additional Features</option>
    <option value="No additional features">No additional features</option>
    <option value="Roadside assistance">Roadside assistance</option>
    <option value="legal coverage">legal coverage</option>
    <option value="Extra third-party liability protection">Extra third-party liability protection</option>
    <option value="Rental car coverage">Rental car coverage</option>
    <option value="glass protection">glass protection</option>
    <option value="Accident forgiveness">Accident forgiveness</option>
    <option value="Enhanced accident forgiveness">Enhanced accident forgiveness</option>
    <option value="luxury vehicle">luxury vehicle</option>
    <option value="New car replacement">New car replacement</option>
    <option value="glass coverage">glass coverage</option>
    <option value="Personal injury protection">Personal injury protection</option>
    <option value="Enhanced injury protection">legal assistance</option>
    <option value="Enhanced injury protection">Enhanced injury protection</option>
    <option value="OEM parts guarantee">OEM parts guarantee</option>
    <option value="accident protection">accident protection</option>
    <option value="Enhanced glass">Enhanced glass</option>
    <option value="Family car discount">Family car discount</option>
    <option value="Luxury family car coverage">Luxury family car coverage</option>
    <option value="Lost key coverage">Lost key coverage</option>
    <option value="Lost key and breakdown coverage">Lost key and breakdown coverage</option>
    <option value="Legal and accident assistance">Legal and accident assistance</option>
    <option value="Urban driver discount">Urban driver discount</option>
    <option value="Enhanced urban coverage">Enhanced urban coverage</option>
    <option value="Senior driver discount">Senior driver discount</option>
    <option value="Enhanced senior discount">Enhanced senior discount</option>
    <option value="Rideshare coverage for city driving">Rideshare coverage for city driving</option>
    <option value="Enhanced rideshare">Enhanced rideshare</option>
    <option value="long-distance travel">long-distance travel</option>
    <option value="Eco-friendly vehicle discount">Eco-friendly vehicle discount</option>
    <option value="Enhanced eco-friendly coverage">Enhanced eco-friendly coverage</option>
    <option value="accident forgiveness">accident forgiveness</option>
    <option value="family protection">family protection</option>
    <option value="Enhanced coverage for luxury vehicles">Enhanced coverage for luxury vehicles</option>
    <option value="Luxury vehicle and accident forgiveness">Luxury vehicle and accident forgiveness</option>
    <option value="Enhanced accident coverage">Enhanced accident coverage</option>
    <option value="extended protection">extended protection</option>
</select>
    <button type="submit">Get Recommendations</button>
</form>

<!-- Personalized recommendations -->
<div class="container recommendations">
    <h2>Top Policy Recommendations Based on Your Inputs</h2>
    <div class="cards-container">
        <?php
        if (count($recommendations) > 0) {
            foreach ($recommendations as $policy) {
                echo "<div class='card'>";
                echo "<h3>" . htmlspecialchars($policy['policy_name']) . "</h3>";
                echo "<p><strong>Coverage Type:</strong> " . htmlspecialchars($policy['coverage_type']) . "</p>";
                echo "<p><strong>Premium:</strong> $" . number_format($policy['premium']) . "</p>";
                echo "<p><strong>Coverage Amount:</strong> $" . number_format($policy['coverage_amount']) . "</p>";
                echo "<p><strong>Deductible:</strong> $" . number_format($policy['deductible']) . "</p>";
                echo "<a href='policy_details.php?policyID=" . urlencode($policy['policyID']) . "' class='view-details'>View Details and Purchase</a>";
                echo "</div>";
            }
        } else {
            echo "<p>No recommendations found based on your input. Please try different criteria.</p>";
        }
        ?>
    </div>
</div>

<!-- Similar users' policy recommendations -->
<div class="container similar-recommendations">
    <h2>Check Out More Policies from Similar Users</h2>
    <div class="cards-container">
        <?php
        if (isset($login_error)) {
            echo "<div class='debug-info'>" . $login_error . "</div>";
        } else if (count($similar_user_recommendations) > 0) {
            foreach ($similar_user_recommendations as $policy) {
                echo "<div class='card'>";
                echo "<h3>" . htmlspecialchars($policy['policy_name']) . "</h3>";
                echo "<p><strong>Coverage Type:</strong> " . htmlspecialchars($policy['coverage_type']) . "</p>";
                echo "<p><strong>Premium:</strong> $" . number_format($policy['premium']) . "</p>";
                echo "<p><strong>Coverage Amount:</strong> $" . number_format($policy['coverage_amount']) . "</p>";
                echo "<p><strong>Deductible:</strong> $" . number_format($policy['deductible']) . "</p>";
                echo "<a href='policy_details.php?policyID=" . urlencode($policy['policyID']) . "' class='view-details'>View Details and Purchase</a>";
                echo "</div>";
            }
        } else {
            echo "<p>No similar user recommendations available at this time.</p>";
        }
        ?>
    </div>
</div>

<footer>
    <p>&copy; 2024 InsuraSync. All rights reserved.</p>
</footer>

</body>
</html>