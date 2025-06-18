<?php 
// Start session to access user ID
session_start();

// Check if the user is logged in
if (isset($_SESSION['user_id'])) {

    

    // Get the user ID from the session
    $user_id = $_SESSION['user_id'];

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
    

    // SQL query to fetch the user's full name and active policy using the user ID
    $sql = "SELECT full_name, active_policy_id FROM users WHERE user_id = '$user_id'";
    $result = $conn->query($sql);

    // Initialize variables
    $user_name = "User";
    $active_policy_id = null;

    // Check if a match is found
    if ($result->num_rows > 0) {
        // Fetch the user data
        $user = $result->fetch_assoc();
        $user_name = $user['full_name']; // Use 'full_name' column
        $active_policy_id = $user['active_policy_id']; // Use 'active_policy' column
    }

    $policy_name_sql = "SELECT policy_name FROM policies WHERE policyID = '$active_policy_id'";
    $policy_name_result = $conn->query($policy_name_sql);

    

    if ($policy_name_result && $policy_name_result->num_rows > 0) {
        $policy_data = $policy_name_result->fetch_assoc();
        $policy_name = $policy_data['policy_name'];
    } else {
        $policy_name = "";
    }



    // Query to check if there is an active claim with pending status
    $claim_status_sql = "SELECT claim_status FROM claims WHERE user_id = '$user_id' AND policy_id = '$active_policy_id' ORDER BY claim_id DESC LIMIT 1";
    $claim_status_result = $conn->query($claim_status_sql);
    $claim_status = null;

    // If a claim exists for the user, fetch the claim status
    if ($claim_status_result->num_rows > 0) {
        $claim_data = $claim_status_result->fetch_assoc();
        $claim_status = $claim_data['claim_status'];
    }

    // Query to check if the user has any past claims (regardless of status)
    $past_claims_sql = "SELECT claim_id FROM claims WHERE user_id = '$user_id'";
    $past_claims_result = $conn->query($past_claims_sql);
    $has_past_claims = $past_claims_result->num_rows > 0;

    // Close the connection
    $conn->close();

} 
else {
    // User is not logged in, redirect to login page
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* General Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            position: relative; /* Set position to allow overlay layering */
            color: #333;
            padding-bottom: 60px;
        }

        body::before {
            content: '';
            position: fixed; /* Covers the entire screen */
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('bg.jpg'); /* Replace with your image URL */
            background-size: cover;
            background-repeat: no-repeat;
            background-attachment: fixed;
            filter: brightness(50%); /* Dims the background */
            z-index: -1; /* Places it behind all content */
        }


        /* Header */
        header {
            /* background-color: rgba(0, 0, 0, 0.8); */
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
            background-color: rgba(0, 0, 0, 0.8); /* Same color as the navbar */
            color: white; /* Ensures text is readable against a dark background */
            padding: 30px; /* Adds inner spacing for a clean look */
            border-radius: 8px; /* Optional: Adds rounded corners for aesthetic */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); /* Optional: Adds a subtle shadow effect */
            max-width: 1200px;
            margin: 20px auto; /* Centers the container */
            text-align: center; /* Aligns text in the container */
        }



        .welcome-message {
            text-align: center;
            margin-bottom: 30px;
        }

        .dashboard-content {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 20px;
        }

        .section {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 48%;
            box-sizing: border-box;
            transition: transform 0.3s, box-shadow 0.3s;
            color: black;
        }

        .section:hover {
            transform: translateY(-10px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }

        .section h3 {
            margin-top: 0;
            color: #333;
        }

        .arrow-container {
            display: inline-block;
            padding: 5px;
        }

        .arrow {
            font-size: 30px;
            margin-top: 15px;
            display: inline-block;
            cursor: pointer;
            transition: transform 0.3s ease;
            color: black;
        }

        .arrow:hover {
            transform: translateX(10px);
        }

        .btn-link {
            text-decoration: none;
            color: inherit;
        }

        /* Footer */
        footer {
            color: white;
            text-align: center;
            padding: 10px 0;
            position: fixed;
            bottom: 0;
            width: 100%;
            background-color: rgba(0, 0, 0, 0.8);
        }

        /* Chatbot Styles */
        .chatbot-icon {
            position: fixed;
            bottom: 80px;
            right: 30px;
            width: 60px;
            height: 60px;
            background-color: #000;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            z-index: 999;
            transition: all 0.3s ease;
        }

        .chatbot-icon:hover {
            transform: scale(1.1);
            background-color: #222;
        }

        .chatbot-icon i {
            color: white;
            font-size: 24px;
        }

        .chat-window {
            position: fixed;
            bottom: 80px;
            right: 30px;
            width: 350px;
            height: 450px;
            background-color: rgba(0, 0, 0, 0.8);
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            z-index: 999;
            display: none;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid #333;
        }

        .chat-header {
            background-color: #000;
            color: white;
            padding: 15px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #333;
        }

        .chat-close {
            cursor: pointer;
            font-size: 18px;
        }

        .chat-messages {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .message {
            max-width: 80%;
            padding: 10px 12px;
            border-radius: 18px;
            margin-bottom: 8px;
            word-wrap: break-word;
        }

        .bot-message {
            background-color: #333;
            color: white;
            align-self: flex-start;
            border-bottom-left-radius: 5px;
        }

        .user-message {
            background-color: #4d4d4d;
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 5px;
        }

        .chat-input-container {
            padding: 10px;
            display: flex;
            background-color: #222;
            border-top: 1px solid #333;
        }

        .chat-input {
            flex: 1;
            border: none;
            padding: 10px 15px;
            border-radius: 20px;
            outline: none;
            background-color: #333;
            color: white;
        }

        .send-button {
            border: none;
            background-color: transparent;
            color: white;
            margin-left: 10px;
            cursor: pointer;
            font-size: 20px;
        }

        /* Loading indicator for chatbot */
        .typing-indicator {
            display: flex;
            align-items: center;
            background-color: #333;
            color: white;
            border-radius: 18px;
            padding: 10px 12px;
            align-self: flex-start;
            border-bottom-left-radius: 5px;
            margin-bottom: 8px;
        }

        .typing-indicator span {
            height: 8px;
            width: 8px;
            float: left;
            margin: 0 1px;
            background-color: #9E9EA1;
            display: block;
            border-radius: 50%;
            opacity: 0.4;
        }

        .typing-indicator span:nth-of-type(1) {
            animation: 1s blink infinite 0.3333s;
        }

        .typing-indicator span:nth-of-type(2) {
            animation: 1s blink infinite 0.6666s;
        }

        .typing-indicator span:nth-of-type(3) {
            animation: 1s blink infinite 0.9999s;
        }

        @keyframes blink {
            50% {
                opacity: 1;
            }
        }
    </style>
</head>
<body>

<header>
    <h1>InsuraSync</h1>
    <nav>
        <a href="#">Home</a>
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
    <div class="welcome-message">
        <h2>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h2>
        <p>You are successfully logged in. Choose an option below:</p>
    </div>

    <div class="dashboard-content">
        <!-- Explore Policies -->
        <div class="section">
            <h3>Explore Policies</h3>
            <p>Discover more about available insurance policies.</p>
            <a href="view_policies.php" class="btn-link">
                <div class="arrow-container">
                    <i class="fas fa-arrow-right arrow"></i>
                </div>
            </a>
        </div>

        <!-- Active Policy -->
        <div class="section">
            <h3>Active Policy</h3>
            <?php if (!empty($policy_name)): ?>
            <p><strong>Policy Name:</strong> <?php echo htmlspecialchars($policy_name); ?></p>
            <p>Your active policy details are available.</p>
            <a href="view_personal_policy.php" class="btn-link">
                <i class="fas fa-arrow-right arrow"></i>
            </a>
            <?php else: ?>
                <p>You don't have an active policy yet. Explore policies to get started.</p>
            <?php endif; ?>
        </div>

        <!-- File Insurance Claim -->
        <div class="section">
    <h3>File Insurance Claim</h3>
    <?php if ($active_policy_id): ?>
        <?php if ($claim_status === 'rejected' || $claim_status === null || $claim_status === 'approved'): ?>
            <p>If you have been in an accident, feel free to file a claim.</p>

            <a href="file_claim.php?policyID=<?php echo urlencode($active_policy_id); ?>" class="btn-link">
            <i class="fas fa-arrow-right arrow"></i>
            </a>
        <?php else: ?>
            <p>We are processing your claims. We will get back to you shortly.</p>
        <?php endif; ?>
    <?php else: ?>
        <p>You don't have an active policy yet. Explore policies to get started.</p>
    <?php endif; ?>
</div>



        <!-- View All Claims -->
        <div class="section">
            <h3>View All Claims</h3>
            <?php if ($has_past_claims): ?>
                <a href="view_claim_status.php?policyID=<?php echo urlencode($active_policy_id); ?>" class="btn-link">
                <p>Click below to view all filed claims.</p>

                    <i class="fas fa-arrow-right arrow"></i>
                </a>
            <?php else: ?>
                <p>No claims available. File your first claim to view its status here.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer>
    <p>&copy; 2024 InsuraSync. All rights reserved.</p>
</footer>

<!-- Chatbot Icon -->
<div class="chatbot-icon" id="chatbotIcon">
    <i class="fas fa-comment-dots"></i>
</div>

<!-- Chat Window -->
<div class="chat-window" id="chatWindow">
    <div class="chat-header">
        <span>InsuraSync AI Assistant</span>
        <span class="chat-close" id="chatClose">&times;</span>
    </div>
    <div class="chat-messages" id="chatMessages">
        <div class="message bot-message">
            Hello! I'm your InsuraSync AI assistant. How can I help with your insurance needs today?
        </div>
    </div>
    <div class="chat-input-container">
        <input type="text" class="chat-input" id="chatInput" placeholder="Type your message...">
        <button class="send-button" id="sendMessage">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>

<script>
    // Chatbot functionality
    document.addEventListener('DOMContentLoaded', function() {
        const chatbotIcon = document.getElementById('chatbotIcon');
        const chatWindow = document.getElementById('chatWindow');
        const chatClose = document.getElementById('chatClose');
        const chatInput = document.getElementById('chatInput');
        const sendButton = document.getElementById('sendMessage');
        const chatMessages = document.getElementById('chatMessages');
        
        const API_URL = 'http://localhost:5002/api/chat';  // Update with your API URL
        
        // Toggle chat window
        chatbotIcon.addEventListener('click', function() {
            chatWindow.style.display = 'flex';
            chatbotIcon.style.display = 'none';
        });
        
        chatClose.addEventListener('click', function() {
            chatWindow.style.display = 'none';
            chatbotIcon.style.display = 'flex';
        });
        
        // Function to create typing indicator
        function createTypingIndicator() {
            const indicator = document.createElement('div');
            indicator.className = 'typing-indicator';
            indicator.innerHTML = '<span></span><span></span><span></span>';
            return indicator;
        }
        
        // Send message function
        function sendMessage() {
            const message = chatInput.value.trim();
            if (message !== '') {
                // Add user message
                const userMessageDiv = document.createElement('div');
                userMessageDiv.className = 'message user-message';
                userMessageDiv.textContent = message;
                chatMessages.appendChild(userMessageDiv);
                
                // Clear input
                chatInput.value = '';
                
                // Scroll to the bottom of chat
                chatMessages.scrollTop = chatMessages.scrollHeight;
                
                // Add typing indicator
                const typingIndicator = createTypingIndicator();
                chatMessages.appendChild(typingIndicator);
                chatMessages.scrollTop = chatMessages.scrollHeight;
                
                // Call API for bot response
                fetch(API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ message: message }),
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    // Remove typing indicator
                    chatMessages.removeChild(typingIndicator);
                    
                    // Add bot response
                    const botMessageDiv = document.createElement('div');
                    botMessageDiv.className = 'message bot-message';
                    botMessageDiv.textContent = data.response;
                    chatMessages.appendChild(botMessageDiv);
                    
                    // Scroll to the bottom again after bot responds
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                })
                .catch(error => {
                    // Remove typing indicator
                    chatMessages.removeChild(typingIndicator);
                    
                    // Show error message
                    const errorMessageDiv = document.createElement('div');
                    errorMessageDiv.className = 'message bot-message';
                    errorMessageDiv.textContent = "I'm having trouble connecting to my brain right now. Please try again later.";
                    chatMessages.appendChild(errorMessageDiv);
                    
                    console.error('Error:', error);
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                });
            }
        }
        
        // Send message on button click
        sendButton.addEventListener('click', sendMessage);
        
        // Send message on Enter key
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
        
        // Fallback functionality in case API is not available
        window.addEventListener('error', function(e) {
            console.error('Error in script execution:', e);
            
            // Override fetch if needed for fallback
            if (typeof fetch === 'undefined') {
                window.fetch = function() {
                    return new Promise(function(resolve, reject) {
                        reject(new Error('Fetch is not available'));
                    });
                };
            }
        });
    });
</script>

</body>
</html>