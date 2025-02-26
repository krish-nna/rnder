<?php
header('Content-Type: application/json');

require_once 'db_config.php';
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    echo json_encode(["success" => false, "error" => "Database connection failed"]);
    exit;
}

$student_id = isset($_POST['tid']) ? intval($_POST['tid']) : 0;
if ($student_id <= 0) {
    echo json_encode(["success" => false, "error" => "Invalid record ID"]);
    pg_close($conn);
    exit;
}

$query = "DELETE FROM stdata WHERE tid = $1";
$result = pg_query_params($conn, $query, array($student_id));

if (!$result) {
    echo json_encode(["success" => false, "error" => "Failed to execute deletion: " . pg_last_error($conn)]);
    pg_close($conn);
    exit;
}

$affected_rows = pg_affected_rows($result);
if ($affected_rows > 0) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => "No record found with the given ID "]);
}

pg_close($conn);
?>