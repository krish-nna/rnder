<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1); // Enable during development
ini_set('log_errors', 1); // Log errors to the server's error log
ini_set('error_log', 'php_errors.log'); // Specify the error log file

// Include database configuration
require 'db_config.php';

// Include PhpSpreadsheet library
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// Function to send a JSON response
function sendResponse($success, $error = "") {
    echo json_encode(["success" => $success, "error" => $error]);
    exit;
}

// Establish PostgreSQL connection
error_log("Attempting to connect to the database...");
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");
if (!$conn) {
    error_log("Database connection failed: " . pg_last_error());
    sendResponse(false, "Database connection failed: " . pg_last_error());
}
error_log("Database connection successful.");

// Check if the file was uploaded successfully
if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] != 0) {
    error_log("Error uploading file: No file uploaded or upload error.");
    sendResponse(false, "Error uploading file: No file uploaded or upload error.");
}
error_log("File uploaded successfully.");

// Validate file type (allow only .xlsx files)
$allowedTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
if (!in_array($_FILES['excel_file']['type'], $allowedTypes)) {
    error_log("Invalid file type. Only .xlsx files are allowed.");
    sendResponse(false, "Invalid file type. Only .xlsx files are allowed.");
}
error_log("File type validation successful.");

// Validate file size (e.g., limit to 5MB)
$maxFileSize = 5 * 1024 * 1024; // 5MB
if ($_FILES['excel_file']['size'] > $maxFileSize) {
    error_log("File size exceeds the maximum limit of 5MB.");
    sendResponse(false, "File size exceeds the maximum limit of 5MB.");
}
error_log("File size validation successful.");

// Get competition ID from POST data
$competition_id = isset($_POST['competition_id']) ? intval($_POST['competition_id']) : 0;
if ($competition_id <= 0) {
    error_log("Invalid competition ID.");
    sendResponse(false, "Invalid competition ID");
}
error_log("Competition ID validation successful.");

// Load the uploaded Excel file
$tmpFilePath = $_FILES['excel_file']['tmp_name'];
try {
    error_log("Attempting to load Excel file...");
    $spreadsheet = IOFactory::load($tmpFilePath);
    error_log("Excel file loaded successfully.");
} catch (Exception $e) {
    error_log("Invalid Excel file: " . $e->getMessage());
    sendResponse(false, "Invalid Excel file: " . $e->getMessage());
}

// Get the active sheet and convert it to an array
$sheet = $spreadsheet->getActiveSheet();
$data = $sheet->toArray();

// Check if the file has at least one data row (excluding headers)
if (count($data) < 2) {
    error_log("No data found in file.");
    sendResponse(false, "No data found in file");
}
error_log("Data found in file.");

// Begin transaction for atomic insert
if (!pg_query($conn, "BEGIN")) {
    error_log("Failed to start transaction: " . pg_last_error($conn));
    sendResponse(false, "Failed to start transaction: " . pg_last_error($conn));
}
error_log("Transaction started successfully.");

$allValid = true;
$errorMessage = "";

// Loop through rows (skip the header row)
for ($i = 1; $i < count($data); $i++) {
    $row = $data[$i];

    // Skip empty rows
    if (empty(array_filter($row))) {
        error_log("Skipping empty row at index " . $i);
        continue;
    }

    // Validate row format (expecting 8 columns)
    if (count($row) < 8) {
        $allValid = false;
        $errorMessage = "Invalid row format at row " . ($i + 1) . ". Expected 8 columns.";
        error_log($errorMessage);
        break;
    }

    // Extract data from the row
    list($student_id, $name, $class, $phno, $division, $rollno, $email, $rawRankStatus) = $row;

    // Validate required fields
    if (empty($student_id) || empty($name) || empty($class) || empty($phno) || empty($division) || empty($rollno) || empty($email) ||
        ($rawRankStatus === null || $rawRankStatus === "")) {
        $allValid = false;
        $errorMessage = "Missing data at row " . ($i + 1);
        error_log($errorMessage);
        break;
    }

    // Validate phone number (must be exactly 10 digits)
    if (!preg_match('/^\d{10}$/', $phno)) {
        $allValid = false;
        $errorMessage = "Invalid phone number at row " . ($i + 1);
        error_log($errorMessage);
        break;
    }

    // Convert data types and sanitize if needed
    $student_id = intval($student_id);
    $rollno = intval($rollno);
    $rank_status = strval($rawRankStatus);

    // Prepare the SQL query for insertion
    $query = "INSERT INTO stdata (student_id, name, class, phno, division, rollno, email, rank_status, id) 
              VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)";
    $params = [$student_id, $name, $class, $phno, $division, $rollno, $email, $rank_status, $competition_id];

    // Execute the query using prepared statements
    error_log("Attempting to insert row " . ($i + 1) . " into the database...");
    $result = pg_query_params($conn, $query, $params);

    if (!$result) {
        $allValid = false;
        $errorMessage = "Database error at row " . ($i + 1) . ": " . pg_last_error($conn);
        error_log($errorMessage);
        break;
    }
    error_log("Row " . ($i + 1) . " inserted successfully.");
}

// Commit or rollback the transaction based on validation
if ($allValid) {
    error_log("All rows inserted successfully. Committing transaction...");
    if (!pg_query($conn, "COMMIT")) {
        error_log("Failed to commit transaction: " . pg_last_error($conn));
        sendResponse(false, "Failed to commit transaction: " . pg_last_error($conn));
    }
    error_log("Transaction committed successfully.");
    sendResponse(true);
} else {
    error_log("Error encountered. Rolling back transaction...");
    if (!pg_query($conn, "ROLLBACK")) {
        error_log("Failed to rollback transaction: " . pg_last_error($conn));
        sendResponse(false, "Failed to rollback transaction: " . pg_last_error($conn));
    }
    error_log("Transaction rolled back successfully.");
    sendResponse(false, $errorMessage);
}

// Close the connection
pg_close($conn);
error_log("Database connection closed.");
?>