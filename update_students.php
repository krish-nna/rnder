<?php
header('Content-Type: application/json');

require_once 'db_config.php';
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    echo json_encode(["success" => false, "error" => "Database connection failed"]);
    exit;
}

$student_id = isset($_POST['tid']) ? intval($_POST['tid']) : 0;
$name = $_POST['name'] ?? '';
$class = $_POST['class'] ?? '';
$phno = $_POST['phno'] ?? '';
$division = $_POST['division'] ?? '';
$rollno = isset($_POST['rollno']) ? intval($_POST['rollno']) : 0;
$email = $_POST['email'] ?? '';
$rank_status = $_POST['rank_status'] ?? '';

if ($student_id == 0 || empty($name) || empty($class) || empty($phno) || empty($division) || empty($rollno) || empty($email) || $rank_status === '') {
    echo json_encode(["success" => false, "error" => "All fields are required"]);
    pg_close($conn);
    exit;
}

$query = "UPDATE stdata 
          SET name = $1, class = $2, phno = $3, division = $4, rollno = $5, email = $6, rank_status = $7 
          WHERE tid = $8";
$params = [$name, $class, $phno, $division, $rollno, $email, $rank_status, $student_id];

$result = pg_query_params($conn, $query, $params);

if ($result) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => "Update failed: " . pg_last_error($conn)]);
}

pg_close($conn);
?>