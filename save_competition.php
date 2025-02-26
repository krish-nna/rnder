
<?php
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
    // Check if the competition already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM competitions WHERE name = :name");
    $stmt->execute([":name" => $name]);
    if ($stmt->fetchColumn() > 0) {
        exit(json_encode(["error" => "Competition name already exists!"]));
    }

    // Insert the new competition
    $stmt = $conn->prepare("INSERT INTO competitions (name, category, college, year) VALUES (:name, :category, :college, :year)");
    $stmt->execute([":name" => $name, ":category" => $category, ":college" => $college, ":year" => $year]);
    
    // Retrieve the competition id from the database (PostgreSQL specific)
    $competitionId = $conn->lastInsertId();

    echo json_encode(["success" => "Competition added successfully", "competitionId" => $competitionId]);
} catch (PDOException $e) {
    exit(json_encode(["error" => "Database error: " . $e->getMessage()]));
}
?>



