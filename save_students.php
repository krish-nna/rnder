<?php
// CORS Headers and other headers remain the same
header("Access-Control-Allow-Origin: https://complogs.netlify.app");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

ini_set('memory_limit', '256M');
set_time_limit(300);

require 'db_config.php';
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

error_log("Received competition_id: " . $_POST['competition_id']);
error_log("File upload error: " . $_FILES['excel_file']['error']);

function sendResponse($success, $error = "") {
    echo json_encode(["success" => $success, "error" => $error]);
    exit;
}

$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");
if (!$conn) {
    sendResponse(false, "Database connection failed: " . pg_last_error());
}

if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
    sendResponse(false, "Error uploading file.");
}

$allowedTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
if (!in_array($_FILES['excel_file']['type'], $allowedTypes)) {
    sendResponse(false, "Invalid file type. Only .xlsx files are allowed.");
}

if ($_FILES['excel_file']['size'] > 5 * 1024 * 1024) {
    sendResponse(false, "File size exceeds the maximum limit of 5MB.");
}

$competition_id = isset($_POST['competition_id']) ? intval($_POST['competition_id']) : 0;
if ($competition_id <= 0) {
    sendResponse(false, "Invalid competition ID.");
}

$tmpFilePath = $_FILES['excel_file']['tmp_name'];
try {
    $spreadsheet = IOFactory::load($tmpFilePath);
} catch (Exception $e) {
    sendResponse(false, "Invalid Excel file: " . $e->getMessage());
}

$sheet = $spreadsheet->getActiveSheet();
$data = $sheet->toArray();

if (count($data) < 2) {
    sendResponse(false, "No data found in file.");
}

pg_query($conn, "BEGIN");

for ($i = 1; $i < count($data); $i++) {
    $row = $data[$i];
    
    // Improved empty row detection
    $isEmptyRow = true;
    foreach ($row as $cell) {
        if (!empty(trim($cell))) {
            $isEmptyRow = false;
            break;
        }
    }
    
    if ($isEmptyRow) {
        error_log("Skipping row " . ($i + 1) . ": Empty row.");
        continue;
    }

    if (count($row) < 8) {
        pg_query($conn, "ROLLBACK");
        sendResponse(false, "Invalid row format at row " . ($i + 1));
    }

    list($student_id, $name, $class, $phno, $division, $rollno, $email, $rank_status) = $row;

    if (!is_numeric($rollno)) {
        error_log("Skipping row " . ($i + 1) . ": Invalid roll number. Value: " . $rollno);
        continue;
    }
    $rollno = intval($rollno);

    if (empty($student_id) || empty($name) || empty($class) || empty($phno) || empty($division) || empty($email) || empty($rank_status)) {
        error_log("Skipping row " . ($i + 1) . ": Missing data. Row data: " . implode(", ", $row));
        continue;
    }

    if (!preg_match('/^\d{10}$/', $phno)) {
        error_log("Skipping row " . ($i + 1) . ": Invalid phone number. Value: " . $phno);
        continue;
    }

    $query = "INSERT INTO stdata (student_id, name, class, phno, division, rollno, email, rank_status, id) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)";
    $params = [$student_id, $name, $class, $phno, $division, $rollno, $email, $rank_status, $competition_id];
    
    if (!pg_query_params($conn, $query, $params)) {
        error_log("Database error at row " . ($i + 1) . ": " . pg_last_error($conn));
        continue;
    } else {
        error_log("Successfully inserted row " . ($i + 1));
    }
}

pg_query($conn, "COMMIT");
sendResponse(true);

pg_close($conn);
?>
