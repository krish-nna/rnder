<?php
// CORS Headers
header("Access-Control-Allow-Origin: https://complogs.netlify.app"); // Allow only your frontend
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Include database configuration
require 'db_config.php';

// Include PhpSpreadsheet library
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
error_log("Received competition_id: " . $_POST['competition_id']);
error_log("File upload error: " . $_FILES['excel_file']['error']);
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

// Validate file upload
if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
    sendResponse(false, "Error uploading file.");
}

// Validate file type
$allowedTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
if (!in_array($_FILES['excel_file']['type'], $allowedTypes)) {
    sendResponse(false, "Invalid file type. Only .xlsx files are allowed.");
}

// Validate file size (max 5MB)
if ($_FILES['excel_file']['size'] > 5 * 1024 * 1024) {
    sendResponse(false, "File size exceeds the maximum limit of 5MB.");
}

// Get competition ID
$competition_id = isset($_POST['competition_id']) ? intval($_POST['competition_id']) : 0;
if ($competition_id <= 0) {
    sendResponse(false, "Invalid competition ID.");
}

// Load the Excel file
$tmpFilePath = $_FILES['excel_file']['tmp_name'];
try {
    $spreadsheet = IOFactory::load($tmpFilePath);
} catch (Exception $e) {
    sendResponse(false, "Invalid Excel file: " . $e->getMessage());
}

// Convert sheet data to an array
$sheet = $spreadsheet->getActiveSheet();
$data = $sheet->toArray();

// Validate at least one row of data
if (count($data) < 2) {
    sendResponse(false, "No data found in file.");
}

// Start transaction
pg_query($conn, "BEGIN");

// Process rows
for ($i = 1; $i < count($data); $i++) {
    $row = $data[$i];
    if (empty(array_filter($row))) continue; // Skip empty rows
    if (count($row) < 8) {
        pg_query($conn, "ROLLBACK");
        sendResponse(false, "Invalid row format at row " . ($i + 1));
    }

    list($student_id, $name, $class, $phno, $division, $rollno, $email, $rank_status) = $row;

    if (empty($student_id) || empty($name) || empty($class) || empty($phno) || empty($division) || empty($rollno) || empty($email) || empty($rank_status)) {
        pg_query($conn, "ROLLBACK");
        sendResponse(false, "Missing data at row " . ($i + 1));
    }

    if (!preg_match('/^\d{10}$/', $phno)) {
        pg_query($conn, "ROLLBACK");
        sendResponse(false, "Invalid phone number at row " . ($i + 1));
    }

    $query = "INSERT INTO stdata (student_id, name, class, phno, division, rollno, email, rank_status, id) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)";
    $params = [$student_id, $name, $class, $phno, $division, $rollno, $email, $rank_status, $competition_id];
    
    if (!pg_query_params($conn, $query, $params)) {
        pg_query($conn, "ROLLBACK");
        sendResponse(false, "Database error at row " . ($i + 1) . ": " . pg_last_error($conn));
    }
}

// Commit transaction
pg_query($conn, "COMMIT");
sendResponse(true);

// Close connection
pg_close($conn);
?>
