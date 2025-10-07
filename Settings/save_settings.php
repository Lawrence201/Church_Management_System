<?php
header('Content-Type: application/json');
date_default_timezone_set('Africa/Accra');

function log_message($message)
{
    $log_file = 'debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "smart_attendance_system";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    log_message("DB connection failed: " . $conn->connect_error);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save working hours
    $start_time = $_POST['start_time'] ?? null;
    $end_time = $_POST['end_time'] ?? null;

    if (!$start_time || !preg_match('/^\d{2}:\d{2}$/', $start_time)) {
        echo json_encode(['success' => false, 'message' => 'Invalid start time format']);
        exit;
    }
    if (!$end_time || !preg_match('/^\d{2}:\d{2}$/', $end_time)) {
        echo json_encode(['success' => false, 'message' => 'Invalid end time format']);
        exit;
    }

    $start_time .= ':00';
    $end_time .= ':00';

    $stmt1 = $conn->prepare("INSERT INTO settings (setting_name, setting_value) VALUES ('working_hours_start', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt1->bind_param("ss", $start_time, $start_time);
    $stmt1->execute();
    $stmt1->close();

    $stmt2 = $conn->prepare("INSERT INTO settings (setting_name, setting_value) VALUES ('working_hours_end', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt2->bind_param("ss", $end_time, $end_time);
    $stmt2->execute();
    $stmt2->close();

    log_message("Saved start: $start_time, end: $end_time");
    echo json_encode(['success' => true, 'message' => 'Working hours updated successfully']);
} else {
    // Return saved values
    $result = $conn->query("SELECT setting_name, setting_value FROM settings WHERE setting_name IN ('working_hours_start', 'working_hours_end')");
    $response = ['success' => true];
    while ($row = $result->fetch_assoc()) {
        $key = $row['setting_name'] === 'working_hours_start' ? 'start_time' : 'end_time';
        $response[$key] = substr($row['setting_value'], 0, 5);
    }
    echo json_encode($response);
}

$conn->close();
