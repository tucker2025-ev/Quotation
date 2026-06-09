<?php
include '../include/dbconnect.php';
header("Content-Type: application/json");

$device_code_raw = $_GET['device_code'] ?? '';
$device_modal_parts = explode('~~', $device_code_raw);
$device_modal_code  = $device_modal_parts[0] ?? '';

if ($device_modal_code) {
    // Escape the value for safety
    $device_modal_code_escaped = $target_conn->real_escape_string($device_modal_code);

    $sql = "SELECT modal_name FROM device_modal WHERE device_code = '$device_modal_code_escaped'";
    $result = $target_conn->query($sql);

    $options = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $options[] = $row['modal_name'];
        }
    }

    echo json_encode(["success" => true, "options" => $options]);
} else {
    echo json_encode(["success" => false, "options" => []]);
}
