<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technical Staff Dashboard - InsuraSync</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-image: url('bg.jpg');
            background-size: cover;
            background-repeat: no-repeat;
            background-attachment: fixed;
            color: #ddd;
            display: flex;
        }

        .sidebar {
            width: 250px;
            background-color: #1a1a2e;
            height: 100vh;
            padding: 20px;
            box-sizing: border-box;
            position: fixed;
            top: 0;
            left: 0;
        }

        .sidebar h2 {
            color: #60a5fa;
            text-align: center;
        }

        .sidebar a {
            display: block;
            padding: 15px;
            color: white;
            text-decoration: none;
            font-size: 16px;
            border-radius: 5px;
            transition: background-color 0.3s;
            margin-bottom: 10px;
        }

        .sidebar a:hover {
            background-color: #0056b3;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            width: calc(100% - 250px);
            box-sizing: border-box;
        }

        header {
            background-color: black;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .container {
            max-width: 800px;
            margin: 60px auto;
            padding: 25px;
            background-color: rgba(25, 25, 50, 0.95);
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            width: 100%;
            box-sizing: border-box;
            text-align: center;
        }

        footer {
            background-color: #1a1a2e;
            color: white;
            text-align: center;
            padding: 10px 0;
            position: fixed;
            bottom: 0;
            left: 250px;
            width: calc(100% - 250px);
        }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Staff Dashboard</h2>
    <a href="view_claims_staff.php">View Pending Claims</a>
    <a href="completed_claims.php">View Completed Claims</a>
    <a href="staff_login.php">Logout</a>
</div>

<div class="main-content">
    <header>
        <h1>InsuraSync - Technical Staff Dashboard</h1>
    </header>

    <div class="container">
        <h2>Welcome to the Staff Dashboard</h2>
        <p>Manage and process insurance claims efficiently.</p>
    </div>
</div>

<footer>
    <p>&copy; 2024 InsuraSync. All rights reserved.</p>
</footer>

</body>
</html>
