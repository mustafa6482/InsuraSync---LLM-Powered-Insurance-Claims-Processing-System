<?php
session_start();

// Database connection
$servername = "localhost"; // Change if needed
$username = "root"; // Update with your database username
$password = ""; // Update with your database password
$database = "insurasync"; // Your database name

$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize error variable
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        // Since passwords are plaintext, we need to modify our approach
        $sql = "SELECT staff_id, password FROM staff WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $staff = $result->fetch_assoc();
            // Direct password comparison for plaintext passwords
            if ($password === $staff['password']) {
                // $_SESSION['user_id'] = $staff['staff_id']; // Store staff_id as user_id in session
                
                // Special case for staff2@email.com
                if ($email === "staff2@email.com") {
                    header("Location: staff_dashboard2.php");
                } else {
                    header("Location: staff_dashboard.php");
                }
                exit();
            } else {
                $error = "Invalid email or password.";
            }
        } else {
            $error = "Invalid email or password.";
        }
        $stmt->close();
    } else {
        $error = "Both fields are required.";
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technical Staff Login - InsuraSync</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-image: url('bg.jpg'); /* Replace with a professional tech-themed image */
            /* background-color: black; */
            background-size: cover;
            background-repeat: no-repeat;
            background-attachment: fixed;
            color: #ddd;
        }

        header {
            background-color: #1a1a2e;
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
            color: #60a5fa;
        }

        .container {
            max-width: 500px;
            margin: 60px auto;
            padding: 25px;
            background-color: rgba(25, 25, 50, 0.95);
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            width: 100%;
            box-sizing: border-box;
        }

        .form-title {
            text-align: center;
            margin-bottom: 20px;
        }

        .form-title h2 {
            margin: 0;
            color: #60a5fa;
        }

        .form-title p {
            margin-top: 5px;
            font-size: 14px;
            color: #bbb;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #ddd;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #444;
            border-radius: 4px;
            background-color: #222;
            color: white;
            box-sizing: border-box;
        }

        .form-group input:focus {
            border-color: #60a5fa;
            outline: none;
        }

        .form-group input[type="submit"] {
            background-color: #007BFF;
            color: white;
            cursor: pointer;
            border: none;
            transition: background-color 0.3s;
        }

        .form-group input[type="submit"]:hover {
            background-color: #0056b3;
        }

        .form-group p {
            text-align: center;
        }

        .form-group p a {
            color: #60a5fa;
            text-decoration: none;
        }

        .form-group p a:hover {
            text-decoration: underline;
        }

        footer {
            background-color: #1a1a2e;
            color: white;
            text-align: center;
            padding: 10px 0;
            position: fixed;
            bottom: 0;
            width: 100%;
        }
        
        .error-message {
            color: #ff6b6b;
            background-color: rgba(255, 107, 107, 0.1);
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>

<header>
    <h1>InsuraSync - Technical Staff</h1>
    <nav>
        <a href="landing_page1.php">Home</a>
    </nav>
</header>

<div class="container">
    <div class="form-title">
        <h2>Technical Staff Login</h2>
        <p>Authorized personnel only. Enter your credentials to access the claim processing system.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="error-message">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="email">Staff Email</label>
            <input type="email" id="email" name="email" required placeholder="Enter your email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="Enter your password">
        </div>

        <div class="form-group">
            <input type="submit" value="Login">
        </div>

        <div class="form-group">
            <p><a href="forgot-password.php">Forgot your password?</a></p>
        </div>
    </form>
</div>

<footer>
    <p>&copy; 2024 InsuraSync. All rights reserved.</p>
</footer>

</body>
</html>