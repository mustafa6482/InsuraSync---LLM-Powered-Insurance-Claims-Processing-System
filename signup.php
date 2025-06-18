<?php
// Start session to retain user ID
session_start();

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure you have PHPMailer installed via Composer

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

$error_message = "";

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $fullName = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Check if the email already exists
    $checkEmail = "SELECT * FROM users WHERE email = '$email'";
    $result = $conn->query($checkEmail);

    if ($result->num_rows > 0) {
        $error_message = "Email already registered!";
    } else {
        // Insert the new user into the database with verified_profile set to 1
        $sql = "INSERT INTO users (full_name, email, password, verified_profile) VALUES ('$fullName', '$email', '$password', 1)";

        if ($conn->query($sql) === TRUE) {
            // Retrieve the user ID of the newly created user
            $user_id = $conn->insert_id;

            // Store the user ID in session
            $_SESSION['user_id'] = $user_id;
            
            // Send email notification
            $mail = new PHPMailer(true);

            try {
                // SMTP Configuration
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'insurasync@gmail.com'; // Your Gmail
                $mail->Password   = 'ojah dlqp ujxi wmab'; // Your App Password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                // Sender
                $mail->setFrom('insurasync@gmail.com', 'InsuraSync');
                
                // Recipients
                $mail->addAddress($email); // Send to user
                $mail->addAddress('i210832@nu.edu.pk'); // Also send to admin

                // Email Content
                $mail->isHTML(true);
                $mail->Subject = 'Welcome to InsuraSync';
                $mail->Body    = "
                    <h2>Welcome to InsuraSync, {$fullName}!</h2>
                    <p>Thank you for creating an account with us.</p>
                    <p>Your account has been successfully registered with the following details:</p>
                    <ul>
                        <li><strong>Name:</strong> {$fullName}</li>
                        <li><strong>Email:</strong> {$email}</li>
                        <li><strong>User ID:</strong> {$user_id}</li>
                    </ul>
                    <p>You can now log in to access your dashboard and start using our services.</p>
                    <p>If you have any questions or need assistance, please don't hesitate to contact our support team.</p>
                    <p>Best regards,<br>The InsuraSync Team</p>
                ";

                $mail->send();
                // Redirect to user dashboard
                header("Location: user_dashboard.php");
                exit();
            } catch (Exception $e) {
                // Even if email fails, we still want to redirect the user
                header("Location: user_dashboard.php");
                exit();
            }
        } else {
            $error_message = "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - InsuraSync</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-image: url('bg.jpg'); background-size: cover; background-repeat: no-repeat; background-attachment: fixed; color: #333; }
        header { background-color: #000; color: white; padding: 20px 50px; display: flex; justify-content: space-between; align-items: center; }
        header h1 { margin: 0; font-size: 24px; }
        nav { display: flex; gap: 15px; }
        nav a { color: white; text-decoration: none; font-size: 15px; }
        nav a:hover { color: #a1bdc5; }
        .container {
        max-width: 500px;
        margin: 15px auto 80px; /* Added bottom margin */
        padding: 20px;
        background-color: rgba(255, 255, 255, 0.9);
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        box-sizing: border-box;
        }
        .form-title { text-align: center; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; }
        .form-group input {
        width: 100%;
        padding: 10px;
        font-size: 16px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box; /* Ensures padding/border don't overflow */
        display: block;
        }
        .form-group input:focus { border-color: #007BFF; outline: none; }
        .form-group input[type="submit"] { background-color: #000; color: white; cursor: pointer; border: none; transition: background-color 0.3s; }
        .form-group input[type="submit"]:hover { background-color: #444; }
        .form-group p { text-align: center; }
        .form-group p a { color: #007BFF; text-decoration: none; }
        .form-group p a:hover { text-decoration: underline; }
        footer { background-color: #000; color: white; text-align: center; padding: 10px 0; position: fixed; bottom: 0; width: 100%; }
        .error-message { color: red; font-size: 14px; text-align: center; margin-bottom: 10px; }
    </style>
    <script>
        function validatePassword() {
            var password = document.getElementById('password').value;
            var confirmPassword = document.getElementById('confirm_password').value;
            if (password !== confirmPassword) {
                alert("Passwords do not match. Please try again.");
                return false;
            }
            return true;
        }
    </script>
</head>
<body>
<header>
    <h1>InsuraSync</h1>
    <nav>
        <a href="landing_page1.php">Home</a>
        <a href="login.php">Login</a>
    </nav>
</header>
<div class="container">
    <div class="form-title">
        <h2>Create an Account</h2>
        <p>Fill in the details below to sign up for InsuraSync.</p>
    </div>
    <?php if (!empty($error_message)) { echo "<div class='error-message'>$error_message</div>"; } ?>
    <form action="" method="POST" onsubmit="return validatePassword()">
        <div class="form-group">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" required placeholder="Enter your full name">
        </div>
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required placeholder="Enter your email">
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="Enter your password">
        </div>
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">
        </div>
        <div class="form-group">
            <input type="submit" value="Sign Up">
        </div>
        <div class="form-group">
            <p>Already have an account? <a href="login.php">Login</a></p>
        </div>
    </form>
</div>
<footer>
    <p>&copy; 2024 InsuraSync. All rights reserved.</p>
</footer>
</body>
</html>