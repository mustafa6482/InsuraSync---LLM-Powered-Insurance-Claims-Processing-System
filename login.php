<?php
// Start session to retain user ID
session_start();

// Database credentials
$servername = "localhost";
$username = "root"; // Your MySQL username
$password = ""; // Your MySQL password
$dbname = "insurasync"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    // SQL query to check if the email exists in the database
    $sql = "SELECT * FROM users WHERE email = '$email' AND password = '$password'";
    $result = $conn->query($sql);

    // Check if a match is found
    if ($result->num_rows > 0) {
        // Fetch the user data
        $user = $result->fetch_assoc();

        // Store the user ID in session
        $_SESSION['user_id'] = $user['user_id']; // Assuming the user table has a column 'id'

        // Login successful, redirect to user dashboard
        header("Location: user_dashboard.php");
        exit(); // Ensure no further code is executed after the redirect
    } else {
        // Login failed
        echo "Invalid email or password!";
    }

    // Close connection
    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - InsuraSync</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-image: url('bg.jpg'); /* Replace with your image URL */
            background-size: cover;
            background-repeat: no-repeat;
            background-attachment: fixed;
            color: #333;
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

        /* Container for the form */
        .container {
            max-width: 500px; /* Ensure the container is not too wide */
            margin: 50px auto;
            padding: 20px;
            background-color: rgba(255, 255, 255, 0.9); /* Slightly transparent background */
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 100%; /* Ensure the container adjusts to different screen sizes */
            box-sizing: border-box;
        }

        .form-title {
            text-align: center;
            margin-bottom: 20px;
        }

        .form-title h2 {
            margin: 0;
            color: #333;
        }

        .form-title p {
            margin-top: 5px;
            font-size: 14px;
            color: #555;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        .form-group input {
            width: 100%; /* Ensure inputs take up full container width */
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box; /* Ensure padding doesn't cause overflow */
        }

        .form-group input:focus {
            border-color: #007BFF;
            outline: none;
        }

        .form-group input[type="submit"] {
            background-color: #000;
            color: white;
            cursor: pointer;
            border: none;
            transition: background-color 0.3s;
        }

        .form-group input[type="submit"]:hover {
            background-color: #444;
        }

        .form-group p {
            text-align: center;
        }

        .form-group p a {
            color: #007BFF;
            text-decoration: none;
        }

        .form-group p a:hover {
            text-decoration: underline;
        }

        footer {
            background-color: #000;
            color: white;
            text-align: center;
            padding: 10px 0;
            position: fixed;
            bottom: 0;
            width: 100%;
        }
    </style>
</head>
<body>

<header>
    <h1>InsuraSync</h1>
    <nav>
        <a href="landing_page1.php">Home</a>
        <a href="signup.php">Sign Up</a>
    </nav>
</header>

<div class="container">
    <div class="form-title">
        <h2>Login to Your Account</h2>
        <p>Enter your credentials to access your account.</p>
    </div>

    <form action="login.php" method="POST">
        <!-- Email -->
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required placeholder="Enter your email">
        </div>

        <!-- Password -->
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="Enter your password">
        </div>

        <!-- Submit Button -->
        <div class="form-group">
            <input type="submit" value="Login">
        </div>

        <!-- Forgot Password and Sign Up -->
        <div class="form-group">
            <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
            <p><a href="forgot-password.php">Forgot your password?</a></p>
        </div>
    </form>
</div>

<footer>
    <p>&copy; 2024 InsuraSync. All rights reserved.</p>
</footer>

</body>
</html>

