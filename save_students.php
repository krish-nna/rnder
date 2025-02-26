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
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");
if (!$conn) {
    sendResponse(false, "Database connection failed: " . pg_last_error());
}

// Check if the file was uploaded successfully
if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] != 0) {
    sendResponse(false, "Error uploading file: No file uploaded or upload error.");
}

// Validate file type (allow only .xlsx files)
$allowedTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
if (!in_array($_FILES['excel_file']['type'], $allowedTypes)) {
    sendResponse(false, "Invalid file type. Only .xlsx files are allowed.");
}

// Validate file size (e.g., limit to 5MB)
$maxFileSize = 5 * 1024 * 1024; // 5MB
if ($_FILES['excel_file']['size'] > $maxFileSize) {
    sendResponse(false, "File size exceeds the maximum limit of 5MB.");
}

// Get competition ID from POST data
$competition_id = isset($_POST['competition_id']) ? intval($_POST['competition_id']) : 0;
if ($competition_id <= 0) {
    sendResponse(false, "Invalid competition ID");
}

// Load the uploaded Excel file
$tmpFilePath = $_FILES['excel_file']['tmp_name'];
try {
    $spreadsheet = IOFactory::load($tmpFilePath);
} catch (Exception $e) {
    sendResponse(false, "Invalid Excel file: " . $e->getMessage());
}

// Get the active sheet and convert it to an array
$sheet = $spreadsheet->getActiveSheet();
$data = $sheet->toArray();

// Check if the file has at least one data row (excluding headers)
if (count($data) < 2) {
    sendResponse(false, "No data found in file");
}

// Begin transaction for atomic insert
if (!pg_query($conn, "BEGIN")) {
    sendResponse(false, "Failed to start transaction: " . pg_last_error($conn));
}

$allValid = true;
$errorMessage = "";

// Loop through rows (skip the header row)
for ($i = 1; $i < count($data); $i++) {
    $row = $data[$i];

    // Skip empty rows
    if (empty(array_filter($row))) {
        continue;
    }

    // Validate row format (expecting 8 columns)
    if (count($row) < 8) {
        $allValid = false;
        $errorMessage = "Invalid row format at row " . ($i + 1) . ". Expected 8 columns.";
        break;
    }

    // Extract data from the row
    list($student_id, $name, $class, $phno, $division, $rollno, $email, $rawRankStatus) = $row;

    // Validate required fields
    if (empty($student_id) || empty($name) || empty($class) || empty($phno) || empty($division) || empty($rollno) || empty($email) ||
        ($rawRankStatus === null || $rawRankStatus === "")) {
        $allValid = false;
        $errorMessage = "Missing data at row " . ($i + 1);
        break;
    }

    // Validate phone number (must be exactly 10 digits)
    if (!preg_match('/^\d{10}$/', $phno)) {
        $allValid = false;
        $errorMessage = "Invalid phone number at row " . ($i + 1);
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
    $result = pg_query_params($conn, $query, $params);

    if (!$result) {
        $allValid = false;
        $errorMessage = "Database error at row " . ($i + 1) . ": " . pg_last_error($conn);
        break;
    }
}

// Commit or rollback the transaction based on validation
if ($allValid) {
    if (!pg_query($conn, "COMMIT")) {
        sendResponse(false, "Failed to commit transaction: " . pg_last_error($conn));
    }
    sendResponse(true);
} else {
    if (!pg_query($conn, "ROLLBACK")) {
        sendResponse(false, "Failed to rollback transaction: " . pg_last_error($conn));
    }
    sendResponse(false, $errorMessage);
}

// Close the connection
pg_close($conn);
?>