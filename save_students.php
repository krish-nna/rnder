<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'db_config.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");
if (!$conn) {
    echo json_encode(["success" => false, "error" => "Database connection failed"]);
    exit;
}

if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] != 0) {
    echo json_encode(["success" => false, "error" => "Error uploading file"]);
    exit;
}

// Check file type
$fileType = mime_content_type($_FILES['excel_file']['tmp_name']);
$allowedTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
$fileExtension = pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION);

if (!in_array($fileType, $allowedTypes) || strtolower($fileExtension) !== 'xlsx') {
    echo json_encode(["success" => false, "error" => "Invalid file format. Please upload a valid .xlsx file"]);
    exit;
}

$competition_id = isset($_POST['competition_id']) ? intval($_POST['competition_id']) : 0;
if ($competition_id <= 0) {
    echo json_encode(["success" => false, "error" => "Invalid competition ID"]);
    exit;
}

$tmpFilePath = $_FILES['excel_file']['tmp_name'];
try {
    $spreadsheet = IOFactory::load($tmpFilePath);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => "Invalid Excel file: " . $e->getMessage()]);
    exit;
}

$sheet = $spreadsheet->getActiveSheet();
$data = $sheet->toArray();

if (count($data) < 2) {
    echo json_encode(["success" => false, "error" => "No data found in file"]);
    exit;
}

pg_query($conn, "BEGIN");

$allValid = true;
$errorMessage = "";

for ($i = 1; $i < count($data); $i++) {
    $row = array_map('trim', $data[$i]); // Trim all fields
    if (empty(array_filter($row))) continue; // Skip empty rows

    if (count($row) < 8) {
        $allValid = false;
        $errorMessage = "Invalid row format at row " . ($i + 1) . ". Expected 8 columns.";
        break;
    }

    list($student_id, $name, $class, $phno, $division, $rollno, $email, $rank_status) = $row;

    if (empty($student_id) || empty($name) || empty($class) || empty($phno) || empty($division) || empty($rollno) || empty($email) || $rank_status === '') {
        $allValid = false;
        $errorMessage = "Missing data at row " . ($i + 1);
        break;
    }

    if (!preg_match('/^\d{10}$/', $phno)) {
        $allValid = false;
        $errorMessage = "Invalid phone number at row " . ($i + 1);
        break;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $allValid = false;
        $errorMessage = "Invalid email format at row " . ($i + 1);
        break;
    }

    $student_id = intval($student_id);
    $rollno = intval($rollno);
    $rank_status = strval($rank_status);

    $query = "INSERT INTO stdata (student_id, name, class, phno, division, rollno, email, rank_status, id) 
              VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)";
    $params = [$student_id, $name, $class, $phno, $division, $rollno, $email, $rank_status, $competition_id];

    $result = pg_query_params($conn, $query, $params);

    if (!$result) {
        $allValid = false;
        $errorMessage = "Database error at row " . ($i + 1) . ": " . pg_last_error($conn);
        break;
    }
}

if ($allValid) {
    pg_query($conn, "COMMIT");
    echo json_encode(["success" => true]);
} else {
    pg_query($conn, "ROLLBACK");
    echo json_encode(["success" => false, "error" => $errorMessage]);
}

pg_close($conn);
?>
