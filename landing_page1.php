<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InsuraSync</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            overflow: hidden; /* Hide the scroll bar */
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

        /* Hero section styling */
        .hero {
            position: relative;
            background: url('./bg.jpg') no-repeat center center;
            background-size: cover;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #fff;
            padding: 0;
            margin: 0;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.45); /* Black with 50% opacity */
            z-index: 1;
        }

        .hero-content {
            position: relative;
            max-width: 800px;
            z-index: 2;
        }

        .hero h1 {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 15px;
            line-height: 1.2;
        }

        .hero p {
            font-size: 28px;
            margin-bottom: 30px;
        }

        .button-group {
            display: flex;
            gap: 20px;
            justify-content: center;
        }

        .learn-more-btn {
            padding: 10px 20px;
            background-color: transparent;
            color: #fff;
            border: 2px solid #fff;
            border-radius: 5px;
        }

        .get-insurance-btn {
            padding: 10px 20px;
            background-color: transparent;
            color: #fff;
            border: 2px solid #fff;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .get-insurance-btn:hover {
            background-color: #fff;
            color: #000;
            border-color: #fff;
        }

        /* Footer styling */
        footer {
            background-color: #333;
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
        <a href="#">Home</a>
        <a href="#">About</a>
        <a href="#">Contact</a>        
    </nav>
    <div class="auth-buttons">
    <a href="login.php"><button class="login-btn">Login</button></a>
    <a href="signup.php"><button class="signup-btn">Sign Up</button></a>
</div>

</header>

<section class="hero">
    <div class="hero-content">
        <h1>Revolutionizing Insurance Claims with AI – Fast, and Hassle-Free</h1>
        <p>Auto insurance is not just a choice - It's a necessity!</p>
        <div class="button-group">
            <a href="landing_page2.php">
                <button class="get-insurance-btn">Explore Policies</button>
            </a>
        </div>
    </div>
</section>

</body>
</html>
