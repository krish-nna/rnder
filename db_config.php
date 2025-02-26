<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get environment variables
$host = getenv('PGHOST') ?: 'metro.proxy.rlwy.net';
$dbname = getenv('PGDATABASE') ?: 'railway';
$user = getenv('PGUSER') ?: 'postgres';
$password = getenv('PGPASSWORD') ?: 'BSIoqAqteGwgiUvpCSepmCyNaiojnYFM';
$port = getenv('PGPORT') ?: '12302';

// Log the environment variables for debugging
error_log("PGHOST: " . ($host ? $host : "NOT SET"));
error_log("PGDATABASE: " . ($dbname ? $dbname : "NOT SET"));
error_log("PGUSER: " . ($user ? $user : "NOT SET"));
error_log("PGPASSWORD: " . ($password ? "***" : "NOT SET")); // Mask password for security
error_log("PGPORT: " . ($port ? $port : "NOT SET"));

// Validate environment variables
if (!$host || !$dbname || !$user || !$password || !$port) {
    die("Database configuration is missing. Please check your environment variables.");
}

// Create a PDO connection
try {
    $conn = new PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    error_log("Database connection successful!");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>