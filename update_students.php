<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

// Database connection
require_once 'db_config.php';
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    echo json_encode(["success" => false, "error" => "Database connection failed"]);
    exit();
}

// Validate and sanitize input
$student_id = isset($_POST['tid']) && ctype_digit($_POST['tid']) ? intval($_POST['tid']) : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$class = isset($_POST['class']) ? trim($_POST['class']) : '';
$phno = isset($_POST['phno']) ? trim($_POST['phno']) : '';
$division = isset($_POST['division']) ? trim($_POST['division']) : '';
$rollno = isset($_POST['rollno']) && ctype_digit($_POST['rollno']) ? intval($_POST['rollno']) : 0;
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$rank_status = isset($_POST['rank_status']) ? trim($_POST['rank_status']) : '';

// Ensure all required fields are filled
if ($student_id === 0 || empty($name) || empty($class) || empty($phno) || empty($division) || $rollno === 0 || empty($email) || $rank_status === '') {
    echo json_encode(["success" => false, "error" => "All fields are required"]);
    pg_close($conn);
    exit();
}

// Update query with parameters
$query = "UPDATE stdata 
          SET name = $1, class = $2, phno = $3, division = $4, rollno = $5, email = $6, rank_status = $7 
          WHERE tid = $8";
$params = [$name, $class, $phno, $division, $rollno, $email, $rank_status, $student_id];

$result = pg_query_params($conn, $query, $params);

// Send response
if ($result) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => "Update failed", "details" => pg_last_error($conn)]);
}

pg_close($conn);
?>
