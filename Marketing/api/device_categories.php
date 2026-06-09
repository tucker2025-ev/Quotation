<?php
include '../include/dbconnect.php';
header("Content-Type: application/json");

$response = ["success" => false, "device_types" => []];

try {
    // --- Fetch Device Categories ---
    $sql = "SELECT code, label FROM device_categories WHERE active = 1 ORDER BY label ASC";
    $result = $target_conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $response["device_types"][] = [
                "value" => $row["code"] . "~~" . $row["label"],
                "label" => $row["label"]
            ];
        }
        $response["success"] = true;
    }

    $response["success"] = true;
} catch (Exception $e) {
    $response["message"] = $e->getMessage();
}

echo json_encode($response);
