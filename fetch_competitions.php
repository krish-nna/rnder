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

  // Fetch competitions grouped by category, sorted by year within each category
  $stmt = $conn->query("
  SELECT category, json_agg(
      json_build_object(
          'id', id,
          'name', name,
          'college', college,
          'year', year
      ) ORDER BY year DESC
  ) AS competitions
  FROM competitions 
  GROUP BY category
  ORDER BY MAX(year) DESC, category ASC
");
$categories = $stmt->fetchAll();

// Fetch distinct filter values
$filters = $conn->query("
  SELECT 
      json_agg(DISTINCT year ORDER BY year DESC) AS years,
      json_agg(DISTINCT category ORDER BY category ASC) AS categories,
      json_agg(DISTINCT college ORDER BY college ASC) AS colleges
  FROM competitions
")->fetch();

// Prepare response
$response = [
  "categories" => $categories ?: [],
  "filters" => [
      "years" => $filters['years'] ?: [],
      "categories" => $filters['categories'] ?: [],
      "colleges" => $filters['colleges'] ?: []
  ]
];

}    
?>
