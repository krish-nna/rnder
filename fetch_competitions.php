<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

// Include database configuration
require_once 'db_config.php';

try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Get filters from request
    $compId = isset($_GET['compId']) ? (int) $_GET['compId'] : null;
    $filter_class = isset($_GET['filter_class']) ? trim($_GET['filter_class']) : '';
    $filter_rank = isset($_GET['filter_rank']) ? trim($_GET['filter_rank']) : '';

    if (!$compId) {
        echo json_encode(["error" => "Missing competition ID"]);
        exit();
    }

    // Build query dynamically
    $query = "SELECT * FROM students WHERE competition_id = :compId";
    $params = [':compId' => $compId];

    if (!empty($filter_class)) {
        $query .= " AND class = :filter_class";
        $params[':filter_class'] = $filter_class;
    }

    if ($filter_rank === 'all') {
        // No rank filtering needed
    } elseif ($filter_rank === 'top3') {
        $query .= " AND rank <= 3";
    } else {
        echo json_encode(["error" => "Invalid rank filter"]);
        exit();
    }

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $students = $stmt->fetchAll();

    echo json_encode(["students" => $students], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>
