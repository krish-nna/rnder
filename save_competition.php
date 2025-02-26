<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the common database configuration
require_once 'db_config.php';

// Read and decode JSON input
$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data["name"], $data["category"], $data["college"], $data["year"])) {
    exit(json_encode(["error" => "Invalid or missing data", "received" => $data]));
}

$name = trim($data["name"]);
$category = trim($data["category"]);
$college = trim($data["college"]);
$year = intval($data["year"]); // Ensure year is an integer

if ($year < 1900 || $year > 2099) {
    exit(json_encode(["error" => "Invalid year value"]));
}

try {
    // Connect to database
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Check if the competition already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM competitions WHERE name = :name");
    $stmt->execute([":name" => $name]);
    if ($stmt->fetchColumn() > 0) {
        exit(json_encode(["error" => "Competition name already exists!"]));
    }

    // Insert the new competition and return the inserted ID
    $stmt = $conn->prepare("
        INSERT INTO competitions (name, category, college, year) 
        VALUES (:name, :category, :college, :year) 
        RETURNING id
    ");
    $stmt->execute([":name" => $name, ":category" => $category, ":college" => $college, ":year" => $year]);
    
    // Fetch the newly inserted ID
    $competitionId = $stmt->fetchColumn();

    echo json_encode(["success" => "Competition added successfully", "competitionId" => $competitionId]);
} catch (PDOException $e) {
    exit(json_encode(["error" => "Database error: " . $e->getMessage()]));
}
?>
