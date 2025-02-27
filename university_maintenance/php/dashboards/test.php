<?php
require_once("../database/db_connect.php");
include("../../php/common/session_manager.php"); // Include session management script

// Parameters
$database = 'university_maintenance'; // Database name
$table = 'maintenance_requests'; // Table name
$column = 'request_id'; // Column name

// Query to check if column is AUTO_INCREMENT
$sql = "
    SELECT COLUMN_NAME, EXTRA 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = ? 
      AND TABLE_NAME = ? 
      AND COLUMN_NAME = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('sss', $database, $table, $column);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row) {
    if (strpos($row['EXTRA'], 'auto_increment') !== false) {
        echo "The column '{$column}' in table '{$table}' is AUTO_INCREMENT.";
    } else {
        echo "The column '{$column}' in table '{$table}' is NOT AUTO_INCREMENT.";
    }
} else {
    echo "Column '{$column}' not found in table '{$table}'.";
}

$stmt->close();
$conn->close();
?>
