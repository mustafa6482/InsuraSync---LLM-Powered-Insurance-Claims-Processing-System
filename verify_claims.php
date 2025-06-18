<?php

// Add debugging here
error_log("Script started. Claim ID: " . (isset($_GET['id']) ? $_GET['id'] : 'not set'));
error_log("Download doc: " . (isset($_GET['download_doc']) ? $_GET['download_doc'] : 'not set'));
error_log("View doc: " . (isset($_GET['view_doc']) ? $_GET['view_doc'] : 'not set'));




// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$database = "insurasync";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get claim ID from URL
$claim_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// First, let's determine what the primary key is for claim_documents table
$table_info_sql = "SHOW COLUMNS FROM claim_documents";
$table_info_result = $conn->query($table_info_sql);
$primary_key = null;

if ($table_info_result && $table_info_result->num_rows > 0) {
    while ($column = $table_info_result->fetch_assoc()) {
        if ($column['Key'] == 'PRI') {
            $primary_key = $column['Field'];
            break;
        }
    }
}

// If we couldn't find a primary key, let's assume 'document_id' as a fallback
if (!$primary_key) {
    $primary_key = 'document_id';
}

// Handle document download if requested
if (isset($_GET['download_doc']) && is_numeric($_GET['download_doc'])) {
    $doc_id = intval($_GET['download_doc']);
    
    // Fetch document info from database
    $doc_sql = "SELECT document_name, document_type FROM claim_documents WHERE $primary_key = ? AND claim_id = ?";
    $doc_stmt = $conn->prepare($doc_sql);
    $doc_stmt->bind_param("ii", $doc_id, $claim_id);
    $doc_stmt->execute();
    $doc_result = $doc_stmt->get_result();
    
    if ($doc_result->num_rows > 0) {
        $doc = $doc_result->fetch_assoc();
        
        // Construct the file path using the new standardized format
// Inside the download handler, after this line:
$file_path = "C:\\xampp\\htdocs\\InsuraSync\\claim_documents\\{$claim_id}\\" . $doc['document_name'];

// Add these lines:
error_log("Download: Looking for file at: " . $file_path);
error_log("Download: File exists: " . (file_exists($file_path) ? 'Yes' : 'No'));        
        if (file_exists($file_path)) {
            // Set headers for download
            header("Content-Type: {$doc['document_type']}");
            header("Content-Disposition: attachment; filename=\"{$doc['document_name']}\"");
            header("Content-Length: " . filesize($file_path));
            
            // Disable output buffering to prevent memory issues with large files
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Output the file
            readfile($file_path);
            exit();
        } else {
            // For debugging, show the attempted path
            echo "File not found.<br>";
            echo "Attempted path: " . htmlspecialchars($file_path) . "<br>";
            echo "Claim ID: " . htmlspecialchars($claim_id) . "<br>";
            echo "Back to <a href='verify_claims.php?id={$claim_id}'>claim page</a>";
            exit();
        }
    }
    
    // If document not found in database, redirect back to verify page
    header("Location: verify_claims.php?id={$claim_id}&error=document_not_found");
    exit();
}

