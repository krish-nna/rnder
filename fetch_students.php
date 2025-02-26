<?php
header("Access-Control-Allow-Origin: https://complogs.netlify.app"); // Allow requests from your frontend only
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if PostgreSQL is installed
if (!function_exists('pg_connect')) {
    die(json_encode(["success" => false, "error" => "PostgreSQL extension is not installed!"]));
}

// Set JSON response header
header('Content-Type: application/json');

// Include database configuration file
require 'db_config.php';

// Check if config variables are set correctly
if (!$host || !$port || !$dbname || !$user || !$password) {
    echo json_encode(["success" => false, "error" => "Database configuration missing!"]);
    exit;
}

// Connect to PostgreSQL
$conn = pg_connect("host=$host port=$port dbname=$dbname user=$user password=$password");

if (!$conn) {
    echo json_encode(["success" => false, "error" => "Database connection failed: " . pg_last_error()]);
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

// Check if `stdata` table exists (debugging step)
$table_check = pg_query($conn, "SELECT to_regclass('public.stdata')");
$table_exists = pg_fetch_result($table_check, 0, 0);

if (!$table_exists) {
    echo json_encode(["success" => false, "error" => "Table 'stdata' does not exist in the database"]);
    exit;
}

// Base query: get students associated with a given competition
$sql = "SELECT * FROM stdata WHERE id = $1";
$params = [$compId];

// Apply class filter if provided
if (!empty($filter_class) && strtolower($filter_class) !== "all") {
    $sql .= " AND class = $" . (count($params) + 1);
    $params[] = $filter_class;
}

// Apply rank filter logic
if (strtolower($filter_rank) === "top3") {
    $sql .= " AND rank_status IN ('1', '2', '3')";
}

// Debugging: Log the query
error_log("SQL Query: " . $sql);
error_log("Query Parameters: " . json_encode($params));

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

// Close database connection
pg_close($conn);
?>
