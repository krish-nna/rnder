<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Include database configuration
require_once "db_config.php";

// Get request path
$request = $_GET["action"] ?? null;

switch ($request) {
    case "save_competition":
        require "save_competitions.php";
        break;
    case "fetch_competitions":
        require "fetch_competitions.php";
        break;
    case "save_student":
        require "save_students.php";
        break;
    case "fetch_students":
        require "fetch_students.php";
        break;
    case "delete_student":
        require "delete_students.php";
        break;
    case "update_student":
        require "update_students.php";
        break;
    case "download_template":
        require "download_template.php";
        break;
    default:
        echo json_encode(["error" => "Invalid API request"]);
}