// Handle document viewing if requested
if (isset($_GET['view_doc']) && is_numeric($_GET['view_doc'])) {
    $doc_id = intval($_GET['view_doc']);
    
    // Fetch document info from database
    $doc_sql = "SELECT document_name, document_type FROM claim_documents WHERE $primary_key = ? AND claim_id = ?";
    $doc_stmt = $conn->prepare($doc_sql);
    $doc_stmt->bind_param("ii", $doc_id, $claim_id);
    $doc_stmt->execute();
    $doc_result = $doc_stmt->get_result();
    
    if ($doc_result->num_rows > 0) {
        $doc = $doc_result->fetch_assoc();
        
        // Construct the file path using the new standardized format
// Inside the view handler, after this line:
$file_path = "C:\\xampp\\htdocs\\InsuraSync\\claim_documents\\{$claim_id}\\" . $doc['document_name'];

// Add these lines:
error_log("View: Looking for file at: " . $file_path);
error_log("View: File exists: " . (file_exists($file_path) ? 'Yes' : 'No'));        
        if (file_exists($file_path)) {
            // Set headers for viewing
            header("Content-Type: {$doc['document_type']}");
            header("Content-Length: " . filesize($file_path));
            
            // Disable output buffering to prevent memory issues with large files
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Output the file
            readfile($file_path);
            exit();
        } else {
            // For debugging, show the attempted path
            echo "File not found.<br>";
            echo "Attempted path: " . htmlspecialchars($file_path) . "<br>";
            echo "Claim ID: " . htmlspecialchars($claim_id) . "<br>";
            echo "Back to <a href='verify_claims.php?id={$claim_id}'>claim page</a>";
            exit();
        }
    }
    
    // If document not found in database, redirect back to verify page
    header("Location: verify_claims.php?id={$claim_id}&error=document_not_found");
    exit();
}

// Check if form was submitted to update verification status
$status_message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verification_action'])) {
    $action = $_POST['verification_action'];
    
    if ($action == "verify" || $action == "reject") {
        $new_status = ($action == "verify") ? "verified" : "rejected";
        
        // Update the verification status in the database
        $update_sql = "UPDATE claims SET verification_status = ? WHERE claim_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $new_status, $claim_id);
        
        if ($stmt->execute()) {
            $status_message = "Claim has been " . $new_status . " successfully!";
        } else {
            $status_message = "Error updating claim: " . $conn->error;
        }
        
        $stmt->close();
    }
}

// Get claim details
$sql = "SELECT * FROM claims WHERE claim_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $claim_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Claim not found
    header("Location: view_claims_staff.php");
    exit();
}

$claim = $result->fetch_assoc();

// Fetch user data
$user_sql = "SELECT * FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $claim['user_id']);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

// Fetch policy data
$policy_sql = "SELECT * FROM policies WHERE policyID = ?";
$policy_stmt = $conn->prepare($policy_sql);
$policy_stmt->bind_param("i", $claim['policy_id']);
$policy_stmt->execute();
$policy_result = $policy_stmt->get_result();
$policy = $policy_result->fetch_assoc();

