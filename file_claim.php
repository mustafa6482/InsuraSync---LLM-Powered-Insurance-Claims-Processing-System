<?php
session_start();

// Ensure user_id is set
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Ensure policyID is set
if (isset($_GET['policyID'])) {
    $policyID = $_GET['policyID'];
} else {
    echo "Error: Policy ID is missing.";
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "insurasync";
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch policy name
$policy_name_sql = "SELECT policy_name FROM policies WHERE policyID = ?";
$stmt = $conn->prepare($policy_name_sql);
$stmt->bind_param("i", $policyID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $policy_name_row = $result->fetch_assoc();
    $policy_name = $policy_name_row['policy_name'];
} else {
    echo "Policy not found.";
    exit();
}

// Handling the claim form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $accident_date = $_POST['accident_date'];
    $accident_description = $_POST['accident_description'];
    $claim_amount = $_POST['claim_amount'];
    $cnic = $_POST['cnic'];
    $vehicle_reg_num = $_POST['vehicle_reg_num'];
    
    // Determine claim category based on claim amount
    if ($claim_amount < 50000) {
        $claim_category = "low cost";
    } elseif ($claim_amount >= 50000 && $claim_amount < 100000) {
        $claim_category = "moderate cost";
    } else {
        $claim_category = "high cost";
    }
    
    // Fix for damaged parts handling
    if (isset($_POST['damaged_parts'])) {
        // If it's coming as a string (from our hidden input)
        $damaged_parts = $_POST['damaged_parts'];
    } else {
        $damaged_parts = "";
    }

    // Set filing date
    $file_date = date("Y-m-d");

    // Default claim status
    $claim_status = "pending";
    // Set verification status to pending
    $verification_status = "pending";

    // Fetch user details to check CNIC and Vehicle Reg
    $sql = "SELECT cnic, vehicle_reg FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();

        // Check for discrepancies
        if ($user_data['cnic'] !== $cnic || $user_data['vehicle_reg'] !== $vehicle_reg_num) {
            $claim_status = "pending"; 
        }
    } 
    else {
        echo "User not found.";
        exit();
    }

    // Insert claim into database
    $sql = "INSERT INTO claims (user_id, policy_id, accident_date, accident_description, claim_amount, claim_status, filing_date, damaged_parts, cnic, vehicle_reg_num, claim_category, verification_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissdsssssss", $user_id, $policyID, $accident_date, $accident_description, $claim_amount, $claim_status, $file_date, $damaged_parts, $cnic, $vehicle_reg_num, $claim_category, $verification_status);
    
    if ($stmt->execute()) {
        // Get the claim_id of the newly inserted claim
        $claim_id = $conn->insert_id;
        
        // Create a directory for this claim's documents if it doesn't exist
        $upload_dir = "claim_documents/" . $claim_id;
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Process uploaded documents
if (isset($_FILES['documents'])) {
    $file_count = count($_FILES['documents']['name']);
    $uploaded_files = array(); // Track uploaded files for database insertion
    
    // Loop through each uploaded file
    for ($i = 0; $i < $file_count; $i++) {
        if ($_FILES['documents']['error'][$i] == 0) {
            $document_name = $_FILES['documents']['name'][$i];
            $document_type = $_FILES['documents']['type'][$i];
            $document_tmp_name = $_FILES['documents']['tmp_name'][$i];
            
            // Use the original filename
            $file_path = $upload_dir . '/' . $document_name;
            
            // Handle duplicate filenames if they exist
            $counter = 1;
            $file_extension = pathinfo($document_name, PATHINFO_EXTENSION);
            $file_basename = pathinfo($document_name, PATHINFO_FILENAME);
            
            while (file_exists($file_path)) {
                $new_name = $file_basename . '(' . $counter . ').' . $file_extension;
                $file_path = $upload_dir . '/' . $new_name;
                $counter++;
            }
            
            // Get the final filename (either original or with counter)
            $final_filename = basename($file_path);
            
            // Move the uploaded file to the claim folder
            if (move_uploaded_file($document_tmp_name, $file_path)) {
                // Add to the list of uploaded files
                $uploaded_files[] = array(
                    'name' => $final_filename, // This might be the original or a numbered version
                    'type' => $document_type,
                    'path' => $file_path
                );
            }
        }
    }
    
    // Insert document references into the database
    if (!empty($uploaded_files)) {
        $doc_sql = "INSERT INTO claim_documents (claim_id, document_name, document_type, document_path) VALUES (?, ?, ?, ?)";
        $doc_stmt = $conn->prepare($doc_sql);
        
        foreach ($uploaded_files as $file) {
            $doc_stmt->bind_param("isss", $claim_id, $file['name'], $file['type'], $file['path']);
            $doc_stmt->execute();
        }
        
        $doc_stmt->close();
    }
}
        
        header("Location: user_dashboard.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close connection
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File a Claim</title>
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden; /* Prevent the page from scrolling as a whole */
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-image: url('bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: #fff;
            height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            
        }

        body::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.0);
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

        .main-content {
            padding: 30px;
            color: #fff;
            display: flex;
            gap: 20px;
            flex-grow: 1;
            overflow-y: auto; /* Enable scrolling */
            max-height: calc(100vh - 120px); /* Adjust for header and footer */
        }   

        .instructions {
            flex: 1;
            min-width: 300px;
            font-family: 'Arial', sans-serif;
            font-size: 18px;
            line-height: 1.8;
            color: #f1f1f1;
            background-color: rgba(0, 0, 0, 0.8);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            text-align: left;
        }

        .instructions h2 {
            font-family: 'Arial', sans-serif;
            font-size: 24px;
            font-weight: bold;
            color: #ffffff;
            margin-bottom: 15px;
        }

        .instructions ul {
            list-style-type: square;
            margin-left: 20px;
            font-size: 18px;
        }

        .instructions li {
            margin-bottom: 12px;
        }

        .form-container {
            flex: 1;
            background-color: rgba(255, 255, 255, 0.9);
            color: #333;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            margin: 0;
            box-sizing: border-box;
            height: vh; /* Adjust as needed */
            overflow-y: auto; /* Enables scrolling inside the form */
            margin-bottom: 0px;
        }


        .form-container h3 {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }

        .form-container label {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 8px;
            display: block;
            color: #555;
        }

        .form-container input,
        .form-container textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }

        .form-container input:focus,
        .form-container textarea:focus {
            border-color: #007bff;
            outline: none;
        }

        .form-container button {
            width: 100%;
            padding: 12px;
            background-color: #000;
            color: white;
            font-size: 16px;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .form-container button:hover {
            background-color: #444;
        }

        .back-btn {
            display: block;
            text-align: center;
            margin-top: 15px;
            padding: 12px;
            background-color: #f44336;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .back-btn:hover {
            background-color: #d32f2f;
        }

        footer {
            background-color: #000;
            color: white;
            text-align: center;
            padding: 10px 0;
            position: relative;
            bottom: 0;
            width: 100%;
            z-index: 10;
        }

        @media (max-width: 768px) {
            .main-content {
                flex-direction: column;
            }
        }
        #selected-parts {
            display: flex;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        /* Selected Parts (CC-Style Tags) */
        .selected-part {
            background: #007bff;
            color: white;
            padding: 8px 12px;
            margin: 5px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            font-size: 14px;
            font-weight: bold;
            transition: background 0.3s ease;
        }

        .remove-part {
            margin-left: 10px;
            cursor: pointer;
            font-weight: bold;
            background: #fff;
            color: #007bff;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: background 0.3s ease, color 0.3s ease;
        }

        /* Hover Effect for Remove Button */
        .remove-part:hover {
            background: #ff4444;
            color: white;
        }

        #damaged_parts {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 2px solid #007bff;
            border-radius: 5px;
            background: #fff;
            color: #333;
            cursor: pointer;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        /* Hover & Focus Effects */
        #damaged_parts:hover,
        #damaged_parts:focus {
            border-color: #0056b3;
            outline: none;
            box-shadow: 0 0 8px rgba(0, 91, 187, 0.4);
        }

        /* File upload styles */
        .file-upload-container {
            margin-bottom: 20px;
        }

        .file-label {
            display: block;
            background-color: #007bff;
            color: white;
            text-align: center;
            padding: 12px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-bottom: 10px;
        }

        .file-label:hover {
            background-color: #0056b3;
        }

        .file-input {
            display: none;
        }

        .file-list {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
            max-height: 150px;
            overflow-y: auto;
            background-color: #f9f9f9;
        }

        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px;
            border-bottom: 1px solid #eee;
        }

        .file-item:last-child {
            border-bottom: none;
        }

        .file-name {
            flex-grow: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .remove-file {
            background-color: #ff4444;
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            margin-left: 10px;
            transition: background-color 0.3s;
        }

        .remove-file:hover {
            background-color: #cc0000;
        }

        .no-files {
            color: #888;
            font-style: italic;
            text-align: center;
            padding: 10px;
        }
    </style>
</head>
<body>
<header>
    <h1>InsuraSync</h1>
    <nav>
        <a href="user_dashboard.php">Home</a>
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

<div class="main-content">
    <div class="instructions">
        <h2>Policy: <?php echo htmlspecialchars($policy_name); ?></h2>
        <p>Here are the instructions or guidelines to file your claim. Please fill in all the required fields below carefully.</p>
        <ul>
            <li>Ensure the claim is for the same vehicle that the policy is registered under.</li>
            <li>Provide accurate and truthful details to avoid fraud. False claims will be rejected.</li>
            <li>Upload valid supporting documents to substantiate your claim.</li>
            <li>Double-check the CNIC and vehicle registration number for accuracy.</li>
            <li>Claims with discrepancies or missing details may face delays or rejection.</li>
            <li>Contact customer support if you encounter any issues while filing your claim.</li>
            <li>Make sure the accident description is clear and concise.</li>
            <li>All claims are subject to verification and approval by the insurer.</li>
        </ul>
    </div>

    <div class="form-container">
        <h3>Submit Your Claim</h3>
        <form id="claim-form" action="file_claim.php?policyID=<?php echo urlencode($policyID); ?>" method="POST" enctype="multipart/form-data">
            <label for="accident_date">Accident Date</label>
            <input type="date" id="accident_date" name="accident_date" required>

            <label for="accident_description">Accident Description</label>
            <textarea id="accident_description" name="accident_description" rows="5" required></textarea>

            <label for="claim_amount">Claim Amount</label>
            <input type="number" id="claim_amount" name="claim_amount" step="0.01" required>

            <div class="file-upload-container">
                <label for="documents" class="file-label">Select Supporting Documents</label>
                <input type="file" id="documents" name="documents[]" class="file-input" multiple>
                <div class="file-list" id="file-list">
                    <div class="no-files">No files selected</div>
                </div>
            </div>

            <label for="cnic">CNIC</label>
            <input type="text" id="cnic" name="cnic" required>

            <label for="vehicle_reg_num">Vehicle Registration Number</label>
            <input type="text" id="vehicle_reg_num" name="vehicle_reg_num" required>

            <label for="damaged_parts">Damaged Car Parts</label>
            <div id="damaged-parts-container">
                <select id="damaged_parts">
                    <option value="" disabled selected>📌 Select a damaged part</option>
                    <option value="Front Bumper">🚗 Front Bumper</option>
                    <option value="Rear Bumper">🚗 Rear Bumper</option>
                    <option value="Left Headlight">💡 Left Headlight</option>
                    <option value="Right Headlight">💡 Right Headlight</option>
                    <option value="Windshield">🪟 Windshield</option>
                    <option value="Left Door">🚪 Left Door</option>
                    <option value="Right Door">🚪 Right Door</option>
                    <option value="Side Mirror">🔍 Side Mirror</option>
                    <option value="Hood">🛠 Hood</option>
                    <option value="Trunk">🎒 Trunk</option>
                </select>
            </div>
            <div id="selected-parts"></div>
            <input type="hidden" name="damaged_parts" id="hidden-damaged-parts">

            <button type="submit">Submit Claim</button>
        </form>
    </div>
</div>

<footer>
    <p>&copy; 2024 InsuraSync. All rights reserved.</p>
</footer>

<script>
    document.addEventListener("DOMContentLoaded", function () {
    // Get form element
    const claimForm = document.getElementById("claim-form");
    
    // Damaged parts selection handling (unchanged)
    const select = document.getElementById("damaged_parts");
    const selectedPartsContainer = document.getElementById("selected-parts");
    const hiddenInput = document.getElementById("hidden-damaged-parts");
    let selectedParts = [];

    select.addEventListener("change", function () {
        const part = select.value;
        if (part && !selectedParts.includes(part)) {
            selectedParts.push(part);
            updateSelectedParts();
        }
        select.value = ""; 
    });

    function updateSelectedParts() {
        selectedPartsContainer.innerHTML = "";
        selectedParts.forEach((part, index) => {
            const partElement = document.createElement("div");
            partElement.textContent = part;
            partElement.classList.add("selected-part");

            const removeBtn = document.createElement("span");
            removeBtn.textContent = "×";
            removeBtn.classList.add("remove-part");
            removeBtn.addEventListener("click", function () {
                selectedParts.splice(index, 1);
                updateSelectedParts();
            });

            partElement.appendChild(removeBtn);
            selectedPartsContainer.appendChild(partElement);
        });

        // Set the comma-separated string to the hidden input
        hiddenInput.value = selectedParts.join(", "); 
    }

    // File upload handling
    const fileInput = document.getElementById('documents');
    const fileList = document.getElementById('file-list');
    let files = new DataTransfer(); // Use `let` to allow reassignment

    fileInput.addEventListener('change', function() {
        updateFileList();
    });

    function updateFileList() {
        // Add newly selected files to our FileList
        Array.from(fileInput.files).forEach(file => {
            // Check if the file is already in the list to avoid duplicates
            if (!Array.from(files.files).some(existingFile => existingFile.name === file.name && existingFile.size === file.size)) {
                files.items.add(file);
            }
        });

        // Update the file input with our managed list
        fileInput.files = files.files;

        // Update the visual list
        renderFileList();
    }

    function renderFileList() {
        // Clear the current list
        fileList.innerHTML = '';

        if (files.files.length === 0) {
            const noFiles = document.createElement('div');
            noFiles.className = 'no-files';
            noFiles.textContent = 'No files selected';
            fileList.appendChild(noFiles);
            return;
        }

        // Add each file to the list
        Array.from(files.files).forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';

            const fileName = document.createElement('div');
            fileName.className = 'file-name';
            fileName.textContent = file.name;

            const removeButton = document.createElement('button');
            removeButton.className = 'remove-file';
            removeButton.textContent = '×';
            removeButton.type = 'button';
            removeButton.addEventListener('click', function() {
                removeFile(index);
            });

            fileItem.appendChild(fileName);
            fileItem.appendChild(removeButton);
            fileList.appendChild(fileItem);
        });
    }

    function removeFile(index) {
        // Create a new DataTransfer object
        const newFiles = new DataTransfer();

        // Add all files except the one to be removed
        Array.from(files.files).forEach((file, i) => {
            if (i !== index) {
                newFiles.items.add(file);
            }
        });

        // Replace our files object with the new one
        files = newFiles;

        // Update the file input
        fileInput.files = files.files;

        // Update the visual list
        renderFileList();
    }

    // --- NEW VALIDATION CODE ---
    
    // Form submission validation
    claimForm.addEventListener("submit", function(event) {
        // Prevent form submission initially
        event.preventDefault();
        
        let isValid = true;
        let errorMessage = "";
        
        // Validate accident date (not more than a week old)
        const accidentDate = new Date(document.getElementById("accident_date").value);
        const currentDate = new Date();
        const oneWeekAgo = new Date();
        oneWeekAgo.setDate(currentDate.getDate() - 7);
        
        if (accidentDate < oneWeekAgo || accidentDate > currentDate) {
            errorMessage += "• Accident date must be within the last week and not in the future.\n";
            isValid = false;
            document.getElementById("accident_date").classList.add("error-field");
        } else {
            document.getElementById("accident_date").classList.remove("error-field");
        }
        
        // Validate claim amount (not negative or absurdly high)
        const claimAmount = parseFloat(document.getElementById("claim_amount").value);
        if (isNaN(claimAmount) || claimAmount <= 0 || claimAmount > 1000000) {
            errorMessage += "• Claim amount must be positive and reasonable (max 1,000,000).\n";
            isValid = false;
            document.getElementById("claim_amount").classList.add("error-field");
        } else {
            document.getElementById("claim_amount").classList.remove("error-field");
        }
        
        // Validate CNIC (13 digits only)
        const cnic = document.getElementById("cnic").value;
        const cnicRegex = /^\d{13}$/;
        if (!cnicRegex.test(cnic)) {
            errorMessage += "• CNIC must be exactly 13 digits.\n";
            isValid = false;
            document.getElementById("cnic").classList.add("error-field");
        } else {
            document.getElementById("cnic").classList.remove("error-field");
        }
        
        // Validate vehicle registration number (3 numbers followed by 3 alphabets or vice versa)
        const vehicleRegNum = document.getElementById("vehicle_reg_num").value;
        const regNumRegex = /^([A-Za-z]{3}\d{3}|\d{3}[A-Za-z]{3})$/;
        if (!regNumRegex.test(vehicleRegNum)) {
            errorMessage += "• Vehicle registration number must be 6 characters (3 numbers and 3 alphabets).\n";
            isValid = false;
            document.getElementById("vehicle_reg_num").classList.add("error-field");
        } else {
            document.getElementById("vehicle_reg_num").classList.remove("error-field");
        }
        
        // Validate if at least one damaged part is selected
        if (selectedParts.length === 0) {
            errorMessage += "• Please select at least one damaged part.\n";
            isValid = false;
            document.getElementById("damaged_parts").classList.add("error-field");
        } else {
            document.getElementById("damaged_parts").classList.remove("error-field");
        }
        
        // Display error message or submit form
        if (!isValid) {
            showValidationErrors(errorMessage);
        } else {
            // If all validations pass, submit the form
            claimForm.submit();
        }
    });
    
    // Function to display validation errors
    function showValidationErrors(message) {
        // Check if error message element already exists
        let errorElement = document.getElementById("validation-errors");
        if (!errorElement) {
            // Create error element if it doesn't exist
            errorElement = document.createElement("div");
            errorElement.id = "validation-errors";
            errorElement.className = "validation-errors";
            claimForm.insertBefore(errorElement, claimForm.firstChild);
        }
        
        // Set error message content
        errorElement.innerHTML = "<h4>Please correct the following errors:</h4><pre>" + message + "</pre>";
        
        // Scroll to top of form to show errors
        errorElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Add CSS for error styling
    const style = document.createElement('style');
    style.textContent = `
        .validation-errors {
            background-color: #ffebee;
            color: #d32f2f;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border-left: 5px solid #f44336;
        }
        
        .validation-errors h4 {
            margin-top: 0;
            margin-bottom: 10px;
            color: #d32f2f;
        }
        
        .validation-errors pre {
            margin: 0;
            white-space: pre-wrap;
            font-family: Arial, sans-serif;
            line-height: 1.5;
        }
        
        .error-field {
            border: 2px solid #f44336 !important;
            background-color: #fff8f8;
        }
        
        .error-field:focus {
            border-color: #f44336 !important;
            box-shadow: 0 0 0 2px rgba(244, 67, 54, 0.25) !important;
        }
    `;
    document.head.appendChild(style);
});
</script>
</body>
</html>