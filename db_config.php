<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get environment variables securely
$host = getenv('PGHOST');
$dbname = getenv('PGDATABASE');
$user = getenv('PGUSER');
$password = getenv('PGPASSWORD');
$port = getenv('PGPORT');

// Ensure all necessary environment variables are set
if (!$host || !$dbname || !$user || !$password || !$port) {
    error_log("Database configuration is missing.");
    die("Database configuration error. Please check environment variables.");
}

// Log only non-sensitive environment variables for debugging
error_log("PGHOST: $host");
error_log("PGDATABASE: $dbname");
error_log("PGUSER: $user");
error_log("PGPORT: $port");

// Create a secure PDO connection
try {
    $conn = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    error_log("Database connection successful.");
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection error. Please try again later.");
}
?>
