<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_GET['file'])) {
    die("No file specified.");
}

$file_path = $_GET['file'];

if (file_exists($file_path)) {
    // Set headers for file download
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
    header('Content-Length: ' . filesize($file_path));
    readfile($file_path);
    exit;
} else {
    die("File not found.");
}
?>
