<?php


// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "insurasync";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set default sort order
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'recent';

// Construct SQL query based on sort parameter - modified to filter by both verification_status AND claim_status
switch ($sort_by) {
    case 'high_cost':
        $sql = "SELECT claim_id, policy_id, user_id, claim_status, verification_status, filing_date, claim_amount, claim_category FROM claims WHERE verification_status = 'verified' AND claim_status = 'pending' ORDER BY claim_amount DESC";
        break;
    case 'low_cost':
        $sql = "SELECT claim_id, policy_id, user_id, claim_status, verification_status, filing_date, claim_amount, claim_category FROM claims WHERE verification_status = 'verified' AND claim_status = 'pending' ORDER BY claim_amount ASC";
        break;
    case 'recent':
    default:
        $sql = "SELECT claim_id, policy_id, user_id, claim_status, verification_status, filing_date, claim_amount, claim_category FROM claims WHERE verification_status = 'verified' AND claim_status = 'pending' ORDER BY filing_date DESC";
        break;
}

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claims Dashboard - Staff</title>
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
            max-width: 900px;
            margin: 60px auto;
            padding: 25px;
            background-color: rgba(25, 25, 50, 0.95);
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            width: 100%;
            box-sizing: border-box;
            text-align: center;
            max-height: 500px;
            overflow-y: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #1a1a2e;
            color: white;
        }
        .action-btn {
            padding: 5px 10px;
            text-decoration: none;
            color: white;
            background-color: #007bff;
            border-radius: 4px;
        }
        .action-btn:hover {
            background-color: #0056b3;
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
        
        .sort-controls {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 15px;
            align-items: center;
        }
        
        .sort-controls label {
            margin-right: 10px;
            font-weight: bold;
        }
        
        .sort-controls select {
            padding: 8px;
            border-radius: 4px;
            background-color: #2a2a4a;
            color: white;
            border: 1px solid #3a3a5a;
        }
        
        .sort-controls button {
            padding: 8px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }
        
        .sort-controls button:hover {
            background-color: #0056b3;
        }
        
        .category-low {
            color: #90ee90; /* Light green */
        }
        
        .category-moderate {
            color: #ffcb6b; /* Amber */
        }
        
        .category-high {
            color: #ff6b6b; /* Light red */
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
            <h2>Pending Claims Dashboard</h2>
            
            <div class="sort-controls">
                <label for="sort_by">Sort By:</label>
                <select id="sort_by" name="sort_by">
                    <option value="recent" <?php echo ($sort_by == 'recent') ? 'selected' : ''; ?>>Most Recent</option>
                    <option value="high_cost" <?php echo ($sort_by == 'high_cost') ? 'selected' : ''; ?>>High Cost</option>
                    <option value="low_cost" <?php echo ($sort_by == 'low_cost') ? 'selected' : ''; ?>>Low Cost</option>
                </select>
                <button onclick="applySorting()">Apply</button>
            </div>
            
            <table>
                <tr>
                    <th>Claim ID</th>
                    <th>Policy ID</th>
                    <th>Claimant ID</th>
                    <th>Claim Status</th>
                    <th>Verification Status</th>
                    <th>Date Filed</th>
                    <th>Claim Amount</th>
                    <th>Category</th>
                    <th>Action</th>
                </tr>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['claim_id']) ?></td>
                            <td><?= htmlspecialchars($row['policy_id']) ?></td>
                            <td><?= htmlspecialchars($row['user_id']) ?></td>
                            <td><?= htmlspecialchars($row['claim_status']) ?></td>
                            <td><?= htmlspecialchars($row['verification_status']) ?></td>
                            <td><?= htmlspecialchars($row['filing_date']) ?></td>
                            <td><?= htmlspecialchars('$' . number_format($row['claim_amount'], 2)) ?></td>
                            <?php 
                                $category_class = '';
                                if ($row['claim_category'] == 'low cost') {
                                    $category_class = 'category-low';
                                } elseif ($row['claim_category'] == 'moderate cost') {
                                    $category_class = 'category-moderate';
                                } elseif ($row['claim_category'] == 'high cost') {
                                    $category_class = 'category-high';
                                }
                            ?>
                            <td class="<?= $category_class ?>"><?= htmlspecialchars($row['claim_category']) ?></td>
                            <td><a href="process_claim.php?id=<?= $row['claim_id'] ?>" class="action-btn">Review</a></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9">No pending claims found.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 InsuraSync. All rights reserved.</p>
    </footer>

    <script>
        function applySorting() {
            const sortBy = document.getElementById('sort_by').value;
            window.location.href = `view_claims_staff.php?sort_by=${sortBy}`;
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>