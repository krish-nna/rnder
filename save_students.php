<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require 'db_config.php';

require 'vendor/autoload.php'; // Ensure the path is correct

use PhpOffice\PhpSpreadsheet\IOFactory;

// Establish PostgreSQL connection using configuration variables
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");
if (!$conn) {
    echo json_encode(["success" => false, "error" => "Database connection failed"]);
    exit;
}

// Check if the file was uploaded successfully
if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] != 0) {
    echo json_encode(["success" => false, "error" => "Error uploading file"]);
    exit;
}

// Get competition ID from POST data
$competition_id = isset($_POST['competition_id']) ? intval($_POST['competition_id']) : 0;
if ($competition_id <= 0) {
    echo json_encode(["success" => false, "error" => "Invalid competition ID"]);
    exit;
}

// Load the uploaded Excel file
$tmpFilePath = $_FILES['excel_file']['tmp_name'];
try {
    $spreadsheet = IOFactory::load($tmpFilePath);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => "Invalid Excel file: " . $e->getMessage()]);
    exit;
}

// Get the active sheet and convert it to an array
$sheet = $spreadsheet->getActiveSheet();
$data = $sheet->toArray();

// Check if the file has at least one data row (excluding headers)
if (count($data) < 2) {
    echo json_encode(["success" => false, "error" => "No data found in file"]);
    exit;
}

// Begin transaction for atomic insert
pg_query($conn, "BEGIN");

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

    // Extract data from the row; Excel columns in order:
    // student_id, name, class, phno, division, rollno, email, rank_status
    list($student_id, $name, $class, $phno, $division, $rollno, $email, $rawRankStatus) = $row;

    // Validate required fields except rank_status.
    // For rank_status, allow a value of 0. Check explicitly for null or empty string.
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
    // Convert rank_status to string (as the enum expects string values)
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
    pg_query($conn, "COMMIT");
    echo json_encode(["success" => true]);
} else {
    pg_query($conn, "ROLLBACK");
    echo json_encode(["success" => false, "error" => $errorMessage]);
}

// Close the connection
pg_close($conn);
?>