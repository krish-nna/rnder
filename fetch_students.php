<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set the response content type to JSON
header('Content-Type: application/json');

// Include database configuration file
require 'db_config.php';

// Connect to the PostgreSQL database
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    echo json_encode(["success" => false, "error" => "Database connection failed"]);
    exit;
}

// Get parameters from query string
$compId = isset($_GET['compId']) ? intval($_GET['compId']) : 0;
$filter_class = isset($_GET['filter_class']) ? trim($_GET['filter_class']) : "";
$filter_rank = isset($_GET['filter_rank']) ? trim($_GET['filter_rank']) : "";

// Validate competition ID
if ($compId <= 0) {
    echo json_encode(["success" => false, "error" => "Invalid competition ID"]);
    exit;
}

// Base query: get students associated with a given competition (foreign key 'id')
$sql = "SELECT * FROM stdata WHERE id = $1";
$params = [$compId];

// Apply class filter if provided (and not "all")
if (!empty($filter_class) && strtolower($filter_class) !== "all") {
    $sql .= " AND class = $" . (count($params) + 1);
    $params[] = $filter_class;
}

// Apply rank filter logic
if (strtolower($filter_rank) === "top3") {
    $sql .= " AND rank_status IN ('1', '2', '3')";
}

// Execute query with parameters
$result = pg_query_params($conn, $sql, $params);

if (!$result) {
    echo json_encode(["success" => false, "error" => "Query error: " . pg_last_error($conn)]);
    pg_close($conn);
    exit;
}

// Fetch all rows as an associative array
$students = pg_fetch_all($result);

// Handle empty result set
if ($students === false) {
    $students = []; // Ensure JSON response always contains an array
}

// Return JSON response
echo json_encode(["success" => true, "students" => $students]);

// Close the database connection
pg_close($conn);
?><?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set the response content type to JSON
header('Content-Type: application/json');

// Include database configuration file
require 'db_config.php';

// Connect to the PostgreSQL database
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    echo json_encode(["success" => false, "error" => "Database connection failed"]);
    exit;
}

// Get parameters from query string
$compId = isset($_GET['compId']) ? intval($_GET['compId']) : 0;
$filter_class = isset($_GET['filter_class']) ? trim($_GET['filter_class']) : "";
$filter_rank = isset($_GET['filter_rank']) ? trim($_GET['filter_rank']) : "";

// Validate competition ID
if ($compId <= 0) {
    echo json_encode(["success" => false, "error" => "Invalid competition ID"]);
    exit;
}

// Validate and sanitize filters
$allowed_classes = ["10A", "10B", "11A", "11B"]; // Example allowed classes
if (!empty($filter_class) && !in_array($filter_class, $allowed_classes)) {
    echo json_encode(["success" => false, "error" => "Invalid class filter"]);
    exit;
}

if (!empty($filter_rank) && strtolower($filter_rank) !== "top3") {
    echo json_encode(["success" => false, "error" => "Invalid rank filter"]);
    exit;
}

// Base query: get students associated with a given competition (foreign key 'id')
$sql = "SELECT * FROM stdata WHERE id = $1";
$params = [$compId];

// Apply class filter if provided (and not "all")
if (!empty($filter_class) && strtolower($filter_class) !== "all") {
    $sql .= " AND class = $" . (count($params) + 1);
    $params[] = $filter_class;
}

// Apply rank filter logic
if (strtolower($filter_rank) === "top3") {
    $sql .= " AND rank_status IN ('1', '2', '3')";
}

// Execute query with parameters
$result = pg_query_params($conn, $sql, $params);

if (!$result) {
    echo json_encode(["success" => false, "error" => "Query error: " . pg_last_error($conn)]);
    pg_close($conn);
    exit;
}

// Fetch all rows as an associative array
$students = pg_fetch_all($result);

// Handle empty result set
if ($students === false) {
    $students = []; // Ensure JSON response always contains an array
}

// Return JSON response
echo json_encode(["success" => true, "students" => $students]);

// Close the database connection
pg_close($conn);
?>