// Fetch claim documents - Note: we don't need document_path anymore since we're using a standard path
$doc_sql = "SELECT $primary_key, document_name, document_type FROM claim_documents WHERE claim_id = ?";
$doc_stmt = $conn->prepare($doc_sql);
$doc_stmt->bind_param("i", $claim_id);
$doc_stmt->execute();
$doc_result = $doc_stmt->get_result();
$documents = [];
while ($doc = $doc_result->fetch_assoc()) {
    $documents[] = $doc;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Claim - Staff</title>
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
        }

        .claim-details {
            margin-bottom: 30px;
            text-align: left;
        }

        .claim-details table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .claim-details th {
            text-align: left;
            padding: 10px;
            background-color: #1a1a2e;
            width: 35%;
        }

        .claim-details td {
            padding: 10px;
            background-color: rgba(40, 40, 75, 0.6);
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }

        .action-btn {
            padding: 12px 25px;
            text-decoration: none;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .verify-btn {
            background-color: #4CAF50;
        }

        .verify-btn:hover {
            background-color: #3e8e41;
        }

        .reject-btn {
            background-color: #f44336;
        }

        .reject-btn:hover {
            background-color: #d32f2f;
        }

        .back-btn {
            background-color: #607D8B;
        }

        .back-btn:hover {
            background-color: #455A64;
        }

        .status-message {
            margin-top: 20px;
            padding: 15px;
            border-radius: 4px;
            text-align: center;
            font-weight: bold;
        }

        .status-success {
            background-color: rgba(76, 175, 80, 0.3);
            border: 1px solid #4CAF50;
            color: #A5D6A7;
        }

        .status-error {
            background-color: rgba(244, 67, 54, 0.3);
            border: 1px solid #f44336;
            color: #EF9A9A;
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
        
        .category-low {
            color: #90ee90; /* Light green */
        }
        
        .category-moderate {
            color: #ffcb6b; /* Amber */
        }
        
        .category-high {
            color: #ff6b6b; /* Light red */
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.7);
        }
        
        .modal-content {
            background-color: rgba(25, 25, 50, 0.95);
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 400px;
            text-align: center;
        }
        
        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }

        /* Document section styles */
        .documents-section {
            margin-top: 30px;
            border-top: 1px solid #3a3a5e;
            padding-top: 20px;
        }

        .documents-section h3 {
            margin-bottom: 15px;
            color: #60a5fa;
        }

        .documents-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        .document-card {
            background-color: rgba(40, 40, 75, 0.8);
            border-radius: 6px;
            padding: 15px;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .document-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .document-icon {
            font-size: 36px;
            margin-bottom: 10px;
            text-align: center;
            color: #60a5fa;
        }

        .document-name {
            font-size: 14px;
            margin-bottom: 10px;
            word-break: break-word;
            text-align: center;
        }

        .document-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: auto;
        }

        .document-btn {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            text-decoration: none;
            color: white;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .view-btn {
            background-color: #2196F3;
        }

        .view-btn:hover {
            background-color: #0b7dda;
        }

        .download-btn {
            background-color: #4CAF50;
        }

        .download-btn:hover {
            background-color: #3e8e41;
        }

        .no-documents {
            text-align: center;
            padding: 20px;
            background-color: rgba(40, 40, 75, 0.6);
            border-radius: 6px;
            font-style: italic;
            color: #aaa;
        }

        /* Document viewer modal */
        .document-modal {
            display: none;
            position: fixed;
            z-index: 2;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.9);
            overflow: auto;
        }

        .document-modal-content {
            position: relative;
            background-color: rgba(25, 25, 50, 0.98);
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 900px;
            height: 80%;
            display: flex;
            flex-direction: column;
        }

        .document-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #3a3a5e;
        }

        .document-header h3 {
            margin: 0;
            color: #60a5fa;
        }

        .close-document {
            color: #aaa;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close-document:hover {
            color: #fff;
        }

        .document-iframe {
            flex: 1;
            width: 100%;
            height: 100%;
            border: none;
            background-color: white;
        }

        .document-download-btn {
            margin-left: 15px;
            padding: 5px 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .document-download-btn:hover {
            background-color: #3e8e41;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Staff Dashboard</h2>
        <a href="verification_claims.php">Verify Claims</a>
        <a href="staff_login.php">Logout</a>
    </div>

    <div class="main-content">
        <header>
            <h1>InsuraSync - Technical Staff Dashboard</h1>
        </header>

        <div class="container">
            <h2>Verify Claim #<?php echo htmlspecialchars($claim_id); ?></h2>
            
            <?php if (!empty($status_message)): ?>
                <div class="status-message <?php echo strpos($status_message, "successfully") !== false ? "status-success" : "status-error"; ?>">
                    <?php echo $status_message; ?>
                    <?php if (strpos($status_message, "successfully") !== false): ?>
                        <p>Redirecting to claims dashboard in <span id="countdown">5</span> seconds...</p>
                        <script>
                            let seconds = 5;
                            const countdownElement = document.getElementById('countdown');
                            
                            const countdown = setInterval(() => {
                                seconds--;
                                countdownElement.textContent = seconds;
                                
                                if (seconds <= 0) {
                                    clearInterval(countdown);
                                    window.location.href = 'verification_claims.php';
                                }
                            }, 1000);
                        </script>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="claim-details">
                <table>
                    <tr>
                        <th>Claim ID:</th>
                        <td><?php echo htmlspecialchars($claim['claim_id']); ?></td>
                    </tr>
                    <tr>
                        <th>Policy Number:</th>
                        <td>
                            <?php if (isset($policy) && isset($policy['policy_number'])): ?>
                                <?php echo htmlspecialchars($policy['policy_number']); ?>
                            <?php else: ?>
                                Policy ID: <?php echo htmlspecialchars($claim['policy_id']); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Claimant:</th>
                        <td>
                            <?php if (isset($user) && isset($user['name'])): ?>
                                <?php echo htmlspecialchars($user['name']); ?>
                            <?php elseif (isset($user) && isset($user['username'])): ?>
                                <?php echo htmlspecialchars($user['username']); ?>
                            <?php endif; ?>
                            (ID: <?php echo htmlspecialchars($claim['user_id']); ?>)
                        </td>
                    </tr>
                    <tr>
                        <th>Filing Date:</th>
                        <td><?php echo htmlspecialchars($claim['filing_date']); ?></td>
                    </tr>
                    <tr>
                        <th>Accident Date:</th>
                        <td><?php echo htmlspecialchars($claim['accident_date']); ?></td>
                    </tr>
                    <tr>
                        <th>Accident Description:</th>
                        <td><?php echo htmlspecialchars($claim['accident_description']); ?></td>
                    </tr>
                    <tr>
                        <th>Damaged Parts:</th>
                        <td><?php echo htmlspecialchars($claim['damaged_parts']); ?></td>
                    </tr>
                    <tr>
                        <th>Vehicle Reg. Number:</th>
                        <td><?php echo htmlspecialchars($claim['vehicle_reg_num']); ?></td>
                    </tr>
                    <tr>
                        <th>CNIC:</th>
                        <td><?php echo htmlspecialchars($claim['cnic']); ?></td>
                    </tr>
                    <tr>
                        <th>Claim Amount:</th>
                        <td><?php echo htmlspecialchars('$' . number_format($claim['claim_amount'], 2)); ?></td>
                    </tr>
                    <tr>
                        <th>Category:</th>
                        <?php 
                            $category_class = '';
                            if ($claim['claim_category'] == 'low cost') {
                                $category_class = 'category-low';
                            } elseif ($claim['claim_category'] == 'moderate cost') {
                                $category_class = 'category-moderate';
                            } elseif ($claim['claim_category'] == 'high cost') {
                                $category_class = 'category-high';
                            }
                        ?>
                        <td class="<?php echo $category_class; ?>"><?php echo htmlspecialchars($claim['claim_category']); ?></td>
                    </tr>
                    <tr>
                        <th>Current Status:</th>
                        <td>
                            Claim Status: <?php echo htmlspecialchars($claim['claim_status']); ?><br>
                            Verification Status: <?php echo htmlspecialchars($claim['verification_status']); ?>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Documents Section -->
            <div class="documents-section">
                <h3><i class="fas fa-file-alt"></i> Supporting Documents</h3>
                
                <?php if (count($documents) > 0): ?>
                    <div class="documents-list">
                        <?php foreach ($documents as $doc): ?>
                            <div class="document-card">
                                <div class="document-icon">
                                    <?php
                                    // Determine icon based on file type
                                    $icon = 'fa-file';
                                    $type = strtolower($doc['document_type']);
                                    
                                    if (strpos($type, 'pdf') !== false) {
                                        $icon = 'fa-file-pdf';
                                    } elseif (strpos($type, 'image') !== false) {
                                        $icon = 'fa-file-image';
                                    } elseif (strpos($type, 'word') !== false || strpos($type, 'doc') !== false) {
                                        $icon = 'fa-file-word';
                                    } elseif (strpos($type, 'excel') !== false || strpos($type, 'sheet') !== false || strpos($type, 'csv') !== false) {
                                        $icon = 'fa-file-excel';
                                    } elseif (strpos($type, 'text') !== false) {
                                        $icon = 'fa-file-alt';
                                    }
                                    ?>
                                    <i class="fas <?php echo $icon; ?>"></i>
                                </div>
                                <div class="document-name" title="<?php echo htmlspecialchars($doc['document_name']); ?>">
                                    <?php echo htmlspecialchars($doc['document_name']); ?>
                                </div>
                                <div class="document-actions">
                                    <a href="#" class="document-btn view-btn" onclick="viewDocument(<?php echo $doc[$primary_key]; ?>, '<?php echo htmlspecialchars(addslashes($doc['document_name'])); ?>')">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="verify_claims.php?id=<?php echo $claim_id; ?>&download_doc=<?php echo $doc[$primary_key]; ?>" class="document-btn download-btn">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-documents">
                        <i class="fas fa-exclamation-circle"></i> No supporting documents attached to this claim.
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($claim['verification_status'] == 'pending'): ?>
                <div class="action-buttons">
                    <button class="action-btn verify-btn" onclick="confirmAction('verify')">Verify Claim</button>
                    <button class="action-btn reject-btn" onclick="confirmAction('reject')">Reject Claim</button>
                    <a href="view_claims_staff.php" class="action-btn back-btn">Back to Claims</a>
                </div>
            <?php else: ?>
                <div class="action-buttons">
                    <a href="view_claims_staff.php" class="action-btn back-btn">Back to Claims</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <h3 id="modalMessage">Are you sure you want to proceed?</h3>
            <div class="modal-buttons">
                <form method="post" id="verificationForm">
                    <input type="hidden" name="verification_action" id="verification_action" value="">
                    <button type="submit" class="action-btn verify-btn">Confirm</button>
                    <button type="button" class="action-btn back-btn" onclick="closeModal()">Cancel</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Document Viewer Modal -->
    <div id="documentModal" class="document-modal">
        <div class="document-modal-content">
            <div class="document-header">
                <h3 id="documentTitle">Document Preview</h3>
                <div>
                    <a id="documentDownloadLink" href="#" class="document-download-btn">
                        <i class="fas fa-download"></i> Download
                    </a>
                    <span class="close-document" onclick="closeDocumentModal()">&times;</span>
                </div>
            </div>
            <iframe id="documentIframe" class="document-iframe" src=""></iframe>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 InsuraSync. All rights reserved.</p>
    </footer>

    <script>
        function confirmAction(action) {
            const modal = document.getElementById('confirmationModal');
            const modalMessage = document.getElementById('modalMessage');
            const verificationAction = document.getElementById('verification_action');
            
            if (action === 'verify') {
                modalMessage.textContent = 'Are you sure you want to verify this claim?';
            } else {
                modalMessage.textContent = 'Are you sure you want to reject this claim?';
            }
            
            verificationAction.value = action;
            modal.style.display = 'block';
        }
        
        function closeModal() {
            const modal = document.getElementById('confirmationModal');
            modal.style.display = 'none';
        }
        
        // Document viewing functions
        function viewDocument(docId, docName) {
            const modal = document.getElementById('documentModal');
            const iframe = document.getElementById('documentIframe');
            const title = document.getElementById('documentTitle');
            const downloadLink = document.getElementById('documentDownloadLink');
            
            // Set the iframe source to view the document
            iframe.src = `verify_claims.php?id=<?php echo $claim_id; ?>&view_doc=${docId}`;
            
            // Set the document title
            title.textContent = docName;
            
            // Set the download link
            downloadLink.href = `verify_claims.php?id=<?php echo $claim_id; ?>&download_doc=${docId}`;
            
            // Show the modal
            modal.style.display = 'block';
        }
        
        function closeDocumentModal() {
            const modal = document.getElementById('documentModal');
            const iframe = document.getElementById('documentIframe');
            
            // Clear the iframe source
            iframe.src = '';
            
            // Hide the modal
            modal.style.display = 'none';
        }
        
        // Close modals if user clicks outside of them
        window.onclick = function(event) {
            const confirmModal = document.getElementById('confirmationModal');
            const docModal = document.getElementById('documentModal');
            
            if (event.target == confirmModal) {
                confirmModal.style.display = 'none';
            }
            
            if (event.target == docModal) {
                closeDocumentModal();
            }
        }
        </script>
</body>
</html